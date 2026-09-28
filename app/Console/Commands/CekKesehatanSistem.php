<?php

namespace App\Console\Commands;

use App\Services\FonnteService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * PERBAIKAN (AUDIT, 2 Agustus 2026).
 *
 * Latar belakang: saat audit ditemukan menu "Verifikasi Data Ortu" milik Guru
 * BK sudah error 500 SEJAK 5 JULI dan tidak pernah diperbaiki. Errornya
 * tercatat rapi di storage/logs/laravel.log -- tapi tidak ada yang membaca
 * log itu, jadi kerusakannya berjalan berminggu-minggu tanpa disadari.
 *
 * Itu pola kegagalan paling berbahaya untuk aplikasi sekolah: bukan error yang
 * kelihatan, tapi yang DIAM. Command ini memeriksa hal-hal yang kalau rusak
 * tidak akan menimbulkan keluhan langsung dari pengguna:
 *
 *   1. Apakah cron/scheduler benar-benar jalan?
 *   2. Apakah backup harian berhasil & masih baru?
 *   3. Berapa banyak error baru sejak kemarin?
 *   4. Apakah ada sesi absensi menggantung (Alpha tidak diproses)?
 *   5. Apakah masih ada job menumpuk di tabel jobs (sisa sistem lama)?
 *   6. Apakah APP_DEBUG tidak sengaja menyala di server?
 *
 * Hasilnya dicetak ke layar, dan (kalau ADMIN_WA_NOTIFIKASI diisi di .env)
 * dikirim sebagai pesan WA -- TAPI hanya kalau ada yang bermasalah, supaya
 * tidak jadi pesan rutin yang lama-lama diabaikan.
 *
 * Manual:  php artisan sistem:cek-kesehatan
 * Paksa kirim WA walau semua sehat:  php artisan sistem:cek-kesehatan --kirim
 */
class CekKesehatanSistem extends Command
{
    protected $signature = 'sistem:cek-kesehatan
                            {--kirim : Tetap kirim WA ke admin walaupun semua kondisi normal}';

    protected $description = 'Periksa kondisi sistem (cron, backup, error, absensi menggantung) dan kabari admin bila ada masalah';

    public function __construct(protected FonnteService $fonnteService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $masalah = [];
        $catatan = [];

        $this->periksaBackup($masalah, $catatan);
        $this->periksaLogError($masalah, $catatan);
        $this->periksaAbsensiMenggantung($masalah, $catatan);
        $this->periksaAntrianJob($masalah, $catatan);
        $this->periksaKonfigurasi($masalah, $catatan);

        $this->newLine();
        $this->line('=== Kondisi Normal ===');
        foreach ($catatan as $baris) {
            $this->line('  [ok] ' . $baris);
        }

        if (empty($masalah)) {
            $this->newLine();
            $this->info('Semua pemeriksaan lolos. Sistem sehat.');
        } else {
            $this->newLine();
            $this->error('=== Perlu Perhatian ===');
            foreach ($masalah as $baris) {
                $this->line('  [!] ' . $baris);
            }
        }

        $this->newLine();

        if (! empty($masalah) || $this->option('kirim')) {
            $this->kabariAdmin($masalah, $catatan);
        }

        return empty($masalah) ? self::SUCCESS : self::FAILURE;
    }

    private function periksaBackup(array &$masalah, array &$catatan): void
    {
        $folder = storage_path('app/backup');
        $berkas = glob($folder . DIRECTORY_SEPARATOR . '*.sql*') ?: [];

        if (empty($berkas)) {
            $masalah[] = 'Belum ada file backup database sama sekali di storage/app/backup. '
                . 'Kemungkinan besar cron scheduler belum dipasang di cPanel.';

            return;
        }

        $terbaru = max(array_map('filemtime', $berkas));
        $umurJam = (int) round((time() - $terbaru) / 3600);

        if ($umurJam > 48) {
            $masalah[] = "Backup terakhir sudah {$umurJam} jam yang lalu (seharusnya harian). "
                . 'Periksa cron scheduler dan jalankan: php artisan backup:database';
        } else {
            $catatan[] = sprintf(
                'Backup terakhir %d jam lalu, total %d file tersimpan.',
                $umurJam,
                count($berkas)
            );
        }
    }

