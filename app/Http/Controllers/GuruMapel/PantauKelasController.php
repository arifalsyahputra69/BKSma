<?php

namespace App\Http\Controllers\GuruMapel;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Semester;
use App\Models\SesiAbsensi;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * PANTAU KELAS SAYA -- GURU MAPEL (21 Agustus 2026)
 *
 * Padanan halaman "Pantau Kelas Saya" milik Wali Kelas
 * (WaliKelas\DashboardController), tapi cakupannya disesuaikan dengan peran
 * Guru Mapel:
 *
 * - Wali Kelas punya kelas tetap lewat kolom kelas.wali_kelas_id, jadi
 *   daftar "kelas yang diampu" tinggal di-query langsung. Guru Mapel tidak
 *   punya penugasan kelas yang tersimpan di mana pun -- kelas yang ia ajar
 *   hanya bisa diketahui dari jejak sesi_absensis yang pernah ia buka
 *   (kolom guru_id). Karena itu "kelas diampu" di sini didefinisikan
 *   sebagai: kelas mana pun yang pernah punya sesi absensi dari guru ini.
 *
 * - Rekap kehadiran Wali Kelas sengaja menggabungkan sesi dari SEMUA guru
 *   mapel di kelas itu, karena wali kelas bertanggung jawab atas kehadiran
 *   anak didiknya secara keseluruhan. Guru Mapel sebaliknya: ia hanya
 *   mengajar satu (atau beberapa) mata pelajaran di kelas itu, bukan
 *   pemilik kelasnya. Maka rekap di sini DIBATASI hanya pada sesi yang
 *   dibuka guru ini sendiri (guru_id = Auth::id()) -- kehadiran mata
 *   pelajaran yang ia ajar, bukan rapor kehadiran keseluruhan siswa.
 *
 * - Riwayat layanan konseling TIDAK ditampilkan di sini. Itu data BK yang
 *   sensitif dan di luar wewenang Guru Mapel; tidak satu pun controller
 *   Guru Mapel yang lain menyentuh JurnalLayanan/SesiKonseling. Sebagai
 *   gantinya ditampilkan riwayat sesi absensi terbaru miliknya sendiri,
 *   yang sepenuhnya dalam cakupan perannya.
 */
