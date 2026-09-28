<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\SesiAbsensi;
use App\Services\FonnteService;
use Illuminate\Support\Facades\Auth;

class AbsensiScanController extends Controller
{
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
     * Riwayat absensi milik siswa yang sedang login.
     */
    public function riwayat()
    {
        $siswa = Auth::user()->siswa;

        $riwayat = $siswa
            ? Absensi::with('sesiAbsensi')
                ->where('siswa_id', $siswa->id)
                ->latest('created_at')
                ->paginate(15)
            : collect();

        return view('siswa.absensi.riwayat', compact('riwayat'));
    }

    /**
     * Siswa scan QR (dari kamera HP) -> membuka URL ini.
     * Karena route ini sudah dilindungi middleware auth+role:Siswa,
     * kalau siswa belum login, mereka akan diarahkan ke halaman login dulu,
     * lalu kembali ke URL scan ini (Laravel default 'intended' redirect).
     */
    public function scan($token)
    {
        $sesi = SesiAbsensi::with('kelas')->where('token', $token)->first();

        if (! $sesi) {
            return view('siswa.absensi.scan', [
                'status' => 'invalid',
                'pesan' => 'QR tidak dikenali atau sudah tidak berlaku.',
            ]);
        }

        $siswa = Auth::user()->siswa;

        if (! $siswa) {
            return view('siswa.absensi.scan', [
                'status' => 'invalid',
                'pesan' => 'Akun kamu tidak terhubung dengan data siswa.',
            ]);
        }

        if ($siswa->kelas_id != $sesi->kelas_id) {
            return view('siswa.absensi.scan', [
                'status' => 'invalid',
                'pesan' => 'QR ini bukan untuk kelasmu. Pastikan kamu scan QR yang ditampilkan di kelasmu sendiri.',
            ]);
        }

        if ($sesi->isExpired()) {
            return view('siswa.absensi.scan', [
                'status' => 'expired',
                'pesan' => 'Waktu absensi untuk sesi ini sudah habis.',
                'sesi' => $sesi,
            ]);
        }

        $sudahAda = Absensi::where('sesi_absensi_id', $sesi->id)
            ->where('siswa_id', $siswa->id)
            ->first();

        // PERBAIKAN (9 Agustus 2026): baris hasil salinan otomatis TIDAK
        // menghalangi scan.
        //
        // Sejak keterangan Izin/Sakit berlaku sehari penuh, siswa bisa saja
        // sudah ditandai Sakit di jam ini padahal dia sebenarnya datang --
        // misalnya berobat pagi lalu masuk sekolah setelahnya. Kalau scan-nya
        // ditolak, siswa yang berdiri di depan kelas sambil memegang HP-nya
        // tetap tercatat tidak hadir, dan dia tidak punya cara memperbaikinya
        // sendiri. Kehadiran yang benar-benar terjadi lebih kuat daripada
        // perkiraan sistem, jadi scan-nya menang.
        //
        // Yang tetap ditolak adalah baris dari sumber lain: kalau gurunya
        // sendiri yang mengetik Izin/Sakit untuk jam ini, itu keputusan yang
        // tidak boleh dibatalkan siswa hanya dengan memindai QR.
        if ($sudahAda && ! $sudahAda->berasalDariSalinanOtomatis()) {
            return view('siswa.absensi.scan', [
                'status' => 'sudah',
                'pesan' => 'Kamu sudah tercatat ' . $sudahAda->status . ' pada sesi ini.',
                'sesi' => $sesi,
                'absen' => $sudahAda,
            ]);
        }

        $absen = $sudahAda ?: new Absensi([
            'sesi_absensi_id' => $sesi->id,
            'siswa_id' => $siswa->id,
        ]);

        $absen->status = 'Hadir';
        $absen->waktu_scan = now();
        $absen->sumber = Absensi::SUMBER_SCAN;

        // Keterangan & bukti sakit dilepas untuk jam ini karena siswanya
        // terbukti hadir. Berkasnya sendiri TIDAK dihapus dari storage -- surat
        // dokter yang sama masih menempel di jam-jam lain yang memang tidak dia
        // hadiri.
        $absen->keterangan = null;
        $absen->bukti = null;

        $absen->save();

        $absen->setRelation('siswa', $siswa);
        $this->notifikasiOrtuAbsensi($absen, $sesi);

        return view('siswa.absensi.scan', [
            'status' => 'sukses',
            'pesan' => 'Absensi berhasil dicatat. Kamu Hadir.',
            'sesi' => $sesi,
            'absen' => $absen,
        ]);
    }
}
