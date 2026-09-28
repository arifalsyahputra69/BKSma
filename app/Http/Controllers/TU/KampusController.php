<?php

namespace App\Http\Controllers\TU;

use App\Http\Controllers\Controller;
use App\Models\Kampus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * FITUR BARU (19 Juli 2026): Kelola Informasi Kampus untuk TU/Admin.
 * Pola CRUD & upload logo mengikuti konvensi yang sudah ada di project:
 * - Struktur CRUD: mengikuti TU\KelasController (index/store/update/destroy)
 * - Upload file: mengikuti UserProfileController::updateFoto()
 *   (Storage::disk('public'), folder khusus, filename timestamp-based)
 */
class KampusController extends Controller
{
    public function index()
    {
        $kampus = Kampus::latest()->get();

        return view('tu.kampus.index', compact('kampus'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_kampus'      => 'required|string|max:150',
            'logo'             => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'deskripsi'        => 'nullable|string',
            'jurusan_unggulan' => 'nullable|string|max:255',
            'jalur_beasiswa'   => 'nullable|string|max:255',
            'link_website'     => 'nullable|url|max:255',
        ]);

        $data = $request->only([
            'nama_kampus', 'deskripsi', 'jurusan_unggulan', 'jalur_beasiswa', 'link_website',
        ]);

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            // PERBAIKAN KEAMANAN (AUDIT): nama file dibuat acak oleh server,
            // ekstensi diambil dari deteksi isi file -- bukan dari nama asli
            // yang dikirim klien (getClientOriginalName). Lihat catatan yang
            // sama di UserProfileController::updateFoto().
            $filename = Str::random(32) . '.' . ($file->guessExtension() ?: 'jpg');
            $file->storeAs('kampus', $filename, 'public');
            $data['logo'] = $filename;
        }

        Kampus::create($data);

        return back()->with('success', 'Data kampus berhasil ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        $kampus = Kampus::findOrFail($id);

        $request->validate([
            'nama_kampus'      => 'required|string|max:150',
            'logo'             => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'deskripsi'        => 'nullable|string',
            'jurusan_unggulan' => 'nullable|string|max:255',
            'jalur_beasiswa'   => 'nullable|string|max:255',
            'link_website'     => 'nullable|url|max:255',
        ]);

        $data = $request->only([
            'nama_kampus', 'deskripsi', 'jurusan_unggulan', 'jalur_beasiswa', 'link_website',
        ]);

        if ($request->hasFile('logo')) {
            // Hapus logo lama kalau ada
            if ($kampus->logo && Storage::disk('public')->exists('kampus/' . $kampus->logo)) {
                Storage::disk('public')->delete('kampus/' . $kampus->logo);
            }

            $file = $request->file('logo');
            // PERBAIKAN KEAMANAN (AUDIT): nama file dibuat acak oleh server,
            // ekstensi diambil dari deteksi isi file -- bukan dari nama asli
            // yang dikirim klien (getClientOriginalName). Lihat catatan yang
            // sama di UserProfileController::updateFoto().
            $filename = Str::random(32) . '.' . ($file->guessExtension() ?: 'jpg');
            $file->storeAs('kampus', $filename, 'public');
            $data['logo'] = $filename;
        }

        $kampus->update($data);

        return back()->with('success', 'Data kampus berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $kampus = Kampus::findOrFail($id);

        if ($kampus->logo && Storage::disk('public')->exists('kampus/' . $kampus->logo)) {
            Storage::disk('public')->delete('kampus/' . $kampus->logo);
        }

        $kampus->delete();

        return back()->with('success', 'Data kampus berhasil dihapus.');
    }
}
