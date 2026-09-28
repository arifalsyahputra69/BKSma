<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FITUR BARU (dulu Batasan Penelitian, sekarang dikerjakan atas permintaan
 * penulis skripsi - 19 Juli 2026): Kelola Informasi Kampus untuk TU/Admin.
 *
 * Tabel ini terpisah dari pilihan_alur_chatbot.tag_kampus (yang tetap teks
 * bebas, tidak diubah, supaya tidak merusak fitur alur chatbot yang sudah
 * berjalan). Kelola Informasi Kampus di sini murni fitur data master yang
 * dikelola TU, ditampilkan sebagai referensi/katalog kampus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kampus', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kampus');
            $table->string('logo')->nullable(); // nama file, disimpan di storage/app/public/kampus
            $table->text('deskripsi')->nullable();
            $table->string('jurusan_unggulan')->nullable();
            $table->string('jalur_beasiswa')->nullable();
            $table->string('link_website')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kampus');
    }
};
