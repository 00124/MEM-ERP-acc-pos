<?php

namespace App\Models\Hrm;

use App\Casts\Hash;
use App\Models\BaseModel;
use App\Models\StaffMember;
use App\Models\Order;
use App\Scopes\CompanyScope;

class Incentive extends BaseModel
{
    protected $table = 'hrm_incentives';

    protected $default = ['xid'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'user_id', 'order_id', 'company_id'];

    protected $appends = ['xid', 'x_user_id', 'x_order_id'];

    protected $filterable = ['user_id'];

    protected $hashableGetterFunctions = [
        'getXUserIdAttribute'  => 'user_id',
        'getXOrderIdAttribute' => 'order_id',
    ];

    protected $casts = [
        'user_id'  => Hash::class . ':hash',
        'order_id' => Hash::class . ':hash',
        'amount'   => 'double',
        'date'     => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new CompanyScope);
    }

    public function user()
    {
        return $this->belongsTo(StaffMember::class, 'user_id', 'id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
