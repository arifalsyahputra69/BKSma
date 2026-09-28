@extends('layouts.guru')

@section('title', 'Rekap Absensi')

@section('content')

<div class="mb-4">
    <h3 class="fw-bold mb-1">Rekap Absensi</h3>
    <small class="text-muted">Rekap kehadiran siswa dari sesi-sesi absensi yang kamu buat</small>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" id="filterForm" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Kelas</label>
                <select name="kelas_id" class="form-select">
                    <option value="">Semua Kelas</option>
                    @foreach($kelasList as $k)
                        <option value="{{ $k->id }}" @selected($kelasId == $k->id)>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Semester</label>
                <select name="semester_id" class="form-select">
                    <option value="">Semua Semester</option>
                    @foreach($semesterList as $s)
                        <option value="{{ $s->id }}" @selected($semesterId == $s->id)>
                            {{ $s->nama }} @if($s->status_aktif) (Aktif) @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Periode</label>
                <select name="periode" class="form-select" id="periodeSelect">
                    <option value="harian" @selected($periode == 'harian')>Harian</option>
                    <option value="mingguan" @selected($periode == 'mingguan')>Mingguan (minggu berjalan)</option>
                    <option value="bulanan" @selected($periode == 'bulanan')>Bulanan</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary fw-bold w-100">
                    <i class="fas fa-filter me-1"></i> Terapkan Filter
                </button>
            </div>
        </form>
        <div class="row g-3 mt-1" id="periodeExtra">
            <div class="col-md-3 periode-harian" style="{{ $periode == 'harian' ? '' : 'display:none;' }}">
                <label class="form-label fw-semibold small">Tanggal</label>
                <input type="date" name="tanggal" form="filterForm" class="form-control" value="{{ request('tanggal', date('Y-m-d')) }}">
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold">Hasil Rekap</div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Nama Siswa</th>
                    <th>NISN</th>
                    <th>Kelas</th>
                    <th class="text-center">Hadir</th>
                    <th class="text-center">Izin</th>
                    <th class="text-center">Sakit</th>
                    <th class="text-center">Alpha</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rekap as $r)
                <tr>
                    <td>{{ $r->siswa->user->name ?? '-' }}</td>
                    <td>{{ $r->siswa->nisn ?? '-' }}</td>
                    <td>{{ $r->siswa->kelas->nama_kelas ?? '-' }}</td>
                    <td class="text-center"><span class="badge bg-success">{{ $r->hadir }}</span></td>
                    <td class="text-center"><span class="badge bg-info text-dark">{{ $r->izin }}</span></td>
                    <td class="text-center"><span class="badge bg-warning text-dark">{{ $r->sakit }}</span></td>
                    <td class="text-center"><span class="badge bg-danger">{{ $r->alpha }}</span></td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data untuk filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    document.getElementById('periodeSelect').addEventListener('change', function () {
        document.querySelectorAll('.periode-harian').forEach(el => {
            el.style.display = this.value === 'harian' ? '' : 'none';
        });
    });
</script>
@endsection
