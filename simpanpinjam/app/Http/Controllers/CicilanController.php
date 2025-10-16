<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ModelPinjaman;
use App\Models\ModelCicilan;
use App\Models\ModelPayment;
use App\Models\ModelUser;
use Illuminate\Database\QueryException;

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
        $query = ModelPayment::with(['loan', 'installment', 'user'])
            ->orderBy('created_at', 'desc');

        // Jika user biasa → hanya lihat milik sendiri
        if ($r->user()->cannot('approve-payment')) {
            $query->where('user_id', $r->user()->id);
        }

        // Filter opsional berdasarkan status (?status=PENDING/APPROVED/REJECTED)
        if ($r->has('status')) {
            $query->where('status', $r->status);
        }

        $payments = $query->get()->map(function ($p) {
            return [
                'id'             => $p->id,
                'loan_id'        => $p->loan_id,
                'installment_id' => $p->installment_id,
                'amount'         => (float) $p->amount,
                'status'         => $p->status,
                'note'           => $p->note,
                'proof_url'      => $p->proof ? asset('storage/'.$p->proof) : null,
                'created_at'     => $p->created_at->toDateTimeString(),
                'approved_at'    => $p->approved_at,
                'rejected_at'    => $p->rejected_at,
                'loan'           => [
                    'loan_type' => $p->loan->loan_type,
                    'status'    => $p->loan->status,
                ],
                'installment'    => [
                    'amount'   => $p->installment->amount,
                    'due_date' => $p->installment->due_date,
                ],
                'user' => [
                    'id'        => $p->user->id,
                    'username'  => $p->user->username,
                    'full_name' => $p->user->full_name,
                ]
            ];
        });

        return response()->json(['payments' => $payments]);
    }

    // ✅ Request bayar cicilan (user)
    public function requestPayment(Request $r, $loanId, $installmentId)
    {
        $loan = ModelPinjaman::where('user_id', $r->user()->id)->findOrFail($loanId);
        $installment = $loan->installments()->where('id', $installmentId)->firstOrFail();

        if ($installment->paid_at) {
            return response()->json(['success' => false, 'message' => 'Cicilan ini sudah dibayar.'], 400);
        }

        $existingPayment = ModelPayment::where('installment_id', $installment->id)
            ->where('status', 'PENDING')
            ->first();

        if ($existingPayment) {
            return response()->json([
                'success' => false,
                'message' => 'Permintaan pembayaran sebelumnya masih menunggu verifikasi bendahara.'
            ], 400);
        }

        $r->validate([
            'amount' => ['required','numeric',function ($attr,$value,$fail) use ($installment){
                if ((float)$value < (float)$installment->amount) {
                    $fail("Jumlah bayar minimal {$installment->amount}");
                }
            }],
            'note'  => ['nullable','string','max:255'],
            'proof' => ['nullable','file','mimes:jpg,jpeg,png,pdf','max:2048'],
        ]);

        $proofPath = $r->hasFile('proof') 
            ? $r->file('proof')->store('proofs', 'public')
            : null;

        try {
            $payment = ModelPayment::create([
                'loan_id'        => $loan->id,
                'installment_id' => $installment->id,
                'user_id'        => $r->user()->id,
                'amount'         => $r->amount,
                'note'           => $r->note,
                'proof'          => $proofPath,
                'status'         => 'PENDING'
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Permintaan pembayaran duplikat terdeteksi. Tunggu hingga permintaan sebelumnya diproses.'
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Permintaan pembayaran berhasil dibuat. Menunggu verifikasi bendahara.',
            'payment' => [
                'id'             => $payment->id,
                'loan_id'        => $loan->id,
                'installment_id' => $installment->id,
                'amount'         => (float)$payment->amount,
                'note'           => $payment->note,
                'proof_url'      => $payment->proof ? asset('storage/'.$payment->proof) : null,
                'status'         => $payment->status,
                'created_at'     => $payment->created_at->toDateTimeString(),
            ]
        ], 201);
    }

    // ✅ Approve pembayaran (bendahara)
    public function approvePayment(Request $r, $paymentId)
    {
        $payment = ModelPayment::with(['loan', 'installment'])->findOrFail($paymentId);

        if ($payment->status !== 'PENDING') {
            return response()->json(['error' => 'Pembayaran sudah diproses.'], 400);
        }

        $payment->update([
            'status' => 'APPROVED',
            'approved_at' => now()
        ]);

        $payment->installment->update([
            'paid_at' => now(),
            'paid_amount' => $payment->amount
        ]);

        if ($payment->loan->installments()->whereNull('paid_at')->count() === 0) {
            $payment->loan->update(['status' => 'LUNAS']);
        }

        return response()->json([
            'message' => 'Pembayaran disetujui dan cicilan ditandai lunas.',
            'loan_status' => $payment->loan->fresh()->status,
            'payment' => $payment
        ]);
    }

    // ✅ Reject pembayaran (bendahara)
    public function rejectPayment(Request $r, $paymentId)
    {
        $payment = ModelPayment::findOrFail($paymentId);

        if ($payment->status !== 'PENDING') {
            return response()->json(['error' => 'Pembayaran sudah diproses.'], 400);
        }

        $payment->update([
            'status' => 'REJECTED',
            'rejected_at' => now(),
            'note' => $r->note ?? null
        ]);

        return response()->json([
            'message' => 'Pembayaran ditolak oleh bendahara.',
            'payment' => $payment
        ]);
    }
}
