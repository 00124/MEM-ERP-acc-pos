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
     * Payments received by mode during a register session.
     * Returns [{ name, total }] ordered by total desc.
     */
    private function paymentBreakdown(CashRegister $register): array
    {
        $closeAt = $register->closed_at ?? now();

        return DB::table('payments')
            ->join('payment_modes', 'payments.payment_mode_id', '=', 'payment_modes.id')
            ->where('payments.warehouse_id', $register->warehouse_id)
            ->where('payments.payment_type', 'in')
            ->whereBetween('payments.created_at', [$register->opened_at, $closeAt])
            ->groupBy('payment_modes.id', 'payment_modes.name')
            ->select('payment_modes.name', DB::raw('SUM(payments.paid_amount) as total'))
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    /**
     * Expenses by category during a register session.
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

    /**
     * Get the current open register for the logged-in user.
     */
    public function status()
    {
        $loggedInUser = user();

        $register = CashRegister::where('user_id', $loggedInUser->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        $paymentBreakdown = [];
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

    /**
     * Open a new cash register for today.
     */
    public function open(Request $request)
    {
        $loggedInUser = user();
        $warehouseObj = warehouse();

        // Enforce one open register per user
        $existing = CashRegister::where('user_id', $loggedInUser->id)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return ApiResponse::make('Register already open', [
                'register' => $existing,
            ]);
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

        return ApiResponse::make('Cash Register Opened', [
            'register' => $register,
        ]);
    }

    /**
     * Close the current open register.
     */
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

        // Capture breakdowns before closing (so timestamps still in range)
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

    /**
     * Daily cash report — full summary of the current (or last) register.
     */
    public function report()
    {
        $loggedInUser = user();

        $register = CashRegister::where('user_id', $loggedInUser->id)
            ->latest('opened_at')
            ->first();

        if (!$register) {
            return ApiResponse::make('No register found', [
                'register'          => null,
                'payment_breakdown' => [],
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

        $paymentBreakdown = $this->paymentBreakdown($register);
        $expenseBreakdown = $this->expenseBreakdown($register);

        return ApiResponse::make('Cash Register Report', [
            'register'          => $register,
            'payment_breakdown' => $paymentBreakdown,
            'expense_breakdown' => $expenseBreakdown,
            'expected_closing'  => $expectedClosing,
            'difference'        => $difference,
        ]);
    }

    /**
     * List all registers (history) for the logged-in user.
     */
    public function history()
    {
        $loggedInUser = user();

        $registers = CashRegister::where('user_id', $loggedInUser->id)
            ->orderByDesc('opened_at')
            ->take(30)
            ->get();

        return ApiResponse::make('Cash Register History', [
            'registers' => $registers,
        ]);
    }
}
