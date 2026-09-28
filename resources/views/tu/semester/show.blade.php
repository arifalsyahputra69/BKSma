@extends('layouts.tu')

@section('title', 'Detail Semester')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 mb-0 text-dark fw-bold">Detail Semester</h1>
            <p class="text-muted mb-0" style="font-size: 0.85rem;">Rincian data periode tahun ajaran</p>
        </div>
        <a href="{{ route('tu.semester.index') }}" class="btn btn-secondary shadow-sm px-3 rounded-pill">
            <i class="fas fa-arrow-left fa-sm me-1"></i> Kembali ke Daftar
        </a>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h6 class="fw-bold text-primary mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Periode</h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <label class="text-muted small text-uppercase fw-bold mb-1">Nama Semester</label>
                        <p class="fw-bold fs-5 mb-0 text-dark">{{ $semester->nama }}</p>
                    </div>
                    <div class="mb-4">
                        <label class="text-muted small text-uppercase fw-bold mb-2">Status Saat Ini</label>
                        <div>
                            @if($semester->status_aktif)
                                <span class="badge bg-success px-3 py-2 rounded-pill"><i class="fas fa-check me-1"></i> Sedang Aktif</span>
                            @else
                                <span class="badge bg-secondary px-3 py-2 rounded-pill"><i class="fas fa-archive me-1"></i> Arsip</span>
                            @endif
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="text-muted small text-uppercase fw-bold mb-1">Tanggal Mulai</label>
                        <p class="mb-0 text-dark">{{ \Carbon\Carbon::parse($semester->tanggal_mulai)->translatedFormat('d F Y') }}</p>
                    </div>
                    <div>
                        <label class="text-muted small text-uppercase fw-bold mb-1">Tanggal Selesai</label>
                        <p class="mb-0 text-dark">{{ \Carbon\Carbon::parse($semester->tanggal_selesai)->translatedFormat('d F Y') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8 mb-4">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h6 class="fw-bold text-success mb-0"><i class="fas fa-chart-pie me-2"></i>Statistik Semester (Segera Hadir)</h6>
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center p-5">
                    <div class="bg-light rounded-circle p-4 mb-3 text-muted" style="width: 100px; height: 100px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-folder-open fa-3x"></i>
                    </div>
                    <h5 class="fw-bold text-dark mt-2">Ruang Rekapitulasi Data</h5>
                    <p class="text-muted mb-0 w-75">Nantinya, di halaman ini Admin TU bisa melihat rekapitulasi total sesi layanan bimbingan konseling dan log aktivitas chatbot siswa yang terjadi secara spesifik pada periode <strong>{{ $semester->nama }}</strong>.</p>
                </div>
            </div>
        </div>
    </div>
@endsection