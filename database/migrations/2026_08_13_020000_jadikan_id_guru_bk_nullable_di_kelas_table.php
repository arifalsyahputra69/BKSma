<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * REVISI (13 Agustus 2026): TU minta saat menambah kelas baru, Guru BK
 * TIDAK wajib langsung dipilih -- boleh diisi belakangan.
 *
 * Kolom id_guru_bk di tabel kelas sejak awal dibuat WAJIB (NOT NULL, lihat
 * migration create_kelas_table) dan terikat foreign key dengan
 * cascadeOnDelete(). Supaya field ini bisa dikosongkan:
 *   1. Foreign key lama (yang cascadeOnDelete) dilepas dulu -- ALTER COLUMN
 *      jadi nullable tidak bisa dilakukan MySQL selama masih terikat FK.
 *   2. Kolom diubah jadi nullable.
 *   3. Foreign key dipasang ulang, tapi diganti ke nullOnDelete() (dulu
 *      cascadeOnDelete artinya kalau akun Guru BK dihapus, SELURUH kelas
 *      beserta data siswanya ikut terhapus -- masuk akal selama kolom ini
 *      wajib diisi, tapi begitu boleh kosong, perilaku yang lebih aman
 *      adalah id_guru_bk-nya saja yang dikosongkan, bukan kelasnya ikut
 *      lenyap. Ini juga menyamakan perilakunya dengan wali_kelas_id yang
 *      dari awal sudah pakai nullOnDelete()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropForeign(['id_guru_bk']);
        });

        Schema::table('kelas', function (Blueprint $table) {
            $table->foreignId('id_guru_bk')->nullable()->change();
        });

        Schema::table('kelas', function (Blueprint $table) {
            $table->foreign('id_guru_bk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropForeign(['id_guru_bk']);
        });

        Schema::table('kelas', function (Blueprint $table) {
            $table->foreignId('id_guru_bk')->nullable(false)->change();
        });

        Schema::table('kelas', function (Blueprint $table) {
            $table->foreign('id_guru_bk')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
