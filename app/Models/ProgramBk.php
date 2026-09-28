<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramBk extends Model
{
    use HasFactory;

    protected $table = 'program_bks';

    protected $fillable = [
        'guru_bk_id',
        'jenis_program',
        'semester_id',
        'judul',
        'deskripsi',
        'rps_file',
        'sasaran',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'status_pelaksanaan',
        'catatan_kepsek',
        'ditinjau_oleh',
        'ditinjau_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'ditinjau_at' => 'datetime',
        ];
    }

    public function guruBk()
    {
        return $this->belongsTo(User::class, 'guru_bk_id');
    }

    public function ditinjauOleh()
    {
        return $this->belongsTo(User::class, 'ditinjau_oleh');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    /**
     * PERBAIKAN (28 Juli 2026): jenis_program punya 3 opsi
     * (Tahunan, Semesteran, Bulanan). Helper ini dipakai di blade
     * (Guru BK & Kepsek) supaya badge/label seragam di semua tempat
     * tanpa mengulang if-else jenis satu per satu.
     */
    public function jenisProgramBadgeClass(): string
    {
        return match ($this->jenis_program) {
            'Tahunan' => 'bg-primary-subtle text-primary border border-primary-subtle',
            'Semesteran' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
            'Bulanan' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            default => 'bg-light text-dark border',
        };
    }

    /**
     * Area Monitoring #1: program dianggap "mangkrak" kalau sudah
     * disetujui, tanggal selesainya sudah lewat, tapi Guru BK belum
     * menandainya "Selesai". Dihitung on-the-fly (bukan kolom tersimpan)
     * supaya statusnya selalu akurat terhadap tanggal hari ini tanpa
     * perlu job/cron terpisah untuk menyinkronkannya.
     */
    public function isMangkrak(): bool
    {
        if ($this->status !== 'Disetujui') {
            return false;
        }

        if ($this->status_pelaksanaan === 'Selesai') {
            return false;
        }

        return $this->tanggal_selesai !== null && $this->tanggal_selesai->isPast();
    }

    /**
     * PERBAIKAN (28 Juli 2026, revisi ke-2): dropdown realisasi manual
     * (Belum Mulai/Berjalan/Selesai) dihapus atas masukan Kepsek --
     * dianggap merepotkan Guru BK karena harus rutin diubah manual.
     * Sekarang "Belum Mulai" & "Berjalan" dihitung OTOMATIS dari
     * tanggal_mulai/tanggal_selesai. Guru BK hanya perlu 1 aksi manual:
     * klik "Tandai Selesai" saat programnya benar-benar rampung --
     * itu satu-satunya hal yang tidak bisa ditebak dari tanggal saja,
     * dan itu jugalah yang membedakan "Selesai" dari "Mangkrak".
     */
    public function statusRealisasiLabel(): string
    {
        if ($this->status !== 'Disetujui') {
            return '-';
        }

        if ($this->status_pelaksanaan === 'Selesai') {
            return 'Selesai';
        }

        if ($this->isMangkrak()) {
            return 'Mangkrak';
        }

        if ($this->tanggal_mulai !== null && $this->tanggal_mulai->isFuture()) {
            return 'Belum Mulai';
        }

        return 'Berjalan';
    }

    public function statusRealisasiBadgeClass(): string
    {
        return match ($this->statusRealisasiLabel()) {
            'Selesai' => 'bg-success',
            'Berjalan' => 'bg-info text-dark',
            'Mangkrak' => 'bg-danger',
            'Belum Mulai' => 'bg-secondary',
            default => 'bg-light text-dark',
        };
    }

    /**
     * Tombol "Tandai Selesai" hanya relevan ditampilkan kalau program
     * sudah disetujui dan belum ditandai selesai sebelumnya.
     */
    public function bisaDitandaiSelesai(): bool
    {
        return $this->status === 'Disetujui' && $this->status_pelaksanaan !== 'Selesai';
    }
}