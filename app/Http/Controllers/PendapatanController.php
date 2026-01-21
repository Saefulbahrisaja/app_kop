<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ModelPendapatan;
use App\Models\ModelPinjaman;
use App\Models\ModelPayment;

class PendapatanController extends Controller
{
    // ==========================================
    // CEK APAKAH SHU MUNCUL (UNTUK UI)
    // ==========================================
    public function canInput(Request $r)
{
    $userId = $r->user()->id;

    // ambil pinjaman terakhir user (baik masih jalan atau sudah lunas)
    $loan = ModelPinjaman::where('user_id', $userId)
        ->whereIn('status', ['APPROVED', 'LUNAS'])
        ->orderByDesc('created_at')
        ->first();

    if (!$loan) {
        return response()->json([
            'can_input' => false
        ]);
    }

    // cek apakah SHU sudah dibayar (APPROVED)
    $shuPaid = ModelPayment::where('loan_id', $loan->id)
        ->whereNull('installment_id')
        ->whereNull('simpanan_id')
        ->where('note', 'like', '%SHU%')
        ->where('status', 'APPROVED')
        ->exists();

    return response()->json([
        'can_input' => !$shuPaid,
        'loan_id'   => $loan->id
    ]);
}


    public function store(Request $r)
{
    $r->validate([
        'loan_id' => 'required|exists:pinjaman,id',
        'amount'  => 'required|numeric|min:1',
    ]);

    $user = $r->user();

    // ================= VALIDASI PINJAMAN =================
    $loan = ModelPinjaman::where('id', $r->loan_id)
        ->where('user_id', $user->id)
        ->whereIn('status', ['APPROVED', 'APPROVED_BENDAHARA'])
        ->whereHas('installments', fn ($q) => $q->whereNull('paid_at'))
        ->first();

    if (!$loan) {
        return response()->json([
            'error' => 'Pinjaman sudah lunas atau tidak valid.'
        ], 400);
    }

    // ================= CEGAH DOUBLE SHU =================
    $exists = ModelPendapatan::where('loan_id', $loan->id)
        ->where('type', 'SHU')
        ->exists();

    if ($exists) {
        return response()->json([
            'error' => 'SHU untuk pinjaman ini sudah dibayarkan.'
        ], 400);
    }

    // ================= NOTE & PERIODE OTOMATIS =================
    $note = sprintf(
        'SHU %s dari pinjaman %s',
        $user->full_name ?? $user->username,
        strtoupper($loan->loan_type)
    );

    $periode = now(); // ⬅️ TANGGAL BAYAR SHU

    // ================= SIMPAN =================
    $data = ModelPendapatan::create([
        'user_id' => $user->id,
        'loan_id' => $loan->id,
        'amount'  => $r->amount,
        'type'    => 'SHU',
        'periode' => $periode,
        'note'    => $note
    ]);

    return response()->json([
        'success' => true,
        'data'    => $data
    ]);
}


}
