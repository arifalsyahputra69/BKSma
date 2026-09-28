<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\JurnalLayanan;
use App\Models\Kelas;
use App\Models\Semester;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Dashboard Wali Kelas: pantau absensi & konseling untuk kelas
     * yang menjadi tanggung jawabnya (kelas.wali_kelas_id = user login).
     */
    public function index(Request $request)
    {
        // Guru BK dan jumlah siswa ikut dimuat sejak awal supaya kartu identitas
        // kelas di dashboard bisa menampilkannya tanpa query tambahan per baris.
        $kelasDiampu = Kelas::with('guruBk')
            ->withCount('siswas')
            ->where('wali_kelas_id', Auth::id())
            ->orderBy('nama_kelas')
            ->get();

        $semesterList = Semester::orderByDesc('tanggal_mulai')->get();
        $semesterId = $request->semester_id ?? Semester::where('status_aktif', true)->value('id');

        $kelasId = $request->kelas_id ?? $kelasDiampu->first()?->id;

        $kelasAktif = $kelasDiampu->firstWhere('id', $kelasId);

        $rekapAbsensi = ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alpha' => 0];
        $riwayatKonseling = collect();
        $siswaBermasalah = collect();
        $rekapPerSiswa = collect();

        if ($kelasAktif) {
            $absensiQuery = Absensi::whereHas('sesiAbsensi', function ($q) use ($kelasAktif, $semesterId) {
                $q->where('kelas_id', $kelasAktif->id);
                if ($semesterId) {
                    $q->where('semester_id', $semesterId);
                }
            });

            // PERBAIKAN (10 Agustus 2026): angka ringkasan dibatasi pada anggota
            // resmi kelas saat ini.
            //
            // Absensi terhubung ke kelas lewat sesinya, jadi tanpa pembatasan
            // ini catatan siswa yang SUDAH PINDAH kelas masih ikut terhitung di
            // kelas lamanya. Wali kelas jadi melihat angka Alpha milik anak yang
            // bukan anak didiknya lagi, dan persentase kehadirannya ikut
            // tertarik turun. Riwayat mereka tetap bisa dilihat di halaman Rekap
            // Absensi Kelas, di bagiannya sendiri.
            $idAnggotaResmi = Siswa::where('kelas_id', $kelasAktif->id)->pluck('id');

            foreach (array_keys($rekapAbsensi) as $status) {
                $rekapAbsensi[$status] = (clone $absensiQuery)
                    ->whereIn('siswa_id', $idAnggotaResmi)
                    ->where('status', $status)
                    ->count();
            }

            // Riwayat konseling digabung dari 2 sumber, karena jurnal_layanans
            // punya 2 jalur pengisian (lihat GuruBK\JurnalLayananController@storeJurnal):
            // - Jurnal MANUAL: siswa_id & tanggal_konseling terisi langsung.
            // - Jurnal dari ANTREAN (booking -> dipanggil Guru BK): siswa_id &
            //   tanggal_konseling NULL, datanya cuma bisa didapat lewat relasi
            //   antrian->siswa & antrian->updated_at.
            // Query lama di sini cuma menangkap jurnal manual, jadi riwayat dari
            // alur booking antrean tidak pernah muncul di dashboard Wali Kelas.
            $dariAntrianKelas = JurnalLayanan::with('antrian.siswa.user')
                ->whereHas('antrian.siswa', fn ($q) => $q->where('kelas_id', $kelasAktif->id))
                ->get()
                ->map(fn ($j) => (object) [
                    'siswa' => $j->antrian->siswa,
                    'kategori_masalah' => $j->kategori_masalah,
                    'tanggal_konseling' => $j->antrian->updated_at,
                ]);

            $manualKelas = JurnalLayanan::with('siswa.user')
                ->whereNull('antrian_id')
                ->whereHas('siswa', fn ($q) => $q->where('kelas_id', $kelasAktif->id))
                ->get()
                ->map(fn ($j) => (object) [
                    'siswa' => $j->siswa,
                    'kategori_masalah' => $j->kategori_masalah,
                    'tanggal_konseling' => $j->tanggal_konseling,
                ]);

            $riwayatKonseling = $dariAntrianKelas->concat($manualKelas)
                ->sortByDesc('tanggal_konseling')
                ->take(10)
                ->values();

            // Siswa dengan Alpha terbanyak di kelas ini (untuk perhatian Wali Kelas)
            $siswaBermasalah = Absensi::whereHas('sesiAbsensi', function ($q) use ($kelasAktif, $semesterId) {
                    $q->where('kelas_id', $kelasAktif->id);
                    if ($semesterId) {
                        $q->where('semester_id', $semesterId);
                    }
                })
                ->where('status', 'Alpha')
                ->with('siswa.user')
                ->get()
                ->groupBy('siswa_id')
                ->map(fn ($rows) => (object) [
                    'siswa' => $rows->first()->siswa,
                    'jumlah_alpha' => $rows->count(),
                ])
                ->sortByDesc('jumlah_alpha')
                ->take(5)
                ->values();

            // ============================================================
            // REKAP KEHADIRAN PER SISWA (9 Agustus 2026)
            // ============================================================
            // Dashboard sebelumnya hanya menyajikan angka gabungan sekelas dan
            // lima siswa dengan Alpha terbanyak. Wali kelas jadi tidak bisa
            // menjawab pertanyaan paling dasar dalam perannya: "bagaimana
            // kehadiran si A sepanjang semester ini?"
            //
            // Rekap ini mencakup absensi dari SELURUH guru mapel yang mengajar
            // di kelasnya, bukan hanya sesi yang dibuka wali kelas itu sendiri.
            // Sebagai wali, yang ia perlu tahu memang kehadiran anak didiknya
            // secara utuh, bukan sepotong menurut siapa yang mencatat.
            // Seluruh baris absensi kelas ini diambil sekali lalu dikelompokkan
            // di memori. Dengan cara ini jumlah query tetap satu, tidak ikut
            // bertambah mengikuti banyaknya siswa di kelas.
            $absensiKelas = (clone $absensiQuery)->get()->groupBy('siswa_id');

            // PERBAIKAN (9 Agustus 2026): daftar siswa tidak lagi diambil hanya
            // dari kolom kelas_id di tabel siswas.
            //
            // Sebabnya, absensi terhubung ke kelas lewat SESI-nya
            // (sesi_absensis.kelas_id), bukan lewat data induk siswa. Kedua
            // jalur itu bisa berbeda isi: siswa yang diabsen di sesi XII F1
            // belum tentu tercatat sebagai anggota XII F1 di tabel siswas --
            // misalnya karena pemindahan kelas belum dirapikan TU. Waktu daftar
            // ini hanya bersandar pada satu jalur, hasilnya kosong melompong
            // padahal absensinya jelas ada, dan wali kelas tidak diberi petunjuk
            // apa pun tentang apa yang salah.
            //
            // Sekarang keduanya digabung: anggota resmi kelas ini, DITAMBAH
            // siapa pun yang benar-benar punya catatan absensi di kelas ini.
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
                $baris = $absensiKelas->get($siswa->id, collect());

                $hadir = $baris->where('status', 'Hadir')->count();
                $izin  = $baris->where('status', 'Izin')->count();
                $sakit = $baris->where('status', 'Sakit')->count();
                $alpha = $baris->where('status', 'Alpha')->count();
                $total = $hadir + $izin + $sakit + $alpha;

                return (object) [
                    'siswa'   => $siswa,
                    // Penanda bahwa siswa ini punya absensi di kelas tsb tapi
                    // data induknya menyebut kelas lain (atau belum berkelas).
                    // Ditampilkan terang-terangan supaya wali kelas tahu ada
                    // yang perlu dibereskan TU, bukan dibiarkan diam-diam.
                    'anggotaResmi' => (int) $siswa->kelas_id === (int) $kelasAktif->id,
                    'hadir'   => $hadir,
                    'izin'    => $izin,
                    'sakit'   => $sakit,
                    'alpha'   => $alpha,
                    'total'   => $total,
                    // Siswa yang belum pernah tercatat sama sekali diberi null,
                    // bukan 0%. Keduanya berbeda arti: "belum ada data" tidak
                    // sama dengan "tidak pernah hadir", dan menyamakannya bisa
                    // membuat wali kelas menegur siswa yang tidak bersalah.
                    'persen'  => $total > 0 ? round(($hadir / $total) * 100, 1) : null,
                ];
            })
            // Siswa pindahan dikeluarkan dari kartu dashboard. Tempatnya di
            // halaman Rekap Absensi Kelas, pada bagian "Riwayat Siswa yang
            // Sudah Pindah Kelas" -- di sana ada ruang untuk menjelaskan
            // statusnya, sementara kartu ringkas ini tidak.
            ->filter(fn ($baris) => $baris->anggotaResmi)
            ->values();
        }

        $totalAbsensi = array_sum($rekapAbsensi);
        $persenKehadiran = $totalAbsensi > 0
            ? round(($rekapAbsensi['Hadir'] / $totalAbsensi) * 100, 1)
            : 0;

        return view('walikelas.dashboard', compact(
            'kelasDiampu',
            'kelasAktif',
            'kelasId',
            'semesterList',
            'semesterId',
            'rekapAbsensi',
            'persenKehadiran',
            'riwayatKonseling',
            'siswaBermasalah',
            'rekapPerSiswa'
        ));
    }
}