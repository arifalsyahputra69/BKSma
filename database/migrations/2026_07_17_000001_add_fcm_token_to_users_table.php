<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// LETAKKAN DI: database/migrations/2026_07_17_000001_add_fcm_token_to_users_table.php
//
// Menyimpan token perangkat (browser/HP) untuk push notification (Fase 7 - FCM).
// Didesain simpel: satu user = satu token aktif terakhir (cukup untuk kebutuhan
// "notif masuk saat browser/app terbuka di device yang sedang dipakai").
// Kalau nanti butuh banyak device sekaligus per user, tinggal dipecah jadi
// tabel fcm_tokens terpisah -- untuk Fase 7 ini cukup 1 kolom saja.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('fcm_token')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('fcm_token');
        });
    }
};
