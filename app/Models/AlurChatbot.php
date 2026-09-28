<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlurChatbot extends Model
{
    use HasFactory;
    protected $table = 'alur_chatbot';
    protected $fillable = ['pertanyaan'];

    // Relasi: Satu pertanyaan punya banyak pilihan jawaban
    public function pilihan()
    {
        return $this->hasMany(PilihanAlurChatbot::class, 'alur_id');
    }
}