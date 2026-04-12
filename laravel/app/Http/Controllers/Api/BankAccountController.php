<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Scopes\CompanyScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;

class BankAccountController extends Controller
{
    public function index()
    {
        $companyId = company()->id;

        $accounts = ChartOfAccount::where('company_id', $companyId)
            ->where('account_type', 'bank')
            ->orderBy('account_name')
            ->get();

        // Get balances from journal entry lines
        $balances = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->where('je.company_id', $companyId)
            ->where('je.status', 'posted')
            ->select('jel.account_id',
                DB::raw('COALESCE(SUM(jel.debit),0) as total_debit'),
                DB::raw('COALESCE(SUM(jel.credit),0) as total_credit'))
            ->groupBy('jel.account_id')
            ->get()
            ->keyBy('account_id');

        // Get payment totals per bank account
        $paymentTotals = DB::table('payments')
            ->where('company_id', $companyId)
            ->whereNotNull('bank_account_id')
            ->select('bank_account_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('COALESCE(SUM(CASE WHEN payment_type="in" THEN amount ELSE 0 END),0) as total_in'),
                DB::raw('COALESCE(SUM(CASE WHEN payment_type="out" THEN amount ELSE 0 END),0) as total_out'))
            ->groupBy('bank_account_id')
            ->get()
            ->keyBy('bank_account_id');

        $result = $accounts->map(function ($acc) use ($balances, $paymentTotals) {
            $b = $balances->get($acc->id);
            $p = $paymentTotals->get($acc->id);

            $debit  = $b ? (float)$b->total_debit  : 0;
            $credit = $b ? (float)$b->total_credit : 0;
            $journalBalance = $debit - $credit;
            $currentBalance = (float)$acc->opening_balance + $journalBalance;

            return [
                'id'               => $acc->id,
                'xid'              => Hashids::encode($acc->id),
                'account_name'     => $acc->account_name,
                'account_code'     => $acc->account_code,
                'account_number'   => $acc->account_number,
                'branch_name'      => $acc->branch_name,
                'opening_balance'  => (float)$acc->opening_balance,
                'current_balance'  => round($currentBalance, 2),
                'total_debit'      => round($debit, 2),
                'total_credit'     => round($credit, 2),
                'transaction_count'=> $p ? (int)$p->transaction_count : 0,
                'total_in'         => $p ? (float)$p->total_in : 0,
                'total_out'        => $p ? (float)$p->total_out : 0,
                'status'           => $acc->status ?? 'active',
                'description'      => $acc->description,
            ];
        });

        $summary = [
            'total_banks'      => $result->count(),
            'total_balance'    => round($result->sum('current_balance'), 2),
            'total_in'         => round($result->sum('total_in'), 2),
            'total_out'        => round($result->sum('total_out'), 2),
        ];

        return response()->json(['data' => $result, 'summary' => $summary]);
    }

