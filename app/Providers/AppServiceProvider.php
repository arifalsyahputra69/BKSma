<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\Notifikasi;
use App\Models\Semester;
use App\Observers\NotifikasiObserver;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // FASE 7: setiap baris Notifikasi baru otomatis didorong juga sebagai
        // push notification (FCM) ke device user terkait, tanpa perlu ubah
        // controller yang sudah memanggil Notifikasi::create([...]).
        Notifikasi::observe(NotifikasiObserver::class);

        // Bagikan notifikasi ke semua view yang menggunakan layout TU/Admin.
        // PENTING: yang ditampilkan hanya notifikasi untuk TU/Admin, yaitu
        // notifikasi broadcast (user_id NULL, mis. "Perbaikan Data Kelas")
        // atau notifikasi yang memang ditujukan ke user TU yang sedang login.
        // Notifikasi milik siswa/guru BK (user_id spesifik user lain) TIDAK ikut tampil.
        View::composer('layouts.tu', function ($view) {
            $notifikasi = Auth::check()
                ? Notifikasi::where('is_read', false)
                    ->where(function ($q) {
                        $q->whereNull('user_id')
                          ->orWhere('user_id', Auth::id());
                    })
                    ->latest()
                    ->take(5)
                    ->get()
                : collect();

            $view->with('notifikasi_admin', $notifikasi);
        });

        // Bagikan notifikasi milik user yang sedang login (user_id = Auth::id())
        // ke layout role selain TU/Admin. PENTING: berbeda dari komposer TU di
        // atas, di sini TIDAK ikut menampilkan notifikasi broadcast (user_id
        // NULL) karena broadcast tsb memang khusus ditujukan untuk TU/Admin.
        View::composer(
            ['layouts.guru', 'layouts.kepsek', 'layouts.walikelas', 'layouts.gurumapel', 'layouts.siswa'],
            function ($view) {
                $notifikasi = Auth::check()
                    ? Notifikasi::where('user_id', Auth::id())
                        ->where('is_read', false)
                        ->latest()
                        ->take(5)
                        ->get()
                    : collect();

                $view->with('notifikasi_user', $notifikasi);
            }
        );

        // ============================================================
        // SEMESTER BERJALAN (6 Agustus 2026)
        // ============================================================
        // Ditempelkan ke partial-nya sendiri, bukan ke tiap layout, supaya
        // query hanya jalan saat badge-nya benar-benar dirender -- sekali per
        // halaman. Kalau di-share global (View::share), query ikut jalan pada
        // permintaan yang tidak merender view sama sekali.
        //
        // Schema::hasTable dipakai sebagai jaring pengaman: saat migrate
        // dijalankan dari nol, provider ini sudah aktif sebelum tabel semesters
        // ada. Tanpa penjagaan itu, `php artisan migrate` pada database kosong
        // bisa gagal hanya gara-gara badge ini.
        View::composer('partials.badge-semester', function ($view) {
            $semesterAktif = null;

            try {
                if (Schema::hasTable('semesters')) {
                    $semesterAktif = Semester::where('status_aktif', true)->first();
                }
            } catch (\Throwable $e) {
                // Database bermasalah bukan alasan seluruh halaman ikut gagal
                // tampil. Badge cukup menampilkan "belum diatur".
                $semesterAktif = null;
            }

            $view->with('semesterAktif', $semesterAktif);
        });
    }
}