<?php

// LETAKKAN DI: app/Models/KegiatanBk.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KegiatanBk extends Model
{
    use HasFactory;

    protected $table = 'kegiatan_bks';

    protected $fillable = [
        'guru_bk_id',
        'judul',
        'deskripsi',
        'kategori',
        'sasaran',
        'tanggal_mulai',
        'tanggal_selesai',
        'lokasi',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'datetime',
            'tanggal_selesai' => 'datetime',
        ];
    }

    public function guruBk()
    {
        return $this->belongsTo(User::class, 'guru_bk_id');
    }

    public function sudahLewat(): bool
    {
        return $this->status === 'Direncanakan'
            && $this->tanggal_mulai !== null
            && $this->tanggal_mulai->isPast();
    }
}
