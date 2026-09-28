<?php

// LETAKKAN DI: app/Http/Controllers/Siswa/InformasiKampusController.php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Kampus;

/**
 * PERBAIKAN (30 Juli 2026): fitur "Konsultasi Jurusan" lama menulis ke
 * jurnal_layanans dan memuat relasi kampus() di model JurnalLayanan.
 * Kolom jenis_konsultasi, kampus_id, minat_jurusan sudah dihapus total dari
 * tabel jurnal_layanans oleh migrasi
 * 2026_07_29_120000_hapus_fitur_konsultasi_jurusan_dan_alumni.php, dan
 * model JurnalLayanan memang tidak (dan tidak seharusnya) punya relasi
 * kampus() lagi. Itu sebabnya controller lama selalu error 500
 * (RelationNotFoundException: kampus).
 *
 * Controller ini menggantikan fitur tersebut dengan sesuatu yang jauh lebih
 * sederhana dan aman: halaman informasi kampus untuk siswa yang SIFATNYA
 * HANYA BACA. Tidak ada form, tidak ada insert, tidak menyentuh
 * jurnal_layanans sama sekali -- murni menampilkan data yang diinput
 * TU/Guru BK lewat menu "Kelola Informasi Kampus" (tabel kampus).
 */
class InformasiKampusController extends Controller
{
    public function index()
    {
        $kampusList = Kampus::orderBy('nama_kampus')->get();

        return view('siswa.informasi-kampus.index', compact('kampusList'));
    }
}
