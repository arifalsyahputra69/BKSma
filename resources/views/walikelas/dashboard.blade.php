@extends('layouts.walikelas')

@section('title', 'Pantau Kelas Saya')

@section('content')
    <div class="mb-4 d-flex align-items-center gap-3">
        <div class="page-icon"><i class="fas fa-chalkboard-teacher"></i></div>
        <div>
            <h1 class="h4 text-dark fw-bold m-0">Pantau Kelas Saya</h1>
            <p class="text-muted mb-0" style="font-size: 0.85rem;">Absensi & layanan konseling kelas yang Anda ampu</p>
        </div>
    </div>

    @if($kelasDiampu->isEmpty())
        <div class="alert alert-warning border-0 shadow-sm rounded-4 d-flex align-items-center">
            <i class="fas fa-exclamation-triangle fa-lg me-3"></i>
            <div>Anda belum ditugaskan sebagai Wali Kelas untuk kelas manapun. Hubungi Tata Usaha untuk penugasan kelas.</div>
        </div>
    @else
        {{-- ============ FILTER ============ --}}
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-body p-3">
                <form method="GET" action="{{ route('walikelas.dashboard') }}" class="row g-3 align-items-end">
                    @if($kelasDiampu->count() > 1)
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-muted small">Kelas</label>
                        <select name="kelas_id" class="form-select rounded-3" onchange="this.form.submit()">
                            @foreach($kelasDiampu as $k)
                                <option value="{{ $k->id }}" {{ (string)$kelasId === (string)$k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                            @endforeach
                        </select>
                    </div>
                    @else
                        <input type="hidden" name="kelas_id" value="{{ $kelasId }}">
                    @endif
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-muted small">Semester</label>
                        <select name="semester_id" class="form-select rounded-3" onchange="this.form.submit()">
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

        @if($kelasAktif)
            {{-- ============ IDENTITAS PERWALIAN ============
                 Dulu di sini hanya ada lencana bertuliskan nama kelas. Itu tidak
                 menjawab pertanyaan yang paling wajar ditanyakan seorang guru
                 saat membuka halaman ini: saya wali kelas mana, berapa anak
                 didik saya, dan siapa Guru BK yang mendampingi kelas itu. --}}
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="row g-4 align-items-center">
                        <div class="col-md-5">
                            <div class="d-flex align-items-center gap-3">
                                <div class="page-icon" style="width: 54px; height: 54px; font-size: 1.35rem;">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div>
                                    <div class="text-muted small">Anda Wali Kelas dari</div>
                                    <div class="h4 fw-bold text-brand mb-0">{{ $kelasAktif->nama_kelas }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="text-muted small mb-1">
                                <i class="fas fa-users me-1"></i> Jumlah Anak Didik
                            </div>
                            <div class="fw-bold text-dark">
                                {{ $kelasAktif->siswas_count }} siswa
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-muted small mb-1">
                                <i class="fas fa-user-graduate me-1"></i> Guru BK Pendamping
                            </div>
                            <div class="fw-bold text-dark">
                                {{-- Kalau kelas ini sempat kehilangan Guru BK-nya, lebih baik
                                     dikatakan terus terang daripada dibiarkan kosong. --}}
                                {{ $kelasAktif->guruBk->name ?? 'Belum ditetapkan' }}
                            </div>
                        </div>
                    </div>

                    @if($kelasDiampu->count() > 1)
                        <div class="form-text mt-3">
                            <i class="fas fa-circle-info me-1"></i>
                            Anda mewalikelasi {{ $kelasDiampu->count() }} kelas:
                            {{ $kelasDiampu->pluck('nama_kelas')->join(', ', ' dan ') }}.
                            Gunakan penyaring di atas untuk berpindah.
                        </div>
                    @endif
                </div>
            </div>

            {{-- ============ KARTU RINGKASAN ============ --}}
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100 shadow-sm border-0 rounded-4 p-4 stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-user-check"></i></div>
                            <div>
                                <div class="text-xs fw-bold text-success text-uppercase mb-1">Kehadiran</div>
                                <div class="h4 mb-0 fw-bold text-dark">{{ $persenKehadiran }}%</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100 shadow-sm border-0 rounded-4 p-4 stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fas fa-file-signature"></i></div>
                            <div>
                                <div class="text-xs fw-bold text-uppercase mb-1" style="color:#0dcaf0;">Izin</div>
                                <div class="h4 mb-0 fw-bold text-dark">{{ $rekapAbsensi['Izin'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100 shadow-sm border-0 rounded-4 p-4 stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-warning bg-opacity-10" style="color:#997404;"><i class="fas fa-notes-medical"></i></div>
                            <div>
                                <div class="text-xs fw-bold text-uppercase mb-1" style="color:#997404;">Sakit</div>
                                <div class="h4 mb-0 fw-bold text-dark">{{ $rekapAbsensi['Sakit'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100 shadow-sm border-0 rounded-4 p-4 stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="fas fa-user-xmark"></i></div>
                            <div>
                                <div class="text-xs fw-bold text-danger text-uppercase mb-1">Alpha</div>
                                <div class="h4 mb-0 fw-bold text-dark">{{ $rekapAbsensi['Alpha'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                {{-- ============ SISWA PERLU PERHATIAN ============ --}}
                <div class="col-lg-5">
                    <div class="card shadow-sm border-0 rounded-4 h-100">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-dark mb-3"><i class="fas fa-triangle-exclamation text-warning me-2"></i>Siswa Perlu Perhatian (Alpha Terbanyak)</h6>
                            @forelse($siswaBermasalah as $sb)
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom list-row">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-circle">{{ strtoupper(substr($sb->siswa->user->name ?? '?', 0, 1)) }}</div>
                                        <span class="fw-semibold small">{{ $sb->siswa->user->name ?? '(tanpa nama)' }}</span>
                                    </div>
                                    <span class="badge bg-danger rounded-pill">{{ $sb->jumlah_alpha }}x Alpha</span>
                                </div>
                            @empty
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-circle-check fa-2x text-success opacity-50 mb-2 d-block"></i>
                                    <p class="small mb-0">Tidak ada siswa dengan catatan Alpha pada periode ini.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- ============ RIWAYAT KONSELING ============ --}}
                <div class="col-lg-7">
                    <div class="card shadow-sm border-0 rounded-4 h-100">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-dark mb-3"><i class="fas fa-comments text-brand me-2"></i>Riwayat Layanan Konseling Terbaru</h6>
                            @forelse($riwayatKonseling as $rk)
                                <div class="d-flex justify-content-between align-items-start py-2 border-bottom list-row">
                                    <div class="d-flex align-items-start gap-2">
                                        <div class="avatar-circle">{{ strtoupper(substr($rk->siswa->user->name ?? '?', 0, 1)) }}</div>
                                        <div>
                                            <div class="fw-semibold small">{{ $rk->siswa->user->name ?? '(tanpa nama)' }}</div>
                                            <div class="text-muted small">{{ $rk->kategori_masalah }}</div>
                                        </div>
                                    </div>
                                    <span class="text-muted small">{{ optional($rk->tanggal_konseling)->format('d M Y') }}</span>
                                </div>
                            @empty
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-comment-slash fa-2x opacity-50 mb-2 d-block"></i>
                                    <p class="small mb-0">Belum ada riwayat layanan konseling untuk kelas ini.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============ REKAP KEHADIRAN PER SISWA ============
                 Mencakup absensi dari semua guru mapel yang mengajar di kelas
                 ini, bukan hanya sesi yang dibuka wali kelas sendiri. --}}
            <div class="card shadow-sm border-0 rounded-4 mt-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h6 class="fw-bold text-dark mb-1">
                                <i class="fas fa-list-check text-brand me-2"></i>Rekap Kehadiran per Siswa
                            </h6>
                            <small class="text-muted">
                                Seluruh sesi absensi kelas {{ $kelasAktif->nama_kelas }} pada periode terpilih.
                            </small>
                        </div>
                        <span class="badge bg-brand-soft text-brand rounded-pill px-3 py-2">
                            {{ $rekapPerSiswa->count() }} siswa
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 48px;">No</th>
                                    <th>Nama Siswa</th>
                                    <th class="text-center">Hadir</th>
                                    <th class="text-center">Izin</th>
                                    <th class="text-center">Sakit</th>
                                    <th class="text-center">Alpha</th>
                                    <th class="text-center">Kehadiran</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rekapPerSiswa as $index => $r)
                                <tr>
                                    <td class="text-muted">{{ $index + 1 }}</td>
                                    <td class="fw-semibold">
                                        {{ $r->siswa->user->name ?? '(tanpa nama)' }}
                                    </td>
                                    <td class="text-center">{{ $r->hadir }}</td>
                                    <td class="text-center">{{ $r->izin }}</td>
                                    <td class="text-center">{{ $r->sakit }}</td>
                                    <td class="text-center">
                                        @if($r->alpha > 0)
                                            <span class="badge bg-danger rounded-pill">{{ $r->alpha }}</span>
                                        @else
                                            0
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if(is_null($r->persen))
                                            {{-- Sengaja dibedakan dari 0%. Belum ada data absensi
                                                 bukan berarti siswa tidak pernah hadir. --}}
                                            <span class="text-muted small">Belum ada data</span>
                                        @else
                                            <span class="fw-bold {{ $r->persen >= 75 ? 'text-success' : 'text-danger' }}">
                                                {{ $r->persen }}%
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Belum ada siswa terdaftar di kelas ini.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    @endif
@endsection

@push('scripts')
<style>
:root { --brand: #1f4b3f; }
.text-brand { color: var(--brand) !important; }
.bg-brand-soft { background-color: rgba(31,75,63,0.1) !important; }
.page-icon {
    width: 46px; height: 46px; border-radius: 12px;
    background: rgba(31,75,63,0.1); color: var(--brand);
    display: flex; align-items: center; justify-content: center; font-size: 1.15rem;
}
.stat-icon {
    width: 52px; height: 52px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.stat-card { transition: transform .2s ease, box-shadow .2s ease; }
.stat-card:hover { transform: translateY(-4px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.1) !important; }
.avatar-circle {
    width: 32px; height: 32px; border-radius: 50%;
    background: rgba(31,75,63,0.12); color: var(--brand);
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: .8rem; flex-shrink: 0;
}
.list-row:last-child { border-bottom: none !important; }
</style>
@endpush