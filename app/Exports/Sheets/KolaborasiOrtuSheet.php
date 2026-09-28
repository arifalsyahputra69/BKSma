<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class KolaborasiOrtuSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(protected array $data) {}

    public function title(): string
    {
        return 'Kolaborasi Ortu';
    }

    public function headings(): array
    {
        return ['Kelas', 'Total Kasus Libatkan Ortu', 'Sudah Ada Jadwal', 'Belum Ada Jadwal'];
    }

    public function array(): array
    {
        $kolaborasiPerKelas = $this->data['kolaborasiPerKelas'] ?? collect();

        $rows = collect($kolaborasiPerKelas)->map(fn ($k) => [
            $k->kelas->nama_kelas ?? '-',
            $k->totalKasus,
            $k->sudahAdaJadwal,
            $k->belumAdaJadwal,
        ])->toArray();

        $rows[] = ['', '', '', ''];
        $rows[] = ['Total Seluruh Sekolah', $this->data['totalKasusLibatkanOrtu'] ?? 0,
            $this->data['totalSudahAdaJadwal'] ?? 0, $this->data['totalBelumAdaJadwal'] ?? 0];

        return $rows;
    }
}
