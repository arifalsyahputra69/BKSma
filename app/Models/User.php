<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; // 1. Import Trait HasRoles


class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles; // 2. Trait ini sudah membawa method hasRole()

    protected $fillable = [
        'name', 
        'email', 
        'password', 
        'nip', 
        'mata_pelajaran', // FITUR GABUNGAN TAB GURU (22 Juli 2026): khusus jabatan Guru Mapel
        'foto', 
        'is_active', 
        'jenis_kelamin',
        'kelas_id',            // <-- TAMBAHKAN INI
        'is_class_confirmed',
        'waktu_tidak_konfirmasi', // Tambahkan
        'status_dibatasi',
        'fcm_token', // FASE 7: token push notification (FCM)
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    public function siswa()
    {
        return $this->hasOne(Siswa::class);
    }
    public function kelasDiampu()
    {
        // Menyatakan bahwa id_guru_bk adalah kolom penghubung di tabel kelas
        return $this->hasMany(Kelas::class, 'id_guru_bk');
    }

    // --- HAPUS fungsi hasRole() manual di sini ---
    // Jangan buat fungsi hasRole sendiri karena Spatie sudah menyediakannya!
}