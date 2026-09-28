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
        Schema::create('alur_chatbot', function (Blueprint $table) {
            $table->id();
            $table->string('pertanyaan'); // Contoh: "Kamu lebih suka menghitung atau menggambar?"
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alur_chatbot');
    }
};
