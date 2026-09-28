<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom-kolom yang dibutuhkan untuk jurnal konseling
 * individu yang dibuat manual oleh Guru BK (di luar antrean sesi),
 * sesuai GuruBK\JurnalLayananController@storeManual.
 *
 * Semua kolom dibuat nullable karena:
 * - Baris jurnal yang berasal dari antrian (antrian_id terisi) tidak
 *   mengisi kolom-kolom ini (siswa/guru BK/tanggal sudah bisa
 *   didapat lewat relasi antrian -> siswa & antrian -> sesi -> guru_bk).
 * - Baris jurnal manual (antrian_id NULL) yang justru mengisi
 *   kolom-kolom ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jurnal_layanans', function (Blueprint $table) {
            $table->foreignId('siswa_id')
                ->nullable()
                ->after('antrian_id')
                ->constrained('siswas')
                ->cascadeOnDelete();

            $table->foreignId('guru_bk_id')
                ->nullable()
                ->after('siswa_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->date('tanggal_konseling')->nullable()->after('guru_bk_id');
            $table->string('keperluan')->nullable()->after('tanggal_konseling');
        });
    }

    public function down(): void
    {
        Schema::table('jurnal_layanans', function (Blueprint $table) {
            $table->dropForeign(['siswa_id']);
            $table->dropForeign(['guru_bk_id']);
            $table->dropColumn(['siswa_id', 'guru_bk_id', 'tanggal_konseling', 'keperluan']);
        });
    }
};
