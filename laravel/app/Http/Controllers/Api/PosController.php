<?php

namespace App\Http\Controllers\Api;

use App\Classes\Common;
use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Api\Order\PosRequest;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductDetails;
use App\Models\Settings;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\AccountingService;
use Carbon\Carbon;
use Examyou\RestAPI\ApiResponse;
use Examyou\RestAPI\Exceptions\ApiException;

class PosController extends ApiBaseController
{
    public function posProducts()
    {
        $request = request();
        $allProducs = [];
        $warehouse = warehouse();
        $warehouseId = $warehouse->id;

        $products = Product::select(
            'products.id',
            'products.name',
            'products.image',
            'products.product_type',
            'product_details.sales_price',
            'products.unit_id',
            'product_details.sales_tax_type',
            'product_details.tax_id',
            'product_details.current_stock',
            'taxes.rate'
        )
            ->join('product_details', 'product_details.product_id', '=', 'products.id')
            ->leftJoin('taxes', 'taxes.id', '=', 'product_details.tax_id')
            ->join('units', 'units.id', '=', 'products.unit_id')
            ->where('product_details.warehouse_id', '=', $warehouseId);

        $products = $products->where(function ($query) {
            $query->where(function ($qry) {
                $qry->where('products.product_type', '!=', 'service')
                    ->where('product_details.current_stock', '>', 0);
            })->orWhere('products.product_type', '=', 'service');
        });

        if ($warehouse->products_visibility == 'warehouse') {
            $products->where('products.warehouse_id', '=', $warehouse->id);
        }

        // Category Filters
        if ($request->has('category_id') && $request->category_id != "") {
            $categoryId = $this->getIdFromHash($request->category_id);
            $products = $products->where('category_id', '=', $categoryId);
        }

        // Brand Filters
        if ($request->has('brand_id') && $request->brand_id != "") {
            $brandId = $this->getIdFromHash($request->brand_id);
            $products = $products->where('brand_id', '=', $brandId);
        }

        // Search Term — LIKE across product name and item_code (SKU/model number)
        if ($request->has('search_term') && trim($request->search_term) != "") {
            $searchTerm = '%' . trim(strtolower($request->search_term)) . '%';
            $products = $products->where(function ($query) use ($searchTerm) {
                $query->where(\DB::raw('LOWER(products.name)'), 'LIKE', $searchTerm)
                      ->orWhere(\DB::raw('LOWER(products.item_code)'), 'LIKE', $searchTerm)
                      ->orWhere(\DB::raw('LOWER(products.parent_item_code)'), 'LIKE', $searchTerm);
            });
        }

        $products = $products->get();

        // Pre-load all units and taxes as lookup maps to avoid N+1 queries
        $unitMap = Unit::all()->keyBy('id');
        $taxMap  = Tax::all()->keyBy('id');

        foreach ($products as $product) {
            $stockQuantity = $product->current_stock;
            $unit = $product->unit_id != null ? $unitMap->get($product->unit_id) : null;
            $tax  = $product->tax_id  != null ? $taxMap->get($product->tax_id)   : null;
            $taxType = $product->sales_tax_type;

            $unitPrice = $product->sales_price;
            $singleUnitPrice = $unitPrice;

            if ($product->rate != '') {
                $taxRate = $product->rate;

                if ($product->sales_tax_type == 'inclusive') {
                    $subTotal = $singleUnitPrice;
                    $singleUnitPrice =  ($singleUnitPrice * 100) / (100 + $taxRate);
                    $taxAmount = ($singleUnitPrice) * ($taxRate / 100);
                } else {
                    $taxAmount =  ($singleUnitPrice * ($taxRate / 100));
                    $subTotal = $singleUnitPrice + $taxAmount;
                }
            } else {
                $taxAmount = 0;
                $taxRate = 0;
                $subTotal = $singleUnitPrice;
            }

            $allProducs[] = [
                'item_id'    =>  '',
                'xid'    =>  $product->xid,
                'name'    =>  $product->name,
                'image'    =>  $product->image,
                'image_url'    =>  $product->image_url,
                'discount_rate'    =>  0,
                'total_discount'    =>  0,
                'x_tax_id'    => $tax ? $tax->xid : null,
                'tax_type'    =>  $taxType,
                'tax_rate'    =>  $taxRate,
                'total_tax'    =>  $taxAmount,
                'x_unit_id'    =>  $unit ? $unit->xid : null,
                'unit'    =>  $unit,
                'unit_price'    =>  $unitPrice,
                'single_unit_price'    =>  $singleUnitPrice,
                'subtotal'    =>  $subTotal,
                'quantity'    =>  1,
                'stock_quantity'    =>  $stockQuantity,
                'unit_short_name'    =>  $unit ? $unit->short_name : '',
                'product_type'      => $product->product_type
            ];
        }

        $data = [
            'products' => $allProducs,
        ];

        return ApiResponse::make('Data fetched', $data);
    }

