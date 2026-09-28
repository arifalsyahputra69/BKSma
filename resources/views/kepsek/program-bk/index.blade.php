@extends('layouts.kepsek')

@section('title', 'Pengajuan Program BK')

@section('content')

{{-- PERBAIKAN (28 Juli 2026, revisi ke-2) berdasarkan masukan Kepsek:
     1. Tombol "Lihat RPS" dipertegas jadi tombol hijau bersolid, bukan
        teks link kecil yang mudah terlewat.
     2. Kolom "Ditinjau" (nama peninjau) dihapus dari tabel Riwayat --
        cukup Program/Diajukan Oleh/Status, karena menampilkan nama
        peninjau terkesan seolah ada lebih dari satu Kepala Sekolah.
     3. Realisasi tidak lagi manual, kini otomatis mengikuti tanggal
        program (lihat ProgramBk::statusRealisasiLabel()).
     4. Halaman dipercantik: header banner, spacing, & ikon lebih jelas. --}}
<div class="page-header-banner mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="page-header-icon"><i class="fas fa-clipboard-check"></i></div>
        <div>
            <h3 class="fw-bold mb-1 text-white">Pengajuan Program BK</h3>
            <small class="text-white-50">Tinjau, setujui, dan pantau realisasi program kerja Guru BK</small>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3">
    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- ============ MENUNGGU PERSETUJUAN ============ --}}
<div class="card shadow-sm border-0 rounded-4 mb-4">
    <div class="card-body p-4">
        <h6 class="fw-bold text-dark mb-3">
            <i class="fas fa-hourglass-half text-warning me-2"></i>Menunggu Persetujuan
            <span class="badge bg-warning text-dark ms-1">{{ $menunggu->count() }}</span>
        </h6>

        @forelse($menunggu as $p)
        <div class="border rounded-4 p-3 mb-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <div class="fw-bold text-dark">
                        {{ $p->judul }}
                        <span class="badge {{ $p->jenisProgramBadgeClass() }} ms-1">{{ $p->jenis_program }}</span>
                    </div>
                    <div class="text-muted small mb-2">
                        Diajukan oleh <strong>{{ $p->guruBk->name ?? '-' }}</strong> &middot;
                        Sasaran: {{ $p->sasaran }} &middot;
                        {{ $p->tanggal_mulai->format('d M Y') }} &ndash; {{ $p->tanggal_selesai->format('d M Y') }}
                    </div>
                    <p class="small mb-2">{{ $p->deskripsi }}</p>
                    @if($p->rps_file)
                        <a href="{{ asset('storage/' . $p->rps_file) }}" target="_blank" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-file-pdf me-1"></i> Lihat File RPS
                        </a>
                    @else
                        <span class="badge bg-light text-muted border"><i class="fas fa-circle-exclamation me-1"></i>Belum ada file RPS dilampirkan</span>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalSetujui{{ $p->id }}">
                        <i class="fas fa-check me-1"></i> Setujui
                    </button>
                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalTolak{{ $p->id }}">
                        <i class="fas fa-times me-1"></i> Tolak
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal Setujui --}}
        <div class="modal fade" id="modalSetujui{{ $p->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('kepsek.program-bk.approve', $p->id) }}" method="POST" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-success">Setujui "{{ $p->judul }}"?</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label small fw-semibold text-muted">Catatan (opsional)</label>
                        <textarea name="catatan_kepsek" class="form-control" rows="3" placeholder="Mis. arahan tambahan untuk pelaksanaan program"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success px-4">Ya, Setujui</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Tolak --}}
        <div class="modal fade" id="modalTolak{{ $p->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('kepsek.program-bk.reject', $p->id) }}" method="POST" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-danger">Tolak "{{ $p->judul }}"?</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label small fw-semibold text-muted">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="catatan_kepsek" class="form-control" rows="3" required placeholder="Jelaskan alasan penolakan agar Guru BK bisa memperbaiki pengajuan"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger px-4">Ya, Tolak</button>
                    </div>
                </form>
            </div>
        </div>
        @empty
        <p class="text-muted small mb-0">Tidak ada pengajuan yang menunggu persetujuan saat ini.</p>
        @endforelse
    </div>
</div>

{{-- ============ REALISASI PROGRAM YANG SUDAH DISETUJUI (Area Monitoring #1) ============ --}}
<div class="card shadow-sm border-0 rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h6 class="fw-bold text-dark m-0">
                <i class="fas fa-tasks text-primary me-2"></i>Realisasi Program Disetujui
                <span class="badge bg-primary ms-1">{{ $programAktif->count() }}</span>
            </h6>
            @if($totalMangkrak > 0)
            <span class="badge bg-danger">
                <i class="fas fa-exclamation-triangle me-1"></i>{{ $totalMangkrak }} program berpotensi mangkrak
            </span>
            @endif
        </div>
        <p class="text-muted small mb-3">
            Program berstatus <strong>Mangkrak</strong> berarti tanggal selesainya sudah lewat tapi Guru BK belum menandai program tersebut "Selesai".
        </p>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted">
                    <tr>
                        <th class="px-3 py-3">Program</th>
                        <th class="py-3">Guru BK</th>
                        <th class="py-3">Periode</th>
                        <th class="py-3">Realisasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($programAktif as $p)
                    <tr>
                        <td class="px-3 py-3">
                            <div class="fw-semibold text-dark">
                                {{ $p->judul }}
                                <span class="badge {{ $p->jenisProgramBadgeClass() }} ms-1">{{ $p->jenis_program }}</span>
                            </div>
                            <div class="text-muted small">{{ $p->sasaran }}</div>
                        </td>
                        <td class="py-3">{{ $p->guruBk->name ?? '-' }}</td>
                        <td class="py-3 small">{{ $p->tanggal_mulai->format('d M Y') }} &ndash; {{ $p->tanggal_selesai->format('d M Y') }}</td>
                        <td class="py-3">
                            <span class="badge {{ $p->statusRealisasiBadgeClass() }}">{{ $p->statusRealisasiLabel() }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">Belum ada program yang disetujui.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ============ RIWAYAT ============ --}}
<div class="card shadow-sm border-0 rounded-4">
    <div class="card-body p-4 pb-0">
        <h6 class="fw-bold text-dark mb-0">
            <i class="fas fa-clock-rotate-left text-secondary me-2"></i>Riwayat Keputusan
        </h6>
    </div>
    <div class="card-body p-0 pt-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted">
                    <tr>
                        <th class="px-4 py-3">Program</th>
                        <th class="py-3">Diajukan Oleh</th>
                        <th class="py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($riwayat as $r)
                    <tr>
                        <td class="px-4 py-3 fw-semibold text-dark">{{ $r->judul }}</td>
                        <td class="py-3">{{ $r->guruBk->name ?? '-' }}</td>
                        <td class="py-3">
                            @if($r->status === 'Disetujui')
                                <span class="badge bg-success">Disetujui</span>
                            @else
                                <span class="badge bg-danger">Ditolak</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center py-5 text-muted">Belum ada riwayat keputusan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection