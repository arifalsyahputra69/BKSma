<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    use HasFactory;

    /**
     * Asal-usul catatan absensi. Menentukan boleh-tidaknya baris ini ditimpa
     * oleh sistem; lihat migration 2026_08_09_020000 untuk alasannya.
     */
    public const SUMBER_SCAN = 'scan';         // siswa memindai QR sendiri
    public const SUMBER_MANUAL = 'manual';     // guru mengetiknya
    public const SUMBER_OTOMATIS = 'otomatis'; // disalin sistem dari jam lain hari itu
    public const SUMBER_SISTEM = 'sistem';     // penanda Alpha oleh cron

    protected $fillable = [
        'sesi_absensi_id',
        'siswa_id',
        'status',
        'waktu_scan',
        'keterangan',
        'bukti',
        'sumber',
    ];

    /**
     * Alamat berkas bukti (surat dokter / tangkapan layar WhatsApp), atau null
     * kalau tidak ada. Foldernya ditulis di satu tempat ini saja supaya tidak
     * tersebar di banyak view.
     */
    public function urlBukti(): ?string
    {
        return $this->bukti ? asset('storage/bukti-absensi/' . $this->bukti) : null;
    }

    /**
     * Berkas PDF perlu diperlakukan berbeda dari gambar saat ditampilkan --
     * gambar bisa dipratinjau langsung, PDF harus dibuka di tab baru.
     */
    public function buktiBerupaGambar(): bool
    {
        if (! $this->bukti) {
            return false;
        }

        return in_array(
            strtolower(pathinfo($this->bukti, PATHINFO_EXTENSION)),
            ['jpg', 'jpeg', 'png', 'webp']
        );
    }

    /**
     * Baris hasil salinan otomatis -- satu-satunya jenis yang boleh ditimpa
     * sistem tanpa bertanya. Baris lama (sumber null) sengaja TIDAK dianggap
     * otomatis: asalnya tidak diketahui, jadi diperlakukan seolah ditulis
     * manusia dan dibiarkan apa adanya.
     */
    public function berasalDariSalinanOtomatis(): bool
    {
        return $this->sumber === self::SUMBER_OTOMATIS;
    }

    protected function casts(): array
    {
        return [
            'waktu_scan' => 'datetime',
        ];
    }

    public function sesiAbsensi()
    {
        return $this->belongsTo(SesiAbsensi::class, 'sesi_absensi_id');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}
