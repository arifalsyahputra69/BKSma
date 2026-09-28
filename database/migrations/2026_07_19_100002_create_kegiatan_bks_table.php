<?php

// LETAKKAN DI: database/migrations/2026_07_19_100002_create_kegiatan_bks_table.php
//
// FASE 8 (Backlog): Kalender kegiatan BK. Sebelumnya belum ada tabel
// event/agenda -- Guru BK tidak punya tempat mencatat rencana kegiatan BK
// (sosialisasi jurusan, penyuluhan, rapat koordinasi, dll) dan Kepsek tidak
// bisa memantau jadwal kegiatan BK ke depan.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatan_bks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_bk_id')->constrained('users')->cascadeOnDelete();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('kategori')->default('Lainnya'); // Sosialisasi, Penyuluhan, Rapat/Koordinasi, Bimbingan Klasikal, Lainnya
            $table->string('sasaran')->nullable(); // mis. "Kelas X", "Seluruh Siswa", "Internal BK"
            $table->dateTime('tanggal_mulai');
            $table->dateTime('tanggal_selesai')->nullable();
            $table->string('lokasi')->nullable();
            $table->string('status')->default('Direncanakan'); // Direncanakan, Terlaksana, Dibatalkan
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan_bks');
    }
};
