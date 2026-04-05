<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use Examyou\RestAPI\ApiResponse;
use Illuminate\Support\Facades\DB;

class NotificationsController extends ApiBaseController
{
    /**
     * Returns all alert categories for the Notifications UI.
     */
    public function alerts()
    {
        $company = company();

        return ApiResponse::make('Notifications', [
            'low_stock'      => $this->lowStockAlerts($company->id),
            'high_due'       => $this->highDueCustomers($company->id),
            'cash_transfers' => $this->recentCashTransfers($company->id),
            'counts'         => $this->getCounts($company->id),
        ]);
    }

    /**
     * Summary counts for the bell badge.
     */
    public function counts()
    {
        $company = company();

        return ApiResponse::make('Notification Counts', $this->getCounts($company->id));
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function getCounts(int $companyId): array
    {
        $lowStock = DB::table('product_details as pd')
            ->join('products as p', 'p.id', '=', 'pd.product_id')
            ->where('p.company_id', $companyId)
            ->where('pd.stock_quantitiy_alert', '>', 0)
            ->whereRaw('pd.current_stock <= pd.stock_quantitiy_alert')
            ->count();

        $highDue = DB::table('orders as o')
            ->join('users as u', 'u.id', '=', 'o.user_id')
            ->where('o.order_type', 'sales')
            ->where('o.company_id', $companyId)
            ->where('o.cancelled', 0)
            ->where('o.due_amount', '>', 0)
            ->distinct('o.user_id')
            ->count('o.user_id');

        $recentTransfers = DB::table('cash_transfers')
            ->where('company_id', $companyId)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return [
            'low_stock'       => (int) $lowStock,
            'high_due'        => (int) $highDue,
            'cash_transfers'  => (int) $recentTransfers,
            'total'           => (int) ($lowStock + $highDue + $recentTransfers),
        ];
    }

    private function lowStockAlerts(int $companyId): array
    {
        return DB::table('product_details as pd')
            ->join('products as p', 'p.id', '=', 'pd.product_id')
            ->join('warehouses as w', 'w.id', '=', 'pd.warehouse_id')
            ->where('p.company_id', $companyId)
            ->where('pd.stock_quantitiy_alert', '>', 0)
            ->whereRaw('pd.current_stock <= pd.stock_quantitiy_alert')
            ->orderByRaw('(pd.stock_quantitiy_alert - pd.current_stock) DESC')
            ->limit(50)
            ->select([
                'p.name as product_name',
                'p.item_code',
                'w.name as warehouse_name',
                'pd.current_stock',
                'pd.stock_quantitiy_alert as alert_qty',
                DB::raw('(pd.stock_quantitiy_alert - pd.current_stock) as shortage'),
            ])
            ->get()
            ->toArray();
    }

    private function highDueCustomers(int $companyId): array
    {
        return DB::table('orders as o')
            ->join('users as u', 'u.id', '=', 'o.user_id')
            ->where('o.order_type', 'sales')
            ->where('o.company_id', $companyId)
            ->where('o.cancelled', 0)
            ->where('o.due_amount', '>', 0)
            ->groupBy('o.user_id', 'u.name', 'u.phone', 'u.email')
            ->orderByRaw('SUM(o.due_amount) DESC')
            ->limit(50)
            ->select([
                'u.name as customer_name',
                'u.phone',
                'u.email',
                DB::raw('SUM(o.due_amount) as total_due'),
                DB::raw('COUNT(o.id) as invoice_count'),
                DB::raw('MAX(o.order_date) as last_order'),
            ])
            ->get()
            ->toArray();
    }

    private function recentCashTransfers(int $companyId): array
    {
        return DB::table('cash_transfers as ct')
            ->join('warehouses as fw', 'fw.id', '=', 'ct.from_warehouse_id')
            ->join('warehouses as tw', 'tw.id', '=', 'ct.to_warehouse_id')
            ->join('users as u', 'u.id', '=', 'ct.transferred_by')
            ->where('ct.company_id', $companyId)
            ->orderBy('ct.created_at', 'desc')
            ->limit(20)
            ->select([
                'ct.reference_number',
                'ct.amount',
                'ct.transfer_type',
                'ct.transfer_date',
                'ct.notes',
                'ct.created_at',
                'fw.name as from_branch',
                'tw.name as to_branch',
                'u.name as transferred_by',
            ])
            ->get()
            ->toArray();
    }
}
