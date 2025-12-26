<?php

namespace App\Models;
use App\Models\ModelUser as User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modeltariksimpanan extends Model
{
    use HasFactory;
    protected $table = 'tarik_simpanan';
    protected $fillable = ['user_id','type','amount','status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
