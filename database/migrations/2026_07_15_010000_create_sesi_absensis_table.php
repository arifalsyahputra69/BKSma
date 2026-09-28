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
        Schema::create('sesi_absensis', function (Blueprint $table) {
            $table->id();

            // Guru Mapel yang generate QR
            $table->foreignId('guru_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Kelas yang diabsen
            $table->foreignId('kelas_id')
                ->constrained('kelas')
                ->cascadeOnDelete();

            // Semester saat sesi dibuat (untuk rekap per semester)
            $table->foreignId('semester_id')
                ->nullable()
                ->constrained('semesters')
                ->nullOnDelete();

            // Mata pelajaran (input bebas, belum ada master mapel)
            $table->string('mapel');

            // Jam pelajaran (input bebas, contoh: "Jam ke-3" / "07:30 - 08.15")
            $table->string('jam_ke');

            $table->date('tanggal');

            // Token unik untuk QR, dipakai di URL scan siswa
            $table->string('token', 64)->unique();

            // Aktif = QR masih berlaku / sesi masih berjalan
            // Selesai = sudah ditutup manual ATAU sudah diproses job auto-alpha
            $table->enum('status', ['Aktif', 'Selesai'])->default('Aktif');

            // Waktu QR kedaluwarsa (waktu generate + 5 menit)
            $table->timestamp('waktu_expired');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesi_absensis');
    }
};
