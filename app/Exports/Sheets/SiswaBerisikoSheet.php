<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SiswaBerisikoSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(protected array $data) {}

    public function title(): string
    {
        return 'Siswa Berisiko';
    }

    public function headings(): array
    {
        return ['Nama Siswa', 'Kelas', 'Alpha', 'Kasus Terbuka', 'Total Konseling', 'Belum Tersentuh BK', 'Skor Risiko'];
    }

    public function array(): array
    {
        $siswaBerisiko = $this->data['siswaBerisiko'] ?? collect();

        return collect($siswaBerisiko)->map(fn ($x) => [
            optional($x->siswa->user)->name ?? '-',
            optional($x->siswa->kelas)->nama_kelas ?? '-',
            $x->alpha,
            $x->kasusTerbuka,
            $x->totalKonseling,
            $x->belumTersentuhBk ? 'Ya' : 'Tidak',
            $x->skorRisiko,
        ])->toArray();
    }
}
