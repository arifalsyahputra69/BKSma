<?php

// LETAKKAN DI: app/Http/Controllers/GuruMapel/NotifikasiController.php
//
// Perbaikan bug: dropdown notifikasi di layout Guru Mapel (resources/views/
// layouts/gurumapel.blade.php) sebelumnya membuka langsung "$notif->link"
// tanpa pernah menandai notifikasi tsb sebagai is_read = true. Akibatnya
// notifikasi yang sudah diklik/dibuka tetap muncul terus di dropdown karena
// query notifikasi_user (lihat AppServiceProvider) hanya menampilkan yang
// is_read = false. Controller ini meniru pola yang sudah benar di
// App\Http\Controllers\Siswa\NotifikasiController.

namespace App\Http\Controllers\GuruMapel;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use Illuminate\Support\Facades\Auth;

class NotifikasiController extends Controller
{
    /**
     * Tandai satu notifikasi sebagai sudah dibaca, lalu arahkan
     * Guru Mapel ke halaman terkait (kolom "link" pada notifikasi).
     */
    public function baca(string $id)
    {
        $notifikasi = Notifikasi::where('user_id', Auth::id())
            ->findOrFail($id);

        $notifikasi->update([
            'is_read' => true,
        ]);

        // PERBAIKAN KEAMANAN (AUDIT): lihat Notifikasi::tujuanAman().
        return redirect($notifikasi->tujuanAman() ?: route('gurumapel.dashboard'));
    }

    /**
     * Tandai semua notifikasi milik Guru Mapel yang sedang login
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