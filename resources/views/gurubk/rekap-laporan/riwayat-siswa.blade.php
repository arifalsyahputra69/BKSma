@extends('layouts.guru')

@section('title', 'Riwayat Konseling & Pelanggaran Siswa')

@section('content')
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <a href="{{ route('gurubk.rekap.index') }}" class="text-decoration-none text-muted small">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Jurnal BK
            </a>
            <h1 class="h4 mb-0 text-dark fw-bold mt-1">Riwayat Konseling & Pelanggaran Siswa</h1>
        </div>
    </div>

    <!-- Kartu profil singkat siswa -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <img class="rounded-circle" src="https://ui-avatars.com/api/?name={{ urlencode($siswa->user->name ?? 'Siswa') }}&background=0284c7&color=fff" width="56">
                <div>
                    <h5 class="fw-bold mb-0">{{ $siswa->user->name ?? 'Anonim' }}</h5>
                    <div class="text-muted small">
                        {{ $siswa->kelas->nama_kelas ?? '-' }} &bull; NISN: {{ $siswa->nisn ?? '-' }}
                    </div>
                </div>
            </div>
            <div class="d-flex gap-4 text-center flex-wrap">
                <div>
                    <div class="h4 fw-bold mb-0 text-primary">{{ $totalSesi }}</div>
                    <div class="small text-muted">Total Catatan</div>
                </div>
                <div>
                    <div class="h4 fw-bold mb-0 text-warning">{{ $kategoriTerbanyak ?? '-' }}</div>
                    <div class="small text-muted">Kategori Paling Sering</div>
                </div>
                <div>
                    <div class="h4 fw-bold mb-0 {{ $totalPoinPelanggaran > 0 ? 'text-danger' : 'text-muted' }}">{{ $totalPoinPelanggaran }}</div>
                    <div class="small text-muted">Total Poin Pelanggaran ({{ $totalKasusPelanggaran }} kasus)</div>
                </div>
            </div>
        </div>
    </div>

    @if($totalSesi === 0)
        <div class="card shadow-sm border-0">
            <div class="card-body text-center py-5 text-muted">
                Belum ada riwayat konseling untuk siswa ini.
            </div>
        </div>
    @else
        <!-- Timeline riwayat -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                @foreach($riwayat as $item)
                    <div class="d-flex mb-4 {{ !$loop->last ? 'border-bottom pb-4' : '' }}">
                        <div class="me-3 text-center" style="width: 90px;">
                            <div class="fw-bold small text-dark">
                                {{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') : '-' }}
                            </div>
                            <div class="text-muted" style="font-size: 0.72rem;">
                                {{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->translatedFormat('H:i') : '' }}
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                @if($item->jurnal && $item->jurnal->isPelanggaran())
                                    <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning px-2 py-1 rounded-pill" style="font-size: 0.7rem;"><i class="fas fa-triangle-exclamation me-1"></i>Pelanggaran</span>
                                @elseif($item->tipe === 'antrian')
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2 py-1 rounded-pill" style="font-size: 0.7rem;">Antrean Sesi</span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary px-2 py-1 rounded-pill" style="font-size: 0.7rem;">Ditambah Manual</span>
                                @endif

                                @if($item->jurnal && $item->jurnal->isPelanggaran())
                                    <span class="badge {{ $item->jurnal->tingkat_pelanggaran === 'Berat' ? 'bg-danger' : ($item->jurnal->tingkat_pelanggaran === 'Sedang' ? 'bg-warning text-dark' : 'bg-secondary') }} px-2 py-1 rounded-pill" style="font-size: 0.7rem;">
                                        {{ $item->jurnal->tingkat_pelanggaran }} &middot; {{ $item->jurnal->poin }} poin
                                    </span>
                                @elseif($item->jurnal)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1 rounded-pill" style="font-size: 0.7rem;">{{ $item->jurnal->kategori_masalah }}</span>
                                    <span class="badge bg-light text-dark border px-2 py-1 rounded-pill" style="font-size: 0.7rem;">{{ $item->jurnal->status_kasus }}</span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-1 rounded-pill" style="font-size: 0.7rem;">Jurnal Belum Diisi</span>
                                @endif
                            </div>
                            <div class="fw-semibold text-dark small mb-1">
                                {{ $item->jurnal && $item->jurnal->isPelanggaran() ? 'Pencatatan Pelanggaran' : ($item->keperluan ?: '-') }}
                            </div>
                            @if($item->jurnal)
                                <p class="text-muted small mb-0">{{ \Illuminate\Support\Str::limit($item->jurnal->uraian_masalah, 160) }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endsection