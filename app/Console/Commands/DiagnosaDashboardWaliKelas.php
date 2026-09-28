<?php

namespace App\Console\Commands;

use App\Models\Absensi;
use App\Models\JurnalLayanan;
use App\Models\Kelas;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * ALAT DIAGNOSA: Dashboard Wali Kelas kehabisan memori (19 Agustus 2026).
 *
 * Halaman /walikelas/dashboard terus gagal dengan "Allowed memory size ...
 * exhausted", dan lokasi error di log berpindah-pindah (kadang di lapisan
 * database, kadang di lapisan render Blade). Pola seperti itu berarti memori
 * sudah nyaris penuh lebih dulu, lalu alokasi kecil apa pun jadi pemicunya --
 * jadi log TIDAK bisa dipakai untuk menunjuk penyebab aslinya.
 *
 * Karena hosting tidak punya akses SSH/Tinker, perintah ini menjalankan ulang
 * langkah-langkah controller SATU PER SATU lewat cron, dan mencetak jumlah
 * baris + pemakaian memori setelah tiap langkah. Langkah yang angkanya
 * melonjak itulah biang keroknya.
 *
 * Perintah ini HANYA MEMBACA data -- tidak mengubah/menghapus apa pun.
 *
 * Jalankan:  php artisan diagnosa:dashboard-walikelas --user=31
 */
class DiagnosaDashboardWaliKelas extends Command
{
    protected $signature = 'diagnosa:dashboard-walikelas
                            {--user= : ID user Wali Kelas yang bermasalah (lihat kolom user_id di laravel.log)}';

    protected $description = 'Diagnosa pemakaian memori Dashboard Wali Kelas, langkah demi langkah';

    private function lapor(string $langkah, ?int $jumlah = null): void
    {
        $mb = round(memory_get_usage(true) / 1048576, 1);
        $puncak = round(memory_get_peak_usage(true) / 1048576, 1);
        $info = $jumlah === null ? '' : " | baris: {$jumlah}";

        $this->line(str_pad($langkah, 45) . " memori: {$mb} MB (puncak {$puncak} MB){$info}");
    }

