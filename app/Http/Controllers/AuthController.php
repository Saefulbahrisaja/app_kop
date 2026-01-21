<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ModelUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserVerifiedMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
{
 

public function register(Request $r)
{
    $v = Validator::make($r->all(), [
        'full_name'      => 'required|string',
        'alamat'         => 'nullable|string',
        'tanggal_lahir'  => 'nullable|date',
        'agama'          => 'nullable|string',
        'no_hp'          => 'nullable|string',
        'email'          => 'required|email|unique:user,email',
        'password'       => 'required|min:6'
    ]);

    if ($v->fails()) {
        return response()->json([
            'success' => false,
            'errors'  => $v->errors()
        ], 422);
    }

    // ✅ username unik otomatis
    $username = 'user_' . Str::random(6);

    // 🔁 pastikan benar-benar unik
    while (ModelUser::where('username', $username)->exists()) {
        $username = 'user_' . Str::random(6);
    }

    $user = ModelUser::create([
        'username'       => $username,
        'password'       => Hash::make($r->password),
        'full_name'      => $r->full_name,
        'alamat'         => $r->alamat,
        'tanggal_lahir'  => $r->tanggal_lahir,
        'agama'          => $r->agama,
        'no_hp'          => $r->no_hp,
        'email'          => $r->email,
        'status'         => 'PENDING'
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Registrasi berhasil. Menunggu verifikasi admin.',
        'user_id' => $user->id
    ], 201);
}



public function verifyUser(Request $r, $userId)
{
    $v = Validator::make($r->all(), [
        'tanggal_gabung'    => 'required|date',
        'simpanan_pokok'    => 'required|numeric|min:0',
        'simpanan_wajib'    => 'required|numeric|min:0',
        'simpanan_manasuka' => 'required|numeric|min:0',
    ]);

    if ($v->fails()) {
        return response()->json(['errors' => $v->errors()], 422);
    }

    return DB::transaction(function () use ($r, $userId) {

        /** 🔒 Lock user */
        $user = ModelUser::lockForUpdate()->findOrFail($userId);

        if ($user->status === 'active') {
            return response()->json([
                'message' => 'User sudah diverifikasi'
            ], 400);
        }

        /* ===============================
            * GENERATE NO ANGGOTA (YYYYMMXXXX)
            * =============================== */
            $tanggalGabung = Carbon::parse($r->tanggal_gabung);

            // prefix TAHUN + BULAN
            $prefix = $tanggalGabung->format('Ym'); // contoh: 202601

            // ambil no anggota terakhir di bulan tsb
            $lastUser = ModelUser::where('no_anggota', 'like', $prefix.'%')
                ->orderByDesc('no_anggota')
                ->lockForUpdate()
                ->first();

            if ($lastUser) {
                // ambil 4 digit terakhir
                $lastNumber = (int) substr($lastUser->no_anggota, -4);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            // safety loop (anti race condition / data kotor)
            do {
                $noAnggota = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                $exists = ModelUser::where('username', $noAnggota)
                    ->orWhere('no_anggota', $noAnggota)
                    ->exists();
                $nextNumber++;
            } while ($exists);

        /* ===============================
         * UPDATE USER
         * =============================== */
        $user->update([
            'tanggal_gabung' => $tanggalGabung->format('Y-m-d'),
            'no_anggota'     => $noAnggota,
            'username'       => $noAnggota, // ✅ sekarang 100% unik
            'status'         => 'active',
            'verified_at'    => now()
        ]);

        /* ===============================
         * SIMPANAN (INPUT ADMIN)
         * =============================== */
        $period = $tanggalGabung->format('Y-m-d');
        $now = now();

        $user->savings()->createMany([
            [
                'type' => 'pokok',
                'amount' => $r->simpanan_pokok,
                'period' => $period,
                'paid_at' => $period,
                'approved_at' => $now
            ],
            [
                'type' => 'wajib',
                'amount' => $r->simpanan_wajib,
                'period' => $period,
                'paid_at' =>$period,
                'approved_at' => $now
            ],
            [
                'type' => 'manasuka',
                'amount' => $r->simpanan_manasuka,
                'period' => $period,
                'paid_at' => $period,
                'approved_at' => $now
            ]
        ]);

        /* ===============================
         * EMAIL
         * =============================== */
        Mail::to($user->email)->send(
            new UserVerifiedMail($user)
        );

        return response()->json([
            'success'    => true,
            'message'    => 'User diverifikasi & username dikirim ke email',
            'no_anggota' => $noAnggota
        ]);
    });
}



public function login(Request $r)
{
    $r->validate([
        'username' => 'required',
        'password' => 'required'
    ]);

    $user = ModelUser::where('username', $r->username)->first();

    // 1️⃣ Username tidak ditemukan
    if (!$user) {
        return response()->json([
            'message' => 'Username atau password salah'
        ], 401);
    }

    // 2️⃣ Password salah
    if (!Hash::check($r->password, $user->password)) {
        return response()->json([
            'message' => 'Username atau password salah'
        ], 401);
    }

    // 3️⃣ User belum diverifikasi admin
    if ($user->status !== 'active') {
        return response()->json([
            'message' => 'Akun belum diverifikasi admin'
        ], 403);
    }

    // 4️⃣ Login berhasil
    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'success' => true,
        'token'   => $token,
        'user'    => $user
    ]);
}



public function changePassword(Request $r){
    $r->validate(['old_password'=>'required','new_password'=>'required|min:6']);
    $user = $r->user();
    if(!Hash::check($r->old_password,$user->password)) 
        return response()->json(['message'=>'Wrong current password'],403);
    $user->password = Hash::make($r->new_password);
    $user->save();
    return response()->json(['message'=>'Password changed']);
}
}
