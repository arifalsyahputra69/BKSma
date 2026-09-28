<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FITUR BARU (29 Juli 2026): jawaban siswa atas AKPD.
 *
 * Satu baris = 1 siswa "mencentang" (mengalami) 1 item pertanyaan pada
 * 1 semester tertentu. Kalau siswa tidak mencentang suatu item, tidak ada
 * baris yang dibuat (bukan disimpan sebagai false) -- lebih hemat & lebih
 * gampang dihitung persentasenya di rekap (tinggal COUNT baris per item).
 *
 * unique(siswa_id, akpd_item_id, semester_id) supaya submit ulang di
 * semester yang sama tidak menghasilkan data ganda (siswa cuma perlu
 * kirim sekali per semester, lihat AkpdController@store di sisi Siswa).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akpd_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('akpd_item_id')->constrained('akpd_items')->cascadeOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->nullOnDelete();
            $table->timestamps();

            $table->unique(['siswa_id', 'akpd_item_id', 'semester_id'], 'akpd_responses_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akpd_responses');
    }
};
