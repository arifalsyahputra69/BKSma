<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jurnal_layanans', function (Blueprint $table) {

            $table->dropColumn([
                'jenis_layanan',
                'ringkasan_masalah',
                'hasil_dan_tindak_lanjut',
            ]);

            $table->string('kategori_masalah');
            $table->longText('uraian_masalah');

            $table->string('pendekatan_teknik')->nullable();

            $table->longText('rencana_tindak_lanjut');

            $table->string('status_kasus');
        });
    }

    public function down(): void
    {
        Schema::table('jurnal_layanans', function (Blueprint $table) {

            $table->dropColumn([
                'kategori_masalah',
                'uraian_masalah',
                'pendekatan_teknik',
                'rencana_tindak_lanjut',
                'status_kasus',
            ]);

            $table->string('jenis_layanan');
            $table->text('ringkasan_masalah');
            $table->text('hasil_dan_tindak_lanjut');
        });
    }
};