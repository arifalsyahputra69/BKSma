<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Absensi {{ $kelas->nama_kelas }}</title>
    <style>
        /* dompdf tidak mendukung flexbox/grid -- semua layout pakai table & block biasa.
           PERBAIKAN (30 Juli 2026): PDF sebelumnya polos hitam-putih tanpa identitas
           sekolah sama sekali. Sekarang diberi header band warna hijau brand (senada
           .page-header-banner di dashboard web), kartu ringkasan bergaya "chip",
           tabel dengan header hijau + baris selang-seling, dan baris Alpha ditandai
           lebih tegas -- tetap memakai properti CSS yang aman untuk dompdf
           (background-color, border, border-radius terbatas; tanpa flexbox/grid). */
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #222; margin: 0; }

        .header-band {
            background-color: #1f4b3f;
            color: #ffffff;
            padding: 16px 22px;
            margin-bottom: 16px;
        }
        .header-band table { width: 100%; border: none; margin: 0; }
        .header-band td { border: none; padding: 0; }
        .header-band h1 { font-size: 19px; margin: 0 0 4px 0; color: #ffffff; }
        .header-band p.subtitle { font-size: 10px; color: #d6e8df; margin: 0; }
        .header-band .sekolah-tag {
            font-size: 9px; color: #bfe0cf; text-transform: uppercase; letter-spacing: .5px;
            margin: 0 0 3px 0;
        }

        .content-wrap { padding: 0 22px; }

        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.data-table th, table.data-table td {
            border: 1px solid #d7e2dd; padding: 5px 7px; text-align: left; font-size: 10px;
        }
        table.data-table th {
            background-color: #1f4b3f; color: #ffffff; font-weight: bold;
            text-transform: uppercase; font-size: 9px; letter-spacing: .3px;
        }
        td.angka, th.angka { text-align: center; }
        tr.baris-genap td { background-color: #f3f8f6; }
        tr.baris-alpha td { background-color: #fdeaea; }
        tr.baris-alpha td.kolom-alpha { color: #b91c1c; font-weight: bold; }

        /* ===== Kartu ringkasan bergaya "chip" (4 kotak sejajar pakai table) ===== */
        table.kartu-ringkasan { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin-bottom: 14px; }
        table.kartu-ringkasan td {
            border: 1px solid #d7e2dd; border-radius: 6px; background-color: #f7faf9;
            padding: 8px 10px; width: 25%; text-align: center;
        }
        table.kartu-ringkasan .label { color: #5b7169; font-size: 8.5px; text-transform: uppercase; letter-spacing: .4px; }
        table.kartu-ringkasan .angka-besar { font-size: 17px; font-weight: bold; color: #1f4b3f; display: block; margin-top: 2px; }
        table.kartu-ringkasan td.chip-kehadiran { background-color: #1f4b3f; border-color: #1f4b3f; }
        table.kartu-ringkasan td.chip-kehadiran .label { color: #bfe0cf; }
        table.kartu-ringkasan td.chip-kehadiran .angka-besar { color: #ffffff; }
        table.kartu-ringkasan td.chip-alpha .angka-besar { color: #b91c1c; }

        .footer-note {
            font-size: 8.5px; color: #7c8c86; margin-top: 14px; border-top: 1px solid #d7e2dd;
            padding-top: 8px;
        }
        .legenda { font-size: 8.5px; color: #5b7169; margin-bottom: 10px; }
        .legenda span.kotak {
            display: inline-block; width: 8px; height: 8px; margin-right: 3px;
            border: 1px solid #d7e2dd; vertical-align: middle;
        }
    </style>
</head>
<body>

    <div class="header-band">
        <table>
            <tr>
                <td>
                    <p class="sekolah-tag">SMA Kartika I-5 Padang &middot; Bimbingan &amp; Konseling</p>
                    <h1>Rekap Absensi Kelas {{ $kelas->nama_kelas }}</h1>
                    <p class="subtitle">
                        Semester: {{ $semester->nama ?? 'Semua Semester' }}
                        &nbsp;|&nbsp; Guru BK: {{ Auth::user()->name }}
                        &nbsp;|&nbsp; Dicetak {{ now()->translatedFormat('d F Y H:i') }} WIB
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <div class="content-wrap">

        <table class="kartu-ringkasan">
            <tr>
                <td class="chip-kehadiran"><span class="label">Persentase Kehadiran</span><span class="angka-besar">{{ $persenKehadiran }}%</span></td>
                <td><span class="label">Izin</span><span class="angka-besar">{{ $ringkasan['Izin'] }}</span></td>
                <td><span class="label">Sakit</span><span class="angka-besar">{{ $ringkasan['Sakit'] }}</span></td>
                <td class="chip-alpha"><span class="label">Alpha</span><span class="angka-besar">{{ $ringkasan['Alpha'] }}</span></td>
            </tr>
        </table>

        <p class="legenda"><span class="kotak" style="background-color:#fdeaea;"></span>Baris merah muda menandakan siswa dengan catatan Alpha pada periode ini.</p>

        <table class="data-table">
            <tr>
                <th>No</th>
                <th>Nama Siswa</th>
                <th>NISN</th>
                <th class="angka">Hadir</th>
                <th class="angka">Izin</th>
                <th class="angka">Sakit</th>
                <th class="angka">Alpha</th>
                <th class="angka">Total</th>
                <th class="angka">% Hadir</th>
            </tr>
            @forelse($rows as $i => $row)
                <tr class="{{ $row->alpha > 0 ? 'baris-alpha' : ($i % 2 === 1 ? 'baris-genap' : '') }}">
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row->nama }}</td>
                    <td>{{ $row->nisn }}</td>
                    <td class="angka">{{ $row->hadir }}</td>
                    <td class="angka">{{ $row->izin }}</td>
                    <td class="angka">{{ $row->sakit }}</td>
                    <td class="angka kolom-alpha">{{ $row->alpha }}</td>
                    <td class="angka">{{ $row->total }}</td>
                    <td class="angka">{{ $row->persen_hadir }}%</td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align:center; padding:16px; color:#7c8c86;">Belum ada data siswa di kelas ini.</td></tr>
            @endforelse
        </table>

        <p class="footer-note">
            Laporan ini hanya memuat rekap kehadiran (status Hadir/Izin/Sakit/Alpha) kelas
            {{ $kelas->nama_kelas }} dan tidak menyertakan data dari kelas lain maupun sekolah secara keseluruhan.
            Dihasilkan otomatis oleh SIM BK SMA Kartika I-5 Padang.
        </p>

    </div>

</body>
</html>
