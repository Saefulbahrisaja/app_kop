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
        Schema::create('pinjaman', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user')->onDelete('cascade');
            $table->bigInteger('amount');
            $table->integer('term_months');
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('approved_by_bendahara_at')->nullable();
            $table->dateTime('approved_by_ketua_at')->nullable();
            $table->text('note')->nullable();
            $table->enum('loan_type', ['REGULER','TALANGAN'])->default('REGULER');
            $table->enum('status', ['PENDING','APPROVED','APPROVED_BENDAHARA','REJECTED','LUNAS'])->default('PENDING');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pinjaman');
    }
};
