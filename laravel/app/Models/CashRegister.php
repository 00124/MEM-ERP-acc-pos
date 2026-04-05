<?php

namespace App\Models;

use App\Casts\Hash;
use App\Models\BaseModel;
use App\Scopes\CompanyScope;

class CashRegister extends BaseModel
{
    protected $table = 'cash_registers';

    protected $default = ['xid'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'user_id', 'warehouse_id', 'company_id'];

    protected $appends = ['xid', 'x_user_id', 'x_warehouse_id'];

    protected $casts = [
        'opening_balance' => 'double',
        'closing_balance' => 'double',
        'total_sales'     => 'double',
        'total_received'  => 'double',
        'total_expense'   => 'double',
        'total_cash_in'   => 'double',
        'total_cash_out'  => 'double',
        'actual_cash'     => 'double',
        'opened_at'       => 'datetime',
        'closed_at'       => 'datetime',
    ];

    protected $hashableGetterFunctions = [
        'getXUserIdAttribute'      => 'user_id',
        'getXWarehouseIdAttribute' => 'warehouse_id',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new CompanyScope());
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
