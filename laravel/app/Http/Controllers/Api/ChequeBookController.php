<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChequeBook;
use App\Models\Cheque;
use App\Scopes\CompanyScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ChequeBookController extends Controller
{
    // ─── Cheque Books ────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $books = ChequeBook::withCount([
            'cheques',
            'cheques as used_count'   => fn($q) => $q->where('status', 'issued'),
            'cheques as cancelled_count' => fn($q) => $q->where('status', 'cancelled'),
        ])
        ->orderBy('id', 'desc')
        ->get();

        return response()->json(['data' => $books]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'bank_name'       => 'required|string|max:100',
            'book_no'         => 'required|string|max:50',
            'start_cheque_no' => 'required|integer|min:1',
            'end_cheque_no'   => 'required|integer|gte:start_cheque_no',
        ]);

        $start = (int) $request->start_cheque_no;
        $end   = (int) $request->end_cheque_no;
        $total = $end - $start + 1;

        if ($total > 500) {
            return response()->json(['message' => 'Cheque book cannot have more than 500 cheques.'], 422);
        }

        DB::transaction(function () use ($request, $start, $end, $total) {
            $book = ChequeBook::create([
                'bank_name'        => $request->bank_name,
                'account_title'    => $request->account_title,
                'account_number'   => $request->account_number,
                'book_no'          => $request->book_no,
                'start_cheque_no'  => $start,
                'end_cheque_no'    => $end,
                'total_cheques'    => $total,
                'remaining_cheques'=> $total,
                'notes'            => $request->notes,
                'created_by'       => auth()->id(),
            ]);

            $cheques = [];
            $companyId = company()->id;
            $now = now();
            for ($no = $start; $no <= $end; $no++) {
                $cheques[] = [
                    'company_id'     => $companyId,
                    'cheque_book_id' => $book->id,
                    'cheque_no'      => $no,
                    'status'         => 'unused',
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }
            Cheque::withoutGlobalScope(CompanyScope::class)->insert($cheques);
        });

        return response()->json(['message' => 'Cheque book created successfully.'], 201);
    }

    public function show($id)
    {
        $decodedId = hashids()->decode($id)[0] ?? null;
        if (!$decodedId) return response()->json(['message' => 'Not found.'], 404);

        $book = ChequeBook::withCount([
            'cheques',
            'cheques as used_count'      => fn($q) => $q->where('status', 'issued'),
            'cheques as cancelled_count' => fn($q) => $q->where('status', 'cancelled'),
        ])->findOrFail($decodedId);

        return response()->json(['data' => $book]);
    }

    public function destroy($id)
    {
        $decodedId = hashids()->decode($id)[0] ?? null;
        if (!$decodedId) return response()->json(['message' => 'Not found.'], 404);

        $book = ChequeBook::findOrFail($decodedId);

        $usedCount = $book->cheques()->where('status', 'issued')->count();
        if ($usedCount > 0) {
            return response()->json(['message' => 'Cannot delete: book has issued cheques.'], 422);
        }

        DB::transaction(function () use ($book) {
            Cheque::withoutGlobalScope(CompanyScope::class)
                ->where('cheque_book_id', $book->id)
                ->delete();
            $book->delete();
        });

        return response()->json(['message' => 'Cheque book deleted.']);
    }

    // ─── Cheques ─────────────────────────────────────────────────────────────

    public function cheques(Request $request, $bookId)
    {
        $decodedId = hashids()->decode($bookId)[0] ?? null;
        if (!$decodedId) return response()->json(['message' => 'Not found.'], 404);

        $query = Cheque::where('cheque_book_id', $decodedId);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $cheques = $query->orderBy('cheque_no')->get();

        return response()->json(['data' => $cheques]);
    }

    public function issueCheque(Request $request, $chequeId)
    {
        $decodedId = hashids()->decode($chequeId)[0] ?? null;
        if (!$decodedId) return response()->json(['message' => 'Not found.'], 404);

        $cheque = Cheque::findOrFail($decodedId);

        if ($cheque->status !== 'unused') {
            return response()->json(['message' => 'Cheque is already ' . $cheque->status . '.'], 422);
        }

        $request->validate([
            'payee'      => 'required|string|max:200',
            'amount'     => 'required|numeric|min:0.01',
            'issue_date' => 'required|date',
            'remarks'    => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($cheque, $request) {
            $cheque->update([
                'status'     => 'issued',
                'payee'      => $request->payee,
                'amount'     => $request->amount,
                'issue_date' => $request->issue_date,
                'remarks'    => $request->remarks,
            ]);

            ChequeBook::withoutGlobalScope(CompanyScope::class)
                ->where('id', $cheque->cheque_book_id)
                ->decrement('remaining_cheques');
        });

        return response()->json(['message' => 'Cheque issued successfully.', 'data' => $cheque->fresh()]);
    }

    public function cancelCheque(Request $request, $chequeId)
    {
        $decodedId = hashids()->decode($chequeId)[0] ?? null;
        if (!$decodedId) return response()->json(['message' => 'Not found.'], 404);

        $cheque = Cheque::findOrFail($decodedId);

        if ($cheque->status === 'cancelled') {
            return response()->json(['message' => 'Cheque is already cancelled.'], 422);
        }

        $wasUnused = $cheque->status === 'unused';

        $cheque->update(['status' => 'cancelled', 'remarks' => $request->remarks ?? $cheque->remarks]);

        if (!$wasUnused) {
            ChequeBook::withoutGlobalScope(CompanyScope::class)
                ->where('id', $cheque->cheque_book_id)
                ->increment('remaining_cheques');
        }

        return response()->json(['message' => 'Cheque cancelled.', 'data' => $cheque->fresh()]);
    }

    public function clearCheque(Request $request, $chequeId)
    {
        $decodedId = hashids()->decode($chequeId)[0] ?? null;
        if (!$decodedId) return response()->json(['message' => 'Not found.'], 404);

        $cheque = Cheque::findOrFail($decodedId);

        if ($cheque->status !== 'issued') {
            return response()->json(['message' => 'Only issued cheques can be cleared. Current status: ' . $cheque->status], 422);
        }

        $request->validate([
            'clear_date' => 'required|date',
        ]);

        $cheque->update([
            'status'     => 'cleared',
            'clear_date' => $request->clear_date,
            'cleared_by' => auth()->id(),
        ]);

        return response()->json(['message' => 'Cheque cleared successfully.', 'data' => $cheque->fresh()]);
    }

    public function bounceCheque(Request $request, $chequeId)
    {
        $decodedId = hashids()->decode($chequeId)[0] ?? null;
        if (!$decodedId) return response()->json(['message' => 'Not found.'], 404);

        $cheque = Cheque::findOrFail($decodedId);

        if (!in_array($cheque->status, ['issued', 'cleared'])) {
            return response()->json(['message' => 'Only issued or cleared cheques can be marked bounced.'], 422);
        }

        $cheque->update([
            'status'       => 'bounced',
            'bounce_reason' => $request->bounce_reason ?? null,
        ]);

        // Restore remaining count if it was issued (not yet cleared)
        // No book count change needed — remaining was already decremented when issued

        return response()->json(['message' => 'Cheque marked as bounced.', 'data' => $cheque->fresh()]);
    }

    public function clearanceList(Request $request)
    {
        $query = Cheque::with('chequeBook')
            ->whereIn('status', ['issued', 'cleared', 'bounced'])
            ->orderBy('issue_date', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('cheque_book_id')) {
            $decodedId = hashids()->decode($request->cheque_book_id)[0] ?? null;
            if ($decodedId) $query->where('cheque_book_id', $decodedId);
        }

        if ($request->filled('date_from')) {
            $query->where('issue_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('issue_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('cheque_no', 'like', "%{$search}%")
                  ->orWhere('payee', 'like', "%{$search}%");
            });
        }

        $cheques = $query->get();

        $summary = [
            'total_pending'        => $cheques->where('status', 'issued')->count(),
            'total_cleared_amount' => $cheques->where('status', 'cleared')->sum('amount'),
            'total_pending_amount' => $cheques->where('status', 'issued')->sum('amount'),
            'total_bounced_amount' => $cheques->where('status', 'bounced')->sum('amount'),
        ];

        return response()->json([
            'data'    => $cheques,
            'summary' => $summary,
        ]);
    }

    public function report(Request $request)
    {
        $query = Cheque::with('chequeBook')
            ->orderBy('cheque_no');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('cheque_book_id')) {
            $decodedId = hashids()->decode($request->cheque_book_id)[0] ?? null;
            if ($decodedId) $query->where('cheque_book_id', $decodedId);
        }

        if ($request->filled('date_from')) {
            $query->where('issue_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('issue_date', '<=', $request->date_to);
        }

        $cheques = $query->get();
        $totalAmount = $cheques->where('status', 'issued')->sum('amount');

        return response()->json([
            'data'         => $cheques,
            'total_amount' => $totalAmount,
        ]);
    }
}
