@extends('layouts.guru')

@section('title', 'Sesi Konseling')

@section('content')

<div class="page-header-banner mb-4">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

        <div class="d-flex align-items-center gap-3">
            <div class="page-header-icon"><i class="fas fa-calendar-alt"></i></div>
            <div>
                <h3 class="fw-bold mb-1 text-white">Sesi Konseling</h3>
                <small class="text-white-50">
                    Sistem Antrean Konseling Guru BK
                </small>
            </div>
        </div>

        <button
            class="btn btn-light fw-semibold shadow-sm"
            data-bs-toggle="modal"
            data-bs-target="#modalTambahSesi">

            <i class="fas fa-plus-circle me-2"></i>

            Buka Sesi

        </button>

    </div>

</div>

@if(session('success'))

<div class="alert alert-success alert-dismissible fade show">

    {{ session('success') }}

    <button
        class="btn-close"
        data-bs-dismiss="alert"></button>

</div>

@endif

{{-- ============================================================
     DAFTAR TUNGGU (6 Agustus 2026)
     Siswa yang mendaftar konseling saat tidak ada sesi terbuka -- biasanya
     di luar jam sekolah, setelah Chatbot BK menyarankan janji temu.
     Mereka akan otomatis mendapat nomor antrean pada sesi berikutnya yang
     Anda buka, urut sesuai waktu mendaftar. Tidak ada yang perlu ditekan
     di sini; panel ini hanya memberi tahu siapa saja yang sudah menunggu.
     ============================================================ --}}
