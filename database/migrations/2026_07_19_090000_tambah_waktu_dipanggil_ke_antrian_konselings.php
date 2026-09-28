<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Area Monitoring #6: Kepatuhan waktu layanan.
     *
     * `updated_at` TIDAK bisa dipakai untuk mengukur "kapan status berubah jadi
     * Dipanggil", karena begitu antrean itu lanjut ke status berikutnya (Selesai),
     * `updated_at` ikut berubah lagi mengikuti waktu transisi TERAKHIR -- bukan
     * lagi waktu saat dipanggil. Makanya dibutuhkan kolom timestamp terpisah yang
     * diisi HANYA pada momen status berubah jadi "Dipanggil"
     * (lihat GuruBK\SesiKonselingController::panggilBerikutnya()).
     */
    public function up(): void
    {
        Schema::table('antrian_konselings', function (Blueprint $table) {
            $table->timestamp('waktu_dipanggil')->nullable()->after('waktu_booking');
        });

        // Backfill terbatas: HANYA untuk antrean yang saat ini masih berstatus
        // "Dipanggil" (belum lanjut ke status berikutnya), karena untuk kasus itu
        // updated_at MASIH akurat mencerminkan saat dipanggil. Untuk antrean yang
        // statusnya sudah lanjut (mis. Selesai), waktu panggil aslinya sudah tidak
        // bisa direkonstruksi dari data lama -- sengaja dibiarkan NULL, bukan
        // ditebak, supaya metrik waktu tunggu tidak memuat data yang salah.
        DB::table('antrian_konselings')
            ->where('status', 'Dipanggil')
            ->whereNull('waktu_dipanggil')
            ->update(['waktu_dipanggil' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('antrian_konselings', function (Blueprint $table) {
            $table->dropColumn('waktu_dipanggil');
        });
    }
};
