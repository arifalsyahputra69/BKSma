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
        Schema::create('sesi_konselings', function (Blueprint $table) {
            $table->id();

            // Guru BK yang membuka sesi
            $table->foreignId('guru_bk_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Tanggal sesi
            $table->date('tanggal');

            // Label sesi
            // Contoh:
            // Istirahat Pertama
            // Istirahat Kedua
            // Sepulang Sekolah
            $table->string('nama_sesi');

            // Tempat
            $table->string('tempat');

            // Catatan Guru BK (opsional)
            $table->text('keterangan')->nullable();

            // Status sesi
            $table->enum('status', [
                'Dibuka',
                'Ditutup'
            ])->default('Dibuka');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesi_konselings');
    }
};