<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SimpananService;
use App\Services\PiutangService;
use App\Models\ModelUser;
use App\Models\ModelCicilan;
use Carbon\Carbon;
use App\Services\KasKoperasiService;

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
    SimpananService $simpanan
) {
    $kasData = $kas->kasSummary();
    $piutangData = $piutang->summary();
    $simpanan=$simpanan->summary();


    return response()->json([
        'simpanan' => [
                    'pokok'   => $simpanan['pokok'],
                    'wajib'  => $simpanan['wajib'],
                    'manasuka' => $simpanan['manasuka'],
                ],

        'kas' => [
            'saldo'   => $kasData['saldo'],
            'inflow'  => $kasData['inflow'],
            'outflow' => $kasData['outflow'],
        ],

        'piutang' => [
            'total_pinjaman' => $piutangData['total_pinjaman'],
            'terbayar'       => $piutangData['total_terbayar'],
            'sisa'           => $piutangData['sisa_piutang'],
        ],
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
            ->whereBetween('due_date', [$start, $end])
            ->whereHas('loan.user')
            ->with(['loan:id,user_id,loan_type,term_months,status','loan.user:id,full_name'])
            ->get()
            ->map(fn($c) => [
                'anggota_id' => $c->loan->user->id,
                'nama' => $c->loan->user->full_name,
                'jenis' => 'CICILAN_PINJAMAN',
                'cicilan' => [
                    'nominal' => $c->amount,
                    'jatuh_tempo' => $c->due_date->format('Y-m-d'),
                    'hari_telat' => now()->diffInDays($c->due_date, false) * -1
                ]
            ]);

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
        $data = ModelUser::where('role','MEMBER')
            ->with(['savings' => fn($q) => $q->whereNotNull('approved_at')])
            ->get()
            ->map(function ($u) {

                $wajib = $u->savings->where('type','wajib')->sum('amount');
                $manasuka = $u->savings->where('type','manasuka')->sum('amount');
                $pokok = $u->savings->where('type','pokok')->sum('amount');

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
    public function grafikKasTahunan(Request $request, KasKoperasiService $service)
    {
        $tahun = $request->get('tahun', now()->year);
        return response()->json($service->grafikKasTahunan($tahun));
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
}
