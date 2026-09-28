<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabel untuk fitur "Pengajuan & Persetujuan Program BK".
     * Guru BK mengajukan program kerja (mis. sosialisasi jurusan,
     * penyuluhan anti-bullying, dll), lalu Kepala Sekolah meninjau
     * dan menyetujui/menolaknya.
     */
    public function up(): void
    {
        Schema::create('program_bks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_bk_id')->constrained('users')->cascadeOnDelete();
            $table->string('judul');
            $table->text('deskripsi');
            $table->string('sasaran'); // mis. "Seluruh Siswa", "Kelas X", "Siswa Kelas XII"
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->enum('status', ['Diajukan', 'Disetujui', 'Ditolak'])->default('Diajukan');
            $table->text('catatan_kepsek')->nullable();
            $table->foreignId('ditinjau_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditinjau_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_bks');
    }
};
