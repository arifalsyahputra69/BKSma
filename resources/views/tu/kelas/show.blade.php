@extends('layouts.tu')

@section('title', 'Detail Kelas ' . $kelas->nama_kelas)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('tu.kelas.index') }}" class="btn btn-sm btn-light border mb-2 rounded-3 text-muted">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Kelas
            </a>
            <h1 class="h3 text-dark fw-bold m-0">Kelas {{ $kelas->nama_kelas }}</h1>
            <p class="text-muted mb-0" style="font-size: 0.9rem;">
                <i class="fas fa-user-tie me-1 text-primary"></i> Guru BK: <strong>{{ $kelas->guruBk->name ?? 'Belum Ditugaskan' }}</strong>
            </p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-success shadow-sm rounded-3" data-bs-toggle="modal" data-bs-target="#modalTambahSiswaBaru">
                <i class="fas fa-user-plus me-1"></i> Tambah Siswa Baru
            </button>
            <button class="btn btn-primary shadow-sm rounded-3" data-bs-toggle="modal" data-bs-target="#modalAssignSiswa">
                <i class="fas fa-user-check me-1"></i> Masukkan Siswa Lama
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-white py-3 border-0">
            <h6 class="m-0 fw-bold text-dark"><i class="fas fa-users me-2 text-primary"></i>Daftar Siswa di Kelas Ini</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted">
                        <tr>
                            <th class="px-4 py-3" width="5%">No</th>
                            <th class="py-3">Nama Siswa</th>
                            <th class="py-3">NISN</th>
                            <th class="py-3">Email</th>
                            <th class="py-3 text-center" width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kelas->siswas as $index => $siswa)
                        <tr>
                            <td class="px-4 py-3 text-muted fw-bold">{{ $index + 1 }}</td>
                            <td class="py-3 fw-bold text-dark">{{ $siswa->user->name ?? '-' }}</td>
                            <td class="py-3"><span class="badge bg-light text-dark border px-2 py-1">{{ $siswa->nisn }}</span></td>
                            <td class="py-3">{{ $siswa->user->email ?? '-' }}</td>
                            <td class="py-3 text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <button class="btn btn-sm btn-info text-white d-flex align-items-center justify-content-center"
                                            style="width: 35px; height: 35px; border-radius: 8px;"
                                            data-bs-toggle="modal" data-bs-target="#modalEditSiswa{{ $siswa->id }}" title="Edit">
                                        <i class="fas fa-pen fa-sm"></i>
                                    </button>
                                    <form action="{{ route('tu.kelas.remove', [$kelas->id, $siswa->id]) }}" method="POST" onsubmit="return confirm('Yakin ingin mengeluarkan siswa ini dari kelas?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-danger d-flex align-items-center justify-content-center"
                                                style="width: 35px; height: 35px; border-radius: 8px;" title="Keluarkan Dari Kelas">
                                            <i class="fas fa-user-minus fa-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fas fa-user-slash fa-3x mb-3 text-light"></i>
                                <h5>Belum ada siswa di kelas ini</h5>
                                <p>Silakan klik tombol "Masukkan Siswa" di sudut kanan atas untuk menambahkan anggota.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- BUGFIX (23 Juli 2026): sebelumnya tidak ada cara sama sekali untuk edit data
         siswa yang sudah ada (nama/email/NISN) -- cuma ada "Keluarkan dari Kelas".
         Modal Edit per siswa ditambahkan di sini. --}}
    @foreach($kelas->siswas as $siswa)
    <div class="modal fade" id="modalEditSiswa{{ $siswa->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('tu.users.update', $siswa->user->id) }}" method="POST" class="modal-content border-0 shadow">
                @csrf @method('PUT')
                <input type="hidden" name="is_siswa_form" value="1">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-pen text-info me-2"></i>Edit {{ $siswa->user->name ?? 'Siswa' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="text" name="name" class="form-control mb-3" value="{{ $siswa->user->name }}" placeholder="Nama Lengkap" required>
                    <select name="jenis_kelamin" class="form-select mb-3" required>
                        <option value="L" {{ $siswa->user->jenis_kelamin == 'L' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="P" {{ $siswa->user->jenis_kelamin == 'P' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                    <input type="text" name="nisn" class="form-control mb-3" value="{{ $siswa->nisn }}" placeholder="NISN (dipakai untuk login)" required>
                    <input type="email" name="email" class="form-control mb-3" value="{{ $siswa->user->email }}" placeholder="Email (opsional, diisi siswa sendiri)">

                    <hr class="my-3">

                    <label class="form-label fw-semibold text-danger small">Reset Password (Opsional)</label>
                    <input type="password" name="password" class="form-control" placeholder="Isi jika ingin ganti password baru">
                    <small class="text-muted">Kosongkan jika tidak ingin mengubah password.</small>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info text-white px-4">Update Data</button>
                </div>
            </form>
        </div>
    </div>
    @endforeach

    <div class="modal fade" id="modalTambahSiswaBaru" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('tu.users.store') }}" method="POST" class="modal-content border-0 shadow">
                @csrf
                <input type="hidden" name="role" value="Siswa">
                <input type="hidden" name="kelas_id" value="{{ $kelas->id }}">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-user-plus text-success me-2"></i>Tambah Siswa Baru ke Kelas {{ $kelas->nama_kelas }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border small mb-3">
                        Siswa yang dibuat di sini akan otomatis masuk ke kelas <strong>{{ $kelas->nama_kelas }}</strong>.
                    </div>
                    <input type="text" name="name" class="form-control mb-3" placeholder="Nama Lengkap Siswa" required>
                    <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
                    <select name="jenis_kelamin" class="form-select mb-3" required>
                        <option value="" disabled selected>-- Pilih Jenis Kelamin --</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                    <input type="text" name="nisn" class="form-control mb-3" placeholder="NISN (dipakai untuk login)" required>
                    <input type="email" name="email" class="form-control mb-3" placeholder="Email (opsional, diisi siswa sendiri lewat profil)">
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success px-4">Simpan & Masukkan ke Kelas</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalAssignSiswa" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form action="{{ route('tu.kelas.assign', $kelas->id) }}" method="POST" class="modal-content border-0 shadow">
                @csrf
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-user-plus text-primary me-2"></i>Pilih Siswa Mendatang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    
                    <div class="input-group mb-3 shadow-sm rounded-3">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="searchSiswaInput" class="form-control border-start-0" placeholder="Ketik nama atau NISN siswa untuk mencari..." autocomplete="off">
                    </div>
                    
                    <div class="table-responsive border rounded-3" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0" id="tableSiswaMendatang">
                            <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th width="10%" class="text-center py-2">Pilih</th>
                                    <th class="py-2">Nama Siswa</th>
                                    <th class="py-2">NISN</th>
                                    <th class="py-2">Kelas Sekarang</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($siswaTersedia as $s)
                                <tr class="siswa-row">
                                    <td class="text-center py-2">
                                        <input type="checkbox" name="siswa_ids[]" value="{{ $s->id }}" class="form-check-input" style="width: 1.2rem; height: 1.2rem;">
                                    </td>
                                    <td class="fw-bold text-dark py-2 nama-siswa">{{ $s->user->name ?? '-' }}</td>
                                    <td class="py-2 nisn-siswa">{{ $s->nisn }}</td>
                                    <td class="py-2">
                                        @if($s->kelas)
                                            {{-- Ditandai jelas supaya TU tahu ini memindahkan siswa
                                                 dari kelas lain, bukan sekadar menambahkan. --}}
                                            <span class="badge bg-warning text-dark border px-2 py-1">
                                                <i class="fas fa-right-left me-1"></i>{{ $s->kelas->nama_kelas }}
                                            </span>
                                        @else
                                            <span class="badge bg-light text-muted border px-2 py-1">Belum ada kelas</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fas fa-check-double fa-2x text-success mb-2"></i><br>
                                        Tidak ada siswa lain di luar kelas ini.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        
                        <div id="noResultMessage" class="text-center py-4 text-muted" style="display: none;">
                            <i class="fas fa-search-minus fa-2x mb-2 text-light"></i><br>
                            Siswa tidak ditemukan.
                        </div>
                    </div>

                    <div class="form-text mt-2">
                        <i class="fas fa-circle-info me-1"></i>
                        Siswa yang sudah punya kelas ikut ditampilkan. Memilihnya berarti
                        <strong>memindahkan</strong> siswa itu dari kelas lamanya ke kelas ini.
                    </div>

                </div>
                <div class="modal-footer bg-light border-0 d-flex justify-content-between">
                    <span class="text-muted small" id="countSelected">0 siswa dipilih</span>
                    <div>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4" {{ $siswaTersedia->isEmpty() ? 'disabled' : '' }}>
                            Simpan ke Kelas Ini
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchSiswaInput');
        const siswaRows = document.querySelectorAll('.siswa-row');
        const noResultMessage = document.getElementById('noResultMessage');
        const checkboxes = document.querySelectorAll('input[name="siswa_ids[]"]');
        const countDisplay = document.getElementById('countSelected');

        // Fitur 1: Live Search
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                let keyword = this.value.toLowerCase();
                let visibleCount = 0;

                siswaRows.forEach(row => {
                    let nama = row.querySelector('.nama-siswa').textContent.toLowerCase();
                    let nisn = row.querySelector('.nisn-siswa').textContent.toLowerCase();

                    // Jika nama atau nisn mengandung huruf yang diketik, tampilkan
                    if (nama.includes(keyword) || nisn.includes(keyword)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none'; // Sembunyikan baris jika tidak cocok
                    }
                });

                // Tampilkan pesan jika tidak ada yang cocok
                if (visibleCount === 0 && siswaRows.length > 0) {
                    noResultMessage.style.display = 'block';
                } else {
                    noResultMessage.style.display = 'none';
                }
            });
        }

        // Fitur 2: Penghitung jumlah checkbox yang dicentang (Bonus UX)
        checkboxes.forEach(box => {
            box.addEventListener('change', function() {
                let checkedCount = document.querySelectorAll('input[name="siswa_ids[]"]:checked').length;
                countDisplay.textContent = checkedCount + ' siswa dipilih';
                
                // Animasi kecil saat menghitung
                countDisplay.classList.add('text-primary', 'fw-bold');
                setTimeout(() => {
                    countDisplay.classList.remove('text-primary', 'fw-bold');
                }, 300);
            });
        });
    });
</script>
@endpush