<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; // ✅ ganti dari Model → Authenticatable
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use App\Models\ModelSimpanan;
use App\Models\ModelPinjaman;

class ModelUser extends Authenticatable implements AuthorizableContract
{
    use HasApiTokens, Notifiable, Authorizable;

    protected $table = 'user';

    protected $fillable = [
        'username',
        'password',
        'full_name',
        'alamat',
        'tanggal_lahir',
        'agama',
        'tanggal_gabung',
        'no_hp',
        'email',
        'no_anggota'
    ];

    protected $hidden = ['password'];

    public function savings()
    {
        return $this->hasMany(ModelSimpanan::class, 'user_id');
    }

    public function loans()
    {
        return $this->hasMany(ModelPinjaman::class, 'user_id');
    }
}
