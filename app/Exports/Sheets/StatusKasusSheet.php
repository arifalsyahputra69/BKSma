<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class StatusKasusSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(protected array $data) {}

    public function title(): string
    {
        return 'Status Kasus & Kerawanan';
    }

    public function headings(): array
    {
        return ['Kelas', 'Jumlah Siswa', 'Hadir', 'Alpha', 'Konseling', 'Kasus Terbuka', 'Skor Kerawanan', 'Level'];
    }

    public function array(): array
    {
        $rekapPerKelas = $this->data['rekapPerKelas'] ?? collect();

        $rows = collect($rekapPerKelas)->map(fn ($r) => [
            $r->kelas->nama_kelas ?? '-',
            $r->kelas->siswas_count ?? 0,
            $r->hadir,
            $r->alpha,
            $r->konseling,
            $r->kasusTerbuka,
            $r->skorKerawanan,
            $r->levelKerawanan,
        ])->toArray();

        // Baris ringkasan status kasus keseluruhan sekolah di bagian bawah.
        $rekapStatusKasus = $this->data['rekapStatusKasus'] ?? [];
        $rows[] = ['', '', '', '', '', '', '', ''];
        $rows[] = ['Ringkasan Status Kasus (seluruh sekolah)', '', '', '', '', '', '', ''];
        $rows[] = ['Selesai', $rekapStatusKasus['Selesai'] ?? 0, '', '', '', '', '', ''];
        $rows[] = ['Dipantau', $rekapStatusKasus['Dipantau'] ?? 0, '', '', '', '', '', ''];
        $rows[] = ['Referal', $rekapStatusKasus['Referal'] ?? 0, '', '', '', '', '', ''];

        return $rows;
    }
}
