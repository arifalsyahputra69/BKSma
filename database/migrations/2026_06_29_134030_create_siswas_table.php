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
        Schema::create('siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Relasi ke akun login
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->nullOnDelete(); // Relasi ke tabel kelas
            $table->string('nisn')->unique();
            $table->string('foto')->nullable();
            $table->boolean('status_konfirmasi_kelas')->default(false);
            $table->timestamp('waktu_tidak_konfirmasi')->nullable();
            $table->boolean('status_dibatasi')->default(false);
            $table->timestamp('waktu_update_kelas')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siswas');
    }
};
