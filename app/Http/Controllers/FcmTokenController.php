<?php

namespace App\Http\Controllers;

// LETAKKAN DI: app/Http/Controllers/FcmTokenController.php (GANTI file lama)
//
// PERBAIKAN: warning "Undefined method 'update'" dari intelephense/editor
// (BUKAN error PHP sungguhan -- kodenya tetap jalan normal, karena Auth::user()
// pasti mengembalikan instance App\Models\User yang punya method update() dari
// Eloquent). Tapi supaya editor tidak lagi menganggap ini error dan supaya IDE
// bisa autocomplete dengan benar, di sini kita beri tahu tipe sebenarnya lewat
// docblock @var -- pola yang sama seperti yang sudah dipakai di
// UserProfileController.php pada project ini.

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FcmTokenController extends Controller
{
    public function store(Request $request)
    {
        // PERBAIKAN KEAMANAN (30 Juli 2026): dulu route ini tidak dijaga
        // middleware 'auth' (lihat routes/web.php), jadi Auth::user() bisa
        // saja null dan ->update() di bawah bikin fatal error. Middleware-nya
        // sudah diperbaiki, tapi null-check ini tetap dipertahankan sebagai
        // lapisan pengaman kedua supaya endpoint ini tidak pernah crash kalau
        // suatu saat route-nya berubah lagi / diakses lewat cara lain.
        if (! Auth::check()) {
            return response()->json(['status' => 'unauthenticated'], 401);
        }

        $request->validate([
            'token' => 'required|string',
        ]);

        /** @var User $user */
        $user = Auth::user();
        $user->update([
            'fcm_token' => $request->token,
        ]);

        return response()->json(['status' => 'ok']);
    }
}