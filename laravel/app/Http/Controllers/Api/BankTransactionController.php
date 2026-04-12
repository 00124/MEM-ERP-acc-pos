<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankTransaction;
use App\Models\ChartOfAccount;
use App\Scopes\CompanyScope;
use Illuminate\Http\Request;

class BankTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = BankTransaction::with(['fromAccount:id,account_name', 'toAccount:id,account_name'])
            ->orderBy('transaction_date', 'desc');

        if ($request->filled('type'))      $query->where('type', $request->type);
        if ($request->filled('date_from')) $query->where('transaction_date', '>=', $request->date_from);
        if ($request->filled('date_to'))   $query->where('transaction_date', '<=', $request->date_to);

        $txns = $query->get();
        $totalDeposits  = round($txns->where('type', 'cash_deposit')->sum('amount'), 2);
        $totalTransfers = round($txns->where('type', 'bank_transfer')->sum('amount'), 2);

        return response()->json([
            'data'            => $txns,
            'total_deposits'  => $totalDeposits,
            'total_transfers' => $totalTransfers,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type'             => 'required|in:cash_deposit,bank_transfer',
            'amount'           => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'to_account_id'    => 'required',
            'description'      => 'nullable|string|max:255',
        ]);

        $toAccountId = hashids()->decode($request->to_account_id)[0] ?? null;
        if (!$toAccountId) return response()->json(['message' => 'Invalid destination account.'], 422);

        $fromAccountId = null;
        if ($request->filled('from_account_id')) {
            $fromAccountId = hashids()->decode($request->from_account_id)[0] ?? null;
        }

        if ($request->type === 'bank_transfer' && !$fromAccountId) {
            return response()->json(['message' => 'Source bank account required for transfers.'], 422);
        }

        if ($request->type === 'bank_transfer' && $fromAccountId === $toAccountId) {
            return response()->json(['message' => 'Source and destination accounts cannot be the same.'], 422);
        }

        $txn = BankTransaction::create([
            'type'             => $request->type,
            'amount'           => $request->amount,
            'transaction_date' => $request->transaction_date,
            'from_account_id'  => $fromAccountId,
            'to_account_id'    => $toAccountId,
            'reference'        => $request->reference,
            'description'      => $request->description,
            'created_by'       => auth()->id(),
        ]);

        return response()->json([
            'message' => $request->type === 'cash_deposit' ? 'Cash deposited to bank.' : 'Bank transfer recorded.',
            'data'    => $txn->load('fromAccount', 'toAccount'),
        ], 201);
    }

    public function destroy($id)
    {
        $decodedId = hashids()->decode($id)[0] ?? null;
        $txn = BankTransaction::findOrFail($decodedId);
        $txn->delete();
        return response()->json(['message' => 'Transaction deleted.']);
    }
}
