<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelSimpanan extends Model
{
    protected $table = 'simpanan';
    protected $fillable = ['user_id','type','amount','period'];
    
}
