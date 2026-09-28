<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProporsiLayananSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(protected array $data) {}

    public function title(): string
    {
        return 'Proporsi Layanan';
    }

    public function headings(): array
    {
        return ['Kategori Masalah', 'Jumlah Kasus'];
    }

    public function array(): array
    {
        $proporsi = $this->data['proporsiKategoriMasalah'] ?? collect();

        return collect($proporsi)
            ->map(fn ($jumlah, $kategori) => [$kategori, $jumlah])
            ->values()
            ->toArray();
    }
}
