<?php

namespace App\Http\Controllers\GuruMapel;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Semester;
use App\Models\SesiAbsensi;
use App\Models\Siswa;
use App\Services\FonnteService;
use App\Services\KeteranganHarianAbsensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AbsensiController extends Controller
{
    // Berapa lama QR berlaku setelah digenerate
    const MENIT_EXPIRED = 5;

    // CATATAN (AUDIT): konstanta MENIT_GRACE_PERIOD dihapus. Dulu dipakai
    // untuk menghitung ->delay() job queue; sekarang penandaan Alpha
    // dikerjakan command terjadwal `absensi:tutup-kedaluwarsa` yang jalan
    // tiap menit, jadi jeda tambahan itu tidak relevan lagi.

    public function __construct(protected FonnteService $fonnteService)
    {
    }

    // Notifikasi WA ke orang tua untuk SETIAP status absensi (Hadir/Izin/
    // Sakit/Alpha) per sesi/mata pelajaran, supaya orang tua bisa memantau
    // kehadiran anaknya di sekolah secara real-time.
    private function notifikasiOrtuAbsensi(Absensi $absen, SesiAbsensi $sesi): void
    {
        $siswa = $absen->siswa;

        if (! $siswa || blank($siswa->no_wa_ortu) || ! $siswa->is_wa_verified) {
            return;
        }

        $this->fonnteService->notifikasiOrtuAbsensi(
            $siswa->no_wa_ortu,
            optional($siswa->user)->name ?? 'Ananda',
            $siswa->nama_ortu ?: 'Bapak/Ibu',
            $absen->status,
            $sesi->mapel,
            $sesi->jam_ke,
            $sesi->tanggal->translatedFormat('d F Y'),
            $absen->keterangan
        );
    }

    /**
     * Daftar riwayat sesi absensi milik Guru Mapel ini + form generate QR baru
     */
    public function index()
    {
        $kelas = Kelas::orderBy('nama_kelas')->get();

        $sesis = SesiAbsensi::with('kelas')
            ->withCount([
                'absensis as hadir_count' => fn ($q) => $q->where('status', 'Hadir'),
                'absensis as izin_count' => fn ($q) => $q->where('status', 'Izin'),
                'absensis as sakit_count' => fn ($q) => $q->where('status', 'Sakit'),
                'absensis as alpha_count' => fn ($q) => $q->where('status', 'Alpha'),
            ])
            ->where('guru_id', Auth::id())
            ->latest('tanggal')
            ->latest('id')
            ->paginate(15);

        return view('gurumapel.absensi.index', compact('kelas', 'sesis'));
    }

    /**
     * Generate QR baru (buka sesi absensi)
     */
    public function store(Request $request)
    {
        $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'mapel' => 'required|string|max:100',
            'jam_ke' => 'required|string|max:50',
            'tanggal' => 'required|date',
        ]);

        $semesterAktif = Semester::where('status_aktif', true)->first();

        $sesi = SesiAbsensi::create([
            'guru_id' => Auth::id(),
            'kelas_id' => $request->kelas_id,
            'semester_id' => $semesterAktif?->id,
            'mapel' => $request->mapel,
            'jam_ke' => $request->jam_ke,
            'tanggal' => $request->tanggal,
            'token' => Str::random(40),
            'status' => 'Aktif',
            'waktu_expired' => now()->addMinutes(self::MENIT_EXPIRED),
        ]);

        // PERBAIKAN (AUDIT, 2 Agustus 2026): baris dispatch ke queue DIHAPUS.
        //
        // Dulu di sini ada:
        //   AutoAlphaAbsensiJob::dispatch($sesi->id)->delay(...)
        // yang hanya bekerja kalau ada `php artisan queue:work` berjalan
        // permanen. Di shared hosting (cPanel) itu tidak mungkin, sehingga di
        // server sekolah job-nya cuma menumpuk di tabel `jobs` dan tidak
        // pernah jalan -- siswa yang bolos tidak pernah tercatat Alpha, dan
        // orang tua tidak pernah dapat WA. Gagalnya diam-diam, tanpa error.
        //
        // Sekarang penandaan Alpha dikerjakan command terjadwal
        // `absensi:tutup-kedaluwarsa` yang dipanggil cron tiap menit. Sesi ini
        // otomatis terjaring begitu waktu_expired terlewat -- tidak perlu
        // didaftarkan ke mana pun dari sini.

        // FITUR BARU (9 Agustus 2026): siswa yang sudah dinyatakan Izin/Sakit
        // pada jam pelajaran sebelumnya hari ini langsung terisi di sesi ini,
        // lengkap dengan buktinya. Guru jam berikutnya tidak perlu mengetik
        // ulang keterangan yang sama, dan yang lebih penting: siswa itu tidak
        // lagi ditandai Alpha oleh cron hanya karena gurunya tidak tahu.
        $terisiOtomatis = KeteranganHarianAbsensi::isiSesiBaru($sesi);

        $pesan = 'QR absensi berhasil dibuat. Tampilkan ke siswa untuk discan.';

        if ($terisiOtomatis > 0) {
            $pesan .= ' '.$terisiOtomatis.' siswa otomatis tercatat Izin/Sakit '
                .'mengikuti keterangan dari jam pelajaran sebelumnya hari ini.';
        }

        return redirect()
            ->route('gurumapel.absensi.show', $sesi->id)
            ->with('success', $pesan);
    }

    /**
     * Tampilkan QR + status kehadiran live per siswa di kelas tsb
     */
    public function show($id)
    {
        $sesi = SesiAbsensi::with('kelas')
            ->where('guru_id', Auth::id())
            ->findOrFail($id);

        $roster = $this->buildRoster($sesi);

        $scanUrl = route('siswa.absensi.scan', $sesi->token);

        return view('gurumapel.absensi.show', compact('sesi', 'roster', 'scanUrl'));
    }

    /**
     * Endpoint JSON dipoll oleh JS di halaman show() untuk refresh status live
     * tanpa reload halaman (dan supaya QR tidak ikut ke-render ulang).
     */
    public function statusJson($id)
    {
        $sesi = SesiAbsensi::where('guru_id', Auth::id())->findOrFail($id);

        $roster = $this->buildRoster($sesi);

        return response()->json([
            'is_expired' => $sesi->isExpired(),
            'waktu_expired' => $sesi->waktu_expired->toIso8601String(),
            'roster' => $roster,
        ]);
    }

    /**
     * Guru Mapel tutup sesi lebih awal (sebelum 5 menit habis)
     */
    public function tutup($id)
    {
        $sesi = SesiAbsensi::where('guru_id', Auth::id())->findOrFail($id);

        // PERBAIKAN (AUDIT): dulu baris ini langsung menulis status 'Selesai'.
        // Sekarang yang dimajukan adalah waktu_expired-nya, dan status
        // dibiarkan 'Aktif' supaya sesi ini terjaring oleh command terjadwal
        // `absensi:tutup-kedaluwarsa` -- command itulah yang menandai Alpha
        // siswa yang belum absen, mengirim notifikasi, lalu menutup sesi
        // menjadi 'Selesai'.
        //
        // Bagi Guru Mapel & siswa tidak ada bedanya: SesiAbsensi::isExpired()
        // sudah menganggap sesi kedaluwarsa begitu waktu_expired terlewat,
        // jadi QR langsung mati saat tombol ini ditekan.
        $sesi->update(['waktu_expired' => now()]);

        return back()->with('success', 'Sesi absensi ditutup. Siswa yang belum scan akan otomatis Alpha dalam beberapa menit.');
    }

    /**
     * Guru Mapel input manual Izin / Sakit untuk siswa tertentu di sesi ini
     */
    public function manualStore(Request $request, $id)
    {
        $sesi = SesiAbsensi::where('guru_id', Auth::id())->findOrFail($id);

        $request->validate([
            'siswa_id' => 'required|exists:siswas,id',
            'status' => 'required|in:Izin,Sakit',
            'keterangan' => 'nullable|string|max:500',
            // FITUR BARU (9 Agustus 2026): lampiran bukti keterangan.
            // Dibatasi jenis berkas yang benar-benar dipakai di lapangan --
            // foto surat dokter, tangkapan layar WhatsApp orang tua, atau surat
            // dalam bentuk PDF. Batas 4 MB cukup untuk foto kamera ponsel
            // sekaligus menjaga kuota penyimpanan hosting.
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
        ], [
            'bukti.mimes' => 'Bukti hanya boleh berupa gambar (JPG/PNG/WEBP) atau PDF.',
            'bukti.max'   => 'Ukuran bukti maksimal 4 MB.',
        ]);

        // PERBAIKAN KEAMANAN (AUDIT): validasi di atas cuma memastikan siswa_id
        // ADA di tabel siswas -- bukan bahwa siswa itu anggota kelas pada sesi
        // ini. Akibatnya seorang Guru Mapel bisa (dengan mengubah siswa_id di
        // form) mencatatkan Izin/Sakit untuk siswa kelas lain yang tidak dia
        // ajar, dan memicu notifikasi WA ke orang tua siswa tsb. Sekarang
        // keanggotaan kelas dicek dulu.
        $siswaValid = Siswa::where('id', $request->siswa_id)
            ->where('kelas_id', $sesi->kelas_id)
            ->exists();

        if (! $siswaValid) {
            return back()->with('error', 'Siswa tersebut bukan anggota kelas pada sesi absensi ini.');
        }

        $absen = Absensi::firstOrNew([
            'sesi_absensi_id' => $sesi->id,
            'siswa_id' => $request->siswa_id,
        ]);

        $absen->status = $request->status;
        $absen->keterangan = $request->keterangan;

        // Ditulis guru, jadi ditandai manual -- termasuk kalau baris ini
        // sebelumnya hasil salinan otomatis. Sejak detik ini keterangannya
        // adalah keputusan manusia dan tidak boleh ditimpa sistem lagi.
        $absen->sumber = Absensi::SUMBER_MANUAL;

        if ($request->hasFile('bukti')) {
            // Berkas lama dibuang lebih dulu. Tanpa ini, setiap kali guru
            // memperbaiki entri yang sama akan tertinggal berkas yatim di
            // storage -- tidak terpakai, tidak terlacak, tapi tetap memakan
            // kuota hosting sekolah.
            $buktiLama = $absen->bukti;

            $file = $request->file('bukti');
            $namaFile = 'bukti-'.$sesi->id.'-'.$request->siswa_id.'-'.time().'.'.$file->getClientOriginalExtension();
            $file->storeAs('bukti-absensi', $namaFile, 'public');

            $absen->bukti = $namaFile;
            $absen->save();

            // Penghapusan dilakukan SETELAH baris disimpan. Kalau dihapus lebih
            // dulu, baris ini masih terhitung sebagai pemakai berkas lama dan
            // pemeriksaan "masih dipakai baris lain" jadi selalu benar.
            $this->hapusBukti($buktiLama);
        } else {
            $absen->save();
        }

        $absen->loadMissing('siswa');
        $this->notifikasiOrtuAbsensi($absen, $sesi);

        // Sebarkan ke jam pelajaran lain pada hari yang sama.
        $tersebar = KeteranganHarianAbsensi::sebarkan($absen, $sesi);

        $pesan = 'Status kehadiran siswa berhasil diperbarui.';

        if ($tersebar > 0) {
            $pesan .= ' Keterangan ini otomatis berlaku juga untuk '.$tersebar
                .' jam pelajaran lain hari ini, jadi guru berikutnya tidak perlu mencatatnya lagi.';
        }

        return back()->with('success', $pesan);
    }

    /**
     * Buang berkas bukti dari storage. Diam saja kalau memang tidak ada --
     * pemanggilnya tidak perlu memeriksa dulu.
     *
     * PENTING: satu berkas bukti dipakai bersama oleh beberapa baris absensi.
     * Foto surat dokter yang sama menempel di seluruh jam pelajaran hari itu
     * (lihat KeteranganHarianAbsensi). Kalau berkasnya dihapus begitu saja saat
     * salah satu baris melepaskannya, tautan "Lihat bukti" di jam-jam lain
     * berubah jadi halaman 404 -- dan rusaknya tidak kelihatan sampai ada yang
     * mengkliknya berminggu-minggu kemudian. Karena itu berkas hanya dibuang
     * kalau benar-benar sudah tidak dirujuk baris mana pun.
     */
    private function hapusBukti(?string $namaFile): void
    {
        if (! $namaFile) {
            return;
        }

        $masihDipakai = Absensi::where('bukti', $namaFile)->exists();

        if ($masihDipakai) {
            return;
        }

        $jalur = 'bukti-absensi/'.$namaFile;

        if (Storage::disk('public')->exists($jalur)) {
            Storage::disk('public')->delete($jalur);
        }
    }

    /**
     * Rekap absensi harian / mingguan / bulanan, difilter per kelas & semester
     */
    public function rekap(Request $request)
    {
        $kelasList = Kelas::orderBy('nama_kelas')->get();
        $semesterList = Semester::orderByDesc('status_aktif')->latest()->get();

        $kelasId = $request->kelas_id;
        $periode = $request->periode ?? 'mingguan'; // harian | mingguan | bulanan
        $semesterId = $request->semester_id ?? Semester::where('status_aktif', true)->value('id');

        $query = SesiAbsensi::where('guru_id', Auth::id());

        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }
        if ($kelasId) {
            $query->where('kelas_id', $kelasId);
        }

        match ($periode) {
            'harian' => $query->whereDate('tanggal', $request->tanggal ?? today()),
            'bulanan' => $query->whereMonth('tanggal', $request->bulan ?? now()->month)
                ->whereYear('tanggal', $request->tahun ?? now()->year),
            default => $query->whereBetween('tanggal', [
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString(),
            ]),
        };

        $sesiIds = $query->pluck('id');

        $rekap = Absensi::with(['siswa.user', 'siswa.kelas'])
            ->whereIn('sesi_absensi_id', $sesiIds)
            ->get()
            ->groupBy('siswa_id')
            ->map(function ($rows) {
                $siswa = $rows->first()->siswa;
                return (object) [
                    'siswa' => $siswa,
                    'hadir' => $rows->where('status', 'Hadir')->count(),
                    'izin' => $rows->where('status', 'Izin')->count(),
                    'sakit' => $rows->where('status', 'Sakit')->count(),
                    'alpha' => $rows->where('status', 'Alpha')->count(),
                ];
            })
            ->sortBy(fn ($r) => $r->siswa?->user?->name)
            ->values();

        return view('gurumapel.absensi.rekap', compact(
            'kelasList',
            'semesterList',
            'rekap',
            'kelasId',
            'periode',
            'semesterId'
        ));
    }

    private function buildRoster(SesiAbsensi $sesi)
    {
        $sesi->loadMissing('kelas.siswas.user');

        // PERBAIKAN BUG (5 Agustus 2026): dulu langsung dipanggil
        // $sesi->kelas->siswas. Kalau kelas yang dirujuk sesi ini sudah dihapus
        // (baris sesi absensinya tertinggal sebagai data yatim), $sesi->kelas
        // bernilai null dan baris itu menjatuhkan permintaan dengan error 500.
        //
        // Dampaknya lebih luas daripada satu halaman: buildRoster() juga dipakai
        // statusJson(), yang dipanggil browser tiap 5 detik. Jadi satu sesi
        // rusak bukan cuma membuat halaman detail gagal dibuka, tapi juga
        // membanjiri storage/logs dengan error yang sama sepanjang halaman itu
        // terbuka.
        //
        // Sekarang keadaan itu ditangani: rosternya kosong, halaman tetap bisa
        // dibuka, dan penyebabnya dicatat sekali dengan menyebut id sesi supaya
        // datanya bisa dibereskan.
        $siswaKelas = $sesi->kelas?->siswas;

        if ($siswaKelas === null) {
            Log::warning('Sesi absensi menunjuk kelas yang sudah tidak ada.', [
                'sesi_id' => $sesi->id,
                'kelas_id' => $sesi->kelas_id,
            ]);

            return collect();
        }

        $tercatat = Absensi::where('sesi_absensi_id', $sesi->id)->get()->keyBy('siswa_id');

        return $siswaKelas->map(function ($siswa) use ($tercatat) {
            $absen = $tercatat->get($siswa->id);
            return [
                'siswa_id' => $siswa->id,
                'nama' => $siswa->user?->name ?? '(tanpa nama)',
                'nisn' => $siswa->nisn,
                'status' => $absen->status ?? 'Belum Absen',
                'waktu_scan' => $absen?->waktu_scan?->format('H:i:s'),
                // Ikut dikirim supaya baris yang disegarkan tiap 5 detik lewat
                // statusJson() tetap menampilkan keterangan & buktinya -- kalau
                // hanya ditulis di blade, semuanya lenyap pada refresh pertama.
                'keterangan' => $absen?->keterangan,
                'bukti_url' => $absen?->urlBukti(),
                'bukti_gambar' => (bool) $absen?->buktiBerupaGambar(),
                // Dipakai untuk memberi label "otomatis" pada baris yang
                // disalin sistem dari jam pelajaran lain. Tanpa label itu guru
                // melihat status yang tidak pernah dia tulis dan mengira
                // sistemnya salah -- atau lebih buruk, mengira rekannya
                // mengabsenkan kelasnya.
                'otomatis' => (bool) $absen?->berasalDariSalinanOtomatis(),
            ];
        })->values();
    }
}
