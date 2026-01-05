<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ModelCicilan;
use App\Models\ModelUser;

class ModelPinjaman extends Model
{
    protected $table = 'pinjaman';
    protected $fillable = ['user_id','amount','term_months','status','loan_type','note','approved_at'];
    public function user()
    {
        return $this->belongsTo(ModelUser::class, 'user_id');
    }

    // ✅ RELASI KE CICILAN
    public function installments()
    {
        return $this->hasMany(ModelCicilan::class, 'loan_id');
    }

}
