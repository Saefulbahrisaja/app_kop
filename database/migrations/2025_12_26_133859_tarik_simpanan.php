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
        Schema::create('tarik_simpanan', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('user')->onDelete('cascade');
        $table->enum('type', ['manasuka','pokok','wajib']);
        $table->bigInteger('amount');
        $table->enum('status', ['PENDING','APPROVED','REJECTED'])->default('PENDING');
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
