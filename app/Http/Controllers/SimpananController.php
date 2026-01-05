<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\ModelSimpanan;
use App\Models\Modeljenissimpanan as SavingType;
use App\Models\Modeltariksimpanan as SavingWithdrawal;
use Illuminate\Support\Facades\DB;

class SimpananController extends Controller
{
    /**
     * LIST SIMPANAN YANG MUNCUL DI APLIKASI
     */
    public function index(Request $r)
{
    $user = $r->user();

    $simpanan = $user->savings()
        ->whereNotNull('paid_at')              // ✅ sudah dibayar
        ->orderBy('paid_at', 'desc')
        ->get()
        ->map(function ($s) {

            $jenis = SavingType::where('code', strtoupper($s->type))->first();

            return [
                'code'       => $jenis?->code,
                'type'       => $s->type,
                'name'       => $jenis?->name,
                'amount'     => (float) $s->amount,      // ✅ amount real
                'paid_at'    => $s->paid_at
                                    ? Carbon::parse($s->period)->format('Y-m-d')
                                    : null,
                'period'     => $s->period
                                    ? Carbon::parse($s->period)->format('Y-m')
                                    : null,
                'mandatory'  => (bool) ($jenis?->mandatory ?? false),
            ];
        });

    return response()->json([
        'data' => $simpanan
    ]);
}

    public function store(Request $r)
    {
        $r->validate([
            'type'   => 'required|in:pokok,wajib,manasuka',
            'amount' => 'required_if:type,manasuka|integer|min:1000'
        ]);

        $typeMaster = SavingType::where('code', strtoupper($r->type))->firstOrFail();

        $amount = $r->type === 'manasuka'
            ? $r->amount
            : $typeMaster->amount;

        $period = null;

        if ($r->type === 'pokok') {
            if ($r->user()->savings()->where('type','pokok')->exists()) {
                return response()->json([
                    'message' => 'Simpanan pokok sudah dibayar'
                ], 422);
            }
        }

        if ($r->type === 'wajib') {
            $period = now()->startOfMonth();

            if ($r->user()->savings()
                ->where('type','wajib')
                ->where('period',$period)
                ->exists()) {

                return response()->json([
                    'message' => 'Simpanan wajib bulan ini sudah dibayar'
                ], 422);
            }
        }

        $saving = $r->user()->savings()->create([
            'type'   => $r->type,
            'amount' => $amount,
            'period' => $period
        ]);

        return response()->json([
            'message' => 'Setoran simpanan berhasil',
            'data' => $saving
        ]);
    }


    /**
     * RIWAYAT SIMPANAN
     */
    public function history(Request $r)
    {
        return response()->json([
            'data' => $r->user()->savings()
                ->orderBy('created_at','desc')
                ->get()
        ]);
    }

   public function totalByType(Request $r)
    {
        $data = $r->user()->savings()
            ->whereNotNull('paid_at')
            ->whereNotNull('approved_at')
            ->selectRaw('type, COALESCE(SUM(amount),0) as total')
            ->groupBy('type')
            ->get();

        // Cek apakah collection kosong
        if ($data->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data saldo belum tersedia',
                'data'    => []
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diambil',
            'data'    => $data
        ]);
    }



    public function mutations(Request $r)
    {
        $mutations = $r->user()->savings()
            ->whereNotNull('paid_at')                 // ✅ hanya yang sudah dibayar
            ->orderBy('paid_at')                      // ✅ urut berdasarkan tanggal bayar
            ->get();
        $saldo = 0;
        $data = $mutations->map(function ($row) use (&$saldo) {
            $saldo += (float) $row->amount;
            return [
                'date'   => Carbon::parse($row->paid_at)->format('Y-m-d'),
                'type'   => $row->type,                // pokok / wajib / manasuka
                'amount' => (float) $row->amount,
                'saldo'  => $saldo
            ];
        });

        return response()->json([
            'data' => $data
        ]);
    }

    public function withdraw(Request $r)
    {
        $r->validate([
            'type'   => 'required|in:manasuka,pokok,wajib',
            'amount' => 'required_if:type,manasuka|integer|min:1000'
        ]);

        $user = $r->user();

        /** BLOK POKOK & WAJIB (KECUALI KELUAR) */
        if (in_array($r->type, ['pokok','wajib']) && $user->status !== 'KELUAR') {
            return response()->json([
                'message' => 'Penarikan simpanan pokok dan wajib hanya saat anggota keluar'
            ], 403);
        }

        /** CEK PENDING DULU */
        $pending = SavingWithdrawal::where('user_id',$user->id)
            ->where('type',$r->type)
            ->where('status','PENDING')
            ->exists();

        if ($pending) {
            return response()->json([
                'message' => 'Masih ada pengajuan penarikan yang belum diproses'
            ], 422);
        }

        /** HITUNG SALDO PER JENIS */
        $saldo = $user->savings()
            ->where('type', $r->type)
            ->sum('amount');

        if ($saldo <= 0) {
            return response()->json([
                'message' => 'Saldo tidak tersedia'
            ], 422);
        }

        /** CEK SALDO MENCUKUPI */
        if ($r->type === 'manasuka' && $saldo < $r->amount) {
            return response()->json([
                'message' => 'Saldo tidak mencukupi',
                'saldo'   => $saldo
            ], 422);
        }

        /** TENTUKAN AMOUNT FINAL */
        $amount = $r->type === 'manasuka'
            ? $r->amount
            : $saldo; // pokok & wajib = full saldo

        /** SIMPAN PENGAJUAN */
        $withdrawal = SavingWithdrawal::create([
            'user_id' => $user->id,
            'type'    => $r->type,
            'amount'  => $amount,
            'status'  => 'PENDING'
        ]);

        return response()->json([
            'message' => 'Pengajuan penarikan berhasil, menunggu persetujuan',
            'data'    => $withdrawal
        ]);
    }


    
     public function approveWithdrawal(SavingWithdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'PENDING') {
            return response()->json([
                'message' => 'Pengajuan sudah diproses'
            ], 422);
        }

        DB::transaction(function () use ($withdrawal) {

            $user = $withdrawal->user;

            $saldo = $user->savings()
                ->where('type', $withdrawal->type)
                ->sum('amount');

            if ($saldo < $withdrawal->amount) {
                throw new \Exception('Saldo tidak mencukupi');
            }

            $currentBalance = $user->savings()->sum('amount');

            $user->savings()->create([
                'type'          => $withdrawal->type,
                'amount'        => -$withdrawal->amount, // ⬅️ POTONG SALDO
                'period'        => null,
                'balance_after' => $currentBalance - $withdrawal->amount
            ]);

            $withdrawal->update([
                'status' => 'APPROVED'
            ]);
        });

        return response()->json([
            'message' => 'Penarikan berhasil disetujui dan saldo telah dikurangi'
        ]);
    }

    public function rejectWithdrawal(SavingWithdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'PENDING') {
            return response()->json(['message'=>'Pengajuan sudah diproses'],422);
        }

        $withdrawal->update(['status'=>'REJECTED']);
        return response()->json(['message'=>'Penarikan ditolak']);
    }

    public function withdrawalHistory(Request $r)
    {
        $withdrawals = SavingWithdrawal::where('user_id', $r->user()->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($w) {
                return [
                    'id'        => $w->id,
                    'type'      => $w->type,
                    'amount'    => $w->amount,
                    'status'    => $w->status,
                    'date'      => $w->created_at->format('Y-m-d H:i'),
                ];
            });

        return response()->json([
            'data' => $withdrawals
        ]);
    }

    



}
