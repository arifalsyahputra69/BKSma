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
 * IMPORT DATA SISWA KELAS XII F5 (19 Agustus 2026).
 *
 * Sama persis pola & alasannya dengan ImportSiswaXiiF6 / ImportSiswaXiiF7 /
 * ImportSiswaXiF4 / ImportSiswaXE7 (lihat file itu untuk penjelasan lengkap):
 * 1 akun per siswa (User + role Siswa + baris Siswa), username login = NISN,
 * password awal SAMA untuk semua = 12345678.
 *
 * Data diambil dari foto "DAFTAR HADIR SISWA KELAS XII F 5" (Kimia-Fisika-
 * Matematika-Prakarya, TP 2026/2027), Wali Kelas: Suci Rolanda Gusti, S.Pd.
 * NISN diambil dari kolom NISN (bukan NIS).
 *
 * PERLU DIKONFIRMASI TU SEBELUM DIJALANKAN:
 * - Baris 8 (Farraz Althaf Pratama): NISN di foto terbaca "096548529" --
 *   cuma 9 digit (semua NISN lain di lembar ini 10 digit), kemungkinan besar
 *   angka nol di depan kepotong/kabur saat difoto. Sudah saya tulis sebagai
 *   "0096548529" (menambah satu nol di depan, mengikuti pola NISN lain di
 *   kelas ini) -- tolong cek ulang ke foto aslinya sebelum command dijalankan.
 *
 * Jumlah L/P dicocokkan dengan rekap di bawah tabel absen (L=15, P=21,
 * Total=36) -- sudah pas, jadi kolom jenis kelamin di bawah ini terpercaya.
 *
 * Jalankan sekali:  php artisan siswa:import-xii-f5
 * Aman dijalankan berkali-kali -- baris dengan NISN yang sudah ada di
 * tabel siswas otomatis dilewati (tidak dobel).
 */
class ImportSiswaXiiF5 extends Command
{
    protected $signature = 'siswa:import-xii-f5 {--kelas=XII F 5 : Nama kelas persis seperti di tabel kelas}';

    protected $description = 'Import 36 siswa kelas XII F5 dari daftar hadir ke tabel users & siswas';

    private const PASSWORD_AWAL = '12345678';

    /**
     * Urutan: nama, jenis_kelamin (L/P), nisn.
     */
    private array $roster = [
        ['Abdul Gofur Subibto', 'L', '0095077624'],
        ['Abil Raydhatul Efendi', 'L', '0097821438'],
        ['Ali Alghani', 'L', '0094941924'],
        ['Ananda Putri Akbar', 'P', '0085348844'],
        ['Chintya Percha Dinatha', 'P', '0086474201'],
        ['Dwi Shifa Aurellia', 'P', '0089706576'],
        ['Fajra Rahmadhan Fadillah Zukhruf', 'L', '0082015314'],
        ['Farraz Althaf Pratama', 'L', '0096548529'], // lihat catatan di atas -- cek ulang sebelum import
        ['Feyruz Qurratu Aini', 'P', '0096048190'],
        ['Haswardi Pratama', 'L', '0088916887'],
        ['Irsyadi Kemal Arif', 'L', '0095197296'],
        ['Jhonatan Nick Nababan', 'L', '0095673908'],
        ['Keisya Azharea', 'P', '0089058955'],
        ['Kirana Larasati', 'P', '3084387313'],
        ['M Danu Al Azzam', 'L', '0081054903'],
        ['M Rizky Akbar', 'L', '0086591496'],
        ['Melani Siska Rahayu', 'P', '0089077518'],
        ['Minory Aisyah Nursahzira', 'P', '0095679864'],
        ['Muhammad Farel', 'L', '0096268160'],
        ['Muhammad Raihan Wijaya', 'L', '0097677149'],
        ['Nabila Hayatul Syafa', 'P', '0089452935'],
        ['Nur Rahmah Julianti', 'P', '0084495190'],
        ['Priyanka Kayla Mayana', 'P', '0093233896'],
        ['Raffa Zevio', 'L', '0092872221'],
        ['Rahmah Fadhilah', 'P', '0091194196'],
        ['Richi Onorato.Z', 'L', '0094079638'],
        ['Rifqi Nugraha Putra', 'L', '0088200578'],
        ['Sapira Putri Yasinta', 'P', '0084095785'],
        ['Shifa Mustika', 'P', '0083999598'],
        ['Syifa', 'P', '0094880494'],
        ['Syifaa Hamdika', 'P', '0097927371'],
        ['Tiara Anggreani', 'P', '0095092084'],
        ['Viona Panesa Putri', 'P', '0085208611'],
        ['Virsa Delova', 'P', '0097987124'],
        ['Zahara Novita', 'P', '0083421587'],
        ['Zura Humaira', 'P', '0083478100'],
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
            $this->line('Ulangi dengan: php artisan siswa:import-xii-f5 --kelas="Nama Kelas Persis"');

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
