<?php

namespace App\Models;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Model;

class DispatchItem extends Model
{
    protected $table = 'dispatch_items';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'dispatch_id', 'product_id', 'order_item_id', 'warehouse_id'];

    protected $appends = ['xid', 'x_product_id', 'x_warehouse_id'];

    protected $hashableGetterFunctions = [
        'getXProductIdAttribute'   => 'product_id',
        'getXWarehouseIdAttribute' => 'warehouse_id',
    ];

    public function getXidAttribute()
    {
        return hashids()->encode($this->id);
    }

    public function getXProductIdAttribute()
    {
        return hashids()->encode($this->product_id);
    }

    public function getXWarehouseIdAttribute()
    {
        return hashids()->encode($this->warehouse_id);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
