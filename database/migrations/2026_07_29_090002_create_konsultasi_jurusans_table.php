<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FITUR BARU (29 Juli 2026): Form Pemilihan Jurusan / Jalur Studi Lanjut.
 *
 * Diisi siswa setelah chatbot (Mode 1 - Alur Karier & Jurusan) memberi
 * rekomendasi awal, kalau siswa masih butuh konsultasi lebih lanjut
 * dengan Guru BK (soal pilihan universitas, beasiswa, atau jurusan
 * SMK/SMA). kampus_id opsional, tidak dipakai untuk redirect otomatis
 * seperti pilihan_alur_chatbot.tag_kampus (yang tetap teks bebas, tidak
 * diubah), murni referensi kalau siswa memang sudah condong ke 1 kampus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('konsultasi_jurusans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->enum('jenis_konsultasi', ['Universitas', 'Beasiswa', 'Jurusan SMK/SMA']);
            $table->foreignId('kampus_id')->nullable()->constrained('kampus')->nullOnDelete();
            $table->string('minat_jurusan')->nullable();
            $table->text('pertanyaan');
            $table->enum('status', ['Menunggu', 'Diproses', 'Selesai'])->default('Menunggu');
            $table->text('catatan_guru_bk')->nullable();
            $table->foreignId('ditangani_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditangani_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('konsultasi_jurusans');
    }
};
