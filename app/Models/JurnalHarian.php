<?php

// LETAKKAN DI: app/Models/JurnalHarian.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JurnalHarian extends Model
{
    use HasFactory;

    protected $fillable = [
        'guru_bk_id',
        'tanggal_waktu',
        'jenis_kegiatan',
        'sasaran',
        'deskripsi_kegiatan',
        'hambatan_catatan',
    ];

    protected $casts = [
        'tanggal_waktu' => 'datetime',
    ];

    public function guruBk()
    {
        return $this->belongsTo(User::class, 'guru_bk_id');
    }
}
