<?php

// LETAKKAN DI: database/migrations/2026_08_12_090000_tambah_dokumentasi_ke_klasikal_dan_kelompok.php
//
// PERBAIKAN (12 Agustus 2026): tab "Layanan Klasikal" dan "Bimbingan
// Kelompok" di halaman Rekap Laporan Guru BK belum punya kolom untuk
// menyimpan foto/dokumentasi kegiatan, padahal "Home Visit" (Kunjungan
// Rumah) sudah punya (kolom dokumentasi_path di tabel kunjungan_rumahs).
// Migration ini menambahkan kolom yang sama ke 2 tabel ini supaya Guru BK
// juga bisa mengunggah foto bukti pelaksanaan layanan klasikal & bimbingan
// kelompok, persis seperti pola yang sudah ada di Home Visit.
//
// Kerahasiaan: dokumentasi_path TIDAK ditarik ke sisi Kepsek sama sekali --
// Kepsek hanya melihat aktivitas generik "Guru BK sedang melakukan layanan
// konseling klasikal/kelompok" (lihat Kepsek\DashboardController::buildData()
// dan resources/views/kepsek/dashboard.blade.php), tanpa topik, tanpa daftar
// anggota, dan tanpa foto -- sama seperti prinsip kerahasiaan yang sudah
// dipakai di jurnal_layanans & kunjungan_rumahs.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('layanan_klasikals', function (Blueprint $table) {
            $table->string('dokumentasi_path')->nullable()->after('evaluasi_proses');
        });

        Schema::table('bimbingan_kelompoks', function (Blueprint $table) {
            $table->string('dokumentasi_path')->nullable()->after('kesimpulan_hasil');
        });
    }

    public function down(): void
    {
        Schema::table('layanan_klasikals', function (Blueprint $table) {
            $table->dropColumn('dokumentasi_path');
        });

        Schema::table('bimbingan_kelompoks', function (Blueprint $table) {
            $table->dropColumn('dokumentasi_path');
        });
    }
};
