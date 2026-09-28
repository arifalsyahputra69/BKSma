<?php

namespace App\Console\Commands;

use App\Models\Absensi;
use App\Models\Notifikasi;
use App\Models\SesiAbsensi;
use App\Services\FonnteService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PERBAIKAN (AUDIT, 2 Agustus 2026) -- MASALAH TERBESAR SEBELUM HOSTING.
 *
 * Sebelumnya penandaan "Alpha" otomatis dikerjakan oleh AutoAlphaAbsensiJob
 * yang di-dispatch dengan ->delay() ke queue database. Cara itu HANYA jalan
 * kalau ada proses `php artisan queue:work` yang hidup terus-menerus.
 *
 * Di shared hosting (cPanel) proses permanen seperti itu praktis tidak bisa
 * dijalankan. Akibatnya di server sekolah nanti:
 *   - job-nya menumpuk di tabel `jobs` dan TIDAK PERNAH dieksekusi,
 *   - siswa yang tidak scan QR selamanya berstatus "Belum Absen",
 *     tidak pernah menjadi Alpha,
 *   - rekap absensi jadi salah, dan WA pemberitahuan ke orang tua
 *     tidak pernah terkirim.
 * Fitur ini dipakai setiap hari, jadi kegagalannya tidak akan kelihatan
 * sebagai "error" -- cuma diam-diam tidak bekerja. Itu yang berbahaya.
 *
 * Command ini menggantikan mekanisme tersebut: cukup dipanggil cron tiap
 * menit (satu baris di cPanel), tidak butuh worker sama sekali.
 *
 * Sifat penting command ini:
 *  - IDEMPOTEN. Aman dijalankan berkali-kali. Siswa yang sudah punya catatan
 *    absensi (Hadir/Izin/Sakit/Alpha) tidak pernah ditimpa.
 *  - TAHAN TUMPANG-TINDIH. Setiap sesi dikunci dengan lockForUpdate, jadi
 *    kalau cron sebelumnya belum selesai dan cron berikutnya sudah jalan,
 *    tidak akan ada Alpha ganda.
 *  - TAHAN GAGAL SEBAGIAN. Kegagalan mengirim WA satu siswa tidak
 *    membatalkan pemrosesan siswa lain (FonnteService sendiri sudah
 *    menelan errornya dan mengembalikan false).
 */
class TutupSesiAbsensiKedaluwarsa extends Command
{
    protected $signature = 'absensi:tutup-kedaluwarsa
                            {--dry-run : Hanya menampilkan apa yang AKAN dilakukan, tanpa mengubah data}';

    protected $description = 'Tandai Alpha siswa yang tidak scan QR pada sesi absensi yang sudah lewat batas waktu (pengganti queue worker)';

    public function __construct(protected FonnteService $fonnteService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Sesi yang perlu diproses: masih berstatus 'Aktif' TAPI waktu
        // expired-nya sudah lewat.
        //
        // Status dipakai sekaligus sebagai penanda "sudah diproses": begitu
        // command ini selesai menangani sebuah sesi, statusnya diubah jadi
        // 'Selesai' sehingga tidak pernah terambil lagi di menit berikutnya.
        // Itu sebabnya AbsensiController::tutup() (tombol "Tutup Sesi" milik
        // Guru Mapel) sekarang TIDAK langsung menulis 'Selesai', melainkan
        // memajukan waktu_expired ke sekarang -- biar command ini yang
        // menuntaskan Alpha-nya lalu menutup sesi. Lihat catatan di sana.
        $sesiIds = SesiAbsensi::query()
            ->where('status', 'Aktif')
            ->where('waktu_expired', '<=', now())
            ->pluck('id');

        if ($sesiIds->isEmpty()) {
            $this->info('Tidak ada sesi absensi yang perlu ditutup.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%sMemproses %d sesi absensi yang sudah lewat batas waktu.',
            $dryRun ? '[DRY-RUN] ' : '',
            $sesiIds->count()
        ));

        $totalAlpha = 0;

        foreach ($sesiIds as $sesiId) {
            $totalAlpha += $this->prosesSatuSesi($sesiId, $dryRun);
        }

        $this->info(sprintf(
            '%sSelesai. %d siswa ditandai Alpha.',
            $dryRun ? '[DRY-RUN] ' : '',
            $totalAlpha
        ));

        if (! $dryRun && $totalAlpha > 0) {
            Log::info('absensi:tutup-kedaluwarsa selesai.', [
                'jumlah_sesi' => $sesiIds->count(),
                'jumlah_alpha' => $totalAlpha,
            ]);
        }

        return self::SUCCESS;
    }

