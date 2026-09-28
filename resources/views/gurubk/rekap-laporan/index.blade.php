@extends('layouts.guru')

@section('title', 'Jurnal BK')

@section('content')
    <div class="page-header-banner mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="page-header-icon"><i class="fas fa-book"></i></div>
            <div>
                <h1 class="h4 mb-1 text-white fw-bold">Jurnal BK</h1>
                <p class="text-white-50 mb-0" style="font-size: 0.85rem;">Pusat administrasi dan rekapitulasi layanan Bimbingan Konseling</p>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <strong>Gagal menyimpan:</strong>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <ul class="nav nav-pills mb-4 bg-white p-2 rounded-3 shadow-sm" id="jurnalTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold px-4" data-bs-toggle="pill" data-bs-target="#tab-individu" type="button">
                <i class="fas fa-user me-2"></i>Konseling Individu
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-4" data-bs-toggle="pill" data-bs-target="#tab-harian" type="button">
                <i class="fas fa-calendar-day me-2"></i>Jurnal Harian
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-4" data-bs-toggle="pill" data-bs-target="#tab-kelompok" type="button">
                <i class="fas fa-users me-2"></i>Bimbingan Kelompok
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-4" data-bs-toggle="pill" data-bs-target="#tab-klasikal" type="button">
                <i class="fas fa-chalkboard-teacher me-2"></i>Layanan Klasikal
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-4" data-bs-toggle="pill" data-bs-target="#tab-homevisit" type="button">
                <i class="fas fa-home me-2"></i>Home Visit
            </button>
        </li>
    </ul>

    <div class="tab-content" id="jurnalTabContent">
        
