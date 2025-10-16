<?php
namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\ModelUser;
use Illuminate\Support\Facades\Hash;


class DatabaseSeeder extends Seeder {
    public function run(){
        // User biasa
        ModelUser::create([
            'username' => 'user1',
            'password' => Hash::make('password'),
            'full_name' => 'User Biasa',
            'role' => 'MEMBER',
            'email' => 'user1@example.com'
        ]);

        // Bendahara
        ModelUser::create([
            'username' => 'bendahara',
            'password' => Hash::make('password'),
            'full_name' => 'Bendahara Koperasi',
            'role' => 'BENDAHARA',
            'email' => 'bendahara@example.com'
        ]);
}
}