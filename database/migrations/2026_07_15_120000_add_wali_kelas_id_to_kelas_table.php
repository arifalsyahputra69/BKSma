<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambahkan kolom wali_kelas_id ke tabel kelas.
     * Diperlukan supaya Dashboard Wali Kelas (Fase 6) bisa tahu
     * kelas mana saja yang harus dipantau oleh user Wali Kelas yang login.
     * Nullable karena kelas lama belum tentu langsung punya Wali Kelas.
     */
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->foreignId('wali_kelas_id')
                ->nullable()
                ->after('id_guru_bk')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('wali_kelas_id');
        });
    }
};
