<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel jadwal_konselings (beserta kolom nama_manual yang sempat
 * ditambahkan manual di luar sesi ini) sudah tidak dipakai lagi oleh
 * controller/view manapun sejak sistem pindah ke alur antrean.
 *
 * Migration ini WAJIB dijalankan setelah
 * 2026_07_14_160000_hubungkan_jurnal_layanans_ke_antrian, karena
 * migration itu yang melepas foreign key jurnal_layanans.jadwal_id
 * yang tadinya menunjuk ke tabel ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('jadwal_konselings');
    }

    public function down(): void
    {
        // Rekonstruksi skema asli (create_jadwal_konselings_table +
        // ubah_status_jadwal_menjadi_string + add_nama_manual), supaya
        // rollback tidak kehilangan struktur kolom yang pernah ada.
        Schema::create('jadwal_konselings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_bk_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('siswa_id')->nullable()->constrained('siswas')->onDelete('cascade');
            $table->string('nama_manual')->nullable()->after('siswa_id');
            $table->date('tanggal');
            $table->time('waktu');
            $table->string('tempat')->default('Ruang BK');
            $table->string('keperluan')->nullable();
            $table->string('status')->default('Tersedia');
            $table->text('catatan_hasil')->nullable();
            $table->timestamps();
        });
    }
};
