<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KasKoperasiService
{

    public function saldoAwal($tanggal)
    {
        // ==========================
        // INFLOW
        // ==========================

        $simpanan = DB::table('simpanan')
            ->whereNotNull('paid_at')
            ->whereDate('paid_at', '<', $tanggal)
            ->sum('amount');

        $cicilan = DB::table('cicilan')
            ->whereNotNull('paid_at')
            ->whereDate('paid_at', '<', $tanggal)
            ->sum('amount');

        $pendapatan = DB::table('pendapatan')
            ->whereDate('periode', '<', $tanggal)
            ->sum('amount');

        // ==========================
        // OUTFLOW
        // ==========================

        $pinjaman = DB::table('pinjaman')
            ->whereNotNull('approved_at')
            ->whereDate('approved_at', '<', $tanggal)
            ->sum('amount');

        $expense = DB::table('expense')
            ->whereDate('approved_at', '<', $tanggal)
            ->sum('amount');

        $withdrawal = DB::table('tarik_simpanan')
            ->where('status', 'DISBURSED')
            ->whereDate('updated_at', '<', $tanggal)
            ->sum('amount');

        return (float) (

            ($simpanan + $cicilan + $pendapatan)

            -

            ($pinjaman + $expense + $withdrawal)

        );
    }
    private function totalSimpanan()
    {
        return (float) DB::table('simpanan')
            ->whereNotNull('paid_at')
            ->sum('amount');
    }

    private function totalCicilan()
    {
        return (float) DB::table('cicilan')
            ->whereNotNull('paid_at')
            ->sum('amount');
    }
    private function totalPinjaman()
    {
        return (float) DB::table('pinjaman')
            ->whereNotNull('approved_at')
            ->sum('amount');
    }

    private function totalExpense()
    {
        return (float) DB::table('expense')
            ->sum('amount');
    }
    private function totalWithdrawal()
    {
        return (float) DB::table('tarik_simpanan')
            ->where('status', 'PAID')
            ->sum('amount');
    }

    private function totalPendapatan()
    {
        return (float) DB::table('pendapatan')
            ->sum('amount');
    }

    public function kasSummary()
    {
        $simpanan    = $this->totalSimpanan();
        $cicilan     = $this->totalCicilan();
        $pendapatan  = $this->totalPendapatan();

        $pinjaman    = $this->totalPinjaman();
        $expense     = $this->totalExpense();
        $withdrawal  = $this->totalWithdrawal();

        // Semua pemasukan
        $inflow = $simpanan + $cicilan + $pendapatan;

        // Semua pengeluaran
        $outflow = $pinjaman + $expense + $withdrawal;

        // Saldo kas
        $saldo = $inflow - $outflow;

        return [

            "simpanan"    => $simpanan,
            "cicilan"     => $cicilan,
            "pendapatan"  => $pendapatan,
            "pinjaman"    => $pinjaman,
            "expense"     => $expense,
            "withdrawal"  => $withdrawal,
            "inflow"      => $inflow,
            "outflow"     => $outflow,
            "saldo"       => $saldo

        ];
    }
    public function statusKas()
    {
        $saldo = $this->kasSummary()['saldo'];
        if ($saldo < 0) {
            return [
                "saldo" => $saldo,
                "status" => "KRITIS",
                "color" => "RED"
            ];
        }

        if ($saldo <= 10000000) {
            return [
                "saldo" => $saldo,
                "status" => "WASPADA",
                "color" => "ORANGE"
            ];
        }
        return [
            "saldo" => $saldo,
            "status" => "AMAN",
            "color" => "GREEN"

        ];
    }

    public function kasSummaryAkuntansi()
    {
        $kas = $this->kasSummary();

        return [
            "saldo_kas"      => $kas["saldo"],
            "pendapatan_shu" => $kas["pendapatan"],
            "pengeluaran"    => $kas["expense"],
            "withdrawal"     => $kas["withdrawal"],
            "saldo_bersih"   => $kas["saldo"]

        ];
    }

    private function inflowPeriode(Carbon $start, Carbon $end)
    {
        $simpanan = DB::table('simpanan')
            ->selectRaw("
                DATE_FORMAT(paid_at,'%Y-%m') periode,
                SUM(amount) total
            ")
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$start, $end])
            ->groupBy('periode');

        $cicilan = DB::table('cicilan')
            ->selectRaw("
                DATE_FORMAT(paid_at,'%Y-%m') periode,
                SUM(amount) total
            ")
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$start, $end])
            ->groupBy('periode');

        $pendapatan = DB::table('pendapatan')
            ->selectRaw("
                DATE_FORMAT(periode,'%Y-%m') periode,
                SUM(amount) total
            ")
            ->whereBetween('periode', [$start, $end])
            ->groupBy('periode');

        return DB::query()
            ->fromSub(

                $simpanan
                    ->unionAll($cicilan)
                    ->unionAll($pendapatan),

                'x'

            )

            ->selectRaw("
                periode,
                SUM(total) inflow
            ")

            ->groupBy('periode')

            ->pluck('inflow', 'periode');
    }

    private function outflowPeriode(Carbon $start, Carbon $end)
    {

        $pinjaman = DB::table('pinjaman')
            ->selectRaw("
            DATE_FORMAT(approved_at,'%Y-%m') periode,
            SUM(amount) total
        ")
            ->whereNotNull('approved_at')
            ->whereBetween('approved_at', [$start, $end])
            ->groupBy('periode');

        $expense = DB::table('expense')
            ->selectRaw("
            DATE_FORMAT(approved_at,'%Y-%m') periode,
            SUM(amount) total
        ")
            ->whereBetween('approved_at', [$start, $end])
            ->groupBy('periode');

        $withdrawal = DB::table('tarik_simpanan')
            ->selectRaw("
            DATE_FORMAT(updated_at,'%Y-%m') periode,
            SUM(amount) total
        ")
            ->where('status', 'PAID')
            ->whereBetween('updated_at', [$start, $end])
            ->groupBy('periode');

        return DB::query()

            ->fromSub(

                $pinjaman
                    ->unionAll($expense)
                    ->unionAll($withdrawal),
                'x'

            )

            ->selectRaw("
            periode,
            SUM(total) outflow
        ")

            ->groupBy('periode')

            ->pluck('outflow', 'periode');
    }


    public function saldoAkhir()
    {
        return $this->kasSummary()['saldo'];
    }


    /**
     * ============================
     * INFLOW PER BULAN
     * ============================
     */
    public function inflowBulanan($bulan = 12)
    {
        $start = now()
            ->subMonths($bulan - 1)
            ->startOfMonth();

        $end = now()->endOfMonth();

        return $this->inflowPeriode(
            $start,
            $end
        );
    }


    /**
     * ============================
     * OUTFLOW PER BULAN
     * ============================
     */
    public function outflowBulanan($bulan = 12)
    {
        $start = now()
            ->subMonths($bulan - 1)
            ->startOfMonth();

        $end = now()->endOfMonth();

        return $this->outflowPeriode(
            $start,
            $end
        );
    }
    /**
     * ============================
     * GABUNG SEMUA UNTUK GRAFIK
     * ============================
     */
    public function grafikKas($bulan = 12)
    {

        $start = now()
            ->subMonths($bulan - 1)
            ->startOfMonth();

        $end = now()->endOfMonth();

        $saldo = $this->saldoAwal($start);

        $inflow = $this->inflowPeriode(
            $start,
            $end
        );

        $outflow = $this->outflowPeriode(
            $start,
            $end
        );

        $periode = [];

        $date = $start->copy();

        while ($date <= $end) {

            $periode[] = $date->format('Y-m');

            $date->addMonth();
        }

        $hasil = [];

        foreach ($periode as $p) {

            $masuk = (float) ($inflow[$p] ?? 0);

            $keluar = (float) ($outflow[$p] ?? 0);

            $saldo += $masuk - $keluar;

            $hasil[] = [

                "periode" => $p,

                "inflow" => $masuk,

                "outflow" => $keluar,

                "saldo" => $saldo

            ];
        }

        return [

            "saldo_awal" => $this->saldoAwal($start),

            "data" => $hasil

        ];
    }

    public function grafikKasTahunan($tahun = null)
    {

        $tahun = $tahun ?? now()->year;

        $start = Carbon::create($tahun, 1, 1);

        $end = Carbon::create($tahun, 12, 31);

        $saldo = $this->saldoAwal($start);

        $inflow = $this->inflowPeriode(
            $start,
            $end
        );

        $outflow = $this->outflowPeriode(
            $start,
            $end
        );

        $data = [];

        for ($i = 1; $i <= 12; $i++) {

            $periode = sprintf(
                "%04d-%02d",
                $tahun,
                $i
            );

            $masuk = (float)($inflow[$periode] ?? 0);

            $keluar = (float)($outflow[$periode] ?? 0);

            $saldo += $masuk;

            $saldo -= $keluar;

            $data[] = [

                "periode" => $periode,
                "inflow" => $masuk,
                "outflow" => $keluar,
                "saldo" => $saldo

            ];
        }

        return [

            "saldo_awal" => $this->saldoAwal($start),

            "data" => $data

        ];
    }

    public function saldoRealtime()
    {
        return $this->kasSummary();
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
}
