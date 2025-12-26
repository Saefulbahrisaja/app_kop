<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PinjamanController;
use App\Http\Controllers\SimpananController;
use App\Http\Controllers\CicilanController;

Route::post('/register',[AuthController::class,'register']);
Route::post('/login',[AuthController::class,'login']);

Route::middleware('auth:sanctum')->group(function () {

    // ✅ User info
    Route::get('/user', fn (Request $request) => $request->user());

    // ✅ Simpanan
    Route::get('/savings', [SimpananController::class, 'index']);        
    Route::post('/savings/deposit', [SimpananController::class, 'store']);
    Route::get('/savings/history', [SimpananController::class, 'history']); 
    Route::get('/savings/total', [SimpananController::class, 'totalByType']);
    Route::get('/savings/mutations', [SimpananController::class, 'mutations']);
    Route::post('/savings/withdraw', [SimpananController::class, 'withdraw']);
    Route::get('/savings/withdrawals', [SimpananController::class, 'withdrawalHistory']);


    // ✅ Pinjaman
    Route::get('/loans', [PinjamanController::class, 'index']);
    Route::post('/loans', [PinjamanController::class, 'store']);

    // ✅ Cicilan
    Route::get('/loans/{loan}/installments', [CicilanController::class, 'index']);

    // ✅ Request pembayaran cicilan (USER)
    Route::post('/loans/{loan}/installments/{installment}/pay', [CicilanController::class, 'requestPayment']);
    Route::get('/payments', [CicilanController::class, 'listPayments']);
    // ✅ Verifikasi pembayaran (BENDAHARA)
    Route::middleware('can:approve-payment')->group(function () {
        Route::post('/payments/{payment}/approve', [CicilanController::class, 'approvePayment']);
        Route::post('/payments/{payment}/reject',  [CicilanController::class, 'rejectPayment']);
    });
    Route::middleware('can:approve-withdrawal')->group(function () {
        Route::post('/withdrawals/{withdrawal}/approve', [SimpananController::class, 'approveWithdrawal']);
        Route::post('/withdrawals/{withdrawal}/reject',  [SimpananController::class, 'rejectWithdrawal']);
    });
    Route::middleware('can:approve-loan')->group(function () {
        Route::post('/loans/{loan}/approve', [PinjamanController::class, 'approveLoan']);
    });

    // ✅ Change password
    Route::post('/change-password',[AuthController::class,'changePassword']);

    // ✅ Summary untuk dashboard user
    Route::get('/summary', function(Request $r){
        return response()->json([
            'total_savings' => $r->user()->savings()->sum('balance'),
            'total_loans'   => $r->user()->loans()->where('status','APPROVED')->sum('amount')
        ]);
    });
});
