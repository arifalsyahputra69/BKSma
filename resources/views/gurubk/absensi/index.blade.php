@extends('layouts.guru')

@section('title', 'Absensi QR')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">Absensi QR Code</h3>
        <small class="text-muted">Generate QR untuk absensi kelas saat Layanan Klasikal/kegiatan BK di kelas, berlaku 10 menit</small>
    </div>
    <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalGenerateQR">
        <i class="fas fa-qrcode me-2"></i> Generate QR Baru
    </button>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    {{ session('success') }}
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
        <span>Riwayat Sesi Absensi</span>
        <a href="{{ route('gurubk.absensi.rekap') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-chart-bar me-1"></i> Rekap
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Kelas</th>
                    <th>Kegiatan</th>
                    <th>Jam</th>
                    <th>Hadir</th>
                    <th>Izin</th>
                    <th>Sakit</th>
                    <th>Alpha</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($sesis as $sesi)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($sesi->tanggal)->format('d-m-Y') }}</td>
                    <td>{{ $sesi->kelas?->nama_kelas ?? '(kelas dihapus)' }}</td>
                    <td>{{ $sesi->mapel }}</td>
                    <td>{{ $sesi->jam_ke }}</td>
                    <td><span class="badge bg-success">{{ $sesi->hadir_count }}</span></td>
                    <td><span class="badge bg-info text-dark">{{ $sesi->izin_count }}</span></td>
                    <td><span class="badge bg-warning text-dark">{{ $sesi->sakit_count }}</span></td>
                    <td><span class="badge bg-danger">{{ $sesi->alpha_count }}</span></td>
                    <td>
                        <span class="badge {{ $sesi->isExpired() ? 'bg-secondary' : 'bg-success' }}">
                            {{ $sesi->isExpired() ? 'Selesai' : 'Aktif' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('gurubk.absensi.show', $sesi->id) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="text-center text-muted py-4">Belum ada riwayat sesi absensi.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @if($sesis->hasPages())
    <div class="card-footer bg-white">
        {{ $sesis->links() }}
    </div>
    @endif
</div>

<!-- Modal Generate QR -->
<div class="modal fade" id="modalGenerateQR" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('gurubk.absensi.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Generate QR Absensi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Kelas</label>
                    <select name="kelas_id" class="form-select" required>
                        <option value="">-- Pilih Kelas --</option>
                        @foreach($kelas as $k)
                            <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Kegiatan</label>
                    <input type="text" name="mapel" class="form-control" placeholder="Contoh: Layanan Klasikal - Bimbingan Karir" required>
                    <div class="form-text">Diisi bebas, misalnya "Layanan Klasikal" atau nama topik kegiatannya.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Jam Pelajaran</label>
                    <input type="text" name="jam_ke" class="form-control" placeholder="Contoh: Jam ke-3 (08.30 - 09.15)" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="alert alert-light border small mb-0">
                    <i class="fas fa-circle-info me-1"></i>
                    QR akan aktif selama <strong>10 menit</strong>. Siswa yang belum scan sampai
                    batas waktu akan otomatis tercatat <strong>Alpha</strong>.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-bold">Generate QR</button>
            </div>
        </form>
    </div>
</div>

@endsection
