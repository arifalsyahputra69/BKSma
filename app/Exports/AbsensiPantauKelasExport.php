<?php

namespace App\Exports;

use App\Models\Kelas;
use App\Models\Semester;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * FITUR BARU (30 Juli 2026): unduh rekap absensi Excel untuk SATU kelas
 * binaan Guru BK (sengaja dibatasi per kelas, bukan gabungan seluruh kelas
 * binaan/seluruh sekolah -- lihat AbsensiPantauController::resolveKelasTerpilih()).
 *
 * PERBAIKAN (30 Juli 2026): sebelumnya file Excel yang dihasilkan cuma teks
 * polos tanpa warna/border sama sekali sehingga terlihat kurang menarik.
 * Sekarang dipercantik lewat WithEvents/AfterSheet -- judul & ringkasan
 * digabung (merge) & diberi warna brand, header tabel diberi warna hijau
 * tua + teks putih tebal, seluruh tabel diberi border, baris diberi warna
 * selang-seling (banding) supaya mudah dibaca, baris siswa dengan Alpha > 0
 * ditandai merah muda, plus freeze panes & autofilter di header tabel.
 */
class AbsensiPantauKelasExport implements FromCollection, WithHeadings, WithTitle, WithColumnWidths, WithEvents
{
    /** Baris ke berapa header tabel (No, Nama Siswa, dst) berada. */
    private const BARIS_HEADER_TABEL = 6;

    /** Warna brand SIM BK (senada dengan .page-header-banner di dashboard web). */
    private const WARNA_HIJAU_TUA = '1F4B3F';
    private const WARNA_HIJAU_MUDA = 'E7F2EC';
    private const WARNA_MERAH_MUDA = 'FDE8E8';

    public function __construct(
        protected Kelas $kelas,
        protected ?Semester $semester,
        protected Collection $rows,
        protected array $ringkasan,
        protected float $persenKehadiran,
    ) {
    }

    public function title(): string
    {
        // Nama sheet Excel maksimal 31 karakter & tidak boleh ada karakter tertentu.
        return Str::limit(preg_replace('/[\[\]\*\/\\\\\?:]/', '', $this->kelas->nama_kelas), 31, '');
    }

    public function headings(): array
    {
        return [
            ['Rekap Absensi Kelas '.$this->kelas->nama_kelas],
            ['Semester: '.($this->semester->nama ?? 'Semua Semester')],
            ['Dicetak: '.now()->translatedFormat('d F Y H:i').' WIB'],
            ['Kehadiran: '.$this->persenKehadiran.'% | Izin: '.$this->ringkasan['Izin'].' | Sakit: '.$this->ringkasan['Sakit'].' | Alpha: '.$this->ringkasan['Alpha']],
            [],
            ['No', 'Nama Siswa', 'NISN', 'Hadir', 'Izin', 'Sakit', 'Alpha', 'Total Absensi', '% Kehadiran'],
        ];
    }

    public function collection()
    {
        return $this->rows->values()->map(fn ($row, $i) => [
            $i + 1,
            $row->nama,
            $row->nisn,
            $row->hadir,
            $row->izin,
            $row->sakit,
            $row->alpha,
            $row->total,
            $row->persen_hadir.'%',
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 30,
            'C' => 16,
            'D' => 10,
            'E' => 10,
            'F' => 10,
            'G' => 10,
            'H' => 15,
            'I' => 14,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $barisHeader = self::BARIS_HEADER_TABEL;
                $barisDataTerakhir = $barisHeader + max($this->rows->count(), 1);
                $kolomTerakhir = 'I';

                // ===== Judul utama (baris 1): merge A:I, hijau tua, teks putih besar =====
                $sheet->mergeCells("A1:{$kolomTerakhir}1");
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15)->getColor()->setRGB('FFFFFF');
                $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::WARNA_HIJAU_TUA);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension(1)->setRowHeight(28);

                // ===== Baris info (2-4): semester, tanggal cetak, ringkasan angka =====
                foreach ([2, 3, 4] as $baris) {
                    $sheet->mergeCells("A{$baris}:{$kolomTerakhir}{$baris}");
                    $sheet->getStyle("A{$baris}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::WARNA_HIJAU_MUDA);
                    $sheet->getStyle("A{$baris}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(self::WARNA_HIJAU_TUA));
                    $sheet->getStyle("A{$baris}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
                }
                $sheet->getStyle('A4')->getFont()->setBold(true);

                // Baris 4 (ringkasan) sedikit lebih tebal bordernya di bawah, sebagai
                // pemisah visual sebelum tabel data dimulai.
                $sheet->getStyle("A4:{$kolomTerakhir}4")->getBorders()->getBottom()
                    ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB(self::WARNA_HIJAU_TUA);

                // ===== Header tabel (baris 6): hijau tua, teks putih tebal, center =====
                $rangeHeader = "A{$barisHeader}:{$kolomTerakhir}{$barisHeader}";
                $sheet->getStyle($rangeHeader)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
                $sheet->getStyle($rangeHeader)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::WARNA_HIJAU_TUA);
                $sheet->getStyle($rangeHeader)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($barisHeader)->setRowHeight(22);

                // ===== Seluruh area tabel (header + data): border tipis rapi =====
                if ($barisDataTerakhir >= $barisHeader) {
                    $rangeTabel = "A{$barisHeader}:{$kolomTerakhir}{$barisDataTerakhir}";
                    $sheet->getStyle($rangeTabel)->getBorders()->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('C9D6D1');

                    // Kolom angka (D-I) rata tengah.
                    $sheet->getStyle("D".($barisHeader + 1).":{$kolomTerakhir}{$barisDataTerakhir}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Baris selang-seling (banding) supaya lebih mudah dibaca, dan
                    // baris siswa dengan Alpha > 0 (kolom G) ditandai merah muda
                    // supaya Guru BK langsung sadar siswa mana yang perlu perhatian.
                    $daftarSiswa = $this->rows->values();
                    for ($i = 0; $i < $daftarSiswa->count(); $i++) {
                        $baris = $barisHeader + 1 + $i;
                        $siswa = $daftarSiswa->get($i);
                        $adaAlpha = $siswa && (int) $siswa->alpha > 0;

                        if ($adaAlpha) {
                            $warna = self::WARNA_MERAH_MUDA;
                        } elseif ($i % 2 === 1) {
                            $warna = 'F5F8F6';
                        } else {
                            $warna = 'FFFFFF';
                        }

                        $sheet->getStyle("A{$baris}:{$kolomTerakhir}{$baris}")
                            ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($warna);

                        if ($adaAlpha) {
                            $sheet->getStyle("G{$baris}")->getFont()->setBold(true)->getColor()->setRGB('B91C1C');
                        }
                    }
                }

                // ===== Freeze panes: baris di atas & termasuk header tabel tetap
                // terlihat saat sheet di-scroll ke bawah. =====
                $sheet->freezePane('A'.($barisHeader + 1));

                // ===== Autofilter di header tabel supaya bisa difilter/diurutkan
                // langsung di Excel tanpa perlu menambah kolom bantu. =====
                if ($barisDataTerakhir >= $barisHeader) {
                    $sheet->setAutoFilter("A{$barisHeader}:{$kolomTerakhir}{$barisDataTerakhir}");
                }

                // Baris kosong (5) disamarkan tingginya supaya transisi ke tabel rapi.
                $sheet->getRowDimension(5)->setRowHeight(6);
            },
        ];
    }
}
