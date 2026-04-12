<?php

namespace App\Models;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;

class PartyCheque extends Model
{
    protected $table = 'party_cheques';
    protected $guarded = ['id', 'company_id', 'created_at', 'updated_at'];
    protected $hidden  = ['id', 'company_id', 'party_id', 'bank_account_id', 'created_by'];
    protected $appends = ['xid', 'x_party_id', 'x_bank_account_id'];
    protected $casts   = ['cheque_date' => 'date', 'action_date' => 'date'];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new CompanyScope);
        static::creating(fn($m) => $m->company_id = company()->id);
    }

    public function getXidAttribute()            { return hashids()->encode($this->id); }
    public function getXPartyIdAttribute()       { return hashids()->encode($this->party_id); }
    public function getXBankAccountIdAttribute() { return $this->bank_account_id ? hashids()->encode($this->bank_account_id) : null; }

    public function party()       { return $this->belongsTo(User::class, 'party_id'); }
    public function bankAccount() { return $this->belongsTo(ChartOfAccount::class, 'bank_account_id'); }
}
