<?php

namespace App\Http\Controllers\GuruBK;

use App\Http\Controllers\Controller;
use App\Models\SesiKonseling;
use App\Models\AntrianKonseling;
use App\Models\Notifikasi;
use App\Models\PermintaanKonseling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SesiKonselingController extends Controller
{
    /**
     * Dashboard sesi.
     *
     * PERBAIKAN (26 Juli 2026): sebelumnya SEMUA sesi konseling yang pernah
     * dibuat (dari bulan/tahun berapa pun) selalu ditampilkan sekaligus,
     * sehingga daftar terus menumpuk dan menyulitkan Guru BK mencari sesi
     * bulan berjalan. Sekarang, tampilan default hanya menampilkan sesi
     * pada bulan & tahun yang sedang berjalan (atau bulan yang dipilih lewat
     * filter). Guru BK tetap bisa melihat riwayat bulan lain lewat filter,
     * atau memilih "Semua Bulan" untuk menampilkan seluruh riwayat.
     */
    public function index(Request $request)
    {
        $bulan = $request->input('bulan', now()->format('n'));
        $tahun = $request->input('tahun', now()->format('Y'));
        $tampilkanSemua = $request->boolean('semua');

        $query = SesiKonseling::with([
            'antrians.siswa.user',
            'antrians',
        ])->where('guru_bk_id', Auth::id());

        if (! $tampilkanSemua) {
            $query->whereYear('tanggal', $tahun)
                ->whereMonth('tanggal', $bulan);
        }

        // PERBAIKAN (27 Juli 2026 - bugfix #3): sesi yang statusnya "Dibuka"
        // (sedang berjalan) diurutkan ke paling atas, supaya Guru BK tidak
        // perlu scroll ke bawah untuk menemukan sesi yang sedang aktif di
        // antara riwayat sesi-sesi lain yang sudah ditutup/dibatalkan.
        $sesis = $query
            ->orderByRaw("CASE WHEN status = 'Dibuka' THEN 0 ELSE 1 END ASC")
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // PERBAIKAN (26 Juli 2026 - bugfix #2): kolom `tanggal` pada model
        // SesiKonseling TIDAK di-cast ke Carbon (tetap string biasa), jadi
        // harus di-parse manual dengan Carbon::parse() dulu sebelum dipakai
        // ->format(). Pola yang sama juga dipakai di file blade sesi/index.
        $daftarBulanTahun = SesiKonseling::where('guru_bk_id', Auth::id())
            ->select('tanggal')
            ->get()
            ->map(function ($item) {
                $tgl = \Carbon\Carbon::parse($item->tanggal);

                return [
                    'tahun' => $tgl->format('Y'),
                    'bulan' => $tgl->format('m'),
                ];
            })
            ->unique(fn ($item) => $item['tahun'] . '-' . $item['bulan'])
            ->sortByDesc(fn ($item) => $item['tahun'] . $item['bulan'])
            ->values();

        // DAFTAR TUNGGU (6 Agustus 2026): siswa yang mendaftar saat tidak ada
        // sesi terbuka. Ditandai dulu yang sudah lewat masa berlaku supaya
        // angka yang tampil benar-benar mencerminkan yang akan dialihkan.
        PermintaanKonseling::tandaiYangKedaluwarsa(
            PermintaanKonseling::where('guru_bk_id', Auth::id())
        );

        $daftarTunggu = PermintaanKonseling::with(['siswa.user', 'siswa.kelas'])
            ->where('guru_bk_id', Auth::id())
            ->aktif()
            ->orderBy('created_at')
            ->get();

        return view('gurubk.sesi.index', compact(
            'sesis',
            'bulan',
            'tahun',
            'tampilkanSemua',
            'daftarBulanTahun',
            'daftarTunggu'
        ));
    }

    /**
     * Guru BK membuka sesi
     */
    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'nama_sesi' => 'required|string|max:100',
            'tempat' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ]);

        // ============================================================
        // MEMBUKA SESI + MENGALIHKAN DAFTAR TUNGGU (6 Agustus 2026)
        // ============================================================
        // Siswa yang mendaftar saat belum ada sesi (lihat
        // Siswa\SesiKonselingController::daftarTunggu) langsung mendapat nomor
        // antrean di sesi yang baru dibuka ini -- tanpa perlu membuka aplikasi
        // lagi dan mengambil antrean sendiri. Itulah inti janji fiturnya.
        //
        // Pembuatan sesi dan konversinya dibungkus satu transaction supaya
        // tidak mungkin terjadi keadaan setengah jadi: sesi terlanjur dibuka
        // tapi sebagian siswa gagal dialihkan dan permintaannya sudah terlanjur
        // ditandai "Dialihkan".
        [$sesi, $dialihkan] = DB::transaction(function () use ($request) {

            $sesi = SesiKonseling::create([
                'guru_bk_id' => Auth::id(),
                'tanggal' => $request->tanggal,
                'nama_sesi' => $request->nama_sesi,
                'tempat' => $request->tempat,
                'keterangan' => $request->keterangan,
                'status' => 'Dibuka',
            ]);

            // lockForUpdate: kalau Guru BK (atau dua tab yang sama) menekan
            // "Buka Sesi" nyaris bersamaan, permintaan yang sama tidak terbaca
            // dua kali dan berubah jadi dua antrean.
            $permintaans = PermintaanKonseling::where('guru_bk_id', Auth::id())
                ->aktif()
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            $dialihkan = collect();
            $nomor = 0;

            foreach ($permintaans as $permintaan) {
                // Urutan nomor mengikuti waktu mendaftar: yang paling dulu
                // meminta konseling dapat nomor paling awal. Sesi ini baru
                // dibuat sehingga belum ada antrean lain, jadi penomoran aman
                // dimulai dari 1.
                $nomor++;

                $antrian = AntrianKonseling::create([
                    'sesi_konseling_id' => $sesi->id,
                    'siswa_id' => $permintaan->siswa_id,
                    'nomor_antrian' => $nomor,
                    'keperluan' => $permintaan->keperluan,
                    'status' => 'Menunggu',
                    // Sengaja memakai waktu siswa mendaftar di daftar tunggu,
                    // bukan waktu konversi. Dengan begitu laporan waktu tunggu
                    // di dashboard Kepsek menggambarkan lama siswa benar-benar
                    // menunggu, bukan hanya jarak sejak sesi dibuka.
                    'waktu_booking' => $permintaan->created_at,
                ]);

                $permintaan->update([
                    'status' => 'Dialihkan',
                    'antrian_id' => $antrian->id,
                ]);

                $dialihkan->push([
                    'siswa' => $permintaan->siswa,
                    'nomor' => $nomor,
                ]);
            }

            return [$sesi, $dialihkan];
        });

        // Notifikasi dikirim SETELAH transaction selesai. Kalau pembuatan
        // notifikasi gagal (misalnya tabelnya bermasalah), antrean siswa tetap
        // sah dan tidak ikut dibatalkan -- yang hilang cuma pemberitahuannya.
        foreach ($dialihkan as $item) {
            $siswa = $item['siswa'];

            if (! $siswa || ! $siswa->user_id) {
                continue;
            }

            try {
                Notifikasi::create([
                    'user_id' => $siswa->user_id,
                    'judul'   => 'Antrean Konseling Kamu Sudah Aktif',
                    'pesan'   =>
                        "Guru BK membuka sesi \"{$sesi->nama_sesi}\" pada " .
                        \Carbon\Carbon::parse($sesi->tanggal)->translatedFormat('l, d F Y') . ".\n\n" .
                        "Permintaan konseling yang kamu ajukan sebelumnya sudah otomatis " .
                        "menjadi nomor antrean " . sprintf('%02d', $item['nomor']) . ".\n\n" .
                        "Tempat: {$sesi->tempat}\n\n" .
                        "Kamu tidak perlu mengambil antrean lagi.",
                    'link'    => route('siswa.sesi.index'),
                    'is_read' => false,
                ]);
            } catch (\Throwable $e) {
                Log::error('Gagal mengirim notifikasi pengalihan daftar tunggu: ' . $e->getMessage());
            }
        }

        $pesan = 'Sesi berhasil dibuka.';

        if ($dialihkan->count() > 0) {
            $pesan .= ' ' . $dialihkan->count() . ' siswa dari daftar tunggu ' .
                'otomatis mendapat nomor antrean dan sudah diberi tahu.';
        }

        return back()->with('success', $pesan);
    }

    /**
     * Tutup sesi
     */
    public function tutup($id)
    {
        $sesi = SesiKonseling::where('guru_bk_id', Auth::id())->findOrFail($id);

        $sesi->update([
            'status' => 'Ditutup',
        ]);

        return back()->with('success', 'Sesi berhasil ditutup.');
    }

    /**
     * Tombol tunggal
     * Panggil Antrean Berikutnya
     */
    public function panggilBerikutnya($id)
    {
        $sesi = SesiKonseling::where('guru_bk_id', Auth::id())->findOrFail($id);

        // Selesaikan antrean yang sedang dipanggil
        AntrianKonseling::where('sesi_konseling_id', $id)
            ->where('status', 'Dipanggil')
            ->update([
                'status' => 'Selesai',
            ]);

        // Cari antrean berikutnya
        $berikutnya = AntrianKonseling::where('sesi_konseling_id', $id)
            ->where('status', 'Menunggu')
            ->orderBy('nomor_antrian', 'asc')
            ->first();

        if (! $berikutnya) {
            return back()->with(
                'success',
                'Semua antrean pada sesi ini telah selesai.'
            );
        }

        $berikutnya->update([
            'status' => 'Dipanggil',
            // Area Monitoring #6: rekam SAAT status berubah jadi Dipanggil,
            // supaya waktu tunggu (waktu_dipanggil - waktu_booking) bisa dihitung
            // akurat oleh Kepsek\DashboardController, walau antrean ini nanti
            // lanjut lagi ke status "Selesai" dan updated_at berubah lagi.
            'waktu_dipanggil' => now(),
        ]);

        return back()->with(
            'success',
            'Memanggil nomor antrean ' . $berikutnya->nomor_antrian
        );
    }

    public function batalkan(Request $request, $id)
    {
        $request->validate([
            'alasan_pembatalan' => 'required|string|max:1000',
        ]);

        $sesi = SesiKonseling::with('antrians.siswa')
            ->where('guru_bk_id', Auth::id())
            ->findOrFail($id);

        // Simpan status sesi
        $sesi->update([
            'status' => 'Dibatalkan',
            'alasan_pembatalan' => $request->alasan_pembatalan,
        ]);

        // Ambil seluruh antrean yang belum selesai
        $antrians = AntrianKonseling::with('siswa')
            ->where('sesi_konseling_id', $id)
            ->whereIn('status', ['Menunggu', 'Dipanggil'])
            ->get();

        foreach ($antrians as $antrian) {
            // Ubah status antrean
            $antrian->update([
                'status' => 'Dibatalkan',
            ]);

            // Kirim notifikasi
            if ($antrian->siswa && $antrian->siswa->user_id) {
                Notifikasi::create([
                    'user_id' => $antrian->siswa->user_id,
                    'judul' => 'Sesi Konseling Dibatalkan',
                    'pesan' =>
                        "Sesi {$sesi->nama_sesi} dibatalkan oleh Guru BK.\n\n" .
                        "Alasan:\n" .
                        $request->alasan_pembatalan .
                        "\n\nSilakan mengambil antrean kembali ketika Guru BK membuka sesi baru.",
                    'link' => route('siswa.sesi.index'),
                    'is_read' => false,
                ]);
            }
        }

        return back()->with(
            'success',
            'Sesi berhasil dibatalkan dan notifikasi telah dikirim ke seluruh siswa.'
        );
    }
}