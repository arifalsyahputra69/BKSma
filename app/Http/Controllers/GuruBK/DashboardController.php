<?php

namespace App\Http\Controllers\GuruBK;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    // HALAMAN DASHBOARD UTAMA
    public function index()
    {
        $guruBkId = Auth::id();

        // Helper scope: batasi query LogAktivitas hanya ke siswa yang
        // kelasnya dibina oleh Guru BK yang sedang login.
        // Rantai relasi: LogAktivitas -> user (User) -> siswa (Siswa) -> kelas (Kelas.id_guru_bk)
        $scopeLogKeGuruBk = function ($query) use ($guruBkId) {
            $query->whereHas('user.siswa.kelas', function ($q) use ($guruBkId) {
                $q->where('id_guru_bk', $guruBkId);
            });
        };

        // 1. Total Siswa (Hanya menghitung siswa di kelas binaan Guru BK ini)
        $totalSiswa = Siswa::whereHas('kelas', function($q) use ($guruBkId) {
            $q->where('id_guru_bk', $guruBkId);
        })->count();

        // 2. Total Percakapan
        // PERBAIKAN AUDIT (Poin 2, 19 Juli 2026): sebelumnya LogAktivitas::count() menghitung
        // SELURUH percakapan chatbot di sekolah (global, lintas Guru BK).
        // Sekarang di-scope hanya ke siswa binaan Guru BK yang login.
        // PERBAIKAN AUDIT (Bagian I, 22 Juli 2026): sejak Siswa\ChatbotController
        // mencatat 1 baris log untuk SETIAP balasan chatbot (bukan cuma 1x/hari/siswa
        // seperti sebelumnya), angka ini sekarang benar-benar merepresentasikan
        // "Total Percakapan" (tiap tanya-jawab), bukan lagi sekadar "jumlah siswa unik yang chat hari itu".
        $totalPercakapan = LogAktivitas::where($scopeLogKeGuruBk)->count();

        // 3. Siswa Aktif Hari Ini
        // PERBAIKAN AUDIT (Poin 2): sama seperti di atas, di-scope ke siswa binaan.
        $percakapanAktif = LogAktivitas::where($scopeLogKeGuruBk)
            ->whereDate('created_at', today())
            ->distinct('user_id')
            ->count('user_id');

        $jurnalTersimpan = 0; // Biarkan 0 dahulu karena fitur jurnal belum dibuat

        // 4. Log Aktivitas Terkini
        // PERBAIKAN AUDIT (Poin 2): di-scope ke siswa binaan Guru BK yang login.
        $logTerbaru = LogAktivitas::where($scopeLogKeGuruBk)
            ->with('user')
            ->latest()
            ->take(5)
            ->get();

        return view('gurubk.dashboard.index', compact(
            'totalSiswa', 
            'totalPercakapan', 
            'percakapanAktif', 
            'jurnalTersimpan',
            'logTerbaru'
        ));
    }
    // BUGFIX (AUDIT): rute gurubk.verifikasi.index (GET /gurubk/verifikasi-ortu)
    // memanggil method ini, tapi methodnya tidak pernah ada di controller --
    // hasilnya "Call to undefined method ...::verifikasiOrtuIndex()" (error 500)
    // setiap kali Guru BK membuka menu Verifikasi Data Ortu. Terbukti di
    // storage/logs/laravel.log. Method ini melengkapinya: isinya sama dengan
    // dataSiswaIndex() tapi disaring ke siswa yang datanya BENAR-BENAR perlu
    // diverifikasi (is_wa_verified = 0 / pending), sesuai nama menunya.
    public function verifikasiOrtuIndex()
    {
        $guruBkId = Auth::id();

        $siswa = Siswa::with(['user', 'kelas'])
            ->whereHas('kelas', function ($q) use ($guruBkId) {
                $q->where('id_guru_bk', $guruBkId);
            })
            ->whereNotNull('no_wa_ortu')
            ->where('is_wa_verified', 0)
            ->orderBy('updated_at')
            ->get();

        return view('gurubk.data-siswa.index', compact('siswa'));
    }

    // 1. HALAMAN DAFTAR DATA SISWA
    public function dataSiswaIndex()
    {
        $guruBkId = Auth::id();

        // Ambil data siswa hanya dari kelas kelolaan Guru BK ini
        // Urutkan agar yang '0' (Pending / Butuh Verifikasi) berada di paling atas
        $siswa = Siswa::with(['user', 'kelas'])
            ->whereHas('kelas', function($q) use ($guruBkId) {
                $q->where('id_guru_bk', $guruBkId);
            })
            ->orderByRaw('ISNULL(is_wa_verified), is_wa_verified ASC')
            ->get();
                      
        return view('gurubk.data-siswa.index', compact('siswa'));
    }

    // 2. HALAMAN DETAIL DATA SISWA
    public function dataSiswaShow($id)
    {
        $guruBkId = Auth::id();

        // Cari data siswa dan pastikan siswa tersebut berada di kelas kelolaan Guru BK ini
        $siswa = Siswa::with('user')
            ->whereHas('kelas', function($q) use ($guruBkId) {
                $q->where('id_guru_bk', $guruBkId);
            })
            ->findOrFail($id);

        return view('gurubk.data-siswa.show', compact('siswa'));
    }

    // 3. FUNGSI VERIFIKASI / TOLAK DATA ORANG TUA
    //
    // PERBAIKAN BUG (5 Agustus 2026). Laporan: Guru BK menekan "Verifikasi",
    // halaman terlihat sama sekali tidak berubah, dan di akun siswa statusnya
    // tetap "Menunggu". Ada TIGA hal berbeda yang bisa menyebabkan gejala itu,
    // dan versi lama tidak bisa membedakannya karena gagal tanpa suara:
    //
    //   (a) Field 'action' tidak sampai ke server -> jatuh ke cabang terakhir
    //       yang dulu hanya `return back()` tanpa pesan apa pun.
    //   (b) update() ditolak diam-diam oleh mass assignment. update() TIDAK
    //       melempar exception kalau kolomnya tidak ada di $fillable -- kolom
    //       itu hanya dibuang, query UPDATE tetap jalan, dan nilainya tidak
    //       pernah berubah. Kalau salinan Siswa.php di server berbeda dari
    //       yang di komputer lokal, inilah yang terjadi.
    //   (c) Query-nya jalan tapi nilainya tidak benar-benar tersimpan.
    //
    // Penanganannya sekarang:
    //   - 'action' divalidasi eksplisit, jadi (a) memunculkan pesan merah.
    //   - Nilai di-set langsung ke properti model lalu save(), BUKAN update()
    //     dengan array. Cara ini sama sekali tidak melewati filter $fillable,
    //     sehingga (b) tidak mungkin lagi terjadi.
    //   - Setelah simpan, baris dibaca ULANG dari database dan diperiksa. Kalau
    //     ternyata masih belum berubah, Guru BK diberi tahu terang-terangan dan
    //     kejadiannya dicatat ke log -- bukan dibiarkan terlihat "berhasil".
    public function approveOrtu(Request $request, $id)
    {
        $guruBkId = Auth::id();

        // Validasi keamanan: Pastikan Guru BK hanya bisa memproses siswa binaannya sendiri
        $siswa = Siswa::whereHas('kelas', function($q) use ($guruBkId) {
            $q->where('id_guru_bk', $guruBkId);
        })->findOrFail($id);

        $data = $request->validate([
            'action' => 'required|in:approve,reject',
        ], [
            'action.required' => 'Tombol yang ditekan tidak mengirimkan tindakan apa pun ke server. '
                . 'Tekan langsung tombol "Ya, Verifikasi" atau "Ya, Tolak Data" di dalam kotak konfirmasi.',
            'action.in' => 'Tindakan tidak dikenali, data tidak diubah.',
        ]);

        // JIKA GURU BK MENEKAN TOMBOL "TOLAK DATA"
        if ($data['action'] === 'reject') {
            $siswa->nama_ortu = null;
            $siswa->no_wa_ortu = null;
            $siswa->is_wa_verified = null;
            $siswa->save();

            Log::info('Data orang tua ditolak oleh Guru BK.', [
                'siswa_id' => $siswa->id,
                'guru_bk_id' => $guruBkId,
            ]);

            return back()->with('success', 'Data orang tua ditolak. Menunggu siswa menginput ulang.');
        }

        // JIKA GURU BK MENEKAN TOMBOL "VERIFIKASI"
        $siswa->is_wa_verified = 1;
        $siswa->save();

        // Baca ulang baris ini dari database. Ini satu-satunya cara memastikan
        // nilainya BENAR-BENAR tersimpan, bukan sekadar berubah di memori PHP.
        $siswa->refresh();

        if ((int) $siswa->is_wa_verified !== 1) {
            Log::error('Verifikasi data ortu tidak tersimpan ke database.', [
                'siswa_id' => $siswa->id,
                'guru_bk_id' => $guruBkId,
                'nilai_setelah_simpan' => $siswa->is_wa_verified,
            ]);

            return back()->with(
                'error',
                'Verifikasi TIDAK tersimpan ke database. Hubungi pengelola sistem '
                . 'dan sebutkan NISN siswa ini -- penyebabnya sudah dicatat di log server.'
            );
        }

        Log::info('Data orang tua diverifikasi oleh Guru BK.', [
            'siswa_id' => $siswa->id,
            'guru_bk_id' => $guruBkId,
        ]);

        return back()->with('success', 'Data orang tua berhasil diverifikasi.');
    }

    // FITUR BARU (30 Juli 2026): Guru BK bisa mengoreksi langsung Nama/No WA
    // orang tua tanpa harus menolak data (yang akan menghapus semuanya dan
    // memaksa siswa mengisi ulang dari awal). Dipakai misalnya kalau nomor WA
    // orang tua yang lama sudah tidak aktif dan siswa melaporkannya langsung
    // ke Guru BK -- Guru BK cukup ganti nomornya di sini.
    //
    // Sengaja TIDAK mengubah is_wa_verified: kalau datanya sebelumnya sudah
    // terverifikasi, tetap dianggap terverifikasi setelah dikoreksi Guru BK
    // (karena koreksi ini datang dari staf terpercaya, bukan input mandiri
    // siswa yang perlu diverifikasi ulang). Kalau datanya masih pending,
    // tetap pending seperti sebelumnya (Guru BK masih bisa memverifikasi
    // lewat tombol Verifikasi/Tolak seperti biasa setelah dikoreksi).
    public function updateOrtu(Request $request, $id)
    {
        $guruBkId = Auth::id();

        // Validasi keamanan: Pastikan Guru BK hanya bisa memproses siswa binaannya sendiri
        $siswa = Siswa::whereHas('kelas', function ($q) use ($guruBkId) {
            $q->where('id_guru_bk', $guruBkId);
        })->findOrFail($id);

        $request->validate([
            'nama_ortu' => 'required|string|max:255',
            'no_wa_ortu' => 'required|string|max:20',
        ]);

        $siswa->update([
            'nama_ortu' => $request->nama_ortu,
            'no_wa_ortu' => $request->no_wa_ortu,
        ]);

        return back()->with('success', 'Data orang tua berhasil dikoreksi.');
    }
}