<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\ModelUser;

class KetuaSeeder extends Seeder
{
    public function run()
    {
        ModelUser::create([
            'username'        => 'ketua',
            'password'        => Hash::make('123456'),
            'full_name'       => 'Ketua Koperasi',
            'alamat'          => 'Kantor Koperasi',
            'tanggal_lahir'   => '1980-01-01',
            'agama'           => 'Islam',
            'tanggal_gabung'  => now(),
            'no_hp'           => '081234567890',
            'email'           => 'ketua@koperasi.test',
            'no_anggota'      => '00000',
            'role'            => 'KETUA'
        ]);
    }
}
