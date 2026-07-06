<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PinjamanController;
use App\Http\Controllers\SimpananController;
use App\Http\Controllers\CicilanController;
use App\Http\Controllers\BendaharaController;
use App\Http\Controllers\LpjController;
use App\Http\Controllers\PendapatanController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ForgotPasswordController;

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'request']);
Route::post('/reset-password',  [ForgotPasswordController::class, 'reset']);


/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | MEMBER
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:MEMBER')->group(function () {

        // ===== SIMPANAN =====
        Route::prefix('savings')->group(function () {
            Route::get('/',            [SimpananController::class, 'index']);
            Route::post('/deposit',    [SimpananController::class, 'store']);
            Route::get('/history',     [SimpananController::class, 'history']);
            Route::get('/total',       [SimpananController::class, 'totalByType']);
            Route::get('/mutations',   [SimpananController::class, 'mutations']);
            Route::post('/withdraw',   [SimpananController::class, 'withdraw']);
            Route::get('/withdrawals', [SimpananController::class, 'withdrawalHistory']);
        });

        // ===== USER =====
        Route::get('/user', fn(Request $r) => $r->user());
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::get('/info/dashboard', [BendaharaController::class, 'dashboard']);
        Route::get('/summary', function (Request $r) {
            return response()->json([
                'total_savings' => $r->user()->savings()->sum('balance'),
                'total_loans'   => $r->user()->loans()
                    ->where('status', 'APPROVED')
                    ->sum('amount'),
            ]);
        });

        // ===== PINJAMAN =====
        Route::get('/loans',      [PinjamanController::class, 'index']);
        Route::post('/loans',     [PinjamanController::class, 'store']);
        Route::get('/loan/limit', [PinjamanController::class, 'loanLimit']);

        Route::get('/pendapatan/can-input', [PendapatanController::class, 'canInput']);
        Route::post('/pendapatan', [PendapatanController::class, 'store']);

        // ===== CICILAN =====
        Route::get('/loans/{loan}/installments', [CicilanController::class, 'index']);
        Route::get('/tagihan',                   [CicilanController::class, 'TagihanUser']);
        Route::post('/bulk',                     [CicilanController::class, 'bulkPayment']);
    });

    /*
    |--------------------------------------------------------------------------
    | BENDAHARA
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:BENDAHARA')->group(function () {
        // ===== VERIFIKASI CICILAN =====
        Route::prefix('payments')->group(function () {
            Route::get('/list',              [CicilanController::class, 'listPayments']);
            Route::post('/approve-by-proof', [CicilanController::class, 'approveByProof']);
            Route::post('/reject-by-proof',  [CicilanController::class, 'rejectByProof']);
            Route::post('/{payment}/reject', [CicilanController::class, 'rejectPayment']);
        });
        // ===== VERIFIKASI PENARIKAN =====
        Route::prefix('withdrawals')->group(function () {
            Route::post(
                '/{withdrawal}/approve',
                [SimpananController::class, 'approveWithdrawal']
            );
            Route::post(
                '{withdrawal}/disburse',
                [SimpananController::class, 'disburseWithdrawal']
            );
        });

        Route::prefix('expenses')->group(function () {
            Route::get('/', [ExpenseController::class, 'index']);
            Route::post('/', [ExpenseController::class, 'store']);
            Route::put('/{id}', [ExpenseController::class, 'update']);
            Route::delete('/{id}', [ExpenseController::class, 'destroy']);
            Route::get('/history', [ExpenseController::class, 'history']);
            Route::get('/summary', [ExpenseController::class, 'summary']);
        });

        // ===== DATA ANGGOTA =====
        Route::get('/bendahara/anggota/{id}', [BendaharaController::class, 'detailAnggota']);
    });



    /*
    |--------------------------------------------------------------------------
    | BENDAHARA & KETUA (AKSES BERSAMA)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:BENDAHARA,KETUA')->group(function () {

        Route::prefix('withdrawals')->group(function () {
            Route::get('/list', [SimpananController::class, 'withdrawalRequests']);
            Route::get('/withdraw-info', [SimpananController::class, 'withdrawInfo']);
            Route::post(
                '/{withdrawal}/approve-ketua',
                [SimpananController::class, 'approveWithdrawalKetua']
            );
            Route::post('/{withdrawal}/reject',  [SimpananController::class, 'rejectWithdrawal']);
        });


        // ===== PINJAMAN =====
        Route::get('/loan/list',             [PinjamanController::class, 'listPengajuan']);
        Route::post('/loans/{loan}/approve', [PinjamanController::class, 'approveLoan']);

        // ===== DASHBOARD =====
        Route::get('/bendahara/dashboard', [BendaharaController::class, 'dashboard']);
        Route::prefix('bendahara')->group(function () {
            Route::get('/grafik-kas-tahunan',       [BendaharaController::class, 'grafikKasTahunan']);
            Route::get('/grafik/piutang',           [BendaharaController::class, 'grafikSisaPiutang']);
            Route::get('/piutang-per-anggota',      [BendaharaController::class, 'grafikSisaPiutangPerAnggota']);
            Route::get('/proyeksi-piutang',         [BendaharaController::class, 'proyeksiPiutang']);
            Route::get('/tunggakan',                [BendaharaController::class, 'tunggakan']);
            Route::get('/setoran',                  [BendaharaController::class, 'setoran']);
            Route::get('/saldo-simpanan',           [BendaharaController::class, 'saldoSimpanan']);
        });

        // ===== LPJ =====
        Route::get('/lpj',     [LpjController::class, 'lpj']);
        Route::get('/lpj/pdf', [LpjController::class, 'lpjPdf']);
        //=====verifikasi user=====
        Route::post('/users/{id}/verify', [AuthController::class, 'verifyUser']);
    });

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATIONS (SEMUA ROLE LOGIN)
    |--------------------------------------------------------------------------
    */
    Route::get('/notifications', function (Request $r) {
        return response()->json([
            'count_unread' => $r->user()->unreadNotifications()->count(),
            'data' => $r->user()->notifications()->latest()->take(50)->get()->map(function ($n) {
                return [
                    'id'         => $n->id,
                    'message'    => $n->data['message'],
                    'loan_id'    => $n->data['loan_id'] ?? null,
                    'read_at'    => $n->read_at,
                    'is_read'    => $n->read_at !== null,
                    'created_at' => $n->created_at->diffForHumans(),
                ];
            })
        ]);
    });


    Route::post('/notifications/{id}/read', function (Request $r, $id) {
        $notification = $r->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai sudah dibaca'
        ]);
    });

    Route::post('/notifications/read-all', function (Request $r) {
        $r->user()->unreadNotifications->markAsRead();
        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi ditandai sudah dibaca'
        ]);
    });

    Route::get('/loan/pending-count', [PinjamanController::class, 'pendingCount']);

    Route::get('/notifications/unread', function (Request $r) {
        return $r->user()
            ->unreadNotifications()
            ->latest()
            ->get()
            ->map(function ($n) {
                return [
                    'id'         => $n->id,
                    'message'    => $n->data['message'] ?? '',
                    'loan_id'    => $n->data['loan_id'] ?? null,
                    'created_at' => $n->created_at->toISOString(),
                ];
            });
    });
});
