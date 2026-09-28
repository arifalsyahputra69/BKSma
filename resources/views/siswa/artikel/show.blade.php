{{-- LETAKKAN DI: resources/views/siswa/artikel/show.blade.php --}}
@extends('layouts.siswa')

@section('title', $artikel->judul)

@section('content')

<a href="{{ route('siswa.artikel.index') }}" class="btn btn-sm btn-outline-secondary mb-3">
    <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Artikel
</a>

<div class="card shadow-sm border-0">
    @if($artikel->gambar_sampul)
        <img src="{{ asset('storage/artikel-bk/' . $artikel->gambar_sampul) }}" class="card-img-top" style="max-height: 320px; object-fit: cover;" alt="{{ $artikel->judul }}">
    @endif
    <div class="card-body p-4">
        <span class="badge bg-secondary mb-2">{{ $artikel->kategori }}</span>
        <h3 class="fw-bold">{{ $artikel->judul }}</h3>
        <div class="text-muted small mb-4">
            <i class="fas fa-user-edit me-1"></i>{{ $artikel->guruBk->name ?? '-' }}
            &middot; {{ $artikel->created_at->translatedFormat('d M Y') }}
        </div>
        <div class="artikel-konten" style="white-space: pre-line; line-height: 1.8;">
            {{ $artikel->konten }}
        </div>
    </div>
</div>

@endsection