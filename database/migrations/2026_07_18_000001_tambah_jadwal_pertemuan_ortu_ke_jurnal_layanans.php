<?php

// LETAKKAN DI: database/migrations/2026_07_18_000001_tambah_jadwal_pertemuan_ortu_ke_jurnal_layanans.php
//
// Perbaikan bug: notifikasi WA "Panggil Orang Tua" / "Pelanggaran Berat" tidak
// pernah menyertakan jadwal pertemuan, sehingga orang tua bingung kapan harus
// datang ke sekolah. Kolom ini menyimpan tanggal & jam pertemuan yang diisi
// Guru BK saat menulis jurnal, lalu dikirim ke isi pesan WA (lihat
// FonnteService::notifikasiOrtuSetelahKonseling & JurnalLayananController).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jurnal_layanans', function (Blueprint $table) {
            $table->dateTime('jadwal_pertemuan_ortu')->nullable()->after('panggil_ortu');
        });
    }

    public function down(): void
    {
        Schema::table('jurnal_layanans', function (Blueprint $table) {
            $table->dropColumn('jadwal_pertemuan_ortu');
        });
    }
};
