<?php

// LETAKKAN DI: app/Http/Controllers/GuruBK/AkpdController.php

namespace App\Http\Controllers\GuruBK;

use App\Http\Controllers\Controller;
use App\Models\AkpdItem;
use App\Models\AkpdResponse;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AkpdController extends Controller
{
    private const KATEGORI = ['Pribadi', 'Sosial', 'Belajar', 'Karir'];

    /**
     * Halaman ini punya 2 tab (mengikuti pola tab di gurubk.rekap-laporan):
     * "Kelola Pertanyaan" (bank soal AKPD, dipakai bareng semua Guru BK)
     * dan "Rekap Hasil" (persentase siswa binaan yang mencentang tiap
     * item, per kategori -- dasar penyusunan Program Tahunan).
     */
    public function index(Request $request)
    {
        $itemsByKategori = AkpdItem::orderBy('urutan')->orderBy('id')->get()->groupBy('kategori');
        $kategoriList = self::KATEGORI;

        $semesterList = Semester::orderByDesc('id')->get();
        $semesterId = $request->input('semester_id', Semester::where('status_aktif', true)->value('id'));

        $guruBkId = Auth::id();

        // Rekap: untuk tiap item AKPD, hitung berapa siswa BINAAN Guru BK
        // ini yang mencentangnya pada semester terpilih, dibagi total siswa
        // binaan yang SUDAH mengisi AKPD pada semester tersebut.
        $totalSiswaMengisi = AkpdResponse::where('semester_id', $semesterId)
            ->whereHas('siswa.kelas', fn ($q) => $q->where('id_guru_bk', $guruBkId))
            ->distinct('siswa_id')
            ->count('siswa_id');

        $rekapByKategori = AkpdItem::where('is_active', true)
            ->orderBy('urutan')
            ->get()
            ->groupBy('kategori')
            ->map(function ($items) use ($semesterId, $guruBkId) {
                return $items->map(function ($item) use ($semesterId, $guruBkId) {
                    $jumlah = AkpdResponse::where('akpd_item_id', $item->id)
                        ->where('semester_id', $semesterId)
                        ->where('jawaban', 'ya')
                        ->whereHas('siswa.kelas', fn ($q) => $q->where('id_guru_bk', $guruBkId))
                        ->count();

                    return [
                        'pertanyaan' => $item->pertanyaan,
                        'jumlah' => $jumlah,
                    ];
                })->sortByDesc('jumlah')->values();
            });

        return view('gurubk.akpd.index', compact(
            'itemsByKategori',
            'kategoriList',
            'semesterList',
            'semesterId',
            'totalSiswaMengisi',
            'rekapByKategori'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kategori' => 'required|in:' . implode(',', self::KATEGORI),
            'pertanyaan' => 'required|string|max:500',
            'urutan' => 'nullable|integer|min:0',
        ]);

        // Kalau Guru BK tidak isi urutan manual, sistem otomatis lanjutkan
        // dari nomor urut tertinggi yang sudah ada (bukan reset ke 0).
        $urutan = $request->filled('urutan')
            ? (int) $request->urutan
            : (int) AkpdItem::max('urutan') + 1;

        AkpdItem::create([
            'kategori' => $request->kategori,
            'pertanyaan' => $request->pertanyaan,
            'urutan' => $urutan,
            'is_active' => true,
        ]);

        return back()->with('success', 'Pertanyaan AKPD berhasil ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        $item = AkpdItem::findOrFail($id);

        $request->validate([
            'kategori' => 'required|in:' . implode(',', self::KATEGORI),
            'pertanyaan' => 'required|string|max:500',
            'urutan' => 'nullable|integer|min:0',
        ]);

        $item->update([
            'kategori' => $request->kategori,
            'pertanyaan' => $request->pertanyaan,
            // Kalau field urutan dikosongkan saat edit, pertahankan urutan
            // lama (jangan direset ke 0).
            'urutan' => $request->filled('urutan') ? (int) $request->urutan : $item->urutan,
        ]);

        return back()->with('success', 'Pertanyaan AKPD berhasil diperbarui.');
    }

    public function toggleActive(int $id)
    {
        $item = AkpdItem::findOrFail($id);
        $item->update(['is_active' => !$item->is_active]);

        return back()->with('success', 'Status pertanyaan AKPD berhasil diubah.');
    }

    public function destroy(int $id)
    {
        $item = AkpdItem::findOrFail($id);
        $item->delete();

        return back()->with('success', 'Pertanyaan AKPD berhasil dihapus.');
    }
}