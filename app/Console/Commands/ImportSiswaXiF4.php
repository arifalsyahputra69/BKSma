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
 * IMPORT DATA SISWA KELAS XI F4 (13 Agustus 2026).
 *
 * Sama persis pola & alasannya dengan ImportSiswaXiiF7 (lihat file itu untuk
 * penjelasan lengkap): 1 akun per siswa (User + role Siswa + baris Siswa),
 * username login = NISN, password awal SAMA untuk semua = 12345678.
 *
 * CATATAN DATA (dari foto "DAFTAR HADIR SISWA KELAS XI F4" TP 2026/2027):
 * - Baris 3 (Asyifa Febri Callista): kolom NISN di foto berisi "SMA PGRI 2"
 *   (bukan NISN, sepertinya sisa catatan pindahan sekolah) -- atas arahan
 *   TU, dipakai NIS (25088) sebagai pengganti.
 * - Baris 21 (Raziq Asy-Syakur RA): kolom NISN kosong -- atas arahan TU,
 *   dipakai NIS (25105) sebagai pengganti.
 * - Baris 18: nama "Muhammad Athif Athallah Da" sempat dikira terpotong di
 *   foto, tapi sudah dikonfirmasi TU itu memang nama lengkapnya -- ikut
 *   dimasukkan.
 *
 * Jalankan sekali:  php artisan siswa:import-xi-f4
 * Aman dijalankan berkali-kali -- baris dengan NISN yang sudah ada di
 * tabel siswas otomatis dilewati (tidak dobel).
 */
class ImportSiswaXiF4 extends Command
{
    protected $signature = 'siswa:import-xi-f4 {--kelas=XI F 4 : Nama kelas persis seperti di tabel kelas}';

    protected $description = 'Import 24 siswa kelas XI F4 dari daftar hadir ke tabel users & siswas';

    private const PASSWORD_AWAL = '12345678';

    /**
     * Urutan: nama, jenis_kelamin (L/P), nisn.
     */
    private array $roster = [
        ['Adelya Putri Edelva', 'P', '101219203'],
        ['Alif Basandi Furqaan', 'L', '0093058696'],
        ['Asyifa Febri Callista', 'P', '25088'], // NIS, bukan NISN -- lihat catatan di atas
        ['Azzam Affandi Pohan', 'L', '0104193918'],
        ['Cemal Altair Valian', 'L', '0103459957'],
        ['Daffa Zayyan Putra', 'L', '0101555477'],
        ['Dhieka Geraldi', 'L', '0106981215'],
        ['Evan Fadillah', 'L', '0109068513'],
        ['Farel Indra Novryandi', 'L', '0086976867'],
        ['Geza Adzikra', 'L', '0106252463'],
        ['Habiburrahman Zakky Yosna', 'L', '0091747400'],
        ['Jihan Syofyani Ningsih', 'P', '0104063157'],
        ['Jonatan Aprian Zai', 'L', '0108553511'],
        ['Jumadil Ilham', 'L', '0104672986'],
        ['Kaira Andesi', 'P', '0091849009'],
        ['M. Alvero Rizky Sangir', 'L', '0102126337'],
        ['Mohammad Arrafa Amanda', 'L', '0102327568'],
        ['Muhammad Athif Athallah Da', 'L', '0101176085'],
        ['Nadhifa Alisha Kaili', 'P', '0095415895'],
        ['Rafif Dzaki Rafaya', 'L', '0095859729'],
        ['Raziq Asy-Syakur RA', 'L', '25105'], // NIS, bukan NISN -- lihat catatan di atas
        ['Riziq Maulana Syahputra', 'L', '0104342114'],
        ['Sarah Ayuki', 'P', '0108026309'],
        ['Sulaiman Ali Kurnia', 'L', '0096106093'],
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
            $this->line('Ulangi dengan: php artisan siswa:import-xi-f4 --kelas="Nama Kelas Persis"');

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
