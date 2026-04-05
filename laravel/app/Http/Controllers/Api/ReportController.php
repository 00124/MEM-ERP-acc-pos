<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Warehouse;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Examyou\RestAPI\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends ApiBaseController
{
    // public function profitLoss()
    // {
    //     $request = request();
    //     $warehouse = warehouse();

    //     $sales = Order::where('order_type', 'sales');
    //     $purchases = Order::where('order_type', 'purchases');
    //     $salesReturns = Order::where('order_type', 'sales-returns');
    //     $purchaseReturns = Order::where('order_type', 'purchase-returns');
    //     $stockTransferTransfered = Order::where('order_type', 'stock-transfers');
    //     $stockTransferReceived = Order::where('order_type', 'stock-transfers');
    //     $expenses = Expense::select('amount');

    //     $paymentReceived = Payment::where('payment_type', 'in');
    //     $paymentSent = Payment::where('payment_type', 'out');

    //     // Dates Filters
    //     if ($request->has('dates') && $request->dates != null && count($request->dates) > 0) {
    //         $dates = $request->dates;
    //         $startDate = $dates[0];
    //         $endDate = $dates[1];

    //         $sales = $sales->whereRaw('orders.order_date >= ?', [$startDate])
    //             ->whereRaw('orders.order_date <= ?', [$endDate]);
    //         $purchases = $purchases->whereRaw('orders.order_date >= ?', [$startDate])
    //             ->whereRaw('orders.order_date <= ?', [$endDate]);
    //         $salesReturns = $salesReturns->whereRaw('orders.order_date >= ?', [$startDate])
    //             ->whereRaw('orders.order_date <= ?', [$endDate]);
    //         $purchaseReturns = $purchaseReturns->whereRaw('orders.order_date >= ?', [$startDate])
    //             ->whereRaw('orders.order_date <= ?', [$endDate]);
    //         $stockTransferTransfered = $stockTransferTransfered->whereRaw('orders.order_date >= ?', [$startDate])
    //             ->whereRaw('orders.order_date <= ?', [$endDate]);
    //         $stockTransferReceived = $stockTransferReceived->whereRaw('orders.order_date >= ?', [$startDate])
    //             ->whereRaw('orders.order_date <= ?', [$endDate]);
    //         $expenses = $expenses->whereRaw('expenses.date >= ?', [$startDate])
    //             ->whereRaw('expenses.date <= ?', [$endDate]);

    //         $paymentReceived = $paymentReceived->whereRaw('payments.date >= ?', [$startDate])
    //             ->whereRaw('payments.date <= ?', [$endDate]);
    //         $paymentSent = $paymentSent->whereRaw('payments.date >= ?', [$startDate])
    //             ->whereRaw('payments.date <= ?', [$endDate]);
    //     }

    //     $sales = $sales->where('orders.warehouse_id', $warehouse->id);
    //     $purchases = $purchases->where('orders.warehouse_id', $warehouse->id);
    //     $salesReturns = $salesReturns->where('orders.warehouse_id', $warehouse->id);
    //     $purchaseReturns = $purchaseReturns->where('orders.warehouse_id', $warehouse->id);
    //     $stockTransferTransfered = $stockTransferTransfered->where('orders.from_warehouse_id', $warehouse->id);
    //     $stockTransferReceived = $stockTransferReceived->where('orders.warehouse_id', $warehouse->id);
    //     $expenses = $expenses->where('expenses.warehouse_id', $warehouse->id);

    //     $paymentReceived = $paymentReceived->where('payments.warehouse_id', $warehouse->id);
    //     $paymentSent = $paymentSent->where('payments.warehouse_id', $warehouse->id);

    //     $sales = $sales->sum('total');
    //     $purchases = $purchases->sum('total');
    //     $salesReturns = $salesReturns->sum('total');
    //     $purchaseReturns = $purchaseReturns->sum('total');
    //     $stockTransferTransfered = $stockTransferTransfered->sum('total');
    //     $stockTransferReceived = $stockTransferReceived->sum('total');
    //     $expenses = $expenses->sum('amount');

    //     $paymentReceived = $paymentReceived->sum('amount');
    //     $paymentSent = $paymentSent->sum('amount');

    //     $profit = $sales + $purchaseReturns + $stockTransferTransfered - $purchases - $salesReturns - $stockTransferReceived - $expenses;
    //     $profitByPayment = $paymentReceived - $paymentSent - $expenses;

    //     return ApiResponse::make('Success', [
    //         'sales' => $sales,
    //         'purchases' => $purchases,
    //         'sales_returns' => $salesReturns,
    //         'purchase_returns' => $purchaseReturns,
    //         'stock_transfer_transfered' => $stockTransferTransfered,
    //         'stock_transfer_received' => $stockTransferReceived,
    //         'expenses' => $expenses,
    //         'profit' => $profit,
    //         'payment_received' => $paymentReceived,
    //         'payment_sent' => $paymentSent,
    //         'profit_by_payment' => $profitByPayment,
    //     ]);
    // }

    public function profitLoss()
    {
        $request = request();
        $dateResults = [];
        $company = company();
        $startDate = null;
        $endDate = null;
        $dateArray = [];

        // Dates Filters
        if ($request->has('dates') && $request->dates != null && count($request->dates) > 0) {
            $dates = $request->dates;
            $startDate = $dates[0];
            $endDate = $dates[1];

            $startDateStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $startDate, 'UTC')
                ->setTimezone($company->timezone)
                ->startOfDay()
                ->setTimezone('UTC')
                ->format('Y-m-d H:i:s');
            $startDateEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $startDate, 'UTC')
                ->setTimezone($company->timezone)
                ->endOfDay()
                ->setTimezone('UTC')
                ->format('Y-m-d H:i:s');

            $startDateInCompanyTimezone = Carbon::createFromFormat('Y-m-d H:i:s', $startDate, 'UTC')
                ->setTimezone($company->timezone)
                ->format('Y-m-d');

            $endDateInCompanyTimezone = Carbon::createFromFormat('Y-m-d H:i:s', $endDate, 'UTC')
                ->setTimezone($company->timezone)
                ->format('Y-m-d');

            $period = CarbonPeriod::create($startDateInCompanyTimezone, $endDateInCompanyTimezone);

            $dateRangeArray = [];
            foreach ($period as $date) {
                $dateRangeArray[] = $date->format('Y-m-d');
            }


            $dateRanges = array_reverse($dateRangeArray);
            foreach ($dateRanges as $dateRange) {
                $startDateStartTime = Carbon::createFromFormat('Y-m-d', $dateRange, 'UTC')
                    ->setTimezone($company->timezone)
                    ->startOfDay()
                    ->setTimezone('UTC')
                    ->format('Y-m-d H:i:s');
                $startDateEndTime = Carbon::createFromFormat('Y-m-d', $dateRange, 'UTC')
                    ->setTimezone($company->timezone)
                    ->endOfDay()
                    ->setTimezone('UTC')
                    ->format('Y-m-d H:i:s');

                $dateArray[] = [
                    'date' => $dateRange,
                    'start' => $startDateStartTime,
                    'end' => $startDateEndTime,
                ];

                $dateResults[] = [
                    'date' => Carbon::createFromFormat('Y-m-d', $dateRange, $company->timezone)
                        ->startOfDay()
                        ->setTimezone('UTC')
                        ->format('Y-m-d\TH:i:sP'),
                    'result' => $this->getProfitLossByDates($startDateStartTime, $startDateEndTime)
                ];
            }
        }

        return ApiResponse::make('Success', [
            'results' => $this->getProfitLossByDates($startDate, $endDate),
            'dates' => $dateResults,
            'dateArray' => $dateArray,
        ]);
    }

    public function getProfitLossByDates($startDate, $endDate)
    {
        $request = request();
        $warehouse = warehouse();

        $sales = Order::where('order_type', 'sales');
        $purchases = Order::where('order_type', 'purchases');
        $salesReturns = Order::where('order_type', 'sales-returns');
        $purchaseReturns = Order::where('order_type', 'purchase-returns');
        $stockTransferTransfered = Order::where('order_type', 'stock-transfers');
        $stockTransferReceived = Order::where('order_type', 'stock-transfers');
        $expenses = Expense::select('amount');

        $paymentReceived = Payment::where('payment_type', 'in');
        $paymentSent = Payment::where('payment_type', 'out');

        // Dates Filters
        if ($startDate != null && $endDate != null) {
            $sales = $sales->whereBetween('orders.order_date', [$startDate, $endDate]);
            $purchases = $purchases->whereBetween('orders.order_date', [$startDate, $endDate]);
            $salesReturns = $salesReturns->whereBetween('orders.order_date', [$startDate, $endDate]);
            $purchaseReturns = $purchaseReturns->whereBetween('orders.order_date', [$startDate, $endDate]);
            $stockTransferTransfered = $stockTransferTransfered->whereBetween('orders.order_date', [$startDate, $endDate]);
            $stockTransferReceived = $stockTransferReceived->whereBetween('orders.order_date', [$startDate, $endDate]);
            $expenses = $expenses->whereBetween('expenses.date', [$startDate, $endDate]);

            $paymentReceived = $paymentReceived->whereBetween('payments.date', [$startDate, $endDate]);
            $paymentSent = $paymentSent->whereBetween('payments.date', [$startDate, $endDate]);
        }

        $sales = $sales->where('orders.warehouse_id', $warehouse->id);
        $purchases = $purchases->where('orders.warehouse_id', $warehouse->id);
        $salesReturns = $salesReturns->where('orders.warehouse_id', $warehouse->id);
        $purchaseReturns = $purchaseReturns->where('orders.warehouse_id', $warehouse->id);
        $stockTransferTransfered = $stockTransferTransfered->where('orders.from_warehouse_id', $warehouse->id);
        $stockTransferReceived = $stockTransferReceived->where('orders.warehouse_id', $warehouse->id);
        $expenses = $expenses->where('expenses.warehouse_id', $warehouse->id);

        $paymentReceived = $paymentReceived->where('payments.warehouse_id', $warehouse->id);
        $paymentSent = $paymentSent->where('payments.warehouse_id', $warehouse->id);

        $sales = $sales->sum('total');
        $purchases = $purchases->sum('total');
        $salesReturns = $salesReturns->sum('total');
        $purchaseReturns = $purchaseReturns->sum('total');
        $stockTransferTransfered = $stockTransferTransfered->sum('total');
        $stockTransferReceived = $stockTransferReceived->sum('total');
        $expenses = $expenses->sum('amount');

        $paymentReceived = $paymentReceived->sum('amount');
        $paymentSent = $paymentSent->sum('amount');

        $profit = $sales + $purchaseReturns + $stockTransferTransfered - $purchases - $salesReturns - $stockTransferReceived - $expenses;
        $profitByPayment = $paymentReceived - $paymentSent - $expenses;

        return [
            'sales' => $sales,
            'purchases' => $purchases,
            'sales_returns' => $salesReturns,
            'purchase_returns' => $purchaseReturns,
            'stock_transfer_transfered' => $stockTransferTransfered,
            'stock_transfer_received' => $stockTransferReceived,
            'expenses' => $expenses,
            'profit' => $profit,
            'payment_received' => $paymentReceived,
            'payment_sent' => $paymentSent,
            'profit_by_payment' => $profitByPayment,
        ];
    }

    // ─── Branch-wise P&L ──────────────────────────────────────────────────────

    public function branchProfitLoss()
    {
        $request   = request();
        $company   = company();
        $startDate = $request->start_date ?? date('Y-01-01') . ' 00:00:00';
        $endDate   = $request->end_date   ?? now()->format('Y-m-d') . ' 23:59:59';

        $warehouses = Warehouse::where('company_id', $company->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);

        $branches = [];
        $consolidated = [
            'sales'            => 0,
            'purchases'        => 0,
            'sales_returns'    => 0,
            'purchase_returns' => 0,
            'expenses'         => 0,
            'profit'           => 0,
            'payment_received' => 0,
        ];

        foreach ($warehouses as $warehouse) {
            $pl = $this->getPLForWarehouse($warehouse->id, $startDate, $endDate);

            $consolidated['sales']            += $pl['sales'];
            $consolidated['purchases']        += $pl['purchases'];
            $consolidated['sales_returns']    += $pl['sales_returns'];
            $consolidated['purchase_returns'] += $pl['purchase_returns'];
            $consolidated['expenses']         += $pl['expenses'];
            $consolidated['profit']           += $pl['profit'];
            $consolidated['payment_received'] += $pl['payment_received'];

            $branches[] = [
                'id'     => $warehouse->id,
                'name'   => $warehouse->name,
                'email'  => $warehouse->email,
                'phone'  => $warehouse->phone,
                'pl'     => $pl,
            ];
        }

        return ApiResponse::make('Branch Profit & Loss', [
            'branches'     => $branches,
            'consolidated' => $consolidated,
            'start_date'   => $startDate,
            'end_date'     => $endDate,
        ]);
    }

    private function getPLForWarehouse(int $warehouseId, $startDate, $endDate): array
    {
        $sales = Order::where('order_type', 'sales')
            ->where('warehouse_id', $warehouseId)
            ->where('cancelled', 0)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->sum('total');

        $purchases = Order::where('order_type', 'purchases')
            ->where('warehouse_id', $warehouseId)
            ->where('cancelled', 0)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->sum('total');

        $salesReturns = Order::where('order_type', 'sales-returns')
            ->where('warehouse_id', $warehouseId)
            ->where('cancelled', 0)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->sum('total');

        $purchaseReturns = Order::where('order_type', 'purchase-returns')
            ->where('warehouse_id', $warehouseId)
            ->where('cancelled', 0)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->sum('total');

        $expenses = Expense::where('warehouse_id', $warehouseId)
            ->whereBetween('date', [
                Carbon::parse($startDate)->toDateString(),
                Carbon::parse($endDate)->toDateString(),
            ])
            ->sum('amount');

        $paymentReceived = Payment::where('payment_type', 'in')
            ->where('warehouse_id', $warehouseId)
            ->whereBetween('date', [
                Carbon::parse($startDate)->toDateString(),
                Carbon::parse($endDate)->toDateString(),
            ])
            ->sum('amount');

        $netRevenue = $sales - $salesReturns + $purchaseReturns;
        $profit     = $netRevenue - $purchases - $expenses;

        return [
            'sales'            => (float) $sales,
            'purchases'        => (float) $purchases,
            'sales_returns'    => (float) $salesReturns,
            'purchase_returns' => (float) $purchaseReturns,
            'expenses'         => (float) $expenses,
            'net_revenue'      => (float) $netRevenue,
            'profit'           => (float) $profit,
            'payment_received' => (float) $paymentReceived,
        ];
    }

    // ── Dead Stock Report ─────────────────────────────────────────────────────
    //
    // Products that have current_stock > 0 but haven't been sold
    // in at least $threshold days (default 60). Ordered worst-first.

    public function deadStock(Request $request)
    {
        $companyId   = company()->id;
        $threshold   = (int) ($request->days ?? 60);
        $warehouseId = $request->warehouse_id
            ? \App\Classes\Common::getIdFromHash($request->warehouse_id)
            : null;

        $warehouseClause = $warehouseId ? "AND pd.warehouse_id = ?" : "";
        $bindings = array_filter(
            [$companyId, $warehouseId, $threshold],
            fn($v) => $v !== null
        );

        $rows = DB::select("
            SELECT
                p.name                                          AS product_name,
                p.item_code,
                COALESCE(c.name, '—')                          AS category_name,
                w.name                                          AS warehouse_name,
                pd.current_stock,
                ROUND(pd.purchase_price, 2)                    AS purchase_price,
                ROUND(pd.current_stock * pd.purchase_price, 2) AS stock_value,
                DATE(MAX(o.order_date))                        AS last_sale_date,
                CASE
                    WHEN MAX(o.order_date) IS NULL THEN NULL
                    ELSE DATEDIFF(CURDATE(), MAX(o.order_date))
                END                                            AS days_inactive
            FROM product_details pd
            JOIN products p ON p.id = pd.product_id AND p.company_id = ?
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN warehouses w ON w.id = pd.warehouse_id
            LEFT JOIN order_items oi ON oi.product_id = pd.product_id
                AND oi.warehouse_id = pd.warehouse_id
            LEFT JOIN orders o ON o.id = oi.order_id
                AND o.order_type = 'sales'
                AND o.cancelled = 0
            WHERE pd.current_stock > 0
            $warehouseClause
            GROUP BY
                pd.product_id, pd.warehouse_id,
                p.name, p.item_code, c.name, w.name,
                pd.current_stock, pd.purchase_price
            HAVING last_sale_date IS NULL OR days_inactive >= ?
            ORDER BY days_inactive DESC, stock_value DESC
        ", array_values($bindings));

        $totalValue = array_sum(array_column($rows, 'stock_value'));
        $totalQty   = array_sum(array_column($rows, 'current_stock'));

        return ApiResponse::make('Dead Stock Report', [
            'rows'        => $rows,
            'total_value' => round($totalValue, 2),
            'total_qty'   => $totalQty,
            'threshold'   => $threshold,
            'count'       => count($rows),
        ]);
    }
}
