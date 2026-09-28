<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FITUR BARU (30 Juli 2026): menghubungkan tombol pilihan di Alur Chatbot
 * (pilihan_alur_chatbot) ke data master Kampus (tabel `kampus`, dikelola TU
 * di menu "Kelola Informasi Kampus").
 *
 * Sebelumnya, kolom `tag_kampus` di tabel ini cuma teks bebas yang diketik
 * manual Guru BK (contoh: "UNAND") dan cuma ditampilkan sebagai badge teks
 * polos ke siswa -- sama sekali tidak terhubung ke logo/deskripsi/jurusan
 * unggulan/jalur beasiswa yang sudah diinput TU di data master Kampus.
 *
 * Kolom `kampus_id` di sini OPSIONAL (nullable) dan tidak menggantikan
 * `tag_kampus` -- keduanya tetap ada. Kalau Guru BK memilih Kampus dari
 * dropdown data master (kampus_id terisi), chatbot akan menampilkan kartu
 * rekomendasi lengkap. Kalau tidak dipilih (kampus_id kosong), tampilan
 * lama (badge teks dari tag_kampus) tetap jalan seperti biasa -- supaya
 * alur chatbot yang sudah dibuat Guru BK sebelumnya tidak rusak.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pilihan_alur_chatbot', function (Blueprint $table) {
            $table->foreignId('kampus_id')->nullable()->after('tag_kampus')
                ->constrained('kampus')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pilihan_alur_chatbot', function (Blueprint $table) {
            $table->dropForeign(['kampus_id']);
            $table->dropColumn('kampus_id');
        });
    }
};
