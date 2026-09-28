<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class BebanGuruBkSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(protected array $data) {}

    public function title(): string
    {
        return 'Beban Guru BK';
    }

    public function headings(): array
    {
        return ['Nama Guru BK', 'Jumlah Kelas Diampu', 'Jumlah Siswa Binaan', 'Jumlah Sesi', 'Melebihi Rasio Ideal (1:150)'];
    }

    public function array(): array
    {
        $bebanGuruBk = $this->data['bebanGuruBk'] ?? collect();

        return collect($bebanGuruBk)->map(fn ($g) => [
            $g->nama,
            $g->jumlahKelas,
            $g->jumlahSiswaBinaan,
            $g->jumlahSesi,
            $g->melebihiRasioIdeal ? 'Ya' : 'Tidak',
        ])->toArray();
    }
}
