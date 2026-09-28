<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabel 'rule_chatbots' adalah sisa migrasi lama yang tidak lagi
     * dipakai oleh model/controller manapun (fitur chatbot rule sekarang
     * memakai tabel 'chatbot_rules'). Dihapus agar skema tidak membingungkan.
     *
     * OPSIONAL: hapus file ini jika ingin mempertahankan tabel lama tersebut.
     */
    public function up(): void
    {
        Schema::dropIfExists('rule_chatbots');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('rule_chatbots', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');
            $table->text('jawaban');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->timestamps();
        });
    }
};