    private function periksaLogError(array &$masalah, array &$catatan): void
    {
        // Channel 'daily' membuat file laravel-YYYY-MM-DD.log
        $hariIni = storage_path('logs/laravel-' . now()->format('Y-m-d') . '.log');
        $kemarin = storage_path('logs/laravel-' . now()->subDay()->format('Y-m-d') . '.log');
        $tunggal = storage_path('logs/laravel.log');

        $jumlah = 0;
        $diperiksa = false;

        foreach ([$hariIni, $kemarin] as $berkas) {
            if (! is_file($berkas)) {
                continue;
            }

            $diperiksa = true;
            $isi = file_get_contents($berkas);

            if ($isi !== false) {
                $jumlah += substr_count($isi, '.ERROR:');
            }
        }

        if (! $diperiksa && is_file($tunggal)) {
            $ukuranMb = round(filesize($tunggal) / 1048576, 1);

            if ($ukuranMb > 20) {
                $masalah[] = "storage/logs/laravel.log sudah {$ukuranMb} MB. "
                    . "Set LOG_STACK=daily di .env supaya log dirotasi otomatis per hari.";
            }

            return;
        }

        if ($jumlah > 50) {
            $masalah[] = "Ada {$jumlah} baris ERROR dalam 2 hari terakhir. "
                . 'Buka storage/logs/laravel-' . now()->format('Y-m-d') . '.log untuk melihat penyebabnya.';
        } elseif ($jumlah > 0) {
            $catatan[] = "{$jumlah} error tercatat dalam 2 hari terakhir (masih wajar, tapi sesekali dilihat).";
        } else {
            $catatan[] = 'Tidak ada error tercatat dalam 2 hari terakhir.';
        }
    }

    private function periksaAbsensiMenggantung(array &$masalah, array &$catatan): void
    {
        if (! $this->tabelAda('sesi_absensis')) {
            return;
        }

        // Sesi yang masih 'Aktif' padahal waktu expired-nya sudah lewat lebih
        // dari 1 jam = command absensi:tutup-kedaluwarsa tidak berjalan.
        $menggantung = DB::table('sesi_absensis')
            ->where('status', 'Aktif')
            ->where('waktu_expired', '<', now()->subHour())
            ->count();

        if ($menggantung > 0) {
            $masalah[] = "Ada {$menggantung} sesi absensi yang sudah lewat batas waktu tapi belum ditutup. "
                . 'Artinya cron scheduler TIDAK jalan -- siswa yang bolos tidak tercatat Alpha. '
                . 'Ini masalah paling mendesak: periksa Cron Jobs di cPanel.';
        } else {
            $catatan[] = 'Tidak ada sesi absensi yang menggantung (penandaan Alpha berjalan normal).';
        }
    }

    private function periksaAntrianJob(array &$masalah, array &$catatan): void
    {
        if (! $this->tabelAda('jobs')) {
            return;
        }

        $menumpuk = DB::table('jobs')->count();

        if ($menumpuk > 0) {
            $catatan[] = "Ada {$menumpuk} job lama menumpuk di tabel `jobs` (sisa sistem queue sebelum audit). "
                . 'Aman dihapus: DELETE FROM jobs;';
        }
    }

    private function periksaKonfigurasi(array &$masalah, array &$catatan): void
    {
        if (config('app.debug') && app()->environment('production')) {
            $masalah[] = 'APP_DEBUG masih true di server produksi. Halaman error akan '
                . 'membocorkan isi .env (token Fonnte, password database) ke pengunjung. '
                . 'Segera ubah APP_DEBUG=false lalu jalankan: php artisan config:cache';
        } else {
            $catatan[] = 'APP_DEBUG & APP_ENV sudah sesuai.';
        }

        if (blank(config('services.fonnte.token'))) {
            $masalah[] = 'FONNTE_TOKEN kosong. Semua notifikasi WA ke orang tua dan OTP '
                . 'lupa password tidak akan terkirim.';
        }
    }

    private function tabelAda(string $tabel): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($tabel);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function kabariAdmin(array $masalah, array $catatan): void
    {
        $nomor = env('ADMIN_WA_NOTIFIKASI');

        if (blank($nomor)) {
            $this->warn('ADMIN_WA_NOTIFIKASI belum diisi di .env, jadi laporan tidak dikirim via WA.');

            return;
        }

        $judul = empty($masalah)
            ? '*SIM BK - Laporan Kondisi Sistem*'
            : '*SIM BK - ADA YANG PERLU DIPERIKSA*';

        $pesan = $judul . "\n" . now()->translatedFormat('l, d F Y H:i') . " WIB\n\n";

        if (! empty($masalah)) {
            $pesan .= "Perlu perhatian:\n";
            foreach ($masalah as $i => $baris) {
                $pesan .= ($i + 1) . '. ' . $baris . "\n\n";
            }
        }

        if (! empty($catatan)) {
            $pesan .= "Kondisi normal:\n";
            foreach ($catatan as $baris) {
                $pesan .= '- ' . $baris . "\n";
            }
        }

        $pesan .= "\nPesan otomatis dari sistem, mohon tidak dibalas.";

        if ($this->fonnteService->kirim($nomor, $pesan)) {
            $this->info('Laporan terkirim ke WA admin.');
        } else {
            $this->warn('Gagal mengirim laporan ke WA admin (lihat log).');
        }
    }
}
