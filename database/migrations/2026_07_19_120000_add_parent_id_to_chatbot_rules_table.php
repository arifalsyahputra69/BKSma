<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PERBAIKAN AUDIT (Poin 1 - Wajib):
 * Kolom `parent_id` pada tabel `chatbot_rules` sebelumnya sudah ada di
 * database aktual (dibuat manual / lewat migration lain yang sudah dihapus),
 * tapi TIDAK PERNAH tercatat resmi di migration file manapun.
 *
 * Akibatnya: kalau project di-deploy ulang dari nol (php artisan migrate:fresh),
 * kolom ini tidak akan tercipta dan fitur menu bertingkat Chatbot Mode 2
 * (relasi parent-child di ChatbotRule::parent()) akan error saat runtime.
 *
 * Migration ini pakai Schema::hasColumn() supaya AMAN dijalankan baik di:
 * - Database lama yang sudah punya kolom ini (akan di-skip, tidak error)
 * - Database baru hasil migrate:fresh (kolom akan dibuat)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('chatbot_rules', 'parent_id')) {
            Schema::table('chatbot_rules', function (Blueprint $table) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('category')
                    ->constrained('chatbot_rules')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('chatbot_rules', 'parent_id')) {
            Schema::table('chatbot_rules', function (Blueprint $table) {
                $table->dropConstrainedForeignId('parent_id');
            });
        }
    }
};