class PantauKelasController extends Controller
{
    public function index(Request $request)
    {
        $kelasDiampu = $this->kelasDiampu();

        $semesterList = Semester::orderByDesc('tanggal_mulai')->get();
        $semesterId = $request->semester_id
            ?? Semester::where('status_aktif', true)->value('id')
            ?? Semester::orderByDesc('tanggal_mulai')->value('id');

        $kelasId = $request->kelas_id ?? $kelasDiampu->first()?->id;
        $kelasAktif = $kelasDiampu->firstWhere('id', (int) $kelasId);

        $mapelDiampu = collect();
        $rekapAbsensi = ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alpha' => 0];
        $persenKehadiran = 0;
        $siswaBermasalah = collect();
        $rekapPerSiswa = collect();
        $sesiTerbaru = collect();

        if ($kelasAktif) {
            $sesiQuery = SesiAbsensi::where('guru_id', Auth::id())
                ->where('kelas_id', $kelasAktif->id);
            if ($semesterId) {
                $sesiQuery->where('semester_id', $semesterId);
            }

            // Nama mapel ditampilkan sebagai teks bebas apa adanya (kolom
            // sesi_absensis.mapel diisi manual oleh guru saat generate QR,
            // bukan dari daftar mapel baku), jadi bisa lebih dari satu kalau
            // guru ini mengajar lebih dari satu mapel di kelas yang sama.
            $mapelDiampu = (clone $sesiQuery)->distinct()->pluck('mapel')->filter()->values();

            $sesiIds = (clone $sesiQuery)->pluck('id');

            $absensiKelas = Absensi::whereIn('sesi_absensi_id', $sesiIds)
                ->selectRaw('siswa_id, status, COUNT(*) as jumlah')
                ->groupBy('siswa_id', 'status')
                ->get()
                ->groupBy('siswa_id');

            foreach (array_keys($rekapAbsensi) as $status) {
                $rekapAbsensi[$status] = Absensi::whereIn('sesi_absensi_id', $sesiIds)
                    ->where('status', $status)
                    ->count();
            }

            $totalAbsensi = array_sum($rekapAbsensi);
            $persenKehadiran = $totalAbsensi > 0
                ? round(($rekapAbsensi['Hadir'] / $totalAbsensi) * 100, 1)
                : 0;

            // Siswa dengan Alpha terbanyak KHUSUS pada mapel/sesi guru ini --
            // dihitung di level DB (GROUP BY + COUNT) dengan alasan yang sama
            // seperti versi Wali Kelas: menghindari memuat seluruh baris
            // absensi mentah ke memori PHP hanya untuk 5 besar teratas.
            $alphaPerSiswa = Absensi::whereIn('sesi_absensi_id', $sesiIds)
                ->where('status', 'Alpha')
                ->selectRaw('siswa_id, COUNT(*) as jumlah_alpha')
                ->groupBy('siswa_id')
                ->orderByDesc('jumlah_alpha')
                ->limit(5)
                ->get();

            $siswaById = Siswa::with('user')
                ->whereIn('id', $alphaPerSiswa->pluck('siswa_id'))
                ->get()
                ->keyBy('id');

            $siswaBermasalah = $alphaPerSiswa
                ->map(fn ($row) => (object) [
                    'siswa' => $siswaById->get($row->siswa_id),
                    'jumlah_alpha' => (int) $row->jumlah_alpha,
                ])
                ->values();

            // Rekap per siswa: gabungan anggota resmi kelas + siapa pun yang
            // punya catatan absensi di sesi guru ini (alasan sama seperti di
            // WaliKelas\DashboardController -- absensi terhubung ke kelas
            // lewat sesinya, bukan lewat data induk siswa).
            $idSiswaBerabsensi = $absensiKelas->keys();

            $siswaKelas = Siswa::with('user')
                ->where(function ($query) use ($kelasAktif, $idSiswaBerabsensi) {
                    $query->where('kelas_id', $kelasAktif->id);
                    if ($idSiswaBerabsensi->isNotEmpty()) {
                        $query->orWhereIn('id', $idSiswaBerabsensi);
                    }
                })
                ->get()
                ->sortBy(fn ($siswa) => $siswa->user->name ?? '')
                ->values();

            $rekapPerSiswa = $siswaKelas->map(function ($siswa) use ($absensiKelas, $kelasAktif) {
                $baris = $absensiKelas->get($siswa->id, collect())->pluck('jumlah', 'status');

                $hadir = (int) ($baris['Hadir'] ?? 0);
                $izin  = (int) ($baris['Izin'] ?? 0);
                $sakit = (int) ($baris['Sakit'] ?? 0);
                $alpha = (int) ($baris['Alpha'] ?? 0);
                $total = $hadir + $izin + $sakit + $alpha;

                return (object) [
                    'siswa'   => $siswa,
                    'anggotaResmi' => (int) $siswa->kelas_id === (int) $kelasAktif->id,
                    'hadir'   => $hadir,
                    'izin'    => $izin,
                    'sakit'   => $sakit,
                    'alpha'   => $alpha,
                    'total'   => $total,
                    'persen'  => $total > 0 ? round(($hadir / $total) * 100, 1) : null,
                ];
            })
            ->filter(fn ($baris) => $baris->anggotaResmi)
            ->values();

            $sesiTerbaru = SesiAbsensi::where('guru_id', Auth::id())
                ->where('kelas_id', $kelasAktif->id)
                ->when($semesterId, fn ($q) => $q->where('semester_id', $semesterId))
                ->withCount([
                    'absensis as hadir_count' => fn ($q) => $q->where('status', 'Hadir'),
                    'absensis as alpha_count' => fn ($q) => $q->where('status', 'Alpha'),
                ])
                ->latest('tanggal')
                ->latest('id')
                ->limit(10)
                ->get();
        }

        return view('gurumapel.pantau-kelas', compact(
            'kelasDiampu',
            'kelasAktif',
            'kelasId',
            'semesterList',
            'semesterId',
            'mapelDiampu',
            'rekapAbsensi',
            'persenKehadiran',
            'siswaBermasalah',
            'rekapPerSiswa',
            'sesiTerbaru'
        ));
    }

    /**
     * Kelas dianggap "diampu" oleh Guru Mapel ini kalau ia pernah membuka
     * minimal satu sesi absensi di kelas tsb. Lihat catatan di kelas atas
     * soal kenapa ini tidak bisa dibaca dari kolom penugasan tetap seperti
     * milik Wali Kelas.
     */
    private function kelasDiampu()
    {
        $kelasIds = SesiAbsensi::where('guru_id', Auth::id())
            ->distinct()
            ->pluck('kelas_id');

        return Kelas::withCount('siswas')
            ->whereIn('id', $kelasIds)
            ->get()
            ->pipe(fn ($kelas) => Kelas::urutkanAlami($kelas));
    }
}
