<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KasKoperasiService
{
    /**
     * ============================
     * SALDO AWAL KOPERASI
     * ============================
     */
    public function saldoAwal($startDate)
    {
        $simpanan = DB::table('simpanan')
            ->whereNotNull('paid_at')
            ->whereDate('paid_at', '<', $startDate)
            ->sum('amount');

        $cicilan = DB::table('cicilan')
            ->whereNotNull('paid_at')
            ->whereDate('paid_at', '<', $startDate)
            ->sum('amount');

        $pinjaman = DB::table('pinjaman')
            ->whereNotNull('approved_at')
            ->whereDate('approved_at', '<', $startDate)
            ->sum('amount');

        return ($simpanan + $cicilan) - $pinjaman;
    }


    /**
     * ============================
     * INFLOW PER BULAN
     * ============================
     */
    public function inflowBulanan($bulan = 12)
    {
    $start = Carbon::now()->subMonths($bulan)->startOfMonth();

    $simpanan = DB::table('simpanan')
        ->selectRaw("
            DATE_FORMAT(paid_at, '%Y-%m') as periode,
            SUM(amount) as total
        ")
        ->whereNotNull('paid_at')
        ->whereDate('paid_at', '>=', $start)
        ->groupBy('periode');

    $cicilan = DB::table('cicilan')
        ->join('pinjaman', 'pinjaman.id', '=', 'cicilan.loan_id')
        ->selectRaw("
            DATE_FORMAT(paid_at, '%Y-%m') as periode,
            SUM(amount) as total
        ")
        ->whereNotNull('cicilan.paid_at')
        ->whereNotNull('pinjaman.approved_at')
        ->whereDate('cicilan.paid_at', '>=', $start)
        ->groupBy('periode');

    return DB::query()
        ->fromSub($simpanan->unionAll($cicilan), 'inflow')
        ->selectRaw("periode, SUM(total) as inflow")
        ->groupBy('periode')
        ->orderBy('periode')
        ->get();
}


    /**
     * ============================
     * OUTFLOW PER BULAN
     * ============================
     */
    public function outflowBulanan($bulan = 12)
{
    $start = Carbon::now()->subMonths($bulan)->startOfMonth();

    return DB::table('pinjaman')
        ->selectRaw("
            DATE_FORMAT(approved_at, '%Y-%m') as periode,
            SUM(amount) as outflow
        ")
        ->whereNotNull('approved_at')
        ->whereDate('approved_at', '>=', $start)
        ->groupBy('periode')
        ->orderBy('periode')
        ->get();
}


    /**
     * ============================
     * GABUNG SEMUA UNTUK GRAFIK
     * ============================
     */
    public function grafikKas($bulan = 12)
    {
        $start = Carbon::now()->subMonths($bulan)->startOfMonth();

        $saldoAwal = $this->saldoAwal($start);

        $inflow  = $this->inflowBulanan($bulan)->keyBy('periode');
        $outflow = $this->outflowBulanan($bulan)->keyBy('periode');

        $periode = collect($inflow->keys())
            ->merge($outflow->keys())
            ->unique()
            ->sort()
            ->values();

        $saldo = $saldoAwal;
        $result = [];

        foreach ($periode as $p) {
            $in  = $inflow[$p]->inflow  ?? 0;
            $out = $outflow[$p]->outflow ?? 0;

            $saldo = $saldo + $in - $out;

            $result[] = [
                'periode' => $p,
                'inflow'  => (float) $in,
                'outflow' => (float) $out,
                'saldo'   => (float) $saldo,
            ];
        }

        return [
            'saldo_awal' => $saldoAwal,
            'data' => $result
        ];
    }

public function saldoAkhir()
{
    $simpanan = DB::table('simpanan')
        ->whereNotNull('paid_at')
        ->sum('amount');

    $cicilan = DB::table('cicilan')
        ->join('pinjaman', 'pinjaman.id', '=', 'cicilan.loan_id')
        ->whereNotNull('cicilan.paid_at')
        ->whereNotNull('pinjaman.approved_at')
        ->sum('cicilan.amount');

    $pinjaman = DB::table('pinjaman')
        ->whereNotNull('approved_at')
        ->sum('amount');

    return (float)(($simpanan + $cicilan) - $pinjaman);
}

public function grafikKasTahunan($tahun = null)
{
    $tahun = $tahun ?? Carbon::now()->year;

    $start = Carbon::create($tahun, 1, 1)->startOfMonth();
    $end   = Carbon::create($tahun, 12, 31)->endOfMonth();

    // ======================
    // SALDO AWAL (sebelum Jan)
    // ======================
    $saldoAwal = $this->saldoAwal($start);

    // ======================
    // INFLOW
    // ======================
    $simpanan = DB::table('simpanan')
        ->selectRaw("
            DATE_FORMAT(paid_at, '%Y-%m') as periode,
            SUM(amount) as total
        ")
        ->whereBetween('paid_at', [$start, $end])
        ->groupBy('periode');

    $cicilan = DB::table('cicilan')
        ->selectRaw("
            DATE_FORMAT(paid_at, '%Y-%m') as periode,
            SUM(amount) as total
        ")
        ->whereNotNull('paid_at')
        ->whereBetween('paid_at', [$start, $end])
        ->groupBy('periode');

    $inflow = DB::query()
        ->fromSub($simpanan->unionAll($cicilan), 'inflow')
        ->selectRaw("periode, SUM(total) as inflow")
        ->groupBy('periode')
        ->pluck('inflow', 'periode');

    // ======================
    // OUTFLOW
    // ======================
    $outflow = DB::table('pinjaman')
        ->selectRaw("
            DATE_FORMAT(approved_at, '%Y-%m') as periode,
            SUM(amount) as outflow
        ")
        ->whereBetween('approved_at', [$start, $end])
        ->groupBy('periode')
        ->pluck('outflow', 'periode');

    // ======================
    // BENTUK 12 BULAN PENUH
    // ======================
    $result = [];
    $saldo = $saldoAwal;

    for ($i = 0; $i < 12; $i++) {

        $periode = $start->copy()->addMonths($i)->format('Y-m');

        $in  = (float) ($inflow[$periode]  ?? 0);
        $out = (float) ($outflow[$periode] ?? 0);

        $saldo = $saldo + $in - $out;

        $result[] = [
            'periode' => $periode,
            'inflow'  => $in,
            'outflow' => $out,
            'saldo'   => $saldo,
        ];
    }

    return [
        'saldo_awal' => (float) $saldoAwal,
        'data'       => $result
    ];
}

public function saldoRealtime()
{
    $simpanan = DB::table('simpanan')
        ->whereNotNull('paid_at')
        ->sum('amount');

    $cicilan = DB::table('cicilan')
        ->whereNotNull('paid_at')
        ->sum('amount');

    $pinjaman = DB::table('pinjaman')
        ->whereNotNull('approved_at')
        ->sum('amount');

    return [
        'saldo'   => (float)(($simpanan + $cicilan) - $pinjaman),
        'inflow'  => (float)($simpanan + $cicilan),
        'outflow' => (float)$pinjaman
    ];
}


public function statusSaldo($saldo)
{
    if ($saldo < 0) {
        return [
            'saldo' => $saldo,
            'status' => 'KRITIS',
            'color' => 'RED',
        ];
    } elseif ($saldo <= 10000000) {
        return [
            'saldo' => $saldo,
            'status' => 'WASPADA',
            'color' => 'ORANGE',
        ];
    }

    return [
        'saldo' => $saldo,
        'status' => 'AMAN',
        'color' => 'GREEN',
    ];
}

public function kasSummary()
{
    $simpanan = DB::table('simpanan')
        ->whereNotNull('paid_at')
        ->sum('amount');

    $cicilan = DB::table('cicilan')
        ->join('pinjaman', 'pinjaman.id', '=', 'cicilan.loan_id')
        ->whereNotNull('cicilan.paid_at')
        ->whereNotNull('pinjaman.approved_at')
        ->sum('cicilan.amount');

    $pinjaman = DB::table('pinjaman')
        ->whereNotNull('approved_at')
        ->sum('amount');

    return [
        'inflow'  => (float) ($simpanan + $cicilan),
        'outflow' => (float) $pinjaman,
        'saldo'   => (float) (($simpanan + $cicilan) - $pinjaman),
    ];
}




}
