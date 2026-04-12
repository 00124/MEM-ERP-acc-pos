<?php

namespace App\Http\Controllers\Api;

use App\Classes\Common;
use App\Http\Controllers\Controller;
use App\Models\Dispatch;
use App\Models\DispatchItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductDetails;
use App\Models\Warehouse;
use App\Scopes\CompanyScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DispatchController extends Controller
{
    /**
     * List all dispatches for current company, with optional filters.
     */
    public function index(Request $request)
    {
        $query = Dispatch::with(['sale', 'warehouse', 'customer', 'items.product'])
            ->orderBy('id', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('warehouse_id')) {
            $warehouseId = hashids()->decode($request->warehouse_id)[0] ?? null;
            if ($warehouseId) $query->where('warehouse_id', $warehouseId);
        }

        if ($request->filled('date_from')) {
            $query->where('dispatch_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('dispatch_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('dispatch_number', 'like', "%{$search}%")
                  ->orWhere('driver_name', 'like', "%{$search}%")
                  ->orWhere('vehicle_no', 'like', "%{$search}%");
            });
        }

        $limit  = (int)($request->limit ?? 15);
        $offset = (int)($request->offset ?? 0);
        $total  = $query->count();

        $dispatches = $query->skip($offset)->take($limit)->get();

        return response()->json([
            'data' => $dispatches,
            'meta' => [
                'paging' => ['total' => $total],
            ],
        ]);
    }

    /**
     * Return a single dispatch with items.
     */
    public function show($xid)
    {
        $id = hashids()->decode($xid)[0] ?? null;
        if (!$id) abort(404, 'Dispatch not found');

        $dispatch = Dispatch::with(['sale', 'warehouse', 'customer', 'items.product', 'items.warehouse'])
            ->findOrFail($id);

        return response()->json(['data' => $dispatch]);
    }

    /**
     * Load sale items for building the dispatch form.
     * Returns: sale info + order items with product name, qty, default warehouse.
     */
    public function saleItems(Request $request)
    {
        $xid = $request->sale_id;
        $id  = hashids()->decode($xid)[0] ?? null;
        if (!$id) abort(404, 'Sale not found');

        $sale = Order::withoutGlobalScopes()
            ->where('company_id', company()->id)
            ->where('order_type', 'sales')
            ->with(['user', 'warehouse', 'staffMember', 'items.product'])
            ->findOrFail($id);

        $warehouses = Warehouse::withoutGlobalScopes()
            ->where('company_id', company()->id)
            ->where('is_disable', 0)
            ->select('id', 'name')
            ->get()
            ->map(fn($w) => ['xid' => hashids()->encode($w->id), 'name' => $w->name]);

        $items = $sale->items->map(function ($item) use ($sale) {
            return [
                'xid'           => $item->xid,
                'x_product_id'  => hashids()->encode($item->product_id),
                'product_name'  => $item->product?->name ?? '-',
                'item_code'     => $item->product?->item_code ?? '-',
                'quantity'      => $item->quantity,
                'x_warehouse_id' => hashids()->encode($sale->warehouse_id),
            ];
        });

        return response()->json([
            'sale'       => [
                'xid'            => $sale->xid,
                'invoice_number' => $sale->invoice_number,
                'customer_name'  => $sale->user?->name ?? '-',
                'salesman_name'  => $sale->staffMember?->name ?? '-',
                'x_customer_id'  => $sale->user ? hashids()->encode($sale->user_id) : null,
                'x_warehouse_id' => hashids()->encode($sale->warehouse_id),
            ],
            'items'      => $items,
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Create one or more dispatches from a sale, grouped by warehouse.
     * Validates stock availability per warehouse before creation.
     */
    public function store(Request $request)
    {
        $request->validate([
            'sale_id'          => 'required|string',
            'dispatch_date'    => 'required|date',
            'driver_name'      => 'nullable|string|max:100',
            'vehicle_no'       => 'nullable|string|max:50',
            'remarks'          => 'nullable|string',
            'items'            => 'required|array|min:1',
            'items.*.xid'      => 'required|string',
            'items.*.x_warehouse_id' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $saleId = hashids()->decode($request->sale_id)[0] ?? null;
        if (!$saleId) abort(422, 'Invalid sale');

        $sale = Order::withoutGlobalScopes()
            ->where('company_id', company()->id)
            ->where('order_type', 'sales')
            ->with('items.product', 'user')
            ->findOrFail($saleId);

        // Build a map from order_item xid → item
        $orderItemMap = $sale->items->keyBy(fn($i) => $i->xid);

        // Group dispatch items by warehouse
        $groups = [];
        foreach ($request->items as $reqItem) {
            $warehouseId = hashids()->decode($reqItem['x_warehouse_id'])[0] ?? null;
            if (!$warehouseId) abort(422, "Invalid warehouse for item");

            $orderItem = $orderItemMap[$reqItem['xid']] ?? null;
            if (!$orderItem) abort(422, "Order item not found: {$reqItem['xid']}");

            // Validate stock availability
            $productDetails = ProductDetails::withoutGlobalScope('current_warehouse')
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $orderItem->product_id)
                ->first();

            $currentStock = $productDetails?->current_stock ?? 0;
            if ($currentStock < $reqItem['quantity']) {
                $productName = $orderItem->product?->name ?? 'Product';
                $warehouse   = Warehouse::withoutGlobalScopes()->find($warehouseId);
                abort(422, "Insufficient stock for '{$productName}' in warehouse '{$warehouse?->name}'. Available: {$currentStock}, Requested: {$reqItem['quantity']}");
            }

            $groups[$warehouseId][] = [
                'order_item'  => $orderItem,
                'quantity'    => $reqItem['quantity'],
                'warehouse_id' => $warehouseId,
            ];
        }

        $created = [];

        DB::transaction(function () use ($sale, $groups, $request, &$created) {
            foreach ($groups as $warehouseId => $groupItems) {
                $dispatch = new Dispatch();
                $dispatch->sale_id       = $sale->id;
                $dispatch->warehouse_id  = $warehouseId;
                $dispatch->customer_id   = $sale->user_id;
                $dispatch->dispatch_date = $request->dispatch_date;
                $dispatch->driver_name   = $request->driver_name;
                $dispatch->vehicle_no    = $request->vehicle_no;
                $dispatch->remarks       = $request->remarks;
                $dispatch->status        = 'pending';
                $dispatch->created_by    = user()->id;
                $dispatch->save();

                foreach ($groupItems as $groupItem) {
                    $dispatchItem = new DispatchItem();
                    $dispatchItem->dispatch_id   = $dispatch->id;
                    $dispatchItem->product_id    = $groupItem['order_item']->product_id;
                    $dispatchItem->order_item_id = $groupItem['order_item']->id;
                    $dispatchItem->quantity      = $groupItem['quantity'];
                    $dispatchItem->warehouse_id  = $groupItem['warehouse_id'];
                    $dispatchItem->save();
                }

                $dispatch->load(['warehouse', 'items.product', 'items.warehouse']);
                $created[] = $dispatch;
            }
        });

        return response()->json([
            'success'   => true,
            'message'   => count($created) . ' dispatch(es) created successfully.',
            'dispatches' => $created,
        ], 201);
    }

    /**
     * Update dispatch status.
     */
    public function updateStatus(Request $request, $xid)
    {
        $request->validate([
            'status' => 'required|in:pending,dispatched,delivered',
        ]);

        $id = hashids()->decode($xid)[0] ?? null;
        if (!$id) abort(404, 'Dispatch not found');

        $dispatch = Dispatch::findOrFail($id);
        $dispatch->status = $request->status;
        $dispatch->save();

        return response()->json(['success' => true, 'status' => $dispatch->status]);
    }

    /**
     * Return gate pass data for printing.
     */
    public function gatePass($xid)
    {
        $id = hashids()->decode($xid)[0] ?? null;
        if (!$id) abort(404, 'Dispatch not found');

        $dispatch = Dispatch::with([
            'sale.staffMember',
            'warehouse',
            'customer',
            'items.product',
            'items.warehouse',
        ])->findOrFail($id);

        return response()->json(['data' => $dispatch]);
    }

    /**
     * Delivery status report.
     */
    public function report(Request $request)
    {
        $query = Dispatch::with(['sale', 'warehouse', 'customer'])
            ->orderBy('dispatch_date', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('warehouse_id')) {
            $warehouseId = hashids()->decode($request->warehouse_id)[0] ?? null;
            if ($warehouseId) $query->where('warehouse_id', $warehouseId);
        }
        if ($request->filled('date_from')) {
            $query->where('dispatch_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('dispatch_date', '<=', $request->date_to);
        }

        $dispatches = $query->get();

        $summary = [
            'total'      => $dispatches->count(),
            'pending'    => $dispatches->where('status', 'pending')->count(),
            'dispatched' => $dispatches->where('status', 'dispatched')->count(),
            'delivered'  => $dispatches->where('status', 'delivered')->count(),
        ];

        return response()->json([
            'data'    => $dispatches,
            'summary' => $summary,
        ]);
    }
}
