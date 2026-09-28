{{-- LETAKKAN DI: resources/views/siswa/artikel/index.blade.php --}}
@extends('layouts.siswa')

@section('title', 'Artikel Informasi BK')

@section('content')

<div class="mb-4">
    <h3 class="fw-bold mb-1">Artikel Informasi BK</h3>
    <small class="text-muted">Bacaan seputar jurusan, karier, belajar, dan pengembangan diri dari Guru BK</small>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <form method="GET" class="d-flex align-items-center gap-2">
            <label class="text-muted small fw-bold mb-0">Kategori:</label>
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
        <a href="{{ route('siswa.artikel.show', $artikel->slug) }}" class="text-decoration-none text-dark">
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
                    <h6 class="fw-bold">{{ $artikel->judul }}</h6>
                    <p class="text-muted small mb-0">{{ Str::limit($artikel->ringkasan ?: strip_tags($artikel->konten), 90) }}</p>
                </div>
            </div>
        </a>
    </div>
    @empty
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center text-muted py-5">
                <i class="fas fa-newspaper fa-3x mb-3 opacity-25 d-block"></i>
                Belum ada artikel yang dipublikasikan.
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

@endsection