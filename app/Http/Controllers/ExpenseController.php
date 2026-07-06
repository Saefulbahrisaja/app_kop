<?php

namespace App\Http\Controllers;

use App\Services\KasKoperasiService;
use Illuminate\Http\Request;
use App\Models\ModelExpense;
use Carbon\Carbon;

class ExpenseController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | LIST
    |--------------------------------------------------------------------------
    */

   public function index()
{
    return response()->json([

        'success' => true,

        'data' => ModelExpense::whereYear('approved_at', now()->year)
            ->whereMonth('approved_at', now()->month)
            ->latest('approved_at')
            ->get()

    ]);
}

    /*
    |--------------------------------------------------------------------------
    | INPUT PENGELUARAN
    |--------------------------------------------------------------------------
    */

    public function store(Request $r)
{
    $r->validate([
        'name'        => 'required|string|max:255',
        'amount'      => 'required|numeric|min:1000',
        'approved_at' => 'nullable|date',
        'note'        => 'nullable|string|max:500'
    ]);

    $expense = ModelExpense::create([

        'name' => $r->name,

        'amount' => $r->amount,

        'approved_at' => $r->approved_at ?? now(),

        'note' => $r->note

    ]);

    return response()->json([

        'success' => true,

        'message' => 'Pengeluaran berhasil disimpan.',

        'data' => $expense

    ],201);
}

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(Request $r, $id)
    {
        $expense = ModelExpense::findOrFail($id);

        $expense->update([
            'name' => $r->name,
            'amount' => $r->amount,
            'approved_at' => $r->approved_at,
            'note' => $r->note
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran berhasil diperbarui'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        ModelExpense::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran berhasil dihapus'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | HISTORY
    |--------------------------------------------------------------------------
    */

    public function history()
{
    $data = ModelExpense::whereYear('approved_at', now()->year)
        ->whereMonth('approved_at', now()->month)
        ->orderByDesc('approved_at')
        ->get()
        ->map(function ($row) {

            return [
                'id'          => $row->id,
                'name'        => $row->name,
                'amount'      => (float) $row->amount,
                'approved_at' => Carbon::parse($row->approved_at)
                    ->translatedFormat('d M Y'),
                'note'        => $row->note

            ];
        });

    return response()->json([

        'success' => true,
        'bulan'   => now()->translatedFormat('F Y'),
        'total'   => $data->count(),
        'data'    => $data

    ]);
}
    /*
    |--------------------------------------------------------------------------
    | SUMMARY
    |--------------------------------------------------------------------------
    */

    public function summary(KasKoperasiService $kas)
{
    $kasData = $kas->kasSummary();

    $pengeluaranBulan = ModelExpense::whereYear(
            'approved_at',
            now()->year
        )
        ->whereMonth(
            'approved_at',
            now()->month
        )
        ->sum('amount');

    $jumlah = ModelExpense::whereYear('approved_at', now()->year)
    ->whereMonth('approved_at', now()->month)
    ->count();
    return response()->json([

        'success' => true,
        // Saldo kas koperasi (SEMUA DATA)
        'saldo_kas' => (float) ($kasData['saldo'] ?? 0),
        // Hanya bulan berjalan
        'pengeluaran_bulan_ini' => (float) $pengeluaranBulan,
        // Tidak lagi seluruh histori, tetapi bulan berjalan
        'total_pengeluaran' => (float) $pengeluaranBulan,
        'bulan' => now()->translatedFormat('F Y'),
        'jumlah_transaksi'=>$jumlah

    ]);
}
}
