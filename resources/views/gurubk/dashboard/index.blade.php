@extends('layouts.guru')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

    .bk-page {
        font-family: 'Plus Jakarta Sans', sans-serif;
        color: #263238;
    }

    .bk-page .bk-serif {
        font-family: 'Fraunces', serif;
    }

    /* ---------- Header banner ---------- */
    .bk-header {
        position: relative;
        background: linear-gradient(120deg, #1F4B43 0%, #285C51 60%, #2E6A5C 100%);
        border-radius: 22px;
        padding: 2.1rem 2.25rem;
        overflow: hidden;
        margin-bottom: 2rem;
        isolation: isolate;
    }

    .bk-header::before {
        content: "";
        position: absolute;
        right: -60px;
        top: -60px;
        width: 260px;
        height: 260px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(176,133,68,0.35) 0%, rgba(176,133,68,0) 70%);
        z-index: 0;
    }

    .bk-motif {
        position: absolute;
        right: 28px;
        bottom: -18px;
        width: 130px;
        height: 130px;
        opacity: 0.5;
        z-index: 0;
    }

    .bk-header-content { position: relative; z-index: 1; }

    .bk-eyebrow {
        color: rgba(255,255,255,0.65);
        font-size: 0.78rem;
        letter-spacing: 1.6px;
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 0.35rem;
    }

    .bk-header h3 { color: #fff; font-weight: 600; margin-bottom: 0.15rem; }
    .bk-header p { color: rgba(255,255,255,0.8); margin-bottom: 0; }

    .bk-date-chip {
        background: rgba(255,255,255,0.12);
        border: 1px solid rgba(255,255,255,0.18);
        border-radius: 14px;
        padding: 0.65rem 1.15rem;
        text-align: right;
        backdrop-filter: blur(2px);
    }

    .bk-date-chip .bk-date-label {
        color: rgba(255,255,255,0.6);
        font-size: 0.72rem;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-bottom: 0.1rem;
    }

    .bk-date-chip .bk-date-value { color: #F2C879; font-weight: 600; margin-bottom: 0; }

    /* ---------- Stat cards ---------- */
    .bk-stat {
        background: #fff;
        border: 1px solid #E7E4DA;
        border-radius: 18px;
        padding: 1.5rem 1.5rem 1.35rem;
        height: 100%;
        position: relative;
        border-top: 3px solid var(--accent, #1F4B43);
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .bk-stat:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 28px -18px rgba(31,75,67,0.35);
    }

    .bk-stat-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1.1rem;
    }

    .bk-stat-label {
        color: #7C8A86;
        font-size: 0.78rem;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        font-weight: 700;
        margin-bottom: 0.3rem;
    }

    .bk-stat-value { font-size: 2.35rem; font-weight: 600; line-height: 1; color: #1B2B27; }

    .bk-stat-icon {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        background: var(--accent-tint, #EAF2EF);
        color: var(--accent, #1F4B43);
        flex-shrink: 0;
        box-shadow: inset 0 0 0 1px rgba(0,0,0,0.03);
    }

    .bk-stat-foot { font-size: 0.83rem; color: #8A968F; }
    .bk-stat-foot a { color: var(--accent, #1F4B43); text-decoration: none; font-weight: 600; }
    .bk-stat-foot a:hover { text-decoration: underline; }

    .bk-stat--siswa   { --accent: #1F4B43; --accent-tint: #E6EEEC; }
    .bk-stat--chat    { --accent: #5B7FA6; --accent-tint: #E9EFF5; }
    .bk-stat--aktif   { --accent: #B08544; --accent-tint: #F5EEE0; }
    .bk-stat--jurnal  { --accent: #7A5C8F; --accent-tint: #EFE9F3; }

    /* ---------- Activity log ---------- */
    .bk-log-card { background: #fff; border: 1px solid #E7E4DA; border-radius: 18px; overflow: hidden; }

    .bk-log-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #EEECE3;
        display: flex;
        align-items: center;
        gap: 0.65rem;
    }

    .bk-log-header .bk-log-icon {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #E6EEEC;
        color: #1F4B43;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
    }

    .bk-log-header h6 { margin: 0; font-weight: 700; color: #1B2B27; }

    .bk-table thead th {
        background: #FAF9F5;
        color: #8A968F;
        font-size: 0.72rem;
        letter-spacing: 0.6px;
        text-transform: uppercase;
        font-weight: 700;
        border: none;
        padding: 0.9rem 1.25rem;
    }

    .bk-table tbody td { padding: 0.85rem 1.25rem; border-top: 1px solid #F2F0E9; vertical-align: middle; }
    .bk-table tbody tr:hover { background: #FAFAF6; }

    .bk-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #E9EFF5;
        color: #5B7FA6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
        flex-shrink: 0;
    }

    .bk-time-pill {
        background: #F4F6F4;
        border: 1px solid #E7E4DA;
        border-radius: 10px;
        padding: 0.3rem 0.75rem;
        font-size: 0.8rem;
        font-weight: 600;
        color: #37473F;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .bk-quick-row { display: flex; flex-wrap: wrap; gap: 0.6rem; }
    .bk-quick-chip {
        display: inline-flex; align-items: center; gap: 0.55rem;
        background: #fff; border: 1px solid #E7E4DA; border-radius: 999px;
        padding: 0.55rem 1.1rem 0.55rem 0.55rem; color: #37473F; font-weight: 600; font-size: 0.85rem;
        text-decoration: none; transition: all 0.18s ease;
    }
    .bk-quick-chip:hover { border-color: #1F4B43; background: #F4F8F6; color: #1F4B43; transform: translateY(-2px); }
    .bk-quick-icon {
        width: 30px; height: 30px; border-radius: 50%; background: #E6EEEC; color: #1F4B43;
        display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0;
    }
    .bk-empty-state { padding: 3.5rem 1rem; text-align: center; }
    .bk-empty-state i { font-size: 2.4rem; color: #C7D1CC; }
    .bk-empty-state h6 { color: #8A968F; font-weight: 600; margin-top: 0.75rem; margin-bottom: 0; }
</style>

<div class="container-fluid py-4 px-4 bk-page">

    <div class="bk-header">
        <svg class="bk-motif" viewBox="0 0 130 130" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="65" cy="65" r="60" stroke="#F2C879" stroke-width="2" opacity="0.5"/>
            <circle cx="65" cy="65" r="44" stroke="#F2C879" stroke-width="2" opacity="0.35"/>
            <circle cx="65" cy="65" r="28" stroke="#F2C879" stroke-width="2" opacity="0.25"/>
        </svg>
        <div class="bk-header-content d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <p class="bk-eyebrow mb-1">Bimbingan &amp; Konseling</p>
                <h3 class="bk-serif">Dashboard Analitik</h3>
                <p>Ringkasan data dan aktivitas harian siswa Anda</p>
            </div>
            <div class="bk-date-chip">
                <p class="bk-date-label mb-1">Tanggal hari ini</p>
                <p class="bk-date-value bk-serif">{{ \Carbon\Carbon::now()->locale('id')->translatedFormat('l, d F Y') }}</p>
            </div>
        </div>
    </div>

    <div class="bk-quick-row mb-4">
        <a href="{{ route('gurubk.sesi.index') }}" class="bk-quick-chip">
            <span class="bk-quick-icon"><i class="fas fa-calendar-check"></i></span> Sesi Konseling
        </a>
        <a href="{{ route('gurubk.rekap.index') }}" class="bk-quick-chip">
            <span class="bk-quick-icon"><i class="fas fa-file-lines"></i></span> Jurnal BK
        </a>
        <a href="{{ route('gurubk.absensi-pantau.index') }}" class="bk-quick-chip">
            <span class="bk-quick-icon"><i class="fas fa-user-check"></i></span> Pantau Absensi
        </a>
        <a href="{{ route('gurubk.artikel-bk.index') }}" class="bk-quick-chip">
            <span class="bk-quick-icon"><i class="fas fa-newspaper"></i></span> Artikel BK
        </a>
        <a href="{{ route('gurubk.program-bk.index') }}" class="bk-quick-chip">
            <span class="bk-quick-icon"><i class="fas fa-clipboard-list"></i></span> Program BK
        </a>
        <a href="{{ route('gurubk.chatbot.index') }}" class="bk-quick-chip">
            <span class="bk-quick-icon"><i class="fas fa-robot"></i></span> Atur Chatbot
        </a>
    </div>

    <div class="row g-4 mb-4">

        <div class="col-xl-3 col-md-6">
            <div class="bk-stat bk-stat--siswa">
                <div class="bk-stat-top">
                    <div>
                        <p class="bk-stat-label mb-1">Total Siswa</p>
                        <h2 class="bk-stat-value bk-serif">{{ $totalSiswa }}</h2>
                    </div>
                    <div class="bk-stat-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>
                <div class="bk-stat-foot">
                    <a href="{{ route('gurubk.data-siswa.index') }}">Lihat detail data <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="bk-stat bk-stat--chat">
                <div class="bk-stat-top">
                    <div>
                        <p class="bk-stat-label mb-1">Total Percakapan</p>
                        <h2 class="bk-stat-value bk-serif">{{ $totalPercakapan }}</h2>
                    </div>
                    <div class="bk-stat-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                </div>
                <div class="bk-stat-foot">Akumulasi seluruh pesan</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="bk-stat bk-stat--aktif">
                <div class="bk-stat-top">
                    <div>
                        <p class="bk-stat-label mb-1">Siswa Aktif (Hari Ini)</p>
                        <h2 class="bk-stat-value bk-serif">{{ $percakapanAktif }}</h2>
                    </div>
                    <div class="bk-stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <div class="bk-stat-foot">Siswa yang menggunakan chatbot hari ini</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="bk-stat bk-stat--jurnal">
                <div class="bk-stat-top">
                    <div>
                        <p class="bk-stat-label mb-1">Jurnal Tersimpan</p>
                        <h2 class="bk-stat-value bk-serif">{{ $jurnalTersimpan }}</h2>
                    </div>
                    <div class="bk-stat-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                </div>
                <div class="bk-stat-foot">Arsip laporan konseling</div>
            </div>
        </div>
    </div>

    <div class="bk-log-card">
        <div class="bk-log-header">
            <div class="bk-log-icon"><i class="fas fa-stream"></i></div>
            <h6>Log Aktivitas Terkini</h6>
        </div>
        <div class="table-responsive">
            <table class="table bk-table align-middle mb-0">
                <thead>
                    <tr>
                        <th width="6%" class="text-center">No</th>
                        <th width="64%">Deskripsi</th>
                        <th width="30%" class="text-center">Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logTerbaru as $index => $log)
                    <tr>
                        <td class="text-center fw-semibold text-muted">{{ $index + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bk-avatar me-3">
                                    {{ strtoupper(substr($log->user->name ?? 'U', 0, 1)) }}
                                </div>
                                <div>
                                    <span class="d-block">Siswa <strong class="text-dark">{{ $log->user->name ?? 'User Tidak Diketahui' }}</strong></span>
                                    <span class="text-muted small">{{ $log->aktivitas }}</span>
                                    {{-- PERBAIKAN AUDIT (Bagian I, 22 Juli 2026): tampilkan kategori & topik percakapan --}}
                                    @if($log->kategori || $log->topik)
                                    <div class="mt-1">
                                        @if($log->kategori)
                                        <span class="badge bg-light text-dark border">{{ ucfirst($log->kategori) }}</span>
                                        @endif
                                        @if($log->topik)
                                        <span class="text-muted small fst-italic">"{{ Str::limit($log->topik, 40) }}"</span>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="bk-time-pill">
                                {{ $log->created_at->format('H:i') }} WIB
                            </span>
                            <div class="text-muted mt-1" style="font-size: 0.75rem;">{{ $log->created_at->locale('id')->translatedFormat('d M Y') }}</div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3">
                            <div class="bk-empty-state">
                                <i class="fas fa-inbox"></i>
                                <h6>Belum ada aktivitas terekam.</h6>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection