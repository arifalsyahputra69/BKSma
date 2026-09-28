<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Area Monitoring #1: Ketercapaian & Realisasi Program BK.
     *
     * Sebelumnya `status` hanya menandai hasil peninjauan Kepsek
     * (Diajukan/Disetujui/Ditolak). Kolom itu TIDAK menunjukkan apakah
     * program yang sudah disetujui benar-benar dijalankan sesuai
     * rentang tanggalnya atau justru mangkrak.
     *
     * `status_pelaksanaan` menyimpan progres pelaksanaan yang dilaporkan
     * Guru BK sendiri, khusus untuk program yang statusnya "Disetujui".
     * Deteksi "mangkrak" (terlambat) dihitung otomatis di model
     * (lihat ProgramBk::isMangkrak()) dengan membandingkan
     * tanggal_selesai terhadap status_pelaksanaan, bukan lewat kolom baru,
     * supaya tidak ada dua sumber kebenaran yang bisa saling kontradiksi.
     */
    public function up(): void
    {
        Schema::table('program_bks', function (Blueprint $table) {
            $table->enum('status_pelaksanaan', ['Belum Mulai', 'Berjalan', 'Selesai'])
                ->nullable()
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('program_bks', function (Blueprint $table) {
            $table->dropColumn('status_pelaksanaan');
        });
    }
};
