<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Tambahkan 3 baris alias ini untuk Spatie
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'user.status' => \App\Http\Middleware\CheckUserStatus::class,
        ]);

        // PERBAIKAN BUG (30 Juli 2026): middleware TrimStrings bawaan Laravel
        // otomatis memotong spasi di awal/akhir SEMUA input teks, KECUALI
        // field yang namanya persis 'password', 'password_confirmation',
        // 'current_password'. Form "Ubah Password" di halaman profil sendiri
        // (UserProfileController::updatePassword) pakai nama field custom
        // (password_lama, password_baru, password_baru_confirmation) yang
        // TIDAK ada di daftar pengecualian bawaan itu -- akibatnya kalau ada
        // spasi nyelip di ujung password baru (mis. dari autocorrect HP),
        // spasinya terpotong diam-diam saat disimpan, padahal form Login
        // (field 'password', SUDAH dikecualikan) tidak memotong spasi.
        // Hasilnya: password baru yang baru saja "berhasil" diganti jadi
        // tidak pernah cocok lagi saat dipakai login. Baris ini menambahkan
        // nama-nama field custom tsb ke daftar pengecualian supaya perlakuan
        // spasinya konsisten dengan form Login.
        $middleware->trimStrings(except: [
            'password_lama',
            'password_baru',
            'password_baru_confirmation',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // PERBAIKAN (AUDIT, 2 Agustus 2026): tambahkan konteks ke setiap error
        // yang tercatat di log.
        //
        // Sebelumnya log hanya memuat pesan & stack trace. Saat audit ditemukan
        // baris seperti "Call to undefined method ...verifikasiOrtuIndex()"
        // tanpa keterangan HALAMAN MANA yang dibuka dan SIAPA yang membukanya,
        // sehingga sulit ditelusuri ulang. Empat baris di bawah membuat setiap
        // error mencantumkan URL, method, user, dan role -- cukup untuk
        // memperbaiki tanpa harus menebak-nebak.
        // Seluruhnya dibungkus try/catch: pengambilan role menyentuh database,
        // dan kalau errornya justru berasal dari database yang tumbang, kode
        // ini tidak boleh ikut melempar exception baru di dalam penanganan
        // exception (errornya malah jadi tidak tercatat sama sekali).
        $exceptions->context(function () {
            try {
                $user = auth()->user();

                return [
                    'url' => request()->fullUrl(),
                    'method' => request()->method(),
                    'user_id' => $user?->id,
                    'role' => $user ? $user->getRoleNames()->implode(', ') : null,
                ];
            } catch (\Throwable $e) {
                return [];
            }
        });
    })->create();