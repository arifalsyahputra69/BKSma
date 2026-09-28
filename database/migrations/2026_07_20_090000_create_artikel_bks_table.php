<?php

// LETAKKAN DI: database/migrations/2026_07_20_090000_create_artikel_bks_table.php
//
// FITUR BARU (dulu Batasan Penelitian, sekarang dikerjakan atas permintaan
// penulis skripsi - 20 Juli 2026): Artikel Informasi BK.
// Guru BK bisa membuat/edit/hapus artikel + kategori, siswa bisa melihat
// semua artikel yang sudah dipublikasikan (lihat juga
// Siswa\ArtikelController & routes/web.php grup siswa.artikel.*).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artikel_bks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_bk_id')->constrained('users')->cascadeOnDelete();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->string('kategori')->default('Umum'); // Karier, Belajar, Sosial, Pribadi, Umum
            $table->string('gambar_sampul')->nullable(); // nama file, disimpan di storage/app/public/artikel-bk
            $table->text('ringkasan')->nullable();
            $table->longText('konten');
            $table->boolean('status_publish')->default(true); // false = draft, belum tampil ke siswa
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artikel_bks');
    }
};
