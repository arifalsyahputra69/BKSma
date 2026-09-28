<?php

namespace App\Console\Commands;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * IMPORT DATA SISWA KELAS XII F7 (13 Agustus 2026).
 *
 * Dibuat sekali pakai untuk memasukkan 36 siswa dari foto daftar hadir
 * kelas XII F7 (Ekonomi-Sosiologi-Bahasa Jepang-Prakarya, TP 2026/2027)
 * ke database, karena TU tidak mau input satu-satu lewat form Kelola
 * Pengguna. Logikanya sengaja disamakan persis dengan
 * TU\UserController::store() untuk role Siswa, supaya akun yang dibuat
 * dari sini identik dengan akun yang dibuat manual lewat halaman TU:
 *   - Username login = NISN (lihat CustomPasswordController::@nomor_induk)
 *   - Password awal SAMA untuk semua siswa: 12345678 (diminta TU, supaya
 *     mudah dibagikan ke satu kelas sekaligus). Siswa disarankan ganti
 *     lewat halaman profil setelah login pertama.
 *   - Role 'Siswa' di-assign via Spatie
 *   - Baris di tabel siswas dibuat dengan status_konfirmasi_kelas = null
 *     supaya siswa diminta konfirmasi kelas saat login pertama, sama
 *     seperti alur akun siswa lain yang dibuat TU.
 *
 * NISN diambil dari kolom NISN pada berkas (BUKAN kolom NIS, sesuai
 * permintaan). Baris 13 (Friska Tri Aulia) dan Radista Efelin Wanza
 * dikoreksi manual sesuai NISN yang dikonfirmasi ulang oleh TU karena
 * tercetak buram di foto aslinya.
 *
 * Jalankan sekali:  php artisan siswa:import-xii-f7
 * Aman dijalankan berkali-kali -- baris dengan NISN yang sudah ada
 * di tabel siswas otomatis dilewati (tidak dobel).
 */
class ImportSiswaXiiF7 extends Command
{
    protected $signature = 'siswa:import-xii-f7 {--kelas=XII F7 : Nama kelas persis seperti di tabel kelas}';

    protected $description = 'Import 36 siswa kelas XII F7 dari daftar hadir ke tabel users & siswas';

    /**
     * Diminta TU: semua siswa yang diimport pakai password awal yang sama,
     * bukan NISN masing-masing, supaya gampang dibagikan ke satu kelas.
     */
    private const PASSWORD_AWAL = '12345678';

    /**
     * Data diambil dari foto "DAFTAR HADIR SISWA KELAS XII F7" TP 2026/2027.
     * Urutan: nama, jenis_kelamin (L/P), nisn.
     */
    private array $roster = [
        ['Ahmad Hanif Alrasyid', 'L', '0084480472'],
        ['Airin Utami', 'P', '0099785890'],
        ['Akmal Ramadhan', 'L', '0083613661'],
        ['Alfathir Rafa Dwi Arifin', 'L', '0084280128'],
        ['Anggia Nada Erlangga', 'P', '0098468912'],
        ['Aura Yeko Ramadhani', 'P', '0088903234'],
        ['Aurelia Indah Yani', 'P', '0089355893'],
        ['Chelsy Marmora', 'P', '0083251574'],
        ['Divo Yonanda', 'L', '0086417984'],
        ['Fadli Ramadhoni', 'L', '0083235586'],
        ['Faiz Kurnianto', 'L', '0095543472'],
        ['Faras Putri Ozalia', 'P', '3095746216'],
        ['Friska Tri Aulia', 'P', '0088317079'],
        ['Helgi Dharma Putra', 'L', '0092648367'],
        ['Indah Rahmahdani', 'P', '0084194143'],
        ['Iqbal Danuri', 'L', '0095354436'],
        ['Jihan Junita', 'P', '0092812236'],
        ['Kenzhio Dimas Setiawan', 'L', '0092517112'],
        ['Keyla Aprilianisa', 'P', '0098121735'],
        ['Miftahul Fauza', 'P', '0081148024'],
        ['Muhammad Yasnil Emza', 'L', '0097780905'],
        ['Muhammad Zaki', 'L', '0096940552'],
        ['Nabila Putri', 'P', '3084729631'],
        ['Naysila Ananta Putri', 'P', '0096216099'],
        ['Nazhifah Bemaitsa', 'P', '3091634875'],
        ['Radista Efelin Wanza', 'P', '0096566837'],
        ['Rama Ikram', 'L', '0095402439'],
        ['Rayhan Jonata', 'L', '3084594628'],
        ['Rhadytia Pebriatama', 'L', '0097321702'],
        ['Rindu Aura Olivia', 'P', '0086609929'],
        ['Rizky Syaputra', 'L', '0082271212'],
        ['Silvia Maliska', 'P', '0083569296'],
        ['Syabila Fitri', 'P', '0096731346'],
        ['Thafza Antika Pratama', 'L', '0099240684'],
        ['Vici Ziwinata Zulkis', 'P', '0098929790'],
        ['Yolanda Oktari', 'P', '0084446995'],
    ];

    public function handle(): int
    {
        $namaKelas = $this->option('kelas');

        $kelas = Kelas::where('nama_kelas', $namaKelas)->first();

        if (! $kelas) {
            $this->error("Kelas \"{$namaKelas}\" tidak ditemukan di tabel kelas.");
            $mirip = Kelas::where('nama_kelas', 'like', '%' . str_replace(' ', '%', $namaKelas) . '%')->pluck('nama_kelas');
            if ($mirip->isNotEmpty()) {
                $this->line('Kelas yang mirip: ' . $mirip->implode(', '));
            }
            $this->line('Ulangi dengan: php artisan siswa:import-xii-f7 --kelas="Nama Kelas Persis"');

            return self::FAILURE;
        }

        if (! Role::where('name', 'Siswa')->exists()) {
            $this->error('Role "Siswa" belum ada di tabel roles. Jalankan seeder role dulu.');

            return self::FAILURE;
        }

        $dibuat = 0;
        $dilewati = 0;

        foreach ($this->roster as [$nama, $jenisKelamin, $nisn]) {
            if (Siswa::where('nisn', $nisn)->exists()) {
                $this->line("Lewati (sudah ada): {$nama} - {$nisn}");
                $dilewati++;

                continue;
            }

            DB::transaction(function () use ($nama, $jenisKelamin, $nisn, $kelas) {
                $user = User::create([
                    'name' => $nama,
                    'email' => null,
                    'password' => Hash::make(self::PASSWORD_AWAL),
                    'jenis_kelamin' => $jenisKelamin,
                    'is_active' => true,
                ]);

                $user->assignRole('Siswa');

                Siswa::create([
                    'user_id' => $user->id,
                    'nisn' => $nisn,
                    'kelas_id' => $kelas->id,
                    'status_konfirmasi_kelas' => null,
                ]);
            });

            $this->info("Ditambahkan: {$nama} - {$nisn}");
            $dibuat++;
        }

        $this->newLine();
        $this->info("Selesai. Ditambahkan: {$dibuat}, dilewati (sudah ada): {$dilewati}.");
        $this->line('Password awal login semua siswa: ' . self::PASSWORD_AWAL);

        return self::SUCCESS;
    }
}
