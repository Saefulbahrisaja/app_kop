<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ModelUser as User;

class AnggotaController extends Controller
{
    public function index(Request $r)
    {
        $user = $r->user();

        /*
        |--------------------------------------------------------------------------
        | HANYA PENGURUS
        |--------------------------------------------------------------------------
        */
        if (!in_array($user->role, ['BENDAHARA', 'KETUA'])) {

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | DATA ANGGOTA
        |--------------------------------------------------------------------------
        */
       $anggota = User::select(
        'id',
        'username',
        'full_name',
        'alamat',
        'tanggal_lahir',
        'agama',
        'tanggal_gabung',
        'no_hp',
        'email',
        'no_anggota',
        'role',
        'status',
        'created_at'
    )
    ->whereNotIn('role', ['BENDAHARA', 'KETUA']) // ✅ FIX
    ->orderByDesc('created_at')
    ->get();

        return response()->json([
            'success' => true,
            'total'   => $anggota->count(),
            'data'    => $anggota
        ]);
    }

    public function updateStatus(Request $r, $id)
{
    $user = $r->user();

    /*
    |--------------------------------------------------------------------------
    | HANYA PENGURUS
    |--------------------------------------------------------------------------
    */
    if (!in_array($user->role, ['BENDAHARA', 'KETUA'])) {

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 403);
    }

    $r->validate([
        'status' => 'required|in:active,rejected,pending'
    ]);

    $anggota = User::findOrFail($id);

    /*
    |--------------------------------------------------------------------------
    | TIDAK BOLEH NONAKTIFKAN PENGURUS
    |--------------------------------------------------------------------------
    */
    if ($anggota->role !== 'MEMBER') {

        return response()->json([
            'success' => false,
            'message' => 'Pengurus tidak bisa diubah'
        ], 400);
    }

    $anggota->update([
        'status' => $r->status
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Status anggota berhasil diperbarui',
        'data' => $anggota
    ]);
}
}