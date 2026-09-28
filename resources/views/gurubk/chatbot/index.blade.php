@extends('layouts.guru')

@section('title', 'Kelola Aturan Chatbot')

@section('content')
<div class="page-header-banner mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="page-header-icon"><i class="fas fa-robot"></i></div>
        <div>
            <h3 class="fw-bold mb-1 text-white">Aturan Chatbot</h3>
            <small class="text-white-50">Kelola kata kunci & balasan otomatis chatbot BK untuk siswa</small>
        </div>
    </div>
</div>
{{-- Halaman ini sebelumnya tidak menampilkan pesan apa pun setelah aksi
     dijalankan, jadi Guru BK tidak pernah tahu apakah tersimpan atau ditolak.
     Terutama penting sekarang: aksi ubah bisa ditolak karena induk melingkar,
     dan penolakan tanpa pesan hanya terlihat seperti tombol yang tidak bekerja. --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3">
        <i class="fas fa-circle-check me-2"></i>{{ session('success') }}
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-3">
        <i class="fas fa-circle-exclamation me-2"></i>{{ session('error') }}
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card shadow-sm border-0 rounded-4">
    <div class="card-header bg-white p-4 border-0 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Aturan Balasan Otomatis</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddRule">
            <i class="fas fa-plus me-1"></i> Tambah Rule
        </button>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th class="px-4">Keyword (Pemicu)</th>
                    <th>Respons</th>
                    <th>Kategori</th>
                    <th>Induk Percakapan</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rules as $rule)
                <tr>
                    <td class="px-4"><code>{{ $rule->keyword }}</code></td>
                    <td>{{ $rule->response }}</td>
                    <td><span class="badge bg-info text-white">{{ $rule->category }}</span></td>
                    <td>{{ $rule->parent ? $rule->parent->keyword : '-' }}</td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-2">
                            <button class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEditRule{{ $rule->id }}"
                                    title="Ubah aturan ini">
                                <i class="fas fa-pen"></i>
                            </button>

                            <form action="{{ route('gurubk.chatbot.destroy', $rule->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus rule ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger" title="Hapus aturan ini"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Modal ubah, satu per aturan. Sengaja dibuat terpisah dari modal tambah:
     modal tunggal yang diisi lewat JavaScript memang lebih hemat, tapi teks
     balasan chatbot sering memuat tanda kutip dan baris baru, dan menyuntikkan
     itu lewat atribut data gampang rusak. --}}
@foreach($rules as $ruleEdit)
<div class="modal fade" id="modalEditRule{{ $ruleEdit->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('gurubk.chatbot.update', $ruleEdit->id) }}" method="POST" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Ubah Rule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small fw-bold">Keyword (Pemicu)</label>
                {{-- Sengaja TIDAK memakai old(). Karena setiap baris punya modal
                     sendiri, old() akan mengisi seluruh modal dengan masukan dari
                     satu form yang gagal -- Guru BK membuka aturan lain dan
                     menemukan isinya sudah berubah tanpa ia sentuh. --}}
                <input type="text" name="keyword" class="form-control mb-3"
                       value="{{ $ruleEdit->keyword }}" required>

                <label class="form-label small fw-bold">Respons Chatbot</label>
                <textarea name="response" class="form-control mb-3" rows="3" required>{{ $ruleEdit->response }}</textarea>

                <label class="form-label small fw-bold">Kategori</label>
                <select name="category" class="form-select mb-3">
                    @foreach(['umum' => 'Umum', 'akademik' => 'Akademik', 'pribadi' => 'Pribadi', 'sosial' => 'Sosial'] as $nilai => $label)
                        <option value="{{ $nilai }}" @selected($ruleEdit->category === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>

                <label class="form-label small fw-bold text-primary">Induk Percakapan (Opsional)</label>
                <select name="parent_id" class="form-select mb-1">
                    <option value="">-- Tidak Ada (Sebagai Topik Utama) --</option>
                    @foreach($rules as $calonInduk)
                        {{-- Aturan ini sendiri dikeluarkan dari pilihan; sisanya
                             tetap ditawarkan dan lingkaran yang lebih dalam
                             dicegah di controller saat disimpan. --}}
                        @if($calonInduk->id !== $ruleEdit->id)
                            <option value="{{ $calonInduk->id }}" @selected($ruleEdit->parent_id == $calonInduk->id)>
                                {{ $calonInduk->keyword }} - {{ Str::limit($calonInduk->response, 25) }}
                            </option>
                        @endif
                    @endforeach
                </select>
                <small class="text-muted" style="font-size: 11px;">
                    Mengubah induk memindahkan aturan ini ke cabang percakapan lain.
                </small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endforeach

<div class="modal fade" id="modalAddRule" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('gurubk.chatbot.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Tambah Rule Baru</h5></div>
            <div class="modal-body">
                <label class="form-label small fw-bold">Keyword (Pemicu)</label>
                <input type="text" name="keyword" class="form-control mb-3" placeholder="Contoh: bolos, jadwal, nilai" required>
                
                <label class="form-label small fw-bold">Respons Chatbot</label>
                <textarea name="response" class="form-control mb-3" rows="3" placeholder="Jawaban yang akan diberikan chatbot..." required></textarea>
                
                <label class="form-label small fw-bold">Kategori</label>
                <select name="category" class="form-select mb-3">
                    <option value="umum">Umum</option>
                    <option value="akademik">Akademik</option>
                    <option value="pribadi">Pribadi</option>
                    <option value="sosial">Sosial</option>
                </select>

                <label class="form-label small fw-bold text-primary">Pilih Induk Percakapan (Opsional)</label>
                <select name="parent_id" class="form-select mb-1">
                    <option value="">-- Tidak Ada (Sebagai Topik Utama) --</option>
                    @foreach($rules as $rule)
                        <option value="{{ $rule->id }}">{{ $rule->keyword }} - {{ Str::limit($rule->response, 25) }}</option>
                    @endforeach
                </select>
                <small class="text-muted" style="font-size: 11px;">Pilih induk jika keyword ini adalah lanjutan dari percakapan sebelumnya.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Rule</button>
            </div>
        </form>
    </div>
</div>
@endsection