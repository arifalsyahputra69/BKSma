@extends('layouts.walikelas')

@section('title', 'Rekap Absensi')

@section('content')
    <div class="mb-4 d-flex align-items-center gap-3">
        <div class="page-icon"><i class="fas fa-file-lines"></i></div>
        <div>
            <h1 class="h4 text-dark fw-bold m-0">Rekap Absensi</h1>
            <p class="text-muted mb-0" style="font-size: 0.85rem;">
                Kehadiran anak didik Anda, siap dicetak atau diunduh
            </p>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4">
            <i class="fas fa-circle-exclamation me-2"></i>{{ session('error') }}
        </div>
    @endif

    @if($kelasDiampu->isEmpty())
        <div class="alert alert-warning border-0 shadow-sm rounded-4 d-flex align-items-center">
            <i class="fas fa-exclamation-triangle fa-lg me-3"></i>
            <div>Anda belum ditugaskan sebagai Wali Kelas untuk kelas manapun. Hubungi Tata Usaha untuk penugasan kelas.</div>
        </div>
    @else
        {{-- ============ FILTER + TOMBOL UNDUH ============ --}}
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-body p-3">
                <form method="GET" action="{{ route('walikelas.rekap-absensi.index') }}" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-muted small">Kelas</label>
                        <select name="kelas_id" class="form-select rounded-3" onchange="this.form.submit()">
                            @foreach($kelasDiampu as $k)
                                <option value="{{ $k->id }}" {{ (string) $kelasId === (string) $k->id ? 'selected' : '' }}>
                                    {{ $k->nama_kelas }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-muted small">Semester</label>
                        <select name="semester_id" class="form-select rounded-3" onchange="this.form.submit()">
                            <option value="">-- Semua Semester --</option>
                            @foreach($semesterList as $s)
                                <option value="{{ $s->id }}" {{ (string) $semesterId === (string) $s->id ? 'selected' : '' }}>
                                    {{ $s->nama }} {{ $s->status_aktif ? '(Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        {{-- Tautan unduh membawa filter yang sedang dipakai, supaya berkas
                             yang keluar berisi persis apa yang terlihat di layar. --}}
                        <div class="d-flex gap-2">
                            <a href="{{ route('walikelas.rekap-absensi.pdf', ['kelas_id' => $kelasId, 'semester_id' => $semesterId]) }}"
                               class="btn btn-danger rounded-3 flex-fill">
                                <i class="fas fa-file-pdf me-1"></i> PDF
                            </a>
                            <a href="{{ route('walikelas.rekap-absensi.excel', ['kelas_id' => $kelasId, 'semester_id' => $semesterId]) }}"
                               class="btn btn-success rounded-3 flex-fill">
                                <i class="fas fa-file-excel me-1"></i> Excel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if($kelasAktif)
            {{-- ============ RINGKASAN ============ --}}
            <div class="row g-3 mb-4">
                @foreach(['Hadir' => 'success', 'Izin' => 'info', 'Sakit' => 'warning', 'Alpha' => 'danger'] as $status => $warna)
                    <div class="col-6 col-lg-3">
                        <div class="card shadow-sm border-0 rounded-4">
                            <div class="card-body p-3">
                                <div class="text-muted small text-uppercase fw-bold">{{ $status }}</div>
                                <div class="h4 fw-bold text-{{ $warna }} mb-0">{{ $ringkasan[$status] }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ============ TABEL REKAP ============ --}}
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h6 class="fw-bold text-dark mb-1">Kelas {{ $kelasAktif->nama_kelas }}</h6>
                            <small class="text-muted">
                                Kehadiran keseluruhan {{ $persenKehadiran }}% &middot; {{ $rows->count() }} siswa
                            </small>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 48px;">No</th>
                                    <th>Nama Siswa</th>
                                    <th>NISN</th>
                                    <th class="text-center">Hadir</th>
                                    <th class="text-center">Izin</th>
                                    <th class="text-center">Sakit</th>
                                    <th class="text-center">Alpha</th>
                                    <th class="text-center">Kehadiran</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $index => $r)
                                <tr>
                                    <td class="text-muted">{{ $index + 1 }}</td>
                                    <td class="fw-semibold">{{ $r->nama }}</td>
                                    <td class="text-muted small">{{ $r->nisn }}</td>
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
                                        @if($r->total === 0)
                                            {{-- Dibedakan dari 0%. Belum ada data absensi bukan
                                                 berarti siswa tidak pernah hadir. --}}
                                            <span class="text-muted small">Belum ada data</span>
                                        @else
                                            <span class="fw-bold {{ $r->persen_hadir >= 75 ? 'text-success' : 'text-danger' }}">
                                                {{ $r->persen_hadir }}%
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        Belum ada data absensi untuk kelas dan periode ini.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Riwayat siswa yang sudah pindah kelas.

                 Sengaja dipisah dari tabel utama dan TIDAK ikut dihitung ke
                 persentase kehadiran kelas -- mereka bukan anak didik wali
                 kelas ini lagi. Tapi juga tidak disembunyikan: rekap kelas
                 tujuan hanya membaca sesi absensi milik kelas tujuan, jadi
                 catatan ini tidak akan muncul di sana. Kalau di sini pun
                 dihilangkan, kehadiran mereka selama masih di kelas ini
                 lenyap dari seluruh halaman. --}}
            @if($rowsPindahan->isNotEmpty())
            <div class="card border-0 shadow-sm rounded-4 mt-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-1">
                        <i class="fas fa-right-left me-2 text-warning"></i>Riwayat Siswa yang Sudah Pindah Kelas
                    </h6>
                    <p class="text-muted small mb-3">
                        Siswa berikut pernah diabsen di kelas ini, tapi sekarang sudah terdaftar di
                        kelas lain. Angkanya tidak ikut dihitung ke persentase kehadiran
                        {{ $kelasAktif->nama_kelas }} di atas.
                    </p>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Siswa</th>
                                    <th>NISN</th>
                                    <th>Sekarang di</th>
                                    <th class="text-center">Hadir</th>
                                    <th class="text-center">Izin</th>
                                    <th class="text-center">Sakit</th>
                                    <th class="text-center">Alpha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rowsPindahan as $r)
                                <tr>
                                    <td class="fw-semibold">{{ $r->nama }}</td>
                                    <td class="text-muted small">{{ $r->nisn }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            {{ $r->kelasSekarang ?? 'Belum berkelas' }}
                                        </span>
                                    </td>
                                    <td class="text-center">{{ $r->hadir }}</td>
                                    <td class="text-center">{{ $r->izin }}</td>
                                    <td class="text-center">{{ $r->sakit }}</td>
                                    <td class="text-center">{{ $r->alpha }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            {{-- Lampiran bukti Izin/Sakit dari guru mapel. --}}
            <div class="card border-0 shadow-sm rounded-4 mt-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-1">
                        <i class="fas fa-paperclip me-2" style="color:#1f4b3f;"></i>Bukti Keterangan Izin / Sakit
                    </h6>
                    <p class="text-muted small mb-3">
                        Surat dokter atau pesan orang tua yang dilampirkan guru mapel saat mencatat
                        Izin/Sakit. Menampilkan maksimal 50 lampiran terbaru.
                    </p>

                    @if($buktiKeterangan->isEmpty())
                        <p class="text-muted small mb-0">
                            Belum ada bukti keterangan yang dilampirkan untuk kelas dan periode ini.
                        </p>
                    @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Nama Siswa</th>
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
        @endif
    @endif
@endsection

@push('scripts')
<style>
:root { --brand: #1f4b3f; }
.page-icon {
    width: 46px; height: 46px; border-radius: 12px;
    background: rgba(31,75,63,0.1); color: var(--brand);
    display: flex; align-items: center; justify-content: center; font-size: 1.15rem;
}
</style>
@endpush