    public function store(Request $request)
    {
        $companyId = company()->id;

        $request->validate([
            'account_name'   => 'required|string|max:100',
            'account_code'   => 'nullable|string|max:20',
            'account_number' => 'nullable|string|max:50',
            'branch_name'    => 'nullable|string|max:100',
            'opening_balance'=> 'nullable|numeric|min:0',
            'description'    => 'nullable|string|max:255',
        ]);

        // Auto-generate account code if not provided
        $code = $request->account_code;
        if (!$code) {
            $last = ChartOfAccount::where('company_id', $companyId)
                ->where('account_type', 'bank')
                ->orderBy('id', 'desc')
                ->value('account_code');
            $lastNum = $last ? (int) preg_replace('/\D/', '', $last) : 10000;
            $code = 'BNK-' . ($lastNum + 1);
        }

        $account = ChartOfAccount::create([
            'company_id'     => $companyId,
            'account_name'   => $request->account_name,
            'account_code'   => $code,
            'account_type'   => 'bank',
            'account_number' => $request->account_number,
            'branch_name'    => $request->branch_name,
            'opening_balance'=> $request->opening_balance ?? 0,
            'description'    => $request->description,
            'status'         => 1,
        ]);

        return response()->json([
            'message' => 'Bank account created successfully.',
            'data'    => array_merge($account->toArray(), ['xid' => Hashids::encode($account->id)]),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $decodedId = Hashids::decode($id)[0] ?? null;
        if (!$decodedId) return response()->json(['message' => 'Not found.'], 404);

        $account = ChartOfAccount::where('company_id', company()->id)
            ->where('account_type', 'bank')
            ->findOrFail($decodedId);

        $request->validate([
            'account_name'   => 'required|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'branch_name'    => 'nullable|string|max:100',
            'opening_balance'=> 'nullable|numeric|min:0',
            'description'    => 'nullable|string|max:255',
        ]);

        $account->update([
            'account_name'   => $request->account_name,
            'account_number' => $request->account_number,
            'branch_name'    => $request->branch_name,
            'opening_balance'=> $request->opening_balance ?? $account->opening_balance,
            'description'    => $request->description,
            'status'         => $request->status ?? $account->status,
        ]);

        return response()->json(['message' => 'Bank account updated.', 'data' => $account->fresh()]);
    }

    public function destroy($id)
    {
        $decodedId = Hashids::decode($id)[0] ?? null;
        if (!$decodedId) return response()->json(['message' => 'Not found.'], 404);

        $account = ChartOfAccount::where('company_id', company()->id)
            ->where('account_type', 'bank')
            ->findOrFail($decodedId);

        $hasEntries = DB::table('journal_entry_lines')->where('account_id', $account->id)->exists();
        $hasPayments = DB::table('payments')->where('bank_account_id', $account->id)->exists();

        if ($hasEntries || $hasPayments) {
            return response()->json(['message' => 'Cannot delete: account has existing transactions or journal entries.'], 422);
        }

        $account->delete();
        return response()->json(['message' => 'Bank account deleted.']);
    }

    public function transactions(Request $request, $id)
    {
        $decodedId = Hashids::decode($id)[0] ?? null;
        if (!$decodedId) return response()->json(['message' => 'Not found.'], 404);

        $account = ChartOfAccount::where('company_id', company()->id)
            ->where('account_type', 'bank')
            ->findOrFail($decodedId);

        $companyId = company()->id;

        // Journal entry lines for this account
        $journalLines = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->where('jel.account_id', $decodedId)
            ->where('je.company_id', $companyId)
            ->where('je.status', 'posted')
            ->select(
                DB::raw("'journal' as source"),
                'je.entry_date as date',
                'je.reference',
                DB::raw("CONCAT('Journal Entry #', je.id) as description"),
                'jel.debit',
                'jel.credit'
            );

        if ($request->filled('date_from')) {
            $journalLines->where('je.entry_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $journalLines->where('je.entry_date', '<=', $request->date_to);
        }

        // Payments linked to this bank account
        $payments = DB::table('payments as p')
            ->leftJoin('users as u', 'u.id', '=', 'p.user_id')
            ->where('p.bank_account_id', $decodedId)
            ->where('p.company_id', $companyId)
            ->select(
                DB::raw("'payment' as source"),
                DB::raw('DATE(p.date) as date'),
                'p.payment_number as reference',
                DB::raw("CONCAT(UPPER(p.payment_type), ' - ', COALESCE(u.name, '')) as description"),
                DB::raw("CASE WHEN p.payment_type='in' THEN p.amount ELSE 0 END as debit"),
                DB::raw("CASE WHEN p.payment_type='out' THEN p.amount ELSE 0 END as credit")
            );

        if ($request->filled('date_from')) {
            $payments->where('p.date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $payments->where('p.date', '<=', $request->date_to);
        }

        $rows = $journalLines->unionAll($payments)->orderBy('date', 'desc')->get();

        $totalDebit  = $rows->sum('debit');
        $totalCredit = $rows->sum('credit');

        return response()->json([
            'account'      => [
                'xid'            => Hashids::encode($account->id),
                'account_name'   => $account->account_name,
                'account_number' => $account->account_number,
                'branch_name'    => $account->branch_name,
                'opening_balance'=> (float)$account->opening_balance,
            ],
            'data'         => $rows,
            'total_debit'  => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
            'net_balance'  => round((float)$account->opening_balance + $totalDebit - $totalCredit, 2),
        ]);
    }

    public function dropdown()
    {
        $accounts = ChartOfAccount::where('company_id', company()->id)
            ->where('account_type', 'bank')
            ->where('status', 'active')
            ->orderBy('account_name')
            ->get(['id', 'account_name', 'account_code', 'account_number'])
            ->map(fn($a) => [
                'xid'          => Hashids::encode($a->id),
                'account_name' => $a->account_name,
                'account_code' => $a->account_code,
                'account_number'=> $a->account_number,
            ]);

        return response()->json(['data' => $accounts]);
    }
}
