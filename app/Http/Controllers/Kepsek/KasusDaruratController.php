<?php

// LETAKKAN DI: app/Http/Controllers/Kepsek/KasusDaruratController.php
//
// FASE 8: Notifikasi kasus darurat + approval DO/skorsing. Kepsek meninjau
// kasus yang diusulkan Guru BK untuk tindakan disipliner berat (DO/Skorsing),
// lalu menyetujui atau menolaknya.
//
// Kerahasiaan: HANYA kategori_masalah, tingkat_pelanggaran, dan jenis
// tindakan yang diusulkan yang ditampilkan -- uraian_masalah &
// pendekatan_teknik TIDAK PERNAH ditarik ke controller/view ini, konsisten
// dengan prinsip yang sudah dipakai di DashboardController Kepsek.

namespace App\Http\Controllers\Kepsek;

use App\Http\Controllers\Controller;
use App\Models\JurnalLayanan;
use App\Models\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KasusDaruratController extends Controller
{
    public function index()
    {
        $menunggu = JurnalLayanan::with(['siswa.user', 'siswa.kelas', 'guruBk'])
            ->select([
                'id', 'siswa_id', 'guru_bk_id', 'tanggal_konseling',
                'kategori_masalah', 'tingkat_pelanggaran',
                'jenis_tindakan_diusulkan', 'status_persetujuan_kepsek',
                'catatan_kepsek_persetujuan', 'disetujui_oleh', 'disetujui_at',
                'butuh_persetujuan_kepsek',
            ])
            ->where('butuh_persetujuan_kepsek', true)
            ->where('status_persetujuan_kepsek', 'Menunggu')
            ->latest('tanggal_konseling')
            ->get();

        $riwayat = JurnalLayanan::with(['siswa.user', 'siswa.kelas', 'guruBk', 'disetujuiOleh'])
            ->select([
                'id', 'siswa_id', 'guru_bk_id', 'tanggal_konseling',
                'kategori_masalah', 'tingkat_pelanggaran',
                'jenis_tindakan_diusulkan', 'status_persetujuan_kepsek',
                'catatan_kepsek_persetujuan', 'disetujui_oleh', 'disetujui_at',
                'butuh_persetujuan_kepsek',
            ])
            ->where('butuh_persetujuan_kepsek', true)
            ->whereIn('status_persetujuan_kepsek', ['Disetujui', 'Ditolak'])
            ->latest('disetujui_at')
            ->take(20)
            ->get();

        return view('kepsek.kasus-darurat.index', compact('menunggu', 'riwayat'));
    }

    public function approve(Request $request, string $id)
    {
        $jurnal = JurnalLayanan::where('butuh_persetujuan_kepsek', true)->findOrFail($id);

        $jurnal->update([
            'status_persetujuan_kepsek' => 'Disetujui',
            'catatan_kepsek_persetujuan' => $request->catatan_kepsek_persetujuan,
            'disetujui_oleh' => Auth::id(),
            'disetujui_at' => now(),
        ]);

        $this->beriTahuGuruBk($jurnal, 'disetujui');

        return back()->with('success', 'Usulan tindakan disipliner disetujui.');
    }

    public function reject(Request $request, string $id)
    {
        $request->validate([
            'catatan_kepsek_persetujuan' => 'required|string|max:1000',
        ]);

        $jurnal = JurnalLayanan::where('butuh_persetujuan_kepsek', true)->findOrFail($id);

        $jurnal->update([
            'status_persetujuan_kepsek' => 'Ditolak',
            'catatan_kepsek_persetujuan' => $request->catatan_kepsek_persetujuan,
            'disetujui_oleh' => Auth::id(),
            'disetujui_at' => now(),
        ]);

        $this->beriTahuGuruBk($jurnal, 'ditolak');

        return back()->with('success', 'Usulan tindakan disipliner ditolak, catatan sudah dikirim ke Guru BK.');
    }

    private function beriTahuGuruBk(JurnalLayanan $jurnal, string $status): void
    {
        Notifikasi::create([
            'user_id' => $jurnal->guru_bk_id,
            'judul' => 'Usulan Tindakan Disipliner ' . ucfirst($status),
            'pesan' => "Usulan tindakan \"{$jurnal->jenis_tindakan_diusulkan}\" yang Anda ajukan telah {$status} oleh Kepala Sekolah." .
                ($jurnal->catatan_kepsek_persetujuan ? "\n\nCatatan: {$jurnal->catatan_kepsek_persetujuan}" : ''),
            'link' => route('gurubk.rekap.index'),
            'is_read' => false,
        ]);
    }
}
