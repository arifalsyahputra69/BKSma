<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DAFTAR TUNGGU KONSELING (6 Agustus 2026)
 *
 * Latar belakang: antrean konseling (tabel antrian_konselings) WAJIB
 * menempel pada satu sesi lewat kolom sesi_konseling_id. Akibatnya siswa
 * tidak bisa mendaftar sama sekali selama Guru BK belum membuka sesi --
 * padahal justru di luar jam sekolah itulah siswa sering butuh, misalnya
 * setelah Chatbot BK tidak sanggup menjawab dan menyarankan janji temu.
 *
 * Tabel ini menampung "niat mendaftar" siswa saat belum ada sesi apa pun.
 * Begitu Guru BK membuka sesi baru, seluruh permintaan yang masih aktif
 * langsung diubah menjadi antrean bernomor secara otomatis.
 *
 * Kenapa tabel terpisah, bukan menjadikan sesi_konseling_id nullable:
 * antrian_konselings sudah dipakai di banyak tempat (penomoran antrean,
 * pemanggilan, jurnal layanan, monitoring Kepsek) dan semuanya menganggap
 * relasi ke sesi selalu ada. Membuatnya boleh kosong berarti setiap query
 * itu harus ikut diperiksa ulang. Tabel sendiri jauh lebih aman.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_konselings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('siswa_id');
            $table->unsignedBigInteger('guru_bk_id');

            $table->text('keperluan');

            // Menunggu    : belum ada sesi, masih mengantre untuk dialihkan
            // Dialihkan   : sudah berubah jadi antrean (lihat kolom antrian_id)
            // Dibatalkan  : dibatalkan sendiri oleh siswa
            // Kedaluwarsa : lewat masa berlaku, Guru BK tak kunjung buka sesi
            $table->string('status', 20)->default('Menunggu');

            // Diisi saat permintaan berhasil diubah jadi antrean, supaya
            // riwayatnya bisa ditelusuri dari kedua arah.
            $table->unsignedBigInteger('antrian_id')->nullable();

            $table->timestamps();

            $table->foreign('siswa_id')
                ->references('id')->on('siswas')
                ->onDelete('cascade');

            $table->foreign('guru_bk_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

            $table->foreign('antrian_id')
                ->references('id')->on('antrian_konselings')
                ->onDelete('set null');

            // Dipakai setiap kali Guru BK membuka sesi: cari semua permintaan
            // miliknya yang masih berstatus Menunggu.
            $table->index(['guru_bk_id', 'status']);

            // Dipakai di halaman siswa untuk menampilkan permintaannya sendiri.
            $table->index(['siswa_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_konselings');
    }
};
