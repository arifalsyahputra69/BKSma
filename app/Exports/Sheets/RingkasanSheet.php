<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class RingkasanSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(protected array $data) {}

    public function title(): string
    {
        return 'Ringkasan';
    }

    public function headings(): array
    {
        return ['Indikator', 'Nilai'];
    }

    public function array(): array
    {
        $d = $this->data;
        $rekap = $d['rekapAbsensi'] ?? [];

        return [
            ['Total Siswa', $d['totalSiswa'] ?? 0],
            ['Total Kelas', $d['totalKelas'] ?? 0],
            ['Persentase Kehadiran', ($d['persenKehadiran'] ?? 0).' %'],
            ['Jumlah Hadir', $rekap['Hadir'] ?? 0],
            ['Jumlah Izin', $rekap['Izin'] ?? 0],
            ['Jumlah Sakit', $rekap['Sakit'] ?? 0],
            ['Jumlah Alpha', $rekap['Alpha'] ?? 0],
            ['Total Layanan Konseling', $d['totalKonseling'] ?? 0],
            ['Total Kasus Masih Terbuka (Dipantau/Referal)', $d['totalKasusTerbuka'] ?? 0],
        ];
    }
}
