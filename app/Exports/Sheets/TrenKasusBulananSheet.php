<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class TrenKasusBulananSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(protected array $data) {}

    public function title(): string
    {
        return 'Tren Kasus Berulang';
    }

    public function headings(): array
    {
        return ['Bulan', 'Total Kasus', 'Kasus Berulang', '% Berulang'];
    }

    public function array(): array
    {
        $trenKasusBulanan = $this->data['trenKasusBulanan'] ?? collect();

        return collect($trenKasusBulanan)->map(fn ($r) => [
            $r->bulan,
            $r->totalKasus,
            $r->kasusBerulang,
            $r->persenBerulang.' %',
        ])->toArray();
    }
}
