<?php

namespace App\Exports;

use App\Exports\Sheets\BebanGuruBkSheet;
use App\Exports\Sheets\EvaluasiKehadiranSheet;
use App\Exports\Sheets\KolaborasiOrtuSheet;
use App\Exports\Sheets\ProporsiLayananSheet;
use App\Exports\Sheets\RingkasanSheet;
use App\Exports\Sheets\SiswaBerisikoSheet;
use App\Exports\Sheets\StatusKasusSheet;
use App\Exports\Sheets\TrenKasusBulananSheet;
use App\Exports\Sheets\WaktuTungguSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Prioritas 7 (unduh laporan Excel): satu file .xlsx berisi 9 sheet,
 * masing-masing mewakili salah satu Area Monitoring Kepsek (Bagian 1
 * dokumen kebutuhan). Data yang dipakai persis sama dengan yang tampil
 * di dashboard (lihat DashboardController::buildData()), jadi selalu
 * konsisten dengan filter semester & kelas yang sedang dipilih Kepsek.
 *
 * Catatan kerahasiaan: sheet-sheet ini hanya memakai kolom yang sama
 * dengan dashboard (status, tanggal, kategori, skor) -- tidak pernah
 * menyertakan uraian_masalah atau pendekatan_teknik dari jurnal_layanans.
 */
class LaporanMonitoringExport implements WithMultipleSheets
{
    public function __construct(protected array $data) {}

    public function sheets(): array
    {
        return [
            new RingkasanSheet($this->data),
            new ProporsiLayananSheet($this->data),
            new StatusKasusSheet($this->data),
            new SiswaBerisikoSheet($this->data),
            new BebanGuruBkSheet($this->data),
            new WaktuTungguSheet($this->data),
            new KolaborasiOrtuSheet($this->data),
            new EvaluasiKehadiranSheet($this->data),
            new TrenKasusBulananSheet($this->data),
        ];
    }
}
