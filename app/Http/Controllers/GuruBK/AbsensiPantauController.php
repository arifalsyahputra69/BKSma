<?php

namespace App\Http\Controllers\GuruBK;

use App\Exports\AbsensiPantauKelasExport;
use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Semester;
use App\Models\SesiAbsensi;
use App\Models\Siswa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * PERBAIKAN AUDIT (Poin 2):
 * Sebelumnya role Guru BK tidak punya menu untuk memantau absensi
 * siswa binaannya (padahal outline mensyaratkan ini). Guru BK cuma
 * bisa lihat rekap konseling (JurnalLayananController), bukan absensi.
 *
 * Controller ini dibuat dengan pola yang sama seperti
 * WaliKelas\DashboardController, supaya konsisten dengan struktur
 * project yang sudah ada -- bedanya Guru BK bisa membina LEBIH DARI
 * SATU kelas sekaligus (lihat User::kelasDiampu()), jadi ada opsi
 * filter "Semua Kelas Binaan" atau per kelas.
 */
class AbsensiPantauController extends Controller
{
    public function index(Request $request)
    {
        $guruBkId = Auth::id();

        $kelasDiampu = Kelas::where('id_guru_bk', $guruBkId)->orderBy('nama_kelas')->get();

        $semesterList = Semester::orderByDesc('tanggal_mulai')->get();
        $semesterId = $request->semester_id ?? Semester::where('status_aktif', true)->value('id');

        // PERBAIKAN (30 Juli 2026): halaman ini sekarang punya 2 tab --
        // "Hari Ini" (default, grid harian per sesi meniru format daftar
        // hadir kertas) dan "Rekap Periode" (rekap semester yang sudah ada
        // sebelumnya). Sebelumnya cuma ada tampilan semester yang bikin Guru
        // BK bingung kalau mau cek perkembangan absensi HARI INI saja.
        $tab = $request->input('tab', 'hari-ini');

        // kelas_id kosong / "semua" -> gabungkan seluruh kelas binaan Guru BK ini
        // (khusus untuk tab Rekap Periode). Untuk tab Hari Ini, kalau Guru BK
        // cuma membina 1 kelas, otomatis dipilihkan supaya tidak perlu klik
        // filter dulu tiap buka halaman.
        $kelasId = $request->kelas_id
            ?? ($kelasDiampu->count() === 1 ? (string) $kelasDiampu->first()->id : '');
        $kelasIds = $kelasId !== ''
            ? [$kelasId]
            : $kelasDiampu->pluck('id')->toArray();

        // ============ TAB "HARI INI": grid per sesi hari itu ============
        $tanggal = $request->filled('tanggal')
            ? Carbon::parse($request->input('tanggal'))
            : Carbon::today();

        $gridHariIni = null;
        if ($kelasId !== '') {
            $kelasTerpilih = $kelasDiampu->firstWhere('id', (int) $kelasId);
            if ($kelasTerpilih) {
                $gridHariIni = $this->buildGridHarian($kelasTerpilih, $tanggal);
            }
        }

        $rekapAbsensi = ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alpha' => 0];
        $siswaBermasalah = collect();
        $buktiKeterangan = collect();

        if (!empty($kelasIds)) {
            $absensiQuery = Absensi::whereHas('sesiAbsensi', function ($q) use ($kelasIds, $semesterId) {
                $q->whereIn('kelas_id', $kelasIds);
                if ($semesterId) {
                    $q->where('semester_id', $semesterId);
                }
            });

            foreach (array_keys($rekapAbsensi) as $status) {
                $rekapAbsensi[$status] = (clone $absensiQuery)->where('status', $status)->count();
            }

            // Siswa dengan Alpha terbanyak di kelas-kelas binaan Guru BK ini
            $siswaBermasalah = Absensi::whereHas('sesiAbsensi', function ($q) use ($kelasIds, $semesterId) {
                    $q->whereIn('kelas_id', $kelasIds);
                    if ($semesterId) {
                        $q->where('semester_id', $semesterId);
                    }
                })
                ->where('status', 'Alpha')
                ->with('siswa.user', 'siswa.kelas')
                ->get()
                ->groupBy('siswa_id')
                ->map(fn ($rows) => (object) [
                    'siswa' => $rows->first()->siswa,
                    'jumlah_alpha' => $rows->count(),
                ])
                ->sortByDesc('jumlah_alpha')
                ->take(10)
                ->values();

            // FITUR BARU (9 Agustus 2026): daftar bukti keterangan Izin/Sakit
            // yang diunggah Guru Mapel (foto surat dokter / tangkapan layar
            // pesan orang tua). Sebelumnya bukti semacam ini berhenti di ponsel
            // guru yang menerimanya, jadi Guru BK tidak punya cara memeriksa
            // apakah ketidakhadiran siswa memang berdasar sebelum
            // menindaklanjutinya.
            //
            // Dibatasi 50 baris terbaru: ini panel pemantauan, bukan arsip.
            // Kalau semua bukti satu semester dimuat sekaligus, halamannya
            // berat sementara yang dibaca praktis cuma yang paling baru.
            $buktiKeterangan = (clone $absensiQuery)
                ->whereNotNull('bukti')
                ->with(['siswa.user', 'sesiAbsensi.kelas'])
                ->orderByDesc('id')
                ->limit(50)
                ->get();
        }

        $totalAbsensi = array_sum($rekapAbsensi);
        $persenKehadiran = $totalAbsensi > 0
            ? round(($rekapAbsensi['Hadir'] / $totalAbsensi) * 100, 1)
            : 0;

        return view('gurubk.absensi-pantau.index', compact(
            'kelasDiampu',
            'kelasId',
            'semesterList',
            'semesterId',
            'rekapAbsensi',
            'persenKehadiran',
            'siswaBermasalah',
            'buktiKeterangan',
            'tab',
            'tanggal',
            'gridHariIni'
        ));
    }

