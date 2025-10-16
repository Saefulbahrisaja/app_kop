<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ModelPinjaman;
use App\Models\ModelCicilan;
use App\Services\FirebaseService;

class PinjamanController extends Controller
{
    protected $fcm;

    public function __construct(FirebaseService $fcm)
    {
        $this->fcm = $fcm;
    }

    // ✅ Menampilkan semua pinjaman user
    public function index(Request $r)
    {
        $loans = $r->user()->loans()->orderBy('created_at', 'desc')->get();
        return response()->json(['loans' => $loans]);
    }

    // ✅ Pengajuan pinjaman
    public function store(Request $r)
    {
        $r->validate([
            'amount'       => 'required|numeric|min:1',
            'term_months'  => 'required|integer|min:1',
            'loan_type'    => 'required|in:REGULER,TALANGAN'
        ]);

        $userId   = $r->user()->id;
        $loanType = $r->loan_type;

        // Ambil pinjaman aktif user
        $activeLoans = ModelPinjaman::where('user_id', $userId)
            ->whereIn('status', ['PENDING', 'APPROVED'])
            ->get();

        $hasActiveRegular  = $activeLoans->where('loan_type', 'REGULER')->isNotEmpty();
        $hasActiveTalangan = $activeLoans->where('loan_type', 'TALANGAN')->isNotEmpty();

        // =========================
        // VALIDASI UNTUK REGULER
        // =========================
        if ($loanType === 'REGULER') {
            if ($hasActiveRegular || $hasActiveTalangan) {
                return response()->json([
                    'error' => 'Anda tidak dapat mengajukan pinjaman REGULER karena masih ada pinjaman yang belum lunas.'
                ], 400);
            }
        }

        // =========================
        // VALIDASI UNTUK TALANGAN
        // =========================
        if ($loanType === 'TALANGAN') {
            // Tidak boleh ada TALANGAN aktif
            if ($hasActiveTalangan) {
                return response()->json([
                    'error' => 'Tidak dapat mengajukan TALANGAN karena masih ada pinjaman TALANGAN yang belum lunas.'
                ], 400);
            }

            // Jika ada REGULER PENDING (belum disetujui)
            $pendingRegular = $activeLoans->where('loan_type', 'REGULER')
                                          ->where('status', 'PENDING')
                                          ->isNotEmpty();
            if ($pendingRegular) {
                return response()->json([
                    'error' => 'Tidak dapat mengajukan TALANGAN karena pinjaman REGULER Anda belum disetujui.'
                ], 400);
            }

            // Jika ada REGULER APPROVED → pastikan sudah bayar minimal 1 cicilan
            $approvedRegular = $activeLoans->where('loan_type', 'REGULER')
                                           ->where('status', 'APPROVED')
                                           ->first();

            if ($approvedRegular) {
                $paidInstallments = ModelCicilan::where('loan_id', $approvedRegular->id)
                    ->whereNotNull('paid_at')
                    ->count();

                if ($paidInstallments < 1) {
                    return response()->json([
                        'error' => 'Anda hanya dapat mengajukan pinjaman TALANGAN jika sudah membayar minimal 1 cicilan pinjaman REGULER.'
                    ], 400);
                }
            }

            // Talangan selalu 1 kali cicilan
            $r->merge(['term_months' => 1]);
        }

        // ✅ Buat pinjaman baru (status awal: PENDING)
        $loan = ModelPinjaman::create([
            'user_id'     => $userId,
            'amount'      => $r->amount,
            'term_months' => $r->term_months,
            'status'      => 'PENDING',
            'loan_type'   => $loanType
        ]);

        // ✅ Generate cicilan (dibulatkan ribuan ke bawah)
        $totalAmount = $loan->amount;
        $term = $loan->term_months;
        $baseInstallment = floor($totalAmount / $term / 10000) * 10000;

        $totalGenerated = 0;
        for ($i = 1; $i <= $term; $i++) {
            $amount = ($i == $term)
                ? $totalAmount - $totalGenerated
                : $baseInstallment;

            $loan->installments()->create([
                'amount'   => $amount,
                'due_date' => now()->addMonths($i),
            ]);

            $totalGenerated += $amount;
        }

        return response()->json(['loan' => $loan], 201);
    }

    // ✅ Ketua menyetujui atau menolak pinjaman
    public function approveLoan(Request $r, $id)
    {
        // Pastikan hanya KETUA yang bisa melakukan aksi ini
        if ($r->user()->role !== 'KETUA') {
            return response()->json(['error' => 'Anda tidak memiliki izin untuk menyetujui pinjaman.'], 403);
        }

        $r->validate([
            'status' => 'required|in:APPROVED,REJECTED',
            'note'   => 'nullable|string|max:255'
        ]);

        $loan = ModelPinjaman::findOrFail($id);
        $oldStatus = $loan->status;

        if ($oldStatus !== 'PENDING') {
            return response()->json(['error' => 'Pinjaman sudah diproses sebelumnya.'], 400);
        }

        $loan->update([
            'status' => $r->status,
            'note'   => $r->note ?? null,
        ]);

        // ✅ Kirim notifikasi ke semua anggota (via Firebase)
        $title = "Status Pinjaman #{$loan->id}";
        $body  = "Pinjaman Anda telah {$r->status}.";
        $this->fcm->sendToTopic('pinjaman_update', $title, $body, [
            'loan_id' => $loan->id,
            'status'  => $loan->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Pinjaman berhasil diperbarui menjadi {$loan->status}",
            'loan'    => $loan
        ]);
    }
}