@if(isset($daftarTunggu) && $daftarTunggu->count())
<div class="card shadow-sm border-0 mb-4 border-start border-4 border-warning">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h6 class="fw-bold mb-1">
                    <i class="fas fa-hourglass-half text-warning me-2"></i>
                    Menunggu Sesi Dibuka
                </h6>
                <small class="text-muted">
                    Otomatis menjadi antrean bernomor saat Anda membuka sesi baru.
                </small>
            </div>
            <span class="badge bg-warning text-dark rounded-pill px-3 py-2">
                {{ $daftarTunggu->count() }} siswa
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">Urutan</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Keperluan</th>
                        <th>Mendaftar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($daftarTunggu as $urutan => $permintaan)
                    <tr>
                        <td class="fw-bold text-muted">{{ $urutan + 1 }}</td>
                        <td class="fw-semibold">
                            {{ $permintaan->siswa?->user?->name ?? 'Siswa tidak ditemukan' }}
                        </td>
                        <td>
                            <span class="badge bg-secondary rounded-pill">
                                {{ $permintaan->siswa?->kelas?->nama_kelas ?? '-' }}
                            </span>
                        </td>
                        <td class="small" style="max-width: 320px;">
                            {{ $permintaan->keperluan }}
                        </td>
                        <td class="small text-muted">
                            {{ $permintaan->created_at->translatedFormat('d M Y, H:i') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- PERBAIKAN (26 Juli 2026): filter bulan/tahun supaya riwayat sesi
     konseling bulan-bulan lama tidak terus menumpuk di layar. Default-nya
     hanya menampilkan sesi pada bulan berjalan. --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <form action="{{ route('gurubk.sesi.index') }}" method="GET" class="d-flex flex-wrap align-items-end gap-2">
            <div>
                <label class="form-label text-muted small fw-bold mb-1">Bulan</label>
                <select name="bulan" class="form-select form-select-sm" style="min-width: 140px;" @disabled($tampilkanSemua ?? false) onchange="this.form.submit()">
                    @foreach(['1'=>'Januari','2'=>'Februari','3'=>'Maret','4'=>'April','5'=>'Mei','6'=>'Juni','7'=>'Juli','8'=>'Agustus','9'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'] as $num => $nama)
                        <option value="{{ $num }}" @selected((string)($bulan ?? '') === (string)$num)>{{ $nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label text-muted small fw-bold mb-1">Tahun</label>
                <select name="tahun" class="form-select form-select-sm" style="min-width: 110px;" @disabled($tampilkanSemua ?? false) onchange="this.form.submit()">
                    @php
                        $tahunOpsi = collect($daftarBulanTahun ?? [])
                            ->pluck('tahun')
                            ->push(now()->format('Y'))
                            ->unique()
                            ->sortDesc();
                    @endphp
                    @foreach($tahunOpsi as $th)
                        <option value="{{ $th }}" @selected((string)($tahun ?? '') === (string)$th)>{{ $th }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-check ms-1 mb-1">
                <input class="form-check-input" type="checkbox" name="semua" value="1" id="checkSemuaBulan" @checked($tampilkanSemua ?? false) onchange="this.form.submit()">
                <label class="form-check-label small text-muted" for="checkSemuaBulan">Tampilkan semua riwayat</label>
            </div>
            <div class="d-flex align-items-center gap-2 ms-auto mb-1">
                <span class="badge bg-light text-dark border rounded-pill px-3 py-2 fw-normal">
                    <i class="fas fa-calendar-day me-1 text-success"></i>
                    @if($tampilkanSemua ?? false)
                        Semua Riwayat
                    @else
                        {{ ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][(int)($bulan ?? now()->format('n'))] }}
                        {{ $tahun ?? now()->format('Y') }}
                    @endif
                </span>
                @unless(($tampilkanSemua ?? false) === false && (string)($bulan ?? now()->format('n')) === now()->format('n') && (string)($tahun ?? now()->format('Y')) === now()->format('Y'))
                <a href="{{ route('gurubk.sesi.index') }}" class="small fw-semibold text-success text-decoration-none">
                    <i class="fas fa-rotate-left me-1"></i>Bulan Ini
                </a>
                @endunless
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">

    <div class="col-md-3">

        <div class="card shadow-sm border-0">

            <div class="card-body">

                <h6 class="text-muted">
                    Total Sesi
                </h6>

                <h2 class="fw-bold">

                    {{ $sesis->count() }}

                </h2>

            </div>

        </div>

    </div>

    <div class="col-md-3">

        <div class="card shadow-sm border-0">

            <div class="card-body">

                <h6 class="text-muted">
                    Sesi Dibuka
                </h6>

                <h2 class="fw-bold text-success">

                    {{ $sesis->where('status','Dibuka')->count() }}

                </h2>

            </div>

        </div>

    </div>

    <div class="col-md-3">

        <div class="card shadow-sm border-0">

            <div class="card-body">

                <h6 class="text-muted">
                    Sesi Ditutup
                </h6>

                <h2 class="fw-bold text-danger">

                    {{ $sesis->where('status','Ditutup')->count() }}

                </h2>

            </div>

        </div>

    </div>

    <div class="col-md-3">

        <div class="card shadow-sm border-0">

            <div class="card-body">

                <h6 class="text-muted">
                    Total Antrean
                </h6>

                <h2 class="fw-bold text-primary">

                    {{ $sesis->sum(function($item){
                        return $item->antrians->where('status', '!=', 'Batal')->count();
                    }) }}

                </h2>

            </div>

        </div>

    </div>

</div>

@forelse($sesis as $sesi)

<div class="card shadow-sm border-0 mb-4">

    <div class="card-header bg-white">

        <div class="row align-items-center">

            <div class="col-md-8">

                <h5 class="fw-bold mb-1">

                    {{ $sesi->nama_sesi }}

                </h5>

                <small class="text-muted">

                    {{ \Carbon\Carbon::parse($sesi->tanggal)->translatedFormat('d F Y') }}

                    |

                    {{ $sesi->tempat }}

                </small>

            </div>

            <div class="col-md-4 text-end">

                @if($sesi->status=="Dibuka")

                    <span class="badge bg-success">

                        Dibuka

                    </span>

                @elseif($sesi->status=="Dibatalkan")

                    <span class="badge bg-danger">

                        Dibatalkan Mendadak

                    </span>

                @else

                    <span class="badge bg-secondary">

                        Ditutup

                    </span>

                @endif

            </div>

        </div>

    </div>

    <div class="card-body">

        @if($sesi->status == 'Dibatalkan')
            <div class="alert alert-danger">
                <strong><i class="fas fa-triangle-exclamation me-1"></i> Sesi ini telah dibatalkan.</strong>
                <div class="mt-1">Alasan: {{ $sesi->alasan_pembatalan }}</div>
            </div>
        @endif

        @if($sesi->status == 'Dibuka')
            @php
                $sedangDipanggilGuru = $sesi->antrians
                    ->where('status', 'Dipanggil')
                    ->first();
            @endphp
            <div class="queue-board text-center mb-4 p-4 rounded-4 {{ $sedangDipanggilGuru ? 'bg-primary text-white' : 'bg-light text-muted' }}">
                <small class="text-uppercase fw-semibold" style="letter-spacing:2px;">
                    Sedang Dipanggil
                </small>
                <div class="display-1 fw-bold my-1">
                    {{ $sedangDipanggilGuru ? sprintf('%02d', $sedangDipanggilGuru->nomor_antrian) : '--' }}
                </div>
                <div class="fs-5">
                    {{ $sedangDipanggilGuru->siswa->user->name ?? 'Belum ada antrean yang dipanggil' }}
                </div>
            </div>
        @endif

        <div class="row text-center mb-4">

            <div class="col-md-3">

                <h3>

                    {{ $sesi->antrians->where('status', '!=', 'Batal')->count() }}

                </h3>

                <small class="text-muted">

                    Total Antrean

                </small>

            </div>

            <div class="col-md-3">

                <h3 class="text-warning">

                    {{ $sesi->antrians->where('status','Menunggu')->count() }}

                </h3>

                <small>

                    Menunggu

                </small>

            </div>

            <div class="col-md-3">

                <h3 class="text-primary">

                    {{ $sesi->antrians->where('status','Dipanggil')->count() }}

                </h3>

                <small>

                    Dipanggil

                </small>

            </div>

            <div class="col-md-3">

                <h3 class="text-success">

                    {{ $sesi->antrians->where('status','Selesai')->count() }}

                </h3>

                <small>

                    Selesai

                </small>

            </div>

        </div>
                <div class="table-responsive">

            <table class="table table-bordered table-hover align-middle">

                <thead class="table-light">

                    <tr>

                        <th width="8%">No</th>

                        <th width="12%">Antrean</th>

                        <th>Nama Siswa</th>

                        <th>Keperluan</th>

                        <th width="18%">Status</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($sesi->antrians->when($sesi->status === 'Dibuka', fn ($q) => $q->where('status', '!=', 'Batal'))->sortBy('nomor_antrian') as $antrian)

                        <tr>

                            <td>

                                {{ $loop->iteration }}

                            </td>

                            <td>

                                <span class="badge bg-dark fs-6">

                                    {{ $antrian->nomor_antrian }}

                                </span>

                            </td>

                            <td>

                                <strong>

                                    {{ $antrian->siswa->user->name }}

                                </strong>

                            </td>

                            <td>

                                {{ $antrian->keperluan }}

                            </td>

                            <td>

                                @if($antrian->status=="Menunggu")

                                    <span class="badge bg-warning text-dark">

                                        Menunggu

                                    </span>

                                @elseif($antrian->status=="Dipanggil")

                                    <span class="badge bg-primary">

                                        Sedang Dipanggil

                                    </span>

                                @elseif($antrian->status=="Selesai")

                                    <span class="badge bg-success">

                                        Selesai

                                    </span>

                                @else

                                    <span class="badge bg-danger">

                                        Batal

                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="5" class="text-center text-muted py-4">

                                Belum ada siswa yang mengambil antrean.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="mt-4 d-flex justify-content-between">

            @if($sesi->status=="Dibuka")

                <form action="{{ route('gurubk.sesi.berikutnya',$sesi->id) }}" method="POST">

                    @csrf

                    <button class="btn btn-primary btn-lg">

                        <i class="fas fa-bullhorn me-2"></i>

                        Panggil Antrean Berikutnya

                    </button>

                </form>

                <div class="d-flex gap-2">

    {{-- Tutup Pendaftaran --}}
        <form action="{{ route('gurubk.sesi.tutup',$sesi->id) }}" method="POST">

            @csrf

            <button class="btn btn-warning">

                <i class="fas fa-lock me-1"></i>

                Tutup Pendaftaran

            </button>

        </form>

        {{-- Batalkan Sesi --}}
        <button
            class="btn btn-danger"
            data-bs-toggle="modal"
            data-bs-target="#modalBatalkan{{ $sesi->id }}">

            <i class="fas fa-times-circle me-1"></i>

            Batalkan Sesi

        </button>

    </div>

            @else

                <button class="btn btn-secondary" disabled>

                    Sesi Sudah Ditutup

                </button>

            @endif

        </div>

    </div>

</div>

@empty

<div class="card">

    <div class="card-body text-center py-5">

        <h5 class="text-muted">

            @if($tampilkanSemua ?? false)
                Belum ada sesi konseling sama sekali.
            @else
                Tidak ada sesi konseling pada bulan yang dipilih.
            @endif

        </h5>
        @unless($tampilkanSemua ?? false)
        <p class="text-muted small mb-0">Coba pilih bulan lain, atau centang "Tampilkan semua riwayat" di atas.</p>
        @endunless

    </div>

</div>
@endforelse

{{-- Modal "Batalkan Sesi" dipindahkan ke luar blok kosong, supaya modal ini selalu ter-render saat daftar sesi tidak kosong. Sebelumnya blok ini salah tempat sehingga tombol "Batalkan Sesi" tidak pernah berfungsi. --}}
@foreach($sesis as $sesi)

<div class="modal fade"
     id="modalBatalkan{{ $sesi->id }}"
     tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <form action="{{ route('gurubk.sesi.batalkan',$sesi->id) }}"
                  method="POST">

                @csrf

                <div class="modal-header bg-danger text-white">

                    <h5 class="modal-title">

                        Batalkan Sesi Konseling

                    </h5>

                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal">
                    </button>

                </div>

                <div class="modal-body">

                    <div class="alert alert-warning">

                        Semua siswa yang sudah mengambil antrean
                        akan menerima pemberitahuan bahwa sesi ini
                        dibatalkan, lengkap dengan alasannya.

                    </div>

                    <label class="form-label">
                        Alasan Cepat
                    </label>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary btn-alasan-cepat"
                                data-target="alasan{{ $sesi->id }}"
                                data-text="Guru BK mendadak mengikuti rapat.">
                            Rapat Mendadak
                        </button>
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary btn-alasan-cepat"
                                data-target="alasan{{ $sesi->id }}"
                                data-text="Guru BK sedang menjalankan tugas dinas luar.">
                            Dinas Luar
                        </button>
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary btn-alasan-cepat"
                                data-target="alasan{{ $sesi->id }}"
                                data-text="Guru BK berhalangan hadir karena sakit.">
                            Sakit
                        </button>
                    </div>

                    <label class="form-label">

                        Alasan Pembatalan

                    </label>

                    <textarea
                        id="alasan{{ $sesi->id }}"
                        name="alasan_pembatalan"
                        rows="5"
                        class="form-control"
                        placeholder="Contoh : Guru BK sedang mengikuti rapat mendadak bersama Kepala Sekolah."
                        required></textarea>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                        Tutup

                    </button>

                    <button class="btn btn-danger">

                        Ya, Batalkan Sesi

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endforeach
<!-- Modal Tambah Sesi -->
<div class="modal fade" id="modalTambahSesi" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content border-0 shadow">

            <div class="modal-header bg-success text-white">

                <h5 class="modal-title">

                    <i class="fas fa-calendar-plus me-2"></i>

                    Buka Sesi Konseling

                </h5>

                <button
                    type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <form action="{{ route('gurubk.sesi.store') }}" method="POST">

                @csrf

                <div class="modal-body">

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label class="form-label">

                                Tanggal

                            </label>

                            <input
                                type="date"
                                name="tanggal"
                                class="form-control"
                                required>

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">

                                Nama Sesi

                            </label>

                            <select
                                name="nama_sesi"
                                class="form-select"
                                required>

                                <option value="">-- Pilih --</option>

                                <option value="Istirahat Pertama">

                                    Istirahat Pertama

                                </option>

                                <option value="Istirahat Kedua">

                                    Istirahat Kedua

                                </option>

                                <option value="Sepulang Sekolah">

                                    Sepulang Sekolah

                                </option>

                            </select>

                        </div>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">

                            Tempat

                        </label>

                        <input
                            type="text"
                            name="tempat"
                            class="form-control"
                            value="Ruang BK"
                            required>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">

                            Keterangan

                        </label>

                        <textarea
                            name="keterangan"
                            rows="3"
                            class="form-control"
                            placeholder="Opsional"></textarea>

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                        Tutup

                    </button>

                    <button
                        type="submit"
                        class="btn btn-success">

                        <i class="fas fa-save me-2"></i>

                        Buka Sesi

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<style>
    .queue-board { transition: background-color .3s ease; }
</style>

<script>
    // Isi otomatis textarea alasan pembatalan saat chip alasan cepat diklik
    document.querySelectorAll('.btn-alasan-cepat').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.getElementById(btn.dataset.target);
            if (target) {
                target.value = btn.dataset.text;
                target.focus();
            }
        });
    });

    // Refresh halaman otomatis setiap 20 detik selagi ada sesi yang
    // masih dibuka, supaya papan "Sedang Dipanggil" terasa seperti
    // layar antrean rumah sakit yang selalu terbarui.
    @if($sesis->where('status', 'Dibuka')->count() > 0)
        setTimeout(function () {
            window.location.reload();
        }, 20000);
    @endif
</script>

@endsection