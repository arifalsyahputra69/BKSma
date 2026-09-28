<?php

// LETAKKAN DI: database/migrations/2026_07_29_090004_tambah_kolom_jawaban_ke_akpd_responses.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PERBAIKAN (29 Juli 2026): siswa sekarang wajib jawab Ya/Tidak untuk
 * SETIAP pertanyaan AKPD (tidak boleh dikosongkan begitu saja seperti
 * checkbox sebelumnya). Karena itu setiap pertanyaan yang tampil ke
 * siswa selalu punya baris jawaban -- baik dia jawab "ya" maupun
 * "tidak" -- supaya sistem juga bisa tahu siswa itu sudah pernah
 * mengisi AKPD di semester ini atau belum.
 *
 * Rekap Guru BK tetap menghitung yang jawaban='ya' saja, jadi hasil
 * rekap yang sudah ada tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akpd_responses', function (Blueprint $table) {
            $table->enum('jawaban', ['ya', 'tidak'])->default('ya')->after('akpd_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('akpd_responses', function (Blueprint $table) {
            $table->dropColumn('jawaban');
        });
    }
};
