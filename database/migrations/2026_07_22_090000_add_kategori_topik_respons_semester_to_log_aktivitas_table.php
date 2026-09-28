<?php

// LETAKKAN DI: database/migrations/2026_07_22_090000_add_kategori_topik_respons_semester_to_log_aktivitas_table.php
//
// PERBAIKAN AUDIT (Bagian I - Chatbot Rule-Based Engine, 22 Juli 2026):
// Spec outline minta setiap percakapan tercatat lengkap dengan
// siswa, waktu, kategori, topik, respons, dan semester. Sebelumnya
// log_aktivitas cuma catatan generik ("melakukan percakapan baru dengan
// Chatbot") satu kali per siswa per hari (anti-spam), tanpa detail per
// percakapan. Migration ini menambah 4 kolom supaya tiap baris log bisa
// menyimpan detail itu. Lihat juga Siswa\ChatbotController yang sekarang
// mencatat SATU baris log untuk SETIAP kali chatbot membalas (bukan cuma
// sekali per hari lagi), supaya "Total Percakapan" & "Log Aktivitas
// Terkini" di Dashboard Guru BK benar-benar mencerminkan tiap
// percakapan sesuai spec.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('log_aktivitas', function (Blueprint $table) {
            $table->string('kategori')->nullable()->after('aktivitas');
            $table->string('topik')->nullable()->after('kategori');
            $table->text('respons')->nullable()->after('topik');
            $table->foreignId('semester_id')->nullable()->after('respons')
                ->constrained('semesters')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('log_aktivitas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('semester_id');
            $table->dropColumn(['kategori', 'topik', 'respons']);
        });
    }
};
