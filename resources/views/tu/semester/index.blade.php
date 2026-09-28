@extends('layouts.tu')

@section('title', 'Manajemen Semester')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 mb-0 text-dark fw-bold">Data Semester</h1>
            <p class="text-muted mb-0" style="font-size: 0.85rem;">Kelola periode aktif tahun ajaran</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahSemester">
            <i class="fas fa-plus fa-sm me-1"></i> Buat Semester Baru
        </button>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted">
                        <tr>
                            <th class="px-4 py-3">Nama Semester</th>
                            <th class="py-3">Tgl Mulai</th>
                            <th class="py-3">Tgl Selesai</th>
                            <th class="py-3 text-center">Status</th>
                            <th class="py-3 text-center">Aksi</th> </tr>
                    </thead>
                    <tbody>
                        @forelse($semesters as $semester)
                        <tr>
                            <td class="px-4 py-3 fw-bold">{{ $semester->nama }}</td>
                            <td class="py-3">{{ \Carbon\Carbon::parse($semester->tanggal_mulai)->translatedFormat('d M Y') }}</td>
                            <td class="py-3">{{ \Carbon\Carbon::parse($semester->tanggal_selesai)->translatedFormat('d M Y') }}</td>
                            <td class="py-3 text-center">
                                @if($semester->status_aktif)
                                    <span class="badge bg-success px-3 py-2 rounded-pill"><i class="fas fa-check me-1"></i> Aktif</span>
                                @else
                                    <span class="badge bg-secondary px-3 py-2 rounded-pill"><i class="fas fa-archive me-1"></i> Arsip</span>
                                @endif
                            </td>
                            <td class="py-3 text-center">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle rounded-pill px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Kelola
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li>
                                            <a class="dropdown-item py-2" href="{{ route('tu.semester.show', $semester->id) }}">
                                                <i class="fas fa-eye text-info me-2 w-15px"></i> Lihat Detail
                                            </a>
                                        </li>
                                        
                                        <li>
                                            <button class="dropdown-item py-2" data-bs-toggle="modal" data-bs-target="#modalEditSemester{{ $semester->id }}">
                                                <i class="fas fa-edit text-warning me-2 w-15px"></i> Edit Rincian
                                            </button>
                                        </li>
                                        
                                        @if(!$semester->status_aktif)
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('tu.semester.status', $semester->id) }}" method="POST" onsubmit="return confirm('Aktifkan semester ini? Semester yang sedang aktif saat ini akan otomatis diarsipkan.');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="dropdown-item text-success fw-bold py-2">
                                                    <i class="fas fa-power-off me-2 w-15px"></i> Aktifkan Kembali
                                                </button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('tu.semester.destroy', $semester->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus semester {{ $semester->nama }} secara permanen?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger fw-bold py-2">
                                                    <i class="fas fa-trash-alt me-2 w-15px"></i> Hapus Semester
                                                </button>
                                            </form>
                                        </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">Belum ada data semester. Silakan buat semester baru.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- PERBAIKAN (7 Agustus 2026): modal edit dulu ditulis di dalam <tbody>,
         berdampingan dengan <tr>. Padahal <div> bukan elemen yang sah di dalam
         tabel -- browser memindahkannya paksa saat mengurai halaman, lalu
         terjebak di dalam .table-responsive yang memakai overflow-x: auto.
         Hasilnya modal terpotong di dalam kotak tabel dan kolom tanggalnya
         tidak terlihat. Sekarang modal ditaruh di luar tabel, sejajar dengan
         modal "Buat Semester Baru" di bawahnya. --}}
    @foreach($semesters as $semesterEdit)
    <div class="modal fade" id="modalEditSemester{{ $semesterEdit->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold"><i class="fas fa-edit text-warning me-2"></i>Edit Semester</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('tu.semester.update', $semesterEdit->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted small text-uppercase">Nama Semester</label>
                            <input type="text" name="nama" class="form-control" value="{{ $semesterEdit->nama }}" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted small text-uppercase">Tanggal Mulai</label>
                                {{-- Kolom input bertipe date hanya mau menerima format Y-m-d.
                                     Kalau nilainya dilewatkan apa adanya dan ternyata bertipe
                                     datetime, isiannya tampil kosong tanpa pesan apa pun --
                                     lalu tanggal lama ikut hilang begitu disimpan. --}}
                                <input type="date" name="tanggal_mulai" class="form-control"
                                       value="{{ \Carbon\Carbon::parse($semesterEdit->tanggal_mulai)->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted small text-uppercase">Tanggal Selesai</label>
                                <input type="date" name="tanggal_selesai" class="form-control"
                                       value="{{ \Carbon\Carbon::parse($semesterEdit->tanggal_selesai)->format('Y-m-d') }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach

    <div class="modal fade" id="modalTambahSemester" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalTambahLabel"><i class="fas fa-calendar-plus text-primary me-2"></i>Buat Semester Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('tu.semester.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="alert alert-warning mb-4 border-0" style="font-size: 0.85rem; background-color: #fffbeb; color: #b45309;">
                            <i class="fas fa-exclamation-triangle me-1"></i> <strong>Perhatian:</strong> Membuat semester baru akan secara otomatis menonaktifkan (mengarsipkan) semester yang sedang aktif saat ini.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted small text-uppercase">Nama Semester</label>
                            <input type="text" name="nama" class="form-control" placeholder="Contoh: Ganjil 2026/2027" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted small text-uppercase">Tanggal Mulai</label>
                                <input type="date" name="tanggal_mulai" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted small text-uppercase">Tanggal Selesai</label>
                                <input type="date" name="tanggal_selesai" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4">Simpan & Aktifkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection