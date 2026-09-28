<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    protected $fillable = [
        'user_id',
        'judul', 
        'pesan', 
        'link', 
        'is_read'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * PERBAIKAN KEAMANAN (AUDIT): semua NotifikasiController melakukan
     * redirect($notifikasi->link) apa adanya. Kolom `link` itu data biasa di
     * database, bukan konstanta kode -- kalau suatu saat ada baris notifikasi
     * yang linknya berisi alamat luar (bug pengisian, seeder, impor data, atau
     * akun staf yang disalahgunakan), sistem akan melempar user ke situs luar
     * dari domain sekolah yang mereka percayai (open redirect -- pola klasik
     * untuk phishing halaman login palsu).
     *
     * Helper ini memastikan tujuan redirect selalu berada di dalam aplikasi:
     * path relatif diloloskan, URL absolut hanya diloloskan kalau host-nya
     * sama dengan APP_URL. Selain itu dianggap tidak valid dan pemanggil akan
     * memakai halaman default (dashboard) sebagai gantinya.
     */
    public function tujuanAman(): ?string
    {
        $link = trim((string) $this->link);

        if ($link === '') {
            return null;
        }

        // "//evil.com" & "\\evil.com" ikut ditolak: browser memperlakukannya
        // sebagai URL absolut walau tidak menyebut skema.
        if (str_starts_with($link, '/') && ! str_starts_with($link, '//') && ! str_starts_with($link, '/\\')) {
            return $link;
        }

        $hostTujuan = parse_url($link, PHP_URL_HOST);

        if (! $hostTujuan) {
            return null;
        }

        // Host yang dianggap "milik sendiri": APP_URL, DAN host request yang
        // sedang berjalan. Yang kedua penting supaya link notifikasi lama tetap
        // berfungsi di lingkungan dev (mis. Laragon menyajikan aplikasi di
        // bk-kartika-chatbot.test sementara APP_URL masih http://localhost) --
        // tanpa itu semua notifikasi akan terlempar ke dashboard.
        $hostDipercaya = array_filter([
            parse_url((string) config('app.url'), PHP_URL_HOST),
            request()?->getHost(),
        ]);

        foreach ($hostDipercaya as $host) {
            if (strcasecmp($hostTujuan, $host) === 0) {
                return $link;
            }
        }

        return null;
    }
}