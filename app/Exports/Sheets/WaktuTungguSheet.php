<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class WaktuTungguSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(protected array $data) {}

    public function title(): string
    {
        return 'Waktu Tunggu Layanan';
    }

    public function headings(): array
    {
        return ['Guru BK', 'Jumlah Antrean Dipanggil', 'Rata-rata Menit Tunggu', 'Maks Menit Tunggu'];
    }

    public function array(): array
    {
        $waktuTungguPerGuru = $this->data['waktuTungguPerGuru'] ?? collect();
        $namaGuruBkMap = $this->data['namaGuruBkMap'] ?? collect();

        $rows = collect($waktuTungguPerGuru)->map(fn ($w) => [
            $namaGuruBkMap[$w->guru_bk_id] ?? ('Guru #'.$w->guru_bk_id),
            $w->jumlahAntrean,
            $w->rataRataMenit,
            $w->maksMenit,
        ])->toArray();

        $rows[] = ['', '', '', ''];
        $rows[] = ['Ringkasan Keseluruhan Sekolah', '', '', ''];
        $rows[] = ['Total Antrean Dipanggil', $this->data['totalAntreanDipanggil'] ?? 0, '', ''];
        $rows[] = ['Rata-rata Waktu Tunggu (menit)', $this->data['rataRataWaktuTunggu'] ?? '-', '', ''];
        $rows[] = ['Waktu Tunggu Terlama (menit)', $this->data['maksWaktuTunggu'] ?? '-', '', ''];
        $rows[] = [
            'Jumlah Antrean Tunggu Lama (>'.($this->data['ambangWaktuTungguLamaMenit'] ?? 30).' menit)',
            $this->data['totalAntreanTungguLama'] ?? 0, '', '',
        ];

        return $rows;
    }
}
