<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Monitoring BK</title>
    <style>
        /* dompdf tidak mendukung flexbox/grid -- semua layout pakai table & block biasa */
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 18px; margin-bottom: 2px; }
        h2 { font-size: 13px; margin-top: 20px; margin-bottom: 6px; padding-bottom: 3px; border-bottom: 1px solid #999; }
        p.subtitle { font-size: 10px; color: #666; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; font-size: 10px; }
        th { background: #f0f0f0; font-weight: bold; }
        .kartu-ringkasan td { border: none; padding: 6px 10px; }
        .kartu-ringkasan .label { color: #666; font-size: 9px; }
        .kartu-ringkasan .angka { font-size: 15px; font-weight: bold; }
        .catatan { font-size: 9px; color: #666; font-style: italic; margin-top: 4px; }
        .footer-note { font-size: 9px; color: #888; margin-top: 24px; border-top: 1px solid #ccc; padding-top: 6px; }
        .level-Hijau { color: #1a7f37; font-weight: bold; }
        .level-Kuning { color: #9a6700; font-weight: bold; }
        .level-Merah { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>

    <h1>Laporan Monitoring Bimbingan Konseling</h1>
    <p class="subtitle">
        Dicetak {{ now()->translatedFormat('d F Y H:i') }} WIB
        &nbsp;|&nbsp; Semester: {{ optional($semesterList->firstWhere('id', $semesterId))->nama ?? 'Semua Semester' }}
        &nbsp;|&nbsp; Kelas: {{ optional($kelasList->firstWhere('id', $kelasId))->nama_kelas ?? 'Semua Kelas' }}
    </p>

    {{-- ============ RINGKASAN ============ --}}
    <h2>Ringkasan Umum</h2>
    <table class="kartu-ringkasan">
        <tr>
            <td><span class="label">Total Siswa</span><br><span class="angka">{{ $totalSiswa }}</span></td>
            <td><span class="label">Persentase Kehadiran</span><br><span class="angka">{{ $persenKehadiran }}%</span></td>
            <td><span class="label">Total Alpha</span><br><span class="angka">{{ $rekapAbsensi['Alpha'] }}</span></td>
        </tr>
        <tr>
            <td><span class="label">Total Layanan Konseling</span><br><span class="angka">{{ $totalKonseling }}</span></td>
            <td><span class="label">Kasus Masih Terbuka</span><br><span class="angka">{{ $totalKasusTerbuka }}</span></td>
            <td><span class="label">Total Kelas</span><br><span class="angka">{{ $totalKelas }}</span></td>
        </tr>
    </table>

    {{-- ============ AREA 3: PROPORSI JENIS LAYANAN ============ --}}
    <h2>Area 3 &mdash; Proporsi Jenis Layanan BK</h2>
    <table>
        <tr><th>Kategori Masalah</th><th>Jumlah Kasus</th></tr>
        @foreach($proporsiKategoriMasalah as $kategori => $jumlah)
            <tr><td>{{ $kategori }}</td><td>{{ $jumlah }}</td></tr>
        @endforeach
    </table>

    {{-- ============ AREA 5 + PRIORITAS 2: STATUS KASUS & KERAWANAN KELAS ============ --}}
    <h2>Area 5 &mdash; Status Kasus &amp; Peta Kerawanan Kelas</h2>
    <table>
        <tr>
            <th>Kelas</th><th>Hadir</th><th>Alpha</th><th>Konseling</th>
            <th>Kasus Terbuka</th><th>Skor</th><th>Level</th>
        </tr>
        @foreach($rekapPerKelas as $r)
            <tr>
                <td>{{ $r->kelas->nama_kelas ?? '-' }}</td>
                <td>{{ $r->hadir }}</td>
                <td>{{ $r->alpha }}</td>
                <td>{{ $r->konseling }}</td>
                <td>{{ $r->kasusTerbuka }}</td>
                <td>{{ $r->skorKerawanan }}</td>
                <td class="level-{{ $r->levelKerawanan }}">{{ $r->levelKerawanan }}</td>
            </tr>
        @endforeach
    </table>
    <p>Status kasus keseluruhan &mdash; Selesai: {{ $rekapStatusKasus['Selesai'] }},
        Dipantau: {{ $rekapStatusKasus['Dipantau'] }}, Referal: {{ $rekapStatusKasus['Referal'] }}</p>

    {{-- ============ AREA 4: SISWA BERISIKO ============ --}}
    <h2>Area 4 &mdash; Siswa Berisiko / Butuh Perhatian (maks 20)</h2>
    <table>
        <tr>
            <th>Nama</th><th>Kelas</th><th>Alpha</th><th>Kasus Terbuka</th>
            <th>Total Konseling</th><th>Belum Tersentuh BK</th><th>Skor Risiko</th>
        </tr>
        @foreach($siswaBerisiko as $x)
            <tr>
                <td>{{ optional($x->siswa->user)->name ?? '-' }}</td>
                <td>{{ optional($x->siswa->kelas)->nama_kelas ?? '-' }}</td>
                <td>{{ $x->alpha }}</td>
                <td>{{ $x->kasusTerbuka }}</td>
                <td>{{ $x->totalKonseling }}</td>
                <td>{{ $x->belumTersentuhBk ? 'Ya' : 'Tidak' }}</td>
                <td>{{ $x->skorRisiko }}</td>
            </tr>
        @endforeach
    </table>

    <div style="page-break-before: always;"></div>

    {{-- ============ AREA 2: BEBAN GURU BK ============ --}}
    <h2>Area 2 &mdash; Beban Kerja &amp; Pemerataan Guru BK</h2>
    <table>
        <tr><th>Nama Guru BK</th><th>Kelas Diampu</th><th>Siswa Binaan</th><th>Jumlah Sesi</th><th>Melebihi Rasio 1:150</th></tr>
        @foreach($bebanGuruBk as $g)
            <tr>
                <td>{{ $g->nama }}</td>
                <td>{{ $g->jumlahKelas }}</td>
                <td>{{ $g->jumlahSiswaBinaan }}</td>
                <td>{{ $g->jumlahSesi }}</td>
                <td>{{ $g->melebihiRasioIdeal ? 'Ya' : 'Tidak' }}</td>
            </tr>
        @endforeach
    </table>

    {{-- ============ AREA 6: WAKTU TUNGGU ============ --}}
    <h2>Area 6 &mdash; Kepatuhan Waktu Layanan</h2>
    <p>
        Total antrean dipanggil: {{ $totalAntreanDipanggil }} &nbsp;|&nbsp;
        Rata-rata tunggu: {{ $rataRataWaktuTunggu ?? '-' }} menit &nbsp;|&nbsp;
        Terlama: {{ $maksWaktuTunggu ?? '-' }} menit &nbsp;|&nbsp;
        Antrean tunggu lama (&gt;{{ $ambangWaktuTungguLamaMenit }} menit): {{ $totalAntreanTungguLama }}
    </p>
    <table>
        <tr><th>Guru BK</th><th>Jumlah Antrean</th><th>Rata-rata (menit)</th><th>Maks (menit)</th></tr>
        @foreach($waktuTungguPerGuru as $w)
            <tr>
                <td>{{ $namaGuruBkMap[$w->guru_bk_id] ?? ('Guru #'.$w->guru_bk_id) }}</td>
                <td>{{ $w->jumlahAntrean }}</td>
                <td>{{ $w->rataRataMenit }}</td>
                <td>{{ $w->maksMenit }}</td>
            </tr>
        @endforeach
    </table>

    {{-- ============ AREA 7: KOLABORASI ORTU ============ --}}
    <h2>Area 7 &mdash; Kolaborasi Guru BK, Wali Kelas &amp; Orang Tua</h2>
    <p>
        Total kasus libatkan ortu: {{ $totalKasusLibatkanOrtu }} &nbsp;|&nbsp;
        Sudah ada jadwal: {{ $totalSudahAdaJadwal }} &nbsp;|&nbsp;
        Belum ada jadwal: {{ $totalBelumAdaJadwal }}
    </p>
    <table>
        <tr><th>Kelas</th><th>Total Kasus</th><th>Sudah Ada Jadwal</th><th>Belum Ada Jadwal</th></tr>
        @foreach($kolaborasiPerKelas as $k)
            <tr>
                <td>{{ $k->kelas->nama_kelas ?? '-' }}</td>
                <td>{{ $k->totalKasus }}</td>
                <td>{{ $k->sudahAdaJadwal }}</td>
                <td>{{ $k->belumAdaJadwal }}</td>
            </tr>
        @endforeach
    </table>

    <div style="page-break-before: always;"></div>

    {{-- ============ AREA 8a: EVALUASI KEHADIRAN ============ --}}
    <h2>Area 8 &mdash; Evaluasi Kehadiran Sebelum vs Sesudah Konseling Pertama</h2>
    <p>
        Siswa dievaluasi: {{ $totalSiswaDievaluasiKehadiran }} &nbsp;|&nbsp;
        Membaik: {{ $totalMembaikKehadiran }} &nbsp;|&nbsp;
        Memburuk: {{ $totalMemburukKehadiran }} &nbsp;|&nbsp;
        Tetap: {{ $totalTetapKehadiran }} &nbsp;|&nbsp;
        Rata-rata sebelum &rarr; sesudah: {{ $rataRataPersenSebelum ?? '-' }}% &rarr; {{ $rataRataPersenSesudah ?? '-' }}%
    </p>
    <table>
        <tr><th>Nama</th><th>Kelas</th><th>Konseling Pertama</th><th>% Sebelum</th><th>% Sesudah</th><th>Selisih</th></tr>
        @foreach($evaluasiKehadiran as $x)
            <tr>
                <td>{{ optional($x->siswa->user)->name ?? '-' }}</td>
                <td>{{ optional($x->siswa->kelas)->nama_kelas ?? '-' }}</td>
                <td>{{ $x->tanggalPertama->format('d-m-Y') }}</td>
                <td>{{ $x->persenSebelum }}%</td>
                <td>{{ $x->persenSesudah }}%</td>
                <td>{{ $x->selisih > 0 ? '+' : '' }}{{ $x->selisih }}</td>
            </tr>
        @endforeach
    </table>

    {{-- ============ AREA 8b: TREN KASUS BERULANG ============ --}}
    <h2>Area 8 &mdash; Tren Kasus Berulang per Bulan</h2>
    <table>
        <tr><th>Bulan</th><th>Total Kasus</th><th>Kasus Berulang</th><th>% Berulang</th></tr>
        @foreach($trenKasusBulanan as $r)
            <tr>
                <td>{{ $r->bulan }}</td>
                <td>{{ $r->totalKasus }}</td>
                <td>{{ $r->kasusBerulang }}</td>
                <td>{{ $r->persenBerulang }}%</td>
            </tr>
        @endforeach
    </table>

    <p class="footer-note">
        Laporan ini hanya memuat data status, tanggal, dan skor agregat. Uraian masalah dan
        pendekatan/teknik konseling siswa TIDAK disertakan, sesuai prinsip kerahasiaan
        konseling untuk Kepala Sekolah &amp; orang tua.
    </p>

</body>
</html>
