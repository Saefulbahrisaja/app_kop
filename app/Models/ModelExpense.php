<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelExpense extends Model
{
    use HasFactory;

    protected $table = 'expense';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'amount',
        'approved_at',
        'note',
    ];

    protected $casts = [
        'amount'      => 'float',
        'approved_at' => 'date:Y-m-d',
    ];

    /*
    |--------------------------------------------------------------------------
    | ACCESSOR
    |--------------------------------------------------------------------------
    */

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 0, ',', '.');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPE
    |--------------------------------------------------------------------------
    */

    public function scopeThisMonth($query)
    {
        return $query
            ->whereYear('approved_at', now()->year)
            ->whereMonth('approved_at', now()->month);
    }

    public function scopeThisYear($query)
    {
        return $query
            ->whereYear('approved_at', now()->year);
    }
}