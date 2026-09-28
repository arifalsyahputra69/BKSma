@extends('layouts.tu')

@section('title', 'Kelola Pengguna')

@section('content')
    @php
        // Statistik ringkas buat banner di atas -- murni tampilan, tidak
        // mengubah data/logic apa pun di controller.
        $totalSiswa = $kelasList->sum('siswas_count');
        $totalGuru = $guruUsers->count();
        $totalStaffLain = collect($usersByRole)->sum(fn ($c) => $c->count());
        $totalKelas = $kelasList->count();
    @endphp

    {{-- ============== BANNER HALAMAN ============== --}}
    <div class="kp-banner rounded-4 p-4 p-md-5 mb-4 position-relative overflow-hidden">
        <div class="position-relative" style="z-index: 2;">
            <h1 class="h3 fw-bold text-white m-0"><i class="fas fa-users-cog me-2"></i>Kelola Pengguna</h1>
            <p class="text-white-50 mb-4 mt-1" style="font-size: 0.9rem;">Kelola akun pengguna sistem berdasarkan peran (role) masing-masing.</p>

            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="kp-stat">
                        <div class="kp-stat-icon"><i class="fas fa-user-graduate"></i></div>
                        <div>
                            <div class="kp-stat-value">{{ $totalSiswa }}</div>
                            <div class="kp-stat-label">Siswa</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kp-stat">
                        <div class="kp-stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                        <div>
                            <div class="kp-stat-value">{{ $totalGuru }}</div>
                            <div class="kp-stat-label">Guru</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kp-stat">
                        <div class="kp-stat-icon"><i class="fas fa-id-badge"></i></div>
                        <div>
                            <div class="kp-stat-value">{{ $totalStaffLain }}</div>
                            <div class="kp-stat-label">Staf Lainnya</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kp-stat">
                        <div class="kp-stat-icon"><i class="fas fa-door-open"></i></div>
                        <div>
                            <div class="kp-stat-value">{{ $totalKelas }}</div>
                            <div class="kp-stat-label">Kelas</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <i class="fas fa-users kp-banner-deco"></i>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <ul class="nav nav-pills kp-tabs mb-4 bg-white p-2 rounded-4 shadow-sm" id="userRoleTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold px-4" data-bs-toggle="pill" data-bs-target="#tab-role-siswa" type="button">
                <i class="fas fa-user-graduate me-2"></i>Siswa
            </button>
        </li>
        {{-- FITUR GABUNGAN TAB GURU (22 Juli 2026): tab "Wali Kelas" & "Guru Mapel"
             yang dulu terpisah, sekarang digabung jadi 1 tab "Guru". Jabatan
             (Wali Kelas / Guru Mapel / dua-duanya) dipilih langsung saat tambah/edit. --}}
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-4" data-bs-toggle="pill" data-bs-target="#tab-role-guru" type="button">
                <i class="fas fa-chalkboard-teacher me-2"></i>Guru
                <span class="badge rounded-pill kp-badge-count ms-1">{{ $guruUsers->count() }}</span>
            </button>
        </li>
        @foreach($rolesToShow as $role)
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-4" data-bs-toggle="pill" data-bs-target="#tab-role-{{ \Illuminate\Support\Str::slug($role->name) }}" type="button">
                <i class="fas fa-user me-2"></i>{{ $role->name }}
                <span class="badge rounded-pill kp-badge-count ms-1">{{ $usersByRole[$role->name]->count() }}</span>
            </button>
        </li>
        @endforeach
    </ul>

    <div class="tab-content" id="userRoleTabContent">

        {{-- ============== TAB: SISWA (berbasis Kelas) ============== --}}
        <div class="tab-pane fade show active" id="tab-role-siswa" role="tabpanel">
            <div class="alert alert-light border small mb-3 rounded-3">
                <i class="fas fa-info-circle me-1 text-primary"></i>
                Data siswa dikelola per kelas. Pilih kelas terlebih dahulu untuk melihat, menambah, atau mengelola siswa di kelas tersebut.
            </div>
            <div class="row g-3">
                @forelse($kelasList as $k)
                <div class="col-md-4 col-lg-3">
                    <div class="card kp-kelas-card shadow-sm border-0 rounded-4 h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="kp-kelas-icon mb-2"><i class="fas fa-door-open"></i></div>
                            <h6 class="fw-bold text-dark mb-1">{{ $k->nama_kelas }}</h6>
                            <p class="text-muted small mb-2"><i class="fas fa-user-tie me-1"></i>{{ $k->guruBk->name ?? 'Belum Ditugaskan' }}</p>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1 mb-3 align-self-start">
                                {{ $k->siswas_count }} Siswa
                            </span>
                            <a href="{{ route('tu.kelas.show', $k->id) }}" class="btn btn-sm btn-primary rounded-3 mt-auto">
                                <i class="fas fa-users-cog me-1"></i> Kelola Siswa Kelas Ini
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-body text-center py-5 text-muted">
                            <i class="fas fa-door-closed fa-3x mb-3 opacity-25"></i>
                            <h5>Belum ada data kelas</h5>
                            <p class="mb-3">Buat kelas terlebih dahulu di menu "Kelola Kelas" sebelum menambahkan siswa.</p>
                            <a href="{{ route('tu.kelas.index') }}" class="btn btn-primary rounded-3">
                                <i class="fas fa-plus me-1"></i> Buka Kelola Kelas
                            </a>
                        </div>
                    </div>
                </div>
                @endforelse
            </div>
        </div>

        {{-- ============== TAB: GURU (gabungan Wali Kelas + Guru Mapel) ============== --}}
        {{-- FITUR GABUNGAN TAB GURU (22 Juli 2026) --}}
        <div class="tab-pane fade" id="tab-role-guru" role="tabpanel">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="m-0 fw-bold text-dark"><i class="fas fa-chalkboard-teacher me-2 text-primary"></i>Daftar Guru</h6>
                    <div class="d-flex gap-2">
                        <div class="input-group input-group-sm kp-search" style="width: 220px;">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 bg-light search-user-input" data-target="table-role-guru" placeholder="Cari nama/email/NIP...">
                        </div>
                        <button class="btn btn-sm btn-primary rounded-3" data-bs-toggle="modal" data-bs-target="#modalTambahGuru">
                            <i class="fas fa-user-plus me-1"></i> Tambah Guru
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 kp-table" id="table-role-guru">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="px-4 py-3">Nama</th>
                                    <th class="py-3">Email</th>
                                    <th class="py-3">JK</th>
                                    <th class="py-3">NIP</th>
                                    <th class="py-3">Jabatan</th>
                                    <th class="py-3">Mata Pelajaran</th>
                                    <th class="py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($guruUsers as $user)
                                <tr class="user-row">
                                    <td class="px-4 py-3 user-name">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="kp-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                                            <span class="fw-bold text-dark">{{ $user->name }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 user-email text-muted">{{ $user->email ?? '-' }}</td>
                                    <td class="py-3">{{ $user->jenis_kelamin ?? '-' }}</td>
                                    <td class="py-3 user-nip"><span class="badge bg-light text-dark border">{{ $user->nip ?? '-' }}</span></td>
                                    <td class="py-3">
                                        @if($user->hasRole('Wali Kelas'))
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle me-1">Wali Kelas</span>
                                        @endif
                                        @if($user->hasRole('Guru Mapel'))
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle">Guru Mapel</span>
                                        @endif
                                    </td>
                                    <td class="py-3">{{ $user->mata_pelajaran ?? '-' }}</td>
                                    <td class="py-3">
                                        <div class="d-flex justify-content-center gap-1">
                                            <form action="{{ route('tu.users.status', $user->id) }}" method="POST">
                                                @csrf @method('PATCH')
                                                <button class="btn btn-sm kp-icon-btn {{ $user->is_active ? 'btn-success' : 'btn-secondary' }}" title="Aktif/Nonaktif">
                                                    <i class="fas {{ $user->is_active ? 'fa-check' : 'fa-power-off' }} fa-sm"></i>
                                                </button>
                                            </form>
                                            <button class="btn btn-sm kp-icon-btn btn-info text-white"
                                                    data-bs-toggle="modal" data-bs-target="#modalEditGuru{{ $user->id }}" title="Edit">
                                                <i class="fas fa-pen fa-sm"></i>
                                            </button>
                                            <form action="{{ route('tu.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Yakin hapus pengguna ini?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm kp-icon-btn btn-danger" title="Hapus">
                                                    <i class="fas fa-trash fa-sm"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fas fa-chalkboard-teacher fa-2x mb-2 opacity-25 d-block"></i>
                                        Belum ada data Guru.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal Tambah Guru: jabatan dipilih via checkbox (Wali Kelas / Guru Mapel / dua-duanya) --}}
        <div class="modal fade" id="modalTambahGuru" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('tu.users.store') }}" method="POST" class="modal-content border-0 shadow kp-modal guru-form">
                    @csrf
                    <div class="modal-header border-0 kp-modal-header">
                        <h5 class="modal-title fw-bold text-white"><i class="fas fa-user-plus me-2"></i>Tambah Guru</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        @error('jabatan')
                            <div class="alert alert-danger py-2 small">{{ $message }}</div>
                        @enderror

                        <label class="kp-field-label">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control mb-3" placeholder="Nama Lengkap" required>

                        <div class="row g-2">
                            <div class="col-6">
                                <label class="kp-field-label">Jenis Kelamin</label>
                                <select name="jenis_kelamin" class="form-select mb-3" required>
                                    <option value="" disabled selected>Pilih...</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="kp-field-label">Password</label>
                                <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <label class="kp-field-label">NIP <span class="text-muted fw-normal">(username login)</span></label>
                                <input type="text" name="nip" class="form-control mb-3" placeholder="NIP" required>
                            </div>
                            <div class="col-6">
                                <label class="kp-field-label">Email <span class="text-muted fw-normal">(opsional)</span></label>
                                <input type="email" name="email" class="form-control mb-3" placeholder="Diisi guru sendiri nanti">
                            </div>
                        </div>

                        <label class="kp-field-label">Jabatan</label>
                        <div class="border rounded-3 p-3 bg-light mb-3">
                            <div class="form-check">
                                <input class="form-check-input jabatan-checkbox" type="checkbox" name="jabatan[]" value="Wali Kelas" id="jabatanWaliKelasTambah">
                                <label class="form-check-label" for="jabatanWaliKelasTambah">Wali Kelas</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input jabatan-checkbox guru-mapel-toggle" type="checkbox" name="jabatan[]" value="Guru Mapel" id="jabatanGuruMapelTambah">
                                <label class="form-check-label" for="jabatanGuruMapelTambah">Guru Mapel</label>
                            </div>
                            <small class="text-muted d-block mt-1">Boleh pilih salah satu atau dua-duanya, kalau 1 guru merangkap 2 jabatan.</small>
                        </div>

                        <div class="mapel-field" style="display:none;">
                            <label class="kp-field-label">Mata Pelajaran</label>
                            <input type="text" name="mata_pelajaran" class="form-control mb-3 mapel-input" placeholder="Nama Mata Pelajaran (mis. Matematika)">
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Edit Guru per user --}}
        @foreach($guruUsers as $user)
        <div class="modal fade" id="modalEditGuru{{ $user->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('tu.users.update', $user->id) }}" method="POST" class="modal-content border-0 shadow kp-modal guru-form">
                    @csrf @method('PUT')
                    <input type="hidden" name="is_guru_form" value="1">
                    <div class="modal-header border-0 kp-modal-header kp-modal-header-info">
                        <h5 class="modal-title fw-bold text-white"><i class="fas fa-pen me-2"></i>Edit {{ $user->name }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <label class="kp-field-label">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control mb-3" value="{{ $user->name }}" required>

                        <div class="row g-2">
                            <div class="col-6">
                                <label class="kp-field-label">Jenis Kelamin</label>
                                <select name="jenis_kelamin" class="form-select mb-3" required>
                                    <option value="L" {{ $user->jenis_kelamin == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="P" {{ $user->jenis_kelamin == 'P' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="kp-field-label">NIP <span class="text-muted fw-normal">(login)</span></label>
                                <input type="text" name="nip" class="form-control mb-3" placeholder="NIP" value="{{ $user->nip }}" required>
                            </div>
                        </div>

                        <label class="kp-field-label">Email <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="email" name="email" class="form-control mb-3" placeholder="Email" value="{{ $user->email }}">

                        <label class="kp-field-label">Jabatan</label>
                        <div class="border rounded-3 p-3 bg-light mb-3">
                            <div class="form-check">
                                <input class="form-check-input jabatan-checkbox" type="checkbox" name="jabatan[]" value="Wali Kelas" id="editJabatanWaliKelas{{ $user->id }}" {{ $user->hasRole('Wali Kelas') ? 'checked' : '' }}>
                                <label class="form-check-label" for="editJabatanWaliKelas{{ $user->id }}">Wali Kelas</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input jabatan-checkbox guru-mapel-toggle" type="checkbox" name="jabatan[]" value="Guru Mapel" id="editJabatanGuruMapel{{ $user->id }}" {{ $user->hasRole('Guru Mapel') ? 'checked' : '' }}>
                                <label class="form-check-label" for="editJabatanGuruMapel{{ $user->id }}">Guru Mapel</label>
                            </div>
                        </div>

                        <div class="mapel-field" style="{{ $user->hasRole('Guru Mapel') ? '' : 'display:none;' }}">
                            <label class="kp-field-label">Mata Pelajaran</label>
                            <input type="text" name="mata_pelajaran" class="form-control mb-3 mapel-input" placeholder="Nama Mata Pelajaran (mis. Matematika)" value="{{ $user->mata_pelajaran }}">
                        </div>

                        <hr class="my-3">

                        <label class="kp-field-label text-danger">Reset Password (Opsional)</label>
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

        {{-- ============== TAB PER ROLE STAFF (Guru BK, Kepala Sekolah, dst) ============== --}}
        @foreach($rolesToShow as $role)
        <div class="tab-pane fade" id="tab-role-{{ \Illuminate\Support\Str::slug($role->name) }}" role="tabpanel">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="m-0 fw-bold text-dark"><i class="fas fa-user me-2 text-primary"></i>Daftar {{ $role->name }}</h6>
                    <div class="d-flex gap-2">
                        <div class="input-group input-group-sm kp-search" style="width: 220px;">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 bg-light search-user-input" data-target="table-role-{{ \Illuminate\Support\Str::slug($role->name) }}" placeholder="Cari nama/email/NIP...">
                        </div>
                        <button class="btn btn-sm btn-primary rounded-3" data-bs-toggle="modal" data-bs-target="#modalTambah{{ \Illuminate\Support\Str::slug($role->name) }}">
                            <i class="fas fa-user-plus me-1"></i> Tambah {{ $role->name }}
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 kp-table" id="table-role-{{ \Illuminate\Support\Str::slug($role->name) }}">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="px-4 py-3">Nama</th>
                                    <th class="py-3">Email</th>
                                    <th class="py-3">JK</th>
                                    <th class="py-3">NIP</th>
                                    <th class="py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($usersByRole[$role->name] as $user)
                                @php
                                    // Role lain milik akun ini selain role tab saat ini -> dipakai untuk
                                    // badge & kolom "Role Lain", supaya TU tahu ini 1 akun rangkap,
                                    // bukan akun ganda/dobel untuk orang yang sama.
                                    $roleLainUser = $user->roles->pluck('name')->diff([$role->name])->values();
                                @endphp
                                <tr class="user-row">
                                    <td class="px-4 py-3 user-name">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="kp-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                                            <div>
                                                <span class="fw-bold text-dark">{{ $user->name }}</span>
                                                @if($roleLainUser->isNotEmpty())
                                                    <span class="badge bg-warning bg-opacity-25 text-warning-emphasis border border-warning-subtle ms-1" style="font-size: 0.65rem; font-weight: 600;" title="Akun ini juga punya role {{ $roleLainUser->join(', ') }}">
                                                        <i class="fas fa-link me-1"></i>rangkap {{ $roleLainUser->join(', ') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 user-email text-muted">{{ $user->email ?? '-' }}</td>
                                    <td class="py-3">{{ $user->jenis_kelamin ?? '-' }}</td>
                                    <td class="py-3 user-nip"><span class="badge bg-light text-dark border">{{ $user->nip ?? '-' }}</span></td>
                                    <td class="py-3">
                                        <div class="d-flex justify-content-center gap-1">
                                            <form action="{{ route('tu.users.status', $user->id) }}" method="POST">
                                                @csrf @method('PATCH')
                                                <button class="btn btn-sm kp-icon-btn {{ $user->is_active ? 'btn-success' : 'btn-secondary' }}" title="Aktif/Nonaktif">
                                                    <i class="fas {{ $user->is_active ? 'fa-check' : 'fa-power-off' }} fa-sm"></i>
                                                </button>
                                            </form>
                                            <button class="btn btn-sm kp-icon-btn btn-info text-white"
                                                    data-bs-toggle="modal" data-bs-target="#modalEditUser{{ $user->id }}-{{ \Illuminate\Support\Str::slug($role->name) }}" title="Edit">
                                                <i class="fas fa-pen fa-sm"></i>
                                            </button>
                                            <form action="{{ route('tu.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Yakin hapus pengguna ini?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm kp-icon-btn btn-danger" title="Hapus">
                                                    <i class="fas fa-trash fa-sm"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fas fa-user-slash fa-2x mb-2 opacity-25 d-block"></i>
                                        Belum ada data {{ $role->name }}.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal Tambah {{ $role->name }}: role langsung terkunci, tidak perlu pilih role lagi --}}
        <div class="modal fade" id="modalTambah{{ \Illuminate\Support\Str::slug($role->name) }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('tu.users.store') }}" method="POST" class="modal-content border-0 shadow kp-modal">
                    @csrf
                    <input type="hidden" name="role" value="{{ $role->name }}">
                    <div class="modal-header border-0 kp-modal-header">
                        <h5 class="modal-title fw-bold text-white"><i class="fas fa-user-plus me-2"></i>Tambah {{ $role->name }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <label class="kp-field-label">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control mb-3" placeholder="Nama Lengkap" required>

                        <div class="row g-2">
                            <div class="col-6">
                                <label class="kp-field-label">Jenis Kelamin</label>
                                <select name="jenis_kelamin" class="form-select mb-3" required>
                                    <option value="" disabled selected>Pilih...</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="kp-field-label">Password</label>
                                <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <label class="kp-field-label">NIP <span class="text-muted fw-normal">(login)</span></label>
                                <input type="text" name="nip" class="form-control mb-3" placeholder="NIP" required>
                            </div>
                            <div class="col-6">
                                <label class="kp-field-label">Email <span class="text-muted fw-normal">(opsional)</span></label>
                                <input type="email" name="email" class="form-control mb-3" placeholder="Diisi sendiri nanti">
                            </div>
                        </div>
                        {{-- Catatan: jabatan Wali Kelas & Guru Mapel sekarang dikelola di tab
                             "Guru" tersendiri (lihat modal Tambah Guru di atas), bukan di sini lagi. --}}
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Modal Edit User (dipakai semua role staff) --}}
    @foreach($rolesToShow as $role)
        @foreach($usersByRole[$role->name] as $user)
        {{-- BUGFIX (audit): id modal sebelumnya cuma "modalEditUser{id}", tanpa slug role.
             Kalau 1 user punya 2 role (fitur gabungan Wali Kelas + Guru Mapel), user itu
             muncul di 2 tab sekaligus dan blok ini nge-render 2 modal dengan id HTML yang
             SAMA persis -> id harus unik. Bootstrap cuma akan membuka modal pertama yang
             ditemukan di DOM, jadi tombol Edit di tab kedua malah membuka modal (dan
             men-submit form) milik tab yang salah. Tambahkan slug($role->name) supaya
             setiap kombinasi user+role punya id modal yang unik. --}}
        <div class="modal fade" id="modalEditUser{{ $user->id }}-{{ \Illuminate\Support\Str::slug($role->name) }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('tu.users.update', $user->id) }}" method="POST" class="modal-content border-0 shadow kp-modal">
                    @csrf @method('PUT')
                    <input type="hidden" name="role" value="{{ $role->name }}">
                    <div class="modal-header border-0 kp-modal-header kp-modal-header-info">
                        <h5 class="modal-title fw-bold text-white"><i class="fas fa-pen me-2"></i>Edit {{ $user->name }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <label class="kp-field-label">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control mb-3" value="{{ $user->name }}" required>

                        <div class="row g-2">
                            <div class="col-6">
                                <label class="kp-field-label">Jenis Kelamin</label>
                                <select name="jenis_kelamin" class="form-select mb-3" required>
                                    <option value="L" {{ $user->jenis_kelamin == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="P" {{ $user->jenis_kelamin == 'P' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="kp-field-label">NIP <span class="text-muted fw-normal">(login)</span></label>
                                <input type="text" name="nip" class="form-control mb-3" placeholder="NIP" value="{{ $user->nip }}" required>
                            </div>
                        </div>

                        <label class="kp-field-label">Email <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="email" name="email" class="form-control mb-3" placeholder="Email" value="{{ $user->email }}">

                        {{-- Catatan: jabatan Wali Kelas & Guru Mapel sekarang dikelola di tab
                             "Guru" tersendiri (lihat modal Edit Guru di atas), bukan di sini lagi. --}}

                        <hr class="my-3">

                        <label class="kp-field-label text-danger">Reset Password (Opsional)</label>
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
    @endforeach
@endsection

@push('styles')
<style>
    /* ===== PERCANTIK HALAMAN KELOLA PENGGUNA (30 Juli 2026) ===== */
    .kp-banner {
        background: linear-gradient(135deg, var(--primary-color, #1f4b3f) 0%, var(--primary-dark, #14362d) 100%);
    }
    .kp-banner-deco {
        position: absolute; right: 1.5rem; top: 50%; transform: translateY(-50%);
        font-size: 7rem; color: rgba(255,255,255,0.08); z-index: 1; pointer-events: none;
    }
    .kp-stat {
        background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15);
        border-radius: 14px; padding: 0.85rem 1rem; display: flex; align-items: center; gap: 0.75rem;
        backdrop-filter: blur(4px);
    }
    .kp-stat-icon {
        width: 40px; height: 40px; border-radius: 10px; background: rgba(255,255,255,0.15);
        color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.05rem; flex-shrink: 0;
    }
    .kp-stat-value { color: #fff; font-weight: 700; font-size: 1.25rem; line-height: 1.1; }
    .kp-stat-label { color: rgba(255,255,255,0.7); font-size: 0.75rem; }

    .kp-tabs .nav-link { border-radius: 10px; color: var(--text-muted, #6b7280); transition: all .18s ease; }
    .kp-tabs .nav-link:hover { background: var(--primary-light, #e8f3ee); color: var(--primary-color, #1f4b3f); }
    .kp-tabs .nav-link.active { background: var(--primary-color, #1f4b3f); color: #fff; box-shadow: 0 4px 10px rgba(31,75,63,.25); }
    .kp-badge-count { background: rgba(0,0,0,0.08); color: inherit; font-weight: 600; }
    .kp-tabs .nav-link.active .kp-badge-count { background: rgba(255,255,255,0.25); color: #fff; }

    .kp-kelas-card { border-top: 3px solid var(--primary-color, #1f4b3f) !important; }
    .kp-kelas-icon {
        width: 42px; height: 42px; border-radius: 12px; background: var(--primary-light, #e8f3ee);
        color: var(--primary-color, #1f4b3f); display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
    }

    .kp-avatar {
        width: 34px; height: 34px; border-radius: 50%; background: var(--primary-light, #e8f3ee);
        color: var(--primary-color, #1f4b3f); display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.85rem; flex-shrink: 0;
    }

    .kp-table thead th { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.03em; }
    .kp-table tbody tr:hover { background-color: var(--primary-light, #e8f3ee); }

    .kp-icon-btn { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; padding: 0; }

    .kp-search .form-control:focus, .kp-search .input-group-text { box-shadow: none; }

    .kp-modal-header { background: linear-gradient(135deg, var(--primary-color, #1f4b3f), var(--primary-dark, #14362d)); }
    .kp-modal-header-info { background: linear-gradient(135deg, #2563a8, #1a4a7a); }
    .kp-modal .modal-body { max-height: 70vh; overflow-y: auto; }
    .kp-field-label { font-size: 0.78rem; font-weight: 600; color: var(--text-muted, #6b7280); margin-bottom: 0.25rem; display: block; }
</style>
@endpush

@push('scripts')
<script>
    // FITUR GABUNGAN TAB GURU (22 Juli 2026): tampilkan/wajibkan field
    // "Mata Pelajaran" hanya kalau checkbox "Guru Mapel" dicentang, dan pastikan
    // minimal 1 jabatan (Wali Kelas / Guru Mapel) dicentang sebelum form disubmit.
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.guru-form').forEach(function (form) {
            var mapelToggle = form.querySelector('.guru-mapel-toggle');
            var mapelField = form.querySelector('.mapel-field');
            var mapelInput = form.querySelector('.mapel-input');

            function syncMapelField() {
                if (!mapelToggle || !mapelField) return;
                var show = mapelToggle.checked;
                mapelField.style.display = show ? '' : 'none';
                if (mapelInput) {
                    mapelInput.required = show;
                    if (!show) mapelInput.value = mapelInput.defaultValue || '';
                }
            }

            if (mapelToggle) {
                mapelToggle.addEventListener('change', syncMapelField);
                syncMapelField();
            }

            form.addEventListener('submit', function (e) {
                var checked = form.querySelectorAll('.jabatan-checkbox:checked');
                if (form.querySelectorAll('.jabatan-checkbox').length > 0 && checked.length === 0) {
                    e.preventDefault();
                    alert('Pilih minimal satu jabatan: Wali Kelas dan/atau Guru Mapel.');
                }
            });
        });
    });

    // Live search sederhana untuk masing-masing tabel per tab (Guru BK, Kepala Sekolah, dst).
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.search-user-input').forEach(function (input) {
            input.addEventListener('keyup', function () {
                var keyword = this.value.toLowerCase();
                var table = document.getElementById(this.getAttribute('data-target'));
                if (!table) return;

                table.querySelectorAll('tbody tr.user-row').forEach(function (row) {
                    var namaEl = row.querySelector('.user-name');
                    var emailEl = row.querySelector('.user-email');
                    var nipEl = row.querySelector('.user-nip');
                    var nama = namaEl ? namaEl.textContent.toLowerCase() : '';
                    var email = emailEl ? emailEl.textContent.toLowerCase() : '';
                    var nip = nipEl ? nipEl.textContent.toLowerCase() : '';
                    row.style.display = (nama.indexOf(keyword) !== -1 || email.indexOf(keyword) !== -1 || nip.indexOf(keyword) !== -1) ? '' : 'none';
                });
            });
        });
    });
</script>
@endpush
