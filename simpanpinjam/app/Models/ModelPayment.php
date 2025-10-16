<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ModelPinjaman;
use App\Models\ModelCicilan;
use App\Models\ModelUser;

class ModelPayment extends Model
{
    use HasFactory;

    protected $table = 'payment';

    protected $fillable = [
        'loan_id',
        'installment_id',
        'user_id',
        'amount',
        'status',
        'note',
        'proof',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function loan()
    {
        return $this->belongsTo(ModelPinjaman::class, 'loan_id');
    }

    public function installment()
    {
        return $this->belongsTo(ModelCicilan::class, 'installment_id');
    }

    public function user()
    {
        return $this->belongsTo(ModelUser::class, 'user_id');
    }
}
