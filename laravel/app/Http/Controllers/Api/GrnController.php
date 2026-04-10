<?php

namespace App\Http\Controllers\Api;

use App\Classes\Common;
use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Api\Purchase\IndexRequest;
use App\Http\Requests\Api\Grn\StoreRequest;
use App\Http\Requests\Api\Grn\UpdateRequest;
use App\Http\Requests\Api\Purchase\DeleteRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\AccountingService;
use App\Traits\OrderTraits;
use Examyou\RestAPI\ApiResponse;
use Examyou\RestAPI\Exceptions\ApiException;
use Illuminate\Support\Facades\Log;
use Throwable;

class GrnController extends ApiBaseController
{
    use OrderTraits { stored as traitStored; }

    protected $model = Order::class;

    protected $indexRequest = IndexRequest::class;
    protected $storeRequest = StoreRequest::class;
    protected $updateRequest = UpdateRequest::class;
    protected $deleteRequest = DeleteRequest::class;

    public function __construct()
    {
        parent::__construct();
        $this->orderType = 'grn';
    }

    /**
     * Override store to surface the real exception message instead of "An unknown error occurred".
     */
    public function store()
    {
        try {
            return parent::store();
        } catch (ApiException $e) {
            throw $e;
        } catch (Throwable $e) {
            try {
                \DB::rollBack();
            } catch (\Throwable $ignored) {
            }
            $message = $e->getMessage();
            if (empty($message)) {
                $message = 'An error occurred while creating the GRN.';
            }
            throw new ApiException($message, $e, null, 400);
        }
    }

    /**
     * Hook called after a GRN is successfully stored.
     * Auto-creates a journal entry (DR Inventory / CR Accounts Payable).
     */
    public function stored(Order $order)
    {
        $this->traitStored($order);

        try {
            $result = AccountingService::onPurchaseCreated($order);
            if (!$result['ok']) {
                Log::warning('[GRN] JE creation failed for ' . $order->invoice_number . ': ' . $result['message']);
            } else {
                Log::info('[GRN] JE auto-created for ' . $order->invoice_number . ': ' . $result['message']);
            }
        } catch (\Throwable $e) {
            Log::error('[GRN] JE auto-create exception for ' . $order->invoice_number . ': ' . $e->getMessage());
        }
    }

    /**
     * Get a purchase order details for creating GRN (prefill items as PO lines).
     */
    public function purchaseOrderForGrn($id)
    {
        $orderId = Common::getIdFromHash($id);
        $order = Order::with('user')->where('order_type', 'purchases')->findOrFail($orderId);
        $items = OrderItem::with('product:id,name,item_code')
            ->where('order_id', $order->id)
            ->get()
            ->map(function ($item) {
                return [
                    'xid' => $item->xid,
                    'item_id' => $item->xid,
                    'name' => $item->product->name ?? '',
                    'item_code' => $item->product->item_code ?? '',
                    'quantity' => (float) $item->quantity,
                    'received_quantity' => (float) $item->quantity,
                    'short_damaged_quantity' => 0,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                    'x_unit_id' => $item->x_unit_id,
                    'tax_rate' => $item->tax_rate,
                    'tax_type' => $item->tax_type,
                    'discount_rate' => $item->discount_rate,
                    'total_discount' => $item->total_discount,
                    'total_tax' => $item->total_tax,
                    'single_unit_price' => $item->single_unit_price,
                ];
            });

        return ApiResponse::make('Fetched', [
            'order' => $order->only(['xid', 'invoice_number', 'order_date', 'user_id', 'warehouse_id', 'x_warehouse_id', 'x_user_id']),
            'supplier_name' => $order->user->name ?? '',
            'items' => $items,
        ]);
    }
}
