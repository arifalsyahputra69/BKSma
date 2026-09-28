{{-- LETAKKAN DI: resources/views/kepsek/kegiatan-bk/index.blade.php --}}
@extends('layouts.kepsek')

@section('title', 'Kalender Kegiatan BK')

@section('content')

<div class="mb-4">
    <h3 class="fw-bold mb-1">Kalender Kegiatan BK</h3>
    <small class="text-muted">Pantau agenda kegiatan seluruh Guru BK -- sosialisasi, penyuluhan, rapat koordinasi, dan bimbingan klasikal</small>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small fw-bold">Kegiatan Mendatang</div>
                <div class="fs-3 fw-bold text-primary">{{ $totalMendatang }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small fw-bold">Sudah Terlaksana</div>
                <div class="fs-3 fw-bold text-success">{{ $totalTerlaksana }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-bold mb-0"><i class="fas fa-calendar-day text-primary me-2"></i>Agenda Mendatang</h6>
    </div>
    <div class="card-body">
        @forelse($kegiatanMendatang as $bulan => $daftar)
            <div class="mb-4">
                <div class="text-uppercase text-muted small fw-bold mb-2">{{ $bulan }}</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light text-muted">
                            <tr>
                                <th class="py-2">Kegiatan</th>
                                <th class="py-2">Kategori</th>
                                <th class="py-2">Guru BK</th>
                                <th class="py-2">Jadwal</th>
                                <th class="py-2">Lokasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($daftar as $k)
                            <tr>
                                <td class="py-2 fw-bold">{{ $k->judul }}</td>
                                <td class="py-2"><span class="badge bg-secondary">{{ $k->kategori }}</span></td>
                                <td class="py-2">{{ optional($k->guruBk)->name ?? '-' }}</td>
                                <td class="py-2 small">{{ $k->tanggal_mulai->translatedFormat('d M Y, H:i') }}</td>
                                <td class="py-2">{{ $k->lokasi ?: '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-5">
                <i class="fas fa-calendar-check fa-3x mb-3 opacity-25 d-block"></i>
                Belum ada kegiatan BK yang dijadwalkan.
            </div>
        @endforelse
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-bold mb-0"><i class="fas fa-history text-muted me-2"></i>Riwayat Kegiatan</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted">
                    <tr>
                        <th class="px-4 py-3">Kegiatan</th>
                        <th class="py-3">Guru BK</th>
                        <th class="py-3">Jadwal</th>
                        <th class="py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kegiatanLalu as $k)
                    <tr>
                        <td class="px-4 py-3">{{ $k->judul }}</td>
                        <td class="py-3">{{ optional($k->guruBk)->name ?? '-' }}</td>
                        <td class="py-3 small">{{ $k->tanggal_mulai->translatedFormat('d M Y, H:i') }}</td>
                        <td class="py-3 text-center">
                            @if($k->status === 'Terlaksana')
                                <span class="badge bg-success">Terlaksana</span>
                            @else
                                <span class="badge bg-danger">Dibatalkan</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada riwayat kegiatan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection