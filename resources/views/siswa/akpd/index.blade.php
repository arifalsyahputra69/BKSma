{{-- LETAKKAN DI: resources/views/siswa/akpd/index.blade.php --}}
@extends('layouts.siswa')

@section('title', 'AKPD')

@section('content')

<div class="page-header-banner mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="page-header-icon"><i class="fas fa-poll-h"></i></div>
        <div>
            <h3 class="fw-bold mb-1 text-white">Angket Kebutuhan Peserta Didik</h3>
            <small class="text-white-50">Centang masalah yang sedang atau sering kamu alami. Jawabanmu bersifat rahasia dan cuma dilihat Guru BK.</small>
        </div>
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


@if(!$semesterAktif)
    <div class="alert alert-warning">Belum ada semester aktif yang diatur TU, AKPD belum bisa diisi untuk saat ini.</div>
@elseif($itemsByKategori->isEmpty())
    <div class="alert alert-info">Guru BK belum menambahkan pertanyaan AKPD.</div>
@else

@if($sudahMengisi)
<div class="alert alert-success">
    <i class="fas fa-lock me-2"></i>Jawaban AKPD kamu untuk semester <strong>{{ $semesterAktif->nama }}</strong> sudah terkunci
    dan tersimpan. Kamu hanya bisa mengisi AKPD <strong>satu kali</strong> per semester.
</div>
@endif

{{-- PERBAIKAN: kalau $sudahMengisi true, seluruh input dibuat disabled
     (read-only) dan tombol kirim disembunyikan, supaya jawaban yang
     sudah terkunci tidak bisa diubah/dikirim ulang lewat tampilan ini.
     Penguncian sesungguhnya tetap dijaga di server (AkpdController::store)
     supaya tidak bisa dilewati walau atribut disabled ini dihapus manual
     lewat inspect element. --}}
<form action="{{ route('siswa.akpd.store') }}" method="POST">
    @csrf
    @foreach($itemsByKategori as $kategori => $items)
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white fw-bold">
            <i class="fas fa-tag me-2 text-success"></i>{{ $kategori }}
        </div>
        <div class="card-body">
            @foreach($items as $item)
            <div class="d-flex justify-content-between align-items-center border-bottom py-2 flex-wrap gap-2">
                <label class="mb-0 me-2" style="flex: 1 1 300px;">{{ $item->pertanyaan }}</label>
                <div class="btn-group" role="group">
                    <input type="radio" class="btn-check" name="jawaban[{{ $item->id }}]" value="ya"
                           id="ya{{ $item->id }}" required @checked(in_array($item->id, $jawabanSebelumnya)) @disabled($sudahMengisi)>
                    <label class="btn btn-outline-success btn-sm" for="ya{{ $item->id }}">Ya</label>

                    <input type="radio" class="btn-check" name="jawaban[{{ $item->id }}]" value="tidak"
                           id="tidak{{ $item->id }}" required @checked($sudahMengisi && !in_array($item->id, $jawabanSebelumnya)) @disabled($sudahMengisi)>
                    <label class="btn btn-outline-secondary btn-sm" for="tidak{{ $item->id }}">Tidak</label>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach

    @if(!$sudahMengisi)
    <div class="d-grid">
        <button type="submit" class="btn btn-success fw-semibold py-2">
            <i class="fas fa-paper-plane me-2"></i> Kirim Jawaban
        </button>
    </div>
    <p class="text-muted small text-center mt-2">Semester berjalan: <strong>{{ $semesterAktif->nama }}</strong> — jawaban hanya bisa dikirim satu kali dan akan otomatis terkunci setelah itu.</p>
    @endif
</form>
@endif

@endsection