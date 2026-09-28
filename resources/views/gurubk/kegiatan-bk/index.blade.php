{{-- LETAKKAN DI: resources/views/gurubk/kegiatan-bk/index.blade.php --}}
@extends('layouts.guru')

@section('title', 'Kalender Kegiatan BK')

@section('content')

<div class="page-header-banner mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="page-header-icon"><i class="fas fa-calendar-check"></i></div>
            <div>
                <h3 class="fw-bold mb-1 text-white">Kalender Kegiatan BK</h3>
                <small class="text-white-50">Catat rencana kegiatan BK (sosialisasi, penyuluhan, rapat, dsb) supaya Kepsek bisa memantau agenda ke depan</small>
            </div>
        </div>
        <button class="btn btn-light fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahKegiatan">
            <i class="fas fa-plus-circle me-2"></i> Tambah Kegiatan
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    {{ session('success') }}
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-bold mb-0"><i class="fas fa-calendar-day text-primary me-2"></i>Kegiatan Mendatang</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted">
                    <tr>
                        <th class="px-4 py-3">Kegiatan</th>
                        <th class="py-3">Kategori</th>
                        <th class="py-3">Sasaran</th>
                        <th class="py-3">Jadwal</th>
                        <th class="py-3">Lokasi</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kegiatanMendatang as $k)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="fw-bold text-dark">{{ $k->judul }}</div>
                            @if($k->deskripsi)
                                <div class="text-muted small">{{ \Illuminate\Support\Str::limit($k->deskripsi, 70) }}</div>
                            @endif
                        </td>
                        <td class="py-3"><span class="badge bg-secondary">{{ $k->kategori }}</span></td>
                        <td class="py-3">{{ $k->sasaran ?: '-' }}</td>
                        <td class="py-3 small">
                            {{ $k->tanggal_mulai->translatedFormat('d M Y, H:i') }}
                            @if($k->tanggal_selesai)
                                &ndash; {{ $k->tanggal_selesai->translatedFormat('d M Y, H:i') }}
                            @endif
                            @if($k->sudahLewat())
                                <span class="badge bg-danger d-block mt-1" style="width: fit-content;">Sudah lewat, belum ditandai</span>
                            @endif
                        </td>
                        <td class="py-3">{{ $k->lokasi ?: '-' }}</td>
                        <td class="py-3 text-center">
                            <form action="{{ route('gurubk.kegiatan-bk.status', $k->id) }}" method="POST">
                                @csrf
                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="Direncanakan" @selected($k->status === 'Direncanakan')>Direncanakan</option>
                                    <option value="Terlaksana" @selected($k->status === 'Terlaksana')>Terlaksana</option>
                                    <option value="Dibatalkan" @selected($k->status === 'Dibatalkan')>Dibatalkan</option>
                                </select>
                            </form>
                        </td>
                        <td class="py-3 text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <button class="btn btn-sm btn-outline-primary" title="Edit Kegiatan"
                                        data-bs-toggle="modal" data-bs-target="#modalEditKegiatan{{ $k->id }}">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <form action="{{ route('gurubk.kegiatan-bk.destroy', $k->id) }}" method="POST" onsubmit="return confirm('Hapus kegiatan ini dari kalender?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    {{-- Modal Edit Kegiatan --}}
                    <tr class="d-none">
                        <td colspan="7">
                            <div class="modal fade" id="modalEditKegiatan{{ $k->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title fw-bold"><i class="fas fa-pen me-2"></i>Edit Kegiatan BK</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('gurubk.kegiatan-bk.update', $k->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body p-4">
                                                <div class="mb-3">
                                                    <label class="form-label text-muted small fw-bold">Judul Kegiatan</label>
                                                    <input type="text" name="judul" class="form-control" required value="{{ $k->judul }}">
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label text-muted small fw-bold">Kategori</label>
                                                        <select name="kategori" class="form-select" required>
                                                            @foreach(['Sosialisasi','Penyuluhan','Rapat/Koordinasi','Bimbingan Klasikal','Lainnya'] as $kat)
                                                                <option value="{{ $kat }}" @selected($k->kategori === $kat)>{{ $kat }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label text-muted small fw-bold">Sasaran</label>
                                                        <input type="text" name="sasaran" class="form-control" value="{{ $k->sasaran }}">
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label text-muted small fw-bold">Tanggal & Waktu Mulai</label>
                                                        <input type="datetime-local" name="tanggal_mulai" class="form-control" required value="{{ $k->tanggal_mulai->format('Y-m-d\TH:i') }}">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label text-muted small fw-bold">Tanggal & Waktu Selesai (opsional)</label>
                                                        <input type="datetime-local" name="tanggal_selesai" class="form-control" value="{{ $k->tanggal_selesai?->format('Y-m-d\TH:i') }}">
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label text-muted small fw-bold">Lokasi</label>
                                                    <input type="text" name="lokasi" class="form-control" value="{{ $k->lokasi }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label text-muted small fw-bold">Deskripsi</label>
                                                    <textarea name="deskripsi" class="form-control" rows="3">{{ $k->deskripsi }}</textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light border-0">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="fas fa-calendar-check fa-3x mb-3 opacity-25 d-block"></i>
                            Belum ada kegiatan yang direncanakan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-bold mb-0"><i class="fas fa-history text-muted me-2"></i>Riwayat Kegiatan</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted">
                    <tr>
                        <th class="px-4 py-3">Kegiatan</th>
                        <th class="py-3">Kategori</th>
                        <th class="py-3">Jadwal</th>
                        <th class="py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kegiatanLalu as $k)
                    <tr>
                        <td class="px-4 py-3">{{ $k->judul }}</td>
                        <td class="py-3"><span class="badge bg-secondary">{{ $k->kategori }}</span></td>
                        <td class="py-3 small">{{ $k->tanggal_mulai->translatedFormat('d M Y, H:i') }}</td>
                        <td class="py-3 text-center">
                            @if($k->status === 'Terlaksana')
                                <span class="badge bg-success">Terlaksana</span>
                            @else
                                <span class="badge bg-danger">Dibatalkan</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada riwayat kegiatan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahKegiatan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-calendar-plus me-2"></i>Tambah Kegiatan BK</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('gurubk.kegiatan-bk.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Judul Kegiatan</label>
                        <input type="text" name="judul" class="form-control" required placeholder="Contoh: Sosialisasi Jurusan Kuliah">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small fw-bold">Kategori</label>
                            <select name="kategori" class="form-select" required>
                                <option value="Sosialisasi">Sosialisasi</option>
                                <option value="Penyuluhan">Penyuluhan</option>
                                <option value="Rapat/Koordinasi">Rapat/Koordinasi</option>
                                <option value="Bimbingan Klasikal">Bimbingan Klasikal</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small fw-bold">Sasaran</label>
                            <input type="text" name="sasaran" class="form-control" placeholder="Contoh: Kelas XII, Seluruh Siswa">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small fw-bold">Tanggal & Waktu Mulai</label>
                            <input type="datetime-local" name="tanggal_mulai" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small fw-bold">Tanggal & Waktu Selesai (opsional)</label>
                            <input type="datetime-local" name="tanggal_selesai" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Lokasi</label>
                        <input type="text" name="lokasi" class="form-control" placeholder="Contoh: Aula Sekolah, Ruang BK">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Uraian singkat kegiatan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success px-4">Simpan Kegiatan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection