<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PERBAIKAN (29 Juli 2026): gabungkan "Konsultasi Jurusan/Jalur Studi
 * Lanjut" (dulu tabel & menu terpisah) ke dalam Jurnal BK (jurnal_layanans),
 * mengikuti pola yang sama seperti penggabungan Pelanggaran
 * (lihat 2026_07_28_110000_gabungkan_pelanggaran_ke_jurnal_layanans.php).
 *
 * Kolom baru khusus Konsultasi Jurusan:
 * - jenis_konsultasi: Universitas / Beasiswa / Jurusan SMK/SMA
 * - kampus_id: opsional, referensi ke tabel kampus
 * - minat_jurusan: opsional, teks bebas
 *
 * Pemetaan field yang dipakai bersama dengan jenis catatan lain:
 * - uraian_masalah  <- pertanyaan siswa
 * - rencana_tindak_lanjut <- catatan_guru_bk (jawaban/tanggapan)
 * - status_kasus    <- status (Menunggu/Diproses/Selesai)
 * - guru_bk_id      <- ditangani_oleh, fallback ke kelas->id_guru_bk siswa
 *
 * Tabel konsultasi_jurusans lama TIDAK dihapus -- diganti nama jadi
 * arsip, sama seperti pelanggarans, supaya proses ini bisa dibatalkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jurnal_layanans', function (Blueprint $table) {
            $table->enum('jenis_konsultasi', ['Universitas', 'Beasiswa', 'Jurusan SMK/SMA'])
                ->nullable()->after('jenis_catatan');
            $table->foreignId('kampus_id')->nullable()->after('jenis_konsultasi')
                ->constrained('kampus')->nullOnDelete();
            $table->string('minat_jurusan')->nullable()->after('kampus_id');
        });

        if (Schema::hasTable('konsultasi_jurusans')) {
            $dataLama = DB::table('konsultasi_jurusans')->get();

            foreach ($dataLama as $k) {
                $guruBkId = $k->ditangani_oleh;

                if (!$guruBkId) {
                    $kelas = DB::table('siswas')
                        ->join('kelas', 'kelas.id', '=', 'siswas.kelas_id')
                        ->where('siswas.id', $k->siswa_id)
                        ->value('kelas.id_guru_bk');
                    $guruBkId = $kelas;
                }

                DB::table('jurnal_layanans')->insert([
                    'siswa_id' => $k->siswa_id,
                    'guru_bk_id' => $guruBkId,
                    'tanggal_konseling' => $k->created_at,
                    'keperluan' => 'Konsultasi Jurusan/Studi Lanjut',
                    'jenis_catatan' => 'Konsultasi Jurusan',
                    'jenis_konsultasi' => $k->jenis_konsultasi,
                    'kampus_id' => $k->kampus_id,
                    'minat_jurusan' => $k->minat_jurusan,
                    'kategori_masalah' => 'Konsultasi Jurusan/Studi Lanjut',
                    'uraian_masalah' => $k->pertanyaan,
                    'rencana_tindak_lanjut' => $k->catatan_guru_bk,
                    'status_kasus' => $k->status,
                    'panggil_ortu' => false,
                    'butuh_persetujuan_kepsek' => false,
                    'created_at' => $k->created_at,
                    'updated_at' => $k->updated_at,
                ]);
            }

            Schema::rename('konsultasi_jurusans', 'konsultasi_jurusans_arsip_29jul2026');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('konsultasi_jurusans_arsip_29jul2026')) {
            Schema::rename('konsultasi_jurusans_arsip_29jul2026', 'konsultasi_jurusans');
        }

        DB::table('jurnal_layanans')->where('jenis_catatan', 'Konsultasi Jurusan')->delete();

        Schema::table('jurnal_layanans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kampus_id');
            $table->dropColumn(['jenis_konsultasi', 'minat_jurusan']);
        });
    }
};
