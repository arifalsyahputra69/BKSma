<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * PERBAIKAN BUG:
 *
 * Kolom `status` pada tabel `sesi_konselings` sebelumnya hanya
 * mendukung enum('Dibuka','Ditutup'), padahal aplikasi (fitur
 * "Batalkan Sesi" di GuruBK\SesiKonselingController@batalkan)
 * mencoba menyimpan status 'Dibatalkan'. Karena nilai itu tidak
 * ada di enum, MySQL akan menolak/memotong data sehingga fitur
 * batalkan sesi selalu gagal.
 *
 * Hal yang sama terjadi pada kolom `status` di tabel
 * `antrian_konselings`, yang sebelumnya hanya mendukung
 * enum('Menunggu','Dipanggil','Sedang Konseling','Selesai','Batal'),
 * padahal saat sesi dibatalkan oleh Guru BK, seluruh antrean yang
 * masih 'Menunggu'/'Dipanggil' diubah menjadi 'Dibatalkan'.
 *
 * Migration ini menambahkan nilai 'Dibatalkan' ke kedua enum
 * tersebut agar fitur pembatalan sesi (mis. karena Guru BK
 * mendadak rapat) benar-benar bisa berjalan dan notifikasi ke
 * siswa dapat terkirim.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE sesi_konselings
            MODIFY COLUMN status ENUM('Dibuka','Ditutup','Dibatalkan')
            NOT NULL DEFAULT 'Dibuka'
        ");

        DB::statement("
            ALTER TABLE antrian_konselings
            MODIFY COLUMN status ENUM(
                'Menunggu',
                'Dipanggil',
                'Sedang Konseling',
                'Selesai',
                'Batal',
                'Dibatalkan'
            ) NOT NULL DEFAULT 'Menunggu'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE sesi_konselings
            MODIFY COLUMN status ENUM('Dibuka','Ditutup')
            NOT NULL DEFAULT 'Dibuka'
        ");

        DB::statement("
            ALTER TABLE antrian_konselings
            MODIFY COLUMN status ENUM(
                'Menunggu',
                'Dipanggil',
                'Sedang Konseling',
                'Selesai',
                'Batal'
            ) NOT NULL DEFAULT 'Menunggu'
        ");
    }
};
