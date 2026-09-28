{{-- LETAKKAN DI: resources/views/kepsek/kasus-darurat/index.blade.php --}}
@extends('layouts.kepsek')

@section('title', 'Kasus Darurat')

@section('content')

<div class="mb-4">
    <h3 class="fw-bold mb-1">Kasus Darurat &ndash; Persetujuan Tindakan Disipliner</h3>
    <small class="text-muted">Usulan Guru BK untuk tindakan disipliner berat (DO/Skorsing). Uraian masalah konseling tetap rahasia dan tidak ditampilkan di sini.</small>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    {{ session('success') }}
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card shadow-sm border-0 mb-4 border-top border-4 border-danger">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-bold mb-0"><i class="fas fa-triangle-exclamation text-danger me-2"></i>Menunggu Persetujuan ({{ $menunggu->count() }})</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted">
                    <tr>
                        <th class="px-4 py-3">Siswa</th>
                        <th class="py-3">Kategori / Tingkat</th>
                        <th class="py-3">Tindakan Diusulkan</th>
                        <th class="py-3">Guru BK</th>
                        <th class="py-3">Tanggal</th>
                        <th class="py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($menunggu as $j)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="fw-bold text-dark">{{ optional($j->siswa->user)->name ?? '-' }}</div>
                            <div class="text-muted small">{{ optional($j->siswa->kelas)->nama_kelas ?? '-' }}</div>
                        </td>
                        <td class="py-3">
                            <div>{{ $j->kategori_masalah }}</div>
                            @if($j->tingkat_pelanggaran)
                                <span class="badge {{ $j->tingkat_pelanggaran === 'Berat' ? 'bg-danger' : 'bg-warning text-dark' }}">Pelanggaran {{ $j->tingkat_pelanggaran }}</span>
                            @endif
                        </td>
                        <td class="py-3"><span class="badge bg-dark">{{ $j->jenis_tindakan_diusulkan }}</span></td>
                        <td class="py-3">{{ optional($j->guruBk)->name ?? '-' }}</td>
                        <td class="py-3 small">{{ \Illuminate\Support\Carbon::parse($j->tanggal_konseling)->translatedFormat('d M Y') }}</td>
                        <td class="py-3 text-center">
                            <button class="btn btn-sm btn-success mb-1" data-bs-toggle="modal" data-bs-target="#modalApprove{{ $j->id }}">
                                <i class="fas fa-check"></i> Setujui
                            </button>
                            <button class="btn btn-sm btn-outline-danger mb-1" data-bs-toggle="modal" data-bs-target="#modalReject{{ $j->id }}">
                                <i class="fas fa-times"></i> Tolak
                            </button>
                        </td>
                    </tr>

                    <div class="modal fade" id="modalApprove{{ $j->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <form action="{{ route('kepsek.kasus-darurat.approve', $j->id) }}" method="POST">
                                    @csrf
                                    <div class="modal-header bg-success text-white">
                                        <h5 class="modal-title fw-bold">Setujui Tindakan {{ $j->jenis_tindakan_diusulkan }}</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <p>Menyetujui usulan <strong>{{ $j->jenis_tindakan_diusulkan }}</strong> untuk <strong>{{ optional($j->siswa->user)->name ?? '-' }}</strong>.</p>
                                        <label class="form-label text-muted small fw-bold">Catatan (opsional)</label>
                                        <textarea name="catatan_kepsek_persetujuan" class="form-control" rows="3" placeholder="Catatan tambahan untuk Guru BK..."></textarea>
                                    </div>
                                    <div class="modal-footer bg-light border-0">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-success px-4">Setujui</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="modalReject{{ $j->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <form action="{{ route('kepsek.kasus-darurat.reject', $j->id) }}" method="POST">
                                    @csrf
                                    <div class="modal-header bg-danger text-white">
                                        <h5 class="modal-title fw-bold">Tolak Usulan {{ $j->jenis_tindakan_diusulkan }}</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <label class="form-label text-muted small fw-bold">Alasan Penolakan (wajib)</label>
                                        <textarea name="catatan_kepsek_persetujuan" class="form-control" rows="3" required placeholder="Jelaskan alasan penolakan..."></textarea>
                                    </div>
                                    <div class="modal-footer bg-light border-0">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-danger px-4">Tolak</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="fas fa-circle-check fa-3x mb-3 opacity-25 d-block"></i>
                            Tidak ada kasus darurat yang menunggu persetujuan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-bold mb-0"><i class="fas fa-history text-muted me-2"></i>Riwayat Keputusan</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted">
                    <tr>
                        <th class="px-4 py-3">Siswa</th>
                        <th class="py-3">Tindakan</th>
                        <th class="py-3">Keputusan</th>
                        <th class="py-3">Ditinjau Oleh</th>
                        <th class="py-3">Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($riwayat as $j)
                    <tr>
                        <td class="px-4 py-3">{{ optional($j->siswa->user)->name ?? '-' }}</td>
                        <td class="py-3">{{ $j->jenis_tindakan_diusulkan }}</td>
                        <td class="py-3">
                            @if($j->status_persetujuan_kepsek === 'Disetujui')
                                <span class="badge bg-success">Disetujui</span>
                            @else
                                <span class="badge bg-danger">Ditolak</span>
                            @endif
                            @if($j->catatan_kepsek_persetujuan)
                                <div class="text-muted small mt-1">{{ \Illuminate\Support\Str::limit($j->catatan_kepsek_persetujuan, 60) }}</div>
                            @endif
                        </td>
                        <td class="py-3">{{ optional($j->disetujuiOleh)->name ?? '-' }}</td>
                        <td class="py-3 small">{{ optional($j->disetujui_at)->translatedFormat('d M Y, H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada riwayat keputusan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
