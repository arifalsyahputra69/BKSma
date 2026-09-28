<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FITUR BARU (29 Juli 2026): Form Penelusuran Alumni.
 *
 * Sekolah belum punya konsep akun "Alumni" (setelah lulus, akun Siswa
 * tidak otomatis berubah jadi alumni), jadi form ini bersifat PUBLIK
 * (tanpa login) -- linknya dibagikan Guru BK ke alumni lewat WA/medsos.
 * nisn disimpan cuma sebagai catatan pencocokan manual (opsional, boleh
 * kosong), TIDAK dibuat foreign key ke tabel siswas supaya alumni yang
 * datanya sudah lama/terhapus tetap bisa mengisi form ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumnis', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('nisn')->nullable();
            $table->string('tahun_lulus', 4);
            $table->string('kelas_terakhir')->nullable();
            $table->enum('status_saat_ini', ['Kuliah', 'Bekerja', 'Belum Kuliah/Bekerja', 'Lainnya']);
            $table->string('nama_institusi')->nullable(); // nama kampus atau nama perusahaan
            $table->string('jurusan_posisi')->nullable(); // jurusan kuliah atau posisi kerja
            $table->string('no_hp')->nullable();
            $table->string('email')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumnis');
    }
};
