<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PERBAIKAN (28 Juli 2026, revisi ke-3) atas permintaan Kepsek:
     * gabungkan "Data Pelanggaran" (tabel pelanggarans, halaman terpisah)
     * ke dalam "Jurnal BK" (jurnal_layanans), supaya screening riwayat
     * seorang siswa (konseling + pelanggaran) bisa dilihat dalam satu
     * tempat, bukan dua halaman terpisah.
     *
     * Keputusan penting (dikonfirmasi user): kalau kategori pelanggaran
     * dicatat "Berat", WA ke orang tua & notifikasi Wali Kelas terkirim
     * OTOMATIS lewat pipeline yang SAMA dengan Jurnal Konseling "Berat"
     * (lihat JurnalLayananController::notifikasiOrtu/notifikasiWaliKelas)
     * -- karena kolom tingkat_pelanggaran sudah dipakai bersama.
     *
     * Kolom baru:
     * - jenis_catatan: 'Konseling' (default, sesi konseling penuh) atau
     *   'Pelanggaran' (catatan cepat, tanpa perlu isi pendekatan_teknik dsb).
     * - poin: poin pelanggaran (dulu cuma ada di tabel pelanggarans).
     *
     * kategori_masalah & rencana_tindak_lanjut dilonggarkan jadi nullable
     * karena catatan Pelanggaran tidak selalu mengisi keduanya dengan cara
     * yang sama seperti sesi konseling penuh.
     *
     * Tabel `pelanggarans` lama TIDAK dihapus -- diganti nama jadi
     * `pelanggarans_arsip_28jul2026` sebagai backup/arsip histori, supaya
     * proses ini tetap bisa dibatalkan (rollback) kalau ternyata ada
     * masalah di kemudian hari.
     */
    public function up(): void
    {
        Schema::table('jurnal_layanans', function (Blueprint $table) {
            $table->string('jenis_catatan')->default('Konseling')->after('antrian_id');
            $table->unsignedInteger('poin')->nullable()->after('tingkat_pelanggaran');
        });

        DB::statement('ALTER TABLE jurnal_layanans MODIFY kategori_masalah VARCHAR(255) NULL');
        DB::statement('ALTER TABLE jurnal_layanans MODIFY rencana_tindak_lanjut LONGTEXT NULL');

        // Migrasi data lama dari pelanggarans -> jurnal_layanans
        if (Schema::hasTable('pelanggarans')) {
            $pelanggaranLama = DB::table('pelanggarans')->get();

            foreach ($pelanggaranLama as $p) {
                DB::table('jurnal_layanans')->insert([
                    'siswa_id' => $p->siswa_id,
                    'guru_bk_id' => $p->guru_bk_id,
                    'tanggal_konseling' => $p->tanggal_kejadian,
                    'keperluan' => 'Pencatatan Pelanggaran',
                    'jenis_catatan' => 'Pelanggaran',
                    'kategori_masalah' => 'Pelanggaran Tata Tertib',
                    'uraian_masalah' => $p->deskripsi,
                    'rencana_tindak_lanjut' => $p->tindak_lanjut,
                    'status_kasus' => 'Selesai',
                    'tingkat_pelanggaran' => $p->kategori,
                    'poin' => $p->poin,
                    'panggil_ortu' => false,
                    'butuh_persetujuan_kepsek' => false,
                    'created_at' => $p->created_at,
                    'updated_at' => $p->updated_at,
                ]);
            }

            Schema::rename('pelanggarans', 'pelanggarans_arsip_28jul2026');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pelanggarans_arsip_28jul2026')) {
            Schema::rename('pelanggarans_arsip_28jul2026', 'pelanggarans');
        }

        DB::table('jurnal_layanans')->where('jenis_catatan', 'Pelanggaran')->delete();

        Schema::table('jurnal_layanans', function (Blueprint $table) {
            $table->dropColumn(['jenis_catatan', 'poin']);
        });

        DB::statement("ALTER TABLE jurnal_layanans MODIFY kategori_masalah VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE jurnal_layanans MODIFY rencana_tindak_lanjut LONGTEXT NOT NULL");
    }
};
