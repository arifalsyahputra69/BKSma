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
        Schema::create('absensis', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sesi_absensi_id')
                ->constrained('sesi_absensis')
                ->cascadeOnDelete();

            $table->foreignId('siswa_id')
                ->constrained('siswas')
                ->cascadeOnDelete();

            $table->enum('status', ['Hadir', 'Izin', 'Sakit', 'Alpha']);

            // Diisi otomatis saat siswa scan QR
            $table->timestamp('waktu_scan')->nullable();

            // Diisi Guru Mapel untuk Izin/Sakit, atau catatan tambahan
            $table->text('keterangan')->nullable();

            $table->timestamps();

            // Satu siswa hanya boleh punya 1 status per sesi absensi
            $table->unique(['sesi_absensi_id', 'siswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensis');
    }
};
