<?php

// LETAKKAN DI: database/migrations/2026_07_23_120001_create_layanan_klasikals_table.php
//
// PERBAIKAN (23 Juli 2026): tab "Layanan Klasikal" di halaman Rekap
// Laporan sebelumnya cuma tampilan/mockup -- modalnya bukan <form> beneran
// (tidak ada tag <form>, input tidak punya name attribute, tombol
// "Simpan Layanan" cuma type="button" tanpa action/route), jadi tidak
// mungkin bisa disubmit -- kasus yang sama seperti Bimbingan Kelompok &
// Jurnal Harian sebelum diperbaiki. Migration ini menyediakan tabel
// penyimpanannya supaya fitur ini benar-benar berfungsi.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('layanan_klasikals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_bk_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('tanggal_pelaksanaan');
            $table->string('kelas_target');
            $table->string('metode_penyampaian');
            $table->string('materi_kompetensi')->nullable();
            $table->text('evaluasi_proses')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('layanan_klasikals');
    }
};