    /**
     * PERBAIKAN (30 Juli 2026): data untuk tab "Hari Ini" -- grid per siswa
     * x per sesi absensi (QR) yang dibuat Guru Mapel PADA TANGGAL tsb saja,
     * meniru format daftar hadir kertas yang sudah biasa dipakai (baris
     * siswa, kolom per jam pelajaran, kolom "Ket" untuk status I/S/A).
     */
    private function buildGridHarian(Kelas $kelas, Carbon $tanggal): array
    {
        $sesiHariIni = SesiAbsensi::where('kelas_id', $kelas->id)
            ->whereDate('tanggal', $tanggal)
            ->orderBy('id')
            ->get();

        $siswaKelas = Siswa::with('user')
            ->where('kelas_id', $kelas->id)
            ->get()
            ->sortBy(fn ($s) => optional($s->user)->name ?? '')
            ->values();

        $absensiHariIni = Absensi::whereIn('sesi_absensi_id', $sesiHariIni->pluck('id'))
            ->get()
            ->groupBy('siswa_id');

        $ringkasan = ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alpha' => 0];

        $rows = $siswaKelas->map(function ($siswa) use ($sesiHariIni, $absensiHariIni, &$ringkasan) {
            $absensiSiswa = $absensiHariIni->get($siswa->id, collect())->keyBy('sesi_absensi_id');

            $statusPerSesi = $sesiHariIni->map(fn ($sesi) => optional($absensiSiswa->get($sesi->id))->status);

            // Kolom "Ket" (keterangan): tandai kalau ADA minimal satu sesi
            // hari itu berstatus Alpha/Izin/Sakit -- prioritas Alpha > Izin
            // > Sakit kalau kebetulan campur, supaya yang paling perlu
            // perhatian yang ditampilkan (sama seperti kolom Ket di kertas).
            $ket = null;
            if ($statusPerSesi->contains('Alpha')) {
                $ket = 'A';
            } elseif ($statusPerSesi->contains('Izin')) {
                $ket = 'I';
            } elseif ($statusPerSesi->contains('Sakit')) {
                $ket = 'S';
            }

            foreach ($statusPerSesi as $status) {
                if ($status && isset($ringkasan[$status])) {
                    $ringkasan[$status]++;
                }
            }

            // Lampiran bukti Izin/Sakit hari itu (bisa lebih dari satu kalau
            // siswanya absen di beberapa jam pelajaran). Ditarik langsung ke
            // baris siswa supaya Guru BK bisa memeriksanya di tab "Hari Ini" --
            // justru di sinilah bukti paling dibutuhkan, karena keputusan
            // menindaklanjuti ketidakhadiran diambil pada hari itu juga, bukan
            // menunggu rekap satu semester.
            $buktiSiswa = $absensiSiswa
                ->filter(fn ($absen) => filled($absen->bukti))
                ->map(fn ($absen) => (object) [
                    'url' => $absen->urlBukti(),
                    'gambar' => $absen->buktiBerupaGambar(),
                    'status' => $absen->status,
                    'keterangan' => $absen->keterangan,
                    'mapel' => $sesiHariIni->firstWhere('id', $absen->sesi_absensi_id)->mapel ?? null,
                ])
                ->values();

            return (object) [
                'siswa_id' => $siswa->id,
                'nama' => optional($siswa->user)->name ?? '(tanpa nama)',
                'nisn' => $siswa->nisn ?: '-',
                'status_per_sesi' => $statusPerSesi,
                'ket' => $ket,
                'bukti' => $buktiSiswa,
            ];
        })->values();

        $totalRingkasan = array_sum($ringkasan);
        $persenKehadiran = $totalRingkasan > 0 ? round($ringkasan['Hadir'] / $totalRingkasan * 100, 1) : 0;

        return [
            'kelas' => $kelas,
            'tanggal' => $tanggal,
            'sesiList' => $sesiHariIni,
            'rows' => $rows,
            'ringkasan' => $ringkasan,
            'persenKehadiran' => $persenKehadiran,
        ];
    }

