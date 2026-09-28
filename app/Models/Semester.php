<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    use HasFactory;

    // Tambahkan ini untuk mengizinkan input data
    protected $fillable = [
        'nama',
        'tanggal_mulai',
        'tanggal_selesai',
        'status_aktif',
    ];
}