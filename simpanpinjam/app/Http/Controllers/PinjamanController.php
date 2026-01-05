<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ModelPinjaman;
use App\Models\ModelCicilan;
use App\Services\LoanLimitService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PinjamanController extends Controller
{
   
    // ✅ Menampilkan semua pinjaman user
    public function index(Request $r)
    {
        $loans = $r->user()->loans()->orderBy('created_at', 'desc')->get();
        return response()->json(['loans' => $loans]);
    }

    public function listPengajuan(Request $request)
    {
        // hanya admin / ketua (opsional tapi disarankan)
        if (!in_array($request->user()->role, ['BENDAHARA', 'KETUA'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = ModelPinjaman::query()
            ->with(['user:id,full_name'])
            ->where('status', 'PENDING')
            ->orderBy('created_at', 'desc');

        // 🔍 filter opsional
        if ($request->filled('loan_type')) {
            $query->where('loan_type', $request->loan_type);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // 📄 pagination (recommended)
        $loans = $query->paginate(10);

        return response()->json([
            'total' => $loans->total(),
            'data' => $loans->map(function ($loan) {
                return [
                    'pinjaman_id' => $loan->id,
                    'anggota' => [
                        'id' => $loan->user->id,
                        'nama' => $loan->user->full_name,
                    ],
                    'loan_type' => $loan->loan_type,
                    'amount' => (float) $loan->amount,
                    'term_months' => $loan->term_months,
                    'status' => $loan->status,
                    'note' => $loan->note,
                    'tanggal_pengajuan' => $loan->created_at->format('Y-m-d H:i'),
                ];
            })
        ]);
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

    public function loanLimit(Request $r, LoanLimitService $service)
    {
        $user = $r->user();

        $yearsAsMember = Carbon::parse($user->tanggal_gabung)
            ->diffInYears(now());

        $limit = $service->getLimitByYears($yearsAsMember);

        return response()->json([
            'years_as_member' => $yearsAsMember,
            'max_amount'     => $limit['max_nominal'],
            'max_months'       => $limit['max_tenor'],
        ]);
    }

  // ✅ Ketua menyetujui atau menolak pinjaman
   public function approveLoan(Request $r, $id)
{
    // ===============================
    // CEK ROLE
    // ===============================
    if ($r->user()->role !== 'KETUA') {
        return response()->json([
            'success' => false,
            'message' => 'Persetujuan pinjaman harus disetujui oleh Ketua.'
        ], 403);
    }

    // ===============================
    // VALIDASI INPUT
    // ===============================
    $r->validate([
        'status' => 'required|in:APPROVED,REJECTED',
        'note'   => 'nullable|string|max:255',
    ]);

    DB::beginTransaction();

    try {
        // ===============================
        // AMBIL DATA PINJAMAN
        // ===============================
        $loan = ModelPinjaman::findOrFail($id);

        // ===============================
        // CEK STATUS SEBELUMNYA
        // ===============================
        if ($loan->status !== 'PENDING') {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Pinjaman sudah diproses sebelumnya.'
            ], 400);
        }

        // ===============================
        // NOTE DEFAULT JIKA APPROVED
        // ===============================
        $defaultApproveNote =
            'Selamat, pinjaman Anda disetujui. ' .
            'Selanjutnya Anda dapat melakukan pembayaran cicilan sesuai kesepakatan RAT. ' .
            'Semoga uang yang diterima membawa keberkahan.';

        // ===============================
        // TENTUKAN NOTE
        // ===============================
        $note = $r->status === 'APPROVED'
            ? ($r->note ?: $defaultApproveNote)
            : $r->note;

        // ===============================
        // UPDATE PINJAMAN
        // ===============================
        $loan->update([
            'status'      => $r->status,
            'note'        => $note,
            'approved_at' => $r->status === 'APPROVED'
                ? now()
                : null,
        ]);

        // ===============================
        // JIKA REJECT → HAPUS CICILAN
        // ===============================
        if ($r->status === 'REJECTED') {
            $loan->installments()->delete();
        }

        DB::commit();

        // ===============================
        // RESPONSE
        // ===============================
        return response()->json([
            'success' => true,
            'message' => $r->status === 'APPROVED'
                ? 'Pinjaman berhasil disetujui oleh Ketua.'
                : 'Pinjaman ditolak dan data cicilan dihapus.',
            'data' => [
                'loan_id'     => $loan->id,
                'user_id'     => $loan->user_id,
                'amount'      => $loan->amount,
                'term_months' => $loan->term_months,
                'loan_type'   => $loan->loan_type,
                'status'      => $loan->status,
                'note'        => $loan->note,
                'approved_at' => $loan->approved_at,
            ]
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Gagal memproses persetujuan pinjaman.',
            'error'   => $e->getMessage()
        ], 500);
    }
}


}
