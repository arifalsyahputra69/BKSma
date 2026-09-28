<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PilihanAlurChatbot extends Model
{
    use HasFactory;
    protected $table = 'pilihan_alur_chatbot';
    protected $fillable = ['alur_id', 'teks_pilihan', 'respons', 'tag_kampus', 'kampus_id'];

    // Relasi balik ke tabel alur
    public function alur()
    {
        return $this->belongsTo(AlurChatbot::class, 'alur_id');
    }

    // FITUR BARU (30 Juli 2026): relasi opsional ke data master Kampus,
    // supaya rekomendasi jurusan bisa menampilkan info kampus lengkap
    // (logo, deskripsi, jurusan unggulan, jalur beasiswa, link website).
    public function kampus()
    {
        return $this->belongsTo(Kampus::class, 'kampus_id');
    }
}