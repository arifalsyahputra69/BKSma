<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Form "Isi Jurnal" di halaman Rekap Laporan sudah didesain ulang memakai
     * field kategori_masalah, uraian_masalah, pendekatan_teknik,
     * rencana_tindak_lanjut, status_kasus -- tapi tabel jurnal_layanans masih
     * pakai skema lama (jenis_layanan, ringkasan_masalah, hasil_dan_tindak_lanjut).
     * Migration ini menyamakan skema tabel dengan form yang sudah berjalan.
     */
    public function up(): void
    {
        Schema::table('jurnal_layanans', function (Blueprint $table) {
            if (Schema::hasColumn('jurnal_layanans', 'jenis_layanan')) {
                $table->dropColumn('jenis_layanan');
            }
            if (Schema::hasColumn('jurnal_layanans', 'ringkasan_masalah')) {
                $table->dropColumn('ringkasan_masalah');
            }
            if (Schema::hasColumn('jurnal_layanans', 'hasil_dan_tindak_lanjut')) {
                $table->dropColumn('hasil_dan_tindak_lanjut');
            }
        });

        Schema::table('jurnal_layanans', function (Blueprint $table) {
            if (!Schema::hasColumn('jurnal_layanans', 'kategori_masalah')) {
                $table->string('kategori_masalah')->after('jadwal_id');
            }
            if (!Schema::hasColumn('jurnal_layanans', 'uraian_masalah')) {
                $table->text('uraian_masalah')->after('kategori_masalah');
            }
            if (!Schema::hasColumn('jurnal_layanans', 'pendekatan_teknik')) {
                $table->string('pendekatan_teknik')->nullable()->after('uraian_masalah');
            }
            if (!Schema::hasColumn('jurnal_layanans', 'rencana_tindak_lanjut')) {
                $table->text('rencana_tindak_lanjut')->after('pendekatan_teknik');
            }
            if (!Schema::hasColumn('jurnal_layanans', 'status_kasus')) {
                $table->string('status_kasus')->default('Selesai')->after('rencana_tindak_lanjut');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_layanans', function (Blueprint $table) {
            $table->dropColumn(['kategori_masalah', 'uraian_masalah', 'pendekatan_teknik', 'rencana_tindak_lanjut', 'status_kasus']);
            $table->string('jenis_layanan')->nullable();
            $table->text('ringkasan_masalah')->nullable();
            $table->text('hasil_dan_tindak_lanjut')->nullable();
        });
    }
};