<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            // Kita masukkan ketiganya sekaligus di sini
            $table->string('nama_ortu')->nullable();
            $table->string('no_wa_ortu', 20)->nullable();
            $table->boolean('is_wa_verified')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            $table->dropColumn(['nama_ortu', 'no_wa_ortu', 'is_wa_verified']);
        });
    }
};