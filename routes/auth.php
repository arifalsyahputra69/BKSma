<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // PERBAIKAN KEAMANAN (AUDIT, 2 Agustus 2026): route register bawaan Breeze
    // DIHAPUS. Di SIM BK ini semua akun (Siswa, Guru BK, Wali Kelas, Guru
    // Mapel, Kepsek) HANYA boleh dibuat oleh TU/Admin lewat menu Kelola
    // Pengguna -- tidak ada pendaftaran mandiri.
    //
    // Sebelumnya GET /register memang sudah error (view auth.register tidak
    // ada), TAPI POST /register masih hidup dan berfungsi penuh: siapa pun di
    // internet bisa membuat akun di sistem BK sekolah hanya dengan mengirim
    // form ke alamat itu. Akun hasilnya tidak punya role sehingga tidak bisa
    // masuk ke halaman mana pun, tapi tetap: mengotori tabel users, bisa
    // dipakai memborong alamat email (kolom email unique), dan merupakan
    // permukaan serangan yang tidak dibutuhkan sama sekali.

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Catatan: fitur lupa password bawaan Laravel (via email) sengaja tidak
    // dipakai. SIM BK memakai alur custom berbasis NISN/NIP, lihat
    // routes/web.php -> custom.password.request / custom.password.reset.
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});