<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Siswa; // Mengambil model Siswa
use App\Services\FonnteService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

// PERBAIKAN KEAMANAN (30 Juli 2026): sebelumnya alur ini mengizinkan reset
// password HANYA dengan mengetik NISN (siswa) atau NIP (staf) -- tanpa
// verifikasi apa pun. NISN/NIP bukan data rahasia (tertera di kartu pelajar,
// rapor, daftar kelas, dsb), jadi siapa pun yang tahu NISN/NIP orang lain
// bisa mengambil alih akun mereka sepenuhnya. Sekarang ditambahkan verifikasi
// kode OTP 6 digit.
//
// PERUBAHAN (30 Juli 2026): OTP sebelumnya dikirim ke WA ORANG TUA
// (siswas.no_wa_ortu) yang sudah diverifikasi Guru BK. Sekarang dikirim
// LANGSUNG ke HP siswa sendiri (siswas.no_hp_siswa), karena siswa sendiri
// yang mengetik kode OTP-nya di sistem -- jadi tidak perlu lagi lewat WA
// orang tua. Catatan: nomor ini diisi mandiri oleh siswa lewat halaman
// profil (UserProfileController::updateInfo) dan TIDAK melalui proses
// verifikasi berjenjang seperti no_wa_ortu, jadi pastikan siswa selalu
// menjaga & memperbarui nomor HP-nya sendiri di profil supaya OTP
// reset password tetap bisa diterima.
//
// Untuk staf (NIP), sistem ini BELUM punya nomor kontak sama sekali -- jadi
// self-service reset untuk staf DIHAPUS SEMENTARA dan diarahkan untuk
// menghubungi TU/Admin (yang memang sudah punya fitur reset password manual
// di TU\UserController::resetPassword).
class CustomPasswordController extends Controller
{
    public function __construct(protected FonnteService $fonnteService)
    {
    }

    // Masa berlaku kode OTP (menit)
    private const OTP_TTL_MENIT = 10;

    // Maksimal percobaan salah input OTP sebelum sesi direset
    private const OTP_MAX_PERCOBAAN = 5;

    // 1. Menampilkan form input NISN/NIP
    public function showNomorIndukForm()
    {
        return view('auth.lupa-password');
    }

    // 2. Mengecek apakah NISN/NIP ada di database, lalu kirim OTP (khusus siswa)
    public function cekNomorInduk(Request $request)
    {
        $request->validate([
            'nomor_induk' => 'required',
        ]);

        $nomor_induk = $request->nomor_induk;

        // --- SKENARIO 1: Cek di tabel Siswa (berdasarkan NISN) ---
        $siswa = Siswa::where('nisn', $nomor_induk)->first();

        if ($siswa) {
            // OTP dikirim ke HP siswa sendiri (bukan lagi HP orang tua).
            // Wajib sudah mengisi nomor HP-nya sendiri di halaman profil,
            // kalau belum tidak ada cara aman untuk mengirimkan kode OTP.
            if (blank($siswa->no_hp_siswa)) {
                return back()->with(
                    'error',
                    'Nomor HP kamu belum terdaftar di sistem, sehingga kode verifikasi tidak bisa dikirim. Silakan lengkapi nomor HP di halaman Profil terlebih dahulu, atau hubungi Guru BK/TU/Admin untuk reset password secara manual.'
                );
            }

            $kodeOtp = (string) random_int(100000, 999999);

            Session::put('reset_user_id', $siswa->user_id);
            Session::put('reset_otp_hash', Hash::make($kodeOtp));
            Session::put('reset_otp_expires_at', now()->addMinutes(self::OTP_TTL_MENIT));
            Session::put('reset_otp_percobaan', 0);
            Session::put('reset_otp_verified', false);

            $terkirim = $this->fonnteService->kirimOtpResetPassword(
                $siswa->no_hp_siswa,
                optional($siswa->user)->name ?? 'siswa',
                $kodeOtp
            );

            if (! $terkirim) {
                // Gagal kirim WA (mis. gangguan Fonnte) -- jangan lanjutkan
                // ke tahap OTP karena siswa tidak akan pernah menerima
                // kodenya. Bersihkan sesi supaya tidak menggantung.
                Session::forget(['reset_user_id', 'reset_otp_hash', 'reset_otp_expires_at', 'reset_otp_percobaan', 'reset_otp_verified']);

                return back()->with(
                    'error',
                    'Gagal mengirim kode verifikasi ke HP kamu. Silakan coba lagi beberapa saat lagi, atau hubungi TU/Admin.'
                );
            }

            return redirect()->route('custom.password.otp');
        }

        // --- SKENARIO 2: NIP Guru/Staf -- self-service DIMATIKAN, tidak ada
        // kanal kontak terverifikasi untuk staf saat ini. ---
        try {
            $user = User::where('nip', $nomor_induk)->first();
            if ($user) {
                return back()->with(
                    'error',
                    'Untuk akun staf/guru, reset password wajib dilakukan oleh TU/Admin demi keamanan akun. Silakan hubungi TU/Admin sekolah.'
                );
            }
        } catch (\Exception $e) {
            // Abaikan jika terjadi error (misal kolom nip tidak ada), biarkan sistem lanjut ke bawah
        }

        // Jika tidak ketemu di tabel Siswa maupun Users
        return back()->with('error', 'Data tidak ditemukan, hubungi TU untuk bantuan.');
    }

