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
        Schema::table('tarik_simpanan', function (Blueprint $table) {

            // ================= STATUS BARU =================
            $table->enum('status', [
                'PENDING_BENDAHARA',
                'PENDING_KETUA',
                'APPROVED',
                'CAIR',
                'REJECTED'
            ])->default('PENDING_BENDAHARA')->change();

            // ================= APPROVAL =================
            $table->foreignId('bendahara_id')
                ->nullable()
                ->after('status');

            $table->timestamp('bendahara_approved_at')
                ->nullable();

            $table->foreignId('ketua_id')
                ->nullable();

            $table->timestamp('ketua_approved_at')
                ->nullable();

            // ================= PENCAIRAN =================
            $table->foreignId('dicairkan_by')
                ->nullable();

            $table->timestamp('cair_at')
                ->nullable();

            // ================= NOTE =================
            $table->text('note')
                ->nullable();
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
