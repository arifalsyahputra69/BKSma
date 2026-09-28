<?php

// LETAKKAN DI: app/Http/Controllers/Siswa/AkpdController.php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\AkpdItem;
use App\Models\AkpdResponse;
use App\Models\Semester;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AkpdController extends Controller
{
    public function index()
    {
        $siswa = Siswa::where('user_id', Auth::id())->firstOrFail();
        $semesterAktif = Semester::where('status_aktif', true)->first();

        $itemsByKategori = AkpdItem::where('is_active', true)
            ->orderBy('urutan')
            ->orderBy('id')
            ->get()
            ->groupBy('kategori');

        // Kalau siswa sudah pernah mengisi AKPD di semester aktif ini,
        // tampilkan jawaban sebelumnya supaya kelihatan sudah pernah isi
        // (checkbox otomatis tercentang), bukan form kosong lagi.
        $jawabanSebelumnya = [];
        $sudahMengisi = false;
        if ($semesterAktif) {
            $query = AkpdResponse::where('siswa_id', $siswa->id)
                ->where('semester_id', $semesterAktif->id);
            $sudahMengisi = (clone $query)->exists();
            $jawabanSebelumnya = $query->where('jawaban', 'ya')->pluck('akpd_item_id')->toArray();
        }

        return view('siswa.akpd.index', compact('itemsByKategori', 'semesterAktif', 'jawabanSebelumnya', 'sudahMengisi'));
    }

    public function store(Request $request)
    {
        $siswa = Siswa::where('user_id', Auth::id())->firstOrFail();
        $semesterAktif = Semester::where('status_aktif', true)->first();

        if (!$semesterAktif) {
            return back()->with('error', 'Belum ada semester aktif, AKPD belum bisa diisi.');
        }

        // PERBAIKAN: AKPD cuma boleh diisi SATU KALI per semester aktif.
        // Sebelumnya jawaban lama dihapus lalu ditimpa jawaban baru setiap
        // kali form dikirim, jadi siswa bisa submit berkali-kali dan ubah
        // jawaban terus-menerus. Sekarang begitu sudah pernah submit,
        // percobaan submit berikutnya langsung ditolak di sini (server-side)
        // -- bukan cuma disembunyikan di tampilan -- supaya tidak bisa
        // dilewati dengan kirim form manual (curl/Postman/dsb).
        $sudahMengisi = AkpdResponse::where('siswa_id', $siswa->id)
            ->where('semester_id', $semesterAktif->id)
            ->exists();

        if ($sudahMengisi) {
            return back()->with('error', 'Jawaban AKPD kamu untuk semester ini sudah terkunci dan sudah tersimpan. Tidak bisa diisi ulang.');
        }

        // Wajib jawab Ya/Tidak untuk SETIAP pertanyaan aktif yang tampil,
        // supaya siswa tidak bisa mengirim form dengan jawaban kosong.
        $itemIdsAktif = AkpdItem::where('is_active', true)->pluck('id');
        $rules = [];
        foreach ($itemIdsAktif as $itemId) {
            $rules["jawaban.$itemId"] = 'required|in:ya,tidak';
        }
        $request->validate($rules, [
            'required' => 'Masih ada pertanyaan yang belum kamu jawab.',
        ]);

        // PERBAIKAN (AUDIT): validasi di atas hanya MEWAJIBKAN setiap item
        // aktif terjawab -- key tambahan yang tidak ada di daftar item aktif
        // (dikirim manual lewat curl/DevTools) tetap lolos dan ikut ter-INSERT,
        // menghasilkan baris sampah atau error foreign key. Sekarang hanya
        // item yang benar-benar aktif yang diproses.
        $jawabanValid = collect($request->input('jawaban', []))
            ->only($itemIdsAktif->all());

        foreach ($jawabanValid as $itemId => $jawaban) {
            AkpdResponse::create([
                'siswa_id' => $siswa->id,
                'akpd_item_id' => $itemId,
                'jawaban' => $jawaban,
                'semester_id' => $semesterAktif->id,
            ]);
        }

        return back()->with('success', 'Terima kasih, jawaban AKPD kamu berhasil disimpan.');
    }
}