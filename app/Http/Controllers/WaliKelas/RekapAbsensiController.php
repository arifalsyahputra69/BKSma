<?php

namespace App\Http\Controllers\WaliKelas;

use App\Exports\AbsensiPantauKelasExport;
use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Semester;
use App\Models\Siswa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * REKAP ABSENSI WALI KELAS (9 Agustus 2026)
 *
 * Dashboard Wali Kelas sudah memuat rekap per siswa, tapi hanya sebagai kartu
 * di antara ringkasan lain dan tanpa cara mengeluarkannya sebagai berkas.
 * Halaman ini berdiri sendiri supaya rekapnya bisa dibaca dengan tenang,
 * dicetak, dan diunduh sebagai PDF maupun Excel.
 *
 * Berkas keluarannya sengaja memakai ulang AbsensiPantauKelasExport dan blade
 * gurubk.absensi-pantau.pdf milik Guru BK. Membuat cetakan kedua yang isinya
 * sama hanya akan membuat dua laporan berbeda bentuk untuk data yang sama,
 * dan keduanya harus diperbaiki setiap kali ada perubahan.
 */
class RekapAbsensiController extends Controller
{
    public function index(Request $request)
    {
        $kelasDiampu = $this->kelasDiampu();

        $semesterList = Semester::orderByDesc('tanggal_mulai')->get();
        $semesterId = $request->semester_id ?? Semester::where('status_aktif', true)->value('id');

        $kelasId = $request->kelas_id ?? $kelasDiampu->first()?->id;
        $kelasAktif = $kelasDiampu->firstWhere('id', (int) $kelasId);

        $rekap = $kelasAktif
            ? $this->buildRekap($kelasAktif, $semesterId ? (int) $semesterId : null)
            : [
                'rows' => collect(),
                'rowsPindahan' => collect(),
                'ringkasan' => ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alpha' => 0],
                'persenKehadiran' => 0,
            ];

        // Lampiran Izin/Sakit (foto surat dokter, tangkapan layar pesan orang
        // tua) yang diunggah guru mapel. Wali kelas yang paling sering ditanya
        // orang tua soal ketidakhadiran anaknya, jadi buktinya perlu bisa
        // dibuka dari sini, bukan cuma tersimpan di ponsel guru mapel.
        $buktiKeterangan = $kelasAktif
            ? Absensi::whereHas('sesiAbsensi', function ($q) use ($kelasAktif, $semesterId) {
                    $q->where('kelas_id', $kelasAktif->id);
                    if ($semesterId) {
                        $q->where('semester_id', $semesterId);
                    }
                })
                ->whereNotNull('bukti')
                ->with(['siswa.user', 'sesiAbsensi'])
                ->orderByDesc('id')
                ->limit(50)
                ->get()
            : collect();

        return view('walikelas.rekap-absensi', [
            'kelasDiampu'     => $kelasDiampu,
            'buktiKeterangan' => $buktiKeterangan,
            'kelasAktif'      => $kelasAktif,
            'kelasId'         => $kelasId,
            'semesterList'    => $semesterList,
            'semesterId'      => $semesterId,
            'rows'            => $rekap['rows'],
            'rowsPindahan'    => $rekap['rowsPindahan'],
            'ringkasan'       => $rekap['ringkasan'],
            'persenKehadiran' => $rekap['persenKehadiran'],
        ]);
    }

    public function pdf(Request $request)
    {
        $kelas = $this->resolveKelas($request->kelas_id);

        if (! $kelas) {
            return back()->with('error', 'Pilih kelas terlebih dahulu sebelum mengunduh laporan.');
        }

        $semesterId = $request->semester_id ?: null;
        $semester = $semesterId ? Semester::find($semesterId) : null;

        $rekap = $this->buildRekap($kelas, $semesterId ? (int) $semesterId : null);

        $pdf = Pdf::loadView('gurubk.absensi-pantau.pdf', [
            'kelas'           => $kelas,
            'semester'        => $semester,
            'rows'            => $rekap['rows'],
            'ringkasan'       => $rekap['ringkasan'],
            'persenKehadiran' => $rekap['persenKehadiran'],
        ])->setPaper('a4', 'portrait');

        return $pdf->download(
            'absensi-'.Str::slug($kelas->nama_kelas).'-'.now()->format('Y-m-d').'.pdf'
        );
    }

