<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * PERBAIKAN (AUDIT, 2 Agustus 2026).
 *
 * Sebelum ini tidak ada mekanisme backup sama sekali. Untuk aplikasi ini
 * risikonya tidak seimbang: data absensi masih bisa diinput ulang kalau
 * hilang, tapi CATATAN KONSELING tidak. Isinya hasil percakapan pribadi
 * antara Guru BK dan siswa -- kalau databasenya rusak/terhapus, tidak ada
 * cara memulihkannya.
 *
 * Command ini membuat dump SQL terkompresi ke storage/app/backup, dan
 * otomatis membuang backup yang lebih tua dari BACKUP_SIMPAN_HARI.
 *
 * Dijalankan otomatis tiap hari oleh scheduler (lihat routes/console.php),
 * atau manual:  php artisan backup:database
 *
 * PENTING -- backup di server yang sama BUKAN backup sungguhan.
 * Kalau hosting bermasalah atau akun ditangguhkan, backupnya ikut hilang.
 * Unduh berkalanya ke komputer/Google Drive sekolah. Lihat
 * PANDUAN-DEPLOY-CPANEL.md bagian "Backup".
 */
class BackupDatabase extends Command
{
    protected $signature = 'backup:database
                            {--simpan= : Berapa hari backup lama disimpan (default dari env BACKUP_SIMPAN_HARI, atau 14)}';

    protected $description = 'Buat backup (dump) database ke storage/app/backup dan hapus backup yang sudah kedaluwarsa';

