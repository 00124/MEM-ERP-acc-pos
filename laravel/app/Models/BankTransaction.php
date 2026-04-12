<?php

namespace App\Models;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    protected $table = 'bank_transactions';
    protected $guarded = ['id', 'company_id', 'created_at', 'updated_at'];
    protected $hidden  = ['id', 'company_id', 'from_account_id', 'to_account_id', 'created_by'];
    protected $appends = ['xid', 'x_from_account_id', 'x_to_account_id'];
    protected $casts   = ['transaction_date' => 'date'];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new CompanyScope);
        static::creating(fn($m) => $m->company_id = company()->id);
    }

    public function getXidAttribute()           { return hashids()->encode($this->id); }
    public function getXFromAccountIdAttribute() { return $this->from_account_id ? hashids()->encode($this->from_account_id) : null; }
    public function getXToAccountIdAttribute()  { return hashids()->encode($this->to_account_id); }

    public function fromAccount() { return $this->belongsTo(ChartOfAccount::class, 'from_account_id'); }
    public function toAccount()   { return $this->belongsTo(ChartOfAccount::class, 'to_account_id'); }
}
