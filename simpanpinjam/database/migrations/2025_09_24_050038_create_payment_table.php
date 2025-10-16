<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment', function (Blueprint $table) {
            $table->id();

            // Relasi ke tabel pinjaman & cicilan & user
            $table->foreignId('loan_id')
                  ->constrained('pinjaman')
                  ->onDelete('cascade');

            $table->foreignId('installment_id')
                  ->constrained('cicilan')
                  ->onDelete('cascade');

            $table->foreignId('user_id')
                  ->constrained('user')
                  ->onDelete('cascade');

            $table->decimal('amount', 15, 2);

            // Status pembayaran
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])
                  ->default('PENDING');

            // Opsional: catatan bendahara jika pembayaran ditolak
            $table->text('note')->nullable();

            // Waktu approval atau penolakan
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment');
    }
};
