<?php

// FITUR BARU (12 Agustus 2026): "Absensi QR" untuk Guru BK.
//
// Sebelumnya hanya Guru Mapel yang bisa membuka sesi absensi QR (lihat
// App\Http\Controllers\GuruMapel\AbsensiController). Guru BK yang masuk ke
// kelas untuk Layanan Klasikal tidak punya cara mengabsen siswa sama
// sekali. Controller ini adalah salinan pola yang sama persis --
// SesiAbsensi & Absensi memang tidak terikat ke role tertentu (kolom
// guru_id hanya foreign key biasa ke tabel users, mapel hanya string
// bebas) -- jadi tidak perlu perubahan skema apa pun, cukup controller +
// view baru dengan namespace 'gurubk.absensi.*' dan konteks kolom "mapel"
// diberi label "Kegiatan" supaya sesuai dengan cara kerja Guru BK (bukan
// mengajar mata pelajaran, tapi kegiatan seperti "Layanan Klasikal" atau
// "Bimbingan Kelompok").
//
// Siswa tetap scan lewat route & controller yang sama
// (Siswa\AbsensiScanController, siswa.absensi.scan) -- itu sudah generik
// dan tidak perlu diubah sama sekali.

namespace App\Http\Controllers\GuruBK;

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
    const MENIT_EXPIRED = 10;

    public function __construct(protected FonnteService $fonnteService)
    {
    }

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
     * Daftar riwayat sesi absensi milik Guru BK ini + form generate QR baru
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

        return view('gurubk.absensi.index', compact('kelas', 'sesis'));
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

        // Sama seperti alur Guru Mapel: siswa yang sudah dinyatakan Izin/Sakit
        // pada jam sebelumnya hari ini langsung terisi di sesi ini.
        $terisiOtomatis = KeteranganHarianAbsensi::isiSesiBaru($sesi);

        $pesan = 'QR absensi berhasil dibuat. Tampilkan ke siswa untuk discan.';

        if ($terisiOtomatis > 0) {
            $pesan .= ' '.$terisiOtomatis.' siswa otomatis tercatat Izin/Sakit '
                .'mengikuti keterangan dari jam pelajaran sebelumnya hari ini.';
        }

        return redirect()
            ->route('gurubk.absensi.show', $sesi->id)
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

        return view('gurubk.absensi.show', compact('sesi', 'roster', 'scanUrl'));
    }

    /**
     * Endpoint JSON dipoll oleh JS di halaman show() untuk refresh status live
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
     * Guru BK tutup sesi lebih awal (sebelum 10 menit habis)
     */
    public function tutup($id)
    {
        $sesi = SesiAbsensi::where('guru_id', Auth::id())->findOrFail($id);

        $sesi->update(['waktu_expired' => now()]);

        return back()->with('success', 'Sesi absensi ditutup. Siswa yang belum scan akan otomatis Alpha dalam beberapa menit.');
    }

    /**
     * Guru BK input manual kehadiran untuk siswa tertentu di sesi ini.
     *
     * PERBAIKAN (13 Agustus 2026): sebelumnya hanya bisa Izin/Sakit --
     * padahal masukan dari Guru BK: kalau siswa tidak punya kuota internet,
     * dia sama sekali tidak bisa scan QR (bukan Izin, bukan Sakit, bukan
     * Alpha karena sebenarnya hadir di kelas), dan sebelum ini gurunya
     * tidak punya cara menandai siswa itu Hadir selain lewat scan. Sekarang
     * "Hadir" ditambahkan sebagai pilihan status manual juga.
     */
    public function manualStore(Request $request, $id)
    {
        $sesi = SesiAbsensi::where('guru_id', Auth::id())->findOrFail($id);

        $request->validate([
            'siswa_id' => 'required|exists:siswas,id',
            'status' => 'required|in:Hadir,Izin,Sakit',
            'keterangan' => 'nullable|string|max:500',
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
        ], [
            'bukti.mimes' => 'Bukti hanya boleh berupa gambar (JPG/PNG/WEBP) atau PDF.',
            'bukti.max'   => 'Ukuran bukti maksimal 4 MB.',
        ]);

        // Sama seperti Guru Mapel: pastikan siswa memang anggota kelas pada
        // sesi ini, supaya tidak bisa mencatatkan Izin/Sakit untuk siswa
        // kelas lain lewat siswa_id yang diubah manual di form.
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
        $absen->sumber = Absensi::SUMBER_MANUAL;

        if ($request->hasFile('bukti')) {
            $buktiLama = $absen->bukti;

            $file = $request->file('bukti');
            $namaFile = 'bukti-'.$sesi->id.'-'.$request->siswa_id.'-'.time().'.'.$file->getClientOriginalExtension();
            $file->storeAs('bukti-absensi', $namaFile, 'public');

            $absen->bukti = $namaFile;
            $absen->save();

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
     * Buang berkas bukti dari storage kalau sudah tidak dirujuk baris mana pun.
     * Lihat catatan lengkap di GuruMapel\AbsensiController::hapusBukti().
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
        $periode = $request->periode ?? 'mingguan';
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

        return view('gurubk.absensi.rekap', compact(
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

        $siswaKelas = $sesi->kelas?->siswas;

        if ($siswaKelas === null) {
            Log::warning('Sesi absensi (Guru BK) menunjuk kelas yang sudah tidak ada.', [
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
                'keterangan' => $absen?->keterangan,
                'bukti_url' => $absen?->urlBukti(),
                'bukti_gambar' => (bool) $absen?->buktiBerupaGambar(),
                'otomatis' => (bool) $absen?->berasalDariSalinanOtomatis(),
            ];
        })->values();
    }
}
