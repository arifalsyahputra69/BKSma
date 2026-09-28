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
        Schema::create('antrian_konselings', function (Blueprint $table) {

            $table->id();

            // Mengacu ke sesi
            $table->foreignId('sesi_konseling_id')
                ->constrained()
                ->cascadeOnDelete();

            // Siswa yang mengambil antrean
            $table->foreignId('siswa_id')
                ->constrained('siswas')
                ->cascadeOnDelete();

            // Nomor antrean otomatis
            $table->integer('nomor_antrian');

            // Keluhan awal
            $table->text('keperluan');

            // Status antrean
            $table->enum('status',[
                'Menunggu',
                'Dipanggil',
                'Sedang Konseling',
                'Selesai',
                'Batal'
            ])->default('Menunggu');

            // Waktu booking
            $table->timestamp('waktu_booking');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('antrian_konselings');
    }
};