    /**
     * FITUR BARU (30 Juli 2026): unduh rekap absensi PDF.
     *
     * Sengaja dibatasi PER KELAS (bukan gabungan seluruh kelas binaan atau
     * seluruh sekolah) -- kalau belum memilih kelas spesifik di filter,
     * diarahkan kembali dengan pesan supaya pilih kelas dulu.
     */
    public function pdf(Request $request)
    {
        $kelas = $this->resolveKelasTerpilih($request->kelas_id);

        if (! $kelas) {
            return back()->with('error', 'Pilih salah satu kelas terlebih dahulu (bukan "Semua Kelas Binaan") sebelum mengunduh laporan absensi.');
        }

        $semesterId = $request->semester_id ?: null;
        $semester = $semesterId ? Semester::find($semesterId) : null;

        $data = $this->buildRekapPerSiswa($kelas, $semesterId);

        $pdf = Pdf::loadView('gurubk.absensi-pantau.pdf', [
            'kelas' => $kelas,
            'semester' => $semester,
            'rows' => $data['rows'],
            'ringkasan' => $data['ringkasan'],
            'persenKehadiran' => $data['persenKehadiran'],
        ])->setPaper('a4', 'portrait');

        $namaFile = 'absensi-'.Str::slug($kelas->nama_kelas).'-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($namaFile);
    }

    /**
     * FITUR BARU (30 Juli 2026): unduh rekap absensi Excel. Sama seperti
     * pdf(), sengaja dibatasi per kelas.
     */
    public function excel(Request $request)
    {
        $kelas = $this->resolveKelasTerpilih($request->kelas_id);

        if (! $kelas) {
            return back()->with('error', 'Pilih salah satu kelas terlebih dahulu (bukan "Semua Kelas Binaan") sebelum mengunduh laporan absensi.');
        }

        $semesterId = $request->semester_id ?: null;
        $semester = $semesterId ? Semester::find($semesterId) : null;

        $data = $this->buildRekapPerSiswa($kelas, $semesterId);

        $namaFile = 'absensi-'.Str::slug($kelas->nama_kelas).'-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new AbsensiPantauKelasExport($kelas, $semester, $data['rows'], $data['ringkasan'], $data['persenKehadiran']),
            $namaFile
        );
    }

    /**
     * Pastikan kelas yang mau diunduh laporannya benar-benar kelas binaan
     * Guru BK yang sedang login (proteksi IDOR) DAN benar-benar dipilih
     * secara spesifik (bukan kosong / "semua kelas binaan").
     */
    private function resolveKelasTerpilih($kelasId): ?Kelas
    {
        if (blank($kelasId)) {
            return null;
        }

        return Kelas::where('id_guru_bk', Auth::id())->where('id', $kelasId)->first();
    }

    /**
     * Rekap Hadir/Izin/Sakit/Alpha per siswa untuk satu kelas + semester
     * (semester null = semua semester). Dipakai bersama oleh pdf() & excel()
     * supaya angkanya selalu konsisten satu sama lain.
     */
    private function buildRekapPerSiswa(Kelas $kelas, ?int $semesterId): array
    {
        $siswaKelas = Siswa::with('user')->where('kelas_id', $kelas->id)->get();

        $absensiPerSiswa = Absensi::whereHas('sesiAbsensi', function ($q) use ($kelas, $semesterId) {
                $q->where('kelas_id', $kelas->id);
                if ($semesterId) {
                    $q->where('semester_id', $semesterId);
                }
            })
            ->get()
            ->groupBy('siswa_id');

        $rows = $siswaKelas->map(function ($siswa) use ($absensiPerSiswa) {
            $rekap = $absensiPerSiswa->get($siswa->id, collect());
            $hadir = $rekap->where('status', 'Hadir')->count();
            $izin = $rekap->where('status', 'Izin')->count();
            $sakit = $rekap->where('status', 'Sakit')->count();
            $alpha = $rekap->where('status', 'Alpha')->count();
            $total = $hadir + $izin + $sakit + $alpha;

            return (object) [
                'nama' => optional($siswa->user)->name ?? '(tanpa nama)',
                'nisn' => $siswa->nisn ?: '-',
                'hadir' => $hadir,
                'izin' => $izin,
                'sakit' => $sakit,
                'alpha' => $alpha,
                'total' => $total,
                'persen_hadir' => $total > 0 ? round($hadir / $total * 100, 1) : 0,
            ];
        })->sortBy('nama')->values();

        $ringkasan = [
            'Hadir' => $rows->sum('hadir'),
            'Izin' => $rows->sum('izin'),
            'Sakit' => $rows->sum('sakit'),
            'Alpha' => $rows->sum('alpha'),
        ];
        $totalRingkasan = array_sum($ringkasan);
        $persenKehadiran = $totalRingkasan > 0 ? round($ringkasan['Hadir'] / $totalRingkasan * 100, 1) : 0;

        return [
            'rows' => $rows,
            'ringkasan' => $ringkasan,
            'persenKehadiran' => $persenKehadiran,
        ];
    }
}