    public function handle(): int
    {
        $koneksi = config('database.default');
        $db = config("database.connections.{$koneksi}");

        if (($db['driver'] ?? null) !== 'mysql') {
            $this->error("Command ini hanya untuk MySQL. Koneksi aktif: {$koneksi}.");

            return self::FAILURE;
        }

        $folder = storage_path('app/backup');

        if (! is_dir($folder) && ! mkdir($folder, 0775, true) && ! is_dir($folder)) {
            $this->error("Gagal membuat folder backup: {$folder}");

            return self::FAILURE;
        }

        $namaFile = sprintf('%s-%s.sql', $db['database'], now()->format('Y-m-d_His'));
        $tujuan = $folder . DIRECTORY_SEPARATOR . $namaFile;

        // PERBAIKAN (2 Agustus 2026): banyak shared hosting murah (termasuk
        // paket kecil Rumahweb) mematikan fungsi proc_open/exec demi keamanan
        // bersama. Padahal Symfony Process -- yang dipakai untuk memanggil
        // mysqldump -- bergantung pada proc_open. Kalau dipaksakan, backup
        // harian akan GAGAL diam-diam setiap malam.
        //
        // Karena itu ada dua jalur: pakai mysqldump kalau memungkinkan (lebih
        // cepat & lengkap), kalau tidak jatuh ke penulisan dump murni PHP lewat
        // PDO. Untuk ukuran database sekolah, jalur PHP sepenuhnya memadai.
        if (! $this->prosesBisaDijalankan()) {
            $this->warn('proc_open/exec tidak tersedia di server ini (umum di shared hosting).');
            $this->line('Beralih ke mode backup murni PHP.');

            return $this->backupLewatPdo($tujuan, $folder);
        }

        $mysqldump = env('MYSQLDUMP_PATH', 'mysqldump');

        // Password dikirim lewat variabel environment MYSQL_PWD, BUKAN lewat
        // argumen --password. Argumen command terlihat oleh pengguna lain di
        // server yang menjalankan `ps aux`; environment variable tidak.
        $perintah = [
            $mysqldump,
            '--host=' . ($db['host'] ?? '127.0.0.1'),
            '--port=' . ($db['port'] ?? 3306),
            '--user=' . ($db['username'] ?? ''),
            '--single-transaction',   // konsisten tanpa mengunci tabel (InnoDB)
            '--quick',
            '--default-character-set=utf8mb4',
            '--no-tablespaces',       // sering wajib di shared hosting (hak akses terbatas)
            $db['database'] ?? '',
        ];

        $this->info("Membuat backup: {$namaFile}");

        $process = new Process($perintah, null, [
            'MYSQL_PWD' => (string) ($db['password'] ?? ''),
        ]);
        $process->setTimeout(600); // 10 menit

        $handle = fopen($tujuan, 'wb');

        if ($handle === false) {
            $this->error("Tidak bisa menulis ke {$tujuan}");

            return self::FAILURE;
        }

        try {
            $process->run(function ($tipe, $data) use ($handle) {
                if ($tipe === Process::OUT) {
                    fwrite($handle, $data);
                }
            });
        } catch (ProcessTimedOutException $e) {
            fclose($handle);
            @unlink($tujuan);
            $this->error('Backup melebihi batas waktu 10 menit dan dibatalkan.');
            Log::error('backup:database timeout.', ['pesan' => $e->getMessage()]);

            return self::FAILURE;
        }

        fclose($handle);

        if (! $process->isSuccessful()) {
            @unlink($tujuan);
            $pesan = trim($process->getErrorOutput()) ?: 'mysqldump gagal tanpa keterangan.';

            $this->warn('mysqldump gagal: ' . $pesan);
            $this->line('Mencoba ulang dengan mode backup murni PHP...');

            Log::warning('backup:database: mysqldump gagal, beralih ke mode PDO.', ['stderr' => $pesan]);

            return $this->backupLewatPdo($tujuan, $folder);
        }

        $ukuran = filesize($tujuan) ?: 0;

        if ($ukuran < 1024) {
            @unlink($tujuan);
            $this->error('Backup dibatalkan: hasil dump mencurigakan (kurang dari 1 KB).');
            Log::error('backup:database menghasilkan file terlalu kecil.', ['ukuran' => $ukuran]);

            return self::FAILURE;
        }

        // Kompres kalau ekstensi zlib tersedia (dump SQL menyusut drastis).
        if (function_exists('gzencode')) {
            $isi = file_get_contents($tujuan);

            if ($isi !== false) {
                $terkompresi = gzencode($isi, 9);

                if ($terkompresi !== false && file_put_contents($tujuan . '.gz', $terkompresi) !== false) {
                    @unlink($tujuan);
                    $tujuan .= '.gz';
                    $ukuran = filesize($tujuan) ?: $ukuran;
                }
            }
        }

        $this->info(sprintf('Berhasil: %s (%s)', basename($tujuan), $this->formatUkuran($ukuran)));

        $this->bersihkanBackupLama($folder);

        return self::SUCCESS;
    }

    /**
     * Apakah server mengizinkan menjalankan program luar? Shared hosting
     * sering menonaktifkan proc_open/exec lewat disable_functions di php.ini.
     */
    private function prosesBisaDijalankan(): bool
    {
        $dimatikan = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return function_exists('proc_open') && ! in_array('proc_open', $dimatikan, true);
    }

