@extends('layouts.tu')

@section('title', 'Kelola Informasi Kampus')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-dark fw-bold m-0">Kelola Informasi Kampus</h1>
            <p class="text-muted mb-0" style="font-size: 0.85rem;">Data kampus referensi untuk rekomendasi chatbot & informasi siswa</p>
        </div>
        <button class="btn btn-primary shadow-sm rounded-3" data-bs-toggle="modal" data-bs-target="#modalTambahKampus">
            <i class="fas fa-plus me-1"></i> Tambah Kampus
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
                            <th class="py-3" width="8%">Logo</th>
                            <th class="py-3">Nama Kampus</th>
                            <th class="py-3">Jurusan Unggulan</th>
                            <th class="py-3">Jalur Beasiswa</th>
                            <th class="py-3 text-center" width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kampus as $index => $k)
                        <tr>
                            <td class="px-4 py-3 fw-bold text-muted">{{ $index + 1 }}</td>
                            <td class="py-3">
                                @if($k->logo)
                                    <img src="{{ asset('storage/kampus/' . $k->logo) }}" alt="{{ $k->nama_kampus }}" style="width:40px;height:40px;object-fit:cover;border-radius:8px;">
                                @else
                                    <div class="bg-secondary bg-opacity-10 text-secondary rounded-3 d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                        <i class="fas fa-university fa-sm"></i>
                                    </div>
                                @endif
                            </td>
                            <td class="py-3 fw-bold text-dark">
                                {{ $k->nama_kampus }}
                                @if($k->link_website)
                                    <a href="{{ $k->link_website }}" target="_blank" class="text-muted small ms-1"><i class="fas fa-external-link-alt"></i></a>
                                @endif
                            </td>
                            <td class="py-3">{{ $k->jurusan_unggulan ?: '-' }}</td>
                            <td class="py-3">{{ $k->jalur_beasiswa ?: '-' }}</td>
                            <td class="py-3 text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <button class="btn btn-sm btn-info text-white d-flex align-items-center justify-content-center"
                                            style="width: 35px; height: 35px; border-radius: 8px;"
                                            data-bs-toggle="modal" data-bs-target="#modalEditKampus{{ $k->id }}" title="Edit">
                                        <i class="fas fa-pen fa-sm"></i>
                                    </button>

                                    <form action="{{ route('tu.kampus.destroy', $k->id) }}" method="POST" onsubmit="return confirm('Yakin hapus data kampus ini?')">
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
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-university fa-3x mb-3 text-light"></i>
                                <h5>Belum ada data kampus</h5>
                                <p>Silakan klik tombol "Tambah Kampus" untuk memulai.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ============ MODAL TAMBAH ============ --}}
    <div class="modal fade" id="modalTambahKampus" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('tu.kampus.store') }}" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow">
                @csrf
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-plus text-primary me-2"></i>Tambah Kampus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Nama Kampus</label>
                        <input type="text" name="nama_kampus" class="form-control" placeholder="Contoh: Universitas Andalas" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Logo <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Deskripsi <span class="text-muted fw-normal">(opsional)</span></label>
                        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Deskripsi singkat kampus"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Jurusan Unggulan <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="text" name="jurusan_unggulan" class="form-control" placeholder="Contoh: Teknik Informatika, Kedokteran">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Jalur Beasiswa <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="text" name="jalur_beasiswa" class="form-control" placeholder="Contoh: KIP-K, Beasiswa Unggulan">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Link Website <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="url" name="link_website" class="form-control" placeholder="https://...">
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============ MODAL EDIT (per baris) ============ --}}
    @foreach($kampus as $k)
    <div class="modal fade" id="modalEditKampus{{ $k->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('tu.kampus.update', $k->id) }}" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow">
                @csrf @method('PUT')
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-pen text-info me-2"></i>Edit Kampus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Nama Kampus</label>
                        <input type="text" name="nama_kampus" class="form-control" value="{{ $k->nama_kampus }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Logo <span class="text-muted fw-normal">(kosongkan jika tidak diganti)</span></label>
                        @if($k->logo)
                            <div class="mb-2">
                                <img src="{{ asset('storage/kampus/' . $k->logo) }}" style="width:50px;height:50px;object-fit:cover;border-radius:8px;">
                            </div>
                        @endif
                        <input type="file" name="logo" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3">{{ $k->deskripsi }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Jurusan Unggulan</label>
                        <input type="text" name="jurusan_unggulan" class="form-control" value="{{ $k->jurusan_unggulan }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Jalur Beasiswa</label>
                        <input type="text" name="jalur_beasiswa" class="form-control" value="{{ $k->jalur_beasiswa }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Link Website</label>
                        <input type="url" name="link_website" class="form-control" value="{{ $k->link_website }}">
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
