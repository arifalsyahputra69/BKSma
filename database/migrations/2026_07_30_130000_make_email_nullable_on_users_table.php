<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// FITUR LOGIN PAKAI USERNAME (30 Juli 2026): login sekarang pakai NISN/NIP
// (bukan email) untuk semua role KECUALI TU/Admin yang tetap pakai email.
// Karena TU sekarang membuat akun guru/siswa/dst TANPA mengisi email (email
// diisi belakangan oleh siswa/guru sendiri lewat halaman profil), kolom
// email di tabel users harus boleh NULL. Pakai raw SQL (bukan ->change())
// supaya tidak butuh package doctrine/dbal.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'email')) {
            DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        // Isi email kosong dengan placeholder unik dulu supaya tidak bentrok
        // dengan constraint NOT NULL saat rollback.
        DB::statement("UPDATE users SET email = CONCAT('user-', id, '@placeholder.local') WHERE email IS NULL");
        DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL');
    }
};
