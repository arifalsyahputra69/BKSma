<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class KonfirmasiKelasController extends Controller
{
    /**
     * Halaman konfirmasi tersendiri sudah DIHAPUS (5 Agustus 2026).
     * Pertanyaannya sekarang tampil sebagai popup di dashboard siswa.
     *
     * Rutenya sengaja dipertahankan (bukan dihapus) supaya tautan lama --
     * dari bookmark siswa, riwayat browser, atau tautan di notifikasi yang
     * terlanjur terkirim -- tidak berubah jadi 404, melainkan mendarat di
     * tempat yang benar.
     */
    public function index()
    {
        return redirect()->route('siswa.dashboard');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $siswa = Siswa::where('user_id', $user->id)->first();

        if (! $siswa) {
            return redirect()->route('siswa.dashboard')
                ->with('error', 'Data profil siswa tidak ditemukan. Hubungi TU/Admin.');
        }

        // 'jawaban' divalidasi ketat: hanya dua nilai yang dikenal. Tanpa ini,
        // nilai apa pun selain 'ya' akan jatuh ke cabang "kelas berubah" --
        // termasuk request yang datang tanpa field 'jawaban' sama sekali,
        // sehingga siswa bisa tercatat melapor padahal tidak menekan apa pun.
        $data = $request->validate([
            'jawaban' => ['required', 'in:ya,tidak'],

            // Wajib HANYA ketika siswa menjawab "tidak". required_if membuat
            // aturan ini ikut kondisi jawaban, jadi siswa yang menjawab "ya"
            // tidak dipaksa mengirim kelas tujuan.
            'kelas_tujuan_id' => ['required_if:jawaban,tidak', 'nullable', 'exists:kelas,id'],
            'catatan_perbaikan_kelas' => ['nullable', 'string', 'max:500'],
        ], [
            'kelas_tujuan_id.required_if' => 'Pilih dulu kelas kamu yang sekarang.',
            'kelas_tujuan_id.exists' => 'Kelas yang dipilih tidak ditemukan.',
            'catatan_perbaikan_kelas.max' => 'Catatan maksimal 500 karakter.',
        ]);

        if ($data['jawaban'] === 'ya') {
            $siswa->status_konfirmasi_kelas = true;
            $siswa->waktu_tidak_konfirmasi = null;
            $siswa->kelas_tujuan_id = null;
            $siswa->catatan_perbaikan_kelas = null;
            $siswa->save();

            return redirect()->route('siswa.dashboard')
                ->with('success', 'Terima kasih, data kelas kamu sudah dikonfirmasi.');
        }

        // --- Siswa melaporkan kelasnya berubah ---
        $siswa->status_konfirmasi_kelas = false;
        $siswa->waktu_tidak_konfirmasi = now();
        $siswa->kelas_tujuan_id = $data['kelas_tujuan_id'];
        $siswa->catatan_perbaikan_kelas = $data['catatan_perbaikan_kelas'] ?? null;
        $siswa->save();

        // Muat ulang relasi supaya nama kelas terbaca untuk isi notifikasi.
        $siswa->load(['kelas', 'kelasTujuan']);

        $kelasLama = $siswa->kelas->nama_kelas ?? 'belum diisi';
        $kelasBaru = $siswa->kelasTujuan->nama_kelas ?? 'tidak diketahui';

        $pesan = "Siswa {$user->name} melaporkan kelasnya berubah: {$kelasLama} → {$kelasBaru}.";
        if (! empty($siswa->catatan_perbaikan_kelas)) {
            $pesan .= " Catatan: \"{$siswa->catatan_perbaikan_kelas}\"";
        }

        // Notifikasi ditulis dalam try/catch supaya kegagalan mengirim
        // pemberitahuan TIDAK ikut menggagalkan laporan siswa. Laporannya
        // sendiri sudah tersimpan di baris-baris di atas; notifikasi hanya
        // pengantar pesan. Kalau ini melempar exception dan tidak ditangkap,
        // siswa melihat halaman error padahal datanya sebenarnya sudah masuk,
        // lalu ia mencoba lagi dan lagi.
        try {
            Notifikasi::create([
                'user_id' => null, // null = ditujukan ke TU/Admin, bukan user tertentu
                'judul'   => 'Perbaikan Data Kelas',
                'pesan'   => $pesan,
                'link'    => route('tu.users.perbaikan-kelas'),
                'is_read' => false,
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal membuat notifikasi perbaikan kelas.', [
                'siswa_id' => $siswa->id,
                'pesan' => $e->getMessage(),
            ]);
        }

        $batas = $siswa->batasTenggangKelas();

        return redirect()->route('siswa.dashboard')->with(
            'success',
            'Laporan kamu sudah dikirim ke TU. Kamu masih bisa memakai aplikasi sampai '
            . ($batas ? $batas->translatedFormat('d F Y') : 'batas waktu yang ditentukan') . '.'
        );
    }
}
