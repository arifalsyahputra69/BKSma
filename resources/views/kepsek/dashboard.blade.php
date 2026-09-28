@extends('layouts.kepsek')

@section('title', 'Dashboard Monitoring')

@section('content')
    <div class="ks-hero card border-0 rounded-4 text-white p-4 mb-4 position-relative overflow-hidden">
        <div class="ks-hero-decor"></div>
        <div class="position-relative d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <span class="badge bg-white bg-opacity-25 rounded-pill px-3 py-2 mb-2 fw-normal">
                    <i class="fas fa-chart-line me-1"></i> {{ now()->translatedFormat('l, d F Y') }}
                </span>
                <h1 class="h3 fw-bold m-0">Dashboard Monitoring</h1>
                <p class="mb-0 opacity-90" style="font-size: 0.9rem;">Ringkasan absensi & layanan konseling seluruh sekolah</p>
            </div>
            {{-- PRIORITAS 7: Unduh laporan. Filter semester/kelas yang sedang aktif
                 ikut dikirim supaya laporan sesuai tampilan dashboard saat ini. --}}
            <div class="btn-group">
                <a href="{{ route('kepsek.laporan.pdf', ['semester_id' => $semesterId, 'kelas_id' => $kelasId]) }}"
                   class="btn btn-light btn-sm rounded-start-3 fw-semibold">
                    <i class="fa-solid fa-file-pdf text-danger"></i> Unduh PDF
                </a>
                <a href="{{ route('kepsek.laporan.excel', ['semester_id' => $semesterId, 'kelas_id' => $kelasId]) }}"
                   class="btn btn-light btn-sm rounded-end-3 fw-semibold">
                    <i class="fa-solid fa-file-excel text-success"></i> Unduh Excel
                </a>
            </div>
        </div>
    </div>

    {{-- ============ AKSES CEPAT ============ --}}
    <div class="ks-quick-row mb-4">
        <a href="{{ route('kepsek.program-bk.index') }}" class="ks-quick-chip">
            <span class="ks-quick-icon"><i class="fas fa-clipboard-list"></i></span> Program BK
        </a>
        <a href="{{ route('kepsek.kunjungan-rumah.index') }}" class="ks-quick-chip">
            <span class="ks-quick-icon"><i class="fas fa-house-chimney"></i></span> Kunjungan Rumah
        </a>
        <a href="{{ route('kepsek.kasus-darurat.index') }}" class="ks-quick-chip">
            <span class="ks-quick-icon"><i class="fas fa-triangle-exclamation"></i></span> Kasus Darurat
        </a>
        <a href="{{ route('kepsek.kegiatan-bk.index') }}" class="ks-quick-chip">
            <span class="ks-quick-icon"><i class="fas fa-calendar-days"></i></span> Kalender Kegiatan BK
        </a>
    </div>

    {{-- ============ FILTER ============ --}}
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('kepsek.dashboard') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold text-muted small">Semester</label>
                    <select name="semester_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Semua Semester --</option>
                        @foreach($semesterList as $s)
                            <option value="{{ $s->id }}" {{ (string)$semesterId === (string)$s->id ? 'selected' : '' }}>
                                {{ $s->nama }} {{ $s->status_aktif ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold text-muted small">Kelas</label>
                    <select name="kelas_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Semua Kelas --</option>
                        @foreach($kelasList as $k)
                            <option value="{{ $k->id }}" {{ (string)$kelasId === (string)$k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('kepsek.dashboard') }}" class="btn btn-outline-secondary rounded-3">
                        <i class="fas fa-rotate me-1"></i> Reset Filter
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- ============ KARTU RINGKASAN ============ --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 shadow-sm border-0 rounded-4 p-4 ks-stat-card" style="border-left: 4px solid #0d6efd !important;">
                <div class="d-flex align-items-center gap-3">
                    <div class="ks-stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-user-graduate"></i></div>
                    <div>
                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Siswa</div>
                        <div class="h3 mb-0 fw-bold text-dark">{{ $totalSiswa }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 shadow-sm border-0 rounded-4 p-4 ks-stat-card" style="border-left: 4px solid #198754 !important;">
                <div class="d-flex align-items-center gap-3">
                    <div class="ks-stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-user-check"></i></div>
                    <div>
                        <div class="text-xs fw-bold text-success text-uppercase mb-1">Persentase Kehadiran</div>
                        <div class="h3 mb-0 fw-bold text-dark">{{ $persenKehadiran }}%</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 shadow-sm border-0 rounded-4 p-4 ks-stat-card" style="border-left: 4px solid #dc3545 !important;">
                <div class="d-flex align-items-center gap-3">
                    <div class="ks-stat-icon bg-danger bg-opacity-10 text-danger"><i class="fas fa-user-xmark"></i></div>
                    <div>
                        <div class="text-xs fw-bold text-danger text-uppercase mb-1">Total Alpha</div>
                        <div class="h3 mb-0 fw-bold text-dark">{{ $rekapAbsensi['Alpha'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 shadow-sm border-0 rounded-4 p-4 ks-stat-card" style="border-left: 4px solid #b45309 !important;">
                <div class="d-flex align-items-center gap-3">
                    <div class="ks-stat-icon" style="background: rgba(180,83,9,0.1); color:#b45309;"><i class="fas fa-comments"></i></div>
                    <div>
                        <div class="text-xs fw-bold text-uppercase mb-1" style="color:#b45309;">Total Layanan Konseling</div>
                        <div class="h3 mb-0 fw-bold text-dark">{{ $totalKonseling }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ PRIORITAS 1: STATUS TINDAK LANJUT KASUS ============ --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 shadow-sm rounded-4" style="border: none; border-left: 4px solid #6f42c1;">
                <div class="card-body p-4">
                    <div class="text-xs fw-bold text-uppercase mb-1" style="color:#6f42c1;">Kasus Terbuka (Perlu Perhatian)</div>
                    <div class="h3 mb-0 fw-bold text-dark">{{ $totalKasusTerbuka }}</div>
                    <div class="small text-muted mt-1">Dipantau: {{ $rekapStatusKasus['Dipantau'] }} · Referal: {{ $rekapStatusKasus['Referal'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-9 col-md-6">
            <div class="card h-100 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3">Status Tindak Lanjut Kasus Konseling</h6>
                    @if($totalKasusTerbuka + $rekapStatusKasus['Selesai'] === 0)
                        <p class="text-muted small mb-0">Belum ada data jurnal layanan untuk filter ini.</p>
                    @else
                        <canvas id="chartStatusKasus" height="70"></canvas>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============ GRAFIK ============ --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3">Tren Kehadiran per Bulan</h6>
                    @if($labelBulan->isEmpty())
                        <p class="text-muted small mb-0">Belum ada data absensi untuk filter ini.</p>
                    @else
                        <canvas id="chartTren" height="120"></canvas>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3">Komposisi Status Kehadiran</h6>
                    <canvas id="chartStatus" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ PRIORITAS 3: PROPORSI JENIS LAYANAN ============ --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3">Proporsi Jenis Layanan (Kategori Masalah)</h6>
                    @if($proporsiKategoriMasalah->sum() === 0)
                        <p class="text-muted small mb-0">Belum ada data jurnal layanan untuk filter ini.</p>
                    @else
                        <canvas id="chartKategoriMasalah" height="200"></canvas>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3">Beban Kerja & Pemerataan Layanan Guru BK</h6>
                    <p class="text-muted small mb-3">Rasio ideal: 1 Guru BK : 150 siswa binaan</p>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="text-muted small">
                                <tr>
                                    <th>Guru BK</th>
                                    <th class="text-center">Kelas Diampu</th>
                                    <th class="text-center">Siswa Binaan</th>
                                    <th class="text-center">Jumlah Sesi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bebanGuruBk as $b)
                                <tr>
                                    <td class="fw-semibold">{{ $b->nama }}</td>
                                    <td class="text-center">{{ $b->jumlahKelas }}</td>
                                    <td class="text-center">
                                        {{ $b->jumlahSiswaBinaan }}
                                        @if($b->melebihiRasioIdeal)
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger ms-1" style="font-size:0.65rem;">di atas rasio ideal</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $b->jumlahSesi }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Belum ada akun Guru BK.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ TABEL REKAP PER KELAS + PETA KERAWANAN ============ --}}
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-0">
            <div class="px-4 pt-4">
                <h6 class="fw-bold text-dark mb-0">Rekap & Peta Kerawanan per Kelas</h6>
                <p class="text-muted small">Skor kerawanan = jumlah Alpha + (kasus terbuka × 2). Hijau = aman, Kuning = perlu dipantau, Merah = perlu perhatian segera.</p>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted">
                        <tr>
                            <th class="px-4 py-3">Kelas</th>
                            <th class="py-3 text-center">Jumlah Siswa</th>
                            <th class="py-3 text-center">Hadir</th>
                            <th class="py-3 text-center">Alpha</th>
                            <th class="py-3 text-center">Layanan Konseling</th>
                            <th class="py-3 text-center">Kasus Terbuka</th>
                            <th class="py-3 text-center">Kerawanan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rekapPerKelas as $r)
                        <tr>
                            <td class="px-4 py-3 fw-bold text-dark">{{ $r->kelas->nama_kelas }}</td>
                            <td class="py-3 text-center">{{ $r->kelas->siswas_count }}</td>
                            <td class="py-3 text-center text-success fw-semibold">{{ $r->hadir }}</td>
                            <td class="py-3 text-center text-danger fw-semibold">{{ $r->alpha }}</td>
                            <td class="py-3 text-center">{{ $r->konseling }}</td>
                            <td class="py-3 text-center">{{ $r->kasusTerbuka }}</td>
                            <td class="py-3 text-center">
                                @if($r->levelKerawanan === 'Hijau')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2 rounded-pill">Hijau</span>
                                @elseif($r->levelKerawanan === 'Kuning')
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-3 py-2 rounded-pill">Kuning</span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 py-2 rounded-pill">Merah</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">Belum ada data kelas.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ============ PRIORITAS 6 / AREA MONITORING #4: SISWA BERISIKO LINTAS KELAS ============ --}}
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-0">
            <div class="px-4 pt-4">
                <h6 class="fw-bold text-dark mb-0">Siswa Berisiko Lintas Kelas</h6>
                <p class="text-muted small">
                    Daftar level individu siswa (bukan agregat kelas) yang perlu perhatian: skor risiko = jumlah Alpha + (kasus terbuka &times; 2), ditambah sinyal siswa dengan Alpha tinggi yang belum pernah ditangani BK sama sekali. Maksimal 20 siswa dengan risiko tertinggi.
                </p>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted">
                        <tr>
                            <th class="px-4 py-3">Siswa</th>
                            <th class="py-3">Kelas</th>
                            <th class="py-3 text-center">Alpha</th>
                            <th class="py-3 text-center">Kasus Terbuka</th>
                            <th class="py-3 text-center">Total Layanan</th>
                            <th class="py-3 text-center">Sinyal</th>
                            <th class="py-3 text-center">Skor Risiko</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($siswaBerisiko as $sb)
                        <tr>
                            <td class="px-4 py-3 fw-semibold text-dark">{{ $sb->siswa->user->name ?? '-' }}</td>
                            <td class="py-3">{{ $sb->siswa->kelas->nama_kelas ?? '-' }}</td>
                            <td class="py-3 text-center text-danger fw-semibold">{{ $sb->alpha }}</td>
                            <td class="py-3 text-center">{{ $sb->kasusTerbuka }}</td>
                            <td class="py-3 text-center">{{ $sb->totalKonseling }}</td>
                            <td class="py-3 text-center">
                                @if($sb->belumTersentuhBk)
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger" style="font-size:0.7rem;">Belum tersentuh BK</span>
                                @elseif($sb->kasusTerbuka > 0)
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning" style="font-size:0.7rem;">Kasus berjalan</span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="py-3 text-center">
                                <span class="badge bg-dark bg-opacity-75 rounded-pill px-3 py-2">{{ $sb->skorRisiko }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">Tidak ada siswa dengan sinyal risiko untuk filter ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ============ AREA MONITORING #6: KEPATUHAN WAKTU LAYANAN ============ --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-4">
            <div class="card h-100 shadow-sm rounded-4" style="border: none; border-left: 4px solid #0dcaf0;">
                <div class="card-body p-4">
                    <div class="text-xs fw-bold text-uppercase mb-1" style="color:#0dcaf0;">Rata-rata Waktu Tunggu</div>
                    <div class="h3 mb-0 fw-bold text-dark">
                        @if(is_null($rataRataWaktuTunggu))
                            <span class="text-muted fs-6">Belum ada data</span>
                        @else
                            {{ $rataRataWaktuTunggu }} <span class="fs-6 text-muted">menit</span>
                        @endif
                    </div>
                    <div class="small text-muted mt-1">Dari {{ $totalAntreanDipanggil }} antrean yang sudah dipanggil</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100 shadow-sm rounded-4" style="border: none; border-left: 4px solid #fd7e14;">
                <div class="card-body p-4">
                    <div class="text-xs fw-bold text-uppercase mb-1" style="color:#fd7e14;">Waktu Tunggu Terlama</div>
                    <div class="h3 mb-0 fw-bold text-dark">
                        @if(is_null($maksWaktuTunggu))
                            <span class="text-muted fs-6">Belum ada data</span>
                        @else
                            {{ $maksWaktuTunggu }} <span class="fs-6 text-muted">menit</span>
                        @endif
                    </div>
                    <div class="small text-muted mt-1">Antrean tunggu paling lama sejauh ini</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100 shadow-sm rounded-4" style="border: none; border-left: 4px solid #dc3545;">
                <div class="card-body p-4">
                    <div class="text-xs fw-bold text-uppercase mb-1" style="color:#dc3545;">Antrean Tunggu &gt; {{ $ambangWaktuTungguLamaMenit }} Menit</div>
                    <div class="h3 mb-0 fw-bold text-dark">{{ $totalAntreanTungguLama }}</div>
                    <div class="small text-muted mt-1">Indikator kecepatan respons layanan BK</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-0">
            <div class="px-4 pt-4">
                <h6 class="fw-bold text-dark mb-0">Waktu Tunggu per Guru BK</h6>
                <p class="text-muted small">Waktu tunggu = waktu antrean dipanggil &minus; waktu booking siswa. Hanya menghitung antrean yang sudah pernah dipanggil Guru BK.</p>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted">
                        <tr>
                            <th class="px-4 py-3">Guru BK</th>
                            <th class="py-3 text-center">Jumlah Antrean Dipanggil</th>
                            <th class="py-3 text-center">Rata-rata Waktu Tunggu</th>
                            <th class="py-3 text-center">Waktu Tunggu Terlama</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($waktuTungguPerGuru as $w)
                        <tr>
                            <td class="px-4 py-3 fw-semibold text-dark">{{ $namaGuruBkMap[$w->guru_bk_id] ?? '-' }}</td>
                            <td class="py-3 text-center">{{ $w->jumlahAntrean }}</td>
                            <td class="py-3 text-center">
                                {{ $w->rataRataMenit }} menit
                                @if($w->rataRataMenit > $ambangWaktuTungguLamaMenit)
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger ms-1" style="font-size:0.65rem;">lambat</span>
                                @endif
                            </td>
                            <td class="py-3 text-center">{{ $w->maksMenit }} menit</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">Belum ada antrean yang pernah dipanggil untuk filter ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ============ AREA MONITORING #7: KOLABORASI GURU BK - WALI KELAS - ORANG TUA ============ --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-4">
            <div class="card h-100 shadow-sm rounded-4" style="border: none; border-left: 4px solid #6f42c1;">
                <div class="card-body p-4">
                    <div class="text-xs fw-bold text-uppercase mb-1" style="color:#6f42c1;">Kasus Butuh Keterlibatan Orang Tua</div>
                    <div class="h3 mb-0 fw-bold text-dark">{{ $totalKasusLibatkanOrtu }}</div>
                    <div class="small text-muted mt-1">Dipanggil Guru BK atau pelanggaran tingkat Berat</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100 shadow-sm rounded-4" style="border: none; border-left: 4px solid #198754;">
                <div class="card-body p-4">
                    <div class="text-xs fw-bold text-success text-uppercase mb-1">Sudah Ada Jadwal Pertemuan</div>
                    <div class="h3 mb-0 fw-bold text-dark">{{ $totalSudahAdaJadwal }}</div>
                    <div class="small text-muted mt-1">Orang tua & Wali Kelas sudah tahu waktu pastinya</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100 shadow-sm rounded-4" style="border: none; border-left: 4px solid #dc3545;">
                <div class="card-body p-4">
                    <div class="text-xs fw-bold text-danger text-uppercase mb-1">Belum Ada Jadwal</div>
                    <div class="h3 mb-0 fw-bold text-dark">{{ $totalBelumAdaJadwal }}</div>
                    <div class="small text-muted mt-1">Perlu ditindaklanjuti Guru BK</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-0">
            <div class="px-4 pt-4">
                <h6 class="fw-bold text-dark mb-0">Kolaborasi Guru BK &ndash; Wali Kelas &ndash; Orang Tua per Kelas</h6>
                <p class="text-muted small">
                    Sejak revisi ini, Wali Kelas ikut mendapat notifikasi in-app setiap kali Guru BK memanggil orang tua atau mencatat pelanggaran tingkat Berat (lihat GuruBK\JurnalLayananController::notifikasiWaliKelas). Detail isi konseling tetap rahasia dan tidak pernah dikirim ke Wali Kelas maupun orang tua.
                </p>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted">
                        <tr>
                            <th class="px-4 py-3">Kelas</th>
                            <th class="py-3">Wali Kelas</th>
                            <th class="py-3 text-center">Kasus Butuh Ortu</th>
                            <th class="py-3 text-center">Sudah Ada Jadwal</th>
                            <th class="py-3 text-center">Belum Ada Jadwal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kolaborasiPerKelas as $k)
                        <tr>
                            <td class="px-4 py-3 fw-bold text-dark">{{ $k->kelas->nama_kelas }}</td>
                            <td class="py-3">
                                {{ optional($k->kelas->waliKelas)->name ?? '-' }}
                                @if(! $k->kelas->wali_kelas_id)
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary ms-1" style="font-size:0.65rem;">belum ada Wali Kelas</span>
                                @endif
                            </td>
                            <td class="py-3 text-center">{{ $k->totalKasus }}</td>
                            <td class="py-3 text-center text-success fw-semibold">{{ $k->sudahAdaJadwal }}</td>
                            <td class="py-3 text-center">
                                @if($k->belumAdaJadwal > 0)
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">{{ $k->belumAdaJadwal }}</span>
                                @else
                                    0
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">Belum ada kasus yang butuh keterlibatan orang tua untuk filter ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ============ PRIORITAS 4: LOG AKTIVITAS GURU BK (TANPA DETAIL RAHASIA) ============ --}}
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-0">
            <div class="px-4 pt-4">
                <h6 class="fw-bold text-dark mb-0">Log Aktivitas Guru BK</h6>
                <p class="text-muted small">Hanya menampilkan tanggal & jumlah entri per hari — detail isi konseling (uraian masalah, pendekatan/teknik) tetap rahasia dan tidak ditampilkan di sini.</p>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted">
                        <tr>
                            <th class="px-4 py-3">Guru BK</th>
                            <th class="py-3">Tanggal</th>
                            <th class="py-3 text-center">Jumlah Entri Hari Itu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logAktivitasGuruBk as $log)
                        <tr>
                            <td class="px-4 py-3 fw-semibold">{{ $namaGuruBkMap[$log->guru_bk_id] ?? '-' }}</td>
                            <td class="py-3">{{ \Illuminate\Support\Carbon::parse($log->tanggal)->translatedFormat('d M Y') }}</td>
                            <td class="py-3 text-center">{{ $log->jumlah_entri }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-5 text-muted">Belum ada aktivitas jurnal layanan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ============ AREA MONITORING #8: EVALUASI HASIL ============ --}}
    <div class="mb-4 mt-2">
        <h5 class="fw-bold text-dark m-0">Evaluasi Hasil (Bukan Cuma Proses)</h5>
        <p class="text-muted small mb-0">Dua indikator sederhana: apakah kehadiran siswa yang sudah dikonseling membaik, dan apakah proporsi kasus berulang menurun dari bulan ke bulan.</p>
    </div>

    {{-- Kartu ringkasan evaluasi kehadiran --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 shadow-sm rounded-4" style="border: none; border-left: 4px solid #0d6efd;">
                <div class="card-body p-4">
                    <div class="text-xs fw-bold text-primary text-uppercase mb-1">Siswa Dievaluasi</div>
                    <div class="h3 mb-0 fw-bold text-dark">{{ $totalSiswaDievaluasiKehadiran }}</div>
                    <div class="small text-muted mt-1">Punya data absensi sebelum & sesudah konseling pertama</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 shadow-sm rounded-4" style="border: none; border-left: 4px solid #198754;">
                <div class="card-body p-4">
                    <div class="text-xs fw-bold text-success text-uppercase mb-1">Kehadiran Membaik</div>
                    <div class="h3 mb-0 fw-bold text-dark">{{ $totalMembaikKehadiran }}</div>
                    <div class="small text-muted mt-1">Tetap: {{ $totalTetapKehadiran }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 shadow-sm rounded-4" style="border: none; border-left: 4px solid #dc3545;">
                <div class="card-body p-4">
                    <div class="text-xs fw-bold text-danger text-uppercase mb-1">Kehadiran Memburuk</div>
                    <div class="h3 mb-0 fw-bold text-dark">{{ $totalMemburukKehadiran }}</div>
                    <div class="small text-muted mt-1">Perlu perhatian lanjutan</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 shadow-sm rounded-4" style="border: none; border-left: 4px solid #6f42c1;">
                <div class="card-body p-4">
                    <div class="text-xs fw-bold text-uppercase mb-1" style="color:#6f42c1;">Rata-rata Kehadiran</div>
                    <div class="h5 mb-0 fw-bold text-dark">
                        @if($rataRataPersenSebelum !== null)
                            {{ $rataRataPersenSebelum }}% &rarr; {{ $rataRataPersenSesudah }}%
                        @else
                            -
                        @endif
                    </div>
                    <div class="small text-muted mt-1">Sebelum &rarr; sesudah konseling pertama</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel evaluasi kehadiran per siswa --}}
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-0">
            <div class="px-4 pt-4">
                <h6 class="fw-bold text-dark mb-0">Kehadiran Sebelum vs Sesudah Konseling Pertama</h6>
                <p class="text-muted small">
                    Dibandingkan 1 bulan sebelum vs 1 bulan sesudah tanggal konseling pertama tiap siswa. Hanya siswa yang punya data absensi di kedua sisi yang ditampilkan. Diurutkan dari yang paling memburuk, supaya jadi perhatian utama.
                </p>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted">
                        <tr>
                            <th class="px-4 py-3">Siswa</th>
                            <th class="py-3">Kelas</th>
                            <th class="py-3">Konseling Pertama</th>
                            <th class="py-3 text-center">Hadir Sebelum</th>
                            <th class="py-3 text-center">Hadir Sesudah</th>
                            <th class="py-3 text-center">Perubahan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($evaluasiKehadiran as $e)
                        <tr>
                            <td class="px-4 py-3 fw-semibold text-dark">{{ $e->siswa->user->name ?? '-' }}</td>
                            <td class="py-3">{{ $e->siswa->kelas->nama_kelas ?? '-' }}</td>
                            <td class="py-3">{{ $e->tanggalPertama->translatedFormat('d M Y') }}</td>
                            <td class="py-3 text-center">{{ $e->persenSebelum }}%</td>
                            <td class="py-3 text-center">{{ $e->persenSesudah }}%</td>
                            <td class="py-3 text-center">
                                @if($e->selisih > 0)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2 rounded-pill">Membaik (+{{ $e->selisih }}%)</span>
                                @elseif($e->selisih < 0)
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 py-2 rounded-pill">Memburuk ({{ $e->selisih }}%)</span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary px-3 py-2 rounded-pill">Tetap</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Belum ada siswa dengan data absensi lengkap di kedua sisi (sebelum & sesudah konseling pertama) untuk filter ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tren kasus berulang per bulan --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-1">Tren Kasus Berulang per Bulan</h6>
                    <p class="text-muted small mb-3">"Berulang" = siswa yang sudah pernah dikonseling sebelumnya, muncul lagi dengan kasus baru. Idealnya persentase ini menurun dari bulan ke bulan.</p>
                    @if($trenKasusBulanan->isEmpty())
                        <p class="text-muted small mb-0">Belum ada data jurnal layanan untuk filter ini.</p>
                    @else
                        <canvas id="chartKasusBerulang" height="180"></canvas>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-body p-0">
                    <div class="px-4 pt-4">
                        <h6 class="fw-bold text-dark mb-0">Rincian per Bulan</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="px-4 py-3">Bulan</th>
                                    <th class="py-3 text-center">Total Kasus</th>
                                    <th class="py-3 text-center">Kasus Berulang</th>
                                    <th class="py-3 text-center">% Berulang</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($trenKasusBulanan as $t)
                                <tr>
                                    <td class="px-4 py-3 fw-semibold">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $t->bulan)->translatedFormat('M Y') }}</td>
                                    <td class="py-3 text-center">{{ $t->totalKasus }}</td>
                                    <td class="py-3 text-center">{{ $t->kasusBerulang }}</td>
                                    <td class="py-3 text-center">{{ $t->persenBerulang }}%</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">Belum ada data jurnal layanan untuk filter ini.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<style>
    :root { --brand: #1f4b3f; --brand-dark: #14362d; }
    .ks-hero { background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%); }
    .ks-hero-decor {
        position: absolute; width: 220px; height: 220px; border-radius: 50%;
        background: rgba(255,255,255,0.08); right: -60px; top: -90px;
    }
    .ks-quick-row { display: flex; flex-wrap: wrap; gap: 0.6rem; }
    .ks-quick-chip {
        display: inline-flex; align-items: center; gap: 0.55rem;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 999px;
        padding: 0.55rem 1.1rem 0.55rem 0.55rem; color: #37473F; font-weight: 600; font-size: 0.85rem;
        text-decoration: none; transition: all 0.18s ease; box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }
    .ks-quick-chip:hover { border-color: var(--brand); background: #F4F8F6; color: var(--brand); transform: translateY(-2px); }
    .ks-quick-icon {
        width: 30px; height: 30px; border-radius: 50%; background: rgba(31,75,63,0.1); color: var(--brand);
        display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0;
    }
    .ks-stat-icon {
        width: 52px; height: 52px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.1rem;
    }
    .ks-stat-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .ks-stat-card:hover { transform: translateY(-4px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1) !important; }
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    @if($labelBulan->isNotEmpty())
    new Chart(document.getElementById('chartTren'), {
        type: 'line',
        data: {
            labels: {!! json_encode($labelBulan) !!},
            datasets: [
                {
                    label: 'Hadir',
                    data: {!! json_encode($dataHadir) !!},
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25,135,84,0.1)',
                    tension: 0.3,
                    fill: true,
                },
                {
                    label: 'Alpha',
                    data: {!! json_encode($dataAlpha) !!},
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220,53,69,0.1)',
                    tension: 0.3,
                    fill: true,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
    @endif

    new Chart(document.getElementById('chartStatus'), {
        type: 'doughnut',
        data: {
            labels: ['Hadir', 'Izin', 'Sakit', 'Alpha'],
            datasets: [{
                data: [
                    {{ $rekapAbsensi['Hadir'] }},
                    {{ $rekapAbsensi['Izin'] }},
                    {{ $rekapAbsensi['Sakit'] }},
                    {{ $rekapAbsensi['Alpha'] }}
                ],
                backgroundColor: ['#198754', '#0dcaf0', '#ffc107', '#dc3545'],
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    @if($totalKasusTerbuka + $rekapStatusKasus['Selesai'] > 0)
    new Chart(document.getElementById('chartStatusKasus'), {
        type: 'bar',
        data: {
            labels: ['Selesai (Tuntas)', 'Dipantau', 'Referal'],
            datasets: [{
                label: 'Jumlah Kasus',
                data: [
                    {{ $rekapStatusKasus['Selesai'] }},
                    {{ $rekapStatusKasus['Dipantau'] }},
                    {{ $rekapStatusKasus['Referal'] }}
                ],
                backgroundColor: ['#198754', '#ffc107', '#dc3545'],
                borderRadius: 6,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
    @endif

    @if($proporsiKategoriMasalah->sum() > 0)
    new Chart(document.getElementById('chartKategoriMasalah'), {
        type: 'pie',
        data: {
            labels: {!! json_encode($proporsiKategoriMasalah->keys()) !!},
            datasets: [{
                data: {!! json_encode($proporsiKategoriMasalah->values()) !!},
                backgroundColor: ['#0d6efd', '#6f42c1', '#fd7e14', '#20c997'],
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
    @endif

    @if($trenKasusBulanan->isNotEmpty())
    new Chart(document.getElementById('chartKasusBerulang'), {
        data: {
            labels: {!! json_encode($trenKasusBulanan->pluck('bulan')->map(fn ($b) => \Illuminate\Support\Carbon::createFromFormat('Y-m', $b)->translatedFormat('M Y'))) !!},
            datasets: [
                {
                    type: 'bar',
                    label: 'Total Kasus',
                    data: {!! json_encode($trenKasusBulanan->pluck('totalKasus')) !!},
                    backgroundColor: 'rgba(13,110,253,0.5)',
                    borderRadius: 6,
                    yAxisID: 'y',
                },
                {
                    type: 'line',
                    label: '% Kasus Berulang',
                    data: {!! json_encode($trenKasusBulanan->pluck('persenBerulang')) !!},
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220,53,69,0.1)',
                    tension: 0.3,
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, position: 'left', title: { display: true, text: 'Jumlah Kasus' } },
                y1: { beginAtZero: true, max: 100, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: '% Berulang' } }
            }
        }
    });
    @endif
</script>
@endpush