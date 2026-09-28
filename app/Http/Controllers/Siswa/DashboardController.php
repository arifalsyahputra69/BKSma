<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\AntrianKonseling;
use App\Models\ArtikelBk;
use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // Relasi kelas ikut dimuat sejak awal supaya nama kelas bisa
        // ditampilkan tanpa query tambahan di dalam view.
        $siswa = Siswa::with(['kelas', 'kelasTujuan'])
            ->where('user_id', Auth::id())
            ->first();

        // Antrian konseling yang masih aktif (menunggu/dipanggil) milik siswa ini
        $antrianAktif = $siswa
            ? AntrianKonseling::with('sesi')
                ->where('siswa_id', $siswa->id)
                ->whereIn('status', ['Menunggu', 'Dipanggil', 'Sedang Konseling'])
                ->latest('waktu_booking')
                ->first()
            : null;

        // Artikel BK terbaru yang sudah dipublikasikan
        $artikelTerbaru = ArtikelBk::where('status_publish', true)
            ->latest()
            ->take(3)
            ->get();

        // Popup konfirmasi kelas hanya perlu tampil kalau siswa belum pernah
        // menjawab. Daftar kelas pun hanya diambil dalam kondisi itu -- tidak
        // ada gunanya menarik seluruh tabel kelas di setiap kali dashboard
        // dibuka oleh siswa yang sudah beres konfirmasinya.
        $perluKonfirmasiKelas = ! $siswa || is_null($siswa->status_konfirmasi_kelas);

        $daftarKelas = $perluKonfirmasiKelas
            ? Kelas::orderBy('nama_kelas')->get()
            : collect();

        return view('siswa.dashboard.index', compact(
            'siswa',
            'antrianAktif',
            'artikelTerbaru',
            'perluKonfirmasiKelas',
            'daftarKelas'
        ));
    }
}
