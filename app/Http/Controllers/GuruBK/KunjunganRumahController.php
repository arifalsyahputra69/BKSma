<?php

// LETAKKAN DI: app/Http/Controllers/GuruBK/KunjunganRumahController.php

namespace App\Http\Controllers\GuruBK;

use App\Http\Controllers\Controller;
use App\Models\KunjunganRumah;
use App\Models\Notifikasi;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class KunjunganRumahController extends Controller
{
    // Mencatat rencana/riwayat kunjungan rumah baru. Dipanggil dari modal
    // "Form Kunjungan Rumah" di tab Home Visit halaman Rekap Laporan.
    public function store(Request $request)
    {
        $request->validate([
            'siswa_id' => 'required|exists:siswas,id',
            'tanggal_kunjungan' => 'required|date',
            'nama_wali_ditemui' => 'nullable|string|max:255',
            'alasan_kunjungan' => 'required|string',
        ]);

        // Pastikan siswa yang dipilih memang kelolaan Guru BK ini, supaya
        // Guru BK lain tidak bisa mencatat kunjungan rumah siswa yang bukan
        // kelasnya (sama seperti pola di JurnalLayananController::storeManual).
        $siswa = Siswa::whereHas('kelas', function ($query) {
            $query->where('id_guru_bk', Auth::id());
        })->findOrFail($request->siswa_id);

        KunjunganRumah::create([
            'siswa_id' => $siswa->id,
            'guru_bk_id' => Auth::id(),
            'tanggal_kunjungan' => $request->tanggal_kunjungan,
            'nama_wali_ditemui' => $request->nama_wali_ditemui,
            'alasan_kunjungan' => $request->alasan_kunjungan,
            'status' => 'Direncanakan',
        ]);

        return back()->with('success', 'Rencana kunjungan rumah berhasil dicatat.');
    }

    // Melengkapi hasil kunjungan setelah home visit benar-benar dilaksanakan
    // (hasil observasi, kesepakatan bersama, dan bukti dokumentasi).
    public function tandaiTerlaksana(Request $request, string $id)
    {
        $request->validate([
            'hasil_observasi' => 'required|string',
            'kesepakatan_bersama' => 'nullable|string',
            'dokumentasi' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $kunjungan = KunjunganRumah::where('guru_bk_id', Auth::id())->findOrFail($id);

        $path = $kunjungan->dokumentasi_path;
        if ($request->hasFile('dokumentasi')) {
            $path = $request->file('dokumentasi')->store('kunjungan-rumah', 'public');
        }

        $kunjungan->update([
            'hasil_observasi' => $request->hasil_observasi,
            'kesepakatan_bersama' => $request->kesepakatan_bersama,
            'dokumentasi_path' => $path,
            'status' => 'Terlaksana',
        ]);

        $this->notifikasiWaliKelas($kunjungan);

        return back()->with('success', 'Kunjungan rumah ditandai selesai dilaksanakan.');
    }

    public function batalkan(string $id)
    {
        $kunjungan = KunjunganRumah::where('guru_bk_id', Auth::id())->findOrFail($id);
        $kunjungan->update(['status' => 'Dibatalkan']);

        return back()->with('success', 'Rencana kunjungan rumah dibatalkan.');
    }

    // PERBAIKAN (23 Juli 2026): tab "Home Visit" sebelumnya tidak punya
    // fitur Edit maupun Hapus sama sekali di kolom Aksi -- kunjungan yang
    // salah tanggal/keterangan atau salah catat tidak bisa dikoreksi atau
    // dibuang, harus dibiarkan menumpuk selamanya. Method ini melengkapi
    // fitur Edit (mengubah data rencana kunjungan, dan hasil kunjungan
    // kalau statusnya sudah Terlaksana).
    public function update(Request $request, string $id)
    {
        $kunjungan = KunjunganRumah::where('guru_bk_id', Auth::id())->findOrFail($id);

        $request->validate([
            'siswa_id' => 'required|exists:siswas,id',
            'tanggal_kunjungan' => 'required|date',
            'nama_wali_ditemui' => 'nullable|string|max:255',
            'alasan_kunjungan' => 'required|string',
            'hasil_observasi' => $kunjungan->status === 'Terlaksana' ? 'required|string' : 'nullable|string',
            'kesepakatan_bersama' => 'nullable|string',
            'dokumentasi' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // Pastikan siswa yang dipilih tetap kelolaan Guru BK ini, sama seperti
        // validasi kepemilikan di method store().
        $siswa = Siswa::whereHas('kelas', function ($query) {
            $query->where('id_guru_bk', Auth::id());
        })->findOrFail($request->siswa_id);

        $path = $kunjungan->dokumentasi_path;
        if ($request->hasFile('dokumentasi')) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            $path = $request->file('dokumentasi')->store('kunjungan-rumah', 'public');
        }

        $kunjungan->update([
            'siswa_id' => $siswa->id,
            'tanggal_kunjungan' => $request->tanggal_kunjungan,
            'nama_wali_ditemui' => $request->nama_wali_ditemui,
            'alasan_kunjungan' => $request->alasan_kunjungan,
            'hasil_observasi' => $request->hasil_observasi,
            'kesepakatan_bersama' => $request->kesepakatan_bersama,
            'dokumentasi_path' => $path,
        ]);

        return back()->with('success', 'Data kunjungan rumah berhasil diperbarui.');
    }

    // Menghapus catatan kunjungan rumah secara permanen (beda dengan
    // batalkan() yang cuma mengubah status jadi "Dibatalkan" tapi datanya
    // tetap ada). Dokumentasi yang tersimpan di storage ikut dihapus supaya
    // tidak jadi file sampah.
    public function destroy(string $id)
    {
        $kunjungan = KunjunganRumah::where('guru_bk_id', Auth::id())->findOrFail($id);

        if ($kunjungan->dokumentasi_path) {
            Storage::disk('public')->delete($kunjungan->dokumentasi_path);
        }

        $kunjungan->delete();

        return back()->with('success', 'Data kunjungan rumah berhasil dihapus.');
    }

    // Area Monitoring #7 pattern: Wali Kelas diberi tahu in-app begitu home
    // visit selesai dilaksanakan, supaya kolaborasi sekolah-keluarga tidak
    // berhenti di Guru BK saja. Sengaja HANYA info umum (tanggal + status),
    // TIDAK PERNAH menyertakan hasil_observasi / kesepakatan_bersama, supaya
    // kerahasiaan kunjungan tetap terjaga.
    private function notifikasiWaliKelas(KunjunganRumah $kunjungan): void
    {
        $siswa = $kunjungan->siswa;
        $kelas = $siswa?->kelas;
        if (! $kelas || ! $kelas->wali_kelas_id) {
            return;
        }

        $namaSiswa = optional($siswa->user)->name ?? 'Siswa';
        $tanggal = Carbon::parse($kunjungan->tanggal_kunjungan)->translatedFormat('d F Y');

        Notifikasi::create([
            'user_id' => $kelas->wali_kelas_id,
            'judul' => 'Kunjungan Rumah Selesai Dilaksanakan',
            'pesan' => "Guru BK telah melaksanakan kunjungan rumah (home visit) untuk Ananda {$namaSiswa} (kelas {$kelas->nama_kelas}) pada {$tanggal}.\n\nDetail hasil kunjungan bersifat rahasia dan tidak ditampilkan di sini.",
            'link' => route('walikelas.dashboard'),
            'is_read' => false,
        ]);
    }
}