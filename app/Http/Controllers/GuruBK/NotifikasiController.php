<?php
namespace App\Http\Controllers\GuruBK;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use Illuminate\Support\Facades\Auth;

class NotifikasiController extends Controller
{
    /**
     * Tandai satu notifikasi sebagai sudah dibaca, lalu arahkan
     * Guru BK ke halaman terkait (kolom "link" pada notifikasi).
     */
    public function baca(string $id)
    {
        $notifikasi = Notifikasi::where('user_id', Auth::id())
            ->findOrFail($id);

        $notifikasi->update([
            'is_read' => true,
        ]);

        // PERBAIKAN KEAMANAN (AUDIT): lihat Notifikasi::tujuanAman().
        return redirect($notifikasi->tujuanAman() ?: route('gurubk.dashboard'));
    }

    /**
     * Tandai semua notifikasi milik Guru BK yang sedang login
     * sebagai sudah dibaca.
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