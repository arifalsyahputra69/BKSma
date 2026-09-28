<?php

// LETAKKAN DI: database/migrations/2026_07_19_100001_tambah_persetujuan_kepsek_ke_jurnal_layanans.php
//
// FASE 8 (Backlog): Notifikasi kasus darurat + approval DO/skorsing.
// Sebelumnya tidak ada flag "butuh_persetujuan_kepsek" di jurnal_layanans,
// sehingga usulan Guru BK untuk mengeluarkan (DO) atau menskors siswa tidak
// pernah tercatat sebagai keputusan resmi yang butuh persetujuan Kepsek --
// hanya jadi catatan bebas di rencana_tindak_lanjut.
//
// Kolom baru:
// - butuh_persetujuan_kepsek: ditandai Guru BK saat kasus ini mengusulkan
//   tindakan disipliner berat (DO/Skorsing/lainnya).
// - jenis_tindakan_diusulkan: jenis tindakan yang diusulkan Guru BK.
// - status_persetujuan_kepsek: Menunggu / Disetujui / Ditolak.
// - catatan_kepsek_persetujuan, disetujui_oleh, disetujui_at: keputusan Kepsek.
//
// Kerahasiaan: keputusan Kepsek TIDAK PERNAH menampilkan uraian_masalah /
// pendekatan_teknik -- lihat Kepsek\KasusDaruratController yang hanya
// menyertakan kategori_masalah, tingkat_pelanggaran, dan jenis tindakan
// yang diusulkan.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jurnal_layanans', function (Blueprint $table) {
            $table->boolean('butuh_persetujuan_kepsek')->default(false)->after('jadwal_pertemuan_ortu');
            $table->string('jenis_tindakan_diusulkan')->nullable()->after('butuh_persetujuan_kepsek');
            $table->string('status_persetujuan_kepsek')->nullable()->after('jenis_tindakan_diusulkan');
            $table->text('catatan_kepsek_persetujuan')->nullable()->after('status_persetujuan_kepsek');
            $table->foreignId('disetujui_oleh')->nullable()->after('catatan_kepsek_persetujuan')->constrained('users')->nullOnDelete();
            $table->timestamp('disetujui_at')->nullable()->after('disetujui_oleh');
        });
    }

    public function down(): void
    {
        Schema::table('jurnal_layanans', function (Blueprint $table) {
            $table->dropForeign(['disetujui_oleh']);
            $table->dropColumn([
                'butuh_persetujuan_kepsek',
                'jenis_tindakan_diusulkan',
                'status_persetujuan_kepsek',
                'catatan_kepsek_persetujuan',
                'disetujui_oleh',
                'disetujui_at',
            ]);
        });
    }
};
