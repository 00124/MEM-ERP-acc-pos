<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PartyCheque;
use App\Models\ChartOfAccount;
use App\Scopes\CompanyScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartyChequeController extends Controller
{
    public function index(Request $request)
    {
        $query = PartyCheque::with(['party:id,name,user_type', 'bankAccount:id,account_name'])
            ->orderBy('cheque_date', 'desc');

        if ($request->filled('type'))       $query->where('type', $request->type);
        if ($request->filled('status'))     $query->where('status', $request->status);
        if ($request->filled('party_type')) $query->where('party_type', $request->party_type);

        if ($request->filled('date_from'))  $query->where('cheque_date', '>=', $request->date_from);
        if ($request->filled('date_to'))    $query->where('cheque_date', '<=', $request->date_to);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('cheque_no', 'like', "%{$s}%")
                  ->orWhereHas('party', fn($p) => $p->where('name', 'like', "%{$s}%"));
            });
        }

        $cheques = $query->get();

        $summary = [
            'in_hand'        => $cheques->where('status', 'in_hand')->count(),
            'pending'        => $cheques->where('status', 'pending')->count(),
            'cleared_amount' => round($cheques->whereIn('status', ['cleared', 'deposited'])->sum('amount'), 2),
            'bounced_amount' => round($cheques->where('status', 'bounced')->sum('amount'), 2),
        ];

        return response()->json(['data' => $cheques, 'summary' => $summary]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'cheque_no'   => 'required|string|max:50',
            'party_id'    => 'required',
            'party_type'  => 'required|in:customer,supplier',
            'type'        => 'required|in:received,issued',
            'amount'      => 'required|numeric|min:0.01',
            'cheque_date' => 'required|date',
            'bank_name'   => 'nullable|string|max:100',
        ]);

        $partyId = hashids()->decode($request->party_id)[0] ?? null;
        if (!$partyId) return response()->json(['message' => 'Invalid party.'], 422);

        $bankAccountId = null;
        if ($request->filled('bank_account_id')) {
            $bankAccountId = hashids()->decode($request->bank_account_id)[0] ?? null;
        }

        $status = $request->type === 'received' ? 'in_hand' : 'pending';

        $cheque = PartyCheque::create([
            'cheque_no'       => $request->cheque_no,
            'party_id'        => $partyId,
            'party_type'      => $request->party_type,
            'type'            => $request->type,
            'amount'          => $request->amount,
            'cheque_date'     => $request->cheque_date,
            'bank_name'       => $request->bank_name,
            'bank_account_id' => $bankAccountId,
            'status'          => $status,
            'remarks'         => $request->remarks,
            'created_by'      => auth()->id(),
        ]);

        return response()->json(['message' => 'Cheque recorded.', 'data' => $cheque->load('party', 'bankAccount')], 201);
    }

    public function depositToBank(Request $request, $id)
    {
        $decodedId = hashids()->decode($id)[0] ?? null;
        $cheque = PartyCheque::findOrFail($decodedId);

        if ($cheque->status !== 'in_hand') {
            return response()->json(['message' => 'Only in-hand cheques can be deposited.'], 422);
        }

        $request->validate([
            'bank_account_id' => 'required',
            'action_date'     => 'required|date',
        ]);

        $bankAccountId = hashids()->decode($request->bank_account_id)[0] ?? null;

        $cheque->update([
            'status'          => 'deposited',
            'bank_account_id' => $bankAccountId,
            'action_date'     => $request->action_date,
            'remarks'         => $request->remarks ?? $cheque->remarks,
        ]);

        return response()->json(['message' => 'Cheque deposited to bank.', 'data' => $cheque->fresh()->load('party', 'bankAccount')]);
    }

    public function clearCheque(Request $request, $id)
    {
        $decodedId = hashids()->decode($id)[0] ?? null;
        $cheque = PartyCheque::findOrFail($decodedId);

        if (!in_array($cheque->status, ['in_hand', 'pending', 'deposited'])) {
            return response()->json(['message' => 'Cheque cannot be cleared from current status.'], 422);
        }

        $request->validate(['action_date' => 'required|date']);

        $cheque->update([
            'status'      => 'cleared',
            'action_date' => $request->action_date,
        ]);

        return response()->json(['message' => 'Cheque cleared.', 'data' => $cheque->fresh()->load('party', 'bankAccount')]);
    }

    public function bounceCheque(Request $request, $id)
    {
        $decodedId = hashids()->decode($id)[0] ?? null;
        $cheque = PartyCheque::findOrFail($decodedId);

        if (in_array($cheque->status, ['bounced', 'returned'])) {
            return response()->json(['message' => 'Cheque is already ' . $cheque->status . '.'], 422);
        }

        $cheque->update([
            'status'      => 'bounced',
            'action_date' => now()->toDateString(),
            'remarks'     => $request->remarks ?? $cheque->remarks,
        ]);

        return response()->json(['message' => 'Cheque marked as bounced.', 'data' => $cheque->fresh()]);
    }

    public function returnCheque(Request $request, $id)
    {
        $decodedId = hashids()->decode($id)[0] ?? null;
        $cheque = PartyCheque::findOrFail($decodedId);

        $cheque->update([
            'status'      => 'returned',
            'action_date' => now()->toDateString(),
            'remarks'     => $request->remarks ?? $cheque->remarks,
        ]);

        return response()->json(['message' => 'Cheque marked as returned.', 'data' => $cheque->fresh()]);
    }

    public function destroy($id)
    {
        $decodedId = hashids()->decode($id)[0] ?? null;
        $cheque = PartyCheque::findOrFail($decodedId);

        if (in_array($cheque->status, ['deposited', 'cleared'])) {
            return response()->json(['message' => 'Cannot delete a deposited or cleared cheque.'], 422);
        }

        $cheque->delete();
        return response()->json(['message' => 'Cheque deleted.']);
    }
}
