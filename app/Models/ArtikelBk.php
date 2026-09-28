<?php

// LETAKKAN DI: app/Models/ArtikelBk.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArtikelBk extends Model
{
    use HasFactory;

    protected $table = 'artikel_bks';

    protected $fillable = [
        'guru_bk_id',
        'judul',
        'slug',
        'kategori',
        'gambar_sampul',
        'ringkasan',
        'konten',
        'status_publish',
    ];

    protected $casts = [
        'status_publish' => 'boolean',
    ];

    public function guruBk()
    {
        return $this->belongsTo(User::class, 'guru_bk_id');
    }
}
