<?php

namespace App\Services;

// LETAKKAN DI: app/Services/FonnteService.php
//
// Pusat pengiriman pesan WhatsApp (Fase 7 - bagian Fonnte).
// Fonnte cukup dipanggil lewat HTTP biasa (Illuminate\Support\Facades\Http),
// TIDAK perlu package composer tambahan.
//
// Dipakai di: app/Http/Controllers/GuruBK/JurnalLayananController.php
// -- setiap kali Guru BK menyelesaikan/menyimpan jurnal konseling untuk
// seorang siswa, orang tua (kalau no_wa_ortu sudah terisi & is_wa_verified)
// otomatis dikirim WA ringkas.
//
// PENTING (kerahasiaan): pesan yang dikirim ke orang tua HANYA berisi info umum
// (tanggal & keperluan), TIDAK PERNAH menyertakan uraian_masalah atau
// pendekatan_teknik -- sesuai prinsip "Kepsek/orang tua tidak perlu lihat
// detail intim konseling" yang sudah ditetapkan di dokumen Kebutuhan Monitoring.

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    /**
     * Kirim pesan WhatsApp lewat Fonnte.
     *
     * @param  string $noTujuan  Nomor WA tujuan, format bebas (Fonnte otomatis
     *                            merapikan ke format 62xxx)
     * @param  string $pesan     Isi pesan
     * @return bool  true jika request terkirim & direspon sukses oleh Fonnte
     */
    public function kirim(string $noTujuan, string $pesan): bool
    {
        $token = config('services.fonnte.token');

        if (blank($token) || blank($noTujuan)) {
            // Token belum diisi / nomor kosong -> skip diam-diam, jangan
            // sampai proses simpan jurnal konseling ikut gagal gara-gara ini.
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->asForm()->post('https://api.fonnte.com/send', [
                'target' => $noTujuan,
                'message' => $pesan,
                'countryCode' => '62',
            ]);

            if ($response->failed()) {
                Log::warning('Fonnte: request gagal.', ['status' => $response->status(), 'body' => $response->body()]);

                return false;
            }

            $status = $response->json('status');
            if ($status === false) {
                Log::warning('Fonnte: dikirim tapi ditolak API.', ['body' => $response->body()]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Fonnte: exception saat mengirim WA.', ['pesan' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Notifikasi ringkas ke orang tua terkait layanan BK anaknya.
     * HANYA dikirim oleh pemanggil (lihat JurnalLayananController::notifikasiOrtu)
     * ketika Guru BK menandai "Panggil Orang Tua/Wali" ATAU tingkat pelanggaran
     * = "Berat". Sengaja TIDAK menyertakan uraian_masalah / pendekatan_teknik,
     * supaya kerahasiaan konseling tetap terjaga.
     *
     * @param  string      $alasan            'panggil_ortu' atau 'pelanggaran_berat'
     * @param  string|null $jadwalPertemuan   Tanggal & jam pertemuan yang SUDAH diformat rapi
     *                                         (mis. "Senin, 21 Juli 2026 pukul 09:00 WIB"),
     *                                         supaya orang tua tahu persis kapan harus datang
     *                                         dan tidak kebingungan. Boleh null kalau Guru BK
     *                                         belum menentukan jadwal saat menyimpan jurnal --
     *                                         dalam kasus itu pesan akan meminta orang tua
     *                                         menghubungi sekolah untuk menentukan jadwalnya.
     */
    public function notifikasiOrtuSetelahKonseling(string $noTujuan, string $namaSiswa, string $namaOrtu, string $tanggal, string $keperluan, string $alasan = 'panggil_ortu', ?string $jadwalPertemuan = null): bool
    {
        $pembuka = $alasan === 'pelanggaran_berat'
            ? "Kami informasikan bahwa ananda *{$namaSiswa}* tercatat melakukan pelanggaran dengan "
                . "tingkat *Berat* dan telah mendapatkan layanan Bimbingan Konseling di sekolah."
            : "Sehubungan dengan layanan Bimbingan Konseling terhadap ananda *{$namaSiswa}*, "
                . "kami mengharapkan kehadiran Bapak/Ibu di sekolah untuk berdiskusi lebih lanjut.";

        // PERBAIKAN: sebelumnya pesan hanya menyuruh orang tua "datang ke sekolah"
        // tanpa kejelasan kapan, sehingga orang tua bingung. Sekarang jadwal
        // pertemuan (jika sudah diisi Guru BK) disebutkan secara eksplisit.
        $ajakanPertemuan = filled($jadwalPertemuan)
            ? "Kami mohon kehadiran Bapak/Ibu pada *{$jadwalPertemuan}* di sekolah untuk "
                . "berdiskusi langsung dengan Guru BK terkait hal ini."
            : "Mohon Bapak/Ibu segera menghubungi Guru BK atau pihak sekolah untuk "
                . "menentukan jadwal pertemuan terkait hal ini.";

        $pesan = "Yth. Bpk/Ibu {$namaOrtu},\n\n"
            . $pembuka . "\n\n"
            . "Sesi layanan dilaksanakan pada {$tanggal} terkait: {$keperluan}.\n\n"
            . $ajakanPertemuan . "\n\n"
            . "Pesan ini dikirim otomatis oleh sistem SIM BK, mohon tidak membalas pesan ini.";

        return $this->kirim($noTujuan, $pesan);
    }

    /**
     * PERBAIKAN KEAMANAN (30 Juli 2026): kirim kode OTP sebagai langkah
     * verifikasi identitas sebelum siswa diizinkan mengatur ulang password
     * lewat alur "Lupa Password" (CustomPasswordController). Sebelumnya alur
     * itu langsung mengizinkan reset password hanya dengan mengetahui NISN --
     * padahal NISN tidak rahasia (tertera di kartu pelajar/rapor), sehingga
     * siapa pun yang tahu NISN siswa lain bisa mengambil alih akunnya.
     *
     * PERUBAHAN (30 Juli 2026): OTP sekarang dikirim LANGSUNG ke HP siswa
     * sendiri (siswas.no_hp_siswa) -- bukan lagi ke WA orang tua -- karena
     * siswa sendiri yang mengetikkan kodenya di sistem. Pesan juga diubah
     * supaya bicara langsung ke siswa ("kamu"), bukan ke orang tua.
     */
    public function kirimOtpResetPassword(string $noTujuan, string $namaSiswa, string $kodeOtp): bool
    {
        $pesan = "Halo *{$namaSiswa}*, kode verifikasi untuk reset password akun SIM BK kamu adalah:\n\n"
            . "*{$kodeOtp}*\n\n"
            . "Kode ini berlaku selama 10 menit. JANGAN berikan kode ini ke siapa pun, termasuk teman kamu sendiri.\n\n"
            . "Jika kamu tidak merasa meminta reset password, abaikan pesan ini.\n\n"
            . "Pesan ini dikirim otomatis oleh sistem SIM BK, mohon tidak membalas pesan ini.";

        return $this->kirim($noTujuan, $pesan);
    }

    /**
     * Notifikasi kehadiran ke orang tua, dikirim untuk setiap status absensi
     * (Hadir/Izin/Sakit/Alpha) per sesi/mata pelajaran, supaya orang tua bisa
     * memantau kehadiran anaknya di sekolah secara real-time.
     */
    public function notifikasiOrtuAbsensi(string $noTujuan, string $namaSiswa, string $namaOrtu, string $status, string $mapel, string $jamKe, string $tanggal, ?string $keterangan = null): bool
    {
        $statusText = match ($status) {
            'Hadir' => 'HADIR',
            'Izin' => 'IZIN',
            'Sakit' => 'SAKIT',
            'Alpha' => 'TIDAK HADIR (Alpha)',
            default => strtoupper($status),
        };

        $pesan = "Yth. Bpk/Ibu {$namaOrtu},\n\n"
            . "Kami informasikan bahwa ananda *{$namaSiswa}* tercatat *{$statusText}* "
            . "pada mata pelajaran {$mapel} ({$jamKe}) tanggal {$tanggal}.";

        if (filled($keterangan)) {
            $pesan .= "\nKeterangan: {$keterangan}";
        }

        $pesan .= "\n\nJika ada pertanyaan terkait kehadiran ini, silakan hubungi wali kelas / sekolah.\n\n"
            . "Pesan ini dikirim otomatis oleh sistem SIM BK, mohon tidak membalas pesan ini.";

        return $this->kirim($noTujuan, $pesan);
    }
}