<?php

// LETAKKAN DI: app/Http/Controllers/GuruBK/KegiatanBkController.php
//
// FASE 8 (Backlog): Kalender kegiatan BK. Guru BK mencatat rencana kegiatan
// (sosialisasi, penyuluhan, rapat koordinasi, bimbingan klasikal, dll),
// Kepsek memantaunya lewat Kepsek\KegiatanBkController (read-only).

namespace App\Http\Controllers\GuruBK;

use App\Http\Controllers\Controller;
use App\Models\KegiatanBk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KegiatanBkController extends Controller
{
    public function index()
    {
        // 1. Ambil data list kegiatan
        $kegiatanMendatang = KegiatanBk::where('guru_bk_id', Auth::id())
            ->where('status', 'Direncanakan')
            ->orderBy('tanggal_mulai')
            ->get();

        $kegiatanLalu = KegiatanBk::where('guru_bk_id', Auth::id())
            ->whereIn('status', ['Terlaksana', 'Dibatalkan'])
            ->orderByDesc('tanggal_mulai')
            ->take(30)
            ->get();

        // 2. Hitung jumlah total untuk ditampilkan di Card Statistik / View (Perbaikan Error)
        $totalMendatang = $kegiatanMendatang->count();
        $totalTerlaksana = KegiatanBk::where('guru_bk_id', Auth::id())
            ->where('status', 'Terlaksana')
            ->count();

        // 3. Kirimkan semua variabel ke View
        return view('gurubk.kegiatan-bk.index', compact('kegiatanMendatang', 'kegiatanLalu', 'totalMendatang', 'totalTerlaksana'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'kategori' => 'required|in:Sosialisasi,Penyuluhan,Rapat/Koordinasi,Bimbingan Klasikal,Lainnya',
            'sasaran' => 'nullable|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'lokasi' => 'nullable|string|max:255',
        ]);

        KegiatanBk::create([
            'guru_bk_id' => Auth::id(),
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'kategori' => $request->kategori,
            'sasaran' => $request->sasaran,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'lokasi' => $request->lokasi,
            'status' => 'Direncanakan',
        ]);

        return back()->with('success', 'Kegiatan BK berhasil ditambahkan ke kalender.');
    }

    public function updateStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:Direncanakan,Terlaksana,Dibatalkan',
        ]);

        $kegiatan = KegiatanBk::where('guru_bk_id', Auth::id())->findOrFail($id);
        $kegiatan->update(['status' => $request->status]);

        return back()->with('success', 'Status kegiatan diperbarui.');
    }

    /**
     * PERBAIKAN (26 Juli 2026): kolom Aksi Kalender Kegiatan BK sebelumnya
     * cuma bisa mengubah status & menghapus, tidak bisa mengedit detail
     * kegiatan (judul, jadwal, lokasi, dll) kalau ada salah ketik atau
     * jadwal berubah.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'kategori' => 'required|in:Sosialisasi,Penyuluhan,Rapat/Koordinasi,Bimbingan Klasikal,Lainnya',
            'sasaran' => 'nullable|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'lokasi' => 'nullable|string|max:255',
        ]);

        $kegiatan = KegiatanBk::where('guru_bk_id', Auth::id())->findOrFail($id);

        $kegiatan->update($request->only([
            'judul', 'deskripsi', 'kategori', 'sasaran',
            'tanggal_mulai', 'tanggal_selesai', 'lokasi',
        ]));

        return back()->with('success', 'Kegiatan BK berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $kegiatan = KegiatanBk::where('guru_bk_id', Auth::id())->findOrFail($id);
        $kegiatan->delete();

        return back()->with('success', 'Kegiatan BK dihapus dari kalender.');
    }
}