    /**
     * Backup tanpa program luar: baca seluruh tabel lewat PDO, tulis sendiri
     * pernyataan CREATE TABLE + INSERT-nya.
     *
     * Lebih lambat daripada mysqldump, tapi berjalan di hosting mana pun.
     * Untuk database sekolah (ribuan baris, bukan jutaan) selisihnya tidak
     * terasa. Data ditulis per 200 baris supaya penggunaan memori tetap
     * rendah -- shared hosting biasanya membatasi memory_limit.
     */
    private function backupLewatPdo(string $tujuan, string $folder): int
    {
        $handle = fopen($tujuan, 'wb');

        if ($handle === false) {
            $this->error("Tidak bisa menulis ke {$tujuan}");

            return self::FAILURE;
        }

        try {
            $namaDb = DB::getDatabaseName();

            fwrite($handle, "-- Backup SIM BK (mode PHP/PDO)\n");
            fwrite($handle, "-- Database : {$namaDb}\n");
            fwrite($handle, '-- Dibuat   : ' . now()->toDateTimeString() . "\n\n");
            fwrite($handle, "SET NAMES utf8mb4;\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            $tabelList = array_map(
                fn ($baris) => array_values((array) $baris)[0],
                DB::select('SHOW TABLES')
            );

            $bar = $this->output->createProgressBar(count($tabelList));
            $bar->start();

            foreach ($tabelList as $tabel) {
                $this->tulisSatuTabel($handle, $tabel);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        } catch (\Throwable $e) {
            fclose($handle);
            @unlink($tujuan);

            $this->error('Backup GAGAL: ' . $e->getMessage());
            Log::error('backup:database (mode PDO) gagal.', ['pesan' => $e->getMessage()]);

            return self::FAILURE;
        }

        fclose($handle);

        $ukuran = filesize($tujuan) ?: 0;

        if ($ukuran < 1024) {
            @unlink($tujuan);
            $this->error('Backup dibatalkan: hasil dump mencurigakan (kurang dari 1 KB).');

            return self::FAILURE;
        }

        if (function_exists('gzencode')) {
            $isi = file_get_contents($tujuan);

            if ($isi !== false) {
                $terkompresi = gzencode($isi, 9);

                if ($terkompresi !== false && file_put_contents($tujuan . '.gz', $terkompresi) !== false) {
                    @unlink($tujuan);
                    $tujuan .= '.gz';
                    $ukuran = filesize($tujuan) ?: $ukuran;
                }
            }
        }

        $this->info(sprintf('Berhasil: %s (%s)', basename($tujuan), $this->formatUkuran($ukuran)));

        $this->bersihkanBackupLama($folder);

        return self::SUCCESS;
    }

    /**
     * @param  resource  $handle
     */
    private function tulisSatuTabel($handle, string $tabel): void
    {
        $struktur = DB::select('SHOW CREATE TABLE `' . str_replace('`', '', $tabel) . '`');
        $createSql = array_values((array) $struktur[0])[1] ?? null;

        fwrite($handle, "\n-- ------------------------------------------------\n");
        fwrite($handle, "-- Tabel: {$tabel}\n");
        fwrite($handle, "-- ------------------------------------------------\n");
        fwrite($handle, "DROP TABLE IF EXISTS `{$tabel}`;\n");

        if ($createSql) {
            fwrite($handle, $createSql . ";\n\n");
        }

        $pdo = DB::getPdo();
        $offset = 0;
        $batas = 200;

        do {
            $baris = DB::select("SELECT * FROM `{$tabel}` LIMIT {$batas} OFFSET {$offset}");

            foreach ($baris as $isi) {
                $kolom = (array) $isi;

                $namaKolom = implode(', ', array_map(
                    fn ($k) => '`' . $k . '`',
                    array_keys($kolom)
                ));

                $nilai = implode(', ', array_map(
                    fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v),
                    array_values($kolom)
                ));

                fwrite($handle, "INSERT INTO `{$tabel}` ({$namaKolom}) VALUES ({$nilai});\n");
            }

            $offset += $batas;
        } while (count($baris) === $batas);
    }

    private function bersihkanBackupLama(string $folder): void
    {
        $simpanHari = (int) ($this->option('simpan') ?: env('BACKUP_SIMPAN_HARI', 14));

        if ($simpanHari < 1) {
            return;
        }

        $batas = now()->subDays($simpanHari)->getTimestamp();
        $dihapus = 0;

        foreach (glob($folder . DIRECTORY_SEPARATOR . '*.sql*') ?: [] as $berkas) {
            if (filemtime($berkas) < $batas) {
                @unlink($berkas);
                $dihapus++;
            }
        }

        if ($dihapus > 0) {
            $this->line("Menghapus {$dihapus} backup lama (lebih dari {$simpanHari} hari).");
        }
    }

    private function formatUkuran(int $byte): string
    {
        if ($byte >= 1048576) {
            return round($byte / 1048576, 1) . ' MB';
        }

        return round($byte / 1024, 1) . ' KB';
    }
}
