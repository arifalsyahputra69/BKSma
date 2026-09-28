{{-- LETAKKAN DI: resources/views/siswa/informasi-kampus/index.blade.php --}}
@extends('layouts.siswa')

@section('title', 'Informasi Kampus')

@section('content')

<div class="page-header-banner mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="page-header-icon"><i class="fas fa-university"></i></div>
        <div>
            <h3 class="fw-bold mb-1 text-white">Informasi Kampus & Beasiswa</h3>
            <small class="text-white-50">Referensi kampus, jurusan unggulan, dan jalur beasiswa dari Guru BK/TU. Ada pertanyaan lebih lanjut? Sampaikan langsung ke Guru BK kelasmu.</small>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white fw-bold"><i class="fas fa-university me-2 text-success"></i>Daftar Kampus</div>
    <div class="card-body">
        @if($kampusList->isNotEmpty())
        <div class="row g-3">
            @foreach($kampusList as $kampus)
            <div class="col-md-6 col-lg-4">
                <div class="border rounded-4 h-100 p-3 d-flex gap-3">
                    @if($kampus->logo)
                        <img src="{{ asset('storage/kampus/' . $kampus->logo) }}" alt="{{ $kampus->nama_kampus }}" style="width:48px;height:48px;object-fit:cover;border-radius:8px;flex-shrink:0;">
                    @else
                        <div class="bg-secondary bg-opacity-10 text-secondary rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                            <i class="fas fa-university"></i>
                        </div>
                    @endif
                    <div class="flex-grow-1" style="min-width:0;">
                        <h6 class="fw-bold mb-1">
                            {{ $kampus->nama_kampus }}
                            @if($kampus->link_website)
                                <a href="{{ $kampus->link_website }}" target="_blank" class="text-muted small ms-1"><i class="fas fa-external-link-alt"></i></a>
                            @endif
                        </h6>
                        @if($kampus->deskripsi)
                            <p class="small text-muted mb-1">{{ $kampus->deskripsi }}</p>
                        @endif
                        @if($kampus->jurusan_unggulan)
                            <p class="small mb-0"><span class="fw-semibold">Jurusan unggulan:</span> {{ $kampus->jurusan_unggulan }}</p>
                        @endif
                        @if($kampus->jalur_beasiswa)
                            <p class="small mb-0"><span class="fw-semibold">Jalur beasiswa:</span> {{ $kampus->jalur_beasiswa }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-5 text-muted">
            <i class="fas fa-university fa-3x mb-3 text-light"></i>
            <h5>Belum ada data kampus</h5>
            <p class="mb-0">Guru BK/TU belum menambahkan data kampus. Silakan cek kembali nanti.</p>
        </div>
        @endif
    </div>
</div>

@endsection
