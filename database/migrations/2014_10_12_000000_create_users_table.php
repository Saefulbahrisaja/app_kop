<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('user', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('full_name')->nullable();

            // ✅ Tambahan field baru
            $table->string('alamat')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('agama')->nullable();
            $table->date('tanggal_gabung')->nullable();
            $table->string('no_hp')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('no_anggota')->unique()->nullable();

            // ✅ Tambahan role (default MEMBER)
            $table->enum('role', ['MEMBER', 'BENDAHARA', 'KETUA'])->default('MEMBER');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user');
    }
};