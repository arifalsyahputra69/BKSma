<?php

// LETAKKAN DI: database/migrations/2026_07_23_120000_create_bimbingan_kelompoks_table.php
//
// PERBAIKAN (23 Juli 2026): tab "Bimbingan Kelompok" di halaman Rekap
// Laporan sebelumnya cuma tampilan/mockup -- modalnya bukan <form> beneran
// (tidak ada tag <form>, input tidak punya name attribute, tombol
// "Simpan Laporan" cuma type="button" tanpa action/route), jadi tidak
// mungkin bisa disubmit -- persis kasus yang sama seperti Jurnal Harian
// sebelum diperbaiki. Migration ini menyediakan tabel penyimpanannya
// supaya fitur ini benar-benar berfungsi.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bimbingan_kelompoks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_bk_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('tanggal_pelaksanaan');
            $table->string('topik');
            $table->text('daftar_anggota')->nullable();
            $table->text('dinamika_kelompok')->nullable();
            $table->text('kesimpulan_hasil')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bimbingan_kelompoks');
    }
};
