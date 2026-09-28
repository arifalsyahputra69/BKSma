<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Siswa;
use Illuminate\Support\Facades\Auth;

/**
 * PENJAGA KONFIRMASI KELAS
 *
 * PERUBAHAN (5 Agustus 2026) -- dua hal yang berbeda dari versi lama:
 *
 * 1. Siswa yang BELUM menjawab tidak lagi dilempar ke halaman konfirmasi
 *    tersendiri. Pertanyaannya sekarang muncul sebagai popup di dashboard
 *    (lihat resources/views/siswa/dashboard/index.blade.php), jadi middleware
 *    ini cukup mengarahkan ke dashboard dan popup-nya yang menahan siswa.
 *
 * 2. Siswa yang sudah melapor "kelas berubah" TIDAK langsung dikunci.
 *    Sebelumnya begitu menekan tombol itu akunnya mati total -- padahal
 *    yang salah adalah data sekolah, bukan siswanya, dan dia jadi kehilangan
 *    akses chatbot maupun konseling karena kesalahan administratif.
 *    Sekarang diberi masa tenggang (lihat Siswa::TENGGANG_PERBAIKAN_KELAS_HARI);
 *    penguncian baru berlaku kalau TU belum juga memperbaiki sampai lewat
 *    batas itu.
 */
class CheckClassConfirmation
{
    public function handle(Request $request, Closure $next)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        // Penjagaan ini hanya berlaku untuk Siswa.
        if (! $user || ! $user->hasRole('Siswa')) {
            return $next($request);
        }

        $siswa = Siswa::where('user_id', $user->id)->first();
        $routeName = $request->route()?->getName();

        // Rute yang selalu boleh diakses dalam keadaan apa pun: proses simpan
        // jawaban konfirmasi dan logout. Tanpa ini siswa bisa terjebak --
        // dilempar terus ke satu halaman tanpa bisa mengirim jawaban maupun
        // keluar dari sistem.
        $selaluBoleh = ['siswa.konfirmasi.index', 'siswa.konfirmasi.store', 'logout'];

        if (in_array($routeName, $selaluBoleh, true)) {
            return $next($request);
        }

        // KONDISI 1 — belum menjawab sama sekali (status masih NULL).
        // Diarahkan ke dashboard; di sana popup konfirmasi akan menutupi
        // layar dan tidak bisa ditutup sampai siswa menjawab.
        if (! $siswa || is_null($siswa->status_konfirmasi_kelas)) {
            return $routeName === 'siswa.dashboard'
                ? $next($request)
                : redirect()->route('siswa.dashboard');
        }

        // KONDISI 2 — siswa sudah melapor bahwa kelasnya berubah.
        if ($siswa->status_konfirmasi_kelas === false) {

            // Masa tenggang habis: TU tidak kunjung memperbaiki, akun dikunci.
            if ($siswa->tenggangKelasHabis()) {
                return $routeName === 'siswa.dibatasi'
                    ? $next($request)
                    : redirect()->route('siswa.dibatasi');
            }

            // Masih dalam masa tenggang: akses normal. Dashboard menampilkan
            // banner berisi sisa waktu supaya siswa tahu ini belum selesai.
            if ($routeName === 'siswa.dibatasi') {
                return redirect()->route('siswa.dashboard');
            }

            return $next($request);
        }

        // KONDISI 3 — sudah konfirmasi "kelas sama", akses penuh.
        // Halaman "dibatasi" tidak relevan lagi baginya.
        if ($routeName === 'siswa.dibatasi') {
            return redirect()->route('siswa.dashboard');
        }

        return $next($request);
    }
}
