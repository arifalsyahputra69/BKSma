<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PERBAIKAN (26 Juli 2026):
     *
     * 1. Sebelumnya tabel program_bks tidak membedakan program kerja
     *    "Semester" dan "Tahunan" -- semua program dianggap satu jenis saja,
     *    padahal di lapangan Guru BK membuat program tahunan (payung besar,
     *    1 tahun ajaran) dan program semesteran (turunan per semester).
     *    Kolom `jenis_program` menambahkan pembeda ini.
     *
     * 2. Menambahkan kolom `rps_file` (path file RPL/RPS yang diunggah Guru
     *    BK) supaya program kerja bisa dilampiri dokumen Rencana
     *    Pelaksanaan Layanan/Semester sebagai bukti pendukung.
     */
    public function up(): void
    {
        Schema::table('program_bks', function (Blueprint $table) {
            $table->enum('jenis_program', ['Tahunan', 'Semester'])
                ->default('Semester')
                ->after('guru_bk_id');
            $table->string('rps_file')->nullable()->after('deskripsi');
        });
    }

    public function down(): void
    {
        Schema::table('program_bks', function (Blueprint $table) {
            $table->dropColumn(['jenis_program', 'rps_file']);
        });
    }
};