    /**
     * Satu sesi diproses dalam satu transaction tersendiri, supaya sesi yang
     * bermasalah tidak ikut membatalkan sesi lain yang sudah berhasil.
     */
    private function prosesSatuSesi(int $sesiId, bool $dryRun): int
    {
        // Notifikasi & WA sengaja dikumpulkan dulu, baru dikirim SETELAH
        // transaction commit. Mengirim WA di dalam transaction berbahaya:
        // panggilan HTTP ke Fonnte bisa lambat/menggantung dan menahan row
        // lock jauh lebih lama daripada yang diperlukan.
        $perluDikabari = [];

        $jumlah = DB::transaction(function () use ($sesiId, $dryRun, &$perluDikabari) {
            $sesi = SesiAbsensi::where('id', $sesiId)
                ->lockForUpdate()
                ->with('kelas.siswas.user')
                ->first();

            // Sudah disambar proses cron lain yang berjalan bersamaan.
            if (! $sesi || $sesi->status !== 'Aktif') {
                return 0;
            }

            $siswaSudahTercatat = Absensi::where('sesi_absensi_id', $sesi->id)
                ->pluck('siswa_id')
                ->all();

            $siswaKelas = $sesi->kelas?->siswas ?? collect();
            $dibuat = 0;

            foreach ($siswaKelas as $siswa) {
                if (in_array($siswa->id, $siswaSudahTercatat, true)) {
                    continue; // sudah Hadir / Izin / Sakit / Alpha
                }

                $dibuat++;

                if ($dryRun) {
                    $this->line(sprintf(
                        '  - [%s %s] %s akan ditandai Alpha',
                        $sesi->mapel,
                        $sesi->jam_ke,
                        optional($siswa->user)->name ?? ('siswa#' . $siswa->id)
                    ));

                    continue;
                }

                Absensi::create([
                    'sesi_absensi_id' => $sesi->id,
                    'siswa_id' => $siswa->id,
                    'status' => 'Alpha',
                    'waktu_scan' => null,
                    'keterangan' => 'Otomatis oleh sistem: tidak scan QR sampai batas waktu.',
                    'sumber' => Absensi::SUMBER_SISTEM,
                ]);

                if ($siswa->user_id) {
                    Notifikasi::create([
                        'user_id' => $siswa->user_id,
                        'judul' => 'Absensi: Alpha',
                        'pesan' => "Kamu tercatat Alpha pada mapel {$sesi->mapel} ({$sesi->jam_ke}) tanggal "
                            . $sesi->tanggal->format('d-m-Y')
                            . '. Jika ini keliru, silakan hubungi Guru Mapel bersangkutan.',
                        'link' => route('siswa.absensi.riwayat'),
                        'is_read' => false,
                    ]);
                }

                if (filled($siswa->no_wa_ortu) && $siswa->is_wa_verified) {
                    $perluDikabari[] = [
                        'no_wa_ortu' => $siswa->no_wa_ortu,
                        'nama_siswa' => optional($siswa->user)->name ?? 'Ananda',
                        'nama_ortu' => $siswa->nama_ortu ?: 'Bapak/Ibu',
                        'mapel' => $sesi->mapel,
                        'jam_ke' => $sesi->jam_ke,
                        'tanggal' => $sesi->tanggal->translatedFormat('d F Y'),
                    ];
                }
            }

            if (! $dryRun) {
                $sesi->update(['status' => 'Selesai']);
            }

            return $dibuat;
        });

        foreach ($perluDikabari as $pesan) {
            $this->fonnteService->notifikasiOrtuAbsensi(
                $pesan['no_wa_ortu'],
                $pesan['nama_siswa'],
                $pesan['nama_ortu'],
                'Alpha',
                $pesan['mapel'],
                $pesan['jam_ke'],
                $pesan['tanggal'],
                'Tidak scan QR sampai batas waktu.'
            );
        }

        return $jumlah;
    }
}
