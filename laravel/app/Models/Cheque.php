<?php

namespace App\Models;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;

class Cheque extends Model
{
    protected $table = 'cheques';

    protected $guarded = ['id', 'company_id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'company_id', 'cheque_book_id', 'payment_id'];

    protected $appends = ['xid', 'x_cheque_book_id'];

    protected $casts = [
        'issue_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($cheque) {
            $cheque->company_id = company()->id;
        });
    }

    public function getXidAttribute()
    {
        return hashids()->encode($this->id);
    }

    public function getXChequeBookIdAttribute()
    {
        return hashids()->encode($this->cheque_book_id);
    }

    public function chequeBook()
    {
        return $this->belongsTo(ChequeBook::class, 'cheque_book_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}
