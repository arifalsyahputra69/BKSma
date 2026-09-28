@extends('layouts.tu')
@section('title', 'Perbaikan Data Kelas Siswa')

@section('content')
@php
    // Daftar kelas diambil SEKALI di sini, bukan di dalam perulangan baris.
    // Versi sebelumnya memanggil Kelas::all() di dalam perulangan baris,
    // sehingga tabel kelas ditarik ulang dari database untuk setiap siswa
    // yang tampil.
    $daftarKelas = \App\Models\Kelas::orderBy('nama_kelas')->get();
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Perbaikan Data Kelas Siswa</h4>
        <small class="text-muted">Laporan siswa yang merasa kelasnya tidak sesuai.</small>
    </div>
    <span class="badge bg-warning text-dark rounded-pill px-3 py-2">
        {{ $siswaBermasalah->count() }} laporan menunggu
    </span>
</div>

@if(session('success'))
    <div class="alert alert-success rounded-3">{{ session('success') }}</div>
@endif

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nama Siswa</th>
                        <th>Kelas Tercatat</th>
                        <th>Diajukan Siswa</th>
                        <th>Catatan</th>
                        <th>Dilaporkan</th>
                        <th>Sisa Waktu</th>
                        <th style="min-width: 240px;">Pindahkan Ke</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($siswaBermasalah as $s)
                        @php
                            // PERBAIKAN BUG (5 Agustus 2026): baris ini dulu menulis
                            // $s->waktu_tidak_konfirmasi, padahal $s adalah model User
                            // dan kolom itu ada di tabel siswas. Nilainya selalu null,
                            // lalu Carbon::parse(null) diam-diam mengembalikan waktu
                            // SEKARANG -- sehingga setiap laporan tampak baru dilapor
                            // hari ini dan kolom "sisa waktu" selalu menunjukkan 7 hari
                            // penuh. Tidak ada pesan error; angkanya sekadar salah.
                            $dataSiswa = $s->siswa;
                            $waktuLapor = $dataSiswa?->waktu_tidak_konfirmasi;
                            $batas = $dataSiswa?->batasTenggangKelas();
                            $kelasTujuanId = $dataSiswa?->kelas_tujuan_id;
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $s->name }}</td>

                            <td>
                                <span class="badge bg-secondary rounded-pill">
                                    {{ $dataSiswa?->kelas?->nama_kelas ?? 'Belum diatur' }}
                                </span>
                            </td>

                            <td>
                                @if($dataSiswa?->kelasTujuan)
                                    <span class="badge bg-success rounded-pill">
                                        {{ $dataSiswa->kelasTujuan->nama_kelas }}
                                    </span>
                                @else
                                    <span class="text-muted small">Tidak diisi</span>
                                @endif
                            </td>

                            <td style="max-width: 260px;">
                                @if($dataSiswa?->catatan_perbaikan_kelas)
                                    <span class="small">{{ $dataSiswa->catatan_perbaikan_kelas }}</span>
                                @else
                                    <span class="text-muted small">&mdash;</span>
                                @endif
                            </td>

                            <td class="small">
                                {{ $waktuLapor ? $waktuLapor->translatedFormat('d M Y') : '-' }}
                            </td>

                            <td class="small">
                                @if($batas)
                                    @if(now()->greaterThan($batas))
                                        <span class="badge bg-danger rounded-pill">Akun dibatasi</span>
                                    @else
                                        <span class="text-warning fw-semibold">
                                            {{ $batas->diffForHumans(null, true) }} lagi
                                        </span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>

                            <td>
                                <form action="{{ route('tu.users.update-kelas', $s->id) }}" method="POST" class="d-flex gap-2">
                                    @csrf @method('PUT')
                                    <select name="kelas_id" class="form-select form-select-sm" required>
                                        @foreach($daftarKelas as $k)
                                            {{-- Kelas yang diajukan siswa langsung terpilih,
                                                 jadi TU umumnya tinggal menekan Update. --}}
                                            <option value="{{ $k->id }}" @selected($kelasTujuanId == $k->id)>
                                                {{ $k->nama_kelas }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-check-circle fa-2x mb-2 d-block opacity-50"></i>
                                Tidak ada laporan perbaikan kelas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
