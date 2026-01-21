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
            'simpanan',
            'loan' // ⬅️ penting untuk SHU
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
                    'type'           => 'CICILAN',
                    'sub_type'       => 'Cicilan : ' .$p->installment->loan->loan_type . ' bulan ' .
                        Carbon::parse($p->installment->due_date)->format('F Y'),
                    'loan_id'        => $p->loan_id,
                    'installment_id'=> $p->installment_id,
                    'jatuh_tempo'    => optional($p->installment->due_date)->format('Y-m-d'),
                    'amount'         => (float) $p->amount,
                ];
                continue;
            }

            // ================= SIMPANAN =================
            if ($p->simpanan_id && $p->simpanan) {
                $items[] = [
                    'type'         => 'SIMPANAN',
                    'simpanan_id'  => $p->simpanan_id,
                    'sub_type'     => strtoupper($p->simpanan->type) . ' : ' .
                        Carbon::parse($p->simpanan->period)->format('F Y'),
                    'periode'      => optional($p->simpanan->period)->format('Y-m-d'),
                    'amount'       => (float) $p->amount,
                ];
                continue;
            }

            // ================= SHU =================
            $isSHU =
                $p->loan_id &&
                is_null($p->installment_id) &&
                is_null($p->simpanan_id);

            if ($isSHU && $p->loan) {
                $items[] = [
                    'type'      => 'SHU',
                    'sub_type'  => 'SHU',
                    'loan_id'   => $p->loan_id,
                    'loan_type' => strtoupper($p->loan->loan_type),
                    'amount'    => (float) $p->amount,
                ];
            }
        }

        $typeOrder = [
            'SHU' => 1,
            'CICILAN'      => 2,
            'SIMPANAN'  => 3,
        ];

        usort($items, function ($a, $b) use ($typeOrder) {
            return ($typeOrder[$a['type']] ?? 99)
                <=> ($typeOrder[$b['type']] ?? 99);
        });

        $result[] = [
            'proof_url'  => asset('storage/' . $proof),
            'proof_raw'  => $proof,
            'user' => [
                'id'   => $first->user->id,
                'name' => $first->user->full_name,
            ],
            'total'      => $total,
            'items'      => $items,
            'status'     => 'PENDING',
            'created_at' => $first->created_at->format('Y-m-d H:i:s'),
        ];
    }

    return response()->json([
        'data' => $result
    ]);
}

    
  public function bulkPayment(Request $r)
{
    $createdCount = 0;
    $MIN_INPUT = 1000;

    $r->validate([
        'items'            => 'required|array|min:1',
        'items.*.type'     => 'required|in:SIMPANAN,CICILAN,PENDAPATAN',
        'items.*.ref_id'   => 'nullable|integer',
        'items.*.amount'   => 'required|numeric|min:1',
        'proof'            => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        'note'             => 'nullable|string|max:255',
    ]);

    $user = $r->user();
    $proofPath = null;

    // ================= PROOF =================
    $file = $r->file('proof');
    $ext  = strtolower($file->getClientOriginalExtension());

    if ($ext === 'pdf') {
        $proofPath = $file->store('proofs', 'public');
    } else {
        $manager = new ImageManager(new Driver());
        $image   = $manager->read($file);
        $image->orient()->scaleDown(1080);

        $image->text(
            "KOPERASI DOSEN UBSI\n".now()->format('Y-m-d H:i')."\n".$user->full_name,
            $image->width() - 20,
            $image->height() - 20,
            fn ($font) => $font->size(18)->align('right')->valign('bottom')
        );

        $filename = 'proof_'.Str::uuid().'.jpg';
        $image->toJpeg(75)->save(storage_path('app/public/proofs/'.$filename));
        $proofPath = 'proofs/'.$filename;
    }

    DB::beginTransaction();
    try {

        foreach ($r->items as $item) {

            /* ================= SIMPANAN ================= */
        if ($item['type'] === 'SIMPANAN') {
            $simpanan = ModelSimpanan::where('id', $item['ref_id'])
                ->where('user_id', $user->id)
                ->whereNull('paid_at')
                ->first();
    
            if (!$simpanan) continue;

            // ✅ MINIMAL KHUSUS MANASUKA
                    if ($simpanan->type === 'manasuka' && $item['amount'] < $MIN_INPUT) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Minimal input Simpanan Manasuka adalah Rp 5.000'
                        ], 422);
                    }

                    $exists = ModelPayment::where('simpanan_id', $simpanan->id)
                        ->where('status', 'PENDING')
                        ->exists();

                    if ($exists) continue;

                    ModelPayment::create([
                        'simpanan_id' => $simpanan->id,
                        'user_id'     => $user->id,
                        'amount'      => $item['amount'],
                        'note'        => "Simpanan {$simpanan->type} periode ".
                            Carbon::parse($simpanan->period)->format('F Y'),
                        'proof'       => $proofPath,
                        'status'      => 'PENDING',
                    ]);

                    $createdCount++;
                }


            /* ================= CICILAN ================= */
            if ($item['type'] === 'CICILAN') {

                $cicilan = ModelCicilan::where('id', $item['ref_id'])
                    ->whereNull('paid_at')
                    ->first();

                if (!$cicilan) continue;

                $exists = ModelPayment::where('installment_id', $cicilan->id)
                    ->where('status', 'PENDING')
                    ->exists();

                if ($exists) continue;

                ModelPayment::create([
                    'loan_id'         => $cicilan->loan_id,
                    'installment_id' => $cicilan->id,
                    'user_id'        => $user->id,
                    'amount'         => $item['amount'],
                    'note'           => "Cicilan ".$cicilan->loan->loan_type." jatuh tempo ".
                        Carbon::parse($cicilan->due_date)->format('Y-m-d'),
                    'proof'          => $proofPath,
                    'status'         => 'PENDING',
                ]);

                $createdCount++;
            }

            /* ================= SHU ================= */
            if ($item['type'] === 'PENDAPATAN') {

                // ✅ MINIMAL SHU
                if ($item['amount'] < $MIN_INPUT) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Minimal input SHU adalah Rp 5.000'
                    ], 422);
                }

                $loan = ModelPinjaman::where('id', $item['ref_id'])
                    ->where('user_id', $user->id)
                    ->whereIn('status', ['APPROVED','LUNAS'])
                    ->first();

                if (!$loan) continue;

                $exists = ModelPayment::where('loan_id', $loan->id)
                    ->whereNull('simpanan_id')
                    ->whereNull('installment_id')
                    ->whereIn('status',['PENDING','APPROVED'])
                    ->exists();

                if ($exists) continue;

                ModelPayment::create([
                    'loan_id' => $loan->id,
                    'user_id' => $user->id,
                    'amount'  => $item['amount'],
                    'note'    => 'SHU pinjaman '.$loan->loan_type,
                    'proof'   => $proofPath,
                    'status'  => 'PENDING',
                ]);

                $createdCount++;
            }

        }

        if ($createdCount === 0) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada item yang dapat diproses'
            ], 422);
        }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil dikirim',
                'count'   => $createdCount
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('bulkPayment', ['e'=>$e->getMessage()]);

            return response()->json([
                'success'=>false,
                'message'=>'Server error'
            ],500);
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
                        'amount'      => $p->amount,
                        'paid_at'     => now(),
                        'approved_at' => now(),
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

            // ================= SHU =================
            $isSHU =
                $p->loan_id &&
                is_null($p->simpanan_id) &&
                is_null($p->installment_id);

            if ($isSHU) {

                // ❌ Cegah SHU dobel
                $exists = \App\Models\ModelPendapatan::where('loan_id', $p->loan_id)
                    ->where('type', 'SHU')
                    ->exists();

                if (!$exists) {
                    \App\Models\ModelPendapatan::create([
                        'user_id' => $p->user_id,
                        'loan_id' => $p->loan_id,
                        'amount'  => $p->amount,
                        'periode' => now(),
                        'type'    => 'SHU',
                        'note'    => $p->note,
                    ]);
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


public function rejectByProof(Request $r)
{
    $r->validate([
        'proof' => 'required|string',
        'note'  => 'nullable|string|max:255'
    ]);

    $proof = $r->proof;

    // ✅ NORMALISASI PROOF (URL → path storage)
    if (str_starts_with($proof, 'http')) {
        $proof = parse_url($proof, PHP_URL_PATH);
        $proof = str_replace('/storage/', '', $proof);
    }

    DB::beginTransaction();

    try {
        // 🔑 Ambil semua payment PENDING dengan proof ini
        $payments = ModelPayment::where('proof', $proof)
            ->where('status', 'PENDING')
            ->get();

        if ($payments->isEmpty()) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada pembayaran PENDING untuk bukti ini'
            ], 404);
        }

        foreach ($payments as $p) {
            $p->update([
                'status'      => 'REJECTED',
                'rejected_at' => now(),
                'note'        => $r->note,
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Semua pembayaran dengan bukti ini berhasil ditolak',
            'count'   => $payments->count()
        ]);

    } catch (\Throwable $e) {

        DB::rollBack();
        Log::error('REJECT BY PROOF ERROR', [
            'proof' => $proof,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Gagal menolak pembayaran',
        ], 500);
    }
}


