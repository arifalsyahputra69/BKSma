<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ASAL-USUL CATATAN ABSENSI (9 Agustus 2026)
 *
 * Kolom ini lahir dari fitur "keterangan berlaku sehari penuh": sekali siswa
 * dinyatakan Sakit/Izin di satu jam pelajaran, sistem ikut menandainya di
 * jam-jam lain pada hari yang sama supaya guru berikutnya tidak perlu
 * memasukkannya ulang.
 *
 * Begitu sistem boleh menulis sendiri, catatan absensi tidak lagi seragam:
 * ada yang berasal dari scan QR siswa, dari guru yang mengetiknya, dari cron
 * penanda Alpha, dan sekarang dari penyalinan otomatis. Tanpa penanda ini
 * keempatnya tidak bisa dibedakan -- dan itu berbahaya, karena aturan
 * penimpaannya berbeda:
 *
 *   - baris 'otomatis' BOLEH ditimpa (siswa ternyata datang lalu scan QR,
 *     atau gurunya mengoreksi keterangannya),
 *   - baris 'manual', 'scan', dan 'sistem' TIDAK BOLEH ditimpa diam-diam --
 *     itu keputusan manusia atau bukti kehadiran nyata.
 *
 * Dibiarkan nullable, bukan diberi nilai default, supaya baris lama yang
 * dibuat sebelum fitur ini tetap dikenali sebagai "asalnya tidak diketahui"
 * dan ikut terlindungi dari penimpaan otomatis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->string('sumber', 20)->nullable()->after('bukti');
        });
    }

    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropColumn('sumber');
        });
    }
};
