{{-- LETAKKAN DI: resources/views/kepsek/pantau-bk/index.blade.php --}}
@extends('layouts.kepsek')

@section('title', 'Pantau BK')

@section('content')

<div class="mb-4">
    <h3 class="fw-bold mb-1">Pantau BK</h3>
    <small class="text-muted">Monitoring kegiatan pelayanan konseling BK (Kunjungan Rumah, Layanan Klasikal, Bimbingan Kelompok) lengkap dengan bukti foto/dokumentasi. Isi/kasus siswa (topik, materi, hasil observasi, kesepakatan, daftar anggota) bersifat rahasia dan tidak ditampilkan di sini.</small>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small fw-bold">Kunjungan Rumah</div>
                <div class="fs-3 fw-bold text-warning">{{ $totalHomeVisit }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small fw-bold">Layanan Klasikal</div>
                <div class="fs-3 fw-bold text-info">{{ $totalKlasikal }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small fw-bold">Bimbingan Kelompok</div>
                <div class="fs-3 fw-bold text-success">{{ $totalKelompok }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Daftar Kegiatan</h6>
        <form method="GET" class="d-flex gap-2">
            <select name="jenis" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Semua Jenis Kegiatan</option>
                <option value="Home Visit" @selected(request('jenis') === 'Home Visit')>Kunjungan Rumah</option>
                <option value="Klasikal" @selected(request('jenis') === 'Klasikal')>Layanan Klasikal</option>
                <option value="Kelompok" @selected(request('jenis') === 'Kelompok')>Bimbingan Kelompok</option>
            </select>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted">
                    <tr>
                        <th class="px-4 py-3">Jenis Kegiatan</th>
                        <th class="py-3">Guru BK</th>
                        <th class="py-3">Siswa / Kelas</th>
                        <th class="py-3">Tanggal</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3 text-center">Bukti</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($aktivitas as $akt)
                    <tr>
                        <td class="px-4 py-3">
                            @if($akt->jenis === 'Home Visit')
                                <span class="badge bg-warning text-dark">Kunjungan Rumah</span>
                            @elseif($akt->jenis === 'Klasikal')
                                <span class="badge bg-info text-white">Layanan Klasikal</span>
                            @else
                                <span class="badge bg-success">Bimbingan Kelompok</span>
                            @endif
                        </td>
                        <td class="py-3 fw-semibold">{{ $akt->guruBk }}</td>
                        <td class="py-3">
                            @if($akt->jenis === 'Home Visit')
                                <div class="fw-bold">{{ $akt->siswa ?? '-' }}</div>
                                <div class="text-muted small">{{ $akt->kelas ?? '-' }}</div>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="py-3 small">{{ $akt->tanggal->translatedFormat('d M Y') }}</td>
                        <td class="py-3 text-center">
                            @if($akt->jenis === 'Home Visit')
                                @if($akt->status === 'Direncanakan')
                                    <span class="badge bg-warning text-dark">Direncanakan</span>
                                @elseif($akt->status === 'Terlaksana')
                                    <span class="badge bg-success">Terlaksana</span>
                                @else
                                    <span class="badge bg-danger">Dibatalkan</span>
                                @endif
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="py-3 text-center">
                            @if($akt->dokumentasiPath)
                                <a href="{{ asset('storage/' . $akt->dokumentasiPath) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-paperclip me-1"></i>Lihat File
                                </a>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="fas fa-clipboard-check fa-3x mb-3 opacity-25 d-block"></i>
                            Belum ada kegiatan pelayanan BK yang tercatat.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $aktivitas->links() }}
        </div>
    </div>
</div>

@endsection
