<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ModelCicilan;

class ModelPinjaman extends Model
{
    protected $table = 'pinjaman';
    protected $fillable = ['user_id','amount','term_months','status','loan_type'];
    public function installments()
    {
        return $this->hasMany(ModelCicilan::class, 'loan_id');
    }

}
