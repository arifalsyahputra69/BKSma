<?php

// LETAKKAN DI: app/Http/Controllers/Kepsek/KunjunganRumahController.php

namespace App\Http\Controllers\Kepsek;

use App\Http\Controllers\Controller;
use App\Models\KunjunganRumah;
use Illuminate\Http\Request;

class KunjunganRumahController extends Controller
{
    // FASE 8: Monitoring Kepsek untuk Home Visit. Kerahasiaan tetap
    // dijaga -- kolom hasil_observasi & kesepakatan_bersama SENGAJA TIDAK
    // di-load/ditampilkan di sini, sama seperti prinsip yang dipakai untuk
    // uraian_masalah di jurnal_layanans (lihat DashboardController).
    //
    // PERBAIKAN (27 Juli 2026): dokumentasi_path (foto/surat bukti
    // kunjungan) ditambahkan ke select supaya bisa ditampilkan/dibuka di
    // sisi Kepsek juga. Ini beda dengan hasil_observasi & kesepakatan
    // (yang memang isi percakapan pribadi/rahasia) -- foto/surat cuma
    // bukti administratif bahwa kunjungan benar terjadi, jadi wajar kalau
    // Kepsek ikut bisa memverifikasinya, bukan cuma tersimpan di database
    // tanpa pernah ditampilkan ke siapa pun selain Guru BK.
    public function index(Request $request)
    {
        $query = KunjunganRumah::with(['siswa.user', 'siswa.kelas', 'guruBk'])
            ->select(['id', 'siswa_id', 'guru_bk_id', 'tanggal_kunjungan', 'status', 'dokumentasi_path', 'created_at']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $kunjungan = $query->latest('tanggal_kunjungan')->paginate(15)->withQueryString();

        $totalDirencanakan = KunjunganRumah::where('status', 'Direncanakan')->count();
        $totalTerlaksana = KunjunganRumah::where('status', 'Terlaksana')->count();
        $totalDibatalkan = KunjunganRumah::where('status', 'Dibatalkan')->count();

        return view('kepsek.kunjungan-rumah.index', compact(
            'kunjungan',
            'totalDirencanakan',
            'totalTerlaksana',
            'totalDibatalkan'
        ));
    }
}