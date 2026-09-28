<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tujuan: mendukung aturan baru pengiriman WA konseling ke orang tua --
// WA HANYA dikirim kalau Guru BK menandai "Panggil Orang Tua/Wali" ATAU
// tingkat pelanggaran siswa = "Berat". Lihat app/Http/Controllers/GuruBK/
// JurnalLayananController.php (method notifikasiOrtu).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jurnal_layanans', function (Blueprint $table) {
            // Ringan / Sedang / Berat / null (null = bukan kasus pelanggaran,
            // mis. konseling belajar/karir biasa)
            $table->string('tingkat_pelanggaran')->nullable()->after('kategori_masalah');

            // Ditandai Guru BK saat memang memanggil orang tua/wali untuk
            // datang ke sekolah terkait kasus ini.
            $table->boolean('panggil_ortu')->default(false)->after('tingkat_pelanggaran');
        });
    }

    public function down(): void
    {
        Schema::table('jurnal_layanans', function (Blueprint $table) {
            $table->dropColumn(['tingkat_pelanggaran', 'panggil_ortu']);
        });
    }
};
