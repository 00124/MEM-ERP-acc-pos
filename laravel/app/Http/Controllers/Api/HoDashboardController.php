<?php

namespace App\Http\Controllers\Api;

use App\Classes\Common;
use App\Http\Controllers\ApiBaseController;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Examyou\RestAPI\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HoDashboardController extends ApiBaseController
{
    // ── Helpers ──────────────────────────────────────────────────────────────

    private function parseDates(Request $request): array
    {
        $dateFrom = $request->date_from ?? now()->startOf('month')->toDateString();
        $dateTo   = $request->date_to   ?? now()->toDateString();
        return [$dateFrom, $dateTo];
    }

    private function salesBaseQuery(int $companyId, string $from, string $to)
    {
        return Order::where('company_id', $companyId)
            ->where('order_type', 'sales')
            ->where('cancelled', 0)
            ->whereBetween(DB::raw('DATE(order_date)'), [$from, $to]);
    }

    private function paymentsBaseQuery(int $companyId, string $from, string $to)
    {
        return Payment::where('company_id', $companyId)
            ->where('payment_type', 'in')
            ->whereBetween(DB::raw('DATE(date)'), [$from, $to]);
    }

    // ── Main endpoint ─────────────────────────────────────────────────────────

    public function getData(Request $request)
    {
        $companyId          = company()->id;
        [$dateFrom, $dateTo] = $this->parseDates($request);

        // ── 1. KPI Totals ────────────────────────────────────────────────────
        $kpi = $this->salesBaseQuery($companyId, $dateFrom, $dateTo)
            ->selectRaw('
                COUNT(*)          AS total_orders,
                SUM(total)        AS total_sales,
                SUM(paid_amount)  AS total_paid,
                SUM(due_amount)   AS total_credit
            ')
            ->first();

        $cashReceived = $this->paymentsBaseQuery($companyId, $dateFrom, $dateTo)
            ->sum('amount');

        $advancePayments = $this->paymentsBaseQuery($companyId, $dateFrom, $dateTo)
            ->where('unused_amount', '>', 0)
            ->sum('unused_amount');

        // ── 2. Store-wise Breakdown ──────────────────────────────────────────
        $storeWise = $this->salesBaseQuery($companyId, $dateFrom, $dateTo)
            ->select(
                'warehouses.name as store_name',
                DB::raw('SUM(orders.total)       AS total_sales'),
                DB::raw('COUNT(*)                AS total_orders'),
                DB::raw('SUM(orders.paid_amount) AS cash_sales'),
                DB::raw('SUM(orders.due_amount)  AS credit_sales')
            )
            ->join('warehouses', 'warehouses.id', '=', 'orders.warehouse_id')
            ->groupBy('orders.warehouse_id', 'warehouses.name')
            ->orderByDesc('total_sales')
            ->get();

        // Add per-store advance amounts
        $storeAdvance = $this->paymentsBaseQuery($companyId, $dateFrom, $dateTo)
            ->select('warehouse_id', DB::raw('SUM(unused_amount) AS advance'))
            ->where('unused_amount', '>', 0)
            ->groupBy('warehouse_id')
            ->pluck('advance', 'warehouse_id');

        // Attach advance to each store row (using warehouse_id from a sub-join)
        $storeWiseWithAdv = $this->salesBaseQuery($companyId, $dateFrom, $dateTo)
            ->select(
                'orders.warehouse_id',
                'warehouses.name as store_name',
                DB::raw('SUM(orders.total)       AS total_sales'),
                DB::raw('COUNT(*)                AS total_orders'),
                DB::raw('SUM(orders.paid_amount) AS cash_sales'),
                DB::raw('SUM(orders.due_amount)  AS credit_sales')
            )
            ->join('warehouses', 'warehouses.id', '=', 'orders.warehouse_id')
            ->groupBy('orders.warehouse_id', 'warehouses.name')
            ->orderByDesc('total_sales')
            ->get()
            ->map(function ($row) use ($storeAdvance) {
                $row->advance_sales = $storeAdvance[$row->warehouse_id] ?? 0;
                return $row;
            });

        // ── 3. Daily Sales Trend ─────────────────────────────────────────────
        $dailyTrend = $this->salesBaseQuery($companyId, $dateFrom, $dateTo)
            ->selectRaw('DATE(order_date) AS day, SUM(total) AS total, COUNT(*) AS orders')
            ->groupByRaw('DATE(order_date)')
            ->orderBy('day')
            ->get()
            ->map(fn($r) => ['date' => $r->day, 'total' => (float) $r->total, 'orders' => (int) $r->orders]);

        // ── 4. Payment Mode Distribution ─────────────────────────────────────
        $paymentModes = $this->paymentsBaseQuery($companyId, $dateFrom, $dateTo)
            ->select(
                'payment_modes.name as mode',
                DB::raw('SUM(payments.amount) AS total'),
                DB::raw('COUNT(*)             AS count')
            )
            ->leftJoin('payment_modes', 'payment_modes.id', '=', 'payments.payment_mode_id')
            ->groupBy('payments.payment_mode_id', 'payment_modes.name')
            ->orderByDesc('total')
            ->get();

        // ── 5. Top 10 Products ───────────────────────────────────────────────
        $topProducts = OrderItem::select(
                'products.name as product_name',
                'products.item_code',
                DB::raw('SUM(order_items.quantity)  AS total_qty'),
                DB::raw('SUM(order_items.subtotal)  AS total_amount')
            )
            ->join('orders',   'orders.id',   '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.company_id', $companyId)
            ->where('orders.order_type', 'sales')
            ->where('orders.cancelled', 0)
            ->whereBetween(DB::raw('DATE(orders.order_date)'), [$dateFrom, $dateTo])
            ->groupBy('order_items.product_id', 'products.name', 'products.item_code')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        return ApiResponse::make('Data fetched', [
            'kpi' => [
                'total_sales'      => (float) ($kpi->total_sales   ?? 0),
                'total_orders'     => (int)   ($kpi->total_orders  ?? 0),
                'cash_received'    => (float) $cashReceived,
                'credit_sales'     => (float) ($kpi->total_credit  ?? 0),
                'advance_payments' => (float) $advancePayments,
            ],
            'store_wise'    => $storeWiseWithAdv,
            'daily_trend'   => $dailyTrend,
            'payment_modes' => $paymentModes,
            'top_products'  => $topProducts,
            'date_from'     => $dateFrom,
            'date_to'       => $dateTo,
        ]);
    }
}
