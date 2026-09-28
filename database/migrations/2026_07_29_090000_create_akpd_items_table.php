<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FITUR BARU (29 Juli 2026): AKPD (Angket Kebutuhan Peserta Didik).
 *
 * Tabel ini adalah daftar pertanyaan/masalah (bank soal) yang dikelola
 * Guru BK. Sifatnya sama seperti ArtikelBk::index() -- daftar pertanyaan
 * ini BERSAMA (dipakai bareng semua Guru BK, siapapun boleh tambah/edit/
 * hapus), karena AKPD adalah instrumen satu sekolah, bukan per-guru.
 *
 * Kategori memakai 4 bidang bimbingan baku BK (Pribadi, Sosial, Belajar,
 * Karir) supaya hasilnya bisa langsung dipetakan ke jenis Program BK
 * (lihat kolom jenis_program di tabel program_bks).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akpd_items', function (Blueprint $table) {
            $table->id();
            $table->enum('kategori', ['Pribadi', 'Sosial', 'Belajar', 'Karir']);
            $table->text('pertanyaan');
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akpd_items');
    }
};
