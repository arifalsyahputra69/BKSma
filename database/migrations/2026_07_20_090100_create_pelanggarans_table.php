<?php

// LETAKKAN DI: database/migrations/2026_07_20_090100_create_pelanggarans_table.php
//
// FITUR BARU (dulu Batasan Penelitian, sekarang dikerjakan atas permintaan
// penulis skripsi - 20 Juli 2026): Data Pelanggaran Siswa sebagai entitas
// terpisah (riwayat, kategori, poin), bukan lagi cuma kolom
// tingkat_pelanggaran di jurnal_layanans. Kolom itu di jurnal_layanans
// TIDAK dihapus supaya tidak mengganggu fitur notifikasi WA ortu yang
// sudah berjalan (lihat JurnalLayananController::notifikasiOrtu) --
// tabel ini murni tambahan untuk pencatatan pelanggaran yang berdiri
// sendiri (di luar konteks satu sesi konseling).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelanggarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('guru_bk_id')->constrained('users')->cascadeOnDelete();
            $table->string('kategori'); // Ringan, Sedang, Berat
            $table->unsignedInteger('poin')->default(0);
            $table->text('deskripsi');
            $table->date('tanggal_kejadian');
            $table->text('tindak_lanjut')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggarans');
    }
};
