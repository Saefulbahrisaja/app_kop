<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ModelPinjaman;
use App\Models\ModelCicilan;
use App\Services\LoanLimitService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ModelUser;
use App\Notifications\LoanStatusChanged;

class PinjamanController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LIST PINJAMAN USER
    |--------------------------------------------------------------------------
    */
    public function index(Request $r)
    {
        $loans = $r->user()
            ->loans()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['loans' => $loans]);
    }

    /*
    |--------------------------------------------------------------------------
    | LIST PENGAJUAN (BENDAHARA / KETUA)
    |--------------------------------------------------------------------------
    */
    public function listPengajuan(Request $request)
    {
        $user = $request->user();

        if (!in_array($user->role, ['BENDAHARA', 'KETUA'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $query = ModelPinjaman::with('user:id,full_name')
            ->orderBy('created_at', 'desc');

        // Filter berdasarkan role
        if ($user->role === 'BENDAHARA') {
            $query->where('status', 'PENDING');
        }

        if ($user->role === 'KETUA') {
            $query->where('status', 'APPROVED_BENDAHARA');
        }

        // Filter opsional
        if ($request->filled('loan_type')) {
            $query->where('loan_type', $request->loan_type);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $loans = $query->paginate(10);

        return response()->json([
            'success' => true,
            'role'    => $user->role,
            'total'   => $loans->total(),
            'data'    => $loans->map(function ($loan) use ($user) {

                $canApprove =
                    ($user->role === 'BENDAHARA' && $loan->status === 'PENDING') ||
                    ($user->role === 'KETUA' && $loan->status === 'APPROVED_BENDAHARA');

                return [
                    'pinjaman_id' => $loan->id,
                    'anggota' => [
                        'id'   => $loan->user->id,
                        'nama' => $loan->user->full_name,
                    ],
                    'loan_type'        => $loan->loan_type,
                    'amount'           => (float) $loan->amount,
                    'term_months'      => $loan->term_months,
                    'status'           => $loan->status,
                    'can_approve'      => $canApprove,
                    'can_reject'       => $canApprove,
                    'note'             => $loan->note,
                    'tanggal_pengajuan'=> $loan->created_at->format('Y-m-d H:i'),
                ];
            }),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | AJUKAN PINJAMAN
    |--------------------------------------------------------------------------
    */
    public function store(Request $r)
    {
        $r->validate([
            'amount'      => 'required|numeric|min:1',
            'term_months' => 'required|integer|min:1',
            'loan_type'   => 'required|in:REGULER,TALANGAN'
        ]);

        $userId   = $r->user()->id;
        $loanType = $r->loan_type;

        $activeLoans = ModelPinjaman::where('user_id', $userId)
            ->whereIn('status', ['PENDING', 'APPROVED', 'APPROVED_BENDAHARA'])
            ->get();

        if ($loanType === 'REGULER' && $activeLoans->isNotEmpty()) {
            return response()->json([
                'error' => 'Masih ada pinjaman aktif.'
            ], 400);
        }

        if ($loanType === 'TALANGAN') {
            if ($activeLoans->where('loan_type', 'TALANGAN')->isNotEmpty()) {
                return response()->json([
                    'error' => 'Masih ada pinjaman talangan.'
                ], 400);
            }
            $r->merge(['term_months' => 1]);
        }

        $loan = ModelPinjaman::create([
            'user_id'     => $userId,
            'amount'      => $r->amount,
            'term_months' => $r->term_months,
            'status'      => 'PENDING',
            'loan_type'   => $loanType,
        ]);

        // Generate cicilan
        $base  = floor($loan->amount / $loan->term_months / 10000) * 10000;
        $total = 0;

        for ($i = 1; $i <= $loan->term_months; $i++) {
            $amount = ($i === $loan->term_months)
                ? $loan->amount - $total
                : $base;

            $loan->installments()->create([
                'amount'   => $amount,
                'due_date' => now()->addMonths($i)
            ]);

            $total += $amount;
        }

        return response()->json([
            'success' => true,
            'loan'    => $loan
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | LIMIT PINJAMAN
    |--------------------------------------------------------------------------
    */
    public function loanLimit(Request $r, LoanLimitService $service)
    {
        $user = $r->user();

        $yearsAsMember = Carbon::parse($user->tanggal_gabung)
            ->diffInYears(now());

        $limit = $service->getLimitByYears($yearsAsMember);

        return response()->json([
            'years_as_member' => $yearsAsMember,
            'max_amount'      => $limit['max_nominal'],
            'max_months'      => $limit['max_tenor'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVE / REJECT (2 TAHAP)
    |--------------------------------------------------------------------------
    */
    public function approveLoan(Request $r, $id)
    {
        $user = $r->user();

        $r->validate([
            'status' => 'required|in:APPROVED,REJECTED',
            'note'   => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $loan      = ModelPinjaman::findOrFail($id);
            $oldStatus = $loan->status;

            // ================= BENDAHARA =================
            if ($user->role === 'BENDAHARA') {

                if ($loan->status !== 'PENDING') {
                    throw new \Exception('Pinjaman sudah diproses.');
                }

                if ($r->status === 'REJECTED') {
                    $loan->update([
                        'status' => 'REJECTED',
                        'note'   => $r->note,
                    ]);
                    $loan->installments()->delete();
                } else {
                    $loan->update([
                        'status' => 'APPROVED_BENDAHARA',
                        'note'   => $r->note,
                        'approved_by_bendahara_at' => now(),
                    ]);
                }
            }

            // ================= KETUA =================
            elseif ($user->role === 'KETUA') {

                if ($loan->status !== 'APPROVED_BENDAHARA') {
                    throw new \Exception('Belum disetujui Bendahara.');
                }

                if ($r->status === 'REJECTED') {
                    $loan->update([
                        'status' => 'REJECTED',
                        'note'   => $r->note,
                    ]);
                    $loan->installments()->delete();
                } else {
                    $loan->update([
                        'status' => 'APPROVED',
                        'note'   => $r->note ?: 'Pinjaman disetujui oleh Ketua.',
                        'approved_at' => now(),
                        'approved_by_ketua_at' => now(),
                    ]);
                }
            } else {
                throw new \Exception('Unauthorized');
            }

            /*
            |--------------------------------------------------------------------------
            | NOTIFIKASI (ANTI DOBEL)
            |--------------------------------------------------------------------------
            */
            $notified = false;

            if ($oldStatus !== $loan->status) {

                // ke Ketua (setelah Bendahara approve)
                if (!$notified && $loan->status === 'APPROVED_BENDAHARA') {
                    ModelUser::where('role', 'KETUA')->each(function ($ketua) use ($loan, $oldStatus) {
                        $ketua->notify(
                            new LoanStatusChanged($loan, $oldStatus, $loan->status)
                        );
                    });
                    $notified = true;
                }

                // ke Anggota (final)
                if (
                    !$notified &&
                    in_array($loan->status, ['APPROVED', 'REJECTED'])
                ) {
                    $loan->user->notify(
                        new LoanStatusChanged($loan, $oldStatus, $loan->status)
                    );
                    $notified = true;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status pinjaman diperbarui.',
                'status'  => $loan->status
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function pendingCount(Request $r)
    {
        $user = $r->user();

        if ($user->role === 'BENDAHARA') {
            $count = ModelPinjaman::where('status', 'PENDING')->count();
        } 
        elseif ($user->role === 'KETUA') {
            $count = ModelPinjaman::where('status', 'APPROVED_BENDAHARA')->count();
        } 
        else {
            $count = 0;
        }

        return response()->json([
            'success' => true,
            'role'    => $user->role,
            'count'   => $count
        ]);
    }

}
