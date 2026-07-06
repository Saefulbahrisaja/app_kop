<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;

class ShuService
{
    public function pendapatan($from, $to): array
    {
        // jasa pinjaman = total cicilan terbayar (contoh sederhana)
        $jasaPinjaman = DB::table('cicilan')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');

        // administrasi/denda (jika ada)
        $administrasi = DB::table('payment')
            ->where('status','APPROVED')
            ->whereBetween('approved_at', [$from, $to])
            ->sum('amount');

        return [
            'jasa_pinjaman' => (float)$jasaPinjaman,
            'administrasi'  => (float)$administrasi,
            'total'         => (float)($jasaPinjaman + $administrasi),
        ];
    }

    public function biaya($from, $to): array
    {
        $operasional = DB::table('expense')
            ->whereBetween('approved_at', [$from, $to])
            ->sum('amount');

        return [
            'operasional' => (float)$operasional,
            'total'       => (float)$operasional,
        ];
    }

    public function hitungSHU(array $pendapatan, array $biaya): float
    {
        return (float)($pendapatan['total'] - $biaya['total']);
    }

    public function pembagian(float $shu): array
    {
        return [
            'jasa_anggota' => $shu * 0.40,
            'cadangan'     => $shu * 0.25,
            'pengurus'     => $shu * 0.20,
            'pendidikan'   => $shu * 0.05,
            'sosial'       => $shu * 0.05,
            'pembangunan'  => $shu * 0.05,
        ];
    }
}
