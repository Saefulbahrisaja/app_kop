<?php

namespace App\Services;

use App\Models\ModelSimpanan;
use Carbon\Carbon;

class SimpananAutoService
{
    /**
     * ===============================
     * SIMPANAN POKOK (1x SEUMUR HIDUP)
     * ===============================
     */
    public function ensurePokok(int $userId): void
    {
        // POKOK TIDAK TERIKAT TAHUN
        ModelSimpanan::firstOrCreate(
            [
                'user_id' => $userId,
                'type'    => 'pokok',
            ],
            [
                // period boleh diisi tanggal daftar / created_at
                'period'  => now(), 
                'paid_at' => null,
            ]
        );
    }

    public function ensureManasuka(int $userId): void
    {
        // Cari manasuka yang masih AKTIF (belum dibayar)
        $active = ModelSimpanan::where('user_id', $userId)
            ->where('type', 'manasuka')
            ->whereNull('paid_at')
            ->first();

        // Jika masih ada yang aktif → JANGAN buat baru
        if ($active) {
            return;
        }

        // Jika tidak ada / yang lama sudah paid → buat baru
        ModelSimpanan::create([
            'user_id' => $userId,
            'type'    => 'manasuka',
            'period'  => now(),   // bebas, tidak dipakai logika periode
            'paid_at' => null,
        ]);
    }


    /**
     * ===============================
     * SIMPANAN WAJIB (JAN–DES TAHUN AKTIF)
     * ===============================
     */
    public function ensureWajibPeriods(int $userId): void
    {
        $year = now()->year;

        $start = Carbon::create($year, 1, 1);
        $end   = Carbon::create($year, 12, 1);

        while ($start <= $end) {

            ModelSimpanan::firstOrCreate(
                [
                    'user_id' => $userId,
                    'type'    => 'wajib',
                    'period'  => $start->copy(),
                ],
                [
                    'paid_at' => null,
                ]
            );

            $start->addMonth();
        }
    }
}
