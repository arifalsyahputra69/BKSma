<?php

// LETAKKAN DI: app/Models/KunjunganRumah.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KunjunganRumah extends Model
{
    use HasFactory;

    protected $table = 'kunjungan_rumahs';

    protected $fillable = [
        'siswa_id',
        'guru_bk_id',
        'tanggal_kunjungan',
        'nama_wali_ditemui',
        'alasan_kunjungan',
        'hasil_observasi',
        'kesepakatan_bersama',
        'dokumentasi_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kunjungan' => 'date',
        ];
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function guruBk()
    {
        return $this->belongsTo(User::class, 'guru_bk_id');
    }
}
