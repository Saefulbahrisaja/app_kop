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
        Schema::table('payment', function (Blueprint $table) {
            // Unique gabungan installment_id + status
            $table->unique(['installment_id', 'status'], 'unique_installment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('unique_installment_status');
        });
    }
};
