<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('siswas', function (Blueprint $table) {
            // Kolom ini mencatat kapan siswa terakhir ganti Email/No HP
            if (!Schema::hasColumn('siswas', 'waktu_update_kontak')) {
                $table->timestamp('waktu_update_kontak')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('siswas', function (Blueprint $table) {
            $table->dropColumn('waktu_update_kontak');
        });
    }
};