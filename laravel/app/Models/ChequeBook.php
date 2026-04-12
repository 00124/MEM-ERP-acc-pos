<?php

namespace App\Models;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;

class ChequeBook extends Model
{
    protected $table = 'cheque_books';

    protected $guarded = ['id', 'company_id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'company_id', 'created_by'];

    protected $appends = ['xid'];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($book) {
            $book->company_id = company()->id;
        });
    }

    public function getXidAttribute()
    {
        return hashids()->encode($this->id);
    }

    public function cheques()
    {
        return $this->hasMany(Cheque::class, 'cheque_book_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
