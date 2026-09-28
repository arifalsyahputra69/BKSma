@extends('layouts.siswa')

@section('title', 'Absensi')

@section('content')

@php
    $config = match($status) {
        'sukses' => ['icon' => 'fa-circle-check', 'color' => 'text-success', 'title' => 'Absensi Berhasil'],
        'sudah' => ['icon' => 'fa-circle-info', 'color' => 'text-info', 'title' => 'Sudah Tercatat'],
        'expired' => ['icon' => 'fa-clock', 'color' => 'text-warning', 'title' => 'Waktu Habis'],
        default => ['icon' => 'fa-circle-xmark', 'color' => 'text-danger', 'title' => 'Tidak Valid'],
    };
@endphp

<div class="d-flex justify-content-center">
    <div class="card border-0 shadow-sm" style="max-width: 480px; width: 100%;">
        <div class="card-body text-center py-5">
            <i class="fas {{ $config['icon'] }} {{ $config['color'] }} mb-3" style="font-size: 3.5rem;"></i>
            <h4 class="fw-bold mb-2">{{ $config['title'] }}</h4>
            <p class="text-muted mb-4">{{ $pesan }}</p>

            @if(isset($sesi))
            <div class="bg-light rounded-3 p-3 text-start mb-4 small">
                <div class="d-flex justify-content-between mb-1"><span class="text-muted">Kelas</span><strong>{{ $sesi->kelas->nama_kelas ?? '-' }}</strong></div>
                <div class="d-flex justify-content-between mb-1"><span class="text-muted">Mapel</span><strong>{{ $sesi->mapel }}</strong></div>
                <div class="d-flex justify-content-between mb-1"><span class="text-muted">Jam</span><strong>{{ $sesi->jam_ke }}</strong></div>
                @if(isset($absen) && $absen->waktu_scan)
                <div class="d-flex justify-content-between"><span class="text-muted">Waktu Scan</span><strong>{{ $absen->waktu_scan->format('H:i:s') }}</strong></div>
                @endif
            </div>
            @endif

            <a href="{{ route('siswa.dashboard') }}" class="btn btn-primary fw-bold">
                <i class="fas fa-home me-2"></i> Kembali ke Beranda
            </a>
        </div>
    </div>
</div>

@endsection
