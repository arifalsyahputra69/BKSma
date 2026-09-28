{{-- LETAKKAN DI: resources/views/kepsek/kunjungan-rumah/index.blade.php --}}
@extends('layouts.kepsek')

@section('title', 'Monitoring Kunjungan Rumah')

@section('content')

<div class="mb-4">
    <h3 class="fw-bold mb-1">Monitoring Kunjungan Rumah (Home Visit)</h3>
    <small class="text-muted">Status pelaksanaan kunjungan rumah oleh Guru BK, lengkap dengan bukti kunjungan (foto/surat). Hasil observasi &amp; kesepakatan bersifat rahasia dan tidak ditampilkan di sini.</small>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small fw-bold">Direncanakan</div>
                <div class="fs-3 fw-bold text-warning">{{ $totalDirencanakan }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small fw-bold">Terlaksana</div>
                <div class="fs-3 fw-bold text-success">{{ $totalTerlaksana }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small fw-bold">Dibatalkan</div>
                <div class="fs-3 fw-bold text-danger">{{ $totalDibatalkan }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Daftar Kunjungan Rumah</h6>
        <form method="GET" class="d-flex gap-2">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="Direncanakan" @selected(request('status') === 'Direncanakan')>Direncanakan</option>
                <option value="Terlaksana" @selected(request('status') === 'Terlaksana')>Terlaksana</option>
                <option value="Dibatalkan" @selected(request('status') === 'Dibatalkan')>Dibatalkan</option>
            </select>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted">
                    <tr>
                        <th class="px-4 py-3">Siswa</th>
                        <th class="py-3">Kelas</th>
                        <th class="py-3">Guru BK</th>
                        <th class="py-3">Tanggal Kunjungan</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3 text-center">Bukti Kunjungan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kunjungan as $k)
                    <tr>
                        <td class="px-4 py-3 fw-bold">{{ optional($k->siswa->user)->name ?? '-' }}</td>
                        <td class="py-3">{{ optional($k->siswa->kelas)->nama_kelas ?? '-' }}</td>
                        <td class="py-3">{{ optional($k->guruBk)->name ?? '-' }}</td>
                        <td class="py-3 small">{{ \Illuminate\Support\Carbon::parse($k->tanggal_kunjungan)->translatedFormat('d M Y') }}</td>
                        <td class="py-3 text-center">
                            @if($k->status === 'Direncanakan')
                                <span class="badge bg-warning text-dark">Direncanakan</span>
                            @elseif($k->status === 'Terlaksana')
                                <span class="badge bg-success">Terlaksana</span>
                            @else
                                <span class="badge bg-danger">Dibatalkan</span>
                            @endif
                        </td>
                        <td class="py-3 text-center">
                            @if($k->dokumentasi_path)
                                <a href="{{ asset('storage/' . $k->dokumentasi_path) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
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
                            <i class="fas fa-house-user fa-3x mb-3 opacity-25 d-block"></i>
                            Belum ada data kunjungan rumah.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $kunjungan->links() }}
        </div>
    </div>
</div>

@endsection