<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PiutangService
{
    public function summary()
    {
        $totalPinjaman = DB::table('pinjaman')
            ->whereNotNull('approved_at')
            ->sum('amount');

        $totalCicilan = DB::table('cicilan')
            ->whereNotNull('paid_at')
            ->sum('amount');

        return [
            'total_pinjaman' => (float) $totalPinjaman,
            'total_terbayar' => (float) $totalCicilan,
            'sisa_piutang'   => (float) ($totalPinjaman - $totalCicilan),
        ];
    }

    public function grafikSisaPiutangBulanan($bulan = 12)
    {
        $start = Carbon::now()->subMonths($bulan)->startOfMonth();

        // =========================
        // PINJAMAN (APPROVED)
        // =========================
        $pinjaman = DB::table('pinjaman')
            ->selectRaw("
                DATE_FORMAT(approved_at, '%Y-%m') as periode,
                SUM(amount) as total
            ")
            ->whereNotNull('approved_at')
            ->whereDate('approved_at', '>=', $start)
            ->groupBy('periode')
            ->pluck('total', 'periode');

        // =========================
        // CICILAN (PAID)
        // =========================
        $cicilan = DB::table('cicilan')
            ->join('pinjaman', 'pinjaman.id', '=', 'cicilan.loan_id')
            ->selectRaw("
                DATE_FORMAT(cicilan.paid_at, '%Y-%m') as periode,
                SUM(cicilan.amount) as total
            ")
            ->whereNotNull('cicilan.paid_at')
            ->whereNotNull('pinjaman.approved_at')
            ->whereDate('cicilan.paid_at', '>=', $start)
            ->groupBy('periode')
            ->pluck('total', 'periode');

        // =========================
        // BENTUK PERIODE & KUMULATIF
        // =========================
        $periode = collect($pinjaman->keys())
            ->merge($cicilan->keys())
            ->unique()
            ->sort()
            ->values();

        $totalPinjaman = 0;
        $totalCicilan  = 0;
        $result = [];

        foreach ($periode as $p) {
            $totalPinjaman += (float) ($pinjaman[$p] ?? 0);
            $totalCicilan  += (float) ($cicilan[$p] ?? 0);

            $result[] = [
                'periode' => $p,
                'pinjaman' => $totalPinjaman,
                'cicilan' => $totalCicilan,
                'sisa_piutang' => $totalPinjaman - $totalCicilan,
            ];
        }

        return $result;
    }

    public function grafikSisaPiutangPerAnggota()
{
    // =========================
    // TOTAL PINJAMAN PER ANGGOTA
    // =========================
    $pinjaman = DB::table('pinjaman')
        ->join('user', 'user.id', '=', 'pinjaman.user_id')
        ->selectRaw("
            user.id as user_id,
            user.full_name as nama,
            SUM(pinjaman.amount) as total_pinjaman
        ")
        ->whereNotNull('pinjaman.approved_at')
        ->groupBy('user.id', 'user.full_name')
        ->get()
        ->keyBy('user_id');

    // =========================
    // TOTAL CICILAN PER ANGGOTA
    // =========================
    $cicilan = DB::table('cicilan')
        ->join('pinjaman', 'pinjaman.id', '=', 'cicilan.loan_id')
        ->join('user', 'user.id', '=', 'pinjaman.user_id')
        ->selectRaw("
            user.id as user_id,
            SUM(cicilan.amount) as total_cicilan
        ")
        ->whereNotNull('cicilan.paid_at')
        ->whereNotNull('pinjaman.approved_at')
        ->groupBy('user.id')
        ->pluck('total_cicilan', 'user_id');

    // =========================
    // HITUNG SISA PIUTANG
    // =========================
    $result = [];

    foreach ($pinjaman as $userId => $p) {
        $terbayar = (float) ($cicilan[$userId] ?? 0);
        $sisa = (float) $p->total_pinjaman - $terbayar;

        // hanya tampilkan yang masih punya piutang
        if ($sisa > 0) {
            $result[] = [
                'anggota_id' => $userId,
                'nama' => $p->nama,
                'total_pinjaman' => (float) $p->total_pinjaman,
                'terbayar' => $terbayar,
                'sisa_piutang' => $sisa,
            ];
        }
    }

    return collect($result)
        ->sortByDesc('sisa_piutang')
        ->values();
}

public function proyeksiPiutangByDueDate($bulanKeDepan = 6)
{
    $start = Carbon::now()->startOfMonth();
    $end   = Carbon::now()->addMonths($bulanKeDepan)->endOfMonth();

    $data = DB::table('cicilan')
        ->join('pinjaman', 'pinjaman.id', '=', 'cicilan.loan_id')
        ->selectRaw("
            DATE_FORMAT(cicilan.due_date, '%Y-%m') as periode,
            SUM(cicilan.amount) as total
        ")
        ->whereNull('cicilan.paid_at')
        ->whereNotNull('pinjaman.approved_at')
        ->whereBetween('cicilan.due_date', [$start, $end])
        ->groupBy('periode')
        ->orderBy('periode')
        ->get();

    // Pastikan semua bulan muncul (walau nol)
    $result = [];
    for ($i = 0; $i <= $bulanKeDepan; $i++) {
        $periode = $start->copy()->addMonths($i)->format('Y-m');

        $row = $data->firstWhere('periode', $periode);

        $result[] = [
            'periode' => $periode,
            'piutang_jatuh_tempo' => (float) ($row->total ?? 0),
        ];
    }

    return $result;
}

}
