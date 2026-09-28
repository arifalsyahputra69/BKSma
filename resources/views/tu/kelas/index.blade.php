@extends('layouts.tu')

@section('title', 'Kelola Kelas')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-dark fw-bold m-0">Kelola Kelas</h1>
            <p class="text-muted mb-0" style="font-size: 0.85rem;">Atur data kelas, Guru BK, dan Wali Kelas</p>
        </div>
        <button class="btn btn-primary shadow-sm rounded-3" data-bs-toggle="modal" data-bs-target="#modalTambahKelas">
            <i class="fas fa-plus me-1"></i> Tambah Kelas
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted">
                        <tr>
                            <th class="px-4 py-3" width="5%">No</th>
                            <th class="py-3">Nama Kelas</th>
                            <th class="py-3">Guru BK Penanggung Jawab</th>
                            <th class="py-3">Wali Kelas</th>
                            <th class="py-3 text-center" width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kelas as $index => $k)
                        <tr>
                            <td class="px-4 py-3 fw-bold text-muted">{{ $index + 1 }}</td>
                            <td class="py-3 fw-bold text-dark">{{ $k->nama_kelas }}</td>
                            <td class="py-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px;">
                                        <i class="fas fa-user-tie fa-sm"></i>
                                    </div>
                                    {{ $k->guruBk->name ?? 'Belum Ditugaskan' }}
                                </div>
                            </td>
                            <td class="py-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-warning bg-opacity-25 text-warning-emphasis rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px;">
                                        <i class="fas fa-chalkboard-teacher fa-sm"></i>
                                    </div>
                                    {{ $k->waliKelas->name ?? 'Belum Ditugaskan' }}
                                </div>
                            </td>
                            <td class="py-3 text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('tu.kelas.show', $k->id) }}" 
                                       class="btn btn-sm btn-primary d-flex align-items-center justify-content-center" 
                                       style="width: 35px; height: 35px; border-radius: 8px;" title="Lihat Anggota Kelas">
                                        <i class="fas fa-eye fa-sm"></i>
                                    </a>

                                    <button class="btn btn-sm btn-info text-white d-flex align-items-center justify-content-center" 
                                            style="width: 35px; height: 35px; border-radius: 8px;" 
                                            data-bs-toggle="modal" data-bs-target="#modalEditKelas{{ $k->id }}" title="Edit">
                                        <i class="fas fa-pen fa-sm"></i>
                                    </button>

                                    <form action="{{ route('tu.kelas.destroy', $k->id) }}" method="POST" onsubmit="return confirm('Yakin hapus kelas ini?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-danger d-flex align-items-center justify-content-center" 
                                                style="width: 35px; height: 35px; border-radius: 8px;" title="Hapus">
                                            <i class="fas fa-trash fa-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fas fa-door-open fa-3x mb-3 text-light"></i>
                                <h5>Belum ada data kelas</h5>
                                <p>Silakan klik tombol "Tambah Kelas" untuk memulai.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTambahKelas" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('tu.kelas.store') }}" method="POST" class="modal-content border-0 shadow">
                @csrf
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-plus text-primary me-2"></i>Tambah Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Nama Kelas</label>
                        <input type="text" name="nama_kelas" class="form-control" placeholder="Contoh: X-IPA 1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Pilih Guru BK</label>
                        <select name="id_guru_bk" class="form-select" required>
                            <option value="" disabled selected>-- Pilih Guru BK --</option>
                            @foreach($guruBk as $guru)
                                <option value="{{ $guru->id }}">{{ $guru->name }}</option>
                            @endforeach
                        </select>
                        @if($guruBk->isEmpty())
                            <small class="text-danger mt-1 d-block"><i class="fas fa-exclamation-circle"></i> Anda belum memiliki user dengan role 'Guru BK'. Silakan tambahkan di menu Kelola Pengguna.</small>
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Pilih Wali Kelas <span class="text-muted fw-normal">(opsional)</span></label>
                        <select name="wali_kelas_id" class="form-select">
                            <option value="" selected>-- Belum Ditugaskan --</option>
                            @foreach($waliKelas as $wali)
                                <option value="{{ $wali->id }}">{{ $wali->name }}</option>
                            @endforeach
                        </select>
                        @if($waliKelas->isEmpty())
                            <small class="text-danger mt-1 d-block"><i class="fas fa-exclamation-circle"></i> Anda belum memiliki user dengan role 'Wali Kelas'. Silakan tambahkan di menu Kelola Pengguna.</small>
                        @endif
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4" {{ $guruBk->isEmpty() ? 'disabled' : '' }}>Simpan</button>
                </div>
            </form>
        </div>
    </div>

    @foreach($kelas as $k)
    <div class="modal fade" id="modalEditKelas{{ $k->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('tu.kelas.update', $k->id) }}" method="POST" class="modal-content border-0 shadow">
                @csrf @method('PUT')
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-pen text-info me-2"></i>Edit Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Nama Kelas</label>
                        <input type="text" name="nama_kelas" class="form-control" value="{{ $k->nama_kelas }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Guru BK Penanggung Jawab</label>
                        <select name="id_guru_bk" class="form-select" required>
                            @foreach($guruBk as $guru)
                                <option value="{{ $guru->id }}" {{ $k->id_guru_bk == $guru->id ? 'selected' : '' }}>
                                    {{ $guru->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Wali Kelas <span class="text-muted fw-normal">(opsional)</span></label>
                        <select name="wali_kelas_id" class="form-select">
                            <option value="" {{ is_null($k->wali_kelas_id) ? 'selected' : '' }}>-- Belum Ditugaskan --</option>
                            @foreach($waliKelas as $wali)
                                <option value="{{ $wali->id }}" {{ $k->wali_kelas_id == $wali->id ? 'selected' : '' }}>
                                    {{ $wali->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info text-white px-4">Update Data</button>
                </div>
            </form>
        </div>
    </div>
    @endforeach
@endsection
