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
        Schema::create('jurnal_layanans', function (Blueprint $table) {
            $table->id();

            // Satu jadwal konseling punya satu jurnal hasil layanan
            $table->foreignId('jadwal_id')
                ->constrained('jadwal_konselings')
                ->onDelete('cascade');

            $table->string('jenis_layanan'); // Konseling Individual, Kelompok, Bimbingan Karir, dll
            $table->text('ringkasan_masalah');
            $table->text('hasil_dan_tindak_lanjut');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_layanans');
    }
};