<?php

namespace App\Http\Controllers\Kepsek;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\AntrianKonseling;
use App\Models\JurnalLayanan;
use App\Models\Kelas;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * Dashboard monitoring untuk Kepala Sekolah.
     * Bisa difilter per semester & per kelas.
     */
    public function index(Request $request)
    {
        return view('kepsek.dashboard', $this->buildData($request));
    }

    /**
     * Bangun seluruh data monitoring Kepsek (8 Area Monitoring).
     *
     * Dipisah dari index() supaya Prioritas 7 (LaporanExportController, PDF &
     * Excel) bisa memakai PERSIS logika & angka yang sama dengan yang tampil
     * di dashboard -- tidak ada query terpisah yang bisa "drift" / beda hasil
     * dari dashboard aslinya.
     */
    public function buildData(Request $request): array
    {
        $semesterList = Semester::orderByDesc('tanggal_mulai')->get();
        $kelasList = Kelas::orderBy('nama_kelas')->get();

        $semesterId = $request->semester_id ?? Semester::where('status_aktif', true)->value('id');
        $kelasId = $request->kelas_id;

        // ============ KARTU RINGKASAN ============
        $totalSiswa = Siswa::when($kelasId, fn ($q) => $q->where('kelas_id', $kelasId))->count();
        $totalKelas = Kelas::count();

        $absensiQuery = Absensi::whereHas('sesiAbsensi', function ($q) use ($semesterId, $kelasId) {
            if ($semesterId) {
                $q->where('semester_id', $semesterId);
            }
            if ($kelasId) {
                $q->where('kelas_id', $kelasId);
            }
        });

        $rekapAbsensi = [
            'Hadir' => (clone $absensiQuery)->where('status', 'Hadir')->count(),
            'Izin' => (clone $absensiQuery)->where('status', 'Izin')->count(),
            'Sakit' => (clone $absensiQuery)->where('status', 'Sakit')->count(),
            'Alpha' => (clone $absensiQuery)->where('status', 'Alpha')->count(),
        ];
        $totalAbsensi = array_sum($rekapAbsensi);
        $persenKehadiran = $totalAbsensi > 0
            ? round(($rekapAbsensi['Hadir'] / $totalAbsensi) * 100, 1)
            : 0;

        // ============ GRAFIK: TREN KEHADIRAN PER BULAN (SEMESTER AKTIF) ============
        $trenAbsensiPerBulan = Absensi::whereHas('sesiAbsensi', function ($q) use ($semesterId, $kelasId) {
                if ($semesterId) {
                    $q->where('semester_id', $semesterId);
                }
                if ($kelasId) {
                    $q->where('kelas_id', $kelasId);
                }
            })
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bulan, status, COUNT(*) as jumlah")
            ->groupBy('bulan', 'status')
            ->orderBy('bulan')
            ->get()
            ->groupBy('bulan');

        $labelBulan = $trenAbsensiPerBulan->keys()->values();
        $dataHadir = $labelBulan->map(fn ($b) => optional($trenAbsensiPerBulan[$b]->firstWhere('status', 'Hadir'))->jumlah ?? 0);
        $dataAlpha = $labelBulan->map(fn ($b) => optional($trenAbsensiPerBulan[$b]->firstWhere('status', 'Alpha'))->jumlah ?? 0);

        // ============ DATA JURNAL LAYANAN "EFEKTIF" (GABUNGAN ANTREAN + MANUAL) ============
        // PENTING: jurnal hasil dari alur antrean (booking siswa -> dipanggil Guru BK)
        // TIDAK mengisi kolom siswa_id / guru_bk_id / tanggal_konseling secara langsung
        // di tabel jurnal_layanans -- data itu "nempel" lewat relasi antrian->siswa dan
        // antrian->sesi->guru_bk_id (lihat GuruBK\JurnalLayananController::storeJurnal).
        // Hanya jurnal individu manual yang mengisi kolom-kolom itu langsung.
        // Kalau agregasi di bawah cuma baca kolom langsung, datanya akan meleset besar
        // (jurnal dari alur antrean akan hilang dari hitungan). Makanya digabung dulu
        // di sini jadi satu bentuk data yang seragam & konsisten dipakai di semua
        // agregasi Kepsek (Prioritas 1-5), sekaligus jadi dasar $totalKonseling &
        // $rekapPerKelas->konseling supaya tidak lagi under-count seperti sebelumnya.
        $jurnalEfektif = JurnalLayanan::with(['siswa.kelas', 'antrian.siswa.kelas', 'antrian.sesi'])
            ->get()
            ->map(function ($j) {
                $siswa = $j->siswa ?? optional($j->antrian)->siswa;
                $guruBkId = $j->guru_bk_id ?? optional(optional($j->antrian)->sesi)->guru_bk_id;
                $tanggal = $j->tanggal_konseling ?? optional($j->antrian)->updated_at;

                return (object) [
                    // siswa_id ditambahkan supaya jurnal ini bisa diagregasi PER SISWA
                    // (dipakai di $siswaBerisiko), tidak cuma per kelas seperti sebelumnya.
                    'siswa_id' => optional($siswa)->id,
                    'kelas_id' => optional($siswa)->kelas_id,
                    'guru_bk_id' => $guruBkId,
                    'tanggal' => $tanggal ? Carbon::parse($tanggal)->toDateString() : null,
                    'kategori_masalah' => $j->kategori_masalah,
                    'status_kasus' => $j->status_kasus,
                    // Area Monitoring #7: kolom ini melekat langsung di jurnal_layanans
                    // (bukan di antrian), jadi berlaku sama untuk jurnal manual maupun
                    // jurnal dari alur antrean.
                    'tingkat_pelanggaran' => $j->tingkat_pelanggaran,
                    'panggil_ortu' => (bool) $j->panggil_ortu,
                    'jadwal_pertemuan_ortu' => $j->jadwal_pertemuan_ortu,
                ];
            });

        if ($kelasId) {
            $jurnalEfektif = $jurnalEfektif->where('kelas_id', (int) $kelasId)->values();
        }

        $totalKonseling = $jurnalEfektif->count();

        // ============ GRAFIK: JUMLAH LAYANAN KONSELING PER KELAS ============
        $konselingPerKelas = Kelas::withCount('siswas')
            ->orderBy('nama_kelas')
            ->get()
            ->map(function ($k) use ($jurnalEfektif) {
                $k->jumlah_konseling = $jurnalEfektif->where('kelas_id', $k->id)->count();

                return $k;
            });

        // ============ PRIORITAS 1: STATUS TINDAK LANJUT KASUS ============
        $rekapStatusKasus = [
            'Selesai' => $jurnalEfektif->where('status_kasus', 'Selesai')->count(),
            'Dipantau' => $jurnalEfektif->where('status_kasus', 'Dipantau')->count(),
            'Referal' => $jurnalEfektif->where('status_kasus', 'Referal')->count(),
        ];
        $totalKasusTerbuka = $rekapStatusKasus['Dipantau'] + $rekapStatusKasus['Referal'];

        // ============ PRIORITAS 3: PROPORSI JENIS LAYANAN (KATEGORI MASALAH) ============
        // Daftar kategori mengikuti opsi yang sudah dipakai di form Guru BK
        // (lihat resources/views/gurubk/rekap-laporan/index.blade.php)
        $daftarKategoriMasalah = ['Pribadi', 'Sosial', 'Belajar', 'Karir'];
        $proporsiKategoriMasalah = collect($daftarKategoriMasalah)
            ->mapWithKeys(fn ($k) => [$k => $jurnalEfektif->where('kategori_masalah', $k)->count()]);

        // ============ REKAP PER KELAS + PRIORITAS 2: PETA KERAWANAN (TRAFFIC LIGHT) ============
        $rekapPerKelas = Kelas::withCount('siswas')
            ->when($kelasId, fn ($q) => $q->where('id', $kelasId))
            ->orderBy('nama_kelas')
            ->get()
            ->map(function ($k) use ($semesterId, $jurnalEfektif) {
                $hadir = Absensi::whereHas('sesiAbsensi', function ($q) use ($k, $semesterId) {
                    $q->where('kelas_id', $k->id);
                    if ($semesterId) {
                        $q->where('semester_id', $semesterId);
                    }
                })->where('status', 'Hadir')->count();

                $alpha = Absensi::whereHas('sesiAbsensi', function ($q) use ($k, $semesterId) {
                    $q->where('kelas_id', $k->id);
                    if ($semesterId) {
                        $q->where('semester_id', $semesterId);
                    }
                })->where('status', 'Alpha')->count();

                $jurnalKelas = $jurnalEfektif->where('kelas_id', $k->id);
                $konseling = $jurnalKelas->count();
                $kasusTerbuka = $jurnalKelas->whereIn('status_kasus', ['Dipantau', 'Referal'])->count();

                // Skor kerawanan sederhana: jumlah Alpha + kasus terbuka (dibobot 2x
                // karena menyangkut isu personal, bukan sekadar kehadiran).
                // Threshold berikut CONTOH awal -- silakan disesuaikan dengan kondisi
                // riil sekolah (mis. dibuat proporsional terhadap jumlah siswa per kelas).
                $skor = $alpha + ($kasusTerbuka * 2);
                if ($skor <= 5) {
                    $levelKerawanan = 'Hijau';
                } elseif ($skor <= 15) {
                    $levelKerawanan = 'Kuning';
                } else {
                    $levelKerawanan = 'Merah';
                }

                return (object) [
                    'kelas' => $k,
                    'hadir' => $hadir,
                    'alpha' => $alpha,
                    'konseling' => $konseling,
                    'kasusTerbuka' => $kasusTerbuka,
                    'skorKerawanan' => $skor,
                    'levelKerawanan' => $levelKerawanan,
                ];
            });

        // ============ PRIORITAS 6 / AREA MONITORING #4: SISWA BERISIKO LINTAS KELAS ============
        // Berbeda dari $rekapPerKelas di atas (agregat PER KELAS), ini daftar level
        // INDIVIDU siswa lintas kelas, supaya Kepsek langsung tahu SIAPA yang perlu
        // diperhatikan -- bukan cuma kelas mana yang rawan. Pendekatannya meniru
        // $siswaBermasalah di WaliKelas\DashboardController, tapi digabung untuk
        // semua kelas (atau satu kelas saja kalau filter kelas_id dipakai) dan
        // ditambah sinyal kasus terbuka + siswa yang belum pernah tersentuh BK.
        $alphaPerSiswa = Absensi::whereHas('sesiAbsensi', function ($q) use ($semesterId, $kelasId) {
                if ($semesterId) {
                    $q->where('semester_id', $semesterId);
                }
                if ($kelasId) {
                    $q->where('kelas_id', $kelasId);
                }
            })
            ->where('status', 'Alpha')
            ->selectRaw('siswa_id, COUNT(*) as jumlah_alpha')
            ->groupBy('siswa_id')
            ->pluck('jumlah_alpha', 'siswa_id');

        $kasusTerbukaPerSiswa = $jurnalEfektif
            ->filter(fn ($j) => $j->siswa_id && in_array($j->status_kasus, ['Dipantau', 'Referal']))
            ->groupBy('siswa_id')
            ->map->count();

        $konselingPerSiswa = $jurnalEfektif
            ->filter(fn ($j) => $j->siswa_id)
            ->groupBy('siswa_id')
            ->map->count();

        // Ambang batas Alpha untuk sinyal "belum tersentuh BK padahal datanya
        // mengkhawatirkan". Nilai awal, silakan disesuaikan dengan kondisi riil
        // sekolah -- sama seperti threshold kerawanan kelas di $rekapPerKelas.
        $ambangAlphaBelumTersentuh = 5;

        $siswaBerisiko = Siswa::with(['user', 'kelas'])
            ->when($kelasId, fn ($q) => $q->where('kelas_id', $kelasId))
            ->get()
            ->map(function ($s) use ($alphaPerSiswa, $kasusTerbukaPerSiswa, $konselingPerSiswa, $ambangAlphaBelumTersentuh) {
                $alpha = (int) ($alphaPerSiswa[$s->id] ?? 0);
                $kasusTerbuka = (int) ($kasusTerbukaPerSiswa[$s->id] ?? 0);
                $totalKonseling = (int) ($konselingPerSiswa[$s->id] ?? 0);
                $belumTersentuhBk = $alpha >= $ambangAlphaBelumTersentuh && $totalKonseling === 0;

                return (object) [
                    'siswa' => $s,
                    'alpha' => $alpha,
                    'kasusTerbuka' => $kasusTerbuka,
                    'totalKonseling' => $totalKonseling,
                    'belumTersentuhBk' => $belumTersentuhBk,
                    // Skor konsisten dengan bobot skor kerawanan per kelas di atas
                    // (Alpha + kasus terbuka x2), supaya kedua tampilan tidak
                    // memakai logika yang berbeda-beda.
                    'skorRisiko' => $alpha + ($kasusTerbuka * 2),
                ];
            })
            // Hanya tampilkan siswa yang memang punya sinyal risiko, supaya daftar
            // tidak dipenuhi siswa yang datanya baik-baik saja.
            ->filter(fn ($x) => $x->skorRisiko > 0 || $x->belumTersentuhBk)
            ->sortByDesc(fn ($x) => $x->skorRisiko + ($x->belumTersentuhBk ? 1000 : 0))
            ->take(20)
            ->values();


        // Sengaja HANYA guru_bk_id, tanggal, dan jumlah entri per hari -- TIDAK menarik
        // uraian_masalah / pendekatan_teknik, supaya kerahasiaan konseling tetap terjaga
        // (lihat catatan kerahasiaan di dokumen Kebutuhan Monitoring Kepsek).
        $logAktivitasGuruBk = $jurnalEfektif
            ->filter(fn ($j) => $j->guru_bk_id && $j->tanggal)
            ->groupBy(fn ($j) => $j->guru_bk_id.'|'.$j->tanggal)
            ->map(function ($group) {
                $first = $group->first();

                return (object) [
                    'guru_bk_id' => $first->guru_bk_id,
                    'tanggal' => $first->tanggal,
                    'jumlah_entri' => $group->count(),
                ];
            })
            ->sortByDesc('tanggal')
            ->take(30)
            ->values();

        // ============ PRIORITAS 5: BEBAN KERJA & PEMERATAAN LAYANAN GURU BK ============
        // Rasio ideal 1 Guru BK : 150 siswa (dipakai sebagai penanda peringatan saja,
        // bukan pembatas keras).
        $bebanGuruBk = User::role('Guru BK')
            ->orderBy('name')
            ->get()
            ->map(function ($guru) use ($jurnalEfektif) {
                $kelasIdDiampu = $guru->kelasDiampu()->pluck('id');
                $jumlahSiswaBinaan = Siswa::whereIn('kelas_id', $kelasIdDiampu)->count();
                $jumlahSesi = $jurnalEfektif->where('guru_bk_id', $guru->id)->count();

                return (object) [
                    'nama' => $guru->name,
                    'jumlahKelas' => $kelasIdDiampu->count(),
                    'jumlahSiswaBinaan' => $jumlahSiswaBinaan,
                    'jumlahSesi' => $jumlahSesi,
                    'melebihiRasioIdeal' => $jumlahSiswaBinaan > 150,
                ];
            });

        // ============ AREA MONITORING #6: KEPATUHAN WAKTU LAYANAN ============
        // Waktu tunggu = waktu_dipanggil - waktu_booking, dihitung HANYA dari
        // antrean yang sudah pernah dipanggil (waktu_dipanggil terisi). Kolom ini
        // baru ditambahkan khusus untuk metrik ini -- lihat migration
        // ..._tambah_waktu_dipanggil_ke_antrian_konselings dan
        // GuruBK\SesiKonselingController::panggilBerikutnya().
        // sesi_konselings tidak punya semester_id, jadi filter semester dilakukan
        // lewat rentang tanggal_mulai/tanggal_selesai semester terhadap sesi.tanggal.
        $semesterAktif = $semesterId ? Semester::find($semesterId) : null;

        $antrianDipanggil = AntrianKonseling::with(['siswa.kelas', 'sesi'])
            ->whereNotNull('waktu_dipanggil')
            ->whereHas('sesi', function ($q) use ($semesterAktif) {
                if ($semesterAktif) {
                    $q->whereBetween('tanggal', [$semesterAktif->tanggal_mulai, $semesterAktif->tanggal_selesai]);
                }
            })
            ->when($kelasId, fn ($q) => $q->whereHas('siswa', fn ($qs) => $qs->where('kelas_id', $kelasId)))
            ->get();

        $waktuTungguMenit = $antrianDipanggil->map(fn ($a) => $a->waktu_booking->diffInMinutes($a->waktu_dipanggil));

        $totalAntreanDipanggil = $antrianDipanggil->count();
        $rataRataWaktuTunggu = $waktuTungguMenit->isNotEmpty() ? round($waktuTungguMenit->avg(), 1) : null;
        $maksWaktuTunggu = $waktuTungguMenit->isNotEmpty() ? $waktuTungguMenit->max() : null;

        // Ambang waktu tunggu "lama" -- nilai awal, silakan disesuaikan dengan
        // kondisi riil sekolah (mis. SOP kecepatan layanan BK di sekolah tsb).
        $ambangWaktuTungguLamaMenit = 30;
        $totalAntreanTungguLama = $waktuTungguMenit->filter(fn ($m) => $m > $ambangWaktuTungguLamaMenit)->count();

        $waktuTungguPerGuru = $antrianDipanggil
            ->filter(fn ($a) => optional($a->sesi)->guru_bk_id)
            ->groupBy(fn ($a) => $a->sesi->guru_bk_id)
            ->map(function ($group, $guruId) {
                $menit = $group->map(fn ($a) => $a->waktu_booking->diffInMinutes($a->waktu_dipanggil));

                return (object) [
                    'guru_bk_id' => $guruId,
                    'jumlahAntrean' => $group->count(),
                    'rataRataMenit' => round($menit->avg(), 1),
                    'maksMenit' => $menit->max(),
                ];
            })
            ->sortByDesc('rataRataMenit')
            ->values();

        // $namaGuruBkMap dipakai untuk Prioritas 4 (log aktivitas) DAN Area
        // Monitoring #6 (waktu tunggu per Guru BK) -- disatukan di sini supaya
        // kedua tabel tidak perlu 2 query User yang terpisah.
        $namaGuruBkMap = User::whereIn('id', $logAktivitasGuruBk->pluck('guru_bk_id')
                ->merge($waktuTungguPerGuru->pluck('guru_bk_id'))
                ->unique())
            ->pluck('name', 'id');

        // ============ AREA MONITORING #7: KOLABORASI GURU BK - WALI KELAS - ORANG TUA ============
        // Kasus yang "butuh keterlibatan orang tua" = panggil_ortu dicentang Guru BK
        // ATAU tingkat_pelanggaran = Berat -- sama persis dengan syarat yang dipakai
        // FonnteService/JurnalLayananController untuk memicu WA ke orang tua DAN
        // (sejak revisi ini) notifikasi in-app ke Wali Kelas. Kepsek perlu tahu
        // seberapa banyak kasus begini, dan berapa yang SUDAH punya jadwal
        // pertemuan konkret vs yang masih menggantung tanpa jadwal.
        $kasusLibatkanOrtu = $jurnalEfektif->filter(fn ($j) => $j->panggil_ortu || $j->tingkat_pelanggaran === 'Berat');

        $totalKasusLibatkanOrtu = $kasusLibatkanOrtu->count();
        $totalSudahAdaJadwal = $kasusLibatkanOrtu->filter(fn ($j) => filled($j->jadwal_pertemuan_ortu))->count();
        $totalBelumAdaJadwal = $totalKasusLibatkanOrtu - $totalSudahAdaJadwal;

        // Rekap per kelas, supaya Kepsek tahu kelas mana (dan Wali Kelas siapa)
        // yang paling banyak kasus kolaborasi orang tua & mana yang masih
        // menggantung tanpa jadwal pertemuan.
        $kolaborasiPerKelas = Kelas::with('waliKelas')
            ->when($kelasId, fn ($q) => $q->where('id', $kelasId))
            ->orderBy('nama_kelas')
            ->get()
            ->map(function ($k) use ($kasusLibatkanOrtu) {
                $kasusKelas = $kasusLibatkanOrtu->where('kelas_id', $k->id);
                $sudahAdaJadwal = $kasusKelas->filter(fn ($j) => filled($j->jadwal_pertemuan_ortu))->count();

                return (object) [
                    'kelas' => $k,
                    'totalKasus' => $kasusKelas->count(),
                    'sudahAdaJadwal' => $sudahAdaJadwal,
                    'belumAdaJadwal' => $kasusKelas->count() - $sudahAdaJadwal,
                ];
            })
            // Hanya tampilkan kelas yang memang ada kasusnya, supaya tabel tidak
            // dipenuhi kelas yang tidak relevan untuk Area Monitoring ini.
            ->filter(fn ($x) => $x->totalKasus > 0)
            ->sortByDesc('totalKasus')
            ->values();

        // ============ AREA MONITORING #8: EVALUASI HASIL (BUKAN CUMA PROSES) ============
        // Dua indikator sederhana sesuai kebutuhan dokumen:
        // (a) apakah KEHADIRAN siswa yang sudah dikonseling membaik dibanding sebelum
        //     ia dikonseling (dibandingkan 1 bulan sebelum vs 1 bulan sesudah tanggal
        //     konseling PERTAMA siswa tsb, supaya tidak kena batas ujung semester);
        // (b) apakah proporsi KASUS BERULANG (siswa yang sudah pernah dikonseling
        //     sebelumnya, muncul lagi dengan kasus baru) menurun dari bulan ke bulan.
        // Dihitung dari histori jurnalEfektif & Absensi yang SUDAH ada, tidak perlu
        // migration baru -- sesuai catatan di dokumen kebutuhan (data tersedia, yang
        // belum ada baru logika perbandingan antar-bulan/semester-nya).

        // --- (a) Kehadiran sebelum vs sesudah konseling pertama, per siswa ---
        $tanggalKonselingPertamaPerSiswa = $jurnalEfektif
            ->filter(fn ($j) => $j->siswa_id && $j->tanggal)
            ->groupBy('siswa_id')
            ->map(fn ($group) => $group->min('tanggal'));

        $evaluasiKehadiran = collect();
        $totalMembaikKehadiran = 0;
        $totalMemburukKehadiran = 0;
        $totalTetapKehadiran = 0;
        $rataRataPersenSebelum = null;
        $rataRataPersenSesudah = null;

        if ($tanggalKonselingPertamaPerSiswa->isNotEmpty()) {
            $siswaMap = Siswa::with(['user', 'kelas'])
                ->whereIn('id', $tanggalKonselingPertamaPerSiswa->keys())
                ->get()
                ->keyBy('id');

            // Ambil histori absensi via sesiAbsensi->tanggal (bukan waktu_scan, karena
            // status Alpha/Izin/Sakit biasanya tidak punya waktu_scan) -- pola yang
            // sama dipakai di $rekapPerKelas untuk hitung hadir/alpha per kelas.
            $absensiSiswaDievaluasi = Absensi::with('sesiAbsensi')
                ->whereIn('siswa_id', $tanggalKonselingPertamaPerSiswa->keys())
                ->get()
                ->filter(fn ($a) => $a->sesiAbsensi)
                ->groupBy('siswa_id');

            $evaluasiKehadiran = $tanggalKonselingPertamaPerSiswa
                ->map(function ($tanggalPertama, $siswaId) use ($absensiSiswaDievaluasi, $siswaMap) {
                    $tglPertama = Carbon::parse($tanggalPertama);
                    $awalSebelum = $tglPertama->copy()->subMonth();
                    $akhirSesudah = $tglPertama->copy()->addMonth();

                    $absensiSiswa = $absensiSiswaDievaluasi->get($siswaId, collect());

                    $sebelum = $absensiSiswa->filter(fn ($a) => $a->sesiAbsensi->tanggal->between($awalSebelum, $tglPertama));
                    $sesudah = $absensiSiswa->filter(fn ($a) => $a->sesiAbsensi->tanggal->between($tglPertama, $akhirSesudah));

                    $persenSebelum = $sebelum->count() > 0
                        ? round($sebelum->where('status', 'Hadir')->count() / $sebelum->count() * 100, 1)
                        : null;
                    $persenSesudah = $sesudah->count() > 0
                        ? round($sesudah->where('status', 'Hadir')->count() / $sesudah->count() * 100, 1)
                        : null;

                    return (object) [
                        'siswa' => $siswaMap->get($siswaId),
                        'tanggalPertama' => $tglPertama,
                        'persenSebelum' => $persenSebelum,
                        'persenSesudah' => $persenSesudah,
                        'selisih' => ($persenSebelum !== null && $persenSesudah !== null) ? round($persenSesudah - $persenSebelum, 1) : null,
                    ];
                })
                // Hanya siswa yang punya data absensi di KEDUA sisi (sebelum & sesudah),
                // supaya perbandingan tidak menyesatkan (mis. siswa baru dikonseling
                // minggu ini belum bisa dievaluasi hasilnya).
                ->filter(fn ($x) => $x->persenSebelum !== null && $x->persenSesudah !== null)
                // Kasus yang MEMBURUK ditampilkan lebih dulu, supaya jadi perhatian utama Kepsek.
                ->sortBy('selisih')
                ->values();

            $totalMembaikKehadiran = $evaluasiKehadiran->where('selisih', '>', 0)->count();
            $totalMemburukKehadiran = $evaluasiKehadiran->where('selisih', '<', 0)->count();
            $totalTetapKehadiran = $evaluasiKehadiran->where('selisih', 0)->count();
            $rataRataPersenSebelum = $evaluasiKehadiran->isNotEmpty() ? round($evaluasiKehadiran->avg('persenSebelum'), 1) : null;
            $rataRataPersenSesudah = $evaluasiKehadiran->isNotEmpty() ? round($evaluasiKehadiran->avg('persenSesudah'), 1) : null;
        }

        $totalSiswaDievaluasiKehadiran = $evaluasiKehadiran->count();

        // --- (b) Tren kasus berulang per bulan ---
        // "Berulang" = siswa yang saat kasus ini dibuat SUDAH PERNAH punya jurnal
        // layanan sebelumnya (kapan pun, tidak dibatasi filter semester/kelas di atas
        // supaya riwayat lamanya tetap terhitung). Diurutkan kronologis lalu ditandai
        // per bulan, supaya Kepsek bisa lihat apakah proporsi kasus berulang
        // menurun/naik dari bulan ke bulan.
        $kasusUrutWaktu = $jurnalEfektif
            ->filter(fn ($j) => $j->siswa_id && $j->tanggal)
            ->sortBy('tanggal')
            ->values();

        $siswaSudahPernah = [];
        $trenKasusBulanan = collect();

        foreach ($kasusUrutWaktu as $j) {
            $bulan = Carbon::parse($j->tanggal)->format('Y-m');
            $isBerulang = isset($siswaSudahPernah[$j->siswa_id]);
            $siswaSudahPernah[$j->siswa_id] = true;

            if (! $trenKasusBulanan->has($bulan)) {
                $trenKasusBulanan->put($bulan, (object) [
                    'bulan' => $bulan,
                    'totalKasus' => 0,
                    'kasusBerulang' => 0,
                ]);
            }

            $trenKasusBulanan[$bulan]->totalKasus++;
            if ($isBerulang) {
                $trenKasusBulanan[$bulan]->kasusBerulang++;
            }
        }

        $trenKasusBulanan = $trenKasusBulanan
            ->sortKeys()
            ->map(function ($row) {
                $row->persenBerulang = $row->totalKasus > 0
                    ? round($row->kasusBerulang / $row->totalKasus * 100, 1)
                    : 0;

                return $row;
            })
            ->values();

        return compact(
            'semesterList',
            'kelasList',
            'semesterId',
            'kelasId',
            'totalSiswa',
            'totalKelas',
            'rekapAbsensi',
            'persenKehadiran',
            'totalKonseling',
            'labelBulan',
            'dataHadir',
            'dataAlpha',
            'konselingPerKelas',
            'rekapPerKelas',
            'siswaBerisiko',
            'rekapStatusKasus',
            'totalKasusTerbuka',
            'proporsiKategoriMasalah',
            'logAktivitasGuruBk',
            'namaGuruBkMap',
            'bebanGuruBk',
            'totalAntreanDipanggil',
            'rataRataWaktuTunggu',
            'maksWaktuTunggu',
            'ambangWaktuTungguLamaMenit',
            'totalAntreanTungguLama',
            'waktuTungguPerGuru',
            'totalKasusLibatkanOrtu',
            'totalSudahAdaJadwal',
            'totalBelumAdaJadwal',
            'kolaborasiPerKelas',
            'evaluasiKehadiran',
            'totalSiswaDievaluasiKehadiran',
            'totalMembaikKehadiran',
            'totalMemburukKehadiran',
            'totalTetapKehadiran',
            'rataRataPersenSebelum',
            'rataRataPersenSesudah',
            'trenKasusBulanan'
        );
    }
}