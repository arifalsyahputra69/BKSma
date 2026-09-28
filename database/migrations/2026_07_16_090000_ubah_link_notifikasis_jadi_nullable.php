<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PERBAIKAN BUG:
 *
 * Kolom `link` di tabel `notifikasis` dibuat sebagai string wajib
 * (NOT NULL) sejak migration awal (2026_07_01_141438). Namun,
 * App\Jobs\AutoAlphaAbsensiJob membuat notifikasi "Alpha" dengan
 * 'link' => null untuk setiap siswa yang tidak absen.
 *
 * Karena koneksi database diset strict mode (lihat config/database.php),
 * MySQL akan menolak insert tersebut (SQLSTATE 23000: kolom 'link'
 * tidak boleh NULL). Job berjalan di dalam queue, jadi kegagalan ini
 * tidak terlihat langsung oleh pengguna -- job berhenti di tengah
 * loop foreach siswa, sehingga:
 *  - Siswa yang belum diproses pada urutan berikutnya di kelas yang
 *    sama TIDAK ikut ditandai Alpha.
 *  - Sesi absensi (SesiAbsensi) tidak pernah ter-update ke status
 *    'Selesai' karena baris kode itu ada setelah notifikasi dibuat.
 *
 * Migration ini mengubah kolom `link` menjadi nullable agar notifikasi
 * tanpa link tujuan (seperti notifikasi Alpha) bisa tersimpan dengan
 * aman, sehingga job auto-Alpha berjalan sampai selesai untuk seluruh
 * siswa di kelas tersebut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifikasis', function (Blueprint $table) {
            $table->string('link')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('notifikasis', function (Blueprint $table) {
            $table->string('link')->nullable(false)->change();
        });
    }
};
