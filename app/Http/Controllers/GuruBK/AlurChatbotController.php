<?php

namespace App\Http\Controllers\GuruBK;

use App\Http\Controllers\Controller;
use App\Models\AlurChatbot;
use App\Models\PilihanAlurChatbot;
use App\Models\Kampus;
use Illuminate\Http\Request;

class AlurChatbotController extends Controller
{
    public function index() {
        // Ambil semua alur pertanyaan beserta pilihan jawabannya
        $alurs = AlurChatbot::with(['pilihan', 'pilihan.kampus'])->get();
        // FITUR BARU (30 Juli 2026): daftar kampus master untuk dropdown
        // "Rekomendasikan Kampus" di form tambah pilihan.
        $kampusList = Kampus::orderBy('nama_kampus')->get();
        return view('gurubk.alur_chatbot.index', compact('alurs', 'kampusList'));
    }

    public function storeAlur(Request $request) {
        $request->validate(['pertanyaan' => 'required']);
        AlurChatbot::create($request->only('pertanyaan'));
        return back()->with('success', 'Pertanyaan utama berhasil ditambahkan!');
    }

    public function storePilihan(Request $request, $alur_id) {
        $request->validate([
            'teks_pilihan' => 'required',
            'respons' => 'required',
            'kampus_id' => 'nullable|exists:kampus,id',
        ]);

        PilihanAlurChatbot::create([
            'alur_id' => $alur_id,
            'teks_pilihan' => $request->teks_pilihan,
            'respons' => $request->respons,
            'tag_kampus' => $request->tag_kampus,
            'kampus_id' => $request->kampus_id,
        ]);
        
        return back()->with('success', 'Tombol pilihan berhasil ditambahkan ke pertanyaan!');
    }

    /**
     * Ubah teks pertanyaan utama (7 Agustus 2026).
     */
    public function updateAlur(Request $request, $id) {
        $request->validate(['pertanyaan' => 'required']);

        AlurChatbot::findOrFail($id)->update(
            $request->only('pertanyaan')
        );

        return back()->with('success', 'Pertanyaan berhasil diperbarui.');
    }

    /**
     * Ubah satu tombol pilihan beserta responsnya (7 Agustus 2026).
     *
     * Sebelumnya tidak ada cara mengoreksi satu pilihan. Satu huruf yang salah
     * pada teks tombol memaksa Guru BK menghapus seluruh pertanyaan -- termasuk
     * pilihan-pilihan lain yang sudah benar -- lalu menyusun ulang semuanya.
     */
    public function updatePilihan(Request $request, $id) {
        $request->validate([
            'teks_pilihan' => 'required',
            'respons' => 'required',
            'kampus_id' => 'nullable|exists:kampus,id',
        ]);

        $pilihan = PilihanAlurChatbot::findOrFail($id);

        $pilihan->update([
            'teks_pilihan' => $request->teks_pilihan,
            'respons' => $request->respons,
            // Kolom kampus_id sengaja dinormalkan jadi null saat kosong. Kalau
            // dibiarkan berupa string kosong, relasi kampus() akan mencari baris
            // dengan id '' dan pengecekan $pilihan->kampus di tampilan jadi
            // tidak bisa diandalkan.
            'kampus_id' => $request->kampus_id ?: null,
            'tag_kampus' => $request->tag_kampus ?: null,
        ]);

        return back()->with('success', 'Pilihan jawaban berhasil diperbarui.');
    }

    /**
     * Hapus satu tombol pilihan saja, tanpa membuang pertanyaan induknya.
     */
    public function destroyPilihan($id) {
        PilihanAlurChatbot::findOrFail($id)->delete();
        return back()->with('success', 'Pilihan jawaban berhasil dihapus.');
    }

    public function destroyAlur($id) {
        AlurChatbot::findOrFail($id)->delete();
        return back()->with('success', 'Pertanyaan dan semua pilihannya berhasil dihapus.');
    }
}