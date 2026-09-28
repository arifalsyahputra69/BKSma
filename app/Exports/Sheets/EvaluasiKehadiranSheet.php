<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class EvaluasiKehadiranSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(protected array $data) {}

    public function title(): string
    {
        return 'Evaluasi Kehadiran';
    }

    public function headings(): array
    {
        return ['Nama Siswa', 'Kelas', 'Tanggal Konseling Pertama', '% Hadir Sebelum', '% Hadir Sesudah', 'Selisih'];
    }

    public function array(): array
    {
        $evaluasiKehadiran = $this->data['evaluasiKehadiran'] ?? collect();

        $rows = collect($evaluasiKehadiran)->map(fn ($x) => [
            optional($x->siswa->user)->name ?? '-',
            optional($x->siswa->kelas)->nama_kelas ?? '-',
            $x->tanggalPertama->format('d-m-Y'),
            $x->persenSebelum.' %',
            $x->persenSesudah.' %',
            ($x->selisih > 0 ? '+' : '').$x->selisih,
        ])->toArray();

        $rows[] = ['', '', '', '', '', ''];
        $rows[] = ['Total Siswa Dievaluasi', $this->data['totalSiswaDievaluasiKehadiran'] ?? 0, '', '', '', ''];
        $rows[] = ['Membaik', $this->data['totalMembaikKehadiran'] ?? 0, '', '', '', ''];
        $rows[] = ['Memburuk', $this->data['totalMemburukKehadiran'] ?? 0, '', '', '', ''];
        $rows[] = ['Tetap', $this->data['totalTetapKehadiran'] ?? 0, '', '', '', ''];
        $rows[] = ['Rata-rata % Sebelum', $this->data['rataRataPersenSebelum'] ?? '-', '', '', '', ''];
        $rows[] = ['Rata-rata % Sesudah', $this->data['rataRataPersenSesudah'] ?? '-', '', '', '', ''];

        return $rows;
    }
}
