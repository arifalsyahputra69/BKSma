@extends('layouts.guru')

@section('title', 'Pantau Absensi')

@section('content')
    <div class="page-header-banner mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="page-header-icon"><i class="fas fa-clipboard-check"></i></div>
                <div>
                    <h1 class="h4 text-white fw-bold m-0">Pantau Absensi</h1>
                    <p class="text-white-50 mb-0" style="font-size: 0.85rem;">Rekap absensi siswa di kelas binaan Anda</p>
                </div>
            </div>
            @if($kelasId !== '')
                {{-- Unduh HANYA tersedia kalau satu kelas spesifik sudah dipilih
                     di filter (bukan "Semua Kelas Binaan") -- laporan sengaja
                     dibatasi per kelas, bukan gabungan seluruh kelas/sekolah. --}}
                <div class="d-flex gap-2">
                    <a href="{{ route('gurubk.absensi-pantau.pdf', ['kelas_id' => $kelasId, 'semester_id' => $semesterId]) }}" class="btn btn-light btn-sm fw-semibold" data-no-loading>
                        <i class="fas fa-file-pdf text-danger me-1"></i> Unduh PDF
                    </a>
                    <a href="{{ route('gurubk.absensi-pantau.excel', ['kelas_id' => $kelasId, 'semester_id' => $semesterId]) }}" class="btn btn-light btn-sm fw-semibold" data-no-loading>
                        <i class="fas fa-file-excel text-success me-1"></i> Unduh Excel
                    </a>
                </div>
            @endif
        </div>
    </div>

    @if($kelasDiampu->isEmpty())
        <div class="alert alert-warning border-0 shadow-sm rounded-3">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Anda belum ditugaskan sebagai Guru BK untuk kelas manapun. Hubungi Tata Usaha untuk penugasan kelas binaan.
        </div>
    @else
        {{-- ============ FILTER KELAS (dipakai bersama kedua tab) ============ --}}
        <div class="card shadow-sm border-0 rounded-4 mb-3">
            <div class="card-body p-3">
                <form method="GET" action="{{ route('gurubk.absensi-pantau.index') }}" class="row g-3 align-items-end">
                    <input type="hidden" name="tab" id="filterTabInput" value="{{ $tab }}">
                    <input type="hidden" name="tanggal" value="{{ $tanggal->toDateString() }}">
                    <input type="hidden" name="semester_id" value="{{ $semesterId }}">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold text-muted small">Kelas</label>
                        <select name="kelas_id" class="form-select" onchange="this.form.submit()">
                            <option value="" {{ $kelasId === '' ? 'selected' : '' }}>-- Semua Kelas Binaan --</option>
                            @foreach($kelasDiampu as $k)
                                <option value="{{ $k->id }}" {{ (string)$kelasId === (string)$k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                            @endforeach
                        </select>
                        <p class="text-muted small mb-0 mt-1">Tab "Hari Ini" butuh satu kelas spesifik dipilih (bukan "Semua Kelas Binaan").</p>
                    </div>
                </form>
            </div>
        </div>

        {{-- ============ TOGGLE TAB ============ --}}
        <div class="d-flex gap-2 mb-3">
            <button type="button" id="btnTabHariIni" class="btn btn-sm fw-semibold" onclick="simbkSwitchAbsensiTab('hari-ini')">
                <i class="fas fa-calendar-day me-1"></i> Hari Ini
            </button>
            <button type="button" id="btnTabPeriode" class="btn btn-sm fw-semibold" onclick="simbkSwitchAbsensiTab('periode')">
                <i class="fas fa-chart-column me-1"></i> Rekap Periode
            </button>
        </div>

        {{-- =====================================================================
             TAB "HARI INI": grid per siswa x per sesi absensi PADA TANGGAL yang
             dipilih (default hari ini) -- meniru format daftar hadir kertas yang
             biasa dipakai (baris siswa, kolom per jam pelajaran, kolom Ket).
             ===================================================================== --}}
        <div id="tabHariIni">
            @if(! $gridHariIni)
                <div class="alert alert-info border-0 shadow-sm rounded-3 small">
                    <i class="fas fa-circle-info me-2"></i>
                    Pilih salah satu kelas di filter di atas untuk melihat absensi hari ini per sesi.
                </div>
            @else
                <div class="card shadow-sm border-0 rounded-4 mb-3">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <span class="fw-bold">{{ $gridHariIni['kelas']->nama_kelas }}</span>
                            <span class="text-muted small ms-2">{{ $tanggal->translatedFormat('l, d F Y') }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('gurubk.absensi-pantau.index', ['kelas_id' => $kelasId, 'tab' => 'hari-ini', 'tanggal' => $tanggal->copy()->subDay()->toDateString(), 'semester_id' => $semesterId]) }}" class="btn btn-light btn-sm" data-no-loading title="Hari sebelumnya">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <form method="GET" action="{{ route('gurubk.absensi-pantau.index') }}" class="d-flex align-items-center gap-1 m-0">
                                <input type="hidden" name="kelas_id" value="{{ $kelasId }}">
                                <input type="hidden" name="tab" value="hari-ini">
                                <input type="hidden" name="semester_id" value="{{ $semesterId }}">
                                <input type="date" name="tanggal" value="{{ $tanggal->toDateString() }}" class="form-control form-control-sm" style="width:150px;" onchange="this.form.submit()">
                            </form>
                            @unless($tanggal->isToday())
                                <a href="{{ route('gurubk.absensi-pantau.index', ['kelas_id' => $kelasId, 'tab' => 'hari-ini', 'tanggal' => now()->toDateString(), 'semester_id' => $semesterId]) }}" class="btn btn-light btn-sm fw-semibold" data-no-loading>Hari Ini</a>
                            @endunless
                            <a href="{{ route('gurubk.absensi-pantau.index', ['kelas_id' => $kelasId, 'tab' => 'hari-ini', 'tanggal' => $tanggal->copy()->addDay()->toDateString(), 'semester_id' => $semesterId]) }}" class="btn btn-light btn-sm" data-no-loading title="Hari berikutnya">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Kartu ringkasan KHUSUS tanggal yang dipilih (bukan 1 semester) --}}
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="card h-100 shadow-sm rounded-4" style="border: none; border-left: 4px solid #198754;">
                            <div class="card-body p-3">
                                <div class="text-xs fw-bold text-success text-uppercase mb-1">Hadir</div>
                                <div class="h4 mb-0 fw-bold text-dark">{{ $gridHariIni['ringkasan']['Hadir'] }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card h-100 shadow-sm rounded-4" style="border: none; border-left: 4px solid #0dcaf0;">
                            <div class="card-body p-3">
                                <div class="text-xs fw-bold text-uppercase mb-1" style="color:#0dcaf0;">Izin</div>
                                <div class="h4 mb-0 fw-bold text-dark">{{ $gridHariIni['ringkasan']['Izin'] }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card h-100 shadow-sm rounded-4" style="border: none; border-left: 4px solid #ffc107;">
                            <div class="card-body p-3">
                                <div class="text-xs fw-bold text-uppercase mb-1" style="color:#997404;">Sakit</div>
                                <div class="h4 mb-0 fw-bold text-dark">{{ $gridHariIni['ringkasan']['Sakit'] }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card h-100 shadow-sm rounded-4" style="border: none; border-left: 4px solid #dc3545;">
                            <div class="card-body p-3">
                                <div class="text-xs fw-bold text-danger text-uppercase mb-1">Alpha</div>
                                <div class="h4 mb-0 fw-bold text-dark">{{ $gridHariIni['ringkasan']['Alpha'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Grid: baris siswa x kolom sesi hari itu (meniru format kertas) --}}
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-3">
                        @if($gridHariIni['sesiList']->isEmpty())
                            <p class="text-muted small mb-0">Belum ada sesi absensi (QR) yang dibuat Guru Mapel untuk kelas ini pada tanggal tersebut.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 0.82rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="min-width: 160px;">Nama</th>
                                            @foreach($gridHariIni['sesiList'] as $sesi)
                                                <th class="text-center">
                                                    {{ $sesi->mapel }}<br>
                                                    <span class="text-muted fw-normal">jam {{ $sesi->jam_ke }}</span>
                                                </th>
                                            @endforeach
                                            <th class="text-center">Ket</th>
                                            <th class="text-center" style="min-width: 110px;">Bukti</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($gridHariIni['rows'] as $row)
                                            <tr>
                                                <td>{{ $row->nama }}</td>
                                                @foreach($row->status_per_sesi as $status)
                                                    <td class="text-center">
                                                        @switch($status)
                                                            @case('Hadir')
                                                                <i class="fas fa-check text-success"></i>
                                                                @break
                                                            @case('Izin')
                                                                <span class="text-warning fw-bold">I</span>
                                                                @break
                                                            @case('Sakit')
                                                                <span class="text-info fw-bold">S</span>
                                                                @break
                                                            @case('Alpha')
                                                                <span class="text-danger fw-bold">A</span>
                                                                @break
                                                            @default
                                                                <span class="text-muted">&mdash;</span>
                                                        @endswitch
                                                    </td>
                                                @endforeach
                                                <td class="text-center fw-bold {{ $row->ket === 'A' ? 'text-danger' : ($row->ket === 'I' ? 'text-warning' : ($row->ket === 'S' ? 'text-info' : '')) }}">
                                                    {{ $row->ket }}
                                                </td>
                                                <td class="text-center">
                                                    @forelse($row->bukti as $bukti)
                                                        <a href="{{ $bukti->url }}" target="_blank" rel="noopener"
                                                           class="badge bg-primary text-decoration-none mb-1"
                                                           title="{{ $bukti->status }}{{ $bukti->mapel ? ' — '.$bukti->mapel : '' }}{{ $bukti->keterangan ? ' — '.$bukti->keterangan : '' }}">
                                                            <i class="fas {{ $bukti->gambar ? 'fa-image' : 'fa-file-pdf' }} me-1"></i>Lihat
                                                        </a>
                                                    @empty
                                                        <span class="text-muted">&mdash;</span>
                                                    @endforelse
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="{{ $gridHariIni['sesiList']->count() + 3 }}" class="text-muted text-center">Belum ada data siswa di kelas ini.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-muted small mb-0 mt-2">
                                <i class="fas fa-check text-success"></i> hadir &nbsp; I <span class="text-warning fw-bold">izin</span> &nbsp; S <span class="text-info fw-bold">sakit</span> &nbsp; A <span class="text-danger fw-bold">alpha</span> &nbsp; &mdash; belum absen
                            </p>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- =====================================================================
             TAB "REKAP PERIODE": rekap semester (perilaku lama, dipertahankan
             untuk kebutuhan laporan bulanan/semesteran).
             ===================================================================== --}}
        <div id="tabPeriode" style="display: none;">
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('gurubk.absensi-pantau.index') }}" class="row g-3 align-items-end">
                        <input type="hidden" name="kelas_id" value="{{ $kelasId }}">
                        <input type="hidden" name="tab" value="periode">
                        <div class="col-md-5">
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
                    </form>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100 shadow-sm rounded-4" style="border: none; border-left: 4px solid #198754;">
                        <div class="card-body p-4">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Kehadiran</div>
                            <div class="h3 mb-0 fw-bold text-dark">{{ $persenKehadiran }}%</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100 shadow-sm rounded-4" style="border: none; border-left: 4px solid #0dcaf0;">
                        <div class="card-body p-4">
                            <div class="text-xs fw-bold text-uppercase mb-1" style="color:#0dcaf0;">Izin</div>
                            <div class="h3 mb-0 fw-bold text-dark">{{ $rekapAbsensi['Izin'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100 shadow-sm rounded-4" style="border: none; border-left: 4px solid #ffc107;">
                        <div class="card-body p-4">
                            <div class="text-xs fw-bold text-uppercase mb-1" style="color:#997404;">Sakit</div>
                            <div class="h3 mb-0 fw-bold text-dark">{{ $rekapAbsensi['Sakit'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100 shadow-sm rounded-4" style="border: none; border-left: 4px solid #dc3545;">
                        <div class="card-body p-4">
                            <div class="text-xs fw-bold text-danger text-uppercase mb-1">Alpha</div>
                            <div class="h3 mb-0 fw-bold text-dark">{{ $rekapAbsensi['Alpha'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3"><i class="fas fa-triangle-exclamation text-warning me-2"></i>Siswa Perlu Perhatian (Alpha Terbanyak)</h6>
                    @forelse($siswaBermasalah as $sb)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <span class="fw-semibold">{{ $sb->siswa->user->name ?? '(tanpa nama)' }}</span>
                                <span class="text-muted small ms-2">{{ $sb->siswa->kelas->nama_kelas ?? '' }}</span>
                            </div>
                            <span class="badge bg-danger rounded-pill">{{ $sb->jumlah_alpha }}x Alpha</span>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">Tidak ada siswa dengan catatan Alpha pada periode ini.</p>
                    @endforelse
                </div>
            </div>

            {{-- Bukti keterangan Izin/Sakit yang dilampirkan Guru Mapel. --}}
            <div class="card shadow-sm border-0 rounded-4 mt-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-1">
                        <i class="fas fa-paperclip text-primary me-2"></i>Bukti Keterangan Izin / Sakit
                    </h6>
                    <p class="text-muted small mb-3">
                        Surat dokter atau pesan orang tua yang dilampirkan guru mapel saat mencatat
                        Izin/Sakit. Menampilkan maksimal 50 lampiran terbaru pada periode ini.
                    </p>

                    @if($buktiKeterangan->isEmpty())
                        <p class="text-muted small mb-0">Belum ada bukti keterangan yang dilampirkan pada periode ini.</p>
                    @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Mapel / Jam</th>
                                    <th>Status</th>
                                    <th>Keterangan</th>
                                    <th class="text-end">Bukti</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($buktiKeterangan as $bukti)
                                <tr>
                                    <td class="text-nowrap">
                                        {{ $bukti->sesiAbsensi ? \Carbon\Carbon::parse($bukti->sesiAbsensi->tanggal)->format('d-m-Y') : '-' }}
                                    </td>
                                    <td>{{ $bukti->siswa->user->name ?? '(tanpa nama)' }}</td>
                                    <td>{{ $bukti->sesiAbsensi->kelas->nama_kelas ?? '-' }}</td>
                                    <td class="small text-muted">
                                        {{ $bukti->sesiAbsensi->mapel ?? '-' }}
                                        @if($bukti->sesiAbsensi?->jam_ke)
                                            <span class="d-block">{{ $bukti->sesiAbsensi->jam_ke }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $bukti->status === 'Sakit' ? 'bg-warning text-dark' : 'bg-info text-dark' }}">
                                            {{ $bukti->status }}
                                        </span>
                                    </td>
                                    <td class="small">{{ $bukti->keterangan ?: '-' }}</td>
                                    <td class="text-end">
                                        <a href="{{ $bukti->urlBukti() }}" target="_blank" rel="noopener"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas {{ $bukti->buktiBerupaGambar() ? 'fa-image' : 'fa-file-pdf' }} me-1"></i>Lihat
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <script>
            function simbkSwitchAbsensiTab(tab) {
                var hariIni = tab !== 'periode';
                document.getElementById('tabHariIni').style.display = hariIni ? '' : 'none';
                document.getElementById('tabPeriode').style.display = hariIni ? 'none' : '';
                document.getElementById('btnTabHariIni').className = 'btn btn-sm fw-semibold ' + (hariIni ? 'btn-success' : 'btn-light');
                document.getElementById('btnTabPeriode').className = 'btn btn-sm fw-semibold ' + (hariIni ? 'btn-light' : 'btn-success');
                var filterTabInput = document.getElementById('filterTabInput');
                if (filterTabInput) filterTabInput.value = hariIni ? 'hari-ini' : 'periode';
            }
            simbkSwitchAbsensiTab(@json($tab === 'periode' ? 'periode' : 'hari-ini'));
        </script>
    @endif
@endsection
