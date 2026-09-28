@extends('layouts.guru')

@section('title', 'Pengajuan Program BK')

@section('content')

{{-- PERBAIKAN (28 Juli 2026, revisi ke-2) berdasarkan masukan Kepsek:
     1. Jenis program disederhanakan jadi 3 opsi saja: Tahunan, Semesteran,
        Bulanan (opsi "Mingguan" dihapus, dianggap kebanyakan pilihan).
     2. Form Ajukan/Edit Program: Judul, Jenis Program (dropdown 3 opsi),
        Tahun Ajaran (dropdown dari data Semester), Sasaran Kelas (checklist
        dari data Kelas), Deskripsi. Tanggal mulai & selesai otomatis
        mengikuti Tahun Ajaran yang dipilih. Upload RPS tetap dipertahankan.
        Field "Status Program" (Draft/Aktif) DIHAPUS -- aktif/tidaknya
        program murni ditentukan keputusan Kepsek (Disetujui/Ditolak), tidak
        perlu lagi diinput manual saat mengajukan.
     3. Kolom Realisasi tidak lagi dropdown manual (Belum Mulai/Berjalan/
        Selesai) -- "Belum Mulai" & "Berjalan" kini dihitung otomatis dari
        tanggal program. Guru BK hanya perlu klik tombol "Tandai Selesai"
        saat programnya benar-benar rampung.
     4. Kolom Aksi (Edit & Hapus) tetap tersedia selama program masih
        berstatus "Diajukan". --}}
<div class="page-header-banner mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="page-header-icon"><i class="fas fa-clipboard-list"></i></div>
            <div>
                <h3 class="fw-bold mb-1 text-white">Pengajuan Program BK</h3>
                <small class="text-white-50">Ajukan program kerja BK (Tahunan, Semesteran, Bulanan) beserta RPS-nya, lalu pantau realisasinya</small>
            </div>
        </div>
        <button class="btn btn-light fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAjukanProgram">
            <i class="fas fa-plus-circle me-2"></i> Ajukan Program
        </button>
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

