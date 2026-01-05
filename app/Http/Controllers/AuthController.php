<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ModelUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
   public function register(Request $r)
{
    $v = Validator::make($r->all(), [
        'username'        => 'required|unique:user',
        'password'        => 'required|min:6',
        'full_name'       => 'required|string',
        'alamat'          => 'nullable|string',
        'tanggal_lahir'   => 'nullable|date',
        'agama'           => 'nullable|string',
        'tanggal_gabung'  => 'required|date',
        'no_hp'           => 'nullable|string',
        'email'           => 'nullable|email|unique:user,email'
    ]);

    if ($v->fails()) {
        return response()->json(['errors' => $v->errors()], 422);
    }

    // ✅ Generate no_anggota otomatis berdasarkan tanggal_gabung + urut pendaftar
    $tanggalGabung = \Carbon\Carbon::parse($r->tanggal_gabung)->format('Ymd');
    $countToday = \App\Models\ModelUser::whereDate('tanggal_gabung', $r->tanggal_gabung)->count();
    $urut = str_pad($countToday + 1, 4, '0', STR_PAD_LEFT); // 0001, 0002, dst
    $noAnggota = $tanggalGabung . $urut;

    // ✅ Buat user baru
    $user = ModelUser::create([
        'username'        => $r->username,
        'password'        => Hash::make($r->password),
        'full_name'       => $r->full_name,
        'alamat'          => $r->alamat,
        'tanggal_lahir'   => $r->tanggal_lahir,
        'agama'           => $r->agama,
        'tanggal_gabung'  => $r->tanggal_gabung,
        'no_hp'           => $r->no_hp,
        'email'           => $r->email,
        'no_anggota'      => $noAnggota
    ]);

    // ✅ Buat simpanan default
    $user->savings()->createMany([
        ['type'=>'Simpanan Pokok','balance'=>0],
        ['type'=>'Simpanan Wajib','balance'=>0],
        ['type'=>'Simpanan Sukarela','balance'=>0]
    ]);

    return response()->json(['user' => $user], 201);
}

public function login(Request $r){
    $r->validate(['username'=>'required','password'=>'required']);
    $user = ModelUser::where('username',$r->username)->first();
    if(!$user || !Hash::check($r->password,$user->password)) 
        return response()->json(['message'=>'Invalid credentials'],401);
    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json(['token'=>$token,'user'=>$user]);
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
