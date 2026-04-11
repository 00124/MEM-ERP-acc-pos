<?php

namespace App\Http\Controllers\Api;

use App\Classes\Common;
use App\Classes\Notify;
use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Api\StockAdjustment\IndexRequest;
use App\Http\Requests\Api\StockAdjustment\StoreRequest;
use App\Http\Requests\Api\StockAdjustment\UpdateRequest;
use App\Http\Requests\Api\StockAdjustment\DeleteRequest;
use App\Models\StockAdjustment;
use App\Models\StockHistory;
use Examyou\RestAPI\ApiResponse;
use Examyou\RestAPI\Exceptions\ApiException;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends ApiBaseController
{
    protected $model       = StockAdjustment::class;
    protected $indexRequest  = IndexRequest::class;
    protected $storeRequest  = StoreRequest::class;
    protected $updateRequest = UpdateRequest::class;
    protected $deleteRequest = DeleteRequest::class;

    public function modifyIndex($query)
    {
        $request   = request();
        $warehouse = warehouse();

        $query = $query->where('stock_adjustments.warehouse_id', $warehouse->id);

        if ($request->filled('warranty_type')) {
            $query = $query->where('warranty_type', $request->warranty_type);
        }
        if ($request->filled('status')) {
            $query = $query->where('status', $request->status);
        }
        if ($request->filled('mode')) {
            if ($request->mode === 'warranty') {
                $query = $query->whereNotNull('warranty_type');
            } else {
                $query = $query->whereNull('warranty_type');
            }
        }

        return $query;
    }

    // ── CREATE ────────────────────────────────────────────────────────
    public function storing(StockAdjustment $stockAdjustment)
    {
        $request   = request();
        $warehouse = warehouse();

        $stockAdjustment->created_by    = auth('api')->user()->id;
        $stockAdjustment->warehouse_id  = $warehouse->id;
        $stockAdjustment->warranty_type = $request->warranty_type ?: null;
        $stockAdjustment->remarks       = $request->remarks ?: null;

        // Warranty record → start as pending, no immediate stock impact
        if ($stockAdjustment->warranty_type) {
            $stockAdjustment->adjustment_type = 'subtract';
            $stockAdjustment->status          = 'pending';
            return $stockAdjustment;
        }

        // Classic adjustment → immediate stock history
        $stockAdjustment->status = 'completed';
        $sh = new StockHistory();
        $sh->warehouse_id = $stockAdjustment->warehouse_id;
        $sh->product_id   = $stockAdjustment->product_id;
        $sh->quantity     = $stockAdjustment->quantity;
        $sh->old_quantity = 0;
        $sh->order_type   = 'stock_adjustment';
        $sh->stock_type   = $stockAdjustment->adjustment_type === 'add' ? 'in' : 'out';
        $sh->action_type  = 'add_' . $stockAdjustment->adjustment_type;
        $sh->created_by   = auth('api')->user()->id;
        $sh->save();

        return $stockAdjustment;
    }

    public function stored(StockAdjustment $stockAdjustment)
    {
        Common::recalculateOrderStock($stockAdjustment->warehouse_id, $stockAdjustment->product_id);
        Notify::send('stock_adjustment_create', $stockAdjustment);
    }

    // ── UPDATE ────────────────────────────────────────────────────────
    public function updating(StockAdjustment $stockAdjustment)
    {
        $old = StockAdjustment::find($stockAdjustment->id);
        $stockAdjustment->created_by = user()->id;

        if ($old->warranty_type && $old->status !== 'pending') {
            throw new ApiException('Only pending warranty records can be edited.');
        }

        if (!$old->warranty_type && $old->quantity != $stockAdjustment->quantity) {
            $oldType  = $old->adjustment_type;
            $newType  = $stockAdjustment->adjustment_type;
            $stockType = ($oldType === 'add' && $newType === 'add') ? 'in'
                : (($oldType === 'subtract' && $newType === 'subtract') ? 'out'
                : ($oldType === 'add' ? 'out' : 'in'));

            $sh = new StockHistory();
            $sh->warehouse_id = $stockAdjustment->warehouse_id;
            $sh->product_id   = $stockAdjustment->product_id;
            $sh->quantity     = $stockAdjustment->quantity;
            $sh->old_quantity = $old->quantity;
            $sh->order_type   = 'stock_adjustment';
            $sh->stock_type   = $stockType;
            $sh->action_type  = "edit_{$oldType}_{$newType}";
            $sh->created_by   = auth('api')->user()->id;
            $sh->save();
        }

        return $stockAdjustment;
    }

    public function updated(StockAdjustment $stockAdjustment)
    {
        Common::recalculateOrderStock($stockAdjustment->warehouse_id, $stockAdjustment->product_id);
        Notify::send('stock_adjustment_update', $stockAdjustment);
    }

    // ── DELETE ────────────────────────────────────────────────────────
    public function destroying(StockAdjustment $stockAdjustment)
    {
        $loggedUser = auth('api')->user();

        if (!$loggedUser->hasRole('admin') && $stockAdjustment->warehouse_id != $loggedUser->warehouse_id) {
            throw new ApiException("Don't have valid permission");
        }
        if ($stockAdjustment->warranty_type && $stockAdjustment->status !== 'pending') {
            throw new ApiException('Only pending warranty records can be deleted.');
        }

        if (!$stockAdjustment->warranty_type) {
            $sh = new StockHistory();
            $sh->warehouse_id = $stockAdjustment->warehouse_id;
            $sh->product_id   = $stockAdjustment->product_id;
            $sh->quantity     = 0;
            $sh->old_quantity = $stockAdjustment->quantity;
            $sh->order_type   = 'stock_adjustment';
            $sh->stock_type   = $stockAdjustment->adjustment_type === 'add' ? 'in' : 'out';
            $sh->action_type  = 'delete_' . $stockAdjustment->adjustment_type;
            $sh->created_by   = auth('api')->user()->id;
            $sh->save();

            Common::recalculateOrderStock($stockAdjustment->warehouse_id, $stockAdjustment->product_id);
        }

        return $stockAdjustment;
    }

    public function destroyed(StockAdjustment $stockAdjustment)
    {
        Common::recalculateOrderStock($stockAdjustment->warehouse_id, $stockAdjustment->product_id);
        Notify::send('stock_adjustment_delete', $stockAdjustment);
    }

    // ── APPROVE warranty record → reduces available stock ─────────────
    public function approve($xid)
    {
        $id  = Common::getIdFromHash($xid);
        $adj = StockAdjustment::findOrFail($id);

        if (!$adj->warranty_type) {
            throw new ApiException('This is a classic adjustment, not a warranty record.');
        }
        if ($adj->status !== 'pending') {
            throw new ApiException('Only pending records can be approved.');
        }

        DB::transaction(function () use ($adj) {
            $adj->status = 'approved';
            $adj->save();

            $sh = new StockHistory();
            $sh->warehouse_id = $adj->warehouse_id;
            $sh->product_id   = $adj->product_id;
            $sh->quantity     = $adj->quantity;
            $sh->old_quantity = 0;
            $sh->order_type   = 'stock_adjustment';
            $sh->stock_type   = 'out';
            $sh->action_type  = 'add_subtract';
            $sh->created_by   = auth('api')->user()->id;
            $sh->save();

            Common::recalculateOrderStock($adj->warehouse_id, $adj->product_id);
        });

        return ApiResponse::make('Approved. Stock reduced.', [
            'stock_adjustment' => $adj->fresh()->load('product', 'warehouse'),
        ]);
    }

    // ── REPLACE: vendor sends replacement → restores stock ───────────
    public function replace($xid)
    {
        $id  = Common::getIdFromHash($xid);
        $adj = StockAdjustment::findOrFail($id);

        if (!in_array($adj->warranty_type, ['claimable', 'return_to_vendor'])) {
            throw new ApiException('Replacement is only for Claimable or Return to Vendor records.');
        }
        if ($adj->status !== 'approved') {
            throw new ApiException('Only approved records can be marked as replaced.');
        }

        DB::transaction(function () use ($adj) {
            $adj->status = 'completed';
            $adj->save();

            $sh = new StockHistory();
            $sh->warehouse_id = $adj->warehouse_id;
            $sh->product_id   = $adj->product_id;
            $sh->quantity     = $adj->quantity;
            $sh->old_quantity = 0;
            $sh->order_type   = 'stock_adjustment';
            $sh->stock_type   = 'in';
            $sh->action_type  = 'add_add';
            $sh->created_by   = auth('api')->user()->id;
            $sh->save();

            Common::recalculateOrderStock($adj->warehouse_id, $adj->product_id);
        });

        return ApiResponse::make('Replacement received. Stock restored.', [
            'stock_adjustment' => $adj->fresh()->load('product', 'warehouse'),
        ]);
    }

    // ── WARRANTY REPORT ───────────────────────────────────────────────
    public function warrantyReport()
    {
        $warehouse = warehouse();

        $rows = StockAdjustment::with(['product:id,name,item_code', 'warehouse:id,name'])
            ->where('warehouse_id', $warehouse->id)
            ->whereNotNull('warranty_type')
            ->orderBy('created_at', 'desc')
            ->get();

        $summary = [];
        foreach (['damage', 'expired', 'claimable', 'return_to_vendor'] as $type) {
            $t = $rows->where('warranty_type', $type);
            $summary[$type] = [
                'total'        => $t->count(),
                'pending_qty'  => round($t->where('status', 'pending')->sum('quantity'), 2),
                'approved_qty' => round($t->where('status', 'approved')->sum('quantity'), 2),
                'completed_qty'=> round($t->where('status', 'completed')->sum('quantity'), 2),
            ];
        }

        return ApiResponse::make('Warranty report.', [
            'summary' => $summary,
            'records' => $rows->map(fn($r) => [
                'xid'          => $r->xid,
                'product_name' => $r->product->name ?? '',
                'item_code'    => $r->product->item_code ?? '',
                'warehouse'    => $r->warehouse->name ?? '',
                'quantity'     => $r->quantity,
                'warranty_type'=> $r->warranty_type,
                'status'       => $r->status,
                'remarks'      => $r->remarks,
                'notes'        => $r->notes,
                'created_at'   => $r->created_at?->format('Y-m-d'),
            ])->values(),
        ]);
    }
}
