<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Classes\Common;
use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Examyou\RestAPI\ApiResponse;
use Examyou\RestAPI\Exceptions\ApiException;
use Illuminate\Support\Facades\DB;

class AccountingController extends ApiBaseController
{
    // ─── RESPONSE HELPERS ─────────────────────────────────────────────────

    protected function sendResponse($data, $message)
    {
        return ApiResponse::make($message, $data);
    }

    protected function sendError($message)
    {
        throw new ApiException($message);
    }

    // ─── CHART OF ACCOUNTS ────────────────────────────────────────────────

    public function coaIndex(Request $request)
    {
        $companyId = company()->id;
        $accounts = ChartOfAccount::where('company_id', $companyId)
            ->orderBy('account_code')
            ->get();

        // Build tree
        $map = [];
        foreach ($accounts as $a) {
            $map[$a->id] = $a->toArray();
            $map[$a->id]['children'] = [];
        }
        $tree = [];
        foreach ($map as &$node) {
            if ($node['parent_id'] && isset($map[$node['parent_id']])) {
                $map[$node['parent_id']]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }
        return $this->sendResponse(['data' => $tree, 'flat' => $accounts], '');
    }

    public function coaStore(Request $request)
    {
        $request->validate([
            'account_code' => 'required|string|max:20',
            'account_name' => 'required|string|max:150',
            'account_type' => 'required|in:Asset,Liability,Equity,Income,Expense,COGS',
        ]);
        $companyId = company()->id;
        $account = ChartOfAccount::create([
            'company_id'   => $companyId,
            'account_code' => $request->account_code,
            'account_name' => $request->account_name,
            'account_type' => $request->account_type,
            'parent_id'    => $request->parent_id ?: null,
            'description'  => $request->description,
            'status'       => 1,
        ]);
        return $this->sendResponse(['data' => $account], __('messages.created_successfully', ['name' => 'Account']));
    }

    public function coaUpdate(Request $request, $id)
    {
        $account = ChartOfAccount::findOrFail($id);
        $account->update($request->only(['account_code', 'account_name', 'account_type', 'parent_id', 'description', 'status']));
        return $this->sendResponse(['data' => $account], __('messages.updated_successfully', ['name' => 'Account']));
    }

    public function coaDestroy($id)
    {
        $account = ChartOfAccount::findOrFail($id);
        if (JournalEntryLine::where('account_id', $id)->exists()) {
            return $this->sendError('Cannot delete account with journal entries.');
        }
        $account->delete();
        return $this->sendResponse([], __('messages.deleted_successfully', ['name' => 'Account']));
    }

    // ─── JOURNAL ENTRIES ──────────────────────────────────────────────────

    public function journalIndex(Request $request)
    {
        $companyId = company()->id;
        $q = JournalEntry::where('company_id', $companyId)
            ->with('lines.account')
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->date_from) $q->where('entry_date', '>=', $request->date_from);
        if ($request->date_to)   $q->where('entry_date', '<=', $request->date_to);

        $entries = $q->paginate($request->per_page ?? 20);
        $data = $entries->toArray();
        $data['data'] = array_map(fn($e) => $this->attachOrderItems($e), $data['data']);
        return $this->sendResponse($data, '');
    }

    private function attachOrderItems(array $entry): array
    {
        if (empty($entry['reference'])) {
            $entry['order_items'] = [];
            return $entry;
        }
        $order = \DB::table('orders')
            ->where('invoice_number', $entry['reference'])
            ->first();
        if (!$order) {
            $entry['order_items'] = [];
            return $entry;
        }
        $entry['order_type'] = $order->order_type ?? null;
        $entry['order_status'] = $order->order_status ?? null;
        $entry['order_items'] = \DB::table('order_items as oi')
            ->join('products as p', 'p.id', '=', 'oi.product_id')
            ->where('oi.order_id', $order->id)
            ->select(
                'p.name as product_name',
                'p.item_code',
                'oi.quantity',
                'oi.unit_price',
                'oi.subtotal',
                'oi.discount_rate',
                'oi.total_discount',
                'oi.total_tax'
            )
            ->get()
            ->toArray();
        return $entry;
    }