    public function posWarehouses()
    {
        $warehouses = Warehouse::select('id', 'name')->get()->map(function ($w) {
            return ['xid' => $w->xid, 'name' => $w->name];
        });
        return ApiResponse::make('Fetched', ['warehouses' => $warehouses]);
    }

    public function allWarehouseStock()
    {
        $request = request();
        $productId = $this->getIdFromHash($request->product_xid);

        $warehouses = Warehouse::select('id', 'name')->get();
        $stockData = [];

        foreach ($warehouses as $warehouse) {
            $detail = ProductDetails::withoutGlobalScope('current_warehouse')
                ->where('warehouse_id', $warehouse->id)
                ->where('product_id', $productId)
                ->first();

            $stockData[] = [
                'warehouse_xid' => $warehouse->xid,
                'warehouse_name' => $warehouse->name,
                'stock_quantity' => $detail ? (float) $detail->current_stock : 0,
            ];
        }

        return ApiResponse::make('Fetched', ['stock' => $stockData]);
    }

    public function addPosPayment(PosRequest $request)
    {
        return ApiResponse::make('Success');
    }

    public function savePosPayments()
    {
        $t0 = microtime(true);
        $request = request();
        $loggedInUser = user();
        $warehouse = warehouse();
        \Log::info('[POS] start');

        // Use selected POS warehouse if provided
        if ($request->has('selected_warehouse_xid') && $request->selected_warehouse_xid) {
            $overrideWarehouseId = $this->getIdFromHash($request->selected_warehouse_xid);
            if ($overrideWarehouseId) {
                $overrideWarehouse = Warehouse::find($overrideWarehouseId);
                if ($overrideWarehouse) {
                    $warehouse = $overrideWarehouse;
                }
            }
        }

        $orderDetails = $request->details;
        $oldOrderId = "";
        $posDefaultStatus = $request->order_type == 'quotations' ? 'pending' : $warehouse->default_pos_order_status;

        $allPayments = $request->input('all_payments', []);
        if (!is_array($allPayments)) {
            $allPayments = [];
        }

        if ($request->has('all_payments') && count($request->all_payments) > 0) {
            $allPayments = collect($request->all_payments);

            $total = $allPayments->sum(function ($item) {
                return $item['amount'];
            });

            if ($total > $orderDetails['subtotal']) {
                throw new ApiException('Paid amount should be less than or equal to Grand Total');
            }
        }

        $order = new Order();
        // Determine sale mode: 'full', 'credit', 'advance'
        $saleMode = $request->input('sale_mode', 'full');

        $order->order_type = $request->order_type == 'quotations' ? 'quotations' : 'sales';
        $order->invoice_type = match($saleMode) {
            'credit'  => 'credit',
            'advance' => 'advance',
            default   => 'normal',
        };
        $order->unique_id = Common::generateOrderUniqueId();
        $order->invoice_number = "";
        $order->order_date = Carbon::now();
        $order->warehouse_id = $warehouse->id;
        $order->user_id = isset($orderDetails['user_id']) ? $orderDetails['user_id'] : null;
        $order->tax_id = isset($orderDetails['tax_id']) ? $orderDetails['tax_id'] : null;
        $order->tax_rate = $orderDetails['tax_rate'];
        $order->tax_amount = $orderDetails['tax_amount'];
        $order->discount = $orderDetails['discount'];
        $order->shipping = $orderDetails['shipping'];
        $order->subtotal = 0;
        $order->total = $orderDetails['subtotal'];
        $order->paid_amount = 0;
        $order->due_amount = $order->total;

        // Advance booking → mark order as pending (not yet fulfilled)
        if ($saleMode === 'advance') {
            $order->order_status = 'pending';
        } else {
            $order->order_status = $posDefaultStatus;
        }

        // Use selected salesman if provided, otherwise default to logged-in user
        $salesmanXid = $request->input('salesman_xid', null);
        if ($salesmanXid) {
            $salesmanId = $this->getIdFromHash($salesmanXid);
            $order->staff_user_id = $salesmanId ?: $loggedInUser->id;
        } else {
            $order->staff_user_id = $loggedInUser->id;
        }

        $order->save();

        $order->invoice_number = Common::getTransactionNumber($order->order_type, $order->id);
        $order->save();

        // Defer updateOrderAmount — will call once after payments are saved
        \Log::info('[POS] before storeAndUpdateOrder t=' . round(microtime(true)-$t0, 2));
        Common::storeAndUpdateOrder($order, $oldOrderId, true);
        \Log::info('[POS] after storeAndUpdateOrder t=' . round(microtime(true)-$t0, 2));

        $allPayments = $request->input('all_payments', []);
        if (!is_array($allPayments)) {
            $allPayments = [];
        }

        foreach ($allPayments as $allPayment) {
            // Save Order Payment
            if ($allPayment['amount'] > 0 && $allPayment['payment_mode_id'] != '') {
                $payment = new Payment();
                $payment->warehouse_id = $warehouse->id;
                $payment->payment_type = "in";
                $payment->date = Carbon::now();
                $payment->amount = $allPayment['amount'];
                $payment->paid_amount = $allPayment['amount'];
                $payment->payment_mode_id = $allPayment['payment_mode_id'];
                $payment->notes = $allPayment['notes'];
                $payment->user_id = $order->user_id;
                $payment->save();

                // Generate and save payment number
                $paymentType = 'payment-' . $payment->payment_type;
                $payment->payment_number = Common::getTransactionNumber($paymentType, $payment->id);
                $payment->save();

                $orderPayment = new OrderPayment();
                $orderPayment->order_id = $order->id;
                $orderPayment->payment_id = $payment->id;
                $orderPayment->amount = $allPayment['amount'];
                $orderPayment->save();
            }
        }

        // Single updateOrderAmount after payments — correct final state (skip inside updateWarehouseHistory)
        \Log::info('[POS] before updateOrderAmount t=' . round(microtime(true)-$t0, 2));
        Common::updateOrderAmount($order->id);
        \Log::info('[POS] after updateOrderAmount t=' . round(microtime(true)-$t0, 2));

        // Updating Warehouse History after payments so status is correct; skip its internal updateOrderAmount
        $order->refresh();
        Common::updateWarehouseHistory('order', $order, "add_edit", true);
        \Log::info('[POS] after updateWarehouseHistory t=' . round(microtime(true)-$t0, 2));

        // Auto-generate journal entry for POS sale
        AccountingService::handleOrder($order, $saleMode);
        \Log::info('[POS] after handleOrder t=' . round(microtime(true)-$t0, 2));

        $savedOrder = Order::select('id', 'unique_id', 'invoice_number', 'invoice_type', 'user_id', 'staff_user_id', 'order_date', 'discount', 'shipping', 'tax_amount', 'subtotal', 'total', 'paid_amount', 'due_amount', 'total_items', 'total_quantity', 'order_type')
            ->with(['user:id,name,email,phone', 'items:id,order_id,product_id,unit_id,unit_price,subtotal,quantity,mrp,total_tax,warehouse_id', 'items.product:id,name', 'items.unit:id,name,short_name', 'items.warehouse:id,name', 'orderPayments:id,order_id,payment_id,amount', 'orderPayments.payment:id,payment_mode_id', 'orderPayments.payment.paymentMode:id,name', 'staffMember:id,name'])
            ->find($order->id);

        $totalMrp = 0;
        $totalTax = 0;
        foreach ($savedOrder->items as $orderItem) {
            $totalMrp += ($orderItem->quantity * $orderItem->mrp);
            $totalTax += $orderItem->total_tax;
        }

        $savingOnMrp = $totalMrp - $savedOrder->total;
        $saving_percentage = $totalMrp > 0 ? number_format((float)($savingOnMrp / $totalMrp * 100), 2, '.', '') : 0;

        $savedOrder->saving_on_mrp = $savingOnMrp;
        $savedOrder->saving_percentage = $saving_percentage;
        $savedOrder->total_tax_on_items = $totalTax + $savedOrder->tax_amount;

        return ApiResponse::make('POS Data Saved', [
            'order' => $savedOrder,
        ]);
    }

