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
 * IMPORT DATA SISWA KELAS X E7 (13 Agustus 2026).
 *
 * Sama persis pola & alasannya dengan ImportSiswaXiiF7 / ImportSiswaXiF4
 * (lihat file itu untuk penjelasan lengkap): 1 akun per siswa (User + role
 * Siswa + baris Siswa), username login = NISN, password awal SAMA untuk
 * semua = 12345678.
 *
 * CATATAN DATA: foto daftar hadir kelas ini miring/tidak lurus, jadi
 * pembacaan awal kolom L/P & NISN beberapa kali kegeser 1 baris. Sudah
 * dikoreksi bersama TU sebelum diimport:
 * - Baris 19 Nayyara Huriyah & baris 20 Rafa Hazimulfikri: jenis kelamin
 *   sempat kebaca tertukar (nama vs L/P tidak nyambung), dibetulkan jadi
 *   Nayyara=P, Rafa=L.
 * - Baris 32 Zhivara Maulina: NISN dikonfirmasi ulang oleh TU jadi
 *   01176747773 (bukan hasil baca awal yang sempat sama persis dengan
 *   baris 31).
 * - Baris 33 Zidane: dikonfirmasi ulang oleh TU jadi L, NISN 0105596123
 *   (kolom NISN di foto aslinya tidak terbaca jelas).
 *
 * Jalankan sekali:  php artisan siswa:import-x-e7
 * Aman dijalankan berkali-kali -- baris dengan NISN yang sudah ada di
 * tabel siswas otomatis dilewati (tidak dobel).
 */
class ImportSiswaXE7 extends Command
{
    protected $signature = 'siswa:import-x-e7 {--kelas=X E 7 : Nama kelas persis seperti di tabel kelas}';

    protected $description = 'Import 34 siswa kelas X E7 dari daftar hadir ke tabel users & siswas';

    private const PASSWORD_AWAL = '12345678';

    /**
     * Urutan: nama, jenis_kelamin (L/P), nisn.
     */
    private array $roster = [
        ['Alano Danish Pratama', 'L', '0105578645'],
        ['Aleesya Ratifa', 'P', '0113575288'],
        ['Amin Jasir', 'L', '0106863770'],
        ['Azzairah Ainun Fatimah', 'P', '0106932788'],
        ['Camelia Cahyaningrum', 'P', '0115403283'],
        ['Delvin Xiandra Alwis', 'L', '0107827295'],
        ['Faiz Yusra', 'L', '0107380473'],
        ['Febrian Wirahadi', 'L', '0115978348'],
        ['Fhiona Ramadhani', 'P', '0118233507'],
        ['Jihan Safira Eteng Sitorus', 'P', '0113704731'],
        ['Khanza Haura Ferby', 'P', '0111836005'],
        ['Lutfhiyah Lofiola', 'P', '0111699434'],
        ['Meggy Mediansyah', 'L', '0117742987'],
        ['Muhammad Ibnu Kautsar', 'L', '3098138962'],
        ['Mutiara Vionariza Aulia', 'P', '0113524169'],
        ['Nabila Aprilia Putri', 'P', '0119029616'],
        ['Nadhifa Riani Erman', 'P', '0117055148'],
        ['Naufal Lathifah Labhibb', 'L', '0107088990'],
        ['Nayyara Huriyah', 'P', '0115210634'],
        ['Rafa Hazimulfikri', 'L', '0115569540'],
        ['Rafael Inesta', 'L', '0107149190'],
        ['Raihan Luthfi', 'L', '0114236074'],
        ['Ravael Alubatner', 'L', '0092075959'],
        ['Renindya Triansi', 'P', '0112186396'],
        ['Reyvan Apta Waqar', 'L', '0118469026'],
        ['Rindu Cinta Widia', 'P', '0119386553'],
        ['Salsabila Haniva', 'P', '0104523232'],
        ['Shareffa Evelyn Ramadhani', 'P', '0101090241'],
        ['Silva Wirianda', 'P', '0117042510'],
        ['Syabia Naziha Lutfi', 'P', '0104560951'],
        ['Vidi Prayoto', 'L', '0113136785'],
        ['Zhivara Maulina', 'P', '01176747773'],
        ['Zidane', 'L', '0105596123'],
        ['Zupelnelda', 'P', '0101789691'],
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
            $this->line('Ulangi dengan: php artisan siswa:import-x-e7 --kelas="Nama Kelas Persis"');

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
