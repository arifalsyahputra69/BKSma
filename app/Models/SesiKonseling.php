<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SesiKonseling extends Model
{
    use HasFactory;

   protected $fillable = [
    'guru_bk_id',
    'tanggal',
    'nama_sesi',
    'tempat',
    'keterangan',
    'status',
    'alasan_pembatalan',
    ];

    /**
     * Guru BK yang membuka sesi
     */
    public function guruBk()
    {
        return $this->belongsTo(User::class, 'guru_bk_id');
    }

    /**
     * Satu sesi memiliki banyak antrean
     */
    public function antrians()
    {
        return $this->hasMany(
            AntrianKonseling::class,
            'sesi_konseling_id'
        );
    }
}