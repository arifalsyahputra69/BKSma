<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LAPORAN PERBAIKAN KELAS JADI JELAS (5 Agustus 2026)
 *
 * Sebelumnya, saat siswa menekan "kelas saya berubah", satu-satunya yang
 * tercatat adalah status_konfirmasi_kelas = false. TU hanya menerima
 * notifikasi "siswa X melaporkan ketidaksesuaian data kelas" tanpa tahu
 * kelas yang benar itu apa -- jadi tetap harus bertanya balik ke siswa.
 *
 * Dua kolom di bawah menyimpan jawaban siswa langsung dari formulirnya:
 *   - kelas_tujuan_id          : kelas yang menurut siswa seharusnya
 *   - catatan_perbaikan_kelas  : keterangan tambahan (opsional)
 *
 * Keduanya nullable karena siswa yang menjawab "kelas saya sama" tidak
 * pernah mengisinya, dan data lama tentu saja belum punya isi apa pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            $table->unsignedBigInteger('kelas_tujuan_id')->nullable()->after('kelas_id');
            $table->text('catatan_perbaikan_kelas')->nullable()->after('waktu_tidak_konfirmasi');

            // onDelete('set null'): kalau kelas tujuan dihapus TU, baris siswa
            // tidak ikut terhapus -- laporannya cuma kehilangan tujuan, dan
            // TU bisa memilih ulang secara manual. Menghapus siswa hanya
            // karena kelasnya dihapus jelas bukan yang diinginkan.
            $table->foreign('kelas_tujuan_id')
                  ->references('id')->on('kelas')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            $table->dropForeign(['kelas_tujuan_id']);
            $table->dropColumn(['kelas_tujuan_id', 'catatan_perbaikan_kelas']);
        });
    }
};
