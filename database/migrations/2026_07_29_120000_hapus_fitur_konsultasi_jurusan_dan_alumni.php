<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PERBAIKAN (29 Juli 2026): fitur "Konsultasi Jurusan" (menu terpisah,
 * lalu digabung ke Jurnal BK) dan "Penelusuran Alumni" dihapus total dari
 * aplikasi -- yang tetap dipakai/ditambahkan cuma AKPD (Angket Kebutuhan
 * Peserta Didik). Migration ini membersihkan sisa skema database dari
 * kedua fitur tersebut:
 *
 * - Hapus baris jurnal_layanans yang jenis_catatan = 'Konsultasi Jurusan'.
 * - Hapus kolom jenis_konsultasi, kampus_id, minat_jurusan dari
 *   jurnal_layanans (ditambahkan oleh migration
 *   2026_07_29_090005_gabungkan_konsultasi_jurusan_ke_jurnal_layanans).
 * - Hapus tabel arsip konsultasi_jurusans_arsip_29jul2026 (dulu tabel
 *   konsultasi_jurusans sebelum digabung, lihat migration yang sama).
 * - Hapus tabel alumnis (dari migration
 *   2026_07_29_090003_create_alumnis_table).
 *
 * down() mengembalikan struktur tabel (kolom & tabel kosong) supaya
 * proses ini bisa dibatalkan, tapi TIDAK bisa mengembalikan data yang
 * sudah dihapus oleh up() -- data lama tidak disalin balik.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Hapus dulu baris Konsultasi Jurusan dari jurnal_layanans sebelum
        // kolom-kolom terkait ikut dihapus.
        DB::table('jurnal_layanans')->where('jenis_catatan', 'Konsultasi Jurusan')->delete();

        if (Schema::hasColumn('jurnal_layanans', 'kampus_id')) {
            Schema::table('jurnal_layanans', function (Blueprint $table) {
                $table->dropConstrainedForeignId('kampus_id');
            });
        }

        Schema::table('jurnal_layanans', function (Blueprint $table) {
            $kolom = array_filter(['jenis_konsultasi', 'minat_jurusan'], fn ($k) => Schema::hasColumn('jurnal_layanans', $k));
            if (!empty($kolom)) {
                $table->dropColumn($kolom);
            }
        });

        Schema::dropIfExists('konsultasi_jurusans_arsip_29jul2026');
        Schema::dropIfExists('alumnis');
    }

    public function down(): void
    {
        Schema::create('alumnis', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('nisn')->nullable();
            $table->string('tahun_lulus', 4);
            $table->string('kelas_terakhir')->nullable();
            $table->enum('status_saat_ini', ['Kuliah', 'Bekerja', 'Belum Kuliah/Bekerja', 'Lainnya']);
            $table->string('nama_institusi')->nullable();
            $table->string('jurusan_posisi')->nullable();
            $table->string('no_hp')->nullable();
            $table->string('email')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('konsultasi_jurusans_arsip_29jul2026', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->enum('jenis_konsultasi', ['Universitas', 'Beasiswa', 'Jurusan SMK/SMA']);
            $table->foreignId('kampus_id')->nullable()->constrained('kampus')->nullOnDelete();
            $table->string('minat_jurusan')->nullable();
            $table->text('pertanyaan');
            $table->enum('status', ['Menunggu', 'Diproses', 'Selesai'])->default('Menunggu');
            $table->text('catatan_guru_bk')->nullable();
            $table->foreignId('ditangani_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditangani_at')->nullable();
            $table->timestamps();
        });

        Schema::table('jurnal_layanans', function (Blueprint $table) {
            $table->enum('jenis_konsultasi', ['Universitas', 'Beasiswa', 'Jurusan SMK/SMA'])
                ->nullable()->after('jenis_catatan');
            $table->foreignId('kampus_id')->nullable()->after('jenis_konsultasi')
                ->constrained('kampus')->nullOnDelete();
            $table->string('minat_jurusan')->nullable()->after('kampus_id');
        });
    }
};
