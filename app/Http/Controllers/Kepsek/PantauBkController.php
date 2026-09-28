<?php

// LETAKKAN DI: app/Http/Controllers/Kepsek/PantauBkController.php
//
// FITUR BARU (12 Agustus 2026): "Pantau BK" -- menggantikan halaman
// "Kunjungan Rumah" Kepsek yang lama (yang cuma menampilkan Home Visit
// saja). Halaman ini menggabungkan 3 jenis kegiatan pelayanan BK yang
// punya bukti foto/dokumentasi -- Kunjungan Rumah, Layanan Klasikal, dan
// Bimbingan Kelompok -- dalam satu daftar monitoring, supaya Kepsek bisa
// melihat & memverifikasi bahwa Guru BK benar-benar menjalankan layanan
// konseling (lewat foto bukti), tanpa perlu tahu isi/kasus siswa.
//
// Kerahasiaan (permintaan Guru BK, 12 Agustus 2026):
//   - Kunjungan Rumah: alasan_kunjungan, hasil_observasi, dan
//     kesepakatan_bersama TIDAK PERNAH ditarik ke sini. Nama siswa & kelas
//     TETAP ditampilkan -- ini keputusan eksplisit sekolah (halaman
//     Kunjungan Rumah Kepsek sebelumnya sudah begitu, dan sekolah memilih
//     mempertahankannya saat halaman ini digabung).
//   - Layanan Klasikal: kelas_target, metode_penyampaian,
//     materi_kompetensi, evaluasi_proses TIDAK PERNAH ditarik -- Kepsek
//     hanya melihat kalimat generik "melakukan layanan konseling klasikal".
//   - Bimbingan Kelompok: topik, daftar_anggota, dinamika_kelompok,
//     kesimpulan_hasil TIDAK PERNAH ditarik -- Kepsek hanya melihat
//     kalimat generik "melakukan bimbingan kelompok".
//   - Foto/dokumentasi TETAP ditampilkan untuk ketiga jenis kegiatan --
//     ini murni bukti administratif bahwa kegiatan benar terjadi, bukan
//     isi/konten rahasia percakapan konseling.

namespace App\Http\Controllers\Kepsek;

use App\Http\Controllers\Controller;
use App\Models\BimbinganKelompok;
use App\Models\KunjunganRumah;
use App\Models\LayananKlasikal;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class PantauBkController extends Controller
{
    public function index(Request $request)
    {
        $kunjungan = KunjunganRumah::with(['siswa.user', 'siswa.kelas', 'guruBk'])
            ->select(['id', 'siswa_id', 'guru_bk_id', 'tanggal_kunjungan', 'status', 'dokumentasi_path'])
            ->get()
            ->map(fn ($k) => (object) [
                'jenis' => 'Home Visit',
                'tanggal' => Carbon::parse($k->tanggal_kunjungan),
                'guruBk' => optional($k->guruBk)->name ?? '-',
                'siswa' => optional(optional($k->siswa)->user)->name,
                'kelas' => optional(optional($k->siswa)->kelas)->nama_kelas,
                'status' => $k->status,
                'dokumentasiPath' => $k->dokumentasi_path,
            ]);

        $klasikal = LayananKlasikal::with('guruBk')
            ->select(['id', 'guru_bk_id', 'tanggal_pelaksanaan', 'dokumentasi_path'])
            ->get()
            ->map(fn ($k) => (object) [
                'jenis' => 'Klasikal',
                'tanggal' => Carbon::parse($k->tanggal_pelaksanaan),
                'guruBk' => optional($k->guruBk)->name ?? '-',
                'siswa' => null,
                'kelas' => null,
                'status' => null,
                'dokumentasiPath' => $k->dokumentasi_path,
            ]);

        $kelompok = BimbinganKelompok::with('guruBk')
            ->select(['id', 'guru_bk_id', 'tanggal_pelaksanaan', 'dokumentasi_path'])
            ->get()
            ->map(fn ($k) => (object) [
                'jenis' => 'Kelompok',
                'tanggal' => Carbon::parse($k->tanggal_pelaksanaan),
                'guruBk' => optional($k->guruBk)->name ?? '-',
                'siswa' => null,
                'kelas' => null,
                'status' => null,
                'dokumentasiPath' => $k->dokumentasi_path,
            ]);

        $totalHomeVisit = $kunjungan->count();
        $totalKlasikal = $klasikal->count();
        $totalKelompok = $kelompok->count();

        $semuaAktivitas = $kunjungan->concat($klasikal)->concat($kelompok);

        if ($request->filled('jenis')) {
            $semuaAktivitas = $semuaAktivitas->where('jenis', $request->jenis);
        }

        $semuaAktivitas = $semuaAktivitas->sortByDesc('tanggal')->values();

        $perHalaman = 15;
        $halaman = LengthAwarePaginator::resolveCurrentPage();
        $aktivitas = new LengthAwarePaginator(
            $semuaAktivitas->forPage($halaman, $perHalaman)->values(),
            $semuaAktivitas->count(),
            $perHalaman,
            $halaman,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
        $aktivitas->appends($request->only('jenis'));

        return view('kepsek.pantau-bk.index', compact(
            'aktivitas',
            'totalHomeVisit',
            'totalKlasikal',
            'totalKelompok'
        ));
    }
}
