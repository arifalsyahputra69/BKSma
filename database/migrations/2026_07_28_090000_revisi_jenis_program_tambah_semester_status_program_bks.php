<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PERBAIKAN (28 Juli 2026):
     *
     * 1. Jenis program sebelumnya hanya "Tahunan" / "Semester". Sesuai
     *    revisi halaman Pengajuan Program, opsi "Semester" diganti
     *    penamaannya menjadi "Bulanan" (data lama otomatis dipindahkan),
     *    dan ditambahkan 2 jenis baru: "Semesteran" dan "Mingguan".
     *    Jadi jenis_program final: Tahunan, Semesteran, Bulanan, Mingguan.
     *
     * 2. Menambahkan `semester_id` (Tahun Ajaran aktif yang dipilih saat
     *    pengajuan) sebagai foreign key ke tabel `semesters`.
     *
     * 3. Menambahkan `status_program` (Draft / Aktif) -- status dokumen
     *    program, terpisah dari `status` (Diajukan/Disetujui/Ditolak)
     *    yang menandai hasil peninjauan Kepsek.
     */
    public function up(): void
    {
        // Longgarkan dulu enum jenis_program supaya memuat nilai lama & baru sekaligus,
        // supaya proses migrasi data (rename Semester -> Bulanan) tidak gagal disisipi.
        DB::statement("ALTER TABLE program_bks MODIFY jenis_program ENUM('Tahunan', 'Semester', 'Semesteran', 'Bulanan', 'Mingguan') NOT NULL DEFAULT 'Bulanan'");

        // Migrasi data: program yang tadinya berjenis "Semester" menjadi "Bulanan".
        DB::table('program_bks')->where('jenis_program', 'Semester')->update(['jenis_program' => 'Bulanan']);

        // Persempit enum ke daftar final.
        DB::statement("ALTER TABLE program_bks MODIFY jenis_program ENUM('Tahunan', 'Semesteran', 'Bulanan', 'Mingguan') NOT NULL DEFAULT 'Bulanan'");

        Schema::table('program_bks', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->after('jenis_program')
                ->constrained('semesters')->nullOnDelete();

            $table->enum('status_program', ['Draft', 'Aktif'])->default('Draft')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('program_bks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('semester_id');
            $table->dropColumn('status_program');
        });

        DB::statement("ALTER TABLE program_bks MODIFY jenis_program ENUM('Tahunan', 'Semester', 'Semesteran', 'Bulanan', 'Mingguan') NOT NULL DEFAULT 'Semester'");
        DB::table('program_bks')->where('jenis_program', 'Bulanan')->update(['jenis_program' => 'Semester']);
        DB::statement("ALTER TABLE program_bks MODIFY jenis_program ENUM('Tahunan', 'Semester') NOT NULL DEFAULT 'Semester'");
    }
};
