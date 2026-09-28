{{-- LETAKKAN DI: resources/views/gurubk/artikel-bk/index.blade.php --}}
@extends('layouts.guru')

@section('title', 'Artikel Informasi BK')

@section('content')

<div class="page-header-banner mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="page-header-icon"><i class="fas fa-newspaper"></i></div>
            <div>
                <h3 class="fw-bold mb-1 text-white">Artikel Informasi BK</h3>
                <small class="text-white-50">Kelola artikel yang bisa dibaca semua siswa di halaman Artikel</small>
            </div>
        </div>
        <button class="btn btn-light fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahArtikel">
            <i class="fas fa-plus-circle me-2"></i> Tulis Artikel
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

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <form method="GET" class="d-flex align-items-center gap-2">
            <label class="text-muted small fw-bold mb-0">Filter Kategori:</label>
            <select name="kategori" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">Semua Kategori</option>
                @foreach($kategoriList as $kat)
                    <option value="{{ $kat }}" @selected(request('kategori') === $kat)>{{ $kat }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>

<div class="row g-4">
    @forelse($artikelList as $artikel)
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            @if($artikel->gambar_sampul)
                <img src="{{ asset('storage/artikel-bk/' . $artikel->gambar_sampul) }}" class="card-img-top" style="height: 160px; object-fit: cover;" alt="{{ $artikel->judul }}" loading="lazy">
            @else
                <div class="d-flex align-items-center justify-content-center bg-light" style="height: 160px;">
                    <i class="fas fa-newspaper fa-2x text-muted opacity-50"></i>
                </div>
            @endif
            <div class="card-body">
                <span class="badge bg-secondary mb-2">{{ $artikel->kategori }}</span>
                @if(!$artikel->status_publish)
                    <span class="badge bg-warning text-dark mb-2">Draft</span>
                @endif
                <h6 class="fw-bold">{{ $artikel->judul }}</h6>
                <p class="text-muted small mb-2">{{ Str::limit($artikel->ringkasan ?: strip_tags($artikel->konten), 90) }}</p>
                <div class="text-muted small mb-3">
                    <i class="fas fa-user-edit me-1"></i>{{ $artikel->guruBk->name ?? '-' }}
                    &middot; {{ $artikel->created_at->translatedFormat('d M Y') }}
                </div>
            </div>
            @if($artikel->guru_bk_id === auth()->id())
            <div class="card-footer bg-white border-0 d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary flex-fill"
                        data-bs-toggle="modal" data-bs-target="#modalEditArtikel{{ $artikel->id }}">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <form action="{{ route('gurubk.artikel-bk.destroy', $artikel->id) }}" method="POST"
                      onsubmit="return confirm('Hapus artikel ini?');" class="flex-fill">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger w-100"><i class="fas fa-trash"></i> Hapus</button>
                </form>
            </div>
            @endif
        </div>
    </div>

    {{-- Modal Edit (khusus artikel milik Guru BK yang login) --}}
    @if($artikel->guru_bk_id === auth()->id())
    <div class="modal fade" id="modalEditArtikel{{ $artikel->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Edit Artikel</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('gurubk.artikel-bk.update', $artikel->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Judul</label>
                            <input type="text" name="judul" class="form-control" value="{{ $artikel->judul }}" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">Kategori</label>
                                <select name="kategori" class="form-select" required>
                                    @foreach($kategoriList as $kat)
                                        <option value="{{ $kat }}" @selected($artikel->kategori === $kat)>{{ $kat }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">Gambar Sampul (opsional)</label>
                                <input type="file" name="gambar_sampul" class="form-control" accept="image/*">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Ringkasan Singkat</label>
                            <textarea name="ringkasan" class="form-control" rows="2" maxlength="500">{{ $artikel->ringkasan }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Konten Artikel</label>
                            <textarea name="konten" class="form-control" rows="8" required>{{ $artikel->konten }}</textarea>
                        </div>
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="status_publish" value="1" id="publish{{ $artikel->id }}" @checked($artikel->status_publish)>
                            <label class="form-check-label small" for="publish{{ $artikel->id }}">Publikasikan ke siswa</label>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success px-4">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    @empty
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center text-muted py-5">
                <i class="fas fa-newspaper fa-3x mb-3 opacity-25 d-block"></i>
                Belum ada artikel. Klik "Tulis Artikel" untuk membuat yang pertama.
            </div>
        </div>
    </div>
    @endforelse
</div>

@if($artikelList->hasPages())
<div class="d-flex justify-content-center mt-4">
    {{ $artikelList->onEachSide(1)->links('pagination::bootstrap-5') }}
</div>
@endif

{{-- Modal Tambah Artikel --}}
<div class="modal fade" id="modalTambahArtikel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-newspaper me-2"></i>Tulis Artikel Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('gurubk.artikel-bk.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Judul</label>
                        <input type="text" name="judul" class="form-control" required placeholder="Contoh: Tips Memilih Jurusan Kuliah">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small fw-bold">Kategori</label>
                            <select name="kategori" class="form-select" required>
                                @foreach($kategoriList as $kat)
                                    <option value="{{ $kat }}">{{ $kat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small fw-bold">Gambar Sampul (opsional)</label>
                            <input type="file" name="gambar_sampul" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Ringkasan Singkat</label>
                        <textarea name="ringkasan" class="form-control" rows="2" maxlength="500" placeholder="Ditampilkan di daftar artikel..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Konten Artikel</label>
                        <textarea name="konten" class="form-control" rows="8" required placeholder="Isi lengkap artikel..."></textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" name="status_publish" value="1" id="publishBaru" checked>
                        <label class="form-check-label small" for="publishBaru">Publikasikan ke siswa</label>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success px-4">Simpan Artikel</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection