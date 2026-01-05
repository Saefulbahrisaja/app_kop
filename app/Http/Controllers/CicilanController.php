<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ModelPinjaman;
use App\Models\ModelPayment;
use Carbon\Carbon;
use App\Models\ModelCicilan;
use App\Services\SimpananAutoService;
use App\Services\PaymentStatusService;
use App\Models\ModelSimpanan;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;



class CicilanController extends Controller
{
    // ✅ Melihat semua cicilan untuk pinjaman tertentu
    public function index(Request $r, $loanId)
    {
        $loan = ModelPinjaman::where('user_id', $r->user()->id)->findOrFail($loanId);

        return response()->json([
            'loan' => $loan,

            'installments' => $loan->installments()->orderBy('due_date')->get()
        ]);
    }

    // ✅ Melihat semua permintaan pembayaran (user / bendahara)
    public function listPayments(Request $r)
{
    $payments = ModelPayment::with([
            'user',
            'installment.loan',
            'simpanan'
        ])
        ->where('status', 'PENDING')
        ->orderBy('created_at', 'desc')
        ->get()
        ->groupBy('proof');

    $result = [];

    foreach ($payments as $proof => $group) {

        $first = $group->first();
        $items = [];
        $total = 0;

        foreach ($group as $p) {

            $total += (float) $p->amount;

            // ================= CICILAN =================
            if ($p->installment_id && $p->installment) {
                $items[] = [
                    'type'            => 'CICILAN',
                    'sub_type'        => 'CICILAN : '.Carbon::parse($p->installment->due_date)->format('F Y'),
                    'loan_id'         => $p->loan_id,
                    'installment_id'  => $p->installment_id,
                    'jatuh_tempo'     => optional($p->installment->due_date)->format('Y-m-d'),
                    'amount'          => (float) $p->amount,
                ];
            }

            // ================= SIMPANAN =================
            if ($p->simpanan_id && $p->simpanan) {
                $items[] = [
                    'type'        => 'SIMPANAN',
                    'simpanan_id'=> $p->simpanan_id,
                    'sub_type'   => strtoupper($p->simpanan->type),
                    'periode'    => optional($p->simpanan->period)->format('Y-m-d'),
                    'amount'     => (float) $p->amount,
                ];
            }
        }

        $result[] = [
            'proof_url' => asset('storage/' . $proof),
            'proof_raw' => $proof,
            'user' => [
                'id'   => $first->user->id,
                'name' => $first->user->full_name,
            ],
            'total' => $total,
            'items' => $items,
            'status' => 'PENDING',
            'created_at' => $first->created_at->format('Y-m-d H:i:s'),
        ];
    }

    return response()->json([
        'data' => $result
    ]);
}

    
   public function bulkPayment(Request $r)
{
    // ================= VALIDASI INPUT =================
    $r->validate([
        'items'            => 'required|array|min:1',
        'items.*.type'     => 'required|in:SIMPANAN,CICILAN',
        'items.*.ref_id'   => 'required|integer',
        'items.*.amount'   => 'required|numeric|min:1',
        'proof'            => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        'note'             => 'nullable|string|max:255',
    ]);

    $user = $r->user();
    $proofPath = null;

    // ================= HANDLE PROOF (1 KALI) =================
    if ($r->hasFile('proof')) {

        $file = $r->file('proof');
        $ext  = strtolower($file->getClientOriginalExtension());

        // ===== PDF → SIMPAN LANGSUNG =====
        if ($ext === 'pdf') {

            $proofPath = $file->store('proofs', 'public');

        } else {
            // ===== IMAGE → VALIDASI + COMPRESS + WATERMARK =====

            // 1️⃣ MIME asli
            $realMime = mime_content_type($file->getRealPath());
            if (!in_array($realMime, ['image/jpeg', 'image/png', 'image/webp'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'File proof bukan gambar yang valid.'
                ], 422);
            }

            // 2️⃣ Decode image
            try {
                $manager = new ImageManager(new Driver());
                $image   = $manager->read($file);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'File gambar rusak atau tidak dapat diproses.'
                ], 422);
            }

            // 3️⃣ Sanitize (rotate + resize)
            $image->orient();
            $image->scaleDown(1080);

            // ================= WATERMARK =================
            $watermarkText =
                "KOPERASI DOSEN UBSI SUKABUMI\n" .
                now()->format('Y-m-d H:i') . "\n" .
                $user->full_name;

            $fontSize = max(16, intval($image->width() * 0.03));

            $image->text(
                $watermarkText,
                $image->width() - 20,
                $image->height() - 20,
                function ($font) use ($fontSize) {
                    $font->file(resource_path('fonts/Roboto.ttf'));
                    $font->size($fontSize);
                    $font->color('rgba(219, 205, 205, 0.3)');
                    $font->align('right');
                    $font->valign('bottom');
                }
            );
            // ================= END WATERMARK =================

            // 4️⃣ Re-encode & save
            $filename = 'proof_' . Str::uuid() . '.jpg';
            $fullPath = storage_path('app/public/proofs/' . $filename);

            $image->toJpeg(75)->save($fullPath);

            $proofPath = 'proofs/' . $filename;
        }
    }

    DB::beginTransaction();

    try {
        foreach ($r->items as $item) {

            // ================= SIMPANAN =================
            if ($item['type'] === 'SIMPANAN') {

                $simpanan = ModelSimpanan::where('id', $item['ref_id'])
                    ->where('user_id', $user->id)
                    ->whereNull('paid_at')
                    ->firstOrFail();

                // ❌ Cegah duplikasi PENDING
                $exists = ModelPayment::where('simpanan_id', $simpanan->id)
                    ->where('status', 'PENDING')
                    ->exists();

                if ($exists) continue;

                ModelPayment::create([
                    'simpanan_id' => $simpanan->id,
                    'user_id'     => $user->id,
                    'amount'      => $item['amount'],
                    'note'        => $r->note,
                    'proof'       => $proofPath,
                    'status'      => 'PENDING',
                ]);
            }

            // ================= CICILAN =================
            if ($item['type'] === 'CICILAN') {

                $cicilan = ModelCicilan::where('id', $item['ref_id'])
                    ->whereNull('paid_at')
                    ->firstOrFail();

                // ❌ Cegah duplikasi PENDING
                $exists = ModelPayment::where('installment_id', $cicilan->id)
                    ->where('status', 'PENDING')
                    ->exists();

                if ($exists) continue;

                ModelPayment::create([
                    'loan_id'         => $cicilan->loan_id,
                    'installment_id' => $cicilan->id,
                    'user_id'        => $user->id,
                    'amount'         => $item['amount'],
                    'note'           => $r->note,
                    'proof'          => $proofPath,
                    'status'         => 'PENDING',
                ]);
            }
        }

        DB::commit();

        return response()->json([
            'success'   => true,
            'message'   => 'Permintaan pembayaran berhasil dikirim. Menunggu verifikasi bendahara.',
            'proof_raw' => $proofPath,
            'proof_url' => asset('storage/'.$proofPath),
        ]);

    } catch (\Throwable $e) {

        DB::rollBack();
        Log::error('Bulk payment request failed', [
            'user_id' => $user->id,
            'error'   => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Gagal mengirim permintaan pembayaran.'
        ], 500);
    }
}



    // ✅ Approve pembayaran (bendahara)
