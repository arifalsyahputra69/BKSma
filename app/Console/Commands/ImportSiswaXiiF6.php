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
 * IMPORT DATA SISWA KELAS XII F6 (19 Agustus 2026).
 *
 * Sama persis pola & alasannya dengan ImportSiswaXiiF7 / ImportSiswaXiF4 /
 * ImportSiswaXE7 (lihat file itu untuk penjelasan lengkap): 1 akun per siswa
 * (User + role Siswa + baris Siswa), username login = NISN, password awal
 * SAMA untuk semua = 12345678.
 *
 * Data diambil dari foto "DAFTAR HADIR SISWA KELAS XII F 6" (Kimia-Biologi-
 * Bahasa Jepang-Prakarya, TP 2026/2027), Wali Kelas: Rendy Budiman, S.Pd.
 * NISN diambil dari kolom NISN (bukan NIS).
 *
 * PERLU DIKONFIRMASI TU SEBELUM DIJALANKAN:
 * - Baris 4 (Al Hanny Jusna) dan baris 5 (Allya Putri Zahra): NISN di foto
 *   TERBACA SAMA PERSIS (0098545318) untuk keduanya -- ini kemungkinan besar
 *   salah baca (foto buram/tertutup), bukan data asli yang benar-benar
 *   kembar. Tolong cek ulang NISN salah satu dari dua siswa ini sebelum
 *   command dijalankan, supaya siswa kedua tidak otomatis "dilewati" oleh
 *   command (karena dianggap NISN sudah ada).
 *
 * Jumlah L/P dicocokkan dengan rekap di bawah tabel absen (L=5, P=31,
 * Total=36) -- sudah pas, jadi kolom jenis kelamin di bawah ini terpercaya.
 *
 * Jalankan sekali:  php artisan siswa:import-xii-f6
 * Aman dijalankan berkali-kali -- baris dengan NISN yang sudah ada di
 * tabel siswas otomatis dilewati (tidak dobel).
 */
class ImportSiswaXiiF6 extends Command
{
    protected $signature = 'siswa:import-xii-f6 {--kelas=XII F 6 : Nama kelas persis seperti di tabel kelas}';

    protected $description = 'Import 36 siswa kelas XII F6 dari daftar hadir ke tabel users & siswas';

    private const PASSWORD_AWAL = '12345678';

    /**
     * Urutan: nama, jenis_kelamin (L/P), nisn.
     */
    private array $roster = [
        ['Adinda Putri Akbar', 'P', '0081483919'],
        ['Aisya Muthmainnah', 'P', '0089496582'],
        ['Akila Putri Medinda', 'P', '0089469211'],
        ['Al Hanny Jusna', 'P', '0098545318'], // lihat catatan di atas -- cek ulang sebelum import
        ['Allya Putri Zahra', 'P', '0098545318'], // lihat catatan di atas -- cek ulang sebelum import
        ['Hadi Fadhirrahman', 'L', '0091828547'],
        ['Ibrahim Putra Al Ghazali', 'L', '0083242519'],
        ['Keyla Calista', 'P', '0085535756'],
        ['Latifah Rahmah', 'P', '0074884078'],
        ['Luna Clara Diva Dillah', 'P', '0084177162'],
        ['Luthfina Thalita Sakhi', 'P', '0098438405'],
        ['Mashyta Rossa', 'P', '0091641471'],
        ['Mayang Salwa Maisah', 'P', '0095097210'],
        ['Muhammad Bintang Ramadhan', 'L', '0089253853'],
        ['Naila Insani Basir', 'P', '0084998709'],
        ['Naila Rahmadani', 'P', '0081539520'],
        ['Natasya Adinda', 'P', '3095871282'],
        ['Novelia Fienka', 'P', '0098020114'],
        ['Novi Carisa Putri', 'P', '3088390002'],
        ['Putri Amaliyana Rahmat', 'P', '0099442841'],
        ['Rafi Febri Nandas', 'L', '0091225631'],
        ['Rafila Nisrina Putri', 'P', '0096673517'],
        ['Raissa Putri Zahirah', 'P', '3081875202'],
        ['Ramadhian Sabfitri', 'P', '0088164269'],
        ['Ratu Balqis', 'P', '0086971736'],
        ['Raysa Julia', 'P', '0088534034'],
        ['Reugi Madiano Rudisra', 'L', '0075823607'],
        ['Revi Anjelina', 'P', '0091885888'],
        ['Salsa Novita', 'P', '0094433227'],
        ['Sari Sartika Zain', 'P', '0084706544'],
        ['Selvi Olivia', 'P', '0086823627'],
        ['Sora Widalf Syauqi', 'P', '0095551427'],
        ['Sriani', 'P', '0092361503'],
        ['Syalwa Puti Sikumbang', 'P', '0085923947'],
        ['Syifa Salsabila', 'P', '0095029791'],
        ['Wilda Yenda', 'P', '0093866821'],
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
            $this->line('Ulangi dengan: php artisan siswa:import-xii-f6 --kelas="Nama Kelas Persis"');

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
