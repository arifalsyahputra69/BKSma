<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_kelas',
        'id_guru_bk',
        'wali_kelas_id',
    ];

    public function guruBk()
    {
        // Beri tahu Laravel secara spesifik nama foreign key-nya
        return $this->belongsTo(User::class, 'id_guru_bk');
    }

    // Relasi ke user dengan role Wali Kelas yang bertanggung jawab atas kelas ini
    public function waliKelas()
    {
        return $this->belongsTo(User::class, 'wali_kelas_id');
    }

    // Tuliskan relasi ini agar Kelas mengenali anggota siswanya
    public function siswas()
    {
        return $this->hasMany(Siswa::class, 'kelas_id');
    }
}
