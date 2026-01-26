<?php

namespace App\Http\Controllers;

use App\Models\ModelUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Mail\ForgotPasswordOtpMail;

class ForgotPasswordController extends Controller
{
    /**
     * 1️⃣ REQUEST OTP
     */
   public function request(Request $r)
{
    $r->validate([
        'email' => 'required|email'
    ]);

    $user = ModelUser::where('email', $r->email)->first();

    if (!$user) {
        return response()->json([
            'message' => 'Email tidak ditemukan'
        ], 404);
    }

    $otp = random_int(100000, 999999);

    // 🧹 hapus OTP lama
    DB::table('password_resets')
        ->where('email', $user->email)
        ->delete();

    // 🔐 simpan OTP baru
    DB::table('password_resets')->insert([
        'email'      => $user->email,
        'otp'        => Hash::make($otp),
        'expires_at' => now()->addMinutes(15),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // 📧 kirim email
    Mail::to($user->email)->send(
        new ForgotPasswordOtpMail(
            (string) $otp,
            $user->full_name
        )
    );

    Log::info("OTP reset dikirim ke {$user->email}");

    return response()->json([
        'success' => true,
        'message' => 'Kode OTP berhasil dikirim ke email'
    ]);
}


   public function reset(Request $r)
{
    $r->validate([
        'email'    => 'required|email',
        'otp'      => 'required',
        'password' => [
            'required',
            'min:8',
            'regex:/[0-9]/',
            'regex:/[^a-zA-Z0-9]/'
        ]
    ]);

    $record = DB::table('password_resets')
        ->where('email', $r->email)
        ->first();

    if (!$record) {
        return response()->json([
            'message' => 'OTP tidak ditemukan'
        ], 403);
    }

    // ⏱️ cek expired
    if (now()->greaterThan($record->expires_at)) {
        DB::table('password_resets')
            ->where('email', $r->email)
            ->delete();

        return response()->json([
            'message' => 'OTP kadaluarsa'
        ], 403);
    }

    // 🔐 validasi OTP
    if (!Hash::check($r->otp, $record->otp)) {
        return response()->json([
            'message' => 'OTP tidak valid'
        ], 403);
    }

    // 🔑 update password
    $user = ModelUser::where('email', $r->email)->firstOrFail();
    $user->password = Hash::make($r->password);
    $user->save();

    // 🧹 hapus OTP
    DB::table('password_resets')
        ->where('email', $r->email)
        ->delete();

    return response()->json([
        'success' => true,
        'message' => 'Password berhasil diubah'
    ]);
}

}
