@extends('layouts.tu')

@section('title', 'Dashboard Admin')

@section('content')
    {{-- ===== Kotak Selamat Datang ===== --}}
    <div class="card shadow-sm rounded-4 mb-4 border-0 welcome-card">
        <div class="card-body p-4 d-flex align-items-center">
            <div class="me-4 d-none d-sm-block">
                <div class="d-flex align-items-center justify-content-center rounded-circle icon-wrap-brand">
                    <i class="fas fa-info-circle fa-2x"></i>
                </div>
            </div>
            <div>
                <h5 class="fw-bold mb-2 text-dark">Selamat Datang di SIM BK</h5>
                <p class="mb-0 text-muted" style="font-size: 0.95rem; line-height: 1.6;">
                    Ini adalah <strong>Platform Layanan Bimbingan Konseling Untuk Mendukung Guru BK di SMA Kartika I-5 Padang</strong>. <br class="d-none d-lg-block">Anda sedang mengakses panel kendali utama (Tata Usaha / Administrator).
                </p>
            </div>
        </div>
    </div>

    {{-- ===== Statistik Ringkas ===== --}}
    <div class="row g-4">
        <div class="col-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm rounded-4 p-3 stat-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-primary-soft text-brand"><i class="fas fa-user-graduate"></i></div>
                    <div>
                        <p class="text-muted small mb-0">Total Siswa</p>
                        <h5 class="fw-bold mb-0">{{ \App\Models\Siswa::count() }}</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm rounded-4 p-3 stat-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-info-soft text-info"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div>
                        <p class="text-muted small mb-0">Total Kelas</p>
                        <h5 class="fw-bold mb-0">{{ \App\Models\Kelas::count() }}</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm rounded-4 p-3 stat-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-success-soft text-success"><i class="fas fa-users-cog"></i></div>
                    <div>
                        <p class="text-muted small mb-0">Total Pengguna</p>
                        <h5 class="fw-bold mb-0">{{ \App\Models\User::count() }}</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm rounded-4 p-3 stat-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-warning-soft text-warning"><i class="fas fa-calendar-alt"></i></div>
                    <div>
                        <p class="text-muted small mb-0">Semester Aktif</p>
                        <h6 class="fw-bold mb-0">{{ \App\Models\Semester::where('status_aktif', true)->value('nama') ?? '-' }}</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Menu Shortcut ===== --}}
    <div class="row g-4 mt-1">

        <div class="col-xl-3 col-md-6">
            <div class="card h-100 shadow-sm rounded-4 border-0 transition-hover shortcut-card" style="border-left: 4px solid #0d6efd !important;">
                <div class="card-body p-4">
                    <div class="row align-items-center mb-4">
                        <div class="col">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1" style="letter-spacing: 0.5px;">Manajemen Semester</div>
                            <div class="h6 mb-0 fw-bold text-dark">Kelola Aktif & Arsip</div>
                        </div>
                        <div class="col-auto">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-calendar-check fa-lg"></i></div>
                        </div>
                    </div>
                    <a href="{{ route('tu.semester.index') }}" class="btn btn-primary w-100 rounded-pill fw-semibold shadow-sm">Buka</a>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card h-100 shadow-sm rounded-4 border-0 transition-hover shortcut-card" style="border-left: 4px solid #198754 !important;">
                <div class="card-body p-4">
                    <div class="row align-items-center mb-4">
                        <div class="col">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1" style="letter-spacing: 0.5px;">Kelola Pengguna</div>
                            <div class="h6 mb-0 fw-bold text-dark">Guru, Siswa, Kepsek</div>
                        </div>
                        <div class="col-auto">
                            <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-users-cog fa-lg"></i></div>
                        </div>
                    </div>
                    <a href="{{ route('tu.users.index') }}" class="btn btn-success w-100 rounded-pill fw-semibold shadow-sm">Buka</a>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card h-100 shadow-sm rounded-4 border-0 transition-hover shortcut-card" style="border-left: 4px solid #0dcaf0 !important;">
                <div class="card-body p-4">
                    <div class="row align-items-center mb-4">
                        <div class="col">
                            <div class="text-xs fw-bold text-info text-uppercase mb-1" style="letter-spacing: 0.5px;">Kelola Kelas</div>
                            <div class="h6 mb-0 fw-bold text-dark">Atur & Assign Guru BK</div>
                        </div>
                        <div class="col-auto">
                            <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fas fa-chalkboard-teacher fa-lg"></i></div>
                        </div>
                    </div>
                    <a href="{{ route('tu.kelas.index') }}" class="btn btn-info text-white w-100 rounded-pill fw-semibold shadow-sm">Buka</a>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card h-100 shadow-sm rounded-4 border-0 transition-hover shortcut-card" style="border-left: 4px solid #1f4b3f !important;">
                <div class="card-body p-4">
                    <div class="row align-items-center mb-4">
                        <div class="col">
                            <div class="text-xs fw-bold text-brand text-uppercase mb-1" style="letter-spacing: 0.5px;">Informasi Kampus</div>
                            <div class="h6 mb-0 fw-bold text-dark">Data Kampus Tujuan</div>
                        </div>
                        <div class="col-auto">
                            <div class="stat-icon bg-primary-soft text-brand"><i class="fas fa-university fa-lg"></i></div>
                        </div>
                    </div>
                    <a href="{{ route('tu.kampus.index') }}" class="btn btn-brand w-100 rounded-pill fw-semibold shadow-sm text-white">Buka</a>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
<style>
    :root { --brand: #1f4b3f; --brand-dark: #14362d; }
    .welcome-card { border-left: 6px solid var(--brand); }
    .icon-wrap-brand {
        width: 60px; height: 60px;
        background-color: rgba(31, 75, 63, 0.1);
        color: var(--brand);
    }
    .text-brand { color: var(--brand) !important; }
    .bg-primary-soft { background-color: rgba(31,75,63,0.1) !important; }
    .bg-info-soft { background-color: rgba(13,202,240,0.12) !important; }
    .bg-success-soft { background-color: rgba(25,135,84,0.12) !important; }
    .bg-warning-soft { background-color: rgba(255,193,7,0.15) !important; }
    .btn-brand { background-color: var(--brand); border-color: var(--brand); }
    .btn-brand:hover { background-color: var(--brand-dark); border-color: var(--brand-dark); }

    .stat-icon {
        width: 48px; height: 48px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .stat-mini, .transition-hover {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .stat-mini:hover, .transition-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
    }
</style>
@endpush