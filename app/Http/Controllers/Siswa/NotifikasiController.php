<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use Illuminate\Support\Facades\Auth;

class NotifikasiController extends Controller
{
    /**
     * Tandai satu notifikasi sebagai sudah dibaca, lalu arahkan
     * siswa ke halaman terkait (kolom "link" pada notifikasi).
     */
    public function baca(string $id)
    {
        $notifikasi = Notifikasi::where('user_id', Auth::id())
            ->findOrFail($id);

        $notifikasi->update([
            'is_read' => true,
        ]);

        // PERBAIKAN KEAMANAN (AUDIT): lihat Notifikasi::tujuanAman() -- kolom
        // `link` tidak lagi dipercaya mentah-mentah supaya tidak bisa menjadi
        // open redirect ke situs luar.
        return redirect($notifikasi->tujuanAman() ?: route('siswa.dashboard'));
    }

    /**
     * Tandai semua notifikasi milik siswa yang sedang login sebagai
     * sudah dibaca.
     */
    public function bacaSemua()
    {
        Notifikasi::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
            ]);

        return back()->with('success', 'Semua notifikasi telah ditandai dibaca.');
    }
}
