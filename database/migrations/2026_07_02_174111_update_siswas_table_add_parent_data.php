<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('siswas', function (Blueprint $table) {
            // Kita cek dulu, jika kolom belum ada, baru tambahkan
            if (!Schema::hasColumn('siswas', 'tgl_lahir')) {
                $table->date('tgl_lahir')->nullable();
            }
            if (!Schema::hasColumn('siswas', 'no_hp_siswa')) {
                $table->string('no_hp_siswa')->nullable();
            }
            if (!Schema::hasColumn('siswas', 'nama_ortu')) {
                $table->string('nama_ortu')->nullable();
            }
            if (!Schema::hasColumn('siswas', 'no_hp_ortu')) {
                $table->string('no_hp_ortu')->nullable();
            }
            if (!Schema::hasColumn('siswas', 'status_verifikasi_ortu')) {
                $table->string('status_verifikasi_ortu')->nullable(); 
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('siswas', function (Blueprint $table) {
            $table->dropColumn(['tgl_lahir', 'no_hp_siswa', 'nama_ortu', 'no_hp_ortu', 'status_verifikasi_ortu']);
        });
    }
};