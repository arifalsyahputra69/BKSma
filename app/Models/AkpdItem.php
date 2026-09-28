<?php

// LETAKKAN DI: app/Models/AkpdItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AkpdItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'kategori',
        'pertanyaan',
        'urutan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function responses()
    {
        return $this->hasMany(AkpdResponse::class, 'akpd_item_id');
    }
}
