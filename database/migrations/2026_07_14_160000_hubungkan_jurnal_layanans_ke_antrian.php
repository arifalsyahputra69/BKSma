<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sebelumnya jurnal_layanans terhubung ke jadwal_konselings lewat
 * kolom jadwal_id. Karena sistem sekarang sudah pindah ke alur
 * antrean (sesi_konselings + antrian_konselings), jurnal harus
 * terhubung ke antrian_konselings lewat kolom antrian_id.
 *
 * antrian_id dibuat nullable karena jurnal individu manual (dibuat
 * lewat GuruBK\JurnalLayananController@storeManual) tidak berasal
 * dari antrean sama sekali.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jurnal_layanans', function (Blueprint $table) {
            // Lepas dulu relasi lama ke jadwal_konselings
            $table->dropForeign(['jadwal_id']);
            $table->dropUnique(['jadwal_id']);
            $table->dropColumn('jadwal_id');

            // Pasang relasi baru ke antrian_konselings
            $table->foreignId('antrian_id')
                ->nullable()
                ->unique()
                ->after('id')
                ->constrained('antrian_konselings')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('jurnal_layanans', function (Blueprint $table) {
            $table->dropForeign(['antrian_id']);
            $table->dropUnique(['antrian_id']);
            $table->dropColumn('antrian_id');

            $table->foreignId('jadwal_id')
                ->after('id')
                ->constrained('jadwal_konselings')
                ->onDelete('cascade');
            $table->unique('jadwal_id');
        });
    }
};
