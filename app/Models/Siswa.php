<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    use HasFactory;

    // Pastikan nama tabelnya benar
    protected $table = 'siswas';

    // Kolom-kolom yang diizinkan untuk diisi
    protected $fillable = [
        'user_id',
        'kelas_id', // Wajib ada agar kelas_id bisa tersimpan
        'kelas_tujuan_id',          // kelas yang menurut siswa seharusnya
        'nisn',
        'status_konfirmasi_kelas',
        'waktu_tidak_konfirmasi',
        'catatan_perbaikan_kelas',  // keterangan tambahan dari siswa
        'tgl_lahir',
        'no_hp_siswa',
        'nama_ortu',
        'no_wa_ortu',
        // 'no_hp_ortu', <- INI SUDAH DIHAPUS (kolom lama, jangan dikembalikan)
        'is_wa_verified',
    ];

    /**
     * PERBAIKAN (2 Agustus 2026): sebelumnya model ini tidak punya $casts
     * sama sekali, padahal seluruh alur verifikasi data orang tua memakai
     * perbandingan KETAT (===) terhadap angka:
     *
     *   - gurubk/data-siswa/show.blade.php : is_wa_verified === 0 / === 1
     *   - GuruBK\DashboardController       : where('is_wa_verified', 0)
     *   - FonnteService (guard kirim WA)   : ! $siswa->is_wa_verified
     *
     * Kalau driver database mengembalikan kolom tinyint sebagai STRING "0"
     * (perilaku ini berbeda-beda tergantung versi PHP, driver MySQL, dan
     * setelan emulate_prepares di server), maka "0" === 0 bernilai FALSE --
     * tombol Verifikasi/Tolak tidak pernah dirender, dan status siswa
     * selamanya tampak "Menunggu" walau datanya sudah benar. Gagalnya diam,
     * tanpa pesan error.
     *
     * Cast di bawah memaksa nilainya selalu integer di sisi PHP, apa pun
     * yang dikembalikan driver. null tetap null (Laravel mempertahankan
     * null pada cast), jadi status "siswa belum mengisi" tetap terbaca.
     */
    protected $casts = [
        'is_wa_verified' => 'integer',
        'status_konfirmasi_kelas' => 'boolean',
        'waktu_tidak_konfirmasi' => 'datetime',
        'waktu_update_kontak' => 'datetime',
    ];

    // Relasi balik ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // 👇 INI DIA FUNGSI YANG BIKIN ERROR KARENA KELUPAAN 👇
    public function kelas()
    {
        // Pastikan nama model Kelas sudah benar
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    /**
     * Kelas yang DIAJUKAN siswa lewat laporan perbaikan data kelas.
     * Berbeda dengan kelas() di atas yang berisi kelas resmi saat ini --
     * yang ini baru sebatas usulan, dan baru berlaku setelah TU menyetujui.
     */
    public function kelasTujuan()
    {
        return $this->belongsTo(Kelas::class, 'kelas_tujuan_id');
    }

    /**
     * Batas waktu (dalam hari) sejak siswa melapor sampai akunnya dikunci
     * kalau TU belum juga memperbaiki data kelasnya.
     *
     * Ditaruh sebagai konstanta di model supaya angka 7 tidak tersebar dan
     * berbeda-beda di middleware, halaman TU, dan tampilan siswa -- ketiganya
     * membaca dari sini.
     */
    public const TENGGANG_PERBAIKAN_KELAS_HARI = 7;

    /**
     * Tenggat penguncian akun, atau null kalau siswa memang tidak sedang
     * dalam status melapor.
     */
    public function batasTenggangKelas(): ?\Carbon\Carbon
    {
        if ($this->status_konfirmasi_kelas !== false || ! $this->waktu_tidak_konfirmasi) {
            return null;
        }

        return $this->waktu_tidak_konfirmasi->copy()->addDays(self::TENGGANG_PERBAIKAN_KELAS_HARI);
    }

    /**
     * True kalau masa tenggang sudah lewat dan akun seharusnya dikunci.
     */
    public function tenggangKelasHabis(): bool
    {
        $batas = $this->batasTenggangKelas();

        return $batas !== null && now()->greaterThan($batas);
    }
}