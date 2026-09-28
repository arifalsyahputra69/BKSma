<?php

namespace App\Http\Controllers\Kepsek;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use App\Models\ProgramBk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProgramBkController extends Controller
{
    /**
     * Daftar pengajuan Program BK: yang masih "Diajukan" ditampilkan
     * paling atas untuk ditinjau, program yang sudah disetujui dan
     * sedang berjalan (realisasi) di tengah, riwayat keputusan di bawah.
     */
    public function index()
    {
        $menunggu = ProgramBk::with('guruBk')
            ->where('status', 'Diajukan')
            ->latest()
            ->get();

        // Area Monitoring #1: program yang SUDAH disetujui, dipantau
        // realisasinya (bukan cuma statusnya berhenti di "Disetujui").
        $programAktif = ProgramBk::with('guruBk')
            ->where('status', 'Disetujui')
            ->orderBy('tanggal_selesai')
            ->get();

        $totalMangkrak = $programAktif->filter(fn (ProgramBk $p) => $p->isMangkrak())->count();

        $riwayat = ProgramBk::with(['guruBk', 'ditinjauOleh'])
            ->whereIn('status', ['Disetujui', 'Ditolak'])
            ->latest('ditinjau_at')
            ->take(20)
            ->get();

        return view('kepsek.program-bk.index', compact(
            'menunggu',
            'programAktif',
            'totalMangkrak',
            'riwayat'
        ));
    }

    /**
     * Setujui pengajuan program
     */
    public function approve(Request $request, $id)
    {
        $program = ProgramBk::findOrFail($id);

        $program->update([
            'status' => 'Disetujui',
            // Realisasi mulai dipantau sejak program disetujui.
            'status_pelaksanaan' => 'Belum Mulai',
            'catatan_kepsek' => $request->catatan_kepsek,
            'ditinjau_oleh' => Auth::id(),
            'ditinjau_at' => now(),
        ]);

        $this->beriTahuGuruBk($program, 'disetujui');

        return back()->with('success', 'Program BK disetujui.');
    }

    /**
     * Tolak pengajuan program (wajib isi catatan alasan)
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'catatan_kepsek' => 'required|string|max:1000',
        ]);

        $program = ProgramBk::findOrFail($id);

        $program->update([
            'status' => 'Ditolak',
            'catatan_kepsek' => $request->catatan_kepsek,
            'ditinjau_oleh' => Auth::id(),
            'ditinjau_at' => now(),
        ]);

        $this->beriTahuGuruBk($program, 'ditolak');

        return back()->with('success', 'Program BK ditolak, catatan sudah dikirim ke Guru BK.');
    }

    private function beriTahuGuruBk(ProgramBk $program, string $status): void
    {
        Notifikasi::create([
            'user_id' => $program->guru_bk_id,
            'judul' => 'Program BK ' . ucfirst($status),
            'pesan' => "Program \"{$program->judul}\" yang Anda ajukan telah {$status} oleh Kepala Sekolah." .
                ($program->catatan_kepsek ? "\n\nCatatan: {$program->catatan_kepsek}" : ''),
            'link' => route('gurubk.program-bk.index'),
            'is_read' => false,
        ]);
    }
}