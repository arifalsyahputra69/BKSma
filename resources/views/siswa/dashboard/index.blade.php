@extends('layouts.siswa')

@section('title', 'Dashboard Siswa')

@section('content')
<div class="row g-4">

    {{-- ===== HERO WELCOME ===== --}}
    <div class="col-12">
        <div class="hero-card card border-0 rounded-4 text-white p-4 p-md-5 position-relative overflow-hidden">
            <div class="hero-decor hero-decor-1"></div>
            <div class="hero-decor hero-decor-2"></div>
            <div class="position-relative">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge bg-white bg-opacity-25 rounded-pill px-3 py-2 fw-normal">
                        <i class="fas fa-sun me-1"></i> {{ now()->translatedFormat('l, d F Y') }}
                    </span>
                    <span class="badge bg-white bg-opacity-25 rounded-pill px-3 py-2 fw-normal">
                        <i class="fas fa-graduation-cap me-1"></i>
                        Kelas {{ $siswa?->kelas?->nama_kelas ?? 'belum diatur' }}
                    </span>
                </div>
                <h3 class="fw-bold mb-2">Halo, {{ Auth::user()->name }}! 👋</h3>
                <p class="mb-0 opacity-90 col-lg-8">Selamat datang di SIM BK SMA Kartika I-5 Padang. Gunakan chatbot untuk konsultasi cepat atau buat jadwal konseling dengan Guru BK.</p>
            </div>
        </div>
    </div>

    {{-- Banner ini muncul selama laporan perbaikan kelas belum ditindaklanjuti
         TU. Sisa waktunya ditampilkan terang-terangan supaya siswa tahu ada
         tenggat, bukan mendadak terkunci tanpa peringatan. --}}
    @if($siswa && $siswa->status_konfirmasi_kelas === false)
        @php $batasTenggang = $siswa->batasTenggangKelas(); @endphp
        <div class="col-12">
            <div class="alert alert-warning border-0 shadow-sm rounded-4 d-flex align-items-start">
                <i class="fas fa-hourglass-half fa-lg me-3 mt-1"></i>
                <div>
                    <strong class="d-block">Laporan Perbaikan Kelas Sedang Diproses</strong>
                    Kamu melaporkan kelasmu berubah
                    @if($siswa->kelasTujuan)
                        menjadi <strong>{{ $siswa->kelasTujuan->nama_kelas }}</strong>
                    @endif
                    pada {{ $siswa->waktu_tidak_konfirmasi?->translatedFormat('d F Y') }}.
                    Bagian TU akan memperbarui datanya.
                    @if($batasTenggang)
                        <span class="d-block mt-1 small">
                            Kalau sampai <strong>{{ $batasTenggang->translatedFormat('d F Y') }}</strong>
                            belum diperbarui, akunmu akan dibatasi sementara. Hubungi TU bila mendesak.
                        </span>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if($siswa && !$siswa->is_wa_verified)
    <div class="col-12">
        <div class="alert alert-warning border-0 shadow-sm rounded-4 d-flex align-items-center">
            <i class="fas fa-exclamation-triangle fa-lg me-3"></i>
            <div>
                <strong class="d-block">Data Wali Belum Terverifikasi</strong>
                Nomor WA orang tua kamu belum diverifikasi. Mohon lengkapi di <a href="{{ route('profil.umum.index') }}" class="alert-link">halaman profil</a> agar Guru BK dapat menghubungi orang tua jika diperlukan.
            </div>
        </div>
    </div>
    @endif

    {{-- ===== QUICK STATUS ROW ===== --}}
    <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm rounded-4 p-4 status-card">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-circle bg-primary-soft text-brand">
                    <i class="fas fa-calendar-check fa-lg"></i>
                </div>
                <div class="flex-grow-1">
                    <p class="text-muted small mb-1">Status Antrean Konseling</p>
                    @if($antrianAktif)
                        <h6 class="fw-bold mb-0">
                            No. {{ $antrianAktif->nomor_antrian }} &middot; {{ $antrianAktif->status }}
                        </h6>
                        <p class="text-muted small mb-0">{{ $antrianAktif->sesi->nama_sesi ?? 'Sesi Konseling' }} &mdash; {{ optional($antrianAktif->sesi->tanggal)->translatedFormat('d M Y') }}</p>
                    @else
                        <h6 class="fw-bold mb-0 text-muted">Belum ada antrean aktif</h6>
                    @endif
                </div>
                <a href="{{ route('siswa.sesi.index') }}" class="btn btn-sm btn-outline-brand rounded-pill px-3">Lihat</a>
            </div>
        </div>
    </div>

    {{-- Kartu notifikasi diganti kartu kelas (5 Agustus 2026): siswa lebih
         perlu tahu kelasnya tercatat sebagai apa ketimbang menghitung
         notifikasi -- lonceng notifikasi sudah ada di bilah atas. --}}
    <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm rounded-4 p-4 status-card">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-circle bg-success-soft text-success">
                    <i class="fas fa-graduation-cap fa-lg"></i>
                </div>
                <div class="flex-grow-1">
                    <p class="text-muted small mb-1">Kelas Kamu Saat Ini</p>
                    <h6 class="fw-bold mb-0">
                        {{ $siswa?->kelas?->nama_kelas ?? 'Belum diatur' }}
                    </h6>
                    <p class="text-muted small mb-0">
                        @if($siswa?->nisn)
                            NISN {{ $siswa->nisn }}
                        @else
                            Data kelas dikelola oleh TU
                        @endif
                    </p>
                </div>
                @if($siswa && $siswa->status_konfirmasi_kelas === true)
                    <span class="badge rounded-pill bg-success-soft text-success">
                        <i class="fas fa-check me-1"></i>Terkonfirmasi
                    </span>
                @elseif($siswa && $siswa->status_konfirmasi_kelas === false)
                    <span class="badge rounded-pill bg-warning-soft text-warning">
                        <i class="fas fa-clock me-1"></i>Diproses
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- ===== MAIN ACTION CARDS ===== --}}
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0 rounded-4 text-center p-4 dashboard-card">
            <div class="icon-circle bg-primary-soft text-brand mb-3 mx-auto">
                <i class="fas fa-robot fa-2x"></i>
            </div>
            <h5 class="fw-bold">Chatbot BK</h5>
            <p class="text-muted small">Tanya jawab otomatis seputar masalah sekolah.</p>
            <a href="{{ route('siswa.chatbot.index') }}" class="btn btn-brand rounded-pill mt-auto px-4">
                Mulai Chat <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0 rounded-4 text-center p-4 dashboard-card">
            <div class="icon-circle bg-info-soft text-info mb-3 mx-auto">
                <i class="fas fa-calendar-check fa-2x"></i>
            </div>
            <h5 class="fw-bold">Jadwal Konseling</h5>
            <p class="text-muted small">Atur jadwal pertemuan tatap muka dengan Guru BK.</p>
            <a href="{{ route('siswa.sesi.index') }}" class="btn btn-info text-white rounded-pill mt-auto px-4">
                Buka <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0 rounded-4 text-center p-4 dashboard-card">
            <div class="icon-circle bg-success-soft text-success mb-3 mx-auto">
                <i class="fas fa-qrcode fa-2x"></i>
            </div>
            <h5 class="fw-bold">Absensi QR</h5>
            <p class="text-muted small">Lihat riwayat kehadiran kamu di sekolah.</p>
            <a href="{{ route('siswa.absensi.riwayat') }}" class="btn btn-success rounded-pill mt-auto px-4">
                Buka <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>

    {{-- ===== ARTIKEL TERBARU ===== --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="fw-bold mb-0"><i class="fas fa-newspaper text-brand me-2"></i>Artikel BK Terbaru</h5>
                <a href="{{ route('siswa.artikel.index') }}" class="small text-brand fw-semibold text-decoration-none">
                    Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>

            @if($artikelTerbaru->isNotEmpty())
                <div class="row g-3">
                    @foreach($artikelTerbaru as $artikel)
                    <div class="col-md-4">
                        <a href="{{ route('siswa.artikel.show', $artikel->slug) }}" class="text-decoration-none">
                            <div class="artikel-mini-card rounded-4 h-100">
                                @if($artikel->gambar_sampul)
                                    <img src="{{ asset('storage/artikel-bk/' . $artikel->gambar_sampul) }}" class="artikel-mini-img" alt="{{ $artikel->judul }}">
                                @else
                                    <div class="artikel-mini-img d-flex align-items-center justify-content-center bg-primary-soft">
                                        <i class="fas fa-newspaper fa-2x text-brand opacity-50"></i>
                                    </div>
                                @endif
                                <div class="p-3">
                                    <span class="badge bg-primary-soft text-brand rounded-pill small mb-2">{{ $artikel->kategori }}</span>
                                    <h6 class="fw-bold text-dark mb-0 artikel-mini-title">{{ $artikel->judul }}</h6>
                                </div>
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center text-muted py-4">
                    <i class="fas fa-newspaper fa-2x mb-2 opacity-50"></i>
                    <p class="mb-0 small">Belum ada artikel yang dipublikasikan.</p>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- =====================================================================
     POPUP KONFIRMASI KELAS

     Dipindah ke sini (5 Agustus 2026) dari halaman tersendiri
     resources/views/siswa/konfirmasi-kelas.blade.php yang kini dihapus.
     Alasannya: halaman lama memblokir seluruh aplikasi hanya untuk satu
     pertanyaan, dan siswa tidak bisa melihat kelas mana yang sedang
     dipersoalkan. Sekarang pertanyaannya muncul di atas dashboard, dengan
     kelas yang tercatat ditampilkan jelas di dalamnya.

     Popup ini SENGAJA tidak bisa ditutup (tanpa tombol silang, tanpa Esc,
     tanpa klik-luar) selama siswa belum menjawab -- fungsinya memang
     menahan, sama seperti halaman lama.
     ===================================================================== --}}
