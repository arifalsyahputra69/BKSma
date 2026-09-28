<?php

namespace App\Http\Controllers\GuruBK;

use App\Http\Controllers\Controller;
use App\Models\ChatbotRule;
use Illuminate\Http\Request;

class ChatbotRuleController extends Controller
{
    public function index() {
        // Ambil semua data agar dropdown parent terisi
        $rules = ChatbotRule::all();
        return view('gurubk.chatbot.index', compact('rules'));
    }

    public function store(Request $request) 
    {
        // Validasi harus sesuai dengan nama form dan database
        $request->validate([
            'keyword'   => 'required', 
            'response'  => 'required', 
            'category'  => 'required',
            'parent_id' => 'nullable|exists:chatbot_rules,id' 
        ]);

        // Simpan data
        ChatbotRule::create([
            'keyword'   => $request->keyword,
            'response'  => $request->response, 
            'category'  => $request->category,
            'parent_id' => $request->parent_id
        ]);

        return back()->with('success', 'Aturan balasan otomatis berhasil ditambahkan!');
    }

    /**
     * Ubah aturan yang sudah ada (7 Agustus 2026).
     *
     * Sebelumnya halaman ini hanya punya tombol hapus, sehingga salah ketik
     * sekecil apa pun -- satu huruf pada keyword, atau kalimat balasan yang
     * ingin diperhalus -- memaksa Guru BK menghapus aturannya lalu membuat
     * ulang dari nol. Padahal menghapus induk percakapan juga memutus semua
     * aturan turunannya, jadi cara itu bukan sekadar merepotkan tapi berisiko.
     */
    public function update(Request $request, $id)
    {
        $rule = ChatbotRule::findOrFail($id);

        $request->validate([
            'keyword'   => 'required',
            'response'  => 'required',
            'category'  => 'required',
            'parent_id' => 'nullable|exists:chatbot_rules,id',
        ]);

        $parentId = $request->parent_id ?: null;

        // Sebuah aturan tidak boleh menjadi induk bagi dirinya sendiri.
        if ((int) $parentId === (int) $rule->id) {
            return back()->with(
                'error',
                'Sebuah aturan tidak bisa dijadikan induk bagi dirinya sendiri.'
            );
        }

        // Induk juga tidak boleh diambil dari keturunannya sendiri. Kalau A
        // induk B lalu B dijadikan induk A, rantai percakapannya melingkar dan
        // penelusuran induk akan berputar tanpa henti saat chatbot dipakai.
        if ($parentId && $this->akanMembentukLingkaran($rule->id, $parentId)) {
            return back()->with(
                'error',
                'Aturan itu adalah turunan dari aturan ini, jadi tidak bisa dijadikan induknya. ' .
                'Percakapannya akan berputar tanpa ujung.'
            );
        }

        $rule->update([
            'keyword'   => $request->keyword,
            'response'  => $request->response,
            'category'  => $request->category,
            'parent_id' => $parentId,
        ]);

        return back()->with('success', 'Aturan berhasil diperbarui.');
    }

    /**
     * Telusuri ke atas dari calon induk. Kalau di sepanjang jalur itu ditemukan
     * $ruleId, berarti calon induk sebenarnya keturunan dari aturan yang sedang
     * diubah -- dan menjadikannya induk akan membentuk lingkaran.
     *
     * Ada pembatas langkah sebagai jaring pengaman: kalau data lama terlanjur
     * memuat lingkaran (misalnya diubah langsung lewat database), perulangan
     * ini tetap berhenti dan tidak menggantungkan permintaan.
     */
    private function akanMembentukLingkaran(int $ruleId, int $calonIndukId): bool
    {
        $langkah = 0;
        $sekarang = ChatbotRule::find($calonIndukId);

        while ($sekarang && $langkah < 50) {
            if ((int) $sekarang->id === $ruleId) {
                return true;
            }

            if (! $sekarang->parent_id) {
                return false;
            }

            $sekarang = ChatbotRule::find($sekarang->parent_id);
            $langkah++;
        }

        return false;
    }

    public function destroy($id) {
        ChatbotRule::findOrFail($id)->delete();
        return back()->with('success', 'Aturan berhasil dihapus.');
    }
}