{{-- Tab filter jenis program --}}
<ul class="nav nav-pills gap-2 mb-4 flex-wrap">
    <li class="nav-item">
        <a href="{{ route('gurubk.program-bk.index', ['jenis' => 'Semua']) }}"
           class="nav-link {{ ($jenis ?? 'Semua') === 'Semua' ? 'active' : '' }}">
            Semua
        </a>
    </li>
    <li class="nav-item">
        <a href="{{ route('gurubk.program-bk.index', ['jenis' => 'Tahunan']) }}"
           class="nav-link {{ ($jenis ?? '') === 'Tahunan' ? 'active' : '' }}">
            <i class="fas fa-calendar-days me-1"></i> Program Tahunan
            <span class="badge bg-white text-success ms-1">{{ $totalTahunan ?? 0 }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a href="{{ route('gurubk.program-bk.index', ['jenis' => 'Semesteran']) }}"
           class="nav-link {{ ($jenis ?? '') === 'Semesteran' ? 'active' : '' }}">
            <i class="fas fa-calendar-week me-1"></i> Program Semesteran
            <span class="badge bg-white text-success ms-1">{{ $totalSemesteran ?? 0 }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a href="{{ route('gurubk.program-bk.index', ['jenis' => 'Bulanan']) }}"
           class="nav-link {{ ($jenis ?? '') === 'Bulanan' ? 'active' : '' }}">
            <i class="fas fa-calendar me-1"></i> Program Bulanan
            <span class="badge bg-white text-success ms-1">{{ $totalBulanan ?? 0 }}</span>
        </a>
    </li>
</ul>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted">
                    <tr>
                        <th class="px-4 py-3">Program</th>
                        <th class="py-3">Jenis</th>
                        <th class="py-3">Tahun Ajaran</th>
                        <th class="py-3">Sasaran Kelas</th>
                        <th class="py-3">RPS</th>
                        <th class="py-3">Status Pengajuan</th>
                        <th class="py-3">Realisasi</th>
                        <th class="py-3">Catatan Kepsek</th>
                        <th class="py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($programs as $p)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="fw-bold text-dark">{{ $p->judul }}</div>
                            <div class="text-muted small">{{ \Illuminate\Support\Str::limit($p->deskripsi, 80) }}</div>
                        </td>
                        <td class="py-3">
                            <span class="badge {{ $p->jenisProgramBadgeClass() }}">{{ $p->jenis_program }}</span>
                        </td>
                        <td class="py-3 small">{{ $p->semester->nama ?? '-' }}</td>
                        <td class="py-3">{{ $p->sasaran }}</td>
                        <td class="py-3">
                            @if($p->rps_file)
                                <a href="{{ asset('storage/' . $p->rps_file) }}" target="_blank" class="btn btn-sm btn-success">
                                    <i class="fas fa-file-pdf me-1"></i> Lihat RPS
                                </a>
                            @else
                                <span class="text-muted small fst-italic">Belum ada file</span>
                            @endif
                        </td>
                        <td class="py-3">
                            @if($p->status === 'Diajukan')
                                <span class="badge bg-warning text-dark">Menunggu Persetujuan</span>
                            @elseif($p->status === 'Disetujui')
                                <span class="badge bg-success">Disetujui</span>
                            @else
                                <span class="badge bg-danger">Ditolak</span>
                            @endif
                        </td>
                        <td class="py-3">
                            @if($p->status === 'Disetujui')
                                <span class="badge {{ $p->statusRealisasiBadgeClass() }} mb-1 d-inline-block">{{ $p->statusRealisasiLabel() }}</span>
                                @if($p->bisaDitandaiSelesai())
                                <form action="{{ route('gurubk.program-bk.tandai-selesai', $p->id) }}" method="POST" onsubmit="return confirm('Tandai program ini sudah selesai dilaksanakan?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success d-block">
                                        <i class="fas fa-check-circle me-1"></i> Tandai Selesai
                                    </button>
                                </form>
                                @endif
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="py-3 small text-muted">{{ $p->catatan_kepsek ?? '-' }}</td>
                        <td class="py-3 text-center">
                            @if($p->status === 'Diajukan')
                            <div class="d-flex gap-1 justify-content-center">
                                <button class="btn btn-sm btn-outline-primary" title="Edit Pengajuan"
                                        data-bs-toggle="modal" data-bs-target="#modalEditProgram{{ $p->id }}">
                                    <i class="fas fa-pen fa-sm"></i>
                                </button>
                                <form action="{{ route('gurubk.program-bk.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Batalkan pengajuan ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Batalkan Pengajuan">
                                        <i class="fas fa-trash fa-sm"></i>
                                    </button>
                                </form>
                            </div>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                    </tr>

                    {{-- Modal Edit (hanya dibuat untuk program yang masih "Diajukan") --}}
                    @if($p->status === 'Diajukan')
                    <tr class="d-none">
                        <td colspan="9">
                            <div class="modal fade" id="modalEditProgram{{ $p->id }}" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <form action="{{ route('gurubk.program-bk.update', $p->id) }}" method="POST" class="modal-content" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold"><i class="fas fa-pen text-primary me-2"></i>Edit Pengajuan Program BK</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold small text-muted">Judul/Nama Program</label>
                                                <input type="text" name="judul" class="form-control" placeholder="Contoh: Program Tahunan BK Kelas X 2026/2027" required value="{{ $p->judul }}">
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-semibold small text-muted">Jenis Program</label>
                                                    <select name="jenis_program" class="form-select" required>
                                                        <option value="Tahunan" @selected($p->jenis_program === 'Tahunan')>Tahunan</option>
                                                        <option value="Semesteran" @selected($p->jenis_program === 'Semesteran')>Semesteran</option>
                                                        <option value="Bulanan" @selected($p->jenis_program === 'Bulanan')>Bulanan</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-semibold small text-muted">Tahun Ajaran</label>
                                                    <select name="semester_id" class="form-select" required>
                                                        <option value="" disabled>Pilih tahun ajaran...</option>
                                                        @foreach($semesterList as $s)
                                                            <option value="{{ $s->id }}" @selected($p->semester_id === $s->id)>{{ $s->nama }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold small text-muted">Sasaran Kelas</label>
                                                @php $sasaranTerpilih = array_map('trim', explode(',', $p->sasaran)); @endphp
                                                <div class="border rounded p-2" style="max-height: 150px; overflow-y: auto;">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="sasaran_kelas[]" value="Semua Kelas" id="editSemua{{ $p->id }}" @checked(in_array('Semua Kelas', $sasaranTerpilih))>
                                                        <label class="form-check-label" for="editSemua{{ $p->id }}">Semua Kelas</label>
                                                    </div>
                                                    @foreach($kelasList as $k)
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="sasaran_kelas[]" value="{{ $k->nama_kelas }}" id="editKelas{{ $p->id }}_{{ $k->id }}" @checked(in_array($k->nama_kelas, $sasaranTerpilih))>
                                                        <label class="form-check-label" for="editKelas{{ $p->id }}_{{ $k->id }}">{{ $k->nama_kelas }}</label>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold small text-muted">Deskripsi / Catatan Singkat</label>
                                                <textarea name="deskripsi" class="form-control" rows="3" required>{{ $p->deskripsi }}</textarea>
                                            </div>
                                            <div class="mb-1">
                                                <label class="form-label fw-semibold small text-muted">Dokumen RPS (PDF/Word, opsional)</label>
                                                <input type="file" name="rps_file" class="form-control" accept=".pdf,.doc,.docx">
                                                @if($p->rps_file)
                                                    <div class="form-text">File saat ini: <a href="{{ asset('storage/' . $p->rps_file) }}" target="_blank">lihat RPS</a>. Unggah file baru untuk menggantinya.</div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endif
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fas fa-clipboard-list fa-3x mb-3 text-light"></i>
                            <h5>Belum ada program yang diajukan</h5>
                            <p>Klik "Ajukan Program" untuk mulai mengajukan program kerja BK.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Ajukan Program --}}
<div class="modal fade" id="modalAjukanProgram" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form action="{{ route('gurubk.program-bk.store') }}" method="POST" class="modal-content" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-clipboard-list text-success me-2"></i>Ajukan Program BK</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">Judul/Nama Program</label>
                    <input type="text" name="judul" class="form-control" placeholder="Contoh: Program Tahunan BK Kelas X 2026/2027" required value="{{ old('judul') }}">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold small text-muted">Jenis Program</label>
                        <select name="jenis_program" class="form-select" required>
                            <option value="" disabled selected>Pilih jenis program...</option>
                            <option value="Tahunan" @selected(old('jenis_program') === 'Tahunan')>Tahunan</option>
                            <option value="Semesteran" @selected(old('jenis_program') === 'Semesteran')>Semesteran</option>
                            <option value="Bulanan" @selected(old('jenis_program') === 'Bulanan')>Bulanan</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold small text-muted">Tahun Ajaran</label>
                        <select name="semester_id" class="form-select" required>
                            <option value="" disabled selected>Pilih tahun ajaran...</option>
                            @foreach($semesterList as $s)
                                <option value="{{ $s->id }}" @selected((string) old('semester_id') === (string) $s->id)>{{ $s->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">Sasaran Kelas</label>
                    <div class="border rounded p-2" style="max-height: 150px; overflow-y: auto;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="sasaran_kelas[]" value="Semua Kelas" id="createSemua">
                            <label class="form-check-label" for="createSemua">Semua Kelas</label>
                        </div>
                        @foreach($kelasList as $k)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="sasaran_kelas[]" value="{{ $k->nama_kelas }}" id="createKelas{{ $k->id }}">
                            <label class="form-check-label" for="createKelas{{ $k->id }}">{{ $k->nama_kelas }}</label>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">Deskripsi / Catatan Singkat</label>
                    <textarea name="deskripsi" class="form-control" rows="3" placeholder="Ringkasan, tujuan utama, atau catatan tambahan program" required>{{ old('deskripsi') }}</textarea>
                </div>
                <div class="mb-1">
                    <label class="form-label fw-semibold small text-muted">Dokumen RPS / RPL (PDF/Word, opsional)</label>
                    <input type="file" name="rps_file" class="form-control" accept=".pdf,.doc,.docx">
                    <div class="form-text">Lampirkan Rencana Pelaksanaan Semester/Layanan sebagai bukti pendukung program.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success px-4">Ajukan ke Kepala Sekolah</button>
            </div>
        </form>
    </div>
</div>

@if($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        new bootstrap.Modal(document.getElementById('modalAjukanProgram')).show();
    });
</script>
@endif

@endsection