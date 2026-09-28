<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('jadwal_konselings', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('guru_bk_id')->constrained('users')->onDelete('cascade');
            
            // 👇 TAMBAHKAN ->nullable() DI SINI 👇
            $table->foreignId('siswa_id')->nullable()->constrained('siswas')->onDelete('cascade');
            
            $table->date('tanggal');
            $table->time('waktu');
            $table->string('tempat')->default('Ruang BK'); 
            
            // 👇 TAMBAHKAN ->nullable() DI SINI 👇
            $table->string('keperluan')->nullable(); 
            
            $table->enum('status', ['Tersedia', 'Menunggu Konfirmasi', 'Dijadwalkan', 'Selesai', 'Batal'])->default('Tersedia');
            
            $table->text('catatan_hasil')->nullable(); 
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('jadwal_konselings');
    }
};