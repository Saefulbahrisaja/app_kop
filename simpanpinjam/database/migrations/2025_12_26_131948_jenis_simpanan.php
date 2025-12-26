<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jenis_simpanan', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();   // POKOK, WAJIB, MANASUKA
            $table->string('name');
            $table->integer('amount')->default(0);
            $table->boolean('mandatory')->default(false);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
