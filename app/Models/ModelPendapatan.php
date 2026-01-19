<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelPendapatan extends Model
{
    protected $table = 'pendapatan';

    protected $fillable = [
        'user_id',
        'amount',
        'periode',
        'type',
        'note'
    ];
}
