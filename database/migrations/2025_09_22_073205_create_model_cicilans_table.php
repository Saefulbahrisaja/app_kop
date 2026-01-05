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
        Schema::create('cicilan', function (Blueprint $table) {
           $table->id();
        $table->foreignId('loan_id')->constrained('pinjaman')->onDelete('cascade');
        $table->decimal('amount', 12, 2);
        $table->date('due_date');
        $table->date('paid_at')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_cicilans');
    }
};