<!-- ================= TAB 1: KONSELING INDIVIDU ================= -->
        <div class="tab-pane fade show active" id="tab-individu" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="m-0 fw-bold text-dark">Riwayat Konseling &amp; Pelanggaran</h6>
                        <div class="d-flex gap-2">
                            <button class="btn btn-warning shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCatatPelanggaran">
                                <i class="fas fa-triangle-exclamation me-2"></i>Catat Pelanggaran
                            </button>
                            <button class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahJurnalIndividu">
                                <i class="fas fa-plus me-2"></i>Tambah Jurnal Individu
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="px-4 py-3">Tanggal</th>
                                    <th class="py-3">Siswa</th>
                                    <th class="py-3">Asal</th>
                                    <th class="py-3">Status Jurnal</th>
                                    <th class="py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($riwayatSelesai as $item)
                                <tr>
                                    <td class="px-4 py-3 text-muted small fw-bold">
                                        {{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y (H:i)') : '-' }}
                                    </td>
                                    <td class="py-3">
                                        @if($item->siswa)
                                            <a href="{{ route('gurubk.rekap.siswa', $item->siswa->id) }}" class="fw-bold text-dark text-decoration-none">
                                                {{ $item->siswa->user->name ?? 'Anonim' }} <i class="fas fa-external-link-alt ms-1" style="font-size: 0.65rem;"></i>
                                            </a>
                                        @else
                                            <div class="fw-bold text-dark">Anonim</div>
                                        @endif
                                        <div class="small text-muted">{{ $item->siswa->kelas->nama_kelas ?? '-' }}</div>
                                    </td>
                                    <td class="py-3">
                                        @if($item->tipe === 'antrian')
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 py-1 rounded-pill">Antrean Sesi</span>
                                        @elseif($item->jurnal && $item->jurnal->isPelanggaran())
                                            <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning px-3 py-1 rounded-pill"><i class="fas fa-triangle-exclamation me-1"></i>Pelanggaran</span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary px-3 py-1 rounded-pill">Ditambah Manual</span>
                                        @endif
                                    </td>
                                    <td class="py-3">
                                        @if($item->jurnal && $item->jurnal->isPelanggaran())
                                            <span class="badge {{ $item->jurnal->tingkat_pelanggaran === 'Berat' ? 'bg-danger' : ($item->jurnal->tingkat_pelanggaran === 'Sedang' ? 'bg-warning text-dark' : 'bg-secondary') }} px-3 py-1 rounded-pill">
                                                {{ $item->jurnal->tingkat_pelanggaran }} &middot; {{ $item->jurnal->poin }} poin
                                            </span>
                                        @elseif($item->jurnal)
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-1 rounded-pill"><i class="fas fa-check-circle me-1"></i> Jurnal Disimpan</span>
                                        @else
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 py-1 rounded-pill"><i class="fas fa-exclamation-circle me-1"></i> Belum Diisi</span>
                                        @endif
                                    </td>
                                    <td class="py-3 text-center">
                                        @if($item->jurnal)
                                            <button
                                                class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalDetailJurnal{{ $item->key }}">
                                                <i class="fas fa-eye me-1"></i>
                                                Lihat Detail
                                            </button>
                                        @else
                                            <button
                                                class="btn btn-sm btn-success rounded-pill px-3"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalJurnal{{ $item->key }}">
                                                <i class="fas fa-pen me-1"></i>
                                                Isi Jurnal
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">Belum ada riwayat konseling yang selesai.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($riwayatSelesai instanceof \Illuminate\Contracts\Pagination\Paginator && $riwayatSelesai->hasPages())
                    <div class="card-body border-top py-3">
                        {{ $riwayatSelesai->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                    @endif
                </div>
            </div>

            {{-- PERBAIKAN (30 Juli 2026): modal Detail/Edit/Isi Jurnal SEBELUMNYA
                 dirender di dalam <tbody>/<table> (HTML tidak valid -- browser
                 memindahkan <div> itu keluar dari <tbody> secara otomatis, tapi
                 hasilnya tetap jadi descendant dari .card di atas). Ini terbukti
                 jadi penyebab modal "Lihat Detail" macet total (tidak bisa
                 diklik sama sekali, harus refresh): .card punya efek hover
                 (transform) yang membuatnya jadi "containing block" baru untuk
                 modal (position: fixed) di dalamnya, sehingga posisi & area klik
                 modal salah hitung. Modal "Tambah Jurnal Individu" di bawah ini
                 TIDAK bermasalah karena sudah benar diletakkan di LUAR .card --
                 sekarang modal Detail/Edit/Isi Jurnal per baris juga dipindah ke
                 sini (di luar .card), mengikuti pola yang sama. --}}
            @foreach($riwayatSelesai as $item)
                                <!-- Modal Lihat Detail Jurnal (hanya muncul jika jurnal sudah ada) -->
                                @if($item->jurnal)
                                <div class="modal fade" id="modalDetailJurnal{{ $item->key }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable text-start">
                                        <div class="modal-content border-0 shadow">
                                            <div class="modal-header {{ $item->jurnal->isPelanggaran() ? 'bg-warning text-dark' : 'bg-primary text-white' }}">
                                                <h5 class="modal-title fw-bold">
                                                    @if($item->jurnal->isPelanggaran())
                                                        <i class="fas fa-triangle-exclamation me-2"></i>Detail Pelanggaran
                                                    @else
                                                        <i class="fas fa-file-medical me-2"></i>Detail Jurnal Konseling
                                                    @endif
                                                </h5>
                                                <button type="button" class="btn-close {{ $item->jurnal->isPelanggaran() ? '' : 'btn-close-white' }}" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="alert alert-light border mb-4">
                                                    <strong>Siswa:</strong> {{ $item->siswa->user->name ?? 'Anonim' }} <br>
                                                    @if($item->jurnal->isPelanggaran())
                                                        <strong>Poin:</strong> {{ $item->jurnal->poin }} poin ({{ $item->jurnal->tingkat_pelanggaran }})
                                                    @else
                                                        <strong>Keperluan Awal:</strong> {{ $item->keperluan }}
                                                    @endif
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label text-muted small fw-bold">
                                                        @if($item->jurnal->isPelanggaran()) Deskripsi Pelanggaran
                                                        @else Uraian Masalah
                                                        @endif
                                                    </label>
                                                    <p class="mb-0">{!! nl2br(e($item->jurnal->uraian_masalah)) !!}</p>
                                                </div>
                                                @if(!$item->jurnal->isPelanggaran())
                                                <div class="mb-3">
                                                    <label class="form-label text-muted small fw-bold">Kategori Masalah</label>
                                                    <p class="mb-0">{{ $item->jurnal->kategori_masalah }}</p>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label text-muted small fw-bold">Pendekatan / Teknik</label>
                                                    <p class="mb-0">{{ $item->jurnal->pendekatan_teknik ?? '-' }}</p>
                                                </div>
                                                @endif
                                                <div class="mb-3">
                                                    <label class="form-label text-muted small fw-bold">
                                                        @if($item->jurnal->isPelanggaran()) Tindak Lanjut
                                                        @else Rencana Tindak Lanjut
                                                        @endif
                                                    </label>
                                                    <p class="mb-0">{!! nl2br(e($item->jurnal->rencana_tindak_lanjut ?: '-')) !!}</p>
                                                </div>
                                                @if(!$item->jurnal->isPelanggaran())
                                                <div class="mb-3">
                                                    <label class="form-label text-muted small fw-bold">Status Kasus</label>
                                                    <p class="mb-0">{{ $item->jurnal->status_kasus }}</p>
                                                </div>
                                                @endif
                                                <div class="mb-0 d-flex gap-2">
                                                    @if($item->jurnal->tingkat_pelanggaran)
                                                        <span class="badge {{ $item->jurnal->tingkat_pelanggaran === 'Berat' ? 'bg-danger' : 'bg-warning text-dark' }}">
                                                            Pelanggaran {{ $item->jurnal->tingkat_pelanggaran }}
                                                        </span>
                                                    @endif
                                                    @if($item->jurnal->panggil_ortu)
                                                        <span class="badge bg-primary">
                                                            <i class="fas fa-phone me-1"></i>Orang Tua Dipanggil
                                                        </span>
                                                    @endif
                                                    @if($item->jurnal->tingkat_pelanggaran === 'Berat' || $item->jurnal->panggil_ortu)
                                                        <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                                            <i class="fab fa-whatsapp me-1"></i>WA Ortu Terkirim
                                                        </span>
                                                    @endif
                                                </div>
                                                @if($item->jurnal->jadwal_pertemuan_ortu)
                                                    <div class="mt-2 small text-muted">
                                                        <i class="fas fa-calendar-check me-1"></i>
                                                        Jadwal pertemuan ortu: {{ $item->jurnal->jadwal_pertemuan_ortu->translatedFormat('l, d F Y') }}
                                                        pukul {{ $item->jurnal->jadwal_pertemuan_ortu->format('H:i') }} WIB
                                                    </div>
                                                @endif
                                                {{-- PERBAIKAN: status persetujuan Kepsek sebelumnya tidak pernah
                                                     ditampilkan di modal Detail, padahal Guru BK perlu tahu apakah
                                                     usulan tindakan disipliner (Skorsing/DO) sudah ditinjau. --}}
                                                @if($item->jurnal->butuh_persetujuan_kepsek)
                                                    <hr>
                                                    <div class="alert {{ $item->jurnal->status_persetujuan_kepsek === 'Disetujui' ? 'alert-success' : ($item->jurnal->status_persetujuan_kepsek === 'Ditolak' ? 'alert-danger' : 'alert-warning') }} mb-0">
                                                        <div class="fw-bold mb-1">
                                                            <i class="fas fa-user-tie me-1"></i>
                                                            Persetujuan Kepsek: Usulan {{ $item->jurnal->jenis_tindakan_diusulkan }}
                                                        </div>
                                                        <div class="mb-0">
                                                            Status:
                                                            <span class="badge {{ $item->jurnal->status_persetujuan_kepsek === 'Disetujui' ? 'bg-success' : ($item->jurnal->status_persetujuan_kepsek === 'Ditolak' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                                                {{ $item->jurnal->status_persetujuan_kepsek ?? 'Menunggu' }}
                                                            </span>
                                                        </div>
                                                        @if($item->jurnal->status_persetujuan_kepsek !== 'Menunggu' && $item->jurnal->disetujui_at)
                                                            <div class="small mt-1 mb-0">
                                                                Ditinjau oleh {{ optional($item->jurnal->disetujuiOleh)->name ?? 'Kepala Sekolah' }}
                                                                pada {{ $item->jurnal->disetujui_at->translatedFormat('d F Y, H:i') }} WIB.
                                                            </div>
                                                        @endif
                                                        @if($item->jurnal->catatan_kepsek_persetujuan)
                                                            <div class="small mt-1 mb-0">
                                                                <strong>Catatan Kepsek:</strong> {{ $item->jurnal->catatan_kepsek_persetujuan }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="modal-footer bg-light border-0">
                                                @if($item->jurnal->isPelanggaran())
                                                <form action="{{ route('gurubk.rekap.pelanggaran.destroy', $item->jurnal->id) }}" method="POST" onsubmit="return confirm('Hapus catatan pelanggaran ini?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger"><i class="fas fa-trash me-1"></i>Hapus</button>
                                                </form>
                                                @else
                                                {{-- PERBAIKAN: sebelumnya jurnal konseling yang sudah tersimpan
                                                     tidak bisa diedit/dihapus lagi lewat modal Detail. --}}
                                                {{-- PERBAIKAN (30 Juli 2026): tombol ini dulu punya
                                                     data-bs-dismiss="modal" DAN data-bs-toggle="modal" sekaligus,
                                                     supaya modal Detail langsung tertutup sambil modal Edit langsung
                                                     terbuka. Kombinasi ini bikin race condition di Bootstrap (modal
                                                     Detail masih di tengah animasi "hide" saat modal Edit sudah mulai
                                                     "show"), sehingga backdrop & class "modal-open" di <body> tidak
                                                     sinkron -- modal jadi macet/tidak bisa ditutup sama sekali dan
                                                     guru BK terpaksa refresh halaman. Sekarang dipakai fungsi JS yang
                                                     menunggu modal Detail BENAR-BENAR selesai tertutup dulu (event
                                                     "hidden.bs.modal") sebelum membuka modal Edit. --}}
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-primary"
                                                    onclick="bukaModalEditDariDetail('{{ $item->key }}')">
                                                    <i class="fas fa-pen me-1"></i>Edit
                                                </button>
                                                <form action="{{ route('gurubk.rekap.jurnal.destroy', $item->jurnal->id) }}" method="POST" onsubmit="return confirm('Hapus jurnal konseling ini? Tindakan ini tidak bisa dibatalkan.')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger"><i class="fas fa-trash me-1"></i>Hapus</button>
                                                </form>
                                                @endif
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @endif

                                <!-- Modal Edit Jurnal Konseling (prefilled, hanya untuk jurnal konseling
                                     yang sudah ada -- BUKAN jenis Pelanggaran, yang punya alur edit sendiri) -->
                                @if($item->jurnal && !$item->jurnal->isPelanggaran())
                                <div class="modal fade" id="modalEditJurnal{{ $item->key }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable text-start">
                                        <div class="modal-content border-0 shadow">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title fw-bold"><i class="fas fa-pen me-2"></i>Edit Jurnal Konseling</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="{{ route('gurubk.rekap.jurnal.update', $item->jurnal->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                                                    <div class="alert alert-light border mb-4">
                                                        <strong>Siswa:</strong> {{ $item->siswa->user->name ?? 'Anonim' }}
                                                    </div>
                                                    @if($item->tipe === 'manual')
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label text-muted small fw-bold">Tanggal Konseling</label>
                                                            <input type="date" name="tanggal_konseling" class="form-control" value="{{ optional($item->jurnal->tanggal_konseling)->format('Y-m-d') }}" required>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label text-muted small fw-bold">Keperluan Awal</label>
                                                            <input type="text" name="keperluan" class="form-control" value="{{ $item->jurnal->keperluan }}" required>
                                                        </div>
                                                    </div>
                                                    @endif
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label text-muted small fw-bold">Kategori Masalah</label>
                                                            <select name="kategori_masalah" class="form-select" required>
                                                                @foreach(['Pribadi', 'Sosial', 'Belajar', 'Karir'] as $opt)
                                                                    <option value="{{ $opt }}" {{ $item->jurnal->kategori_masalah === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label text-muted small fw-bold">Pendekatan / Teknik</label>
                                                            <input type="text" name="pendekatan_teknik" class="form-control" value="{{ $item->jurnal->pendekatan_teknik }}">
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted small fw-bold">Uraian Masalah</label>
                                                        <textarea name="uraian_masalah" class="form-control" rows="3" required>{{ $item->jurnal->uraian_masalah }}</textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted small fw-bold">RTL</label>
                                                        <textarea name="rencana_tindak_lanjut" class="form-control" rows="2" required>{{ $item->jurnal->rencana_tindak_lanjut }}</textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted small fw-bold">Status Kasus</label>
                                                        <select name="status_kasus" class="form-select" required>
                                                            <option value="Selesai" {{ $item->jurnal->status_kasus === 'Selesai' ? 'selected' : '' }}>Selesai (Tuntas)</option>
                                                            <option value="Dipantau" {{ $item->jurnal->status_kasus === 'Dipantau' ? 'selected' : '' }}>Dipantau</option>
                                                            <option value="Referal" {{ $item->jurnal->status_kasus === 'Referal' ? 'selected' : '' }}>Referal</option>
                                                        </select>
                                                    </div>
                                                    <hr>
                                                    <div class="row align-items-end">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label text-muted small fw-bold">Tingkat Pelanggaran</label>
                                                            <select name="tingkat_pelanggaran" class="form-select" onchange="toggleJadwalPertemuan('Edit{{ $item->key }}')" id="tingkatPelanggaranEdit{{ $item->key }}">
                                                                <option value="">-- Bukan Kasus Pelanggaran --</option>
                                                                @foreach(['Ringan', 'Sedang', 'Berat'] as $opt)
                                                                    <option value="{{ $opt }}" {{ $item->jurnal->tingkat_pelanggaran === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" value="1" name="panggil_ortu" id="panggilOrtuEdit{{ $item->key }}" onchange="toggleJadwalPertemuan('Edit{{ $item->key }}')" {{ $item->jurnal->panggil_ortu ? 'checked' : '' }}>
                                                                <label class="form-check-label small fw-bold" for="panggilOrtuEdit{{ $item->key }}">
                                                                    Panggil Orang Tua / Wali
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3" id="wrapJadwalPertemuanEdit{{ $item->key }}" style="display: {{ ($item->jurnal->panggil_ortu || $item->jurnal->tingkat_pelanggaran === 'Berat') ? '' : 'none' }};">
                                                        <label class="form-label text-muted small fw-bold">Jadwal Pertemuan dengan Orang Tua <span class="text-danger">*</span></label>
                                                        <input type="datetime-local" name="jadwal_pertemuan_ortu" id="jadwalPertemuanEdit{{ $item->key }}" class="form-control" value="{{ $item->jurnal->jadwal_pertemuan_ortu ? $item->jurnal->jadwal_pertemuan_ortu->format('Y-m-d\TH:i') : '' }}">
                                                        <small class="text-muted">Perubahan jadwal di sini TIDAK mengirim ulang WA ke orang tua -- hanya memperbarui catatan.</small>
                                                    </div>
                                                    <div class="alert alert-info small mb-0">
                                                        <i class="fas fa-circle-info me-1"></i>
                                                        Mengedit jurnal ini TIDAK mengirim ulang notifikasi WA/Wali Kelas -- notifikasi hanya terkirim sekali saat jurnal pertama kali disimpan.
                                                    </div>
                                                    <hr>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" value="1" name="butuh_persetujuan_kepsek" id="butuhPersetujuanEdit{{ $item->key }}" onchange="toggleJenisTindakan('Edit{{ $item->key }}')" {{ $item->jurnal->butuh_persetujuan_kepsek ? 'checked' : '' }}>
                                                        <label class="form-check-label small fw-bold text-danger" for="butuhPersetujuanEdit{{ $item->key }}">
                                                            Kasus ini mengusulkan tindakan disipliner berat (DO/Skorsing) &ndash; butuh persetujuan Kepsek
                                                        </label>
                                                    </div>
                                                    <div class="mb-3" id="wrapJenisTindakanEdit{{ $item->key }}" style="display: {{ $item->jurnal->butuh_persetujuan_kepsek ? '' : 'none' }};">
                                                        <label class="form-label text-muted small fw-bold">Jenis Tindakan yang Diusulkan <span class="text-danger">*</span></label>
                                                        <select name="jenis_tindakan_diusulkan" id="jenisTindakanEdit{{ $item->key }}" class="form-select">
                                                            <option value="">-- Pilih Jenis Tindakan --</option>
                                                            @foreach(['Skorsing', 'Dikeluarkan (DO)', 'Lainnya'] as $opt)
                                                                <option value="{{ $opt }}" {{ $item->jurnal->jenis_tindakan_diusulkan === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                                            @endforeach
                                                        </select>
                                                        @if($item->jurnal->butuh_persetujuan_kepsek)
                                                            <small class="text-muted">
                                                                @if(!$item->jurnal->status_persetujuan_kepsek || $item->jurnal->status_persetujuan_kepsek === 'Menunggu')
                                                                    Usulan ini masih menunggu keputusan Kepsek.
                                                                @else
                                                                    Kalau centang ini dilepas lalu disimpan, kasus akan hilang dari menu Kasus Darurat Kepsek. Kalau tetap dicentang, status persetujuan yang sudah ada ({{ $item->jurnal->status_persetujuan_kepsek }}) tidak berubah.
                                                                @endif
                                                            </small>
                                                        @else
                                                            <small class="text-muted">Kalau baru dicentang di sini, Kepsek akan mendapat notifikasi kasus darurat baru untuk ditinjau.</small>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="modal-footer bg-light border-0">
                                                    <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- Modal Isi Jurnal (hanya muncul untuk antrean yang belum diisi jurnalnya) -->
                                @if(!$item->jurnal && $item->tipe === 'antrian')
                                <div class="modal fade" id="modalJurnal{{ $item->key }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable text-start">
                                        <div class="modal-content border-0 shadow">
                                            <div class="modal-header bg-success text-white">
                                                <h5 class="modal-title fw-bold"><i class="fas fa-clipboard-check me-2"></i>Tulis Jurnal Konseling</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="{{ route('gurubk.rekap.jurnal.store', $item->antrian_id) }}" method="POST">
                                                @csrf
                                                <div class="modal-body p-4">
                                                    <div class="alert alert-light border mb-4">
                                                        <strong>Siswa:</strong> {{ $item->siswa->user->name ?? 'Anonim' }} <br>
                                                        <strong>Keperluan Awal:</strong> {{ $item->keperluan }}
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label text-muted small fw-bold">Kategori Masalah</label>
                                                            <select name="kategori_masalah" class="form-select" required>
                                                                <option value="Pribadi">Pribadi</option>
                                                                <option value="Sosial">Sosial</option>
                                                                <option value="Belajar">Belajar</option>
                                                                <option value="Karir">Karir</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label text-muted small fw-bold">Pendekatan / Teknik</label>
                                                            <input type="text" name="pendekatan_teknik" class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted small fw-bold">Uraian Masalah</label>
                                                        <textarea name="uraian_masalah" class="form-control" rows="3" required></textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted small fw-bold">RTL</label>
                                                        <textarea name="rencana_tindak_lanjut" class="form-control" rows="2" required></textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted small fw-bold">Status Kasus</label>
                                                        <select name="status_kasus" class="form-select" required>
                                                            <option value="Selesai">Selesai (Tuntas)</option>
                                                            <option value="Dipantau">Dipantau</option>
                                                            <option value="Referal">Referal</option>
                                                        </select>
                                                    </div>
                                                    <hr>
                                                    <div class="row align-items-end">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label text-muted small fw-bold">Tingkat Pelanggaran</label>
                                                            <select name="tingkat_pelanggaran" class="form-select" onchange="toggleJadwalPertemuan('{{ $item->key }}')" id="tingkatPelanggaran{{ $item->key }}">
                                                                <option value="">-- Bukan Kasus Pelanggaran --</option>
                                                                <option value="Ringan">Ringan</option>
                                                                <option value="Sedang">Sedang</option>
                                                                <option value="Berat">Berat</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" value="1" name="panggil_ortu" id="panggilOrtu{{ $item->key }}" onchange="toggleJadwalPertemuan('{{ $item->key }}')">
                                                                <label class="form-check-label small fw-bold" for="panggilOrtu{{ $item->key }}">
                                                                    Panggil Orang Tua / Wali
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3" id="wrapJadwalPertemuan{{ $item->key }}" style="display:none;">
                                                        <label class="form-label text-muted small fw-bold">Jadwal Pertemuan dengan Orang Tua <span class="text-danger">*</span></label>
                                                        <input type="datetime-local" name="jadwal_pertemuan_ortu" id="jadwalPertemuan{{ $item->key }}" class="form-control">
                                                        <small class="text-muted">Tanggal & jam ini akan disertakan langsung di pesan WA supaya orang tua tidak bingung kapan harus datang.</small>
                                                    </div>
                                                    <div class="alert alert-info small mb-0">
                                                        <i class="fas fa-circle-info me-1"></i>
                                                        WA ke orang tua HANYA terkirim jika "Panggil Orang Tua/Wali" dicentang, atau tingkat pelanggaran diisi "Berat". Isi jadwal pertemuan agar tercantum di pesan WA.
                                                    </div>
                                                    <hr>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" value="1" name="butuh_persetujuan_kepsek" id="butuhPersetujuan{{ $item->key }}" onchange="toggleJenisTindakan('{{ $item->key }}')">
                                                        <label class="form-check-label small fw-bold text-danger" for="butuhPersetujuan{{ $item->key }}">
                                                            Kasus ini mengusulkan tindakan disipliner berat (DO/Skorsing) &ndash; butuh persetujuan Kepsek
                                                        </label>
                                                    </div>
                                                    <div class="mb-3" id="wrapJenisTindakan{{ $item->key }}" style="display:none;">
                                                        <label class="form-label text-muted small fw-bold">Jenis Tindakan yang Diusulkan <span class="text-danger">*</span></label>
                                                        <select name="jenis_tindakan_diusulkan" id="jenisTindakan{{ $item->key }}" class="form-select">
                                                            <option value="">-- Pilih Jenis Tindakan --</option>
                                                            <option value="Skorsing">Skorsing</option>
                                                            <option value="Dikeluarkan (DO)">Dikeluarkan (DO)</option>
                                                            <option value="Lainnya">Lainnya</option>
                                                        </select>
                                                        <small class="text-muted">Kepsek akan mendapat notifikasi kasus darurat dan meninjau usulan ini. Detail konseling tetap rahasia.</small>
                                                    </div>
                                                </div>
                                                <div class="modal-footer bg-light border-0">
                                                    <button type="submit" class="btn btn-success px-4">Simpan Jurnal</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endif
            @endforeach

            {{-- PERBAIKAN (28 Juli 2026, revisi ke-3): kartu "Siswa Poin
                 Tertinggi", dulu ada di halaman "Data Pelanggaran" yang
                 terpisah, sekarang ditampilkan di sini karena datanya sudah
                 jadi satu dengan Jurnal BK. --}}
            @if($rekapPoin->isNotEmpty())
            <div class="card shadow-sm border-0 mt-3">
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-3"><i class="fas fa-ranking-star text-warning me-2"></i>Siswa Poin Pelanggaran Tertinggi</h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>Siswa</th>
                                    <th>Kelas</th>
                                    <th>Jumlah Kasus</th>
                                    <th>Total Poin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rekapPoin as $r)
                                <tr>
                                    <td>
                                        <a href="{{ route('gurubk.rekap.siswa', $r->siswa->id) }}" class="text-decoration-none fw-bold text-dark">
                                            {{ $r->siswa->user->name ?? 'Anonim' }}
                                        </a>
                                    </td>
                                    <td>{{ $r->siswa->kelas->nama_kelas ?? '-' }}</td>
                                    <td>{{ $r->jumlah_kasus }}</td>
                                    <td><span class="badge bg-warning text-dark">{{ $r->total_poin }} poin</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Modal Tambah Jurnal Individu (konseling di luar antrean sesi) -->
            <div class="modal fade" id="modalTambahJurnalIndividu" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title fw-bold"><i class="fas fa-clipboard-check me-2"></i>Tambah Jurnal Konseling Individu</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('gurubk.rekap.jurnal.store-manual') }}" method="POST">
                            @csrf
                            <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                                <div class="alert alert-light border small mb-4">
                                    Gunakan form ini untuk mencatat konseling individu yang terjadi <strong>di luar antrean sesi</strong>
                                    (mis. konseling dadakan / dipanggil langsung).
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted small fw-bold">Kelas</label>
                                        <select id="filterKelasJurnalIndividu" class="form-select">
                                            <option value="">-- Pilih Kelas --</option>
                                            @foreach($kelasKelolaan as $kelas)
                                                <option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted small fw-bold">Siswa</label>
                                        <select name="siswa_id" id="selectSiswaJurnalIndividu" class="form-select" required disabled>
                                            <option value="">-- Pilih Kelas Terlebih Dahulu --</option>
                                            @foreach($siswaKelolaan as $siswa)
                                                <option value="{{ $siswa->id }}" data-kelas-id="{{ $siswa->kelas->id ?? '' }}" hidden>{{ $siswa->user->name ?? 'Anonim' }} ({{ $siswa->kelas->nama_kelas ?? '-' }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted small fw-bold">Tanggal Konseling</label>
                                        <input type="date" name="tanggal_konseling" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted small fw-bold">Keperluan Awal</label>
                                        <input type="text" name="keperluan" class="form-control" placeholder="Contoh: Konsultasi masalah pribadi" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted small fw-bold">Kategori Masalah</label>
                                        <select name="kategori_masalah" class="form-select" required>
                                            <option value="Pribadi">Pribadi</option>
                                            <option value="Sosial">Sosial</option>
                                            <option value="Belajar">Belajar</option>
                                            <option value="Karir">Karir</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted small fw-bold">Pendekatan / Teknik</label>
                                        <input type="text" name="pendekatan_teknik" class="form-control">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">Uraian Masalah</label>
                                    <textarea name="uraian_masalah" class="form-control" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">RTL</label>
                                    <textarea name="rencana_tindak_lanjut" class="form-control" rows="2" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">Status Kasus</label>
                                    <select name="status_kasus" class="form-select" required>
                                        <option value="Selesai">Selesai (Tuntas)</option>
                                        <option value="Dipantau">Dipantau</option>
                                        <option value="Referal">Referal</option>
                                    </select>
                                </div>
                                <hr>
                                <div class="row align-items-end">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted small fw-bold">Tingkat Pelanggaran</label>
                                        <select name="tingkat_pelanggaran" class="form-select" id="tingkatPelanggaranManual" onchange="toggleJadwalPertemuan('Manual')">
                                            <option value="">-- Bukan Kasus Pelanggaran --</option>
                                            <option value="Ringan">Ringan</option>
                                            <option value="Sedang">Sedang</option>
                                            <option value="Berat">Berat</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="1" name="panggil_ortu" id="panggilOrtuManual" onchange="toggleJadwalPertemuan('Manual')">
                                            <label class="form-check-label small fw-bold" for="panggilOrtuManual">
                                                Panggil Orang Tua / Wali
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3" id="wrapJadwalPertemuanManual" style="display:none;">
                                    <label class="form-label text-muted small fw-bold">Jadwal Pertemuan dengan Orang Tua <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="jadwal_pertemuan_ortu" id="jadwalPertemuanManual" class="form-control">
                                    <small class="text-muted">Tanggal & jam ini akan disertakan langsung di pesan WA supaya orang tua tidak bingung kapan harus datang.</small>
                                </div>
                                <div class="alert alert-info small mb-0">
                                    <i class="fas fa-circle-info me-1"></i>
                                    WA ke orang tua HANYA terkirim jika "Panggil Orang Tua/Wali" dicentang, atau tingkat pelanggaran diisi "Berat". Isi jadwal pertemuan agar tercantum di pesan WA.
                                </div>
                                <hr>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" value="1" name="butuh_persetujuan_kepsek" id="butuhPersetujuanManual" onchange="toggleJenisTindakan('Manual')">
                                    <label class="form-check-label small fw-bold text-danger" for="butuhPersetujuanManual">
                                        Kasus ini mengusulkan tindakan disipliner berat (DO/Skorsing) &ndash; butuh persetujuan Kepsek
                                    </label>
                                </div>
                                <div class="mb-3" id="wrapJenisTindakanManual" style="display:none;">
                                    <label class="form-label text-muted small fw-bold">Jenis Tindakan yang Diusulkan <span class="text-danger">*</span></label>
                                    <select name="jenis_tindakan_diusulkan" id="jenisTindakanManual" class="form-select">
                                        <option value="">-- Pilih Jenis Tindakan --</option>
                                        <option value="Skorsing">Skorsing</option>
                                        <option value="Dikeluarkan (DO)">Dikeluarkan (DO)</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                    <small class="text-muted">Kepsek akan mendapat notifikasi kasus darurat dan meninjau usulan ini. Detail konseling tetap rahasia.</small>
                                </div>
                            </div>
                            <div class="modal-footer bg-light border-0">
                                <button type="submit" class="btn btn-success px-4">Simpan Jurnal</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Catat Pelanggaran (PERBAIKAN 28 Juli 2026, revisi ke-3:
                 digabung ke Jurnal BK, dulu halaman "Data Pelanggaran" terpisah) -->
            <div class="modal fade" id="modalCatatPelanggaran" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title fw-bold"><i class="fas fa-triangle-exclamation me-2"></i>Catat Pelanggaran</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('gurubk.rekap.pelanggaran.store') }}" method="POST">
                            @csrf
                            <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted small fw-bold">Kelas</label>
                                        <select id="filterKelasPelanggaranJurnal" class="form-select">
                                            <option value="">-- Pilih Kelas --</option>
                                            @foreach($kelasKelolaan as $kelas)
                                                <option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted small fw-bold">Siswa</label>
                                        <select name="siswa_id" id="selectSiswaPelanggaranJurnal" class="form-select" required disabled>
                                            <option value="">-- Pilih Kelas Terlebih Dahulu --</option>
                                            @foreach($siswaKelolaan as $siswa)
                                                <option value="{{ $siswa->id }}" data-kelas-id="{{ $siswa->kelas->id ?? '' }}" hidden>{{ $siswa->user->name ?? 'Anonim' }} ({{ $siswa->kelas->nama_kelas ?? '-' }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted small fw-bold">Tanggal Kejadian</label>
                                        <input type="date" name="tanggal_kejadian" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted small fw-bold">Kategori</label>
                                        <select name="kategori" id="kategoriPelanggaranJurnal" class="form-select" required onchange="isiPoinOtomatisJurnal(this.value); toggleJadwalPelanggaranJurnal();">
                                            <option value="">-- Pilih Kategori --</option>
                                            <option value="Ringan">Ringan</option>
                                            <option value="Sedang">Sedang</option>
                                            <option value="Berat">Berat</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">Poin</label>
                                    <input type="number" name="poin" id="poinPelanggaranJurnal" class="form-control" min="0" max="100" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">Deskripsi Pelanggaran</label>
                                    <textarea name="deskripsi" class="form-control" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">Tindak Lanjut (opsional)</label>
                                    <textarea name="tindak_lanjut" class="form-control" rows="2"></textarea>
                                </div>
                                <div class="mb-3" id="wrapJadwalPelanggaranJurnal" style="display:none;">
                                    <label class="form-label text-muted small fw-bold">Jadwal Pertemuan dengan Orang Tua <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="jadwal_pertemuan_ortu" id="jadwalPelanggaranJurnal" class="form-control">
                                    <small class="text-muted">Tanggal & jam ini akan disertakan langsung di pesan WA supaya orang tua tidak bingung kapan harus datang.</small>
                                </div>
                                <div class="alert alert-info small mb-0">
                                    <i class="fas fa-circle-info me-1"></i>
                                    Untuk kategori <strong>Berat</strong>, WA otomatis terkirim ke orang tua &amp; notifikasi ke Wali Kelas -- sama seperti kasus pelanggaran Berat di Jurnal Konseling.
                                </div>
                            </div>
                            <div class="modal-footer bg-light border-0">
                                <button type="submit" class="btn btn-warning px-4">Simpan Pelanggaran</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-harian" role="tabpanel">
            <div class="card shadow-sm border-0 border-top border-4 border-primary">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="m-0 fw-bold text-dark">Daftar Agenda Harian</h6>
                        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalJurnalHarian">
                            <i class="fas fa-plus me-2"></i>Tambah Agenda
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal & Waktu</th>
                                    <th>Jenis Kegiatan</th>
                                    <th>Sasaran / Kelas</th>
                                    <th>Deskripsi</th>
                                    <th>Hambatan/Catatan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($jurnalHarian as $jh)
                                <tr>
                                    <td>{{ $jh->tanggal_waktu->translatedFormat('d M Y, H:i') }}</td>
                                    <td>{{ $jh->jenis_kegiatan }}</td>
                                    <td>{{ $jh->sasaran ?: '-' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($jh->deskripsi_kegiatan, 60) }}</td>
                                    <td>{{ $jh->hambatan_catatan ? \Illuminate\Support\Str::limit($jh->hambatan_catatan, 40) : '-' }}</td>
                                    <td>
                                        <form action="{{ route('gurubk.rekap.harian.destroy', $jh->id) }}" method="POST" onsubmit="return confirm('Hapus jurnal harian ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="text-center py-5 text-muted bg-light rounded">
                                            <i class="fas fa-calendar-day fa-3x mb-3 opacity-25"></i>
                                            <p class="mb-0">Belum ada agenda harian yang dicatat.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-kelompok" role="tabpanel">
            <div class="card shadow-sm border-0 border-top border-4 border-success">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="m-0 fw-bold text-dark">Rekap Bimbingan Kelompok</h6>
                        <button class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#modalBimbinganKelompok">
                            <i class="fas fa-plus me-2"></i>Buat Laporan Baru
                        </button>
                    </div>
                    @if($bimbinganKelompok->isEmpty())
                    <div class="text-center py-5 text-muted bg-light rounded">
                        <i class="fas fa-users fa-3x mb-3 opacity-25"></i>
                        <p class="mb-0">Belum ada laporan Bimbingan Kelompok yang dicatat.</p>
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="py-3">Tanggal</th>
                                    <th class="py-3">Topik</th>
                                    <th class="py-3">Anggota</th>
                                    <th class="py-3">Kesimpulan & Hasil</th>
                                    <th class="py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bimbinganKelompok as $bk)
                                <tr>
                                    <td class="py-3 small">{{ $bk->tanggal_pelaksanaan->translatedFormat('d M Y, H:i') }}</td>
                                    <td class="py-3 fw-bold">{{ $bk->topik }}</td>
                                    <td class="py-3">{{ $bk->daftar_anggota ? \Illuminate\Support\Str::limit($bk->daftar_anggota, 50) : '-' }}</td>
                                    <td class="py-3">{{ $bk->kesimpulan_hasil ? \Illuminate\Support\Str::limit($bk->kesimpulan_hasil, 60) : '-' }}</td>
                                    <td class="py-3 text-center">
                                        <form action="{{ route('gurubk.rekap.kelompok.destroy', $bk->id) }}" method="POST" onsubmit="return confirm('Hapus laporan Bimbingan Kelompok ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-klasikal" role="tabpanel">
            <div class="card shadow-sm border-0 border-top border-4 border-info">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="m-0 fw-bold text-dark">Rekap Layanan Klasikal (Masuk Kelas)</h6>
                        <button class="btn btn-info text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#modalKlasikal">
                            <i class="fas fa-plus me-2"></i>Catat Layanan Klasikal
                        </button>
                    </div>
                    @if($layananKlasikal->isEmpty())
                    <div class="text-center py-5 text-muted bg-light rounded">
                        <i class="fas fa-chalkboard-teacher fa-3x mb-3 opacity-25"></i>
                        <p class="mb-0">Belum ada Layanan Klasikal yang dicatat.</p>
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="py-3">Tanggal</th>
                                    <th class="py-3">Kelas Target</th>
                                    <th class="py-3">Metode</th>
                                    <th class="py-3">Materi</th>
                                    <th class="py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($layananKlasikal as $lk)
                                <tr>
                                    <td class="py-3 small">{{ $lk->tanggal_pelaksanaan->translatedFormat('d M Y, H:i') }}</td>
                                    <td class="py-3 fw-bold">{{ $lk->kelas_target }}</td>
                                    <td class="py-3">{{ $lk->metode_penyampaian }}</td>
                                    <td class="py-3">{{ $lk->materi_kompetensi ?: '-' }}</td>
                                    <td class="py-3 text-center">
                                        <form action="{{ route('gurubk.rekap.klasikal.destroy', $lk->id) }}" method="POST" onsubmit="return confirm('Hapus catatan Layanan Klasikal ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-homevisit" role="tabpanel">
            <div class="card shadow-sm border-0 border-top border-4 border-warning">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="m-0 fw-bold text-dark">Arsip Kunjungan Rumah (Home Visit)</h6>
                        <button class="btn btn-warning text-dark fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalHomeVisit">
                            <i class="fas fa-plus me-2"></i>Catat Kunjungan
                        </button>
                    </div>

                    @if($kunjunganRumah->isEmpty())
                    <div class="text-center py-5 text-muted bg-light rounded">
                        <i class="fas fa-home fa-3x mb-3 opacity-25"></i>
                        <p>Belum ada data kunjungan rumah.</p>
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="py-3">Siswa</th>
                                    <th class="py-3">Tanggal</th>
                                    <th class="py-3">Wali yang Ditemui</th>
                                    <th class="py-3 text-center">Status</th>
                                    <th class="py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($kunjunganRumah as $kr)
                                <tr>
                                    <td class="py-3">
                                        <div class="fw-bold">{{ optional($kr->siswa->user)->name ?? '-' }}</div>
                                        <div class="text-muted small">{{ optional($kr->siswa->kelas)->nama_kelas ?? '-' }}</div>
                                    </td>
                                    <td class="py-3 small">{{ \Illuminate\Support\Carbon::parse($kr->tanggal_kunjungan)->translatedFormat('d M Y') }}</td>
                                    <td class="py-3">{{ $kr->nama_wali_ditemui ?: '-' }}</td>
                                    <td class="py-3 text-center">
                                        @if($kr->status === 'Direncanakan')
                                            <span class="badge bg-warning text-dark">Direncanakan</span>
                                        @elseif($kr->status === 'Terlaksana')
                                            <span class="badge bg-success">Terlaksana</span>
                                        @else
                                            <span class="badge bg-danger">Dibatalkan</span>
                                        @endif
                                        @if($kr->dokumentasi_path)
                                            <a href="{{ asset('storage/' . $kr->dokumentasi_path) }}" target="_blank" rel="noopener" class="d-block small mt-1">
                                                <i class="fas fa-paperclip me-1"></i>Lihat Bukti
                                            </a>
                                        @endif
                                    </td>
                                    <td class="py-3 text-center">
                                        @if($kr->status === 'Direncanakan')
                                            <button class="btn btn-sm btn-success mb-1" data-bs-toggle="modal" data-bs-target="#modalSelesaiKunjungan{{ $kr->id }}">
                                                <i class="fas fa-check"></i> Tandai Selesai
                                            </button>
                                            <form action="{{ route('gurubk.kunjungan-rumah.batalkan', $kr->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Batalkan rencana kunjungan ini?');">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-danger mb-1"><i class="fas fa-times"></i></button>
                                            </form>
                                        @endif
                                        <button class="btn btn-sm btn-outline-primary mb-1" data-bs-toggle="modal" data-bs-target="#modalEditKunjungan{{ $kr->id }}">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <form action="{{ route('gurubk.kunjungan-rumah.destroy', $kr->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data kunjungan rumah ini secara permanen?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger mb-1"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Modal Edit Kunjungan Rumah -->
                                <div class="modal fade" id="modalEditKunjungan{{ $kr->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                        <div class="modal-content border-0 shadow">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title fw-bold"><i class="fas fa-pen me-2"></i>Edit Kunjungan Rumah</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="{{ route('gurubk.kunjungan-rumah.update', $kr->id) }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-body p-4">
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label text-muted small fw-bold">Kelas</label>
                                                            <select id="filterKelasEditKunjungan{{ $kr->id }}" class="form-select">
                                                                <option value="">-- Pilih Kelas --</option>
                                                                @foreach($kelasKelolaan as $kelas)
                                                                    <option value="{{ $kelas->id }}" @selected(optional($kr->siswa->kelas)->id == $kelas->id)>{{ $kelas->nama_kelas }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label text-muted small fw-bold">Siswa</label>
                                                            <select name="siswa_id" id="selectSiswaEditKunjungan{{ $kr->id }}" class="form-select" required>
                                                                @foreach($siswaKelolaan as $s)
                                                                    <option value="{{ $s->id }}" data-kelas-id="{{ $s->kelas->id ?? '' }}" @selected($kr->siswa_id == $s->id) @if(optional($s->kelas)->id != optional($kr->siswa->kelas)->id) hidden @endif>{{ optional($s->user)->name ?? '-' }} ({{ optional($s->kelas)->nama_kelas ?? '-' }})</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label text-muted small fw-bold">Tanggal Kunjungan</label>
                                                            <input type="date" name="tanggal_kunjungan" class="form-control" value="{{ \Illuminate\Support\Carbon::parse($kr->tanggal_kunjungan)->format('Y-m-d') }}" required>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted small fw-bold">Nama Orang Tua / Wali yang Ditemui</label>
                                                        <input type="text" name="nama_wali_ditemui" class="form-control" value="{{ $kr->nama_wali_ditemui }}">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted small fw-bold">Alasan Kunjungan</label>
                                                        <textarea name="alasan_kunjungan" class="form-control" rows="2" required>{{ $kr->alasan_kunjungan }}</textarea>
                                                    </div>
                                                    @if($kr->status === 'Terlaksana')
                                                    <hr>
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted small fw-bold">Hasil Observasi & Wawancara</label>
                                                        <textarea name="hasil_observasi" class="form-control" rows="3" required>{{ $kr->hasil_observasi }}</textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted small fw-bold">Kesepakatan Bersama</label>
                                                        <textarea name="kesepakatan_bersama" class="form-control" rows="2">{{ $kr->kesepakatan_bersama }}</textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted small fw-bold">Ganti Bukti Kunjungan (opsional)</label>
                                                        <input type="file" name="dokumentasi" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                                                        @if($kr->dokumentasi_path)
                                                            <div class="form-text">Sudah ada file tersimpan. Unggah file baru kalau ingin menggantinya.</div>
                                                        @endif
                                                    </div>
                                                    @endif
                                                </div>
                                                <div class="modal-footer bg-light border-0">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal fade" id="modalSelesaiKunjungan{{ $kr->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                        <div class="modal-content border-0 shadow">
                                            <div class="modal-header bg-warning text-dark">
                                                <h5 class="modal-title fw-bold">Hasil Kunjungan Rumah</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="{{ route('gurubk.kunjungan-rumah.terlaksana', $kr->id) }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <div class="modal-body p-4">
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted small fw-bold">Hasil Observasi & Wawancara</label>
                                                        <textarea name="hasil_observasi" class="form-control" rows="3" required placeholder="Kondisi lingkungan atau penjelasan dari pihak keluarga..."></textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted small fw-bold">Kesepakatan Bersama</label>
                                                        <textarea name="kesepakatan_bersama" class="form-control" rows="2" placeholder="Solusi yang disetujui antara sekolah dan keluarga..."></textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted small fw-bold">Bukti Kunjungan (Foto/Surat)</label>
                                                        <input type="file" name="dokumentasi" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                                                    </div>
                                                </div>
                                                <div class="modal-footer bg-light border-0">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-warning fw-bold px-4">Simpan Dokumen</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    <div class="modal fade" id="modalJurnalHarian" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">Form Jurnal Harian</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('gurubk.rekap.harian.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">Tanggal & Waktu</label>
                                <input type="datetime-local" name="tanggal_waktu" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">Jenis Kegiatan</label>
                                <select name="jenis_kegiatan" class="form-select" required>
                                    <option value="Layanan Kelas (Klasikal)" @selected(old('jenis_kegiatan') === 'Layanan Kelas (Klasikal)')>Layanan Kelas (Klasikal)</option>
                                    <option value="Konseling / Bimbingan" @selected(old('jenis_kegiatan') === 'Konseling / Bimbingan')>Konseling / Bimbingan</option>
                                    <option value="Rapat / Koordinasi" @selected(old('jenis_kegiatan') === 'Rapat / Koordinasi')>Rapat / Koordinasi</option>
                                    <option value="Administrasi BK" @selected(old('jenis_kegiatan') === 'Administrasi BK')>Administrasi BK</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Sasaran / Kelas</label>
                            <input type="text" name="sasaran" class="form-control" value="{{ old('sasaran') }}" placeholder="Siapa yang menjadi sasaran kegiatan ini?">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Deskripsi Kegiatan</label>
                            <textarea name="deskripsi_kegiatan" class="form-control" rows="3" placeholder="Uraian aktivitas..." required>{{ old('deskripsi_kegiatan') }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Hambatan / Catatan</label>
                            <textarea name="hambatan_catatan" class="form-control" rows="2" placeholder="Kendala yang dihadapi...">{{ old('hambatan_catatan') }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="submit" class="btn btn-primary px-4">Simpan Jurnal Harian</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalBimbinganKelompok" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">Form Bimbingan Kelompok</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('gurubk.rekap.kelompok.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Tanggal & Waktu Pelaksanaan</label>
                            <input type="datetime-local" name="tanggal_pelaksanaan" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Topik / Tema Pembahasan</label>
                            <input type="text" name="topik" class="form-control" placeholder="Contoh: Tips Lolos SNBP, Pencegahan Bullying..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Daftar Anggota (Siswa)</label>
                            <textarea name="daftar_anggota" class="form-control" rows="2" placeholder="Tuliskan nama-nama siswa yang mengikuti sesi ini..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Dinamika Kelompok</label>
                            <textarea name="dinamika_kelompok" class="form-control" rows="3" placeholder="Bagaimana keaktifan dan respons anggota selama sesi?"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Kesimpulan & Hasil</label>
                            <textarea name="kesimpulan_hasil" class="form-control" rows="2" placeholder="Kesepakatan bersama..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success px-4">Simpan Laporan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalKlasikal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title fw-bold">Form Layanan Klasikal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('gurubk.rekap.klasikal.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Tanggal & Waktu Pelaksanaan</label>
                            <input type="datetime-local" name="tanggal_pelaksanaan" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">Kelas Target</label>
                                <input type="text" name="kelas_target" class="form-control" placeholder="Contoh: XII MIPA 1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">Metode Penyampaian</label>
                                <select name="metode_penyampaian" class="form-select" required>
                                    <option value="Ceramah & Tanya Jawab">Ceramah & Tanya Jawab</option>
                                    <option value="Diskusi Kelompok">Diskusi Kelompok</option>
                                    <option value="Games / Ice Breaking">Games / Ice Breaking</option>
                                    <option value="Pemutaran Video / Media">Pemutaran Video / Media</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Materi / Kompetensi Dasar</label>
                            <input type="text" name="materi_kompetensi" class="form-control" placeholder="Judul materi berdasarkan program tahunan...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Evaluasi Proses (Respons Siswa)</label>
                            <textarea name="evaluasi_proses" class="form-control" rows="3" placeholder="Catatan mengenai suasana kelas dan tingkat antusiasme siswa..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-info text-white px-4">Simpan Layanan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalHomeVisit" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold">Form Kunjungan Rumah (Home Visit)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('gurubk.kunjungan-rumah.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">Kelas</label>
                                <select id="filterKelasHomeVisit" class="form-select">
                                    <option value="">-- Pilih Kelas --</option>
                                    @foreach($kelasKelolaan as $kelas)
                                        <option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">Siswa</label>
                                <select name="siswa_id" id="selectSiswaHomeVisit" class="form-select" required disabled>
                                    <option value="">-- Pilih Kelas Terlebih Dahulu --</option>
                                    @foreach($siswaKelolaan as $s)
                                        <option value="{{ $s->id }}" data-kelas-id="{{ $s->kelas->id ?? '' }}" hidden>{{ optional($s->user)->name ?? '-' }} ({{ optional($s->kelas)->nama_kelas ?? '-' }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">Tanggal Kunjungan</label>
                                <input type="date" name="tanggal_kunjungan" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">Nama Orang Tua / Wali yang Ditemui</label>
                                <input type="text" name="nama_wali_ditemui" class="form-control" placeholder="Nama wali yang ditemui">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Alasan Kunjungan</label>
                            <textarea name="alasan_kunjungan" class="form-control" rows="2" required placeholder="Latar belakang dilakukannya home visit..."></textarea>
                        </div>
                        <div class="alert alert-info small mb-0">
                            <i class="fas fa-circle-info me-1"></i>
                            Kunjungan dicatat sebagai "Direncanakan" dulu. Setelah benar-benar dilaksanakan, tandai selesai dari daftar arsip untuk mengisi hasil observasi & dokumentasi.
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning fw-bold px-4">Simpan Rencana Kunjungan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


<script>
    // PERBAIKAN (27 Juli 2026): tab yang sedang aktif (Konseling Individu,
    // Jurnal Harian, Bimbingan Kelompok, Layanan Klasikal, Home Visit) tadinya
    // selalu kembali ke "Konseling Individu" setiap kali form di tab lain
    // disubmit, karena setiap tombol submit memicu reload halaman penuh dan
    // Bootstrap pill selalu mulai dari tab pertama. Sekarang tab terakhir yang
    // aktif disimpan ke sessionStorage tiap kali guru BK berpindah tab, lalu
    // dipulihkan begitu halaman selesai dimuat -- jadi setelah "Simpan Rencana
    // Kunjungan" di tab Home Visit, guru BK tetap berada di tab Home Visit.
    document.addEventListener('DOMContentLoaded', function () {
        var STORAGE_KEY = 'jurnalBK_activeTab';
        var tabButtons = document.querySelectorAll('#jurnalTab button[data-bs-target]');

        tabButtons.forEach(function (btn) {
            btn.addEventListener('shown.bs.tab', function () {
                sessionStorage.setItem(STORAGE_KEY, btn.getAttribute('data-bs-target'));
            });
        });

        var lastTab = sessionStorage.getItem(STORAGE_KEY);
        if (lastTab) {
            var target = document.querySelector('#jurnalTab button[data-bs-target="' + lastTab + '"]');
            if (target && window.bootstrap) {
                new bootstrap.Tab(target).show();
            }
        }
    });

    // Fungsi generik untuk memfilter dropdown "Siswa" berdasarkan "Kelas" yang
    // dipilih -- dipakai di modal Tambah Jurnal Individu, Tambah Home Visit,
    // dan Edit Home Visit, supaya guru BK tidak perlu mencari nama siswa dari
    // ratusan siswa kelolaan sekaligus.
    function initFilterKelasSiswa(filterId, siswaId, modalId) {
        var filterKelas = document.getElementById(filterId);
        var selectSiswa = document.getElementById(siswaId);

        if (!filterKelas || !selectSiswa) {
            return;
        }

        var siswaOptions = Array.prototype.slice.call(selectSiswa.options).filter(function (opt) {
            return opt.value !== '';
        });

        function terapkanFilter(kelasId, resetPilihan) {
            if (!kelasId) {
                siswaOptions.forEach(function (opt) {
                    opt.hidden = true;
                });
                if (resetPilihan) {
                    selectSiswa.value = '';
                }
                selectSiswa.disabled = true;
                if (selectSiswa.options[0]) {
                    selectSiswa.options[0].text = '-- Pilih Kelas Terlebih Dahulu --';
                }
                return;
            }

            var adaSiswa = false;
            siswaOptions.forEach(function (opt) {
                var cocok = opt.getAttribute('data-kelas-id') === kelasId;
                opt.hidden = !cocok;
                if (cocok) {
                    adaSiswa = true;
                }
            });

            selectSiswa.disabled = false;
            if (resetPilihan) {
                selectSiswa.value = '';
            }
            if (selectSiswa.options[0]) {
                selectSiswa.options[0].text = adaSiswa ? '-- Pilih Siswa --' : '-- Tidak Ada Siswa di Kelas Ini --';
            }
        }

        filterKelas.addEventListener('change', function () {
            terapkanFilter(this.value, true);
        });

        // Modal Tambah: reset ke kondisi awal setiap kali modal ditutup, supaya
        // saat dibuka lagi guru BK mulai dari memilih kelas terlebih dahulu.
        // Modal Edit: TIDAK direset (dropdown Siswa tidak punya opsi
        // placeholder kosong karena selalu ada siswa terpilih), cukup
        // dijalankan sekali saat halaman dimuat supaya filter langsung cocok
        // dengan kelas siswa yang sedang diedit.
        var modalEl = modalId ? document.getElementById(modalId) : null;
        var adaPlaceholder = selectSiswa.options[0] && selectSiswa.options[0].value === '';

        if (modalEl && adaPlaceholder) {
            modalEl.addEventListener('hidden.bs.modal', function () {
                filterKelas.value = '';
                terapkanFilter('', true);
            });
        } else {
            // Modal edit: terapkan filter memakai kelas yang sudah terpilih
            // (siswa yang sedang diedit), tanpa mereset pilihan siswa.
            terapkanFilter(filterKelas.value, false);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initFilterKelasSiswa('filterKelasHomeVisit', 'selectSiswaHomeVisit', 'modalHomeVisit');
        initFilterKelasSiswa('filterKelasPelanggaranJurnal', 'selectSiswaPelanggaranJurnal', 'modalCatatPelanggaran');

        @foreach($kunjunganRumah as $kr)
        initFilterKelasSiswa('filterKelasEditKunjungan{{ $kr->id }}', 'selectSiswaEditKunjungan{{ $kr->id }}', null);
        @endforeach
    });

    // PERBAIKAN (28 Juli 2026, revisi ke-3): saran poin otomatis per kategori
    // di modal Catat Pelanggaran (bisa diubah manual oleh Guru BK), dan toggle
    // jadwal pertemuan ortu wajib diisi kalau kategori = Berat (WA otomatis
    // terkirim, sama seperti tingkat_pelanggaran Berat di Jurnal Konseling).
    function isiPoinOtomatisJurnal(kategori) {
        var petaPoin = { 'Ringan': 5, 'Sedang': 15, 'Berat': 30 };
        document.getElementById('poinPelanggaranJurnal').value = petaPoin[kategori] ?? 5;
    }

    function toggleJadwalPelanggaranJurnal() {
        var kategori = document.getElementById('kategoriPelanggaranJurnal');
        var wrap = document.getElementById('wrapJadwalPelanggaranJurnal');
        var input = document.getElementById('jadwalPelanggaranJurnal');

        if (!kategori || !wrap || !input) {
            return;
        }

        var perluJadwal = kategori.value === 'Berat';
        wrap.style.display = perluJadwal ? '' : 'none';
        input.required = perluJadwal;
    }

    // PERBAIKAN: tampilkan & wajibkan input "Jadwal Pertemuan dengan Orang Tua"
    // begitu Guru BK mencentang "Panggil Orang Tua/Wali" ATAU memilih tingkat
    // pelanggaran "Berat" -- sesuai kondisi yang memicu WA otomatis ke orang tua
    // (lihat JurnalLayananController::notifikasiOrtu). Tujuannya supaya jadwal
    // pertemuan selalu terisi dan ikut disebutkan di pesan WA, sehingga orang
    // tua tidak lagi bingung kapan harus datang ke sekolah.
    function toggleJadwalPertemuan(key) {
        var panggilOrtu = document.getElementById('panggilOrtu' + key);
        var tingkatPelanggaran = document.getElementById('tingkatPelanggaran' + key);
        var wrap = document.getElementById('wrapJadwalPertemuan' + key);
        var input = document.getElementById('jadwalPertemuan' + key);

        if (!panggilOrtu || !tingkatPelanggaran || !wrap || !input) {
            return;
        }

        var perluJadwal = panggilOrtu.checked || tingkatPelanggaran.value === 'Berat';

        wrap.style.display = perluJadwal ? '' : 'none';
        input.required = perluJadwal;
    }

    // FASE 8: tampilkan & wajibkan input "Jenis Tindakan yang Diusulkan"
    // begitu Guru BK mencentang "butuh_persetujuan_kepsek", supaya Kepsek
    // selalu tahu jenis tindakan (Skorsing/DO/Lainnya) yang diusulkan.
    function toggleJenisTindakan(key) {
        var checkbox = document.getElementById('butuhPersetujuan' + key);
        var wrap = document.getElementById('wrapJenisTindakan' + key);
        var select = document.getElementById('jenisTindakan' + key);

        if (!checkbox || !wrap || !select) {
            return;
        }

        wrap.style.display = checkbox.checked ? '' : 'none';
        select.required = checkbox.checked;
    }

    // PERBAIKAN (30 Juli 2026): dipanggil dari tombol "Edit" di modal Detail
    // Jurnal Konseling. Sebelumnya tombol itu memakai data-bs-dismiss="modal"
    // + data-bs-toggle="modal" sekaligus pada elemen yang sama, yang membuat
    // Bootstrap menutup modal Detail dan membuka modal Edit di waktu
    // BERSAMAAN -- race condition ini membuat backdrop modal & class
    // "modal-open" di <body> tidak sinkron sehingga modal terlihat macet
    // (tidak bisa ditutup lagi) dan guru BK terpaksa me-refresh halaman.
    // Fungsi ini menunggu modal Detail benar-benar selesai tertutup
    // (event "hidden.bs.modal") sebelum membuka modal Edit.
    function bukaModalEditDariDetail(key) {
        var modalDetailEl = document.getElementById('modalDetailJurnal' + key);
        var modalEditEl = document.getElementById('modalEditJurnal' + key);

        if (!modalEditEl || !window.bootstrap) {
            return;
        }

        function bukaModalEdit() {
            toggleJadwalPertemuan('Edit' + key);
            toggleJenisTindakan('Edit' + key);
            bootstrap.Modal.getOrCreateInstance(modalEditEl).show();
        }

        var modalDetailInstance = modalDetailEl ? bootstrap.Modal.getInstance(modalDetailEl) : null;

        if (modalDetailEl && modalDetailInstance) {
            modalDetailEl.addEventListener('hidden.bs.modal', bukaModalEdit, { once: true });
            modalDetailInstance.hide();
        } else {
            // Modal Detail tidak sedang terbuka (mis. dipanggil dari tempat
            // lain) -- langsung buka modal Edit saja.
            bukaModalEdit();
        }
    }

    // Filter dropdown "Siswa" pada modal Tambah Jurnal Konseling Individu
    // berdasarkan "Kelas" yang dipilih terlebih dahulu.
    document.addEventListener('DOMContentLoaded', function () {
        var filterKelas = document.getElementById('filterKelasJurnalIndividu');
        var selectSiswa = document.getElementById('selectSiswaJurnalIndividu');

        if (!filterKelas || !selectSiswa) {
            return;
        }

        var siswaOptions = Array.prototype.slice.call(selectSiswa.options).filter(function (opt) {
            return opt.value !== '';
        });

        function resetSiswaSelect(placeholderText, isDisabled) {
            selectSiswa.value = '';
            selectSiswa.disabled = isDisabled;
            selectSiswa.options[0].text = placeholderText;
        }

        filterKelas.addEventListener('change', function () {
            var kelasId = this.value;

            if (!kelasId) {
                siswaOptions.forEach(function (opt) {
                    opt.hidden = true;
                });
                resetSiswaSelect('-- Pilih Kelas Terlebih Dahulu --', true);
                return;
            }

            var adaSiswa = false;
            siswaOptions.forEach(function (opt) {
                var cocok = opt.getAttribute('data-kelas-id') === kelasId;
                opt.hidden = !cocok;
                if (cocok) {
                    adaSiswa = true;
                }
            });

            resetSiswaSelect(adaSiswa ? '-- Pilih Siswa --' : '-- Tidak Ada Siswa di Kelas Ini --', false);
        });

        // Reset form filter setiap kali modal ditutup, supaya saat dibuka lagi
        // guru BK mulai dari memilih kelas terlebih dahulu.
        var modalEl = document.getElementById('modalTambahJurnalIndividu');
        if (modalEl) {
            modalEl.addEventListener('hidden.bs.modal', function () {
                filterKelas.value = '';
                siswaOptions.forEach(function (opt) {
                    opt.hidden = true;
                });
                resetSiswaSelect('-- Pilih Kelas Terlebih Dahulu --', true);
            });
        }

        // FITUR JEMBATAN KE KASUS DARURAT (23 Juli 2026): kalau datang dari
        // tombol "Usulkan ke Kepsek" di halaman Data Pelanggaran (bawa query
        // string usulkan_siswa & usulkan_kelas), otomatis buka modal
        // "Tambah Jurnal Individu" dengan kelas, siswa, dan tingkat
        // pelanggaran "Berat" sudah terisi, plus checkbox persetujuan Kepsek
        // sudah dicentang. Guru BK tinggal lengkapi uraian konseling & submit
        // -- tetap keputusan manual, bukan otomatis terkirim ke Kepsek.
        @if(request('usulkan_siswa'))
        (function () {
            var usulkanKelasId = '{{ request('usulkan_kelas') }}';
            var usulkanSiswaId = '{{ request('usulkan_siswa') }}';

            if (filterKelas && usulkanKelasId) {
                filterKelas.value = usulkanKelasId;
                filterKelas.dispatchEvent(new Event('change'));
            }
            if (selectSiswa && usulkanSiswaId) {
                selectSiswa.value = usulkanSiswaId;
            }

            var tingkatPelanggaran = document.getElementById('tingkatPelanggaranManual');
            if (tingkatPelanggaran) {
                tingkatPelanggaran.value = 'Berat';
                toggleJadwalPertemuan('Manual');
            }

            var butuhPersetujuan = document.getElementById('butuhPersetujuanManual');
            if (butuhPersetujuan) {
                butuhPersetujuan.checked = true;
                toggleJenisTindakan('Manual');
            }

            var modalEl = document.getElementById('modalTambahJurnalIndividu');
            if (modalEl && window.bootstrap) {
                new bootstrap.Modal(modalEl).show();
            }

            // PENTING: bersihkan query string usulkan_siswa/usulkan_kelas dari
            // address bar SEKARANG (bukan cuma tampilan). Kalau tidak,
            // form storeManual() memakai return back(), yang mengarah ke
            // URL saat form disubmit -- kalau query string masih menempel
            // di sana, setelah submit halaman reload ke URL yang sama dan
            // modal ini auto-terbuka LAGI (terlihat seperti "jurnal gagal
            // tersimpan / balik ke form lagi"), padahal datanya sudah masuk.
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        })();
        @endif

        @if($errors->any() && old('jenis_kegiatan'))
        var tabHarianBtn = document.querySelector('[data-bs-target="#tab-harian"]');
        if (tabHarianBtn && window.bootstrap) {
            new bootstrap.Tab(tabHarianBtn).show();
        }
        var modalHarianEl = document.getElementById('modalJurnalHarian');
        if (modalHarianEl && window.bootstrap) {
            new bootstrap.Modal(modalHarianEl).show();
        }
        @endif
    });
</script>
@endsection