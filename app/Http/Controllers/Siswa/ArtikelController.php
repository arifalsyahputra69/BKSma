<?php

// LETAKKAN DI: app/Http/Controllers/Siswa/ArtikelController.php
//
// FITUR BARU (dulu Batasan Penelitian, sekarang dikerjakan atas permintaan
// penulis skripsi - 20 Juli 2026): Halaman Artikel Informasi BK sisi Siswa.
// Read-only, pasangan dari GuruBK\ArtikelBkController. Hanya menampilkan
// artikel dengan status_publish = true.

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\ArtikelBk;
use Illuminate\Http\Request;

class ArtikelController extends Controller
{
    private const KATEGORI = ['Karier', 'Belajar', 'Sosial', 'Pribadi', 'Umum'];

    public function index(Request $request)
    {
        // PERBAIKAN (30 Juli 2026): sama seperti sisi Guru BK
        // (GuruBK\ArtikelBkController) -- sebelumnya SEMUA artikel diambil
        // sekaligus (->get(), tanpa batas), jadi makin banyak artikel
        // ditulis Guru BK, makin berat & lambat halaman ini reload
        // dibanding halaman lain. Sekarang dipaginasi (12/halaman).
        $query = ArtikelBk::where('status_publish', true)->latest();

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        $artikelList = $query->paginate(12)->withQueryString();
        $kategoriList = self::KATEGORI;

        return view('siswa.artikel.index', compact('artikelList', 'kategoriList'));
    }

    public function show(string $slug)
    {
        $artikel = ArtikelBk::where('slug', $slug)->where('status_publish', true)->firstOrFail();

        return view('siswa.artikel.show', compact('artikel'));
    }
}
