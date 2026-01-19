<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ModelPendapatan;
use App\Models\ModelPinjaman;
use App\Models\ModelSimpanan;

class PendapatanController extends Controller
{
    // ===============================
    // CEK BOLEH INPUT SHU
    // ===============================
    public function canInput(Request $r)
    {
        $userId = $r->user()->id;

        $hasActiveLoan = ModelPinjaman::where('user_id', $userId)
            ->whereIn('status', ['APPROVED', 'APPROVED_BENDAHARA'])
            ->whereHas('installments', fn ($q) => $q->whereNull('paid_at'))
            ->exists();

        $hasActiveSimpanan = ModelSimpanan::where('user_id', $userId)
            ->whereNull('paid_at')
            ->exists();

        return response()->json([
            'can_input' => $hasActiveLoan || $hasActiveSimpanan
        ]);
    }

    // ===============================
    // SIMPAN SHU (INPUT USER)
    // ===============================
    public function store(Request $r)
    {
        $r->validate([
            'amount' => 'required|numeric|min:1',
            'note'   => 'nullable|string|max:255'
        ]);

        $userId  = $r->user()->id;
        $periode = now()->year;

        // ❌ CEGAT JIKA SUDAH LUNAS SEMUA
        $canInput = $this->canInputPendapatan($userId);
        if (!$canInput) {
            return response()->json([
                'error' => 'Semua kewajiban sudah lunas. Tidak dapat input SHU.'
            ], 400);
        }

        // ❌ CEGAH DOUBLE INPUT SHU TAHUN YANG SAMA
        $exists = ModelPendapatan::where('user_id', $userId)
            ->where('type', 'SHU')
            ->where('periode', $periode)
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => 'SHU periode ini sudah diinput.'
            ], 400);
        }

        $data = ModelPendapatan::create([
            'user_id' => $userId,
            'amount'  => $r->amount,
            'periode' => $periode,
            'type'    => 'SHU',
            'note'    => $r->note
        ]);

        return response()->json([
            'success' => true,
            'data'    => $data
        ]);
    }

    // ===============================
    // HELPER INTERNAL
    // ===============================
    private function canInputPendapatan(int $userId): bool
    {
        $hasLoan = ModelPinjaman::where('user_id', $userId)
            ->whereIn('status', ['APPROVED', 'APPROVED_BENDAHARA'])
            ->whereHas('installments', fn ($q) => $q->whereNull('paid_at'))
            ->exists();

        $hasSimpanan = ModelSimpanan::where('user_id', $userId)
            ->whereNull('paid_at')
            ->exists();

        return $hasLoan || $hasSimpanan;
    }
}
