<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ModelUser;
use App\Models\ModelJenissimpanan as SimpananMaster;

class ModelSimpanan extends Model
{
    protected $table = 'simpanan';
    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'period',
        'paid_at',
        'approved_at', // ✅ WAJIB ADA
    ];

   public function user()
    {
        return $this->belongsTo(ModelUser::class, 'user_id');
    }

    public function master()
    {
        return $this->belongsTo(SimpananMaster::class, 'code', 'code');
    }
    
}
