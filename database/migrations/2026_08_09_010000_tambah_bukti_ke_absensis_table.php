<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BUKTI KETERANGAN ABSENSI (9 Agustus 2026)
 *
 * Selama ini status Izin/Sakit hanya disertai kolom `keterangan` berupa teks.
 * Padahal buktinya nyata: surat dokter difoto, atau pesan WhatsApp orang tua
 * di-screenshot. Tanpa tempat menyimpannya, bukti itu berhenti di ponsel guru
 * mapel yang menerimanya -- Guru BK dan wali kelas tidak pernah bisa
 * memeriksanya sendiri saat menindaklanjuti siswa yang sering absen.
 *
 * Kolomnya hanya menyimpan NAMA berkas, bukan seluruh jalurnya. Foldernya
 * ditentukan sekali di kode (bukti-absensi), mengikuti pola yang sudah dipakai
 * KampusController dan UserProfileController. Kalau jalur lengkap ikut
 * disimpan, memindahkan folder berarti harus memperbarui seluruh baris.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->string('bukti')->nullable()->after('keterangan');
        });
    }

    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropColumn('bukti');
        });
    }
};
