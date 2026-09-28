<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Permintaan konseling yang dibuat siswa saat Guru BK belum membuka sesi.
 * Lihat migration create_permintaan_konselings_table untuk latar belakangnya.
 */
class PermintaanKonseling extends Model
{
    use HasFactory;

    protected $fillable = [
        'siswa_id',
        'guru_bk_id',
        'keperluan',
        'status',
        'antrian_id',
    ];

    /**
     * Masa berlaku permintaan. Lewat dari ini, permintaan dianggap basi dan
     * tidak ikut dialihkan ke sesi baru -- supaya siswa tidak tiba-tiba masuk
     * antrean untuk keperluan yang ia ajukan berminggu-minggu sebelumnya dan
     * mungkin sudah tidak relevan lagi.
     */
    public const MASA_BERLAKU_HARI = 7;

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function guruBk()
    {
        return $this->belongsTo(User::class, 'guru_bk_id');
    }

    public function antrian()
    {
        return $this->belongsTo(AntrianKonseling::class, 'antrian_id');
    }

    /**
     * Permintaan yang benar-benar masih berlaku: statusnya Menunggu DAN belum
     * lewat masa berlaku.
     *
     * Batas waktu sengaja ditegakkan di sini, bukan mengandalkan penandaan
     * berkala. Dengan begitu permintaan basi tidak akan pernah ikut dialihkan
     * walaupun proses penandaan kedaluwarsa kebetulan belum sempat jalan.
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query
            ->where('status', 'Menunggu')
            ->where('created_at', '>=', now()->subDays(self::MASA_BERLAKU_HARI));
    }

    /**
     * Kapan permintaan ini hangus.
     */
    public function kedaluwarsaPada(): ?\Carbon\Carbon
    {
        if ($this->status !== 'Menunggu' || ! $this->created_at) {
            return null;
        }

        return $this->created_at->copy()->addDays(self::MASA_BERLAKU_HARI);
    }

    /**
     * Tandai permintaan yang sudah lewat masa berlaku, lalu beri tahu siswanya
     * supaya ia tahu harus mendaftar ulang -- bukan menunggu sesuatu yang tidak
     * akan pernah datang.
     *
     * Fungsi ini hanya merapikan tampilan; kebenaran alur sudah dijaga oleh
     * scope aktif() di atas. Aman dipanggil berkali-kali.
     *
     * @param  Builder|null  $lingkup  batasi ke siswa / guru tertentu bila perlu
     * @return int jumlah permintaan yang baru ditandai
     */
    public static function tandaiYangKedaluwarsa(?Builder $lingkup = null): int
    {
        $query = $lingkup ?: static::query();

        $basi = (clone $query)
            ->where('status', 'Menunggu')
            ->where('created_at', '<', now()->subDays(self::MASA_BERLAKU_HARI))
            ->with('siswa')
            ->get();

        if ($basi->isEmpty()) {
            return 0;
        }

        foreach ($basi as $permintaan) {
            $permintaan->update(['status' => 'Kedaluwarsa']);

            if ($permintaan->siswa && $permintaan->siswa->user_id) {
                Notifikasi::create([
                    'user_id' => $permintaan->siswa->user_id,
                    'judul'   => 'Permintaan Konseling Kedaluwarsa',
                    'pesan'   =>
                        'Permintaan konselingmu sudah lewat ' . self::MASA_BERLAKU_HARI .
                        " hari dan Guru BK belum membuka sesi.\n\n" .
                        'Kalau kamu masih ingin berkonsultasi, silakan mendaftar lagi ' .
                        'lewat halaman Sesi Konseling.',
                    'link'    => route('siswa.sesi.index'),
                    'is_read' => false,
                ]);
            }
        }

        return $basi->count();
    }
}
