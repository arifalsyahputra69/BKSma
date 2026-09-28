<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kampus extends Model
{
    use HasFactory;

    protected $table = 'kampus';

    protected $fillable = [
        'nama_kampus',
        'logo',
        'deskripsi',
        'jurusan_unggulan',
        'jalur_beasiswa',
        'link_website',
    ];
}
