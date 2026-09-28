<?php

namespace App\Observers;

// LETAKKAN DI: app/Observers/NotifikasiObserver.php
//
// Tujuan: setiap kali ADA SAJA baris baru masuk ke tabel notifikasis --
// dari mana pun asalnya (ProgramBkController Kepsek/GuruBK, SesiKonselingController,
// KonfirmasiKelasController, AutoAlphaAbsensiJob, dst) -- otomatis dikirim juga
// sebagai push notification (FCM) ke device user yang bersangkutan.
//
// Keuntungan pendekatan Observer ini: TIDAK PERLU mengubah satu pun controller
// yang sudah ada. Semua tempat yang sudah memanggil Notifikasi::create([...])
// otomatis "naik level" jadi push notification real-time begitu Observer ini
// didaftarkan di AppServiceProvider.

use App\Models\Notifikasi;
use App\Services\FcmService;

class NotifikasiObserver
{
    public function created(Notifikasi $notifikasi): void
    {
        if (blank($notifikasi->user_id)) {
            return;
        }

        $user = $notifikasi->user ?? \App\Models\User::find($notifikasi->user_id);

        if (! $user || blank($user->fcm_token)) {
            return;
        }

        app(FcmService::class)->kirimKeToken(
            $user->fcm_token,
            $notifikasi->judul,
            $notifikasi->pesan,
            $notifikasi->link ?: null
        );
    }
}
