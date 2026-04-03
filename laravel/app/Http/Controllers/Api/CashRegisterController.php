<?php

namespace App\Http\Controllers\Api;

use App\Models\CashRegister;
use App\Http\Controllers\ApiBaseController;
use Examyou\RestAPI\ApiResponse;
use Examyou\RestAPI\Exceptions\ApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashRegisterController extends ApiBaseController
{
    /**
     * Payment breakdown matrix grouped by (payment_mode × invoice_type).
     *
     * Returns:
     *   rows           – array of { name, normal, credit, advance, total }
     *   column_totals  – { normal, credit, advance, grand }
     */
    private function paymentBreakdown(CashRegister $register): array
    {
        $closeAt = $register->closed_at ?? now();

        // Join payments → payment_modes, then LEFT JOIN order_payments → orders
        // to get invoice_type per payment record.
        // Only 'in' payments (cash received); only completed (non-cancelled) orders or
        // payments not linked to any order (standalone payments treated as 'normal').
        $raw = DB::table('payments as p')
            ->join('payment_modes as pm', 'p.payment_mode_id', '=', 'pm.id')
            ->leftJoin('order_payments as op', 'op.payment_id', '=', 'p.id')
            ->leftJoin('orders as o', function ($join) {
                $join->on('op.order_id', '=', 'o.id')
                     ->where('o.order_status', '!=', 'cancelled');
            })
            ->where('p.warehouse_id', $register->warehouse_id)
            ->where('p.payment_type', 'in')
            ->whereBetween('p.created_at', [$register->opened_at, $closeAt])
            ->groupBy('pm.id', 'pm.name', DB::raw("COALESCE(o.invoice_type, 'normal')"))
            ->select(
                'pm.name as payment_method',
                DB::raw("COALESCE(o.invoice_type, 'normal') as invoice_type"),
                DB::raw('SUM(p.paid_amount) as total')
            )
            ->get();

        // Build matrix: { method_name → { normal, credit, advance, total } }
        $methods = [];
        $colTotals = ['normal' => 0.0, 'credit' => 0.0, 'advance' => 0.0, 'grand' => 0.0];

        foreach ($raw as $row) {
            $method = $row->payment_method;
            $type   = $row->invoice_type ?? 'normal';
            $amount = (float) $row->total;

            if (!isset($methods[$method])) {
                $methods[$method] = ['name' => $method, 'normal' => 0.0, 'credit' => 0.0, 'advance' => 0.0, 'total' => 0.0];
            }

            $methods[$method][$type]   = ($methods[$method][$type] ?? 0.0) + $amount;
            $methods[$method]['total'] += $amount;

            $colTotals[$type]   = ($colTotals[$type] ?? 0.0) + $amount;
            $colTotals['grand'] += $amount;
        }

        // Sort by total desc
        $rows = array_values($methods);
        usort($rows, fn($a, $b) => $b['total'] <=> $a['total']);

        return [
            'rows'          => $rows,
            'column_totals' => $colTotals,
        ];
    }

    /**
     * Expenses grouped by category during the register session.
     */
    private function expenseBreakdown(CashRegister $register): array
    {
        $closeAt = $register->closed_at ?? now();

        return DB::table('expenses')
            ->leftJoin('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->where('expenses.warehouse_id', $register->warehouse_id)
            ->whereBetween('expenses.created_at', [$register->opened_at, $closeAt])
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->select(
                DB::raw("COALESCE(expense_categories.name, 'Uncategorized') as name"),
                DB::raw('SUM(expenses.amount) as total')
            )
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function status()
    {
        $loggedInUser = user();

        $register = CashRegister::where('user_id', $loggedInUser->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        $paymentBreakdown = ['rows' => [], 'column_totals' => ['normal' => 0, 'credit' => 0, 'advance' => 0, 'grand' => 0]];
        $expenseBreakdown = [];
        $expectedClosing  = 0;

        if ($register) {
            $paymentBreakdown = $this->paymentBreakdown($register);
            $expenseBreakdown = $this->expenseBreakdown($register);
            $expectedClosing  = $register->opening_balance
                + $register->total_received
                - $register->total_expense;
        }

        return ApiResponse::make('Cash Register Status', [
            'register'          => $register,
            'payment_breakdown' => $paymentBreakdown,
            'expense_breakdown' => $expenseBreakdown,
            'expected_closing'  => $expectedClosing,
        ]);
    }

    public function open(Request $request)
    {
        $loggedInUser = user();
        $warehouseObj = warehouse();

        $existing = CashRegister::where('user_id', $loggedInUser->id)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return ApiResponse::make('Register already open', ['register' => $existing]);
        }

        $register = new CashRegister();
        $register->company_id      = $loggedInUser->company_id ?? 1;
        $register->user_id         = $loggedInUser->id;
        $register->warehouse_id    = $warehouseObj ? $warehouseObj->id : null;
        $register->opening_balance = (float) $request->input('opening_balance', 0);
        $register->total_sales     = 0;
        $register->total_received  = 0;
        $register->total_expense   = 0;
        $register->status          = 'open';
        $register->opened_at       = now();
        $register->notes           = $request->input('notes', null);
        $register->save();

        return ApiResponse::make('Cash Register Opened', ['register' => $register]);
    }

    public function close(Request $request)
    {
        $loggedInUser = user();

        $register = CashRegister::where('user_id', $loggedInUser->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        if (!$register) {
            throw new ApiException('No open cash register found.');
        }

        $actualCash = (float) $request->input('actual_cash', 0);

        $expectedClosing = $register->opening_balance
            + $register->total_received
            - $register->total_expense;

        $difference = $actualCash - $expectedClosing;

        // Capture breakdowns before updating closed_at
        $paymentBreakdown = $this->paymentBreakdown($register);
        $expenseBreakdown = $this->expenseBreakdown($register);

        $register->actual_cash     = $actualCash;
        $register->closing_balance = $expectedClosing;
        $register->status          = 'closed';
        $register->closed_at       = now();
        $register->notes           = $request->input('notes', null);
        $register->save();

        return ApiResponse::make('Cash Register Closed', [
            'register'          => $register,
            'payment_breakdown' => $paymentBreakdown,
            'expense_breakdown' => $expenseBreakdown,
            'expected_closing'  => $expectedClosing,
            'actual_cash'       => $actualCash,
            'difference'        => $difference,
        ]);
    }

    public function report()
    {
        $loggedInUser = user();

        $register = CashRegister::where('user_id', $loggedInUser->id)
            ->latest('opened_at')
            ->first();

        $empty = ['rows' => [], 'column_totals' => ['normal' => 0, 'credit' => 0, 'advance' => 0, 'grand' => 0]];

        if (!$register) {
            return ApiResponse::make('No register found', [
                'register'          => null,
                'payment_breakdown' => $empty,
                'expense_breakdown' => [],
                'expected_closing'  => 0,
                'difference'        => null,
            ]);
        }

        $expectedClosing  = $register->opening_balance
            + $register->total_received
            - $register->total_expense;

        $difference = $register->actual_cash !== null
            ? $register->actual_cash - $expectedClosing
            : null;

        return ApiResponse::make('Cash Register Report', [
            'register'          => $register,
            'payment_breakdown' => $this->paymentBreakdown($register),
            'expense_breakdown' => $this->expenseBreakdown($register),
            'expected_closing'  => $expectedClosing,
            'difference'        => $difference,
        ]);
    }

    public function history()
    {
        $loggedInUser = user();

        $registers = CashRegister::where('user_id', $loggedInUser->id)
            ->orderByDesc('opened_at')
            ->take(30)
            ->get();

        return ApiResponse::make('Cash Register History', ['registers' => $registers]);
    }
}
