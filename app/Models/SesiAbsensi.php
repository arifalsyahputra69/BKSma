<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SesiAbsensi extends Model
{
    use HasFactory;

    protected $fillable = [
        'guru_id',
        'kelas_id',
        'semester_id',
        'mapel',
        'jam_ke',
        'tanggal',
        'token',
        'status',
        'waktu_expired',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'waktu_expired' => 'datetime',
        ];
    }

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function absensis()
    {
        return $this->hasMany(Absensi::class, 'sesi_absensi_id');
    }

    /**
     * QR dianggap kedaluwarsa kalau sudah lewat waktu_expired
     * ATAU sesi sudah ditutup manual oleh Guru Mapel.
     */
    public function isExpired(): bool
    {
        return $this->status !== 'Aktif' || now()->greaterThan($this->waktu_expired);
    }
}