    public function journalStore(Request $request)
    {
        $request->validate([
            'entry_date'  => 'required|date',
            'lines'       => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.debit'      => 'required|numeric|min:0',
            'lines.*.credit'     => 'required|numeric|min:0',
        ]);

        $totalDebit  = collect($request->lines)->sum('debit');
        $totalCredit = collect($request->lines)->sum('credit');
        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            return $this->sendError('Debit and Credit must be equal. Debit: ' . $totalDebit . ', Credit: ' . $totalCredit);
        }

        $companyId = company()->id;
        $entryNumber = 'JE-' . date('Ymd') . '-' . str_pad(
            JournalEntry::where('company_id', $companyId)->count() + 1, 5, '0', STR_PAD_LEFT
        );

        DB::beginTransaction();
        try {
            $entry = JournalEntry::create([
                'company_id'   => $companyId,
                'entry_number' => $entryNumber,
                'entry_date'   => $request->entry_date,
                'reference'    => $request->reference,
                'description'  => $request->description,
                'status'       => 'posted',
                'created_by'   => auth('api')->id(),
            ]);

            foreach ($request->lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id'       => $line['account_id'],
                    'description'      => $line['description'] ?? null,
                    'debit'            => $line['debit'],
                    'credit'           => $line['credit'],
                ]);
            }

            DB::commit();
            return $this->sendResponse(['data' => $entry->load('lines.account')], 'Journal entry created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage());
        }
    }

    public function journalShow($id)
    {
        $entry = JournalEntry::with('lines.account')->findOrFail($id);
        $data = $this->attachOrderItems($entry->toArray());
        return $this->sendResponse(['data' => $data], '');
    }

    public function journalDestroy($id)
    {
        $entry = JournalEntry::findOrFail($id);
        $entry->delete();
        return $this->sendResponse([], 'Journal entry deleted.');
    }

    // ─── REPORTS ──────────────────────────────────────────────────────────

    public function trialBalance(Request $request)
    {
        $companyId = company()->id;
        $dateFrom = $request->date_from ?? '2000-01-01';
        $dateTo   = $request->date_to   ?? now()->toDateString();

        $rows = DB::select("
            SELECT
                coa.id,
                coa.account_code,
                coa.account_name,
                coa.account_type,
                coa.parent_id,
                COALESCE(SUM(jel.debit), 0)  AS total_debit,
                COALESCE(SUM(jel.credit), 0) AS total_credit,
                COALESCE(SUM(jel.debit), 0) - COALESCE(SUM(jel.credit), 0) AS balance
            FROM chart_of_accounts coa
            LEFT JOIN journal_entry_lines jel ON jel.account_id = coa.id
            LEFT JOIN journal_entries je ON je.id = jel.journal_entry_id
                AND je.entry_date BETWEEN ? AND ?
                AND je.status = 'posted'
                AND je.company_id = ?
            WHERE coa.company_id = ?
            GROUP BY coa.id, coa.account_code, coa.account_name, coa.account_type, coa.parent_id
            ORDER BY coa.account_code
        ", [$dateFrom, $dateTo, $companyId, $companyId]);

        $totalDebit  = collect($rows)->sum('total_debit');
        $totalCredit = collect($rows)->sum('total_credit');

        return $this->sendResponse([
            'data'         => $rows,
            'total_debit'  => $totalDebit,
            'total_credit' => $totalCredit,
            'date_from'    => $dateFrom,
            'date_to'      => $dateTo,
        ], '');
    }

    public function profitLoss(Request $request)
    {
        $companyId = company()->id;
        $dateFrom = $request->date_from ?? date('Y-01-01');
        $dateTo   = $request->date_to   ?? now()->toDateString();

        $rows = DB::select("
            SELECT
                coa.account_code,
                coa.account_name,
                coa.account_type,
                COALESCE(SUM(jel.credit - jel.debit), 0) AS net
            FROM chart_of_accounts coa
            LEFT JOIN journal_entry_lines jel ON jel.account_id = coa.id
            LEFT JOIN journal_entries je ON je.id = jel.journal_entry_id
                AND je.entry_date BETWEEN ? AND ?
                AND je.status = 'posted'
                AND je.company_id = ?
            WHERE coa.company_id = ?
            AND coa.account_type IN ('Income','COGS','Expense')
            AND coa.parent_id IS NOT NULL
            GROUP BY coa.id
            ORDER BY coa.account_type, coa.account_code
        ", [$dateFrom, $dateTo, $companyId, $companyId]);

        $revenue   = collect($rows)->where('account_type', 'Income')->sum('net');
        $cogs      = collect($rows)->where('account_type', 'COGS')->sum('net');
        $expenses  = collect($rows)->where('account_type', 'Expense')->sum('net');
        $grossProfit = $revenue - abs($cogs);
        $netProfit   = $grossProfit - abs($expenses);

        return $this->sendResponse([
            'data'          => $rows,
            'total_revenue' => $revenue,
            'total_cogs'    => abs($cogs),
            'gross_profit'  => $grossProfit,
            'total_expenses'=> abs($expenses),
            'net_profit'    => $netProfit,
            'date_from'     => $dateFrom,
            'date_to'       => $dateTo,
        ], '');
    }

    public function balanceSheet(Request $request)
    {
        $companyId = company()->id;
        $asOf = $request->as_of ?? now()->toDateString();

        $rows = DB::select("
            SELECT
                coa.account_code,
                coa.account_name,
                coa.account_type,
                coa.parent_id,
                COALESCE(SUM(jel.debit - jel.credit), 0) AS balance
            FROM chart_of_accounts coa
            LEFT JOIN journal_entry_lines jel ON jel.account_id = coa.id
            LEFT JOIN journal_entries je ON je.id = jel.journal_entry_id
                AND je.entry_date <= ?
                AND je.status = 'posted'
                AND je.company_id = ?
            WHERE coa.company_id = ?
            AND coa.account_type IN ('Asset','Liability','Equity')
            AND coa.parent_id IS NOT NULL
            GROUP BY coa.id
            ORDER BY coa.account_type, coa.account_code
        ", [$asOf, $companyId, $companyId]);

        $assets      = collect($rows)->where('account_type', 'Asset')->sum('balance');
        $liabilities = collect($rows)->where('account_type', 'Liability')->sum(fn($r) => abs($r->balance));
        $equity      = collect($rows)->where('account_type', 'Equity')->sum(fn($r) => abs($r->balance));

        return $this->sendResponse([
            'data'              => $rows,
            'total_assets'      => $assets,
            'total_liabilities' => $liabilities,
            'total_equity'      => $equity,
            'as_of'             => $asOf,
        ], '');
    }

    public function generalLedger(Request $request)
    {
        $request->validate(['account_id' => 'required|exists:chart_of_accounts,id']);
        $companyId = company()->id;
        $dateFrom  = $request->date_from ?? '2000-01-01';
        $dateTo    = $request->date_to   ?? now()->toDateString();

        $account = ChartOfAccount::findOrFail($request->account_id);
        $lines = DB::select("
            SELECT
                je.entry_date,
                je.entry_number,
                je.description AS je_description,
                jel.description,
                jel.debit,
                jel.credit
            FROM journal_entry_lines jel
            JOIN journal_entries je ON je.id = jel.journal_entry_id
            WHERE jel.account_id = ?
            AND je.entry_date BETWEEN ? AND ?
            AND je.status = 'posted'
            AND je.company_id = ?
            ORDER BY je.entry_date, je.id
        ", [$request->account_id, $dateFrom, $dateTo, $companyId]);

        $runningBalance = 0;
        foreach ($lines as &$line) {
            $runningBalance += ($line->debit - $line->credit);
            $line->running_balance = $runningBalance;
        }

        return $this->sendResponse([
            'account'   => $account,
            'lines'     => $lines,
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ], '');
    }

    // ─── CUSTOMER LEDGER ──────────────────────────────────────────────────

    public function customerLedger(Request $request)
    {
        $companyId = company()->id;
        $dateFrom  = $request->date_from ?? '2000-01-01';
        $dateTo    = $request->date_to   ?? now()->toDateString();
        $userId    = $request->user_id ? Common::getIdFromHash($request->user_id) : null;

        // ── Opening Balance (all transactions BEFORE dateFrom) ───────────────
        $obSalesDebit = Order::where('company_id', $companyId)
            ->whereIn('order_type', ['sales'])
            ->where('cancelled', 0)
            ->where('order_date', '<', $dateFrom)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->sum('total');

        $obReturnCredit = Order::where('company_id', $companyId)
            ->whereIn('order_type', ['sales-returns'])
            ->where('cancelled', 0)
            ->where('order_date', '<', $dateFrom)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->sum('total');

        $obPaymentCredit = Payment::where('company_id', $companyId)
            ->where('payment_type', 'in')
            ->where(DB::raw('DATE(date)'), '<', $dateFrom)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->sum('amount');

        $openingBalance = $obSalesDebit - $obReturnCredit - $obPaymentCredit;

        // ── Period Sales — loaded separately to attach item details ──────────
        $salesOrders = Order::where('company_id', $companyId)
            ->whereIn('order_type', ['sales'])
            ->where('cancelled', 0)
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->with(['orderItems' => fn($q) => $q->with('product:id,name,item_code')])
            ->orderBy('order_date')
            ->orderBy('invoice_number')
            ->get(['id', 'xid', 'order_date', 'invoice_number', 'total', 'user_id']);

        $salesRows = $salesOrders->map(fn($o) => (object)[
            'date'      => $o->order_date,
            'reference' => $o->invoice_number,
            'order_xid' => $o->xid,
            'type'      => 'Sale',
            'debit'     => $o->total,
            'credit'    => 0,
            'user_id'   => $o->user_id,
            'items'     => $o->orderItems->map(fn($i) => [
                'name'      => $i->product->name ?? '-',
                'item_code' => $i->product->item_code ?? '',
                'qty'       => (float)$i->quantity,
                'unit_price'=> (float)$i->unit_price,
                'subtotal'  => (float)$i->subtotal,
            ])->values()->toArray(),
        ]);

        // ── Sales Returns ────────────────────────────────────────────────────
        $returnQuery = Order::select(
                'order_date as date',
                DB::raw("'' as order_xid"),
                'invoice_number as reference',
                DB::raw("'Sales Return' as type"),
                DB::raw('0 as debit'),
                'total as credit',
                'user_id'
            )
            ->where('company_id', $companyId)
            ->whereIn('order_type', ['sales-returns'])
            ->where('cancelled', 0)
            ->whereBetween('order_date', [$dateFrom, $dateTo]);

        // ── Payments in ──────────────────────────────────────────────────────
        $paymentQuery = Payment::select(
                DB::raw('DATE(date) as date'),
                DB::raw("'' as order_xid"),
                'payment_number as reference',
                DB::raw("'Payment Received' as type"),
                DB::raw('0 as debit'),
                'amount as credit',
                'user_id'
            )
            ->where('company_id', $companyId)
            ->where('payment_type', 'in')
            ->whereBetween(DB::raw('DATE(date)'), [$dateFrom, $dateTo]);

        if ($userId) {
            $returnQuery->where('user_id', $userId);
            $paymentQuery->where('user_id', $userId);
        }

        $otherRows = $returnQuery->union($paymentQuery)
            ->orderBy('date')->orderBy('reference')
            ->get()
            ->map(fn($r) => (object)array_merge((array)$r, ['items' => []]));

        // ── Merge + sort + running balance ───────────────────────────────────
        $rows = $salesRows->concat($otherRows)
            ->sortBy([['date', 'asc'], ['reference', 'asc']])
            ->values();

        $runningBalance = $openingBalance;
        foreach ($rows as $row) {
            $runningBalance += ($row->debit - $row->credit);
            $row->running_balance = $runningBalance;
        }

        $customer = $userId ? User::find($userId) : null;

        return $this->sendResponse([
            'rows'            => $rows->values(),
            'opening_balance' => $openingBalance,
            'customer'        => $customer,
            'date_from'       => $dateFrom,
            'date_to'         => $dateTo,
        ], '');
    }

    // ─── SUPPLIER LEDGER ──────────────────────────────────────────────────

    /**
     * Get the effective total for a purchase/GRN order.
     * If order.total > 0 use it directly; otherwise compute from item prices.
     */
    private function effectivePurchaseTotal(int $orderId, float $recordedTotal): float
    {
        if ($recordedTotal > 0) return $recordedTotal;
        return AccountingService::computeEffectiveOrderTotal($orderId);
    }

    public function supplierLedger(Request $request)
    {
        $companyId = company()->id;
        $dateFrom  = $request->date_from ?? '2000-01-01';
        $dateTo    = $request->date_to   ?? now()->toDateString();
        $userId    = $request->user_id ? Common::getIdFromHash($request->user_id) : null;

        // ── Opening Balance — purchases/GRNs before dateFrom ────────────────
        // Load individually to compute effective total (handles GRNs with total=0).
        $obOrders = Order::where('company_id', $companyId)
            ->whereIn('order_type', ['purchases', 'grn'])
            ->where('cancelled', 0)
            ->where('order_date', '<', $dateFrom)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->get(['id', 'total']);

        $obPurchaseCredit = $obOrders->sum(fn($o) => $this->effectivePurchaseTotal($o->id, (float)$o->total));

        $obReturnDebit = Order::where('company_id', $companyId)
            ->whereIn('order_type', ['purchase-returns'])
            ->where('cancelled', 0)
            ->where('order_date', '<', $dateFrom)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->sum('total');

        $obPaymentDebit = Payment::where('company_id', $companyId)
            ->where('payment_type', 'out')
            ->where(DB::raw('DATE(date)'), '<', $dateFrom)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->sum('amount');

        $openingBalance = $obPurchaseCredit - $obReturnDebit - $obPaymentDebit;

        // ── Period Purchases (GRNs + purchase orders) — loaded with items ────
        $purchaseOrders = Order::where('company_id', $companyId)
            ->whereIn('order_type', ['purchases', 'grn'])
            ->where('cancelled', 0)
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->with(['orderItems' => fn($q) => $q->with('product:id,name,item_code')])
            ->orderBy('order_date')
            ->orderBy('invoice_number')
            ->get(['id', 'xid', 'order_date', 'invoice_number', 'total', 'user_id', 'order_type']);

        $purchaseRows = $purchaseOrders->map(function ($o) {
            $effective = $this->effectivePurchaseTotal($o->id, (float)$o->total);
            $label     = $o->order_type === 'grn' ? 'GRN' : 'Purchase';
            return (object)[
                'date'          => $o->order_date,
                'reference'     => $o->invoice_number,
                'order_xid'     => $o->xid,
                'type'          => $label,
                'debit'         => 0,
                'credit'        => $effective,
                'user_id'       => $o->user_id,
                'effective_amt' => $effective,
                'recorded_amt'  => (float)$o->total,
                'items'         => $o->orderItems->map(fn($i) => [
                    'name'      => $i->product->name ?? '-',
                    'item_code' => $i->product->item_code ?? '',
                    'qty'       => (float)$i->quantity,
                    'unit_price'=> (float)$i->unit_price,
                    'subtotal'  => (float)$i->subtotal,
                ])->values()->toArray(),
            ];
        });

        // ── Purchase Returns ─────────────────────────────────────────────────
        $returnQuery = Order::select(
                'order_date as date',
                DB::raw("'' as order_xid"),
                'invoice_number as reference',
                DB::raw("'Purchase Return' as type"),
                'total as debit',
                DB::raw('0 as credit'),
                'user_id'
            )
            ->where('company_id', $companyId)
            ->whereIn('order_type', ['purchase-returns'])
            ->where('cancelled', 0)
            ->whereBetween('order_date', [$dateFrom, $dateTo]);

        // ── Payments out ─────────────────────────────────────────────────────
        $paymentQuery = Payment::select(
                DB::raw('DATE(date) as date'),
                DB::raw("'' as order_xid"),
                'payment_number as reference',
                DB::raw("'Payment Made' as type"),
                'amount as debit',
                DB::raw('0 as credit'),
                'user_id'
            )
            ->where('company_id', $companyId)
            ->where('payment_type', 'out')
            ->whereBetween(DB::raw('DATE(date)'), [$dateFrom, $dateTo]);

        if ($userId) {
            $returnQuery->where('user_id', $userId);
            $paymentQuery->where('user_id', $userId);
        }

        $otherRows = $returnQuery->union($paymentQuery)
            ->orderBy('date')->orderBy('reference')
            ->get()
            ->map(fn($r) => (object)array_merge((array)$r, ['items' => [], 'effective_amt' => null]));

        // ── Merge + sort + running balance ───────────────────────────────────
        $rows = $purchaseRows->concat($otherRows)
            ->sortBy([['date', 'asc'], ['reference', 'asc']])
            ->values();

        $runningBalance = $openingBalance;
        foreach ($rows as $row) {
            $runningBalance += ($row->credit - $row->debit);
            $row->running_balance = $runningBalance;
        }

        $supplier = $userId ? User::find($userId) : null;

        return $this->sendResponse([
            'rows'            => $rows->values(),
            'opening_balance' => $openingBalance,
            'supplier'        => $supplier,
            'date_from'       => $dateFrom,
            'date_to'         => $dateTo,
        ], '');
    }

    // ─── CATEGORY ACCOUNTING MAPPING ──────────────────────────────────────

    /**
     * GET /api/v1/accounting/category-mappings
     * Returns all categories with their 4 COA account assignments + account names.
     */
    public function categoryMappingIndex(Request $request)
    {
        $companyId = company()->id;

        $categories = Category::withoutGlobalScope(\App\Scopes\CompanyScope::class)
            ->where('company_id', $companyId)
            ->with([
                'salesAccount:id,account_code,account_name,account_type',
                'cogsAccount:id,account_code,account_name,account_type',
                'inventoryAccount:id,account_code,account_name,account_type',
                'purchaseAccount:id,account_code,account_name,account_type',
            ])
            ->orderBy('name')
            ->get([
                'id', 'name', 'slug',
                'sales_account_id', 'cogs_account_id',
                'inventory_account_id', 'purchase_account_id',
            ]);

        // Flat list of all COA accounts for dropdowns
        $accounts = ChartOfAccount::where('company_id', $companyId)
            ->whereNotNull('parent_id') // leaf accounts only
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'account_name', 'account_type']);

        return $this->sendResponse([
            'categories' => $categories->map(function ($c) {
                return [
                    'id'                   => $c->id,
                    'name'                 => $c->name,
                    'slug'                 => $c->slug,
                    'sales_account_id'     => $c->sales_account_id,
                    'cogs_account_id'      => $c->cogs_account_id,
                    'inventory_account_id' => $c->inventory_account_id,
                    'purchase_account_id'  => $c->purchase_account_id,
                ];
            })->values()->all(),
            'accounts' => $accounts->map(function ($a) {
                return [
                    'id'           => $a->id,
                    'account_code' => $a->account_code,
                    'account_name' => $a->account_name,
                    'account_type' => $a->account_type,
                ];
            })->values()->all(),
        ], '');
    }

    /**
     * PUT /api/v1/accounting/category-mappings/{id}
     * Update the 4 COA account IDs for a single category.
     */
    public function categoryMappingUpdate(Request $request, $id)
    {
        $request->validate([
            'sales_account_id'     => 'nullable|exists:chart_of_accounts,id',
            'cogs_account_id'      => 'nullable|exists:chart_of_accounts,id',
            'inventory_account_id' => 'nullable|exists:chart_of_accounts,id',
            'purchase_account_id'  => 'nullable|exists:chart_of_accounts,id',
        ]);

        $companyId = company()->id;
        $category  = Category::withoutGlobalScope(\App\Scopes\CompanyScope::class)
            ->where('company_id', $companyId)
            ->findOrFail($id);

        $category->update($request->only([
            'sales_account_id',
            'cogs_account_id',
            'inventory_account_id',
            'purchase_account_id',
        ]));

        // Reload with account names
        $category->load([
            'salesAccount:id,account_code,account_name',
            'cogsAccount:id,account_code,account_name',
            'inventoryAccount:id,account_code,account_name',
            'purchaseAccount:id,account_code,account_name',
        ]);

        return $this->sendResponse(['category' => $category], 'Category mapping updated successfully.');
    }

    /**
     * POST /api/v1/accounting/category-mappings/bulk
     * Bulk update category mappings. Body: { mappings: [{id, sales_account_id, ...}] }
     */
    public function categoryMappingBulk(Request $request)
    {
        $request->validate([
            'mappings'             => 'required|array',
            'mappings.*.id'        => 'required|integer',
            'mappings.*.sales_account_id'     => 'nullable|exists:chart_of_accounts,id',
            'mappings.*.cogs_account_id'      => 'nullable|exists:chart_of_accounts,id',
            'mappings.*.inventory_account_id' => 'nullable|exists:chart_of_accounts,id',
            'mappings.*.purchase_account_id'  => 'nullable|exists:chart_of_accounts,id',
        ]);

        $companyId = company()->id;
        $updated   = 0;

        DB::beginTransaction();
        try {
            foreach ($request->mappings as $map) {
                $affected = Category::withoutGlobalScope(\App\Scopes\CompanyScope::class)
                    ->where('company_id', $companyId)
                    ->where('id', $map['id'])
                    ->update([
                        'sales_account_id'     => $map['sales_account_id']     ?? null,
                        'cogs_account_id'      => $map['cogs_account_id']      ?? null,
                        'inventory_account_id' => $map['inventory_account_id'] ?? null,
                        'purchase_account_id'  => $map['purchase_account_id']  ?? null,
                    ]);
                $updated += $affected;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(['updated' => $updated], "{$updated} category mappings saved successfully.");
    }

    // ─── OPENING BALANCE ──────────────────────────────────────────────────
    public function postOpeningBalance(Request $request)
    {
        $request->validate([
            'date'           => 'required|date',
            'entries'        => 'required|array|min:1',
            'entries.*.account_code' => 'required|string',
            'entries.*.amount'       => 'required|numeric|min:0.01',
            'entries.*.note'         => 'nullable|string',
        ]);

        $companyId = company()->id;
        $result    = \App\Services\AccountingService::postOpeningBalance(
            $companyId,
            $request->entries,
            $request->date
        );

        if (!$result['ok']) {
            return $this->sendError($result['message']);
        }

        return $this->sendResponse([
            'entry_number' => $result['entry']?->entry_number,
            'warnings'     => $result['warnings'],
        ], 'Opening balance posted successfully.');
    }

    // ─── JE HEALTH CHECK ──────────────────────────────────────────────────
    // Returns a summary of JE status: total, balanced, imbalanced, coverage
    public function jeHealthCheck()
    {
        $companyId = company()->id;

        $total = \App\Models\JournalEntry::where('company_id', $companyId)->count();

        $imbalanced = \DB::table('journal_entries as je')
            ->where('je.company_id', $companyId)
            ->leftJoin('journal_entry_lines as jel', 'jel.journal_entry_id', '=', 'je.id')
            ->select('je.entry_number', \DB::raw('SUM(jel.debit) as dr'), \DB::raw('SUM(jel.credit) as cr'))
            ->groupBy('je.id', 'je.entry_number')
            ->havingRaw('ABS(SUM(jel.debit) - SUM(jel.credit)) > 0.01')
            ->get();

        $ordersWithJE = \DB::table('orders')
            ->where('company_id', $companyId)
            ->whereIn('order_type', ['sales', 'purchases'])
            ->whereRaw("EXISTS (SELECT 1 FROM journal_entries WHERE CONVERT(journal_entries.reference USING utf8mb4) = CONVERT(orders.invoice_number USING utf8mb4))")
            ->count();

        $ordersTotal = \DB::table('orders')
            ->where('company_id', $companyId)
            ->whereIn('order_type', ['sales', 'purchases'])
            ->count();

        $cogsTotal = \DB::table('journal_entries as je')
            ->where('je.company_id', $companyId)
            ->where('je.description', 'like', 'COGS%')
            ->count();

        return $this->sendResponse([
            'total_entries'      => $total,
            'balanced'           => $total - count($imbalanced),
            'imbalanced'         => $imbalanced,
            'orders_with_je'     => $ordersWithJE,
            'orders_total'       => $ordersTotal,
            'je_coverage_pct'    => $ordersTotal > 0 ? round($ordersWithJE / $ordersTotal * 100, 1) : 0,
            'cogs_entries'       => $cogsTotal,
        ], '');
    }

    // ─── BACKFILL MISSING JOURNAL ENTRIES ─────────────────────────────────
    // Generates JEs for all existing orders that don't have one yet.

    public function backfillMissingJournalEntries(Request $request)
    {
        $companyId = company()->id;

        // Find all orders that do NOT have a matching JE by invoice_number
        // Using CONVERT to avoid MySQL collation mismatch between the two tables.
        $orders = Order::where('company_id', $companyId)
            ->whereIn('order_type', ['sales', 'purchases', 'grn', 'sales-returns', 'purchase-returns'])
            ->where('cancelled', 0)
            ->whereRaw("NOT EXISTS (SELECT 1 FROM journal_entries WHERE CONVERT(journal_entries.reference USING utf8mb4) = CONVERT(orders.invoice_number USING utf8mb4) AND journal_entries.company_id = orders.company_id)")
            ->orderBy('order_date')
            ->get();

        $generated = 0;
        $skipped   = 0;
        $failed    = [];
        $warnings  = [];

        foreach ($orders as $order) {
            $result = AccountingService::handleOrder($order);
            if ($result['ok'] ?? false) {
                $generated++;
                if (!empty($result['warnings'])) {
                    $warnings = array_merge($warnings, $result['warnings']);
                }
            } elseif (($result['message'] ?? '') !== '' && str_contains($result['message'] ?? '', 'Zero')) {
                $skipped++;
            } else {
                $failed[] = ($order->invoice_number ?? $order->id) . ': ' . ($result['message'] ?? 'unknown error');
            }
        }

        return $this->sendResponse([
            'processed' => count($orders),
            'generated' => $generated,
            'skipped'   => $skipped,
            'failed'    => $failed,
            'warnings'  => array_values(array_unique($warnings)),
        ], "Backfill complete: {$generated} JEs generated, {$skipped} skipped (zero-value), " . count($failed) . " failed.");
    }
}
