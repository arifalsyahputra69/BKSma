<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// LETAKKAN DI: database/migrations/2026_07_22_000002_add_mata_pelajaran_to_users_table.php
//
// FITUR GABUNGAN TAB GURU (22 Juli 2026): tab "Wali Kelas" & "Guru Mapel" digabung
// jadi 1 tab "Guru" di Kelola Pengguna, jabatan dipilih langsung saat tambah/edit
// data (checkbox, bisa dua-duanya sekaligus). Karena mata pelajaran cuma relevan
// untuk yang berjabatan Guru Mapel, dan 1 guru diasumsikan cuma pegang 1 mapel,
// disimpan sebagai kolom teks biasa di tabel users (bukan tabel/relasi terpisah).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mata_pelajaran')->nullable()->after('nip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('mata_pelajaran');
        });
    }
};
