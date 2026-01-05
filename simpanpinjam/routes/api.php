<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PinjamanController;
use App\Http\Controllers\SimpananController;
use App\Http\Controllers\CicilanController;
use App\Http\Controllers\BendaharaController;

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| AUTHENTICATED
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
        Route::get('/user', fn (Request $r) => $r->user());
        Route::post('/change-password', [AuthController::class, 'changePassword']);

        Route::get('/summary', function (Request $r) {
            return response()->json([
                'total_savings' => $r->user()->savings()->sum('balance'),
                'total_loans'   => $r->user()->loans()
                    ->where('status', 'APPROVED')
                    ->sum('amount')
            ]);
        });

        // ===== PINJAMAN =====
        Route::get('/loans',      [PinjamanController::class, 'index']);
        Route::post('/loans',     [PinjamanController::class, 'store']);
        Route::get('/loan/limit', [PinjamanController::class, 'loanLimit']);

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

        // ===== DASHBOARD BENDAHARA =====
        Route::prefix('bendahara')->group(function () {
            Route::get('/dashboard',                 [BendaharaController::class, 'dashboard']);
            Route::get('/tunggakan',                 [BendaharaController::class, 'tunggakan']);
            Route::get('/setoran',                   [BendaharaController::class, 'setoran']);
            Route::get('/anggota/{id}',              [BendaharaController::class, 'detailAnggota']);
            Route::get('/saldo-simpanan',             [BendaharaController::class, 'saldoSimpanan']);
           
       });

        // ===== VERIFIKASI PENARIKAN =====
        Route::prefix('withdrawals')->group(function () {
            Route::post('/{withdrawal}/approve', [SimpananController::class, 'approveWithdrawal']);
            Route::post('/{withdrawal}/reject',  [SimpananController::class, 'rejectWithdrawal']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | BENDAHARA & KETUA (AKSES BERSAMA)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:BENDAHARA,KETUA')->group(function () {
        Route::get('/loan/list', [PinjamanController::class, 'listPengajuan']);
        Route::get('/dashboard',                 [BendaharaController::class, 'dashboard']);
        Route::get('/bendahara/grafik-kas-tahunan',         [BendaharaController::class, 'grafikKasTahunan']);
        Route::get('/bendahara/grafik/piutang',             [BendaharaController::class, 'grafikSisaPiutang']);
            
    });

    /*
    |--------------------------------------------------------------------------
    | KETUA
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:KETUA')->group(function () {
        Route::post('/loans/{loan}/approve', [PinjamanController::class, 'approveLoan']);
        Route::get('/grafik/piutang-per-anggota', [BendaharaController::class, 'grafikSisaPiutangPerAnggota']);
        Route::get('/grafik/proyeksi-piutang',    [BendaharaController::class, 'proyeksiPiutang']);
     
    });

});
