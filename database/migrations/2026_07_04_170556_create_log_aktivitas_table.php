<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('aktivitas'); // Contoh isi: "Melakukan percakapan baru dengan Chatbot"
            $table->timestamps(); // Akan otomatis mencatat waktu kejadian
        });
    }

    public function down()
    {
        Schema::dropIfExists('log_aktivitas');
    }
};