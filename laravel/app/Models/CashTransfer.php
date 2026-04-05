<?php

namespace App\Models;

use App\Casts\Hash;
use App\Models\BaseModel;
use App\Scopes\CompanyScope;

class CashTransfer extends BaseModel
{
    protected $table = 'cash_transfers';

    protected $default = ['xid'];

    protected $dates = ['transfer_date'];

    protected $guarded = ['id', 'company_id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'company_id', 'from_warehouse_id', 'to_warehouse_id', 'transferred_by'];

    protected $appends = ['xid', 'x_from_warehouse_id', 'x_to_warehouse_id', 'x_transferred_by'];

    protected $hashableGetterFunctions = [
        'getXFromWarehouseIdAttribute' => 'from_warehouse_id',
        'getXToWarehouseIdAttribute'   => 'to_warehouse_id',
        'getXTransferredByAttribute'   => 'transferred_by',
    ];

    protected $casts = [
        'transfer_date' => 'datetime',
        'amount'        => 'double',
        'from_warehouse_id' => Hash::class . ':hash',
        'to_warehouse_id'   => Hash::class . ':hash',
        'transferred_by'    => Hash::class . ':hash',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new CompanyScope);
    }

    public function fromWarehouse()
    {
        return $this->hasOne(Warehouse::class, 'id', 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->hasOne(Warehouse::class, 'id', 'to_warehouse_id');
    }

    public function transferredBy()
    {
        return $this->hasOne(User::class, 'id', 'transferred_by');
    }
}
