<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SimpananService
{
  public function summary()
{
   
    $simpananGroup = DB::table('simpanan')
        ->whereNotNull('paid_at')
        ->select('type', DB::raw('SUM(amount) as total'))
        ->groupBy('type')
        ->pluck('total', 'type');

    $totalSimpanan = $simpananGroup->sum();


    return [
        // Rincian Simpanan (Sesuai kategori di UI)
        'pokok'    => (float) ($simpananGroup['pokok'] ?? 0),
        'wajib'    => (float) ($simpananGroup['wajib'] ?? 0),
        'manasuka' => (float) ($simpananGroup['manasuka'] ?? 0),
        'total_simpanan'    => (float) $totalSimpanan,

    ];
}

}
