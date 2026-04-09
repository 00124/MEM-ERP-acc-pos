<?php

namespace App\Services;

use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMode;
use App\Models\Product;
use App\Models\ProductDetails;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountingService
{
    // ─── FALLBACK ACCOUNT CODES ──────────────────────────────────────────
    const CASH_IN_HAND        = '11001';
    const BANK_ACCOUNT        = '11002';
    const ACCOUNTS_RECEIVABLE = '12001';
    const INVENTORY           = '13007';
    const ACCOUNTS_PAYABLE    = '21001';
    const SALES_REVENUE       = '41006';
    const COGS                = '51006';
    const OWNER_CAPITAL       = '31001';

    // ─── CACHES ──────────────────────────────────────────────────────────
    private static array $accountCache  = [];
    private static array $categoryCache = [];

    // ─── RESULT HELPER ───────────────────────────────────────────────────
    // Every public method returns this shape so callers can check outcome.
    private static function result(
        bool   $ok,
        string $message,
        array  $warnings  = [],
        ?JournalEntry $entry = null
    ): array {
        return compact('ok', 'message', 'warnings', 'entry');
    }

    // ─── RESOLVE ACCOUNT ID FROM CODE ────────────────────────────────────
    public static function getAccountId(string $code, int $companyId = 1): ?int
    {
        $key = $companyId . '_' . $code;
        if (!array_key_exists($key, self::$accountCache)) {
            self::$accountCache[$key] = ChartOfAccount::where('company_id', $companyId)
                ->where('account_code', $code)
                ->value('id');
        }
        return self::$accountCache[$key] ?: null;
    }

    // ─── LOAD CATEGORY WITH CoA ACCOUNT IDs ──────────────────────────────
    // Returns null if category mapping is critically missing.
    private static function getCategoryAccounts(int $categoryId, int $companyId): array
    {
        $key = $companyId . '_cat_' . $categoryId;
        if (!isset(self::$categoryCache[$key])) {
            $cat = Category::withoutGlobalScope(\App\Scopes\CompanyScope::class)
                ->where('id', $categoryId)
                ->first(['id', 'name', 'sales_account_id', 'cogs_account_id', 'inventory_account_id', 'purchase_account_id']);

            $fallbackInv = self::getAccountId(self::INVENTORY, $companyId);
            $usedFallback = [];

            $sales = $cat?->sales_account_id;
            if (!$sales) {
                $sales = self::getAccountId(self::SALES_REVENUE, $companyId);
                $usedFallback[] = 'sales_account';
            }
            $cogs = $cat?->cogs_account_id;
            if (!$cogs) {
                $cogs = self::getAccountId(self::COGS, $companyId);
                $usedFallback[] = 'cogs_account';
            }
            $inv = $cat?->inventory_account_id ?: $fallbackInv;
            if (!$cat?->inventory_account_id) $usedFallback[] = 'inventory_account';

            $pur = $cat?->purchase_account_id ?: $inv;

            if (!empty($usedFallback)) {
                Log::error('[Accounting] FALLBACK accounts used for category [' . ($cat?->name ?? $categoryId) . ']: ' . implode(', ', $usedFallback) . '. Set proper CoA mappings in category settings.');
            }

            self::$categoryCache[$key] = [
                'sales'          => $sales,
                'cogs'           => $cogs,
                'inventory'      => $inv,
                'purchase'       => $pur,
                'used_fallback'  => $usedFallback,
                'cat_name'       => $cat?->name ?? 'Unknown',
            ];
        }
        return self::$categoryCache[$key];
    }

    // ─── ENTRY NUMBER GENERATOR ───────────────────────────────────────────
    private static function nextEntryNumber(int $companyId): string
    {
        $count = JournalEntry::where('company_id', $companyId)->count();
        return 'JE-' . date('Ymd') . '-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
    }

    // ─── CORE: CREATE JE BY ACCOUNT CODE ─────────────────────────────────
    public static function createEntry(
        int    $companyId,
        string $description,
        string $reference,
        string $date,
        array  $lines,
        array  &$warnings = []
    ): array {
        $resolved = [];
        foreach ($lines as $line) {
            $accountId = self::getAccountId($line['account_code'], $companyId);
            if (!$accountId) {
                $msg = "Account code [{$line['account_code']}] not found in CoA — JE skipped: {$description}";
                Log::error('[Accounting] ' . $msg);
                $warnings[] = $msg;
                return self::result(false, $msg, $warnings);
            }
            $resolved[] = [
                'account_id'  => $accountId,
                'debit'       => $line['debit'],
                'credit'      => $line['credit'],
                'description' => $line['note'] ?? null,
            ];
        }
        return self::createEntryWithLines($companyId, $description, $reference, $date, $resolved, $warnings);
    }

    // ─── CORE: CREATE JE BY ACCOUNT ID ───────────────────────────────────
    public static function createEntryById(
        int    $companyId,
        string $description,
        string $reference,
        string $date,
        array  $lines,
        array  &$warnings = []
    ): array {
        $lines = array_values(array_filter($lines, fn($l) => ($l['debit'] + $l['credit']) > 0));
        if (empty($lines)) {
            $msg = "All lines have zero amount — JE skipped: {$description}";
            Log::warning('[Accounting] ' . $msg);
            $warnings[] = $msg;
            return self::result(false, $msg, $warnings);
        }
        $resolved = array_map(fn($l) => [
            'account_id'  => $l['account_id'],
            'debit'       => $l['debit'],
            'credit'      => $l['credit'],
            'description' => $l['note'] ?? null,
        ], $lines);
        return self::createEntryWithLines($companyId, $description, $reference, $date, $resolved, $warnings);
    }

    // ─── INTERNAL: WRITE JE TO DB WITH BALANCE CHECK ─────────────────────
    private static function createEntryWithLines(
        int    $companyId,
        string $description,
        string $reference,
        string $date,
        array  $lines,
        array  &$warnings = []
    ): array {
        $totalDebit  = round(array_sum(array_column($lines, 'debit')), 2);
        $totalCredit = round(array_sum(array_column($lines, 'credit')), 2);

        if ($totalDebit <= 0 || $totalDebit !== $totalCredit) {
            $msg = "Journal Entry IMBALANCED — Dr:{$totalDebit} Cr:{$totalCredit} — [{$description}]";
            Log::error('[Accounting] CRITICAL: ' . $msg);
            $warnings[] = 'Journal Entry imbalanced (Dr=' . number_format($totalDebit, 2) . ' Cr=' . number_format($totalCredit, 2) . ') — not posted for: ' . $description;
            return self::result(false, $msg, $warnings);
        }

        DB::beginTransaction();
        try {
            $entry = JournalEntry::create([
                'company_id'   => $companyId,
                'entry_number' => self::nextEntryNumber($companyId),
                'entry_date'   => $date,
                'reference'    => $reference,
                'description'  => $description,
                'status'       => 'posted',
                'created_by'   => auth('api')->id(),
            ]);

            foreach ($lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id'       => $line['account_id'],
                    'description'      => $line['description'],
                    'debit'            => $line['debit'],
                    'credit'           => $line['credit'],
                ]);
            }

            DB::commit();
            return self::result(true, 'Posted: ' . $entry->entry_number, $warnings, $entry);

        } catch (\Throwable $e) {
            DB::rollBack();
            $msg = 'JE write failed: ' . $e->getMessage() . ' — ' . $description;
            Log::error('[Accounting] ' . $msg);
            $warnings[] = 'Journal Entry DB error — ' . $description;
            return self::result(false, $msg, $warnings);
        }
    }

    // ─── LOAD ORDER ITEMS WITH PRODUCT CATEGORY + COST ───────────────────
    private static function loadItemsWithCategory(Order $order, int $companyId, array &$warnings): array
    {
        $items  = OrderItem::where('order_id', $order->id)->get();
        $result = [];

        foreach ($items as $item) {
            $product    = Product::withoutGlobalScope(\App\Scopes\CompanyScope::class)->find($item->product_id, ['id', 'name', 'item_code', 'category_id']);
            $categoryId = (int)($product?->category_id ?? 0);
            $catAccts   = self::getCategoryAccounts($categoryId, $companyId);

            $productDetail = ProductDetails::withoutGlobalScope('current_warehouse')
                ->where('product_id', $item->product_id)
                ->where('warehouse_id', $order->warehouse_id)
                ->first(['purchase_price']);

            $purchasePrice = (float)($productDetail?->purchase_price ?? 0);
            $qty           = (float)$item->quantity;
            $costTotal     = round($purchasePrice * $qty, 2);
            $unitPrice     = (float)$item->unit_price;

            // ── COGS FALLBACK: use unit_price when purchase_price = 0 ───
            // This ensures COGS always posts. Flag a warning so admin knows
            // to enter real purchase prices.
            $costUsed = $costTotal;
            if ($costTotal <= 0 && $unitPrice > 0) {
                $costUsed = round($unitPrice * $qty, 2);
                $warn = 'Purchase price missing for [' . ($product?->name ?? $item->product_id) . '] — selling price used as COGS placeholder (PKR ' . number_format($costUsed, 2) . '). Update purchase price to correct P&L.';
                Log::error('[Accounting] COGS WARNING: ' . $warn);
                $warnings[] = $warn;
            } elseif ($costTotal <= 0) {
                $warn = 'Purchase price AND selling price both 0 for [' . ($product?->name ?? $item->product_id) . '] — COGS entry will be zero.';
                Log::error('[Accounting] COGS CRITICAL: ' . $warn);
                $warnings[] = $warn;
            }

            $result[] = [
                'product_name'         => $product?->name ?? 'Unknown',
                'subtotal'             => round((float)$item->subtotal, 2),
                'cost'                 => $costUsed,
                'cost_is_fallback'     => ($costTotal <= 0 && $unitPrice > 0),
                'sales_account_id'     => $catAccts['sales'],
                'cogs_account_id'      => $catAccts['cogs'],
                'inventory_account_id' => $catAccts['inventory'],
                'purchase_account_id'  => $catAccts['purchase'],
            ];
        }

        return $result;
    }

    // ─── AGGREGATE ITEMS BY ACCOUNT ──────────────────────────────────────
    private static function sumByAccount(array $items, string $amountKey, string $accountKey): array
    {
        $grouped = [];
        foreach ($items as $item) {
            $acctId = $item[$accountKey];
            if (!$acctId) continue;
            $grouped[$acctId] = ($grouped[$acctId] ?? 0) + $item[$amountKey];
        }
        return $grouped;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ─── SALES TRANSACTION ────────────────────────────────────────────────
    public static function onSaleCreated(Order $order): array
    {
        $warnings  = [];
        $allResults = [];

        try {
            $companyId = $order->company_id ?? 1;
            $total     = round((float)$order->total, 2);
            if ($total <= 0) return self::result(true, 'Zero-value sale — no JE needed', $warnings);

            $date      = $order->order_date ?? now()->toDateString();
            $reference = $order->invoice_number;
            $items     = self::loadItemsWithCategory($order, $companyId, $warnings);

            $paidAmount  = round((float)$order->paid_amount, 2);
            $dueAmount   = round($total - $paidAmount, 2);
            $cashAcctId  = self::getAccountId(self::CASH_IN_HAND, $companyId);
            $arAcctId    = self::getAccountId(self::ACCOUNTS_RECEIVABLE, $companyId);
            $revByAcct   = self::sumByAccount($items, 'subtotal', 'sales_account_id');

            // ── 1. REVENUE ENTRY ──────────────────────────────────────────
            $debitLines = [];
            if ($paidAmount >= $total) {
                $debitLines[] = ['account_id' => $cashAcctId, 'debit' => $total,       'credit' => 0, 'note' => 'Cash received (full)'];
            } elseif ($paidAmount > 0) {
                $debitLines[] = ['account_id' => $cashAcctId, 'debit' => $paidAmount,  'credit' => 0, 'note' => 'Cash received (partial)'];
                $debitLines[] = ['account_id' => $arAcctId,   'debit' => $dueAmount,   'credit' => 0, 'note' => 'Amount receivable'];
            } else {
                $debitLines[] = ['account_id' => $arAcctId,   'debit' => $total,       'credit' => 0, 'note' => 'Credit sale — full AR'];
            }

            $creditLines = [];
            foreach ($revByAcct as $acctId => $amount) {
                if ($amount > 0) $creditLines[] = ['account_id' => $acctId, 'debit' => 0, 'credit' => round($amount, 2), 'note' => 'Sales revenue'];
            }
            if (empty($creditLines)) {
                $fb = self::getAccountId(self::SALES_REVENUE, $companyId);
                $creditLines[] = ['account_id' => $fb, 'debit' => 0, 'credit' => $total, 'note' => 'Sales revenue (fallback)'];
                $warnings[] = 'Revenue account fell back to default for ' . $reference;
            }

            $r1 = self::createEntryById($companyId, "Sale — {$reference}", $reference, $date, array_merge($debitLines, $creditLines), $warnings);
            $allResults[] = $r1;

            // ── 2. COGS ENTRY ─────────────────────────────────────────────
            $r2 = self::buildAndPostCogsEntry($items, $companyId, $date, $reference, "COGS — {$reference}", false, $warnings);
            $allResults[] = $r2;

        } catch (\Throwable $e) {
            $msg = 'onSaleCreated exception: ' . $e->getMessage();
            Log::error('[Accounting] ' . $msg . ' ' . $e->getTraceAsString());
            $warnings[] = $msg;
            return self::result(false, $msg, $warnings);
        }

        $failed = array_filter($allResults, fn($r) => !$r['ok']);
        return self::result(
            empty($failed),
            empty($failed) ? 'Sale JEs posted successfully' : 'Some JEs failed — see warnings',
            $warnings
        );
    }

    // ─── ADVANCE BOOKING ──────────────────────────────────────────────────
    public static function onAdvanceBookingCreated(Order $order): array
    {
        $warnings = [];
        try {
            $companyId  = $order->company_id ?? 1;
            $paidAmount = round((float)$order->paid_amount, 2);
            if ($paidAmount <= 0) return self::result(true, 'Zero advance — no JE needed', $warnings);

            $date      = $order->order_date ?? now()->toDateString();
            $reference = $order->invoice_number;

            $firstPayment = $order->orderPayments()->with('payment.paymentMode')->first();
            $cashAcct = self::CASH_IN_HAND;
            if ($firstPayment?->payment?->paymentMode?->mode_type === 'bank') {
                $cashAcct = self::BANK_ACCOUNT;
            }

            $r = self::createEntry($companyId, "Advance Booking — {$reference}", $reference, $date, [
                ['account_code' => $cashAcct, 'debit' => $paidAmount, 'credit' => 0,           'note' => 'Advance deposit received'],
                ['account_code' => '23002',   'debit' => 0,           'credit' => $paidAmount, 'note' => 'Customer advance liability'],
            ], $warnings);
            return $r;
        } catch (\Throwable $e) {
            $msg = 'onAdvanceBookingCreated: ' . $e->getMessage();
            Log::error('[Accounting] ' . $msg);
            $warnings[] = $msg;
            return self::result(false, $msg, $warnings);
        }
    }

    // ─── PAYMENT RECEIVED (Due → Cash) ────────────────────────────────────
    public static function onPaymentReceived(Payment $payment, Order $order): array
    {
        $warnings = [];
        try {
            $companyId = $order->company_id ?? 1;
            $amount    = round((float)$payment->amount, 2);
            if ($amount <= 0) return self::result(true, 'Zero payment — no JE needed', $warnings);

            $date      = $payment->date ? date('Y-m-d', strtotime($payment->date)) : now()->toDateString();
            $reference = $payment->payment_number ?? ('RCP-' . $order->invoice_number);
            $cashAcct  = self::getPaymentAccount($payment);

            return self::createEntry($companyId,
                "Payment Received — {$reference} (Invoice: {$order->invoice_number})",
                $reference, $date, [
                    ['account_code' => $cashAcct,                 'debit' => $amount, 'credit' => 0,      'note' => 'Cash/Bank received'],
                    ['account_code' => self::ACCOUNTS_RECEIVABLE, 'debit' => 0,       'credit' => $amount, 'note' => 'AR cleared'],
                ], $warnings);
        } catch (\Throwable $e) {
            $msg = 'onPaymentReceived: ' . $e->getMessage();
            Log::error('[Accounting] ' . $msg);
            $warnings[] = $msg;
            return self::result(false, $msg, $warnings);
        }
    }

    // ─── PURCHASE / GRN ───────────────────────────────────────────────────

    /**
     * Compute effective order total from item unit_price or product purchase_price.
     * Used when order.total = 0 (e.g. GRNs created without pricing).
     */
    public static function computeEffectiveOrderTotal(int $orderId): float
    {
        return (float) \DB::table('order_items as oi')
            ->leftJoin('product_details as pd', 'pd.product_id', '=', 'oi.product_id')
            ->where('oi.order_id', $orderId)
            ->sum(\DB::raw(
                'CASE WHEN COALESCE(oi.unit_price, 0) > 0
                      THEN oi.unit_price * oi.quantity
                      ELSE COALESCE(pd.purchase_price, 0) * oi.quantity
                 END'
            ));
    }

    public static function onPurchaseCreated(Order $order): array
    {
        $warnings = [];
        try {
            $companyId = $order->company_id ?? 1;
            $total     = round((float)$order->total, 2);

            // ── If order total = 0, compute from item purchase prices ──────
            if ($total <= 0) {
                $computed = round(self::computeEffectiveOrderTotal($order->id), 2);
                if ($computed <= 0) {
                    return self::result(true, 'Zero purchase — no JE created (total=0, purchase prices=0). Enter product purchase prices to enable accounting.', $warnings);
                }
                $total = $computed;
                $warnings[] = 'Order total was 0; computed effective cost = PKR ' . number_format($total, 2) . ' from product purchase prices. Update item unit prices for accurate records.';
            }

            $date      = $order->order_date ?? now()->toDateString();
            $reference = $order->invoice_number;
            $items     = self::loadItemsWithCategory($order, $companyId, $warnings);

            // ── Build DR lines (Inventory/Purchase accounts per category) ──
            $purchByAcct = self::sumByAccount($items, 'subtotal', 'purchase_account_id');
            $apAcctId    = self::getAccountId(self::ACCOUNTS_PAYABLE, $companyId);
            $purchTotal  = array_sum($purchByAcct);

            $purchaseLines = [];
            foreach ($purchByAcct as $acctId => $amount) {
                if ($amount > 0) $purchaseLines[] = ['account_id' => $acctId, 'debit' => round($amount, 2), 'credit' => 0, 'note' => 'Stock purchased'];
            }

            // ── Fallback: if item subtotals were all 0, use computed total ─
            if (empty($purchaseLines) || $purchTotal <= 0) {
                $fallbackInv   = self::getAccountId(self::INVENTORY, $companyId);
                $purchaseLines = [['account_id' => $fallbackInv, 'debit' => $total, 'credit' => 0, 'note' => 'Stock purchased (DR Inventory — fallback)']];
                $purchTotal    = $total;
                $warnings[]    = 'Inventory DR used default account (no category mapping found) for ' . $reference;
            }

            // ── CR Accounts Payable ────────────────────────────────────────
            $purchaseLines[] = ['account_id' => $apAcctId, 'debit' => 0, 'credit' => round($purchTotal, 2), 'note' => 'CR Accounts Payable — supplier liability'];

            return self::createEntryById($companyId, "Purchase — {$reference}", $reference, $date, $purchaseLines, $warnings);

        } catch (\Throwable $e) {
            $msg = 'onPurchaseCreated: ' . $e->getMessage();
            Log::error('[Accounting] ' . $msg);
            $warnings[] = $msg;
            return self::result(false, $msg, $warnings);
        }
    }

    // ─── SALES RETURN ──────────────────────────────────────────────────────
    // Fix: detect whether original sale was cash or credit and reverse correctly.
    public static function onSaleReturnCreated(Order $order): array
    {
        $warnings = [];
        try {
            $companyId = $order->company_id ?? 1;
            $total     = round((float)$order->total, 2);
            if ($total <= 0) return self::result(true, 'Zero return — no JE needed', $warnings);

            $date      = $order->order_date ?? now()->toDateString();
            $reference = $order->invoice_number;
            $items     = self::loadItemsWithCategory($order, $companyId, $warnings);

            // ── Detect original sale type from parent order ───────────────
            $parentOrder  = $order->parent_order_id ? Order::find($order->parent_order_id) : null;
            $cashAcctId   = self::getAccountId(self::CASH_IN_HAND, $companyId);
            $arAcctId     = self::getAccountId(self::ACCOUNTS_RECEIVABLE, $companyId);

            // Determine credit account: cash return → CR Cash, credit → CR AR
            $creditAcctId = $arAcctId; // default
            $creditNote   = 'Return to customer (AR)';
            if ($parentOrder) {
                $paidAmt = round((float)$parentOrder->paid_amount, 2);
                $parentTotal = round((float)$parentOrder->total, 2);
                if ($paidAmt >= $parentTotal) {
                    // Original was fully paid cash sale → return cash
                    $creditAcctId = $cashAcctId;
                    $creditNote   = 'Cash refunded to customer';
                } elseif ($paidAmt > 0) {
                    // Partial: split proportionally — for simplicity use AR
                    $creditNote = 'Partial cash return — AR adjusted';
                }
                // If paidAmt == 0 → credit sale → AR (already default)
            } else {
                $warnings[] = 'Parent order not found for return ' . $reference . ' — defaulted to AR credit';
            }

            $revByAcct = self::sumByAccount($items, 'subtotal', 'sales_account_id');
            $revTotal  = array_sum($revByAcct);

            // DR Sales Revenue / CR Cash or AR
            $returnLines = [];
            foreach ($revByAcct as $acctId => $amount) {
                if ($amount > 0) $returnLines[] = ['account_id' => $acctId, 'debit' => round($amount, 2), 'credit' => 0, 'note' => 'Sales return — revenue reversed'];
            }
            if (empty($returnLines)) {
                $fb = self::getAccountId(self::SALES_REVENUE, $companyId);
                $returnLines[] = ['account_id' => $fb, 'debit' => $total, 'credit' => 0, 'note' => 'Sales return (fallback)'];
                $revTotal = $total;
            }
            $returnLines[] = ['account_id' => $creditAcctId, 'debit' => 0, 'credit' => $revTotal ?: $total, 'note' => $creditNote];

            $r1 = self::createEntryById($companyId, "Sales Return — {$reference}", $reference, $date, $returnLines, $warnings);

            // Reverse COGS (inventory back)
            $r2 = self::buildAndPostCogsEntry($items, $companyId, $date, $reference, "COGS Reversal — {$reference}", true, $warnings);

            $failed = array_filter([$r1, $r2], fn($r) => !$r['ok']);
            return self::result(empty($failed), empty($failed) ? 'Sales return JEs posted' : 'Some JEs failed', $warnings);

        } catch (\Throwable $e) {
            $msg = 'onSaleReturnCreated: ' . $e->getMessage();
            Log::error('[Accounting] ' . $msg);
            $warnings[] = $msg;
            return self::result(false, $msg, $warnings);
        }
    }

    // ─── PURCHASE RETURN ──────────────────────────────────────────────────
    public static function onPurchaseReturnCreated(Order $order): array
    {
        $warnings = [];
        try {
            $companyId = $order->company_id ?? 1;
            $total     = round((float)$order->total, 2);
            if ($total <= 0) return self::result(true, 'Zero return — no JE needed', $warnings);

            $date      = $order->order_date ?? now()->toDateString();
            $reference = $order->invoice_number;
            $items     = self::loadItemsWithCategory($order, $companyId, $warnings);

            $invByAcct = self::sumByAccount($items, 'subtotal', 'inventory_account_id');
            $apAcctId  = self::getAccountId(self::ACCOUNTS_PAYABLE, $companyId);
            $invTotal  = array_sum($invByAcct);

            $returnLines = [['account_id' => $apAcctId, 'debit' => $invTotal ?: $total, 'credit' => 0, 'note' => 'AP reduced — purchase return']];
            foreach ($invByAcct as $acctId => $amount) {
                if ($amount > 0) $returnLines[] = ['account_id' => $acctId, 'debit' => 0, 'credit' => round($amount, 2), 'note' => 'Inventory returned to supplier'];
            }

            if (count($returnLines) === 1) {
                $fb = self::getAccountId(self::INVENTORY, $companyId);
                $returnLines[] = ['account_id' => $fb, 'debit' => 0, 'credit' => $total, 'note' => 'Inventory returned (fallback)'];
                $returnLines[0]['debit'] = $total;
                $warnings[] = 'Inventory account fell back to default for return ' . $reference;
            }

            return self::createEntryById($companyId, "Purchase Return — {$reference}", $reference, $date, $returnLines, $warnings);

        } catch (\Throwable $e) {
            $msg = 'onPurchaseReturnCreated: ' . $e->getMessage();
            Log::error('[Accounting] ' . $msg);
            $warnings[] = $msg;
            return self::result(false, $msg, $warnings);
        }
    }

    // ─── PAYMENT IN (Customer pays us) ────────────────────────────────────
    public static function onPaymentInCreated(Payment $payment): array
    {
        $warnings = [];
        try {
            $companyId = $payment->company_id ?? 1;
            $amount    = round((float)$payment->amount, 2);
            if ($amount <= 0) return self::result(true, 'Zero amount — no JE', $warnings);

            $date      = $payment->date ? date('Y-m-d', strtotime($payment->date)) : now()->toDateString();
            $reference = $payment->payment_number;
            $cashAcct  = self::getPaymentAccount($payment);

            return self::createEntry($companyId, "Customer Payment — {$reference}", $reference, $date, [
                ['account_code' => $cashAcct,                 'debit' => $amount, 'credit' => 0,      'note' => 'Cash/Bank received'],
                ['account_code' => self::ACCOUNTS_RECEIVABLE, 'debit' => 0,       'credit' => $amount, 'note' => 'AR cleared'],
            ], $warnings);
        } catch (\Throwable $e) {
            $msg = 'onPaymentInCreated: ' . $e->getMessage();
            Log::error('[Accounting] ' . $msg);
            $warnings[] = $msg;
            return self::result(false, $msg, $warnings);
        }
    }

    // ─── PAYMENT OUT (We pay supplier) ────────────────────────────────────
    public static function onPaymentOutCreated(Payment $payment): array
    {
        $warnings = [];
        try {
            $companyId = $payment->company_id ?? 1;
            $amount    = round((float)$payment->amount, 2);
            if ($amount <= 0) return self::result(true, 'Zero amount — no JE', $warnings);

            $date      = $payment->date ? date('Y-m-d', strtotime($payment->date)) : now()->toDateString();
            $reference = $payment->payment_number;
            $cashAcct  = self::getPaymentAccount($payment);

            return self::createEntry($companyId, "Supplier Payment — {$reference}", $reference, $date, [
                ['account_code' => self::ACCOUNTS_PAYABLE, 'debit' => $amount, 'credit' => 0,      'note' => 'AP cleared'],
                ['account_code' => $cashAcct,              'debit' => 0,       'credit' => $amount, 'note' => 'Cash/Bank paid'],
            ], $warnings);
        } catch (\Throwable $e) {
            $msg = 'onPaymentOutCreated: ' . $e->getMessage();
            Log::error('[Accounting] ' . $msg);
            $warnings[] = $msg;
            return self::result(false, $msg, $warnings);
        }
    }

    // ─── OPENING BALANCE ──────────────────────────────────────────────────
    // DR: Cash/Bank/Inventory   CR: Owner Capital
    public static function postOpeningBalance(int $companyId, array $entries, string $date): array
    {
        $warnings = [];
        try {
            $ownerCapitalId = self::getAccountId(self::OWNER_CAPITAL, $companyId);
            if (!$ownerCapitalId) {
                $msg = 'Owner Capital account (31001) not found in CoA';
                Log::error('[Accounting] Opening Balance: ' . $msg);
                return self::result(false, $msg, []);
            }

            $lines       = [];
            $totalDebit  = 0;

            foreach ($entries as $e) {
                $acctId = self::getAccountId($e['account_code'], $companyId);
                if (!$acctId) {
                    $warnings[] = 'Account code ' . $e['account_code'] . ' not found — skipped';
                    continue;
                }
                $amt = round((float)$e['amount'], 2);
                if ($amt <= 0) continue;
                $lines[] = ['account_id' => $acctId, 'debit' => $amt, 'credit' => 0, 'note' => $e['note'] ?? 'Opening balance'];
                $totalDebit += $amt;
            }

            if ($totalDebit <= 0 || empty($lines)) {
                return self::result(false, 'No valid opening balance entries provided', $warnings);
            }

            // CR Owner Capital for the total
            $lines[] = ['account_id' => $ownerCapitalId, 'debit' => 0, 'credit' => $totalDebit, 'note' => 'Owner capital — opening balance'];

            return self::createEntryById($companyId, 'Opening Balance', 'OB-' . date('Ymd', strtotime($date)), $date, $lines, $warnings);

        } catch (\Throwable $e) {
            $msg = 'postOpeningBalance: ' . $e->getMessage();
            Log::error('[Accounting] ' . $msg);
            return self::result(false, $msg, []);
        }
    }

    // ─── COGS ENTRY BUILDER ───────────────────────────────────────────────
    private static function buildAndPostCogsEntry(
        array  $items,
        int    $companyId,
        string $date,
        string $reference,
        string $description,
        bool   $reverse,
        array  &$warnings
    ): array {
        $cogsByAcct = self::sumByAccount($items, 'cost', 'cogs_account_id');
        $invByAcct  = self::sumByAccount($items, 'cost', 'inventory_account_id');
        $totalCost  = array_sum($cogsByAcct);

        if ($totalCost <= 0) {
            $warnings[] = 'COGS entry skipped — all item costs are zero for ' . $reference . '. Enter purchase prices on products to enable cost tracking.';
            return self::result(false, 'COGS skipped — zero cost', $warnings);
        }

        $cogsLines = [];
        if (!$reverse) {
            foreach ($cogsByAcct as $acctId => $amount) {
                if ($amount > 0) $cogsLines[] = ['account_id' => $acctId, 'debit' => round($amount, 2), 'credit' => 0, 'note' => 'Cost of goods sold'];
            }
            foreach ($invByAcct as $acctId => $amount) {
                if ($amount > 0) $cogsLines[] = ['account_id' => $acctId, 'debit' => 0, 'credit' => round($amount, 2), 'note' => 'Inventory reduction'];
            }
        } else {
            foreach ($invByAcct as $acctId => $amount) {
                if ($amount > 0) $cogsLines[] = ['account_id' => $acctId, 'debit' => round($amount, 2), 'credit' => 0, 'note' => 'Return to inventory'];
            }
            foreach ($cogsByAcct as $acctId => $amount) {
                if ($amount > 0) $cogsLines[] = ['account_id' => $acctId, 'debit' => 0, 'credit' => round($amount, 2), 'note' => 'COGS reversal'];
            }
        }

        return empty($cogsLines)
            ? self::result(false, 'No COGS lines generated', $warnings)
            : self::createEntryById($companyId, $description, $reference, $date, $cogsLines, $warnings);
    }

    // ─── HELPER: Cash or Bank account code ───────────────────────────────
    private static function getPaymentAccount(Payment $payment): string
    {
        if (!$payment->payment_mode_id) return self::CASH_IN_HAND;
        $mode = PaymentMode::find($payment->payment_mode_id);
        return ($mode && $mode->mode_type === 'bank') ? self::BANK_ACCOUNT : self::CASH_IN_HAND;
    }

    // ─── DISPATCHER: by order_type ────────────────────────────────────────
    public static function handleOrder(Order $order, string $saleMode = 'full'): array
    {
        if ($order->order_type === 'sales' && $saleMode === 'advance') {
            return self::onAdvanceBookingCreated($order);
        }
        return match ($order->order_type) {
            'sales'            => self::onSaleCreated($order),
            'purchases', 'grn' => self::onPurchaseCreated($order),
            'sales-returns'    => self::onSaleReturnCreated($order),
            'purchase-returns' => self::onPurchaseReturnCreated($order),
            default            => self::result(true, 'Order type [' . $order->order_type . '] has no JE handler — skipped', []),
        };
    }

    // ─── DISPATCHER: by payment_type ─────────────────────────────────────
    public static function handlePayment(Payment $payment): array
    {
        if ($payment->payment_type === 'in')  return self::onPaymentInCreated($payment);
        if ($payment->payment_type === 'out') return self::onPaymentOutCreated($payment);
        return self::result(true, 'Payment type has no JE handler', []);
    }
}