public function TagihanUser(
    Request $r,
    PaymentStatusService $ps,
    SimpananAutoService $auto
) {
    $userId = $r->user()->id;

    // ================= AUTO CREATE =================
    $auto->ensurePokok($userId);
    $auto->ensureWajibPeriods($userId);
    $auto->ensureManasuka($userId);

    $tagihan = collect();

    /*
    |--------------------------------------------------------------------------
    | 1️⃣ SIMPANAN MANASUKA (SELALU TAMPIL)
    |--------------------------------------------------------------------------
    */
    $manasuka = ModelSimpanan::where('user_id', $userId)
        ->where('type', 'manasuka')
        ->whereNull('paid_at')
        ->first();

    if ($manasuka) {
        $status = $ps->getBySimpanan($userId, $manasuka->id) ?? 'UNPAID';

        $tagihan->push([
            'type'       => 'SIMPANAN',
            'sub_type'   => 'MANASUKA',
            'ref_id'     => $manasuka->id,
            'due_date'   => null,
            'amount'     => 0,
            'editable'   => true,
            'status'     => $status,
            'late'       => false,
            'selectable' => $ps->isSelectable($status),
        ]);
    }

    $canInput = app(PendapatanController::class)->canInput(request());
    if ($canInput->getData()->can_input === true) {

        // cek apakah sudah ada payment pending SHU
        $shuPending = DB::table('payment')
            ->where('user_id', $userId)
            ->where('loan_id', $canInput->getData()->loan_id)
            ->whereNull('installment_id')
            ->whereNull('simpanan_id')
            ->where('status', 'PENDING')
            ->exists();

        $status = $shuPending ? 'PENDING' : 'UNPAID';

        $tagihan->push([
            'type'       => 'PENDAPATAN',
            'sub_type'   => 'SHU',
            'ref_id'     => $canInput->getData()->loan_id,
            'due_date'   => null,
            'amount'     => 0,
            'editable'   => true,
            'status'     => $status,
            'late'       => false,
            'selectable' => $ps->isSelectable($status),
        ]);
    }

    $now = now()->startOfMonth();
    $wajibs = ModelSimpanan::where('user_id', $userId)
    ->where('type', 'wajib')
    ->whereNull('paid_at')
    ->whereDate('period', '<=', $now)
    ->orderBy('period')
    ->get();

    foreach ($wajibs as $wajib) {

        $period = Carbon::parse($wajib->period)->startOfMonth();
        $year   = $period->year;

        /**
         * AMBIL AMOUNT BERDASARKAN JENIS_SIMPANAN
         */
        $amount = (float) DB::table('jenis_simpanan')
            ->where('code', 'WAJIB')
            ->where('mandatory', 1)
            ->where(function ($q) use ($year) {
                $q->whereNull('periode')
                ->orWhere('periode', '<=', $year);
            })
            ->orderByDesc('periode')
            ->value('amount') ?? 0;

        /**
         * STATUS TELAT / UNPAID
         */
        $status = $period->lt($now) ? 'TELAT' : 'UNPAID';
        $paymentStatus = $ps->getBySimpanan($userId, $wajib->id);
        if ($paymentStatus) {
            $status = $paymentStatus; // PENDING / PAID
        } else {
            $status = $period->lt($now) ? 'TELAT' : 'UNPAID';
        }

        $tagihan->push([
            'type'       => 'SIMPANAN',
            'sub_type'   => 'WAJIB',
            'ref_id'     => $wajib->id,
            'due_date'   => $period->format('Y-m-d'),
            'amount'     => $amount,
            'editable'   => false,
            'status'     => $status,
            'late'       => $period->lt($now),
            'selectable' => $ps->isSelectable($status),
        ]);
    }
    /*
    |--------------------------------------------------------------------------
    | 3️⃣ CICILAN (SEMUA YANG BELUM DIBAYAR + TELAT)
    |--------------------------------------------------------------------------
    */
    $cicilan = ModelCicilan::whereHas('loan', function ($q) use ($userId) {
            $q->where('user_id', $userId)
            ->whereIn('status', ['APPROVED']);
        })
        ->whereNull('paid_at')
        ->orderBy('due_date')
        ->get();
    $status = $ps->getBySimpanan($userId, $manasuka->id) ?? 'UNPAID';
    $today = Carbon::today();

    foreach ($cicilan as $c) {
        $dueDate = Carbon::parse($c->due_date)->startOfDay();
        // cek payment pending cicilan
        $pending = DB::table('payment')
            ->where('installment_id', $c->id)
            ->where('status', 'PENDING')
            ->exists();

        if ($pending) {
            $status = 'PENDING';
            $late   = false;
        } elseif ($dueDate->lt($today)) {
            $status = 'TELAT';
            $late   = true;
        } else {
            $status = 'UNPAID';
            $late   = false;
        }

        $tagihan->push([
            'type'       => 'CICILAN',
            'sub_type'   => strtoupper($c->loan->loan_type),
            'ref_id'     => $c->id,
            'due_date'   => $dueDate->format('Y-m-d'),
            'amount'     => (float) $c->amount,
            'editable'   => false,
            'status'     => $status,
            'late'       => $late,
            'selectable' => $ps->isSelectable($status),
        ]);
    }
    return response()->json([
        'data' => $tagihan->values()
    ]);
}




}
