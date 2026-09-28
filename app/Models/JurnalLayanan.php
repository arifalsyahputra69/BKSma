<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JurnalLayanan extends Model
{
    use HasFactory;

    // Menentukan nama tabel secara eksplisit (opsional tapi disarankan agar aman)
    protected $table = 'jurnal_layanans';

    // Mengizinkan kolom-kolom ini untuk diisi data (mass assignable)
    protected $fillable = [
        'antrian_id',
        'jenis_catatan',
        'siswa_id',
        'guru_bk_id',
        'tanggal_konseling',
        'keperluan',
        'kategori_masalah',
        'uraian_masalah',
        'pendekatan_teknik',
        'rencana_tindak_lanjut',
        'status_kasus',
        'tingkat_pelanggaran',
        'poin',
        'panggil_ortu',
        'jadwal_pertemuan_ortu',
        // FASE 8: Notifikasi kasus darurat + approval DO/skorsing.
        'butuh_persetujuan_kepsek',
        'jenis_tindakan_diusulkan',
        'status_persetujuan_kepsek',
        'catatan_kepsek_persetujuan',
        'disetujui_oleh',
        'disetujui_at',
    ];

    protected $casts = [
        'tanggal_konseling' => 'date',
        'panggil_ortu' => 'boolean',
        'jadwal_pertemuan_ortu' => 'datetime',
        'butuh_persetujuan_kepsek' => 'boolean',
        'disetujui_at' => 'datetime',
    ];

    // Relasi: Kepsek yang menyetujui/menolak usulan tindakan disipliner
    public function disetujuiOleh()
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    // Relasi: Jurnal ini bisa milik satu Antrian Konseling
    // (nullable — jurnal individu manual tidak punya antrian_id)
    public function antrian()
    {
        return $this->belongsTo(AntrianKonseling::class, 'antrian_id');
    }

    // Relasi: Siswa yang dikonseling (dipakai khusus untuk jurnal manual,
    // karena jurnal dari antrian bisa dapat siswa lewat antrian->siswa)
    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    // Relasi: Guru BK yang menulis jurnal manual
    public function guruBk()
    {
        return $this->belongsTo(User::class, 'guru_bk_id');
    }

    /**
     * PERBAIKAN (28 Juli 2026, revisi ke-3): satu tabel jurnal_layanans
     * sekarang menampung 2 jenis catatan: sesi konseling penuh, dan
     * catatan pelanggaran cepat (dulu tabel terpisah `pelanggarans`).
     * Helper ini dipakai di blade supaya tampilan bisa menyesuaikan
     * (badge, field yang ditampilkan, dsb) tanpa mengulang string
     * 'Pelanggaran' di banyak tempat.
     */
    public function isPelanggaran(): bool
    {
        return $this->jenis_catatan === 'Pelanggaran';
    }

}