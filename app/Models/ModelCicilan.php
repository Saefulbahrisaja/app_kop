<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ModelPinjaman;

class ModelCicilan extends Model
{
    use HasFactory;

    protected $table = 'cicilan';

    protected $fillable = [
        'loan_id','amount','due_date','paid_at'
    ];

     protected $casts = [
        'due_date' => 'date',
        'paid_at'  => 'datetime'
    ];


    public function loan()
    {
        return $this->belongsTo(ModelPinjaman::class, 'loan_id');
    }
    
}
