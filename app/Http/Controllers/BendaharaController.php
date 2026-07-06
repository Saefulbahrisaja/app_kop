<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SimpananService;
use App\Services\PiutangService;
use App\Models\ModelUser;
use App\Models\ModelCicilan;
use Carbon\Carbon;
use App\Services\KasKoperasiService;
use App\Services\StatusSaldoService;

class BendaharaController extends Controller
{
    /**
     * ======================
     * DASHBOARD UTAMA
     * ======================
     */
    public function dashboard(
        KasKoperasiService $kas,
        PiutangService $piutang,
        SimpananService $simpanan,
        StatusSaldoService $statusSaldoService
    ) {

        $kasData = $kas->kasSummary();

        $kasAkuntansi = $kas->kasSummaryAkuntansi();

        $piutangData = $piutang->summary();

        $simpananData = $simpanan->summary();

        $statusSaldo = $statusSaldoService->hitung(

            $kasAkuntansi['saldo_bersih'],

            $piutangData['sisa_piutang']

        );

        return response()->json([

            "simpanan" => [

                "pokok"    => (float)($simpananData["pokok"] ?? 0),

                "wajib"    => (float)($simpananData["wajib"] ?? 0),

                "manasuka" => (float)($simpananData["manasuka"] ?? 0),

            ],

            "kas" => [

                "saldo"       => $kasData["saldo"],

                "inflow"      => $kasData["inflow"],

                "outflow"     => $kasData["outflow"],

                "pinjaman"    => $kasData["pinjaman"],

                "pengeluaran" => $kasData["expense"],

                "penarikan"   => $kasData["withdrawal"],

            ],

            "piutang" => [

                "total_pinjaman" => (float)$piutangData["total_pinjaman"],

                "terbayar"       => (float)$piutangData["total_terbayar"],

                "sisa"           => (float)$piutangData["sisa_piutang"],

            ],

            "kas_akuntansi" => [

                "saldo_kas"      => $kasAkuntansi["saldo_kas"],

                "pendapatan_shu" => $kasAkuntansi["pendapatan_shu"],

                "pengeluaran"    => $kasAkuntansi["pengeluaran"],

                "penarikan"      => $kasAkuntansi["withdrawal"],

                "saldo_bersih"   => $kasAkuntansi["saldo_bersih"]

            ],

            "status_saldo" => $statusSaldo

        ]);
    }

    public function grafikSisaPiutang(PiutangService $piutang)
    {
        return response()->json([
            'data' => $piutang->grafikSisaPiutangBulanan(12)
        ]);
    }

    public function tunggakan()
    {
        $start = Carbon::now()->startOfMonth();
        $end   = Carbon::now()->endOfMonth();

        // SIMPANAN WAJIB
        $tunggakanSimpanan = ModelUser::where('role', 'MEMBER')
            ->whereDoesntHave('savings', function ($q) use ($start) {
                $q->where('type', 'wajib')
                    ->where('period', $start)
                    ->whereNotNull('approved_at');
            })
            ->get(['id', 'full_name'])
            ->map(fn($u) => [
                'anggota_id' => $u->id,
                'nama' => $u->full_name,
                'jenis' => 'SIMPANAN_WAJIB'
            ]);

        // CICILAN

        $tunggakanCicilan = ModelCicilan::whereNull('paid_at')
            ->whereDate('due_date', '<', now()) // 🔑 HANYA YANG TELAT
            ->whereHas('loan.user')
            ->with([
                'loan:id,user_id,loan_type,term_months,status',
                'loan.user:id,full_name'
            ])
            ->get()
            ->map(function ($c) {
                return [
                    'anggota_id' => $c->loan->user->id,
                    'nama'       => $c->loan->user->full_name,
                    'jenis'      => 'CICILAN_PINJAMAN',
                    'cicilan'    => [
                        'nominal'     => (float) $c->amount,
                        'jatuh_tempo' => $c->due_date->format('Y-m-d'),
                        'hari_telat'  => $c->due_date->diffInDays(now()), // ✅ SELALU POSITIF
                    ]
                ];
            });

        return response()->json([
            'periode' => $start->format('Y-m'),
            'summary' => [
                'simpanan_wajib' => $tunggakanSimpanan->count(),
                'cicilan' => $tunggakanCicilan->count(),
                'total' => $tunggakanSimpanan->count() + $tunggakanCicilan->count(),
            ],
            'data' => [
                'simpanan_wajib' => $tunggakanSimpanan,
                'cicilan' => $tunggakanCicilan,
            ]
        ]);
    }

    /**
     * ======================
     * SALDO SIMPANAN PER ANGGOTA
     * ======================
     */
    public function saldoSimpanan()
    {
        $data = ModelUser::where('role', 'MEMBER')
            ->with(['savings' => fn($q) => $q->whereNotNull('approved_at')])
            ->get()
            ->map(function ($u) {

                $wajib = $u->savings->where('type', 'wajib')->sum('amount');
                $manasuka = $u->savings->where('type', 'manasuka')->sum('amount');
                $pokok = $u->savings->where('type', 'pokok')->sum('amount');

                return [
                    'anggota_id' => $u->id,
                    'nama' => $u->full_name,
                    'simpanan' => [
                        'wajib' => $wajib,
                        'manasuka' => $manasuka,
                        'pokok' => $pokok,
                        'total' => $wajib + $manasuka + $pokok,
                    ]
                ];
            });

        return response()->json([
            'total_anggota' => $data->count(),
            'data' => $data
        ]);
    }

    /**
     * ======================
     * GRAFIK KAS TAHUNAN
     * ======================
     */
    public function grafikKasTahunan(
        Request $request,
        KasKoperasiService $service
    ) {

        return response()->json(

            $service->grafikKasTahunan(

                $request->tahun ?? now()->year

            )

        );
    }

    public function grafikSisaPiutangPerAnggota(PiutangService $piutang)
    {
        return response()->json([
            'data' => $piutang->grafikSisaPiutangPerAnggota()
        ]);
    }

    public function proyeksiPiutang(Request $request, PiutangService $piutang)
    {
        $bulan = $request->get('bulan', 6); // default 6 bulan ke depan

        return response()->json([
            'data' => $piutang->proyeksiPiutangByDueDate($bulan)
        ]);
    }

    public function kasSummary(
        KasKoperasiService $kas
    ) {

        return response()->json([

            "success" => true,
            "data" => $kas->kasSummary()

        ]);
    }
}