    public function excel(Request $request)
    {
        $kelas = $this->resolveKelas($request->kelas_id);

        if (! $kelas) {
            return back()->with('error', 'Pilih kelas terlebih dahulu sebelum mengunduh laporan.');
        }

        $semesterId = $request->semester_id ?: null;
        $semester = $semesterId ? Semester::find($semesterId) : null;

        $rekap = $this->buildRekap($kelas, $semesterId ? (int) $semesterId : null);

        return Excel::download(
            new AbsensiPantauKelasExport(
                $kelas,
                $semester,
                $rekap['rows'],
                $rekap['ringkasan'],
                $rekap['persenKehadiran']
            ),
            'absensi-'.Str::slug($kelas->nama_kelas).'-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    private function kelasDiampu()
    {
        return Kelas::where('wali_kelas_id', Auth::id())
            ->orderBy('nama_kelas')
            ->get();
    }

    /**
     * Pastikan kelas yang diminta benar-benar kelas perwalian user yang login.
     * Tanpa penjagaan ini, siapa pun yang berperan Wali Kelas bisa mengunduh
     * rekap kelas lain hanya dengan mengganti angka di alamat URL.
     */
    private function resolveKelas($kelasId): ?Kelas
    {
        if (blank($kelasId)) {
            return null;
        }

        return Kelas::where('wali_kelas_id', Auth::id())
            ->where('id', $kelasId)
            ->first();
    }

    /**
     * Rekap Hadir/Izin/Sakit/Alpha per siswa untuk satu kelas.
     *
     * Daftar siswanya sengaja menggabungkan dua sumber: anggota resmi kelas
     * menurut tabel siswas, DITAMBAH siapa pun yang punya catatan absensi di
     * sesi kelas ini. Absensi terhubung ke kelas lewat sesinya, bukan lewat
     * data induk siswa, dan kedua jalur itu bisa berbeda isi -- misalnya saat
     * siswa dipindahkan ke kelas lain. Kalau hanya bersandar pada satu jalur,
     * rekapnya bisa kosong padahal absensinya jelas ada.
     *
     * PERBAIKAN (10 Agustus 2026): hasilnya sekarang DIPISAH jadi dua.
     *
     * Sebelumnya kedua sumber itu dituang ke satu tabel, dan siswa yang sudah
     * pindah kelas tetap nongkrong di rekap kelas lamanya sambil ikut menyeret
     * persentase kehadiran kelas -- padahal dia bukan anak didik wali kelas itu
     * lagi. Sekarang tabel utama hanya berisi anggota resmi, dan riwayat siswa
     * pindahan ditaruh di bagiannya sendiri.
     *
     * Riwayatnya tidak boleh sekadar disembunyikan: rekap kelas TUJUAN membaca
     * sesi milik kelas tujuan saja, jadi kalau di sini pun tidak ditampilkan,
     * catatan kehadiran itu lenyap dari seluruh halaman meski barisnya masih
     * ada di database.
     */
    private function buildRekap(Kelas $kelas, ?int $semesterId): array
    {
        $absensiPerSiswa = Absensi::whereHas('sesiAbsensi', function ($q) use ($kelas, $semesterId) {
                $q->where('kelas_id', $kelas->id);
                if ($semesterId) {
                    $q->where('semester_id', $semesterId);
                }
            })
            ->get()
            ->groupBy('siswa_id');

        $idBerabsensi = $absensiPerSiswa->keys();

        $siswaKelas = Siswa::with(['user', 'kelas'])
            ->where(function ($query) use ($kelas, $idBerabsensi) {
                $query->where('kelas_id', $kelas->id);

                if ($idBerabsensi->isNotEmpty()) {
                    $query->orWhereIn('id', $idBerabsensi);
                }
            })
            ->get();

        $rows = $siswaKelas->map(function ($siswa) use ($absensiPerSiswa, $kelas) {
            $baris = $absensiPerSiswa->get($siswa->id, collect());

            $hadir = $baris->where('status', 'Hadir')->count();
            $izin  = $baris->where('status', 'Izin')->count();
            $sakit = $baris->where('status', 'Sakit')->count();
            $alpha = $baris->where('status', 'Alpha')->count();
            $total = $hadir + $izin + $sakit + $alpha;

            return (object) [
                'nama'         => optional($siswa->user)->name ?? '(tanpa nama)',
                'nisn'         => $siswa->nisn ?: '-',
                'hadir'        => $hadir,
                'izin'         => $izin,
                'sakit'        => $sakit,
                'alpha'        => $alpha,
                'total'        => $total,
                'persen_hadir' => $total > 0 ? round($hadir / $total * 100, 1) : 0,
                'anggotaResmi' => (int) $siswa->kelas_id === (int) $kelas->id,
                'kelasSekarang' => $siswa->kelas?->nama_kelas,
            ];
        })->sortBy('nama')->values();

        $anggotaResmi = $rows->filter(fn ($baris) => $baris->anggotaResmi)->values();
        $pindahan = $rows->reject(fn ($baris) => $baris->anggotaResmi)->values();

        // Ringkasan & persentase dihitung dari anggota resmi saja. Angka di
        // kartu atas halaman harus menggambarkan kelas yang diampu wali kelas
        // saat ini, bukan tercampur riwayat siswa yang sudah pindah.
        $ringkasan = [
            'Hadir' => $anggotaResmi->sum('hadir'),
            'Izin'  => $anggotaResmi->sum('izin'),
            'Sakit' => $anggotaResmi->sum('sakit'),
            'Alpha' => $anggotaResmi->sum('alpha'),
        ];

        $totalRingkasan = array_sum($ringkasan);

        return [
            'rows'            => $anggotaResmi,
            'rowsPindahan'    => $pindahan,
            'ringkasan'       => $ringkasan,
            'persenKehadiran' => $totalRingkasan > 0
                ? round($ringkasan['Hadir'] / $totalRingkasan * 100, 1)
                : 0,
        ];
    }
}
