<?php

// LETAKKAN DI: app/Models/BimbinganKelompok.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BimbinganKelompok extends Model
{
    use HasFactory;

    protected $fillable = [
        'guru_bk_id',
        'tanggal_pelaksanaan',
        'topik',
        'daftar_anggota',
        'dinamika_kelompok',
        'kesimpulan_hasil',
    ];

    protected $casts = [
        'tanggal_pelaksanaan' => 'datetime',
    ];

    public function guruBk()
    {
        return $this->belongsTo(User::class, 'guru_bk_id');
    }
}
