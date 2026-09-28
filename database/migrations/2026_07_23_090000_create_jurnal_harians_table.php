<?php

// LETAKKAN DI: database/migrations/2026_07_23_090000_create_jurnal_harians_table.php
//
// PERBAIKAN (23 Juli 2026): tab "Jurnal Harian" di halaman Rekap Laporan
// sebelumnya cuma tampilan/mockup -- form-nya bukan <form> beneran (tidak
// ada name attribute, tombol submit type="button" tanpa action/route),
// jadi tidak mungkin bisa disubmit. Migration ini menyediakan tabel
// penyimpanannya supaya fitur ini benar-benar berfungsi.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_harians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_bk_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('tanggal_waktu');
            $table->string('jenis_kegiatan'); // Layanan Kelas (Klasikal), Konseling/Bimbingan, Rapat/Koordinasi, Administrasi BK
            $table->string('sasaran')->nullable(); // mis. nama kelas/siswa/pihak yang jadi sasaran kegiatan
            $table->text('deskripsi_kegiatan');
            $table->text('hambatan_catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_harians');
    }
};
