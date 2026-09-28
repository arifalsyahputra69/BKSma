@extends('layouts.gurumapel')

@section('title', 'Dashboard')

@section('content')

<div class="hero-card card border-0 rounded-4 text-white p-4 mb-4 position-relative overflow-hidden">
    <div class="hero-decor"></div>
    <div class="position-relative">
        <h4 class="fw-bold mb-1">Selamat datang, {{ Auth::user()->name }} 👋</h4>
        <p class="mb-0 opacity-90 small">Ringkasan absensi hari ini &mdash; {{ now()->translatedFormat('l, d F Y') }}</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 stat-mini">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary-soft text-brand"><i class="fas fa-qrcode"></i></div>
                <div>
                    <div class="text-muted small mb-1">Sesi Absensi Hari Ini</div>
                    <div class="fs-3 fw-bold mb-0">{{ $sesiHariIni->count() }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 stat-mini">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-info-soft text-info"><i class="fas fa-calendar-alt"></i></div>
                <div>
                    <div class="text-muted small mb-1">Total Sesi Bulan Ini</div>
                    <div class="fs-3 fw-bold mb-0">{{ $totalSesiBulanIni }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 action-card text-white h-100">
            <div class="small mb-2 opacity-75">Aksi Cepat</div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('gurumapel.absensi.index') }}" class="btn btn-light btn-sm fw-bold rounded-pill">
                    <i class="fas fa-qrcode me-1"></i> Buat QR Absensi
                </a>
                <a href="{{ route('gurumapel.absensi.rekap') }}" class="btn btn-outline-light btn-sm fw-bold rounded-pill">
                    <i class="fas fa-chart-bar me-1"></i> Rekap
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white fw-bold border-0 py-3"><i class="fas fa-list-check text-brand me-2"></i>Sesi Absensi Hari Ini</div>
    <div class="card-body p-0">
        <table class="table mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Kelas</th>
                    <th>Mapel</th>
                    <th>Jam</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($sesiHariIni as $sesi)
                <tr>
                    {{-- PERBAIKAN BUG (5 Agustus 2026): dulu ditulis
                         $sesi->kelas->nama_kelas tanpa pengaman. Kalau kelas
                         yang dirujuk sudah dihapus (mis. kelas percobaan yang
                         dibersihkan, sementara baris sesi absensinya tertinggal),
                         $sesi->kelas bernilai null. Membaca properti dari null
                         memunculkan warning PHP -- dan Laravel MENGUBAH warning
                         jadi ErrorException, sehingga SELURUH halaman dashboard
                         Guru Mapel gagal dengan 500. Satu baris data yatim
                         menjatuhkan seluruh halaman.
                         Sekarang dipakai operator nullsafe: baris tetap tampil
                         dan justru menandai datanya bermasalah. --}}
                    <td class="fw-semibold">{{ $sesi->kelas?->nama_kelas ?? '(kelas dihapus)' }}</td>
                    <td>{{ $sesi->mapel }}</td>
                    <td>{{ $sesi->jam_ke }}</td>
                    <td>
                        <span class="badge {{ $sesi->isExpired() ? 'bg-secondary' : 'bg-success' }} rounded-pill">
                            {{ $sesi->isExpired() ? 'Selesai' : 'Aktif' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('gurumapel.absensi.show', $sesi->id) }}" class="btn btn-sm btn-outline-brand rounded-pill">Lihat</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-5">
                    <i class="fas fa-qrcode fa-2x opacity-25 mb-2 d-block"></i>
                    Belum ada sesi absensi hari ini.
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@section('scripts')
<style>
:root { --brand: #1f4b3f; --brand-dark: #14362d; }
.hero-card { background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%); }
.hero-decor { position: absolute; width: 160px; height: 160px; border-radius: 50%; background: rgba(255,255,255,0.08); right: -40px; top: -60px; }
.text-brand { color: var(--brand) !important; }
.bg-primary-soft { background-color: rgba(31,75,63,0.1) !important; }
.bg-info-soft { background-color: rgba(13,202,240,0.12) !important; }
.btn-outline-brand { border-color: var(--brand); color: var(--brand); }
.btn-outline-brand:hover { background: var(--brand); color: #fff; }
.stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.stat-mini { transition: transform .2s ease, box-shadow .2s ease; }
.stat-mini:hover { transform: translateY(-4px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.08) !important; }
.action-card { background: linear-gradient(135deg,#b45309,#7c3a0a); }
</style>
@endsection