<?php

namespace App\Models;

use App\Scopes\CompanyScope;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Order;
use Illuminate\Database\Eloquent\Model;

class Dispatch extends Model
{
    protected $table = 'dispatches';

    protected $guarded = ['id', 'company_id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'company_id', 'sale_id', 'warehouse_id', 'customer_id', 'created_by'];

    protected $appends = ['xid', 'x_sale_id', 'x_warehouse_id', 'x_customer_id'];

    protected $hashableGetterFunctions = [
        'getXSaleIdAttribute'      => 'sale_id',
        'getXWarehouseIdAttribute' => 'warehouse_id',
        'getXCustomerIdAttribute'  => 'customer_id',
    ];

    protected $casts = [
        'dispatch_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($dispatch) {
            $dispatch->company_id = company()->id;
            $dispatch->dispatch_number = self::generateDispatchNumber();
        });
    }

    public static function generateDispatchNumber(): string
    {
        $companyId = company()->id;
        $last = self::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->orderBy('id', 'desc')
            ->value('dispatch_number');

        $lastNum = $last ? (int) substr($last, 5) : 0;
        return 'DISP-' . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
    }

    public function getXidAttribute()
    {
        return hashids()->encode($this->id);
    }

    public function getXSaleIdAttribute()
    {
        return hashids()->encode($this->sale_id);
    }

    public function getXWarehouseIdAttribute()
    {
        return hashids()->encode($this->warehouse_id);
    }

    public function getXCustomerIdAttribute()
    {
        return $this->customer_id ? hashids()->encode($this->customer_id) : null;
    }

    public function sale()
    {
        return $this->belongsTo(Order::class, 'sale_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(DispatchItem::class, 'dispatch_id');
    }
}
