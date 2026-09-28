<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AntrianKonseling extends Model
{
    use HasFactory;

    protected $fillable = [
        'sesi_konseling_id',
        'siswa_id',
        'nomor_antrian',
        'keperluan',
        'status',
        'waktu_booking',
        'waktu_dipanggil',
    ];

    protected $casts = [
        'waktu_booking' => 'datetime',
        // Area Monitoring #6: dipakai untuk hitung waktu tunggu (waktu_dipanggil -
        // waktu_booking). Diisi sekali saja, tepat saat status berubah jadi
        // "Dipanggil" -- lihat GuruBK\SesiKonselingController::panggilBerikutnya().
        'waktu_dipanggil' => 'datetime',
    ];

    /**
     * Relasi ke sesi
     */
    public function sesi()
    {
        return $this->belongsTo(
            SesiKonseling::class,
            'sesi_konseling_id'
        );
    }

    /**
     * Relasi ke siswa
     */
    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    /**
     * Relasi ke jurnal layanan hasil konseling antrean ini
     * (satu antrian punya paling banyak satu jurnal, sesuai
     * unique constraint pada kolom antrian_id di jurnal_layanans)
     */
    public function jurnal()
    {
        return $this->hasOne(JurnalLayanan::class, 'antrian_id');
    }

    /**
     * Waktu tunggu dalam menit (waktu_dipanggil - waktu_booking).
     * Null kalau antrean belum pernah dipanggil (waktu_dipanggil kosong).
     */
    public function waktuTungguMenit(): ?int
    {
        if (! $this->waktu_dipanggil || ! $this->waktu_booking) {
            return null;
        }

        return $this->waktu_booking->diffInMinutes($this->waktu_dipanggil);
    }
}