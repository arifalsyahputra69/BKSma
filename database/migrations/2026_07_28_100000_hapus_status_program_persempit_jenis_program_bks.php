<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PERBAIKAN (28 Juli 2026, revisi ke-2) berdasarkan masukan Kepsek:
     *
     * 1. Opsi jenis program "Mingguan" dihapus -- terlalu banyak pilihan
     *    untuk kebutuhan di lapangan, cukup sampai Bulanan. Jenis final:
     *    Tahunan, Semesteran, Bulanan.
     *
     * 2. Kolom `status_program` (Draft/Aktif) dihapus. Status aktif/tidak
     *    aktifnya sebuah program sekarang murni ditentukan oleh keputusan
     *    Kepsek (kolom `status`: Diajukan/Disetujui/Ditolak) -- bukan lagi
     *    diinput manual oleh Guru BK saat mengajukan.
     */
    public function up(): void
    {
        // Amankan dulu data lama yang mungkin masih berjenis "Mingguan"
        // sebelum enum dipersempit, supaya migrasi tidak gagal.
        DB::table('program_bks')->where('jenis_program', 'Mingguan')->update(['jenis_program' => 'Bulanan']);

        DB::statement("ALTER TABLE program_bks MODIFY jenis_program ENUM('Tahunan', 'Semesteran', 'Bulanan') NOT NULL DEFAULT 'Bulanan'");

        Schema::table('program_bks', function (Blueprint $table) {
            $table->dropColumn('status_program');
        });
    }

    public function down(): void
    {
        Schema::table('program_bks', function (Blueprint $table) {
            $table->enum('status_program', ['Draft', 'Aktif'])->default('Draft')->after('status');
        });

        DB::statement("ALTER TABLE program_bks MODIFY jenis_program ENUM('Tahunan', 'Semesteran', 'Bulanan', 'Mingguan') NOT NULL DEFAULT 'Bulanan'");
    }
};