    public function handle(): int
    {
        $userId = $this->option('user');

        if (! $userId) {
            $this->error('Wajib sebutkan --user=<id>. Lihat "user_id" di storage/logs/laravel.log.');

            return self::FAILURE;
        }

        $user = User::find($userId);

        if (! $user) {
            $this->error("User dengan id {$userId} tidak ditemukan.");

            return self::FAILURE;
        }

        $this->info("=== DIAGNOSA DASHBOARD WALI KELAS ===");
        $this->line('Waktu   : ' . now()->toDateTimeString());
        $this->line("User    : {$user->name} (id {$user->id})");
        $this->line('Limit   : ' . ini_get('memory_limit'));
        $this->newLine();

        $this->lapor('0. Awal (sebelum query apa pun)');

        // ---- Langkah 1: kelas yang diampu ----
        $kelasDiampu = Kelas::with('guruBk')
            ->withCount('siswas')
            ->where('wali_kelas_id', $user->id)
            ->get();

        $this->lapor('1. Kelas diampu', $kelasDiampu->count());
        foreach ($kelasDiampu as $k) {
            $this->line("     - {$k->nama_kelas} (id {$k->id}, {$k->siswas_count} siswa)");
        }

        if ($kelasDiampu->isEmpty()) {
            $this->warn('User ini tidak mengampu kelas apa pun -- dashboard semestinya ringan.');

            return self::SUCCESS;
        }

        $kelasAktif = $kelasDiampu->first();
        $this->newLine();
        $this->info("Kelas yang diperiksa: {$kelasAktif->nama_kelas} (id {$kelasAktif->id})");

        // ---- Langkah 2: semester ----
        $semesterAktif = Semester::where('status_aktif', true)->first();
        $semesterId = $semesterAktif?->id ?? Semester::orderByDesc('tanggal_mulai')->value('id');

        $this->line('     Semester dipakai: ' . ($semesterId ?? 'TIDAK ADA (filter mati!)')
            . ($semesterAktif ? " ({$semesterAktif->nama}, aktif)" : ' (tidak ada yang ditandai aktif)'));
        $this->line('     Total semester di database: ' . Semester::count());

        // ---- Langkah 3: ukuran tabel absensi (INI YANG PALING DICURIGAI) ----
        $this->newLine();
        $this->info('--- Ukuran data absensi ---');
        $this->line('     Total baris absensis (SELURUH sekolah): ' . Absensi::count());

        $absensiQuery = fn () => Absensi::whereHas('sesiAbsensi', function ($q) use ($kelasAktif, $semesterId) {
            $q->where('kelas_id', $kelasAktif->id);
            if ($semesterId) {
                $q->where('semester_id', $semesterId);
            }
        });

        $jumlahAbsensiKelas = $absensiQuery()->count();
        $this->lapor('3. COUNT absensi kelas ini', $jumlahAbsensiKelas);

        $tanpaSemester = Absensi::whereHas('sesiAbsensi', fn ($q) => $q->where('kelas_id', $kelasAktif->id))->count();
        $this->line("     (tanpa filter semester: {$tanpaSemester} baris)");

        // ---- Langkah 4: versi GROUP BY (perbaikan 19 Agustus 2026) ----
        $absensiKelas = $absensiQuery()
            ->selectRaw('siswa_id, status, COUNT(*) as jumlah')
            ->groupBy('siswa_id', 'status')
            ->get()
            ->groupBy('siswa_id');

        $this->lapor('4. Absensi via GROUP BY', $absensiKelas->count());

        // ---- Langkah 5: daftar siswa ----
        $siswaKelas = Siswa::with('user')
            ->where(function ($q) use ($kelasAktif, $absensiKelas) {
                $q->where('kelas_id', $kelasAktif->id);
                if ($absensiKelas->isNotEmpty()) {
                    $q->orWhereIn('id', $absensiKelas->keys());
                }
            })
            ->get();

        $this->lapor('5. Siswa (anggota + berabsensi)', $siswaKelas->count());
        $this->line('     Anggota resmi saja: ' . Siswa::where('kelas_id', $kelasAktif->id)->count());

        // ---- Langkah 6: jurnal layanan ----
        $this->newLine();
        $this->info('--- Riwayat konseling ---');
        $this->line('     Total baris jurnal_layanans (SELURUH sekolah): ' . JurnalLayanan::count());

        $dariAntrian = JurnalLayanan::with('antrian.siswa.user')
            ->whereHas('antrian.siswa', fn ($q) => $q->where('kelas_id', $kelasAktif->id))
            ->latest('updated_at')
            ->limit(10)
            ->get();

        $this->lapor('6a. Jurnal dari antrean (limit 10)', $dariAntrian->count());

        $manual = JurnalLayanan::with('siswa.user')
            ->whereNull('antrian_id')
            ->whereHas('siswa', fn ($q) => $q->where('kelas_id', $kelasAktif->id))
            ->latest('tanggal_konseling')
            ->limit(10)
            ->get();

        $this->lapor('6b. Jurnal manual (limit 10)', $manual->count());

        // ---- Langkah 7: alpha terbanyak ----
        $alpha = $absensiQuery()
            ->where('status', 'Alpha')
            ->selectRaw('siswa_id, COUNT(*) as jumlah_alpha')
            ->groupBy('siswa_id')
            ->orderByDesc('jumlah_alpha')
            ->limit(5)
            ->get();

        $this->lapor('7. Alpha terbanyak (limit 5)', $alpha->count());

        $this->newLine();
        $this->info('=== SELESAI ===');
        $this->line('Puncak memori seluruh proses: ' . round(memory_get_peak_usage(true) / 1048576, 1) . ' MB');
        $this->newLine();
        $this->line('Kalau semua langkah di atas ringan (puncak < 100 MB), berarti masalahnya');
        $this->line('BUKAN di query controller, melainkan di proses render tampilan.');

        return self::SUCCESS;
    }
}