public function approvePayment(Request $r, $paymentId)
{
    $payment = ModelPayment::findOrFail($paymentId);
    if ($payment->status !== 'PENDING') {
        return response()->json([
            'error' => 'Pembayaran sudah diproses'
        ], 400);
    }

    DB::beginTransaction();

    try {

        $payment->update([
            'status'      => 'APPROVED',
            'approved_at'=> now(),
        ]);

        if ($payment->simpanan_id) {

            ModelSimpanan::where('id', $payment->simpanan_id)->update([
                'amount'      => $payment->amount,
                'paid_at'     => now(),
                'approved_at'=> now(),
            ]);
        }

        // ===============================
        // 3️⃣ JIKA CICILAN
        // ===============================
        if ($payment->installment_id) {

            // update cicilan
            ModelCicilan::where('id', $payment->installment_id)->update([
                'paid_at' => now(),
            ]);

            // cek apakah pinjaman lunas
            $loanId = $payment->loan_id;

            $sisa = ModelCicilan::where('loan_id', $loanId)
                ->whereNull('paid_at')
                ->count();

            if ($sisa === 0) {
                ModelPinjaman::where('id', $loanId)->update([
                    'status' => 'LUNAS'
                ]);
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil di-approve'
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();

        Log::error('Approve payment failed', [
            'payment_id' => $paymentId,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Gagal approve pembayaran'
        ], 500);
    }
}

public function approveByProof(Request $r)
{
     $r->validate([
        'proof' => 'required|string'
    ]);

    $proof = $r->proof;

    // ✅ NORMALISASI PROOF
    if (str_starts_with($proof, 'http')) {
        $proof = parse_url($proof, PHP_URL_PATH);
        $proof = str_replace('/storage/', '', $proof);
    }

    DB::beginTransaction();
    
    try {
        // 🔑 Ambil semua payment pending dengan proof ini
        $payments = ModelPayment::where('proof', $proof)
            ->where('status', 'PENDING')
            ->get();

        if ($payments->isEmpty()) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada payment PENDING dengan bukti ini'
            ], 404);
        }

        foreach ($payments as $p) {

            // ================= PAYMENT =================
            $p->update([
                'status'      => 'APPROVED',
                'approved_at' => now(),
            ]);

            // ================= SIMPANAN =================
            if ($p->simpanan_id) {
                $simpanan = ModelSimpanan::find($p->simpanan_id);

                if ($simpanan && !$simpanan->paid_at) {
                    $simpanan->update([
                        'amount'       => $p->amount, // penting utk MANASUKA
                        'paid_at'      => now(),
                        'approved_at'  => now(),
                    ]);
                }
            }

            // ================= CICILAN =================
            if ($p->installment_id) {
                $cicilan = ModelCicilan::find($p->installment_id);

                if ($cicilan && !$cicilan->paid_at) {
                    $cicilan->update([
                        'paid_at' => now(),
                    ]);
                }

                // ===== CEK PINJAMAN =====
                if ($cicilan && $cicilan->loan) {
                    $loan = $cicilan->loan;

                    $sisa = $loan->installments()
                        ->whereNull('paid_at')
                        ->count();

                    if ($sisa === 0) {
                        $loan->update(['status' => 'LUNAS']);
                    }
                }
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Approve berhasil (berdasarkan bukti)',
            'count'   => $payments->count()
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('APPROVE BY PROOF ERROR', [
            'proof' => $proof,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Gagal approve',
            'error'   => $e->getMessage()
        ], 500);
    }
}


public function rejectByProof(Request $r, $paymentId)
{
    $payment = ModelPayment::findOrFail($paymentId);

    DB::beginTransaction();

    try {
        ModelPayment::where('proof', $payment->proof)
            ->where('status', 'PENDING')
            ->update([
                'status'      => 'REJECTED',
                'rejected_at' => now(),
                'note'        => $r->note ?? null,
            ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Semua pembayaran dengan bukti ini ditolak'
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}

public function TagihanUser(
    Request $r,
    PaymentStatusService $ps,
    SimpananAutoService $auto
) {
    $userId     = $r->user()->id;
    $activeYear = now()->year;

    // ================= AUTO CREATE =================
    $auto->ensurePokok($userId);
    $auto->ensureWajibPeriods($userId);
    $auto->ensureManasuka($userId);

    $tagihan = collect();

    // ================= SIMPANAN =================
    $simpanan = ModelSimpanan::where('user_id', $userId)
        ->whereNull('paid_at')
        ->orderByRaw("CASE WHEN type = 'manasuka' THEN 0 ELSE 1 END") // ⬅️ MANASUKA PALING ATAS
        ->orderBy('period')
        ->get();

    foreach ($simpanan as $s) {

    $code = strtoupper($s->type);

    // ================= AMOUNT =================
    $amount = $ps->getSimpananAmount($code, $activeYear);

    if ($code !== 'MANASUKA' && $amount <= 0) {
        continue;
    }

    // ================= STATUS =================
    $status = $ps->getBySimpanan($userId, $s->id) ?? 'UNPAID';

    if ($status === 'APPROVED') {
        continue;
    }

    $tagihan->push([
        'type'       => 'SIMPANAN',
        'sub_type'   => $code,
        'ref_id'     => $s->id,
        'due_date'   => $code === 'MANASUKA'
            ? null
            : ($s->period ? Carbon::parse($s->period)->format('Y-m-d') : null),
        'amount'     => (float) $amount,
        'editable'   => $code === 'MANASUKA',
        'status'     => $status,
        'selectable' => $ps->isSelectable($status),
    ]);
}

    // ================= CICILAN =================
    $cicilan = ModelCicilan::whereHas('loan', function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->where('status', 'APPROVED');
        })
        ->whereNull('paid_at')
        ->orderBy('due_date')
        ->get();

    foreach ($cicilan as $c) {

        $status = $ps->getByCicilan($c->id) ?? 'UNPAID';

        if ($status === 'APPROVED') {
            continue;
        }

        $tagihan->push([
            'type'       => 'CICILAN',
            'sub_type'   => 'PINJAMAN',
            'ref_id'     => $c->id,
            'due_date'   => $c->due_date
                ? Carbon::parse($c->due_date)->format('Y-m-d')
                : null,
            'amount'     => (float) $c->amount,
            'editable'   => false,
            'status'     => $status,
            'selectable' => $ps->isSelectable($status),
        ]);
    }

    return response()->json([
        'data' => $tagihan->values()
    ]);
}


}
