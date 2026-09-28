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
        Schema::create('pilihan_alur_chatbot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alur_id')->constrained('alur_chatbot')->onDelete('cascade'); // Relasi ke pertanyaan
            $table->string('teks_pilihan'); // Teks di tombol, contoh: "Suka Menghitung"
            $table->text('respons'); // Jawaban chatbot, contoh: "Kamu cocok masuk Akuntansi atau Teknik."
            $table->string('tag_kampus')->nullable(); // Tag kampus relevan (contoh: Universitas Andalas)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pilihan_alur_chatbot');
    }
};
