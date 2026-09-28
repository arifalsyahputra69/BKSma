{{-- LETAKKAN DI: resources/views/gurubk/akpd/index.blade.php --}}
@extends('layouts.guru')

@section('title', 'AKPD (Angket Kebutuhan Peserta Didik)')

@section('content')

<div class="page-header-banner mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="page-header-icon"><i class="fas fa-poll-h"></i></div>
            <div>
                <h3 class="fw-bold mb-1 text-white">AKPD</h3>
                <small class="text-white-50">Angket Kebutuhan Peserta Didik — dasar penyusunan Program Tahunan</small>
            </div>
        </div>
        <button class="btn btn-light fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahItem">
            <i class="fas fa-plus-circle me-2"></i> Tambah Pertanyaan
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    {{ session('success') }}
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    {{ session('error') }}
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif


<ul class="nav nav-pills mb-4" id="akpdTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tabKelola" type="button">
            <i class="fas fa-list-check me-1"></i> Kelola Pertanyaan
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabRekap" type="button">
            <i class="fas fa-chart-column me-1"></i> Rekap Hasil
        </button>
    </li>
</ul>

<div class="tab-content">
    {{-- ============ TAB 1: KELOLA PERTANYAAN ============ --}}
    <div class="tab-pane fade show active" id="tabKelola">
        @forelse($kategoriList as $kategori)
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white fw-bold">
                <i class="fas fa-tag me-2 text-success"></i>{{ $kategori }}
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:60px;">Urutan</th>
                            <th>Pertanyaan</th>
                            <th style="width:110px;">Status</th>
                            <th style="width:140px;" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($itemsByKategori[$kategori] ?? []) as $item)
                        <tr>
                            <td>{{ $item->urutan }}</td>
                            <td>{{ $item->pertanyaan }}</td>
                            <td>
                                @if($item->is_active)
                                    <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">Aktif</span>
                                @else
                                    <span class="badge bg-light text-muted border">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-2 justify-content-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditItem{{ $item->id }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('gurubk.akpd.toggle', $item->id) }}" method="POST">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-secondary" title="Aktifkan/Nonaktifkan">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('gurubk.akpd.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus pertanyaan ini?');">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        {{-- Modal Edit --}}
                        <div class="modal fade" id="modalEditItem{{ $item->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <form action="{{ route('gurubk.akpd.update', $item->id) }}" method="POST" class="modal-content">
                                    @csrf @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Pertanyaan AKPD</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Kategori</label>
                                            <select name="kategori" class="form-select" required>
                                                @foreach($kategoriList as $k)
                                                    <option value="{{ $k }}" @selected($item->kategori === $k)>{{ $k }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Pertanyaan</label>
                                            <textarea name="pertanyaan" class="form-control" rows="3" required>{{ $item->pertanyaan }}</textarea>
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-label fw-semibold">Urutan Tampil</label>
                                            <input type="number" name="urutan" class="form-control" min="0" value="{{ $item->urutan }}">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-success">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">Belum ada pertanyaan pada kategori ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ============ TAB 2: REKAP HASIL ============ --}}
    <div class="tab-pane fade" id="tabRekap">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body py-3">
                <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
                    <label class="text-muted small fw-bold mb-0">Semester:</label>
                    <select name="semester_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                        @foreach($semesterList as $s)
                            <option value="{{ $s->id }}" @selected((string) $semesterId === (string) $s->id)>{{ $s->nama }}</option>
                        @endforeach
                    </select>
                    <span class="text-muted small ms-2">
                        <i class="fas fa-user-graduate me-1"></i>{{ $totalSiswaMengisi }} siswa binaan sudah mengisi
                    </span>
                </form>
            </div>
        </div>

        @forelse($rekapByKategori as $kategori => $items)
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white fw-bold">
                <i class="fas fa-tag me-2 text-success"></i>{{ $kategori }}
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Masalah / Pertanyaan</th>
                            <th style="width:160px;">Jumlah Siswa Mengalami</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $row)
                        <tr>
                            <td>{{ $row['pertanyaan'] }}</td>
                            <td>
                                <span class="badge {{ $row['jumlah'] > 0 ? 'bg-danger-subtle text-danger-emphasis border border-danger-subtle' : 'bg-light text-muted border' }}">
                                    {{ $row['jumlah'] }} siswa
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="text-center text-muted py-3">Belum ada pertanyaan aktif.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @empty
        <div class="text-center text-muted py-5">Belum ada data untuk semester ini.</div>
        @endforelse
    </div>
</div>

{{-- Modal Tambah Pertanyaan --}}
<div class="modal fade" id="modalTambahItem" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('gurubk.akpd.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Tambah Pertanyaan AKPD</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Kategori</label>
                    <select name="kategori" class="form-select" required>
                        <option value="" disabled selected>Pilih kategori</option>
                        @foreach($kategoriList as $k)
                            <option value="{{ $k }}">{{ $k }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pertanyaan</label>
                    <textarea name="pertanyaan" class="form-control" rows="3" placeholder="Contoh: Saya sering merasa cemas menghadapi ujian" required></textarea>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold">Urutan Tampil</label>
                    <input type="number" name="urutan" class="form-control" min="0" placeholder="Otomatis (nomor berikutnya)">
                    <small class="text-muted">Kosongkan supaya sistem otomatis melanjutkan nomor urut terakhir. Isi manual kalau ingin pertanyaan ini tampil di posisi tertentu.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success">Simpan</button>
            </div>
        </form>
    </div>
</div>

@endsection