    // 3. Menampilkan form input kode OTP yang dikirim ke HP siswa sendiri
    public function showOtpForm()
    {
        if (! Session::has('reset_user_id') || ! Session::has('reset_otp_hash')) {
            return redirect()->route('custom.password.request');
        }

        return view('auth.verifikasi-otp');
    }

    // 4. Memverifikasi kode OTP yang diinput siswa
    public function verifyOtp(Request $request)
    {
        if (! Session::has('reset_user_id') || ! Session::has('reset_otp_hash')) {
            return redirect()->route('custom.password.request');
        }

        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        // Kode kedaluwarsa -> reset total, minta siswa mulai ulang dari awal
        // supaya dapat kode yang baru & fresh.
        if (now()->greaterThan(Session::get('reset_otp_expires_at'))) {
            Session::forget(['reset_user_id', 'reset_otp_hash', 'reset_otp_expires_at', 'reset_otp_percobaan', 'reset_otp_verified']);

            return redirect()->route('custom.password.request')
                ->with('error', 'Kode verifikasi sudah kedaluwarsa. Silakan ulangi proses dari awal.');
        }

        if (! Hash::check($request->otp, Session::get('reset_otp_hash'))) {
            $percobaan = Session::increment('reset_otp_percobaan');

            if ($percobaan >= self::OTP_MAX_PERCOBAAN) {
                Session::forget(['reset_user_id', 'reset_otp_hash', 'reset_otp_expires_at', 'reset_otp_percobaan', 'reset_otp_verified']);

                return redirect()->route('custom.password.request')
                    ->with('error', 'Terlalu banyak percobaan kode yang salah. Silakan ulangi proses dari awal.');
            }

            return back()->with('error', 'Kode verifikasi salah. Silakan coba lagi.');
        }

        // OTP benar -> tandai sesi ini boleh lanjut ke halaman set password baru.
        Session::put('reset_otp_verified', true);
        Session::forget('reset_otp_hash');

        return redirect()->route('custom.password.reset');
    }

    // 5. Menampilkan form input password baru
    public function showResetForm()
    {
        // Pastikan siswa sudah melewati tahap cek nomor induk DAN verifikasi OTP
        if (! Session::has('reset_user_id') || ! Session::get('reset_otp_verified')) {
            return redirect()->route('custom.password.request');
        }

        return view('auth.reset-password');
    }

    // 6. Menyimpan password baru
    public function updatePassword(Request $request)
    {
        if (! Session::has('reset_user_id') || ! Session::get('reset_otp_verified')) {
            return redirect()->route('custom.password.request');
        }

        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $userId = Session::get('reset_user_id');
        $user = User::find($userId);

        if ($user) {
            $user->password = Hash::make($request->password);
            $user->save();

            // Hapus seluruh sesi reset (termasuk jejak OTP) setelah berhasil
            Session::forget(['reset_user_id', 'reset_otp_hash', 'reset_otp_expires_at', 'reset_otp_percobaan', 'reset_otp_verified']);

            return redirect()->route('login')->with('success', 'Password berhasil diperbarui. Silakan login dengan password baru.');
        }

        return redirect()->route('login')->with('error', 'Terjadi kesalahan. Silakan coba lagi.');
    }
}
