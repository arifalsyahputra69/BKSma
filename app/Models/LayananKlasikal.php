<?php

// LETAKKAN DI: app/Models/LayananKlasikal.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LayananKlasikal extends Model
{
    use HasFactory;

    protected $fillable = [
        'guru_bk_id',
        'tanggal_pelaksanaan',
        'kelas_target',
        'metode_penyampaian',
        'materi_kompetensi',
        'evaluasi_proses',
    ];

    protected $casts = [
        'tanggal_pelaksanaan' => 'datetime',
    ];

    public function guruBk()
    {
        return $this->belongsTo(User::class, 'guru_bk_id');
    }
}
