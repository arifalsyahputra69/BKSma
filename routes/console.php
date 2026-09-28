<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| TUGAS TERJADWAL (SCHEDULER)
|--------------------------------------------------------------------------
|
| PERBAIKAN (AUDIT, 2 Agustus 2026).
|
| Semua yang di bawah ini dijalankan oleh SATU baris cron di cPanel:
|
|   * * * * * cd /home/USERNAME/sim-bk && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
|
| Laravel sendiri yang mengatur mana yang perlu jalan menit ini dan mana
| yang belum waktunya. Jadi Anda cukup memasang satu cron, bukan satu per
| tugas. Langkah lengkapnya ada di PANDUAN-DEPLOY-CPANEL.md.
|
| Kalau baris cron ini TIDAK dipasang, dua hal berhenti bekerja diam-diam
| (tanpa pesan error apa pun): penandaan Alpha otomatis, dan backup harian.
|
|--------------------------------------------------------------------------
| PENTING -- kenapa memakai Schedule::call(), bukan Schedule::command()
|--------------------------------------------------------------------------
|
| PERBAIKAN (3 Agustus 2026). Versi pertama berkas ini memakai
| Schedule::command('nama-perintah'). Cara itu membuat Laravel menjalankan
| setiap tugas sebagai PROSES TERPISAH lewat Symfony Process -- yang
| bergantung pada fungsi PHP proc_open().
|
| Rumahweb (dan banyak shared hosting lain) MEMBLOKIR proc_open demi
| keamanan bersama. Akibatnya di server, setiap menit muncul di log:
|
|   "The Process class relies on proc_open, which is not available
|    on your PHP installation."
|
| dan TIDAK SATU PUN tugas terjadwal pernah benar-benar dijalankan --
| penandaan Alpha, backup harian, maupun pemeriksaan kesehatan.
|
| Schedule::call() menjalankan perintahnya LANGSUNG di dalam proses PHP
| yang sedang berjalan (lewat Artisan::call), tanpa membuat proses baru,
| jadi tidak butuh proc_open sama sekali. Fitur penjadwalannya sendiri
| (everyMinute, dailyAt, withoutOverlapping) tetap berfungsi normal.
|
| Konsekuensi kecil: kalau satu tugas melempar exception, tugas lain di
| menit yang sama bisa ikut terhenti. Karena itu tiap pemanggilan dibungkus
| try/catch di bawah -- kegagalan satu tugas tidak menjatuhkan yang lain,
| dan tetap tercatat di log.
|
*/

/**
 * Pembungkus aman untuk memanggil perintah Artisan dari scheduler.
 * Lihat penjelasan panjang di atas soal proc_open.
 *
 * PERBAIKAN (5 Agustus 2026). Versi sebelumnya hanya menangkap exception.
 * Itu belum cukup: perintah seperti backup:database TIDAK melempar exception
 * saat gagal -- ia mengembalikan KODE KELUAR (self::FAILURE) lalu berhenti
 * dengan tenang. Contohnya kalau hasil dump kurang dari 1 KB, file dihapus
 * dan command mengembalikan FAILURE.
 *
 * Karena Artisan::call() hanya mengembalikan kode itu tanpa memeriksanya,
 * scheduler tetap mencatat "DONE" di output cron. Artinya backup bisa gagal
 * tiap malam selama berbulan-bulan sementara log cron terlihat sehat -- persis
 * jenis kegagalan diam yang jadi alasan seluruh audit ini dilakukan.
 *
 * Sekarang kode keluarnya diperiksa, dan output perintahnya ikut dicatat
 * supaya penyebabnya kelihatan di storage/logs, bukan hilang begitu saja.
 */
$jalankanPerintah = function (string $perintah, array $parameter = []) {
    try {
        $kode = Artisan::call($perintah, $parameter);

        if ($kode !== 0) {
            Log::error("Tugas terjadwal '{$perintah}' selesai dengan kode gagal {$kode}.", [
                'keluaran' => trim(Artisan::output()),
            ]);
        }
    } catch (\Throwable $e) {
        Log::error("Tugas terjadwal '{$perintah}' gagal.", [
            'pesan' => $e->getMessage(),
        ]);
    }
};

// ---------------------------------------------------------------------------
// 1. Tutup sesi absensi yang sudah lewat batas waktu + tandai Alpha
// ---------------------------------------------------------------------------
// Menggantikan AutoAlphaAbsensiJob yang dulu butuh `queue:work` permanen --
// tidak bisa dijalankan di shared hosting. Command ini idempoten dan sangat
// ringan kalau tidak ada sesi yang perlu diproses (hanya satu SELECT).
//
// withoutOverlapping(): kalau eksekusi menit sebelumnya belum selesai
// (misalnya karena pengiriman WA lambat), menit ini dilewati -- bukan
// dijalankan berbarengan.
Schedule::call(fn () => $jalankanPerintah('absensi:tutup-kedaluwarsa'))
    ->name('absensi-tutup-kedaluwarsa')
    ->everyMinute()
    ->withoutOverlapping(10);

// ---------------------------------------------------------------------------
// 2. Backup database harian
// ---------------------------------------------------------------------------
// Dijalankan dini hari saat sekolah sepi. Catatan konseling tidak bisa
// diinput ulang kalau hilang, jadi ini bukan opsional.
Schedule::call(fn () => $jalankanPerintah('backup:database'))
    ->name('backup-database-harian')
    ->dailyAt('01:30')
    ->withoutOverlapping();

// ---------------------------------------------------------------------------
// 3. Pemeriksaan kesehatan sistem
// ---------------------------------------------------------------------------
// Ini jaring pengaman terhadap "kerusakan diam": fitur yang berhenti bekerja
// tanpa menimbulkan error yang terlihat. Contoh nyatanya ada di project ini --
// menu Verifikasi Data Ortu error sejak 5 Juli dan baru ketahuan saat audit.
//
// Command ini HANYA mengirim WA ke admin kalau menemukan masalah, jadi tidak
// akan jadi pesan rutin yang lama-lama diabaikan. Isi ADMIN_WA_NOTIFIKASI di
// .env supaya laporannya terkirim.
Schedule::call(fn () => $jalankanPerintah('sistem:cek-kesehatan'))
    ->name('cek-kesehatan-sistem')
    ->dailyAt('06:30')
    ->withoutOverlapping();

// ---------------------------------------------------------------------------
// 4. Pembersihan log lama
// ---------------------------------------------------------------------------
// Channel log sudah diubah ke 'daily' (lihat .env.production.example), yang
// otomatis merotasi file per hari dan menyimpan 14 hari terakhir. Baris ini
// membersihkan sisa file laravel.log tunggal yang sudah membengkak dari masa
// sebelum perubahan itu, supaya tidak terus memakan kuota disk hosting.
Schedule::call(function () {
    $lama = storage_path('logs/laravel.log');

    if (is_file($lama) && filesize($lama) > 20 * 1024 * 1024) { // > 20 MB
        file_put_contents($lama, '');
    }
})->weekly();
