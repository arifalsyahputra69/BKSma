<?php

// LETAKKAN DI: app/Models/AkpdResponse.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AkpdResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'siswa_id',
        'akpd_item_id',
        'jawaban',
        'semester_id',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function item()
    {
        return $this->belongsTo(AkpdItem::class, 'akpd_item_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }
}