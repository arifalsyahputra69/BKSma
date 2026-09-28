@extends('layouts.siswa')
@section('title','Sesi Konseling')
@section('content')

{{--
    HALAMAN SESI KONSELING SISWA — ditata ulang 6 Agustus 2026.

    Susunan sebelumnya menempatkan daftar sesi di kiri dan "Antrean Saya" di
    kanan, dengan setiap antrean — termasuk yang sudah selesai berbulan-bulan
    lalu — digambar penuh lengkap dengan angka raksasa dan tabel rincian.
    Akibatnya hal yang paling dicari siswa (nomor antrean saya sekarang berapa,
    sudah sampai nomor berapa) terkubur di antara riwayat lama.

    Susunan sekarang mengikuti urutan pertanyaan siswa:
      1. Apa status saya sekarang?      -> kartu sorotan di paling atas
      2. Kapan sesi berikutnya?         -> daftar jadwal di kolom kiri
      3. Dulu saya pernah konseling apa? -> riwayat ringkas di kolom kanan

    Gaya ditulis sebagai CSS biasa berawalan .sk- (sesi konseling), bukan
    utility Tailwind, karena Tailwind di proyek ini di-compile lewat Vite dan
    kelas yang tidak terpakai dibuang saat build.
--}}

<style>
    :root {
        --sk-primary: #1f4b3f;
        --sk-primary-dark: #14362d;
        --sk-primary-light: #e8f3ee;
        --sk-gold: #c9974a;
    }

    .sk-banner {
        background: linear-gradient(135deg, var(--sk-primary) 0%, var(--sk-primary-dark) 100%);
        border-radius: 18px;
        padding: 1.5rem 1.75rem;
        color: #fff;
        position: relative;
        overflow: hidden;
    }
    .sk-banner::after {
        content: '';
        position: absolute;
        right: -40px; top: -60px;
        width: 200px; height: 200px;
        border-radius: 50%;
        background: rgba(255,255,255,.06);
        pointer-events: none;
    }
    .sk-banner h3 { font-weight: 700; margin: 0 0 .2rem; font-size: 1.35rem; }
    .sk-banner p  { margin: 0; color: rgba(255,255,255,.72); font-size: .85rem; }

    .sk-card {
        background: #fff;
        border: 1px solid #eceff1;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(16,24,40,.04);
        overflow: hidden;
    }
    .sk-card-head {
        padding: .9rem 1.15rem;
        border-bottom: 1px solid #f1f3f5;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
    }
    .sk-card-head h6 {
        margin: 0;
        font-weight: 700;
        font-size: .88rem;
        color: #111827;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .sk-card-head h6 i { color: var(--sk-primary); font-size: .82rem; }
    .sk-card-body { padding: 1.15rem; }

    /* ---------- Kartu sorotan: antrean yang sedang berjalan ---------- */
    .sk-hero {
        border-radius: 18px;
        padding: 1.4rem;
        color: #fff;
        background: linear-gradient(135deg, var(--sk-primary) 0%, var(--sk-primary-dark) 100%);
    }
    .sk-hero.is-dipanggil {
        background: linear-gradient(135deg, #b5822f 0%, var(--sk-gold) 100%);
        animation: skPulse 1.8s ease-in-out infinite;
    }
    @keyframes skPulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(201,151,74,.45); }
        50%      { box-shadow: 0 0 0 14px rgba(201,151,74,0); }
    }
    .sk-nomor-grid {
        display: grid;
        grid-template-columns: 1fr 1px 1fr;
        align-items: center;
        gap: 1rem;
        text-align: center;
    }
    .sk-nomor-sep { background: rgba(255,255,255,.22); height: 62px; }
    .sk-nomor-label {
        font-size: .68rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: rgba(255,255,255,.7);
        margin-bottom: .1rem;
    }
    .sk-nomor {
        font-size: 3.1rem;
        font-weight: 800;
        line-height: 1;
        letter-spacing: -.02em;
    }
    .sk-hero-meta {
        margin-top: 1.15rem;
        padding-top: .95rem;
        border-top: 1px solid rgba(255,255,255,.16);
        display: flex;
        flex-wrap: wrap;
        gap: .4rem 1.5rem;
        font-size: .8rem;
        color: rgba(255,255,255,.85);
    }
    .sk-hero-meta i { width: 14px; opacity: .8; margin-right: .35rem; }

    .sk-progress {
        height: 6px;
        border-radius: 99px;
        background: rgba(255,255,255,.2);
        overflow: hidden;
        margin-top: 1rem;
    }
    .sk-progress span {
        display: block;
        height: 100%;
        border-radius: 99px;
        background: #fff;
        transition: width .4s ease;
    }

    /* ---------- Daftar sesi ---------- */
    .sk-sesi {
        border: 1px solid #eceff1;
        border-radius: 14px;
        padding: 1rem 1.1rem;
        margin-bottom: .85rem;
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    .sk-sesi:hover { border-color: #d7e3dd; box-shadow: 0 2px 10px rgba(16,24,40,.05); }
    .sk-sesi.is-dibuka { border-left: 4px solid var(--sk-primary); }
    .sk-sesi.is-ditutup { border-left: 4px solid #e5e7eb; opacity: .85; }
    .sk-sesi.is-batal { border-left: 4px solid #dc3545; opacity: .85; }
    .sk-sesi-nama { font-weight: 700; font-size: .95rem; color: #111827; margin: 0; }
    .sk-sesi-info {
        display: flex;
        flex-wrap: wrap;
        gap: .25rem 1.1rem;
        font-size: .8rem;
        color: #6b7280;
        margin-top: .4rem;
    }
    .sk-sesi-info i { width: 13px; margin-right: .3rem; opacity: .75; }

    .sk-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .2rem .65rem;
        border-radius: 999px;
        font-size: .7rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .sk-pill-hijau  { background: var(--sk-primary-light); color: var(--sk-primary); }
    .sk-pill-abu    { background: #f3f4f6; color: #6b7280; }
    .sk-pill-merah  { background: #fdecec; color: #b02a37; }
    .sk-pill-kuning { background: #fff6e5; color: #92620f; }
    .sk-pill-biru   { background: #e8f0fe; color: #1a56b3; }

    .sk-btn-utama {
        background: var(--sk-primary);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: .55rem 1.1rem;
        font-size: .85rem;
        font-weight: 600;
        transition: background .2s ease, transform .15s ease;
    }
    .sk-btn-utama:hover { background: var(--sk-primary-dark); color: #fff; transform: translateY(-1px); }

    /* ---------- Riwayat ---------- */
    .sk-riwayat-item {
        display: flex;
        gap: .8rem;
        padding: .8rem 0;
        border-bottom: 1px dashed #eef1f3;
    }
    .sk-riwayat-item:last-child { border-bottom: none; padding-bottom: 0; }
    .sk-riwayat-nomor {
        flex-shrink: 0;
        width: 38px; height: 38px;
        border-radius: 10px;
        background: #f3f4f6;
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: .85rem;
    }
    .sk-riwayat-item.is-selesai .sk-riwayat-nomor {
        background: var(--sk-primary-light);
        color: var(--sk-primary);
    }

    .sk-kosong { text-align: center; padding: 2.2rem 1rem; color: #9ca3af; }
    .sk-kosong i { font-size: 2rem; opacity: .35; display: block; margin-bottom: .6rem; }
    .sk-kosong p { margin: 0; font-size: .85rem; }

    @media (max-width: 575.98px) {
        .sk-nomor { font-size: 2.5rem; }
        .sk-banner { padding: 1.2rem 1.25rem; }
    }
</style>

@php
    // Antrean yang benar-benar sedang berjalan.
    //
    // PERBAIKAN (7 Agustus 2026): status antrean saja tidak cukup. Kalau Guru
    // BK lupa menutup sesi kemarin, antrean siswa di sesi itu selamanya
    // berstatus "Menunggu" dan halaman ini terus menyorotinya seolah konseling
    // hari ini -- padahal sesinya sudah lewat. Karena itu sesi induknya ikut
    // diperiksa: harus masih Dibuka DAN tanggalnya belum lewat.
    $antrianAktif = $antrianSaya->first(function ($item) {
        if (! in_array($item->status, ['Menunggu', 'Dipanggil']) || ! $item->sesi) {
            return false;
        }

        return $item->sesi->status === 'Dibuka'
            && \Carbon\Carbon::parse($item->sesi->tanggal)->startOfDay()->gte(\Carbon\Carbon::today());
    });

    // Sisanya jadi riwayat: selesai, dibatalkan, atau menggantung di sesi lama
    // yang tidak pernah ditutup.
    $riwayatAntrian = $antrianSaya->reject(function ($item) use ($antrianAktif) {
        return $antrianAktif && $item->id === $antrianAktif->id;
    });
@endphp

<div class="sk-banner mb-4">
    <h3>Sesi Konseling</h3>
    <p>Ambil nomor antrean, atau daftar lebih dulu kalau sesi belum dibuka.</p>
</div>

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

{{-- ============================================================
     SOROTAN: antrean yang sedang berjalan
     ============================================================ --}}
@if($antrianAktif && $antrianAktif->sesi)
    @php
        $sedangDipanggil = $antrianAktif->sesi->antrians
            ->where('status', 'Dipanggil')
            ->first();

        $sisaAntrean = $antrianAktif->sesi->antrians
            ->where('status', 'Menunggu')
            ->where('nomor_antrian', '<', $antrianAktif->nomor_antrian)
            ->count();

        // Total antrean aktif dipakai sebagai penyebut batang kemajuan, supaya
        // siswa punya gambaran seberapa jauh gilirannya -- bukan sekadar angka
        // "3 orang lagi" yang tidak ada pembandingnya.
        $totalAntrean = $antrianAktif->sesi->antrians
            ->whereNotIn('status', ['Batal', 'Dibatalkan'])
            ->count();

        // PERBAIKAN: siswa ini sendiri harus dikeluarkan dari hitungan "sudah
        // lewat". Tanpa pengurangan 1, siswa yang berada di urutan paling depan
        // (sisa = 0) akan melihat batang kemajuan penuh 100% -- terbaca seolah
        // konselingnya sudah selesai, padahal ia justru baru akan dipanggil.
        $sudahLewat = max(0, $totalAntrean - $sisaAntrean - 1);
        $persen = $totalAntrean > 0 ? round(($sudahLewat / $totalAntrean) * 100) : 0;

        $giliranSaya = $antrianAktif->status === 'Dipanggil';
    @endphp

    <div class="sk-hero mb-4 {{ $giliranSaya ? 'is-dipanggil' : '' }}">
        @if($giliranSaya)
            <div class="text-center mb-3">
                <span class="sk-pill" style="background: rgba(255,255,255,.2); color:#fff;">
                    <i class="fas fa-bell"></i> Sekarang giliran kamu
                </span>
            </div>
        @endif

        <div class="sk-nomor-grid">
            <div>
                <div class="sk-nomor-label">Nomor Saya</div>
                <div class="sk-nomor">{{ sprintf('%02d', $antrianAktif->nomor_antrian) }}</div>
            </div>
            <div class="sk-nomor-sep"></div>
            <div>
                <div class="sk-nomor-label">Sedang Dipanggil</div>
                <div class="sk-nomor">
                    {{ $sedangDipanggil ? sprintf('%02d', $sedangDipanggil->nomor_antrian) : '--' }}
                </div>
            </div>
        </div>

        @if($giliranSaya)
            <div class="text-center mt-3" style="font-size:.9rem;">
                Silakan menuju <strong>{{ $antrianAktif->sesi->tempat }}</strong> sekarang.
            </div>
        @else
            <div class="sk-progress">
                <span style="width: {{ $persen }}%"></span>
            </div>
            <div class="text-center mt-2" style="font-size:.82rem; color:rgba(255,255,255,.85);">
                @if($sisaAntrean > 0)
                    Masih <strong>{{ $sisaAntrean }} orang</strong> sebelum giliranmu
                @else
                    Kamu antrean berikutnya
                @endif
            </div>
        @endif

        <div class="sk-hero-meta">
            <span><i class="fas fa-calendar-day"></i>{{ $antrianAktif->sesi->nama_sesi }}</span>
            <span><i class="fas fa-location-dot"></i>{{ $antrianAktif->sesi->tempat }}</span>
            <span><i class="fas fa-clock"></i>{{ \Carbon\Carbon::parse($antrianAktif->sesi->tanggal)->translatedFormat('d F Y') }}</span>
        </div>

        @if($antrianAktif->status === 'Menunggu' && $antrianAktif->sesi->status === 'Dibuka')
            <form action="{{ route('siswa.sesi.batal', $antrianAktif->id) }}"
                  method="POST"
                  class="mt-3"
                  onsubmit="return confirm('Batalkan antrean ini? Kamu perlu mengambil nomor baru kalau berubah pikiran.');">
                @csrf
                <button class="btn btn-sm w-100"
                        style="background: rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.3); border-radius:10px;">
                    <i class="fas fa-times-circle me-2"></i>Batalkan Antrean
                </button>
            </form>
        @endif
    </div>
@endif

<div class="row g-4">

    {{-- ============================================================
         KOLOM KIRI — daftar tunggu & jadwal sesi
         ============================================================ --}}
    <div class="col-lg-7">

        {{-- Daftar tunggu: dipakai saat Guru BK belum membuka sesi apa pun,
             misalnya siswa membuka aplikasi malam hari setelah Chatbot BK
             menyarankan janji temu. --}}
        @if($permintaanSaya->count())
            @foreach($permintaanSaya as $permintaan)
                @php $batasBerlaku = $permintaan->kedaluwarsaPada(); @endphp
                <div class="sk-card mb-4" style="border-left: 4px solid var(--sk-gold);">
                    <div class="sk-card-head">
                        <h6><i class="fas fa-hourglass-half" style="color: var(--sk-gold);"></i> Kamu di Daftar Tunggu</h6>
                        <span class="sk-pill sk-pill-kuning">Menunggu sesi</span>
                    </div>
                    <div class="sk-card-body">
                        <p class="mb-3" style="font-size:.87rem; color:#4b5563;">
                            Permintaanmu sudah tercatat. Begitu Guru BK membuka sesi, kamu
                            <strong>otomatis mendapat nomor antrean</strong> &mdash; tidak perlu mendaftar lagi.
                        </p>

                        <div class="mb-3" style="background:#fafbfc; border-radius:10px; padding:.85rem;">
                            <div style="font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; color:#9ca3af; margin-bottom:.25rem;">
                                Keperluan
                            </div>
                            <div style="font-size:.87rem; color:#111827;">{{ $permintaan->keperluan }}</div>
                        </div>

                        <div class="d-flex flex-wrap gap-3 mb-3" style="font-size:.78rem; color:#6b7280;">
                            <span><i class="fas fa-paper-plane me-1"></i>Didaftarkan {{ $permintaan->created_at->translatedFormat('d M Y, H:i') }}</span>
                            @if($batasBerlaku)
                                <span><i class="fas fa-hourglass-end me-1"></i>Berlaku sampai {{ $batasBerlaku->translatedFormat('d M Y') }}</span>
                            @endif
                        </div>

                        <form action="{{ route('siswa.sesi.batal-tunggu', $permintaan->id) }}" method="POST">
                            @csrf
                            <button class="btn btn-sm btn-outline-danger rounded-3">
                                <i class="fas fa-times-circle me-2"></i>Batalkan Permintaan
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach

        {{-- Kalau siswa membuka menu Janji Temu langsung dari sidebar, tidak ada
             apa pun yang ditampilkan di sini. Pendaftaran di luar sesi memang
             sengaja tidak ditawarkan dari halaman ini -- satu-satunya jalur
             adalah lewat Chatbot BK, supaya chatbot punya kesempatan membantu
             lebih dulu sebelum keperluannya diteruskan ke Guru BK. --}}
        @elseif(! $adaSesiTerbuka && ! $antrianAktif && $bolehDaftarTunggu)
            {{-- Siswa tiba lewat tombol "Booking Konsultasi" di chatbot. --}}
            <div class="sk-card mb-4">
                <div class="sk-card-head">
                    <h6><i class="fas fa-calendar-plus"></i> Daftar Konseling</h6>
                    <span class="sk-pill sk-pill-biru">Dari Chatbot BK</span>
                </div>
                <div class="sk-card-body">
                    <p style="font-size:.87rem; color:#4b5563;">
                        Guru BK sedang tidak membuka sesi konseling. Kamu tetap bisa mendaftar
                        sekarang &mdash; permintaanmu akan <strong>otomatis menjadi nomor antrean</strong>
                        begitu sesi dibuka, jadi tidak perlu mengantre dari awal.
                    </p>

                    <form action="{{ route('siswa.sesi.daftar-tunggu') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:.85rem;">
                                Keperluan Konseling
                            </label>
                            <textarea
                                class="form-control @error('keperluan') is-invalid @enderror"
                                name="keperluan"
                                rows="3"
                                maxlength="255"
                                style="border-radius:10px; font-size:.88rem;"
                                placeholder="Contoh : Ingin berkonsultasi soal pilihan jurusan kuliah"
                                required>{{ old('keperluan') }}</textarea>
                            @error('keperluan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted" style="font-size:.75rem;">
                                Ditulis apa adanya saja. Hanya Guru BK yang membacanya.
                            </small>
                        </div>

                        <button class="sk-btn-utama">
                            <i class="fas fa-calendar-plus me-2"></i>Daftar Sekarang
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <div class="sk-card">
            <div class="sk-card-head">
                <h6><i class="fas fa-calendar-alt"></i> Jadwal Sesi</h6>
                @if($sesis->count())
                    <span class="sk-pill sk-pill-abu">{{ $sesis->count() }} sesi</span>
                @endif
            </div>
            <div class="sk-card-body">
                @forelse($sesis as $sesi)
                    @php
                        $jumlahAntrean = $sesi->antrians->where('status', '!=', 'Batal')->count();
                        $kelasKartu = $sesi->status === 'Dibuka'
                            ? 'is-dibuka'
                            : ($sesi->status === 'Dibatalkan' ? 'is-batal' : 'is-ditutup');
                    @endphp

                    <div class="sk-sesi {{ $kelasKartu }}">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div style="min-width:0;">
                                <p class="sk-sesi-nama">{{ $sesi->nama_sesi }}</p>
                                <div class="sk-sesi-info">
                                    <span><i class="fas fa-calendar-day"></i>{{ \Carbon\Carbon::parse($sesi->tanggal)->translatedFormat('d F Y') }}</span>
                                    <span><i class="fas fa-location-dot"></i>{{ $sesi->tempat }}</span>
                                    <span><i class="fas fa-users"></i>{{ $jumlahAntrean }} siswa mengantre</span>
                                </div>
                            </div>

                            @if($sesi->status === 'Dibuka')
                                <span class="sk-pill sk-pill-hijau">Dibuka</span>
                            @elseif($sesi->status === 'Ditutup')
                                <span class="sk-pill sk-pill-abu">Ditutup</span>
                            @else
                                <span class="sk-pill sk-pill-merah">Dibatalkan</span>
                            @endif
                        </div>

                        @if($sesi->status === 'Dibatalkan' && $sesi->alasan_pembatalan)
                            <div class="mt-2 p-2" style="background:#fdecec; border-radius:8px; font-size:.78rem; color:#b02a37;">
                                <strong>Alasan:</strong> {{ $sesi->alasan_pembatalan }}
                            </div>
                        @endif

                        @php
                            // Apakah siswa sudah mengantre DI SESI INI. Batasannya
                            // sengaja per sesi, sama persis dengan aturan di
                            // Siswa\SesiKonselingController::ambil(). Versi
                            // sebelumnya menyembunyikan tombol begitu siswa punya
                            // antrean di sesi mana pun -- lebih ketat daripada
                            // backend, dan membuat siswa mengira fiturnya rusak.
                            $sudahMengantreDiSesiIni = $sesi->antrians
                                ->where('siswa_id', $siswaId)
                                ->whereIn('status', ['Menunggu', 'Dipanggil'])
                                ->isNotEmpty();
                        @endphp

                        @if($sesi->status === 'Dibuka' && ! $sudahMengantreDiSesiIni)
                            <button class="sk-btn-utama mt-3"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalAmbil{{ $sesi->id }}">
                                <i class="fas fa-ticket-alt me-2"></i>Ambil Nomor Antrean
                            </button>
                        @elseif($sesi->status === 'Dibuka')
                            <div class="mt-3">
                                <span class="sk-pill sk-pill-biru">
                                    <i class="fas fa-circle-check"></i>
                                    Kamu sudah mengantre di sesi ini
                                </span>
                            </div>
                        @endif
                    </div>

                    {{-- Modal ambil nomor, satu per sesi yang masih dibuka --}}
                    @if($sesi->status === 'Dibuka')
                        <div class="modal fade" id="modalAmbil{{ $sesi->id }}" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content" style="border:none; border-radius:16px; overflow:hidden;">
                                    <form action="{{ route('siswa.sesi.ambil', $sesi->id) }}" method="POST">
                                        @csrf
                                        <div class="modal-header text-white"
                                             style="background: linear-gradient(135deg, var(--sk-primary), var(--sk-primary-dark)); border:none;">
                                            <h6 class="modal-title fw-bold mb-0">Ambil Nomor Antrean</h6>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>

                                        <div class="modal-body">
                                            <div class="mb-3 p-3" style="background:#fafbfc; border-radius:10px;">
                                                <div class="d-flex justify-content-between mb-1" style="font-size:.83rem;">
                                                    <span class="text-muted">Sesi</span>
                                                    <strong>{{ $sesi->nama_sesi }}</strong>
                                                </div>
                                                <div class="d-flex justify-content-between mb-1" style="font-size:.83rem;">
                                                    <span class="text-muted">Tempat</span>
                                                    <strong>{{ $sesi->tempat }}</strong>
                                                </div>
                                                <div class="d-flex justify-content-between" style="font-size:.83rem;">
                                                    <span class="text-muted">Tanggal</span>
                                                    <strong>{{ \Carbon\Carbon::parse($sesi->tanggal)->translatedFormat('d F Y') }}</strong>
                                                </div>
                                            </div>

                                            <label class="form-label fw-semibold" style="font-size:.85rem;">
                                                Keperluan Konseling
                                            </label>
                                            <textarea class="form-control"
                                                      name="keperluan"
                                                      rows="4"
                                                      maxlength="255"
                                                      style="border-radius:10px; font-size:.88rem;"
                                                      placeholder="Contoh : Konsultasi akademik"
                                                      required></textarea>
                                            <small class="text-muted" style="font-size:.75rem;">
                                                Hanya Guru BK yang membaca keperluanmu.
                                            </small>
                                        </div>

                                        <div class="modal-footer" style="border-top:1px solid #f1f3f5;">
                                            <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">
                                                Tutup
                                            </button>
                                            <button class="sk-btn-utama">Ambil Nomor</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                @empty
                    <div class="sk-kosong">
                        <i class="fas fa-calendar-times"></i>
                        <p>Tidak ada jadwal sesi untuk hari ini ke depan.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ============================================================
         KOLOM KANAN — riwayat antrean
         ============================================================ --}}
    <div class="col-lg-5">
        <div class="sk-card">
            <div class="sk-card-head">
                <h6><i class="fas fa-clock-rotate-left"></i> Riwayat Antrean</h6>
                @if($riwayatAntrian->count())
                    <span class="sk-pill sk-pill-abu">{{ $riwayatAntrian->count() }}</span>
                @endif
            </div>
            <div class="sk-card-body">
                @forelse($riwayatAntrian as $antrian)
                    <div class="sk-riwayat-item {{ $antrian->status === 'Selesai' ? 'is-selesai' : '' }}">
                        <div class="sk-riwayat-nomor">
                            {{ sprintf('%02d', $antrian->nomor_antrian) }}
                        </div>
                        <div style="min-width:0; flex:1;">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <strong style="font-size:.85rem; color:#111827;">
                                    {{ $antrian->sesi->nama_sesi ?? 'Sesi tidak ditemukan' }}
                                </strong>

                                @if($antrian->status === 'Selesai')
                                    <span class="sk-pill sk-pill-hijau">Selesai</span>
                                @elseif($antrian->status === 'Dibatalkan')
                                    <span class="sk-pill sk-pill-merah">Dibatalkan BK</span>
                                @else
                                    <span class="sk-pill sk-pill-abu">Dibatalkan</span>
                                @endif
                            </div>

                            <div style="font-size:.76rem; color:#9ca3af; margin-top:.15rem;">
                                @if($antrian->sesi)
                                    {{ \Carbon\Carbon::parse($antrian->sesi->tanggal)->translatedFormat('d M Y') }}
                                    &middot; {{ $antrian->sesi->tempat }}
                                @else
                                    &mdash;
                                @endif
                            </div>

                            @if($antrian->status === 'Dibatalkan' && $antrian->sesi && $antrian->sesi->alasan_pembatalan)
                                <div class="mt-2 p-2" style="background:#fdecec; border-radius:8px; font-size:.75rem; color:#b02a37;">
                                    <strong>Alasan:</strong> {{ $antrian->sesi->alasan_pembatalan }}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="sk-kosong">
                        <i class="fas fa-inbox"></i>
                        <p>Belum ada riwayat konseling.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@if($antrianAktif)
<script>
    // Halaman disegarkan otomatis tiap 15 detik selagi siswa punya antrean
    // berjalan, supaya nomor "Sedang Dipanggil" dan sisa antrean selalu
    // terbaru tanpa perlu menekan refresh -- mirip layar antrean rumah sakit.
    //
    // CATATAN: jangan pernah menulis nama direktif Blade (tanda at diikuti
    // section, extends, if, php, dan sejenisnya) sebagai teks biasa di dalam
    // blok script atau style, termasuk di dalam komentar seperti ini. Blade
    // memindai seluruh berkas dan tetap mengeksekusinya sebagai direktif,
    // sehingga bisa membuka section baru di tengah halaman dan menggagalkan
    // pemasangan layout.
    setTimeout(function () {
        window.location.reload();
    }, 15000);
</script>
@endif

@endsection
