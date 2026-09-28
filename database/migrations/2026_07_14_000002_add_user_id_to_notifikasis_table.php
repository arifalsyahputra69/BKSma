<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Kolom user_id dibutuhkan agar notifikasi bisa ditarget ke satu user
     * tertentu (contoh: siswa yang jadwal konselingnya dibatalkan Guru BK),
     * bukan cuma notifikasi broadcast seperti yang dipakai TU saat ini.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('notifikasis', 'user_id')) {
            Schema::table('notifikasis', function (Blueprint $table) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('notifikasis', 'user_id')) {
            Schema::table('notifikasis', function (Blueprint $table) {
                try {
                    $table->dropForeign(['user_id']);
                } catch (\Throwable $e) {
                }
                $table->dropColumn('user_id');
            });
        }
    }
};