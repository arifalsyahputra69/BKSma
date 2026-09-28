<?php

// LETAKKAN DI: database/migrations/2026_07_19_100000_create_kunjungan_rumahs_table.php
//
// FASE 8 (Backlog): Home Visit (dokumentasi & persetujuan kunjungan rumah).
// Sebelumnya modal "Form Kunjungan Rumah" di halaman Rekap Laporan Guru BK
// (resources/views/gurubk/rekap-laporan/index.blade.php, tab "Home Visit")
// sudah ada tampilannya tapi belum tersambung ke database sama sekali
// ("Database Kunjungan Rumah belum dihubungkan").
//
// Kerahasiaan: kolom hasil_observasi & kesepakatan_bersama HANYA ditampilkan
// di sisi Guru BK. Dashboard monitoring Kepsek (Kepsek\KunjunganRumahController)
// sengaja hanya menampilkan status, tanggal, dan siswa/kelas -- sama seperti
// prinsip yang sudah dipakai di jurnal_layanans (uraian_masalah tidak pernah
// ditarik ke sisi Kepsek).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kunjungan_rumahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('guru_bk_id')->constrained('users')->cascadeOnDelete();

            $table->date('tanggal_kunjungan');
            $table->string('nama_wali_ditemui')->nullable();
            $table->text('alasan_kunjungan');

            // Diisi Guru BK setelah kunjungan benar-benar berlangsung.
            $table->text('hasil_observasi')->nullable();
            $table->text('kesepakatan_bersama')->nullable();
            $table->string('dokumentasi_path')->nullable();

            // Direncanakan -> kunjungan baru dijadwalkan/dicatat sebagai rencana.
            // Terlaksana -> sudah benar-benar dikunjungi (hasil_observasi wajib diisi).
            // Dibatalkan -> batal dilaksanakan.
            $table->string('status')->default('Direncanakan');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kunjungan_rumahs');
    }
};
