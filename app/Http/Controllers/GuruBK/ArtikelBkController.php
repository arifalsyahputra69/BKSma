<?php

// LETAKKAN DI: app/Http/Controllers/GuruBK/ArtikelBkController.php
//
// FITUR BARU (dulu Batasan Penelitian, sekarang dikerjakan atas permintaan
// penulis skripsi - 20 Juli 2026): Artikel Informasi BK untuk Guru BK.
// Pola CRUD & upload gambar mengikuti konvensi yang sudah ada di project:
// - Struktur CRUD: mengikuti TU\KampusController (index/store/update/destroy)
// - Upload file: mengikuti UserProfileController::updateFoto()
//   (Storage::disk('public'), folder khusus, filename timestamp-based)
// - Setiap Guru BK cuma boleh edit/hapus artikelnya sendiri (scoped by
//   guru_bk_id), sama seperti ProgramBkController & KegiatanBkController.
//   Tapi index menampilkan SEMUA artikel dari semua Guru BK, supaya
//   antar Guru BK bisa saling lihat konten yang sudah dibuat.

namespace App\Http\Controllers\GuruBK;

use App\Http\Controllers\Controller;
use App\Models\ArtikelBk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArtikelBkController extends Controller
{
    /**
     * Daftar kategori artikel yang tersedia (dipakai di form & filter).
     */
    private const KATEGORI = ['Karier', 'Belajar', 'Sosial', 'Pribadi', 'Umum'];

    public function index(Request $request)
    {
        // PERBAIKAN (30 Juli 2026): sebelumnya semua artikel diambil sekaligus
        // (->get(), tanpa batas) dan SETIAP artikel milik Guru BK yang login
        // memunculkan modal Edit penuh (termasuk textarea seluruh konten
        // artikel) di dalam DOM halaman. Makin banyak artikel ditulis, makin
        // berat halaman ini dibanding halaman lain -- reload jadi terasa
        // lambat. Sekarang dipaginasi (12/halaman) supaya jumlah modal yang
        // dirender juga ikut terbatas.
        $query = ArtikelBk::with('guruBk')->latest();

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        $artikelList = $query->paginate(12)->withQueryString();
        $kategoriList = self::KATEGORI;

        return view('gurubk.artikel-bk.index', compact('artikelList', 'kategoriList'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'kategori' => 'required|in:' . implode(',', self::KATEGORI),
            'gambar_sampul' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'ringkasan' => 'nullable|string|max:500',
            'konten' => 'required|string',
            'status_publish' => 'nullable|boolean',
        ]);

        $data = $request->only(['judul', 'kategori', 'ringkasan', 'konten']);
        $data['guru_bk_id'] = Auth::id();
        $data['slug'] = $this->buatSlugUnik($request->judul);
        $data['status_publish'] = $request->boolean('status_publish', true);

        if ($request->hasFile('gambar_sampul')) {
            $file = $request->file('gambar_sampul');
            // PERBAIKAN KEAMANAN (AUDIT): nama file dibuat acak oleh server,
            // ekstensi diambil dari deteksi isi file -- bukan dari nama asli
            // yang dikirim klien (getClientOriginalName). Lihat catatan yang
            // sama di UserProfileController::updateFoto().
            $filename = Str::random(32) . '.' . ($file->guessExtension() ?: 'jpg');
            $file->storeAs('artikel-bk', $filename, 'public');
            $data['gambar_sampul'] = $filename;
        }

        ArtikelBk::create($data);

        return back()->with('success', 'Artikel BK berhasil ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        // Hanya boleh mengedit artikel milik sendiri
        $artikel = ArtikelBk::where('guru_bk_id', Auth::id())->findOrFail($id);

        $request->validate([
            'judul' => 'required|string|max:255',
            'kategori' => 'required|in:' . implode(',', self::KATEGORI),
            'gambar_sampul' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'ringkasan' => 'nullable|string|max:500',
            'konten' => 'required|string',
            'status_publish' => 'nullable|boolean',
        ]);

        $data = $request->only(['kategori', 'ringkasan', 'konten']);
        $data['status_publish'] = $request->boolean('status_publish', true);

        // Slug cuma dibuat ulang kalau judul berubah, supaya link lama yang
        // sudah dibagikan ke siswa tidak mendadak putus.
        if ($request->judul !== $artikel->judul) {
            $data['judul'] = $request->judul;
            $data['slug'] = $this->buatSlugUnik($request->judul, $artikel->id);
        }

        if ($request->hasFile('gambar_sampul')) {
            if ($artikel->gambar_sampul && Storage::disk('public')->exists('artikel-bk/' . $artikel->gambar_sampul)) {
                Storage::disk('public')->delete('artikel-bk/' . $artikel->gambar_sampul);
            }

            $file = $request->file('gambar_sampul');
            // PERBAIKAN KEAMANAN (AUDIT): nama file dibuat acak oleh server,
            // ekstensi diambil dari deteksi isi file -- bukan dari nama asli
            // yang dikirim klien (getClientOriginalName). Lihat catatan yang
            // sama di UserProfileController::updateFoto().
            $filename = Str::random(32) . '.' . ($file->guessExtension() ?: 'jpg');
            $file->storeAs('artikel-bk', $filename, 'public');
            $data['gambar_sampul'] = $filename;
        }

        $artikel->update($data);

        return back()->with('success', 'Artikel BK berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $artikel = ArtikelBk::where('guru_bk_id', Auth::id())->findOrFail($id);

        if ($artikel->gambar_sampul && Storage::disk('public')->exists('artikel-bk/' . $artikel->gambar_sampul)) {
            Storage::disk('public')->delete('artikel-bk/' . $artikel->gambar_sampul);
        }

        $artikel->delete();

        return back()->with('success', 'Artikel BK berhasil dihapus.');
    }

    /**
     * Bikin slug dari judul, tambah angka urut kalau sudah ada yang sama
     * (mis. "manajemen-waktu-belajar", "manajemen-waktu-belajar-2", dst).
     */
    private function buatSlugUnik(string $judul, ?int $ignoreId = null): string
    {
        $slugDasar = Str::slug($judul);
        $slug = $slugDasar;
        $counter = 2;

        while (
            ArtikelBk::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $slugDasar . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
