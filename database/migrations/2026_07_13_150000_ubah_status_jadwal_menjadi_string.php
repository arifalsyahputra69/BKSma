<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up() {
        // Mengubah tipe kolom enum menjadi string biasa agar lebih fleksibel
        DB::statement("ALTER TABLE jadwal_konselings MODIFY COLUMN status VARCHAR(255) DEFAULT 'Tersedia'");
    }
    public function down() {}
};