    // ─── RECEIVE PAYMENT AGAINST EXISTING DUE BALANCE ─────────────────────
    public function receiveDuePayment()
    {
        $request   = request();
        $warehouse = warehouse();

        // Resolve order
        $orderId = $this->getIdFromHash($request->order_xid);
        $order   = Order::find($orderId);

        if (!$order) {
            throw new ApiException('Order not found');
        }

        $dueAmount  = round((float)$order->due_amount, 2);
        $payAmount  = round((float)$request->amount, 2);

        if ($payAmount <= 0) {
            throw new ApiException('Payment amount must be greater than zero');
        }

        if ($payAmount > $dueAmount + 0.01) {
            throw new ApiException("Payment amount ({$payAmount}) cannot exceed due amount ({$dueAmount})");
        }

        // Resolve payment mode
        $paymentModeId = '';
        if ($request->payment_mode_xid) {
            $paymentModeId = $this->getIdFromHash($request->payment_mode_xid);
        }

        // Create payment record
        $payment = new Payment();
        $payment->warehouse_id    = $warehouse->id;
        $payment->payment_type    = 'in';
        $payment->date            = Carbon::now();
        $payment->amount          = $payAmount;
        $payment->paid_amount     = $payAmount;
        $payment->payment_mode_id = $paymentModeId ?: null;
        $payment->notes           = $request->notes ?? '';
        $payment->user_id         = $order->user_id;
        $payment->company_id      = $order->company_id ?? 1;
        $payment->save();

        $payment->payment_number = Common::getTransactionNumber('payment-in', $payment->id);
        $payment->save();

        // Link payment to order
        $orderPayment             = new OrderPayment();
        $orderPayment->order_id   = $order->id;
        $orderPayment->payment_id = $payment->id;
        $orderPayment->amount     = $payAmount;
        $orderPayment->save();

        // Recalculate order amounts
        Common::updateOrderAmount($order->id);
        $order->refresh();

        // Post accounting: DR Cash, CR AR
        AccountingService::onPaymentReceived($payment, $order);

        return ApiResponse::make('Payment received successfully', [
            'order'   => [
                'xid'         => $order->xid,
                'invoice_number' => $order->invoice_number,
                'total'       => $order->total,
                'paid_amount' => $order->paid_amount,
                'due_amount'  => $order->due_amount,
            ],
            'payment' => [
                'payment_number' => $payment->payment_number,
                'amount'         => $payment->amount,
            ],
        ]);
    }

    // ─── CUSTOMER DUE ORDERS ──────────────────────────────────────────────
    public function customerDueOrders()
    {
        $request    = request();
        $customerId = $this->getIdFromHash($request->customer_xid ?? '');

        $query = Order::select('id', 'invoice_number', 'order_date', 'total', 'paid_amount', 'due_amount', 'order_status', 'user_id')
            ->where('order_type', 'sales')
            ->where('due_amount', '>', 0);

        if ($customerId) {
            $query->where('user_id', $customerId);
        }

        if ($request->invoice_number) {
            $query->where('invoice_number', 'like', '%' . $request->invoice_number . '%');
        }

        $orders = $query->orderByDesc('order_date')->limit(50)->get();

        $result = $orders->map(fn($o) => [
            'xid'            => $o->xid,
            'invoice_number' => $o->invoice_number,
            'order_date'     => $o->order_date,
            'total'          => $o->total,
            'paid_amount'    => $o->paid_amount,
            'due_amount'     => $o->due_amount,
            'order_status'   => $o->order_status,
        ]);

        return ApiResponse::make('Customer due orders', ['orders' => $result]);
    }
}
