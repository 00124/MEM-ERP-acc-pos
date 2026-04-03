<?php

namespace App\Http\Controllers\Api;

use App\Models\CashRegister;
use App\Models\Warehouse;
use App\Http\Controllers\ApiBaseController;
use Examyou\RestAPI\ApiResponse;
use Examyou\RestAPI\Exceptions\ApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashRegisterController extends ApiBaseController
{
    /**
     * Resolve the branch (warehouse) that belongs to the logged-in user.
     * Always uses user.warehouse_id — never the request header — so that
     * each user is locked to their assigned branch.
     */
    private function userBranchWarehouse(): ?Warehouse
    {
        $loggedInUser = user();

        if (!$loggedInUser || !$loggedInUser->warehouse_id) {
            return null;
        }

        return Warehouse::find($loggedInUser->warehouse_id);
    }

    /**
     * Safe warehouse payload for API responses.
     */
    private function warehouseInfo(?Warehouse $wh): array
    {
        if (!$wh) return ['id' => null, 'name' => 'Unknown Branch', 'address' => '', 'phone' => '', 'email' => ''];
        return [
            'id'       => $wh->id,
            'name'     => $wh->name,
            'address'  => $wh->address ?? '',
            'phone'    => $wh->phone   ?? '',
            'email'    => $wh->email   ?? '',
        ];
    }

    // ── Payment breakdown matrix: payment_mode × invoice_type ────────────────

    private function paymentBreakdown(CashRegister $register): array
    {
        $closeAt = $register->closed_at ?? now();

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

        $methods   = [];
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
            $colTotals[$type]          = ($colTotals[$type] ?? 0.0) + $amount;
            $colTotals['grand']       += $amount;
        }

        $rows = array_values($methods);
        usort($rows, fn($a, $b) => $b['total'] <=> $a['total']);

        return ['rows' => $rows, 'column_totals' => $colTotals];
    }

    // ── Expense breakdown by category ────────────────────────────────────────

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

    // ── Find open register scoped to user's own branch ───────────────────────

    private function findOpenRegister(): ?CashRegister
    {
        $loggedInUser = user();

        return CashRegister::where('user_id', $loggedInUser->id)
            ->where('warehouse_id', $loggedInUser->warehouse_id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function status()
    {
        $register  = $this->findOpenRegister();
        $warehouse = $this->userBranchWarehouse();

        $empty = ['rows' => [], 'column_totals' => ['normal' => 0, 'credit' => 0, 'advance' => 0, 'grand' => 0]];
        $paymentBreakdown = $empty;
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
            'warehouse'         => $this->warehouseInfo($warehouse),
            'payment_breakdown' => $paymentBreakdown,
            'expense_breakdown' => $expenseBreakdown,
            'expected_closing'  => $expectedClosing,
        ]);
    }

    public function open(Request $request)
    {
        $loggedInUser = user();

        // Always lock to the user's own assigned branch — never trust request headers
        $warehouse = $this->userBranchWarehouse();

        if (!$warehouse) {
            throw new ApiException('Your account is not assigned to any branch. Please contact the administrator.');
        }

        // One open register per user per branch
        $existing = CashRegister::where('user_id', $loggedInUser->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return ApiResponse::make('Register already open', [
                'register'  => $existing,
                'warehouse' => $this->warehouseInfo($warehouse),
            ]);
        }

        $register = new CashRegister();
        $register->company_id      = $loggedInUser->company_id ?? 1;
        $register->user_id         = $loggedInUser->id;
        $register->warehouse_id    = $warehouse->id;           // user's branch — enforced here
        $register->opening_balance = (float) $request->input('opening_balance', 0);
        $register->total_sales     = 0;
        $register->total_received  = 0;
        $register->total_expense   = 0;
        $register->status          = 'open';
        $register->opened_at       = now();
        $register->notes           = $request->input('notes', null);
        $register->save();

        return ApiResponse::make('Cash Register Opened', [
            'register'  => $register,
            'warehouse' => $this->warehouseInfo($warehouse),
        ]);
    }

    public function close(Request $request)
    {
        $register = $this->findOpenRegister();

        if (!$register) {
            throw new ApiException('No open cash register found for your branch.');
        }

        $actualCash = (float) $request->input('actual_cash', 0);

        $expectedClosing = $register->opening_balance
            + $register->total_received
            - $register->total_expense;

        $difference = $actualCash - $expectedClosing;

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
            'warehouse'         => $this->warehouseInfo($this->userBranchWarehouse()),
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
        $warehouse    = $this->userBranchWarehouse();

        // Latest register for this user's branch (open or closed)
        $register = CashRegister::where('user_id', $loggedInUser->id)
            ->where('warehouse_id', $loggedInUser->warehouse_id)
            ->latest('opened_at')
            ->first();

        $empty = ['rows' => [], 'column_totals' => ['normal' => 0, 'credit' => 0, 'advance' => 0, 'grand' => 0]];

        if (!$register) {
            return ApiResponse::make('No register found', [
                'register'          => null,
                'warehouse'         => $this->warehouseInfo($warehouse),
                'payment_breakdown' => $empty,
                'expense_breakdown' => [],
                'expected_closing'  => 0,
                'difference'        => null,
            ]);
        }

        $expectedClosing = $register->opening_balance
            + $register->total_received
            - $register->total_expense;

        $difference = $register->actual_cash !== null
            ? $register->actual_cash - $expectedClosing
            : null;

        return ApiResponse::make('Cash Register Report', [
            'register'          => $register,
            'warehouse'         => $this->warehouseInfo($warehouse),
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
            ->where('warehouse_id', $loggedInUser->warehouse_id)
            ->orderByDesc('opened_at')
            ->take(30)
            ->get();

        return ApiResponse::make('Cash Register History', ['registers' => $registers]);
    }
}