@if($perluKonfirmasiKelas)
<div class="modal fade" id="modalKonfirmasiKelas" tabindex="-1"
     data-bs-backdrop="static" data-bs-keyboard="false"
     aria-labelledby="labelKonfirmasiKelas" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">

            <div class="modal-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="icon-circle bg-primary-soft text-brand mx-auto mb-3">
                        <i class="fas fa-school fa-lg"></i>
                    </div>
                    <h5 class="fw-bold mb-2" id="labelKonfirmasiKelas">Konfirmasi Kelas</h5>
                    <p class="text-muted small mb-0">
                        Halo <strong>{{ Auth::user()->name }}</strong>, menurut data sekolah kamu tercatat di kelas
                    </p>
                    <div class="fs-4 fw-bold text-brand my-2">
                        {{ $siswa?->kelas?->nama_kelas ?? 'Belum diatur' }}
                    </div>
                    <p class="text-muted small mb-0">Apakah ini masih sesuai?</p>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger rounded-3 small">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $pesanError)
                                <li>{{ $pesanError }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('siswa.konfirmasi.store') }}" method="POST" id="formKonfirmasiKelas">
                    @csrf

                    {{-- Jawaban dikirim lewat input tersembunyi, BUKAN lewat
                         atribut name/value pada tombol submit. Nilai pada
                         tombol hanya ikut terkirim kalau browser menganggap
                         form disubmit OLEH tombol itu -- kalau form terkirim
                         dengan cara lain (mis. tekan Enter di kolom teks),
                         nilainya hilang dan server menerima form tanpa
                         jawaban sama sekali. Input tersembunyi selalu ikut. --}}
                    <input type="hidden" name="jawaban" id="inputJawabanKelas" value="ya">

                    {{-- LANGKAH 1 --}}
                    <div id="langkahSatuKelas" class="d-grid gap-2">
                        <button type="submit" class="btn btn-brand btn-lg rounded-3 fw-bold" id="btnKelasSama">
                            <i class="fas fa-check-circle me-2"></i>Ya, kelas saya masih sama
                        </button>
                        <button type="button" class="btn btn-outline-danger rounded-3 fw-bold" id="btnKelasBerubah">
                            Tidak, kelas saya berubah
                        </button>
                    </div>

                    {{-- LANGKAH 2 — baru tampil setelah siswa menekan "kelas berubah" --}}
                    <div id="langkahDuaKelas" class="d-none">
                        <div class="alert alert-light border rounded-3 small mb-3">
                            <i class="fas fa-circle-info me-1 text-brand"></i>
                            Isi kelas kamu yang benar. Data ini dikirim ke TU supaya
                            mereka bisa langsung memindahkanmu tanpa perlu bertanya lagi.
                        </div>

                        <div class="mb-3">
                            <label for="kelasTujuanId" class="form-label fw-semibold small">
                                Kelas saya sekarang <span class="text-danger">*</span>
                            </label>
                            <select name="kelas_tujuan_id" id="kelasTujuanId" class="form-select rounded-3">
                                <option value="">— Pilih kelas —</option>
                                @foreach($daftarKelas as $kelasItem)
                                    <option value="{{ $kelasItem->id }}"
                                        @selected(old('kelas_tujuan_id') == $kelasItem->id)>
                                        {{ $kelasItem->nama_kelas }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="catatanKelas" class="form-label fw-semibold small">
                                Catatan tambahan <span class="text-muted fw-normal">(opsional)</span>
                            </label>
                            <textarea name="catatan_perbaikan_kelas" id="catatanKelas" rows="3"
                                      class="form-control rounded-3"
                                      maxlength="500"
                                      placeholder="Contoh: saya pindah jurusan sejak awal semester ini.">{{ old('catatan_perbaikan_kelas') }}</textarea>
                            <div class="form-text small">Maksimal 500 karakter.</div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger rounded-3 fw-bold" id="btnKirimLaporanKelas">
                                <i class="fas fa-paper-plane me-2"></i>Kirim Laporan ke TU
                            </button>
                            <button type="button" class="btn btn-link text-muted text-decoration-none small" id="btnBatalKelas">
                                Kembali
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
@endif

<style>
:root {
    --brand: #1f4b3f;
    --brand-dark: #14362d;
}
.hero-card {
    background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
}
.hero-decor {
    position: absolute;
    border-radius: 50%;
    background: rgba(255,255,255,0.08);
}
.hero-decor-1 { width: 220px; height: 220px; right: -60px; top: -80px; }
.hero-decor-2 { width: 140px; height: 140px; right: 80px; bottom: -70px; }

.text-brand { color: var(--brand) !important; }
.bg-primary-soft { background-color: rgba(31,75,63,0.1) !important; }
.bg-info-soft { background-color: rgba(13,202,240,0.12) !important; }
.bg-success-soft { background-color: rgba(25,135,84,0.12) !important; }
.bg-warning-soft { background-color: rgba(255,193,7,0.15) !important; }

.btn-brand { background-color: var(--brand); border-color: var(--brand); color: #fff; }
.btn-brand:hover { background-color: var(--brand-dark); border-color: var(--brand-dark); color: #fff; }
.btn-outline-brand { border-color: var(--brand); color: var(--brand); }
.btn-outline-brand:hover { background-color: var(--brand); color: #fff; }

.dashboard-card, .status-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.dashboard-card:hover, .status-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1) !important;
}
.icon-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.dashboard-card .icon-circle { width: 72px; height: 72px; }

.artikel-mini-card { background: #fff; border: 1px solid #eef0f2; overflow: hidden; transition: box-shadow .2s ease, transform .2s ease; }
.artikel-mini-card:hover { box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.08); transform: translateY(-2px); }
.artikel-mini-img { width: 100%; height: 120px; object-fit: cover; }
.artikel-mini-title {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    font-size: 0.92rem;
}
</style>

@if($perluKonfirmasiKelas)
<script>
// Dibungkus DOMContentLoaded karena isi halaman ini dirender SEBELUM tag
// <script> Bootstrap di layouts/siswa.blade.php. Tanpa penundaan ini,
// bootstrap.Modal belum ada saat baris di bawah dijalankan.
//
// CATATAN: jangan pernah menulis nama direktif Blade (@ + section, extends,
// if, php, dsb) sebagai teks biasa di dalam blok <script> atau <style> --
// termasuk di dalam komentar JavaScript seperti ini. Blade memindai SELURUH
// berkas dan tetap mengeksekusinya sebagai direktif, sehingga bisa membuka
// section baru di tengah halaman dan menggagalkan pemasangan layout.
// Kalau memang perlu menyebutnya, tulis dobel (@@) supaya di-escape.
document.addEventListener('DOMContentLoaded', function () {
    var elModal    = document.getElementById('modalKonfirmasiKelas');
    var langkah1   = document.getElementById('langkahSatuKelas');
    var langkah2   = document.getElementById('langkahDuaKelas');
    var inputJawab = document.getElementById('inputJawabanKelas');
    var selKelas   = document.getElementById('kelasTujuanId');
    if (!elModal || !langkah1 || !langkah2 || !inputJawab) return;

    new bootstrap.Modal(elModal).show();

    function tampilkanLangkah2(tampil) {
        langkah1.classList.toggle('d-none', tampil);
        langkah2.classList.toggle('d-none', !tampil);

        // Nilai 'jawaban' mengikuti langkah yang sedang terbuka, jadi tidak
        // mungkin terkirim "tidak" sementara siswa masih di layar pertama.
        inputJawab.value = tampil ? 'tidak' : 'ya';

        // Atribut required dipasang/dilepas mengikuti tampilnya langkah 2.
        // Kalau dibiarkan menempel terus, tombol "kelas saya masih sama"
        // akan diblokir browser karena ada field wajib yang tersembunyi dan
        // kosong -- siswa menekan tombol dan seolah tidak terjadi apa-apa.
        if (selKelas) {
            if (tampil) {
                selKelas.setAttribute('required', 'required');
            } else {
                selKelas.removeAttribute('required');
            }
        }
    }

    document.getElementById('btnKelasBerubah')?.addEventListener('click', function () {
        tampilkanLangkah2(true);
    });

    document.getElementById('btnBatalKelas')?.addEventListener('click', function () {
        tampilkanLangkah2(false);
    });

    // Kalau server menolak isian (mis. kelas belum dipilih), halaman dimuat
    // ulang dan langkah 2 harus langsung terbuka -- kalau tidak, siswa cuma
    // melihat pesan error tanpa tahu kolom mana yang bermasalah.
    @if($errors->any() || old('jawaban') === 'tidak')
        tampilkanLangkah2(true);
    @endif
});
</script>
@endif
@endsection