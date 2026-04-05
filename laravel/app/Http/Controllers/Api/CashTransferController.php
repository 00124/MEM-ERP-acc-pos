<?php

namespace App\Http\Controllers\Api;

use App\Classes\Common;
use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Api\CashTransfer\IndexRequest;
use App\Http\Requests\Api\CashTransfer\StoreRequest;
use App\Models\CashRegister;
use App\Models\CashTransfer;
use App\Models\Warehouse;
use Carbon\Carbon;
use Examyou\RestAPI\ApiResponse;
use Examyou\RestAPI\Exceptions\ApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;

class CashTransferController extends ApiBaseController
{
    protected $model = CashTransfer::class;

    protected $indexRequest = IndexRequest::class;
    protected $storeRequest = StoreRequest::class;

    public function modifyIndex($query)
    {
        $request = request();
        $loggedUser = user();

        // Admin sees all; others see only transfers involving their warehouse
        if (!$loggedUser->hasRole('admin')) {
            $userWarehouseId = $loggedUser->warehouse_id;
            $query = $query->where(function ($q) use ($userWarehouseId) {
                $q->where('from_warehouse_id', $userWarehouseId)
                  ->orWhere('to_warehouse_id', $userWarehouseId);
            });
        }

        if ($request->has('transfer_type') && $request->transfer_type != '') {
            $query = $query->where('transfer_type', $request->transfer_type);
        }

        if ($request->has('dates') && $request->dates != '') {
            $dates = explode(',', $request->dates);
            $query = $query->whereRaw('transfer_date >= ?', [$dates[0]])
                           ->whereRaw('transfer_date <= ?', [$dates[1]]);
        }

        if ($request->has('warehouse_id') && $request->warehouse_id != '') {
            $warehouseId = Common::getIdFromHash($request->warehouse_id);
            $query = $query->where(function ($q) use ($warehouseId) {
                $q->where('from_warehouse_id', $warehouseId)
                  ->orWhere('to_warehouse_id', $warehouseId);
            });
        }

        return $query;
    }

    public function storing(CashTransfer $transfer)
    {
        $request = request();
        $loggedUser = user();

        $fromWarehouseId = Common::getIdFromHash($request->from_warehouse_id);
        $toWarehouseId   = Common::getIdFromHash($request->to_warehouse_id);

        if (!$fromWarehouseId || !$toWarehouseId) {
            throw new ApiException('Invalid warehouse selection.');
        }

        if ($fromWarehouseId === $toWarehouseId) {
            throw new ApiException('Source and destination branch cannot be the same.');
        }

        $transfer->company_id       = $loggedUser->company_id ?? 1;
        $transfer->from_warehouse_id = $fromWarehouseId;
        $transfer->to_warehouse_id   = $toWarehouseId;
        $transfer->transferred_by    = $loggedUser->id;
        $transfer->reference_number  = 'CT-' . strtoupper(substr(uniqid(), -6));
        $transfer->transfer_date     = Carbon::parse($request->transfer_date)->format('Y-m-d');

        return $transfer;
    }

    public function stored(CashTransfer $transfer)
    {
        // Update all open cash registers at the FROM warehouse (cash going out)
        CashRegister::where('warehouse_id', $transfer->from_warehouse_id)
            ->where('status', 'open')
            ->increment('total_cash_out', $transfer->amount);

        // Update all open cash registers at the TO warehouse (cash coming in)
        CashRegister::where('warehouse_id', $transfer->to_warehouse_id)
            ->where('status', 'open')
            ->increment('total_cash_in', $transfer->amount);
    }

    /**
     * Summary endpoint — totals for the current date range / filters
     */
    public function summary(Request $request)
    {
        $loggedUser = user();

        $query = CashTransfer::query();

        if (!$loggedUser->hasRole('admin')) {
            $userWarehouseId = $loggedUser->warehouse_id;
            $query->where(function ($q) use ($userWarehouseId) {
                $q->where('from_warehouse_id', $userWarehouseId)
                  ->orWhere('to_warehouse_id', $userWarehouseId);
            });
        }

        if ($request->has('dates') && $request->dates != '') {
            $dates = explode(',', $request->dates);
            $query->whereRaw('transfer_date >= ?', [$dates[0]])
                  ->whereRaw('transfer_date <= ?', [$dates[1]]);
        }

        $totals = $query->selectRaw('
            SUM(amount) as grand_total,
            SUM(CASE WHEN transfer_type = "ho_to_branch" THEN amount ELSE 0 END) as ho_to_branch,
            SUM(CASE WHEN transfer_type = "branch_to_ho" THEN amount ELSE 0 END) as branch_to_ho,
            SUM(CASE WHEN transfer_type = "branch_to_branch" THEN amount ELSE 0 END) as branch_to_branch
        ')->first();

        return ApiResponse::make('Cash Transfer Summary', [
            'grand_total'      => (float) ($totals->grand_total ?? 0),
            'ho_to_branch'     => (float) ($totals->ho_to_branch ?? 0),
            'branch_to_ho'     => (float) ($totals->branch_to_ho ?? 0),
            'branch_to_branch' => (float) ($totals->branch_to_branch ?? 0),
        ]);
    }
}
