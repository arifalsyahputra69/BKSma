@extends('layouts.guru')

@section('title', 'Alur Chatbot & Pilihan Jurusan')

@section('content')
<div class="page-header-banner mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="page-header-icon"><i class="fas fa-project-diagram"></i></div>
        <div>
            <h3 class="fw-bold mb-1 text-white">Alur Karir</h3>
            <small class="text-white-50">Susun alur pertanyaan chatbot untuk membantu siswa menemukan arah jurusan/karir</small>
        </div>
    </div>
</div>
{{-- Halaman ini sebelumnya tidak pernah menampilkan pesan hasil aksi, padahal
     controllernya sudah mengirimkannya sejak awal. Sekarang lebih penting lagi
     karena ada aksi ubah dan hapus per pilihan. --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3">
        <i class="fas fa-circle-check me-2"></i>{{ session('success') }}
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-3">
        <i class="fas fa-circle-exclamation me-2"></i>{{ session('error') }}
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-4 bg-white p-4">
            <h5 class="fw-bold mb-3">Tambah Pertanyaan Baru</h5>
            <form action="{{ route('gurubk.chatbot.alur.store') }}" method="POST" class="d-flex gap-2">
                @csrf
                <input type="text" name="pertanyaan" class="form-control" placeholder="Contoh: Apa minat utama kamu setelah lulus SMA?" required>
                <button type="submit" class="btn btn-primary px-4">Simpan Pertanyaan</button>
            </form>
        </div>
    </div>
</div>

<div class="row g-4">
    @forelse($alurs as $alur)
    <div class="col-md-6">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-header bg-primary bg-opacity-10 border-0 p-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-primary m-0"><i class="fas fa-question-circle me-2"></i> {{ $alur->pertanyaan }}</h6>
                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                    <button class="btn btn-sm text-primary border-0"
                            data-bs-toggle="modal"
                            data-bs-target="#modalEditAlur{{ $alur->id }}"
                            title="Ubah pertanyaan">
                        <i class="fas fa-pen"></i>
                    </button>
                    <form action="{{ route('gurubk.chatbot.alur.destroy', $alur->id) }}" method="POST" onsubmit="return confirm('Hapus pertanyaan ini beserta semua {{ $alur->pilihan->count() }} pilihannya?');">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm text-danger border-0" title="Hapus pertanyaan"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
            
            <div class="card-body p-3">
                <p class="small text-muted mb-2 fw-semibold">Tombol Pilihan Jawaban Siswa:</p>
                @if($alur->pilihan->count() > 0)
                    <ul class="list-group list-group-flush mb-3">
                        @foreach($alur->pilihan as $pilihan)
                        <li class="list-group-item px-0 py-2 border-bottom-dashed">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="fw-bold text-dark small"><span class="badge bg-secondary me-1">Tombol</span> {{ $pilihan->teks_pilihan }}</div>
                                <div class="d-flex gap-1 flex-shrink-0">
                                    <button class="btn btn-sm text-primary border-0 p-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEditPilihan{{ $pilihan->id }}"
                                            title="Ubah pilihan ini">
                                        <i class="fas fa-pen small"></i>
                                    </button>
                                    <form action="{{ route('gurubk.chatbot.pilihan.destroy', $pilihan->id) }}" method="POST" onsubmit="return confirm('Hapus pilihan ini saja? Pertanyaan induknya tetap ada.');">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm text-danger border-0 p-1" title="Hapus pilihan ini">
                                            <i class="fas fa-trash small"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="text-muted small mt-1"><i class="fas fa-reply me-1"></i> Respons: {{ Str::limit($pilihan->respons, 60) }}</div>
                            @if($pilihan->kampus)
                            <div class="text-success small mt-1"><i class="fas fa-university me-1"></i> Rekomendasi Kampus: <strong>{{ $pilihan->kampus->nama_kampus }}</strong></div>
                            @elseif($pilihan->tag_kampus)
                            <div class="text-info small mt-1"><i class="fas fa-university me-1"></i> Tag Kampus: {{ $pilihan->tag_kampus }}</div>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                @else
                    <div class="alert alert-light text-center small py-2 mb-3 border">Belum ada tombol pilihan.</div>
                @endif
                
                <hr>
                <form action="{{ route('gurubk.chatbot.pilihan.store', $alur->id) }}" method="POST">
                    @csrf
                    <div class="mb-2">
                        <input type="text" name="teks_pilihan" class="form-control form-control-sm" placeholder="Teks Tombol (Misal: Suka Menghitung)" required>
                    </div>
                    <div class="mb-2">
                        <textarea name="respons" class="form-control form-control-sm" rows="2" placeholder="Jawaban Chatbot jika tombol ini diklik..." required></textarea>
                    </div>
                    <div class="mb-2">
                        <select name="kampus_id" class="form-select form-select-sm">
                            <option value="">Rekomendasikan Kampus (opsional, dari Kelola Informasi Kampus)</option>
                            @foreach($kampusList as $kampus)
                                <option value="{{ $kampus->id }}">{{ $kampus->nama_kampus }}</option>
                            @endforeach
                        </select>
                        @if($kampusList->isEmpty())
                            <div class="form-text">Belum ada data kampus di menu "Kelola Informasi Kampus".</div>
                        @endif
                    </div>
                    <div class="mb-2">
                        <input type="text" name="tag_kampus" class="form-control form-control-sm" placeholder="Atau ketik nama kampus manual (kalau belum ada di data master)">
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-plus"></i> Tambah Pilihan</button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12 text-center text-muted py-5">
        <i class="fas fa-project-diagram fa-3x mb-3 opacity-25"></i>
        <p>Belum ada alur chatbot. Silakan buat pertanyaan pertama di atas.</p>
    </div>
    @endforelse
</div>

{{-- ============================================================
     MODAL UBAH
     Sengaja ditaruh di luar grid, bukan di dalam kartu masing-masing.
     Kartu memakai rounded-4 dan overflow tersembunyi, sehingga modal yang
     bersarang di dalamnya bisa ikut terpotong atau tertimpa kartu sebelah.
     ============================================================ --}}
@foreach($alurs as $alurEdit)

    {{-- Ubah teks pertanyaan utama --}}
    <div class="modal fade" id="modalEditAlur{{ $alurEdit->id }}" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('gurubk.chatbot.alur.update', $alurEdit->id) }}" method="POST" class="modal-content">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Pertanyaan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small fw-bold">Pertanyaan</label>
                    <input type="text" name="pertanyaan" class="form-control"
                           value="{{ $alurEdit->pertanyaan }}" required>
                    <small class="text-muted" style="font-size: 11px;">
                        Mengubah pertanyaan tidak memengaruhi tombol pilihan di bawahnya.
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Ubah tiap tombol pilihan milik pertanyaan ini --}}
    @foreach($alurEdit->pilihan as $pilihanEdit)
    <div class="modal fade" id="modalEditPilihan{{ $pilihanEdit->id }}" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('gurubk.chatbot.pilihan.update', $pilihanEdit->id) }}" method="POST" class="modal-content">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Pilihan Jawaban</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border small py-2">
                        Pertanyaan induk: <strong>{{ $alurEdit->pertanyaan }}</strong>
                    </div>

                    <label class="form-label small fw-bold">Teks Tombol</label>
                    <input type="text" name="teks_pilihan" class="form-control mb-3"
                           value="{{ $pilihanEdit->teks_pilihan }}" required>

                    <label class="form-label small fw-bold">Respons Chatbot</label>
                    <textarea name="respons" class="form-control mb-3" rows="3" required>{{ $pilihanEdit->respons }}</textarea>

                    <label class="form-label small fw-bold">Rekomendasi Kampus (Opsional)</label>
                    <select name="kampus_id" class="form-select mb-3">
                        <option value="">-- Tidak merekomendasikan kampus --</option>
                        @foreach($kampusList as $kampus)
                            <option value="{{ $kampus->id }}" @selected($pilihanEdit->kampus_id == $kampus->id)>
                                {{ $kampus->nama_kampus }}
                            </option>
                        @endforeach
                    </select>

                    <label class="form-label small fw-bold">Tag Kampus Manual (Opsional)</label>
                    <input type="text" name="tag_kampus" class="form-control"
                           value="{{ $pilihanEdit->tag_kampus }}"
                           placeholder="Dipakai kalau kampusnya belum ada di data master">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    @endforeach

@endforeach

<style>
    .border-bottom-dashed { border-bottom: 1px dashed #e5e7eb; }
    .border-bottom-dashed:last-child { border-bottom: none; }
</style>
@endsection