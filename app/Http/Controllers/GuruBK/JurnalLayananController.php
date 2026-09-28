<?php

namespace App\Http\Controllers\GuruBK;

use App\Http\Controllers\Controller;
use App\Models\AntrianKonseling;
use App\Models\BimbinganKelompok;
use App\Models\JurnalHarian;
use App\Models\JurnalLayanan;
use App\Models\LayananKlasikal;
use App\Models\Kelas;
use App\Models\KunjunganRumah;
use App\Models\Notifikasi;
use App\Models\Siswa;
use App\Models\User;
use App\Services\FonnteService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class JurnalLayananController extends Controller
{
    public function __construct(protected FonnteService $fonnteService)
    {
    }

    // FASE 7 (+aturan baru): kirim WA ringkas ke orang tua setelah jurnal
    // konseling tersimpan -- TAPI HANYA kalau Guru BK menandai "Panggil Orang
    // Tua/Wali" ATAU tingkat pelanggaran siswa = "Berat". Konseling biasa
    // (belajar/karir/pribadi ringan, dsb) TIDAK mengirim WA supaya orang tua
    // tidak kebanjiran notifikasi untuk hal-hal yang tidak perlu diketahui.
    // Sengaja HANYA info umum (tanggal + keperluan) -- TIDAK PERNAH mengirim
    // uraian_masalah / pendekatan_teknik, supaya kerahasiaan konseling tetap
    // terjaga sesuai catatan di dokumen Kebutuhan Monitoring Kepsek.
    private function notifikasiOrtu(?Siswa $siswa, string $keperluan, mixed $tanggal, ?string $tingkatPelanggaran, bool $panggilOrtu, mixed $jadwalPertemuanOrtu = null): void
    {
        if (! $siswa || blank($siswa->no_wa_ortu) || ! $siswa->is_wa_verified) {
            return;
        }

        $pelanggaranBerat = $tingkatPelanggaran === 'Berat';

        // Bukan pemanggilan orang tua & bukan pelanggaran berat -> jangan kirim WA.
        if (! $panggilOrtu && ! $pelanggaranBerat) {
            return;
        }

        $alasan = $panggilOrtu ? 'panggil_ortu' : 'pelanggaran_berat';

        // PERBAIKAN: format jadwal pertemuan (tanggal + jam) supaya disertakan
        // di pesan WA -- sebelumnya orang tua hanya diminta "datang ke sekolah"
        // tanpa kejelasan kapan, sehingga membingungkan.
        $jadwalTerformat = filled($jadwalPertemuanOrtu)
            ? Carbon::parse($jadwalPertemuanOrtu)->translatedFormat('l, d F Y') . ' pukul ' . Carbon::parse($jadwalPertemuanOrtu)->format('H:i') . ' WIB'
            : null;

        $this->fonnteService->notifikasiOrtuSetelahKonseling(
            $siswa->no_wa_ortu,
            optional($siswa->user)->name ?? 'Ananda',
            $siswa->nama_ortu ?: 'Bapak/Ibu',
            Carbon::parse($tanggal)->translatedFormat('d F Y'),
            $keperluan,
            $alasan,
            $jadwalTerformat
        );
    }

    // Area Monitoring #7: sebelumnya alur "Panggil Orang Tua/Wali" ATAU
    // pelanggaran Berat HANYA memberi tahu orang tua lewat WA -- Wali Kelas
    // sama sekali tidak dilibatkan/diberi tahu, padahal dialah yang paling
    // dekat dengan kondisi harian siswa di kelas dan sering jadi pihak yang
    // dihubungi duluan oleh orang tua. Method ini melengkapi kekosongan itu
    // dengan notifikasi IN-APP (bukan WA, karena Wali Kelas sudah login ke
    // sistem) ke Kelas::waliKelas, dikirim di momen yang SAMA dengan WA ke
    // orang tua (panggil_ortu ATAU tingkat_pelanggaran = Berat).
    // Sama seperti pesan ke orang tua: sengaja HANYA info umum (keperluan,
    // tanggal, status pemanggilan, jadwal pertemuan) -- TIDAK PERNAH
    // menyertakan uraian_masalah / pendekatan_teknik, supaya kerahasiaan
    // konseling tetap terjaga.
    private function notifikasiWaliKelas(?Siswa $siswa, string $keperluan, mixed $tanggal, ?string $tingkatPelanggaran, bool $panggilOrtu, mixed $jadwalPertemuanOrtu = null): void
    {
        if (! $siswa) {
            return;
        }

        $kelas = $siswa->kelas;
        if (! $kelas || ! $kelas->wali_kelas_id) {
            return;
        }

        $pelanggaranBerat = $tingkatPelanggaran === 'Berat';

        if (! $panggilOrtu && ! $pelanggaranBerat) {
            return;
        }

        $namaSiswa = optional($siswa->user)->name ?? 'Siswa';
        $tanggalFormat = Carbon::parse($tanggal)->translatedFormat('d F Y');

        $alasanText = $pelanggaranBerat
            ? 'tercatat melakukan pelanggaran dengan tingkat *Berat*'
            : 'sedang dalam proses pemanggilan orang tua oleh Guru BK';

        $jadwalText = filled($jadwalPertemuanOrtu)
            ? 'Jadwal pertemuan dengan orang tua: ' . Carbon::parse($jadwalPertemuanOrtu)->translatedFormat('l, d F Y') . ' pukul ' . Carbon::parse($jadwalPertemuanOrtu)->format('H:i') . ' WIB.'
            : 'Jadwal pertemuan dengan orang tua belum ditentukan Guru BK.';

        Notifikasi::create([
            'user_id' => $kelas->wali_kelas_id,
            'judul' => 'Siswa Perlu Perhatian: Keterlibatan Orang Tua',
            'pesan' => "Ananda {$namaSiswa} (kelas {$kelas->nama_kelas}) {$alasanText} dan mendapat layanan Bimbingan Konseling pada {$tanggalFormat} terkait: {$keperluan}.\n\n"
                . $jadwalText . "\n\n"
                . 'Mohon berkoordinasi dengan Guru BK terkait tindak lanjutnya. Detail isi konseling tetap bersifat rahasia dan tidak ditampilkan di sini.',
            'link' => route('walikelas.dashboard'),
            'is_read' => false,
        ]);
    }

    // FASE 8: Notifikasi kasus darurat + approval DO/skorsing. Begitu Guru BK
    // menandai kasus butuh_persetujuan_kepsek, SEMUA user role "Kepala
    // Sekolah" diberi tahu in-app supaya kasus darurat tidak menunggu Kepsek
    // membuka dashboard dulu untuk menyadarinya. Sengaja HANYA info umum
    // (kategori masalah, tingkat pelanggaran, jenis tindakan yang diusulkan)
    // -- TIDAK PERNAH menyertakan uraian_masalah / pendekatan_teknik.
    private function notifikasiKepsek(?Siswa $siswa, ?string $tingkatPelanggaran, string $jenisTindakan): void
    {
        $namaSiswa = optional(optional($siswa)->user)->name ?? 'Siswa';
        $kelas = optional($siswa)->kelas;
        $namaKelas = $kelas ? $kelas->nama_kelas : '-';

        $kepsekUsers = User::role('Kepala Sekolah')->get();

        foreach ($kepsekUsers as $kepsek) {
            Notifikasi::create([
                'user_id' => $kepsek->id,
                'judul' => 'Kasus Darurat: Usulan ' . $jenisTindakan,
                'pesan' => "Guru BK mengusulkan tindakan \"{$jenisTindakan}\" untuk Ananda {$namaSiswa} (kelas {$namaKelas})"
                    . ($tingkatPelanggaran ? " terkait pelanggaran tingkat {$tingkatPelanggaran}." : '.')
                    . "\n\nMohon segera ditinjau di menu Kasus Darurat. Detail isi konseling tetap bersifat rahasia dan tidak ditampilkan di sini.",
                'link' => route('kepsek.kasus-darurat.index'),
                'is_read' => false,
            ]);
        }
    }

    /**
     * BUGFIX (AUDIT): query dasar "jurnal konseling yang boleh saya
     * edit/hapus". Kepemilikan diakui lewat DUA jalur:
     *  a. guru_bk_id = saya (jurnal individu manual, dan -- setelah perbaikan
     *     di storeJurnal() -- jurnal baru dari antrean), ATAU
     *  b. jurnal itu menempel pada antrean di sesi milik saya. Jalur (b) wajib
     *     ada supaya baris LAMA yang terlanjur tersimpan dengan guru_bk_id
     *     NULL (sebelum perbaikan) tetap bisa diedit/dihapus, bukan 404 terus.
     * Catatan pelanggaran (jenis_catatan = 'Pelanggaran') sengaja dikecualikan
     * -- ada endpoint tersendiri untuk itu.
     */
    private function jurnalKonselingMilikSaya()
    {
        $guruBkId = Auth::id();

        return JurnalLayanan::where(function ($query) use ($guruBkId) {
            $query->where('guru_bk_id', $guruBkId)
                ->orWhereHas('antrian.sesi', function ($q) use ($guruBkId) {
                    $q->where('guru_bk_id', $guruBkId);
                });
        })->where(function ($query) {
            $query->whereNull('jenis_catatan')->orWhere('jenis_catatan', '!=', 'Pelanggaran');
        });
    }

    public function index()
    {
        // 1. Antrean yang sudah "Selesai" dilayani pada sesi milik Guru BK ini
        $dariAntrian = AntrianKonseling::with(['siswa.user', 'siswa.kelas', 'jurnal'])
            ->whereHas('sesi', function ($query) {
                $query->where('guru_bk_id', Auth::id());
            })
            ->where('status', 'Selesai')
            ->get()
            ->map(function ($antrian) {
                return (object) [
                    'tipe' => 'antrian',
                    'key' => 'antrian-' . $antrian->id,
                    'antrian_id' => $antrian->id,
                    'siswa' => $antrian->siswa,
                    'keperluan' => $antrian->keperluan,
                    'tanggal' => $antrian->updated_at,
                    'jurnal' => $antrian->jurnal,
                ];
            });

        // 2. Jurnal individu yang ditulis manual oleh Guru BK (di luar antrean sesi)
        $manual = JurnalLayanan::with(['siswa.user', 'siswa.kelas'])
            ->whereNull('antrian_id')
            ->where('guru_bk_id', Auth::id())
            ->get()
            ->map(function ($jurnal) {
                return (object) [
                    'tipe' => 'manual',
                    'key' => 'manual-' . $jurnal->id,
                    'antrian_id' => null,
                    'siswa' => $jurnal->siswa,
                    'keperluan' => $jurnal->keperluan,
                    'tanggal' => $jurnal->tanggal_konseling,
                    'jurnal' => $jurnal,
                ];
            });

        // PERBAIKAN (30 Juli 2026): sebelumnya SEMUA riwayat (tanpa batas)
        // dirender ke tabel, dan setiap baris yang sudah ada jurnalnya
        // memunculkan 2 modal penuh (Detail + Edit, termasuk textarea isi
        // konseling) di dalam DOM. Begitu riwayat menumpuk, halaman jadi
        // berat -- membuka "Lihat Detail" pun terasa lambat karena browser
        // harus memproses ratusan modal tersembunyi sekaligus. Sekarang
        // riwayat dipaginasi (15/halaman) supaya modal yang dirender juga
        // hanya untuk baris yang sedang tampil.
        $riwayatSelesaiSemua = $dariAntrian->concat($manual)
            ->sortByDesc('tanggal')
            ->values();

        $perHalamanRiwayat = 15;
        $halamanRiwayat = LengthAwarePaginator::resolveCurrentPage('page_riwayat');
        $riwayatSelesai = new LengthAwarePaginator(
            $riwayatSelesaiSemua->forPage($halamanRiwayat, $perHalamanRiwayat)->values(),
            $riwayatSelesaiSemua->count(),
            $perHalamanRiwayat,
            $halamanRiwayat,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'page_riwayat']
        );

        // Daftar siswa kelolaan Guru BK ini, untuk dropdown "Tambah Jurnal Individu"
        $siswaKelolaan = Siswa::with(['user', 'kelas'])
            ->whereHas('kelas', function ($query) {
                $query->where('id_guru_bk', Auth::id());
            })
            ->get();

        // Daftar kelas yang diampu Guru BK ini, untuk filter "Kelas" pada
        // dropdown Siswa di modal Tambah Jurnal Individu
        $kelasKelolaan = Kelas::where('id_guru_bk', Auth::id())
            ->orderBy('nama_kelas')
            ->get();

        // FASE 8: daftar kunjungan rumah (home visit) milik Guru BK ini,
        // dipakai untuk mengisi tab "Home Visit" di halaman Rekap Laporan.
        $kunjunganRumah = KunjunganRumah::with(['siswa.user', 'siswa.kelas'])
            ->where('guru_bk_id', Auth::id())
            ->latest('tanggal_kunjungan')
            ->get();

        // PERBAIKAN (23 Juli 2026): daftar Jurnal Harian, dipakai untuk
        // mengisi tab "Jurnal Harian" yang sebelumnya cuma mockup kosong.
        $jurnalHarian = JurnalHarian::where('guru_bk_id', Auth::id())
            ->orderByDesc('tanggal_waktu')
            ->get();

        // PERBAIKAN (23 Juli 2026): daftar Bimbingan Kelompok & Layanan
        // Klasikal, dipakai untuk mengisi tab "Bimbingan Kelompok" dan
        // "Layanan Klasikal" yang sebelumnya cuma mockup kosong (modalnya
        // bukan <form> beneran sehingga tidak bisa disubmit sama sekali).
        $bimbinganKelompok = BimbinganKelompok::where('guru_bk_id', Auth::id())
            ->orderByDesc('tanggal_pelaksanaan')
            ->get();

        $layananKlasikal = LayananKlasikal::where('guru_bk_id', Auth::id())
            ->orderByDesc('tanggal_pelaksanaan')
            ->get();

        // PERBAIKAN (28 Juli 2026, revisi ke-3): rekap poin pelanggaran,
        // dulu ditampilkan di halaman "Data Pelanggaran" yang terpisah
        // (kartu "Siswa Poin Tertinggi"). Sekarang dihitung dari
        // jurnal_layanans (jenis_catatan = 'Pelanggaran') punya Guru BK ini.
        $rekapPoin = JurnalLayanan::with('siswa.user', 'siswa.kelas')
            ->whereHas('siswa.kelas', function ($query) {
                $query->where('id_guru_bk', Auth::id());
            })
            ->where('jenis_catatan', 'Pelanggaran')
            ->get()
            ->groupBy('siswa_id')
            ->map(fn ($rows) => (object) [
                'siswa' => $rows->first()->siswa,
                'total_poin' => $rows->sum('poin'),
                'jumlah_kasus' => $rows->count(),
            ])
            ->sortByDesc('total_poin')
            ->take(10)
            ->values();

        return view('gurubk.rekap-laporan.index', compact('riwayatSelesai', 'siswaKelolaan', 'kelasKelolaan', 'kunjunganRumah', 'jurnalHarian', 'bimbinganKelompok', 'layananKlasikal', 'rekapPoin'));
    }

    // Menyimpan jurnal untuk antrean yang sudah "Selesai" dilayani
    public function storeJurnal(Request $request, string $antrian_id)
    {
        $request->validate([
            'kategori_masalah' => 'required|string',
            'uraian_masalah' => 'required|string',
            'pendekatan_teknik' => 'nullable|string|max:255',
            'rencana_tindak_lanjut' => 'required|string',
            'status_kasus' => 'required|string',
            'tingkat_pelanggaran' => 'nullable|in:Ringan,Sedang,Berat',
            'panggil_ortu' => 'nullable|boolean',
            // PERBAIKAN: jadwal pertemuan wajib diisi kalau memang akan memicu
            // WA ke orang tua (dipanggil ATAU pelanggaran Berat), supaya pesan
            // WA yang dikirim tidak lagi kosong soal kapan orang tua harus datang.
            'jadwal_pertemuan_ortu' => ['nullable', 'date', function ($attribute, $value, $fail) use ($request) {
                $perluJadwal = $request->boolean('panggil_ortu') || $request->tingkat_pelanggaran === 'Berat';
                if ($perluJadwal && blank($value)) {
                    $fail('Jadwal pertemuan wajib diisi karena orang tua akan dihubungi via WA (dipanggil atau pelanggaran Berat).');
                }
            }],
            // FASE 8: kasus darurat + approval DO/skorsing.
            'butuh_persetujuan_kepsek' => 'nullable|boolean',
            'jenis_tindakan_diusulkan' => ['nullable', 'required_if:butuh_persetujuan_kepsek,1', 'in:Skorsing,Dikeluarkan (DO),Lainnya'],
        ]);

        // Pastikan antrean ini benar-benar milik sesi Guru BK yang sedang login,
        // supaya Guru BK lain tidak bisa menulis/menimpa jurnal siswa yang
        // bukan kelolaannya.
        $antrian = AntrianKonseling::whereHas('sesi', function ($query) {
            $query->where('guru_bk_id', Auth::id());
        })->findOrFail($antrian_id);

        $panggilOrtu = $request->boolean('panggil_ortu');
        $butuhPersetujuanKepsek = $request->boolean('butuh_persetujuan_kepsek');

        // BUGFIX (AUDIT): sebelumnya jurnal dari antrean disimpan TANPA
        // guru_bk_id & siswa_id (kolomnya dibiarkan NULL). Padahal
        // updateJurnal()/destroyJurnal() menyaring dengan
        // where('guru_bk_id', Auth::id()) -- jadi tombol Edit & Hapus di modal
        // Detail SELALU berakhir 404 untuk jurnal yang berasal dari antrean
        // sesi (hanya jurnal individu manual yang bisa diedit). Kedua kolom
        // sekarang ikut diisi supaya kepemilikannya jelas & konsisten dengan
        // storeManual().
        JurnalLayanan::updateOrCreate(
            [
                'antrian_id' => $antrian->id,
            ],
            [
                'guru_bk_id' => Auth::id(),
                'siswa_id' => $antrian->siswa_id,
                'kategori_masalah' => $request->kategori_masalah,
                'uraian_masalah' => $request->uraian_masalah,
                'pendekatan_teknik' => $request->pendekatan_teknik,
                'rencana_tindak_lanjut' => $request->rencana_tindak_lanjut,
                'status_kasus' => $request->status_kasus,
                'tingkat_pelanggaran' => $request->tingkat_pelanggaran,
                'panggil_ortu' => $panggilOrtu,
                'jadwal_pertemuan_ortu' => $request->jadwal_pertemuan_ortu,
                'butuh_persetujuan_kepsek' => $butuhPersetujuanKepsek,
                'jenis_tindakan_diusulkan' => $butuhPersetujuanKepsek ? $request->jenis_tindakan_diusulkan : null,
                'status_persetujuan_kepsek' => $butuhPersetujuanKepsek ? 'Menunggu' : null,
            ]
        );

        $this->notifikasiOrtu($antrian->siswa, $antrian->keperluan, now(), $request->tingkat_pelanggaran, $panggilOrtu, $request->jadwal_pertemuan_ortu);
        $this->notifikasiWaliKelas($antrian->siswa, $antrian->keperluan, now(), $request->tingkat_pelanggaran, $panggilOrtu, $request->jadwal_pertemuan_ortu);

        if ($butuhPersetujuanKepsek) {
            $this->notifikasiKepsek($antrian->siswa, $request->tingkat_pelanggaran, $request->jenis_tindakan_diusulkan);
        }

        return back()->with('success', 'Jurnal Layanan Konseling berhasil disimpan!');
    }

    // Menyimpan jurnal konseling individu yang dibuat manual oleh Guru BK,
    // tanpa melalui antrean sesi (mis. konseling dadakan / di luar antrean)
    public function storeManual(Request $request)
    {
        $request->validate([
            'siswa_id' => 'required|exists:siswas,id',
            'tanggal_konseling' => 'required|date',
            'keperluan' => 'required|string|max:255',
            'kategori_masalah' => 'required|string',
            'uraian_masalah' => 'required|string',
            'pendekatan_teknik' => 'nullable|string|max:255',
            'rencana_tindak_lanjut' => 'required|string',
            'status_kasus' => 'required|string',
            'tingkat_pelanggaran' => 'nullable|in:Ringan,Sedang,Berat',
            'panggil_ortu' => 'nullable|boolean',
            'jadwal_pertemuan_ortu' => ['nullable', 'date', function ($attribute, $value, $fail) use ($request) {
                $perluJadwal = $request->boolean('panggil_ortu') || $request->tingkat_pelanggaran === 'Berat';
                if ($perluJadwal && blank($value)) {
                    $fail('Jadwal pertemuan wajib diisi karena orang tua akan dihubungi via WA (dipanggil atau pelanggaran Berat).');
                }
            }],
            'butuh_persetujuan_kepsek' => 'nullable|boolean',
            'jenis_tindakan_diusulkan' => ['nullable', 'required_if:butuh_persetujuan_kepsek,1', 'in:Skorsing,Dikeluarkan (DO),Lainnya'],
        ]);

        // Pastikan siswa yang dipilih benar-benar siswa kelolaan Guru BK ini
        // (kelasnya diampu oleh Guru BK yang sedang login), supaya jurnal
        // rahasia siswa tidak bisa dibuat oleh Guru BK yang bukan wali BK-nya.
        $siswa = Siswa::whereHas('kelas', function ($query) {
            $query->where('id_guru_bk', Auth::id());
        })->findOrFail($request->siswa_id);

        $panggilOrtu = $request->boolean('panggil_ortu');
        $butuhPersetujuanKepsek = $request->boolean('butuh_persetujuan_kepsek');

        JurnalLayanan::create([
            'siswa_id' => $siswa->id,
            'guru_bk_id' => Auth::id(),
            'tanggal_konseling' => $request->tanggal_konseling,
            'keperluan' => $request->keperluan,
            'kategori_masalah' => $request->kategori_masalah,
            'uraian_masalah' => $request->uraian_masalah,
            'pendekatan_teknik' => $request->pendekatan_teknik,
            'rencana_tindak_lanjut' => $request->rencana_tindak_lanjut,
            'status_kasus' => $request->status_kasus,
            'tingkat_pelanggaran' => $request->tingkat_pelanggaran,
            'panggil_ortu' => $panggilOrtu,
            'jadwal_pertemuan_ortu' => $request->jadwal_pertemuan_ortu,
            'butuh_persetujuan_kepsek' => $butuhPersetujuanKepsek,
            'jenis_tindakan_diusulkan' => $butuhPersetujuanKepsek ? $request->jenis_tindakan_diusulkan : null,
            'status_persetujuan_kepsek' => $butuhPersetujuanKepsek ? 'Menunggu' : null,
        ]);

        $this->notifikasiOrtu($siswa, $request->keperluan, $request->tanggal_konseling, $request->tingkat_pelanggaran, $panggilOrtu, $request->jadwal_pertemuan_ortu);
        $this->notifikasiWaliKelas($siswa, $request->keperluan, $request->tanggal_konseling, $request->tingkat_pelanggaran, $panggilOrtu, $request->jadwal_pertemuan_ortu);

        if ($butuhPersetujuanKepsek) {
            $this->notifikasiKepsek($siswa, $request->tingkat_pelanggaran, $request->jenis_tindakan_diusulkan);
        }

        return back()->with('success', 'Jurnal Konseling Individu berhasil ditambahkan!');
    }

    // PERBAIKAN: sebelumnya jurnal konseling yang sudah disimpan (baik dari
    // antrean sesi maupun ditulis manual) tidak bisa diedit/dihapus lagi
    // lewat modal Detail -- method ini menutup kekurangan itu. Siswa/sumber
    // (antrian_id) sengaja TIDAK bisa diubah lewat sini, hanya isi jurnalnya.
    //
    // Mengedit di sini SENGAJA TIDAK mengirim ulang WA ke orang tua /
    // notifikasi Wali Kelas -- notifikasi itu hanya dipicu sekali saat
    // jurnal pertama kali disimpan (storeJurnal/storeManual), supaya orang
    // tua tidak kebanjiran WA berulang tiap kali Guru BK membetulkan catatan.
    // Pengecualian: kalau "butuh_persetujuan_kepsek" BARU dicentang di sini
    // (sebelumnya tidak), status di-reset ke "Menunggu" dan Kepsek dikirimi
    // notifikasi kasus darurat baru, karena sebelumnya Kepsek memang belum
    // pernah diberi tahu soal kasus ini sama sekali.
    public function updateJurnal(Request $request, string $id)
    {
        $jurnal = $this->jurnalKonselingMilikSaya()->findOrFail($id);

        $request->validate([
            'keperluan' => 'nullable|string|max:255',
            'tanggal_konseling' => 'nullable|date',
            'kategori_masalah' => 'required|string',
            'uraian_masalah' => 'required|string',
            'pendekatan_teknik' => 'nullable|string|max:255',
            'rencana_tindak_lanjut' => 'required|string',
            'status_kasus' => 'required|string',
            'tingkat_pelanggaran' => 'nullable|in:Ringan,Sedang,Berat',
            'panggil_ortu' => 'nullable|boolean',
            'jadwal_pertemuan_ortu' => ['nullable', 'date', function ($attribute, $value, $fail) use ($request) {
                $perluJadwal = $request->boolean('panggil_ortu') || $request->tingkat_pelanggaran === 'Berat';
                if ($perluJadwal && blank($value)) {
                    $fail('Jadwal pertemuan wajib diisi karena orang tua akan dihubungi via WA (dipanggil atau pelanggaran Berat).');
                }
            }],
            'butuh_persetujuan_kepsek' => 'nullable|boolean',
            'jenis_tindakan_diusulkan' => ['nullable', 'required_if:butuh_persetujuan_kepsek,1', 'in:Skorsing,Dikeluarkan (DO),Lainnya'],
        ]);

        $butuhPersetujuanKepsek = $request->boolean('butuh_persetujuan_kepsek');

        // Kepsek belum pernah diberi tahu soal kasus ini kalau sebelumnya
        // butuh_persetujuan_kepsek belum dicentang -- baru kirim notifikasi
        // & reset status kalau BARU dicentang di edit ini.
        $perluNotifikasiKepsekBaru = $butuhPersetujuanKepsek && ! $jurnal->butuh_persetujuan_kepsek;

        $data = [
            'kategori_masalah' => $request->kategori_masalah,
            'uraian_masalah' => $request->uraian_masalah,
            'pendekatan_teknik' => $request->pendekatan_teknik,
            'rencana_tindak_lanjut' => $request->rencana_tindak_lanjut,
            'status_kasus' => $request->status_kasus,
            'tingkat_pelanggaran' => $request->tingkat_pelanggaran,
            'panggil_ortu' => $request->boolean('panggil_ortu'),
            'jadwal_pertemuan_ortu' => $request->jadwal_pertemuan_ortu,
            'butuh_persetujuan_kepsek' => $butuhPersetujuanKepsek,
            'jenis_tindakan_diusulkan' => $butuhPersetujuanKepsek ? $request->jenis_tindakan_diusulkan : null,
        ];

        if ($perluNotifikasiKepsekBaru) {
            $data['status_persetujuan_kepsek'] = 'Menunggu';
        } elseif (! $butuhPersetujuanKepsek) {
            // Centang dilepas -> keluarkan dari menu Kasus Darurat Kepsek.
            $data['status_persetujuan_kepsek'] = null;
        }
        // Kalau tetap dicentang & sebelumnya sudah pernah diusulkan, status
        // persetujuan yang sudah ada (Menunggu/Disetujui/Ditolak) dibiarkan.

        // Jurnal manual (bukan dari antrean) boleh mengubah keperluan &
        // tanggal konseling; jurnal dari antrean sesi mengikuti tanggal
        // antreannya dan tidak ditampilkan di form edit ini.
        if (is_null($jurnal->antrian_id)) {
            $data['keperluan'] = $request->filled('keperluan') ? $request->keperluan : $jurnal->keperluan;
            $data['tanggal_konseling'] = $request->filled('tanggal_konseling') ? $request->tanggal_konseling : $jurnal->tanggal_konseling;
        }

        $jurnal->update($data);

        if ($perluNotifikasiKepsekBaru) {
            $siswaTerkait = $jurnal->siswa ?: optional($jurnal->antrian)->siswa;
            $this->notifikasiKepsek($siswaTerkait, $request->tingkat_pelanggaran, $request->jenis_tindakan_diusulkan);
        }

        return back()->with('success', 'Jurnal konseling berhasil diperbarui.');
    }

    public function destroyJurnal(string $id)
    {
        $jurnal = $this->jurnalKonselingMilikSaya()->findOrFail($id);

        $jurnal->delete();

        return back()->with('success', 'Jurnal konseling berhasil dihapus.');
    }

    // PERBAIKAN (28 Juli 2026, revisi ke-3): dulu "Data Pelanggaran" adalah
    // halaman & tabel terpisah (Pelanggaran model). Sekarang digabung ke
    // sini sebagai jenis_catatan = 'Pelanggaran' di tabel yang sama dengan
    // jurnal konseling, supaya riwayat siswa (konseling + pelanggaran) bisa
    // dilihat dalam satu timeline (lihat riwayatSiswa()).
    //
    // Kategori "Berat" otomatis memicu WA ke orang tua & notifikasi Wali
    // Kelas lewat pipeline yang SAMA dengan jurnal konseling "Berat" --
    // sudah dikonfirmasi ke Kepsek/pengguna, karena keduanya sama-sama
    // memakai kolom tingkat_pelanggaran.
    public function storePelanggaran(Request $request)
    {
        $request->validate([
            'siswa_id' => 'required|exists:siswas,id',
            'tanggal_kejadian' => 'required|date',
            'kategori' => 'required|in:Ringan,Sedang,Berat',
            'poin' => 'required|integer|min:0|max:100',
            'deskripsi' => 'required|string',
            'tindak_lanjut' => 'nullable|string',
            'jadwal_pertemuan_ortu' => ['nullable', 'date', function ($attribute, $value, $fail) use ($request) {
                if ($request->kategori === 'Berat' && blank($value)) {
                    $fail('Jadwal pertemuan wajib diisi karena orang tua akan dihubungi via WA (pelanggaran Berat).');
                }
            }],
        ]);

        // Pastikan siswa yang dipilih benar-benar siswa kelolaan Guru BK ini
        $siswa = Siswa::whereHas('kelas', function ($query) {
            $query->where('id_guru_bk', Auth::id());
        })->findOrFail($request->siswa_id);

        JurnalLayanan::create([
            'siswa_id' => $siswa->id,
            'guru_bk_id' => Auth::id(),
            'tanggal_konseling' => $request->tanggal_kejadian,
            'keperluan' => 'Pencatatan Pelanggaran',
            'jenis_catatan' => 'Pelanggaran',
            'kategori_masalah' => 'Pelanggaran Tata Tertib',
            'uraian_masalah' => $request->deskripsi,
            'rencana_tindak_lanjut' => $request->tindak_lanjut,
            'status_kasus' => 'Selesai',
            'tingkat_pelanggaran' => $request->kategori,
            'poin' => $request->poin,
            'panggil_ortu' => false,
            'jadwal_pertemuan_ortu' => $request->jadwal_pertemuan_ortu,
        ]);

        $this->notifikasiOrtu($siswa, 'Pencatatan Pelanggaran', $request->tanggal_kejadian, $request->kategori, false, $request->jadwal_pertemuan_ortu);
        $this->notifikasiWaliKelas($siswa, 'Pencatatan Pelanggaran', $request->tanggal_kejadian, $request->kategori, false, $request->jadwal_pertemuan_ortu);

        return back()->with('success', 'Pelanggaran berhasil dicatat.');
    }

    public function updatePelanggaran(Request $request, string $id)
    {
        $jurnal = JurnalLayanan::where('guru_bk_id', Auth::id())
            ->where('jenis_catatan', 'Pelanggaran')
            ->findOrFail($id);

        $request->validate([
            'tanggal_kejadian' => 'required|date',
            'kategori' => 'required|in:Ringan,Sedang,Berat',
            'poin' => 'required|integer|min:0|max:100',
            'deskripsi' => 'required|string',
            'tindak_lanjut' => 'nullable|string',
        ]);

        $jurnal->update([
            'tanggal_konseling' => $request->tanggal_kejadian,
            'tingkat_pelanggaran' => $request->kategori,
            'poin' => $request->poin,
            'uraian_masalah' => $request->deskripsi,
            'rencana_tindak_lanjut' => $request->tindak_lanjut,
        ]);

        return back()->with('success', 'Data pelanggaran berhasil diperbarui.');
    }

    public function destroyPelanggaran(string $id)
    {
        $jurnal = JurnalLayanan::where('guru_bk_id', Auth::id())
            ->where('jenis_catatan', 'Pelanggaran')
            ->findOrFail($id);

        $jurnal->delete();

        return back()->with('success', 'Data pelanggaran berhasil dihapus.');
    }

    // Riwayat lengkap konseling seorang siswa (gabungan dari antrean sesi
    // dan jurnal manual), dipakai untuk melihat pola/tren masalah siswa
    // dari waktu ke waktu.
    public function riwayatSiswa(string $siswaId)
    {
        // Pastikan siswa yang dilihat memang kelolaan Guru BK ini
        $siswa = Siswa::with(['user', 'kelas'])
            ->whereHas('kelas', function ($query) {
                $query->where('id_guru_bk', Auth::id());
            })
            ->findOrFail($siswaId);

        $dariAntrian = AntrianKonseling::with('jurnal')
            ->where('siswa_id', $siswa->id)
            ->where('status', 'Selesai')
            ->get()
            ->map(function ($antrian) {
                return (object) [
                    'tipe' => 'antrian',
                    'key' => 'antrian-' . $antrian->id,
                    'keperluan' => $antrian->keperluan,
                    'tanggal' => $antrian->updated_at,
                    'jurnal' => $antrian->jurnal,
                ];
            });

        $manual = JurnalLayanan::whereNull('antrian_id')
            ->where('siswa_id', $siswa->id)
            ->get()
            ->map(function ($jurnal) {
                return (object) [
                    'tipe' => 'manual',
                    'key' => 'manual-' . $jurnal->id,
                    'keperluan' => $jurnal->keperluan,
                    'tanggal' => $jurnal->tanggal_konseling,
                    'jurnal' => $jurnal,
                ];
            });

        $riwayat = $dariAntrian->concat($manual)->sortByDesc('tanggal')->values();

        // Ringkasan statistik sederhana
        $totalSesi = $riwayat->count();
        $sudahAdaJurnal = $riwayat->filter(fn ($r) => $r->jurnal);
        $kategoriTerbanyak = $sudahAdaJurnal
            ->groupBy(fn ($r) => $r->jurnal->kategori_masalah)
            ->sortByDesc(fn ($grup) => $grup->count())
            ->keys()
            ->first();

        // PERBAIKAN (28 Juli 2026, revisi ke-3): total poin & jumlah kasus
        // pelanggaran siswa ini, supaya halaman screening menampilkan
        // riwayat konseling DAN pelanggaran sekaligus dalam satu tempat.
        $catatanPelanggaran = $sudahAdaJurnal->filter(fn ($r) => $r->jurnal->isPelanggaran());
        $totalPoinPelanggaran = $catatanPelanggaran->sum(fn ($r) => $r->jurnal->poin);
        $totalKasusPelanggaran = $catatanPelanggaran->count();

        return view('gurubk.rekap-laporan.riwayat-siswa', compact(
            'siswa',
            'riwayat',
            'totalSesi',
            'kategoriTerbanyak',
            'totalPoinPelanggaran',
            'totalKasusPelanggaran'
        ));
    }

    // PERBAIKAN (23 Juli 2026): tab "Jurnal Harian" sebelumnya cuma mockup,
    // form-nya tidak punya action/route sama sekali sehingga tidak bisa
    // disubmit. Method ini yang menyimpannya.
    public function storeJurnalHarian(Request $request)
    {
        $request->validate([
            'tanggal_waktu' => 'required|date',
            'jenis_kegiatan' => 'required|string',
            'sasaran' => 'nullable|string',
            'deskripsi_kegiatan' => 'required|string',
            'hambatan_catatan' => 'nullable|string',
        ]);

        JurnalHarian::create([
            'guru_bk_id' => Auth::id(),
            'tanggal_waktu' => $request->tanggal_waktu,
            'jenis_kegiatan' => $request->jenis_kegiatan,
            'sasaran' => $request->sasaran,
            'deskripsi_kegiatan' => $request->deskripsi_kegiatan,
            'hambatan_catatan' => $request->hambatan_catatan,
        ]);

        return back()->with('success', 'Jurnal harian berhasil disimpan.');
    }

    public function destroyJurnalHarian(string $id)
    {
        $jurnal = JurnalHarian::where('guru_bk_id', Auth::id())->findOrFail($id);
        $jurnal->delete();

        return back()->with('success', 'Jurnal harian berhasil dihapus.');
    }

    // PERBAIKAN (23 Juli 2026): tab "Bimbingan Kelompok" sebelumnya cuma
    // mockup, modalnya bukan <form> beneran (input tanpa name attribute,
    // tombol "Simpan Laporan" cuma type="button" tanpa action/route)
    // sehingga tidak mungkin bisa disubmit. Method ini yang menyimpannya.
    public function storeBimbinganKelompok(Request $request)
    {
        $request->validate([
            'tanggal_pelaksanaan' => 'required|date',
            'topik' => 'required|string|max:255',
            'daftar_anggota' => 'nullable|string',
            'dinamika_kelompok' => 'nullable|string',
            'kesimpulan_hasil' => 'nullable|string',
        ]);

        BimbinganKelompok::create([
            'guru_bk_id' => Auth::id(),
            'tanggal_pelaksanaan' => $request->tanggal_pelaksanaan,
            'topik' => $request->topik,
            'daftar_anggota' => $request->daftar_anggota,
            'dinamika_kelompok' => $request->dinamika_kelompok,
            'kesimpulan_hasil' => $request->kesimpulan_hasil,
        ]);

        return back()->with('success', 'Laporan Bimbingan Kelompok berhasil disimpan.');
    }

    public function destroyBimbinganKelompok(string $id)
    {
        $laporan = BimbinganKelompok::where('guru_bk_id', Auth::id())->findOrFail($id);
        $laporan->delete();

        return back()->with('success', 'Laporan Bimbingan Kelompok berhasil dihapus.');
    }

    // PERBAIKAN (23 Juli 2026): tab "Layanan Klasikal" sebelumnya cuma
    // mockup, modalnya bukan <form> beneran (input tanpa name attribute,
    // tombol "Simpan Layanan" cuma type="button" tanpa action/route)
    // sehingga tidak mungkin bisa disubmit. Method ini yang menyimpannya.
    public function storeLayananKlasikal(Request $request)
    {
        $request->validate([
            'tanggal_pelaksanaan' => 'required|date',
            'kelas_target' => 'required|string|max:255',
            'metode_penyampaian' => 'required|string|max:255',
            'materi_kompetensi' => 'nullable|string|max:255',
            'evaluasi_proses' => 'nullable|string',
        ]);

        LayananKlasikal::create([
            'guru_bk_id' => Auth::id(),
            'tanggal_pelaksanaan' => $request->tanggal_pelaksanaan,
            'kelas_target' => $request->kelas_target,
            'metode_penyampaian' => $request->metode_penyampaian,
            'materi_kompetensi' => $request->materi_kompetensi,
            'evaluasi_proses' => $request->evaluasi_proses,
        ]);

        return back()->with('success', 'Layanan Klasikal berhasil disimpan.');
    }

    public function destroyLayananKlasikal(string $id)
    {
        $layanan = LayananKlasikal::where('guru_bk_id', Auth::id())->findOrFail($id);
        $layanan->delete();

        return back()->with('success', 'Layanan Klasikal berhasil dihapus.');
    }
}