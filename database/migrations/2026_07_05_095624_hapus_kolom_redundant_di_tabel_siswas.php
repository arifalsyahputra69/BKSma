<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            // Menghapus kolom yang tidak digunakan
            $table->dropColumn(['status_verifikasi_ortu', 'no_hp_ortu']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            // Logika cadangan jika migrasi ingin dibatalkan (rollback)
            $table->string('no_hp_ortu')->nullable();
            $table->string('status_verifikasi_ortu')->nullable();
        });
    }
};