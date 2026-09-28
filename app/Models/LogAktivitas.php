<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogAktivitas extends Model
{
    use HasFactory;

    protected $table = 'log_aktivitas';

    // PERBAIKAN AUDIT (Bagian I, 22 Juli 2026): tambah kategori, topik,
    // respons, semester_id supaya tiap percakapan tercatat lengkap sesuai spec.
    protected $fillable = ['user_id', 'aktivitas', 'kategori', 'topik', 'respons', 'semester_id'];

    // Relasi untuk memanggil nama user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
}