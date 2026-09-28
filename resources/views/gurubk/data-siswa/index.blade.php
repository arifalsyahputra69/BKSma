@extends('layouts.guru')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

    .ds-page {
        font-family: 'Plus Jakarta Sans', sans-serif;
        color: #263238;
    }

    .ds-page .ds-serif { font-family: 'Fraunces', serif; }

    /* ---------- Page header ---------- */
    .ds-page-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.75rem;
    }

    .ds-eyebrow {
        color: #7C8A86;
        font-size: 0.78rem;
        letter-spacing: 1.6px;
        text-transform: uppercase;
        font-weight: 700;
        margin-bottom: 0.35rem;
    }

    .ds-page-head h3 {
        font-weight: 600;
        color: #1B2B27;
        margin-bottom: 0.15rem;
    }

    .ds-page-head p {
        color: #8A968F;
        margin-bottom: 0;
        font-size: 0.92rem;
    }

    /* ---------- Card / table ---------- */
    .ds-card {
        background: #fff;
        border: 1px solid #E7E4DA;
        border-radius: 18px;
        overflow: hidden;
    }

    .ds-table thead th {
        background: #FAF9F5;
        color: #8A968F;
        font-size: 0.72rem;
        letter-spacing: 0.6px;
        text-transform: uppercase;
        font-weight: 700;
        border: none;
        padding: 0.95rem 1.25rem;
        white-space: nowrap;
    }

    .ds-table tbody td {
        padding: 0.85rem 1.25rem;
        border-top: 1px solid #F2F0E9;
        vertical-align: middle;
    }

    .ds-table tbody tr.ds-row {
        transition: opacity .16s ease, background-color .15s ease;
    }
    .ds-table tbody tr:hover { background: #FAFAF6; }
    .ds-table tbody tr.ds-row-out { opacity: 0; }

    .ds-avatar {
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
        transition: transform .15s ease;
    }
    .ds-table tbody tr:hover .ds-avatar { transform: scale(1.08); }

    .ds-student-name { display: flex; align-items: center; gap: 0.75rem; }

    .ds-muted-cell { color: #6B7A73; }

    /* status badges as soft pills, on-brand instead of default bootstrap colors */
    .ds-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        border-radius: 999px;
        padding: 0.35rem 0.85rem;
        font-size: 0.78rem;
        font-weight: 700;
    }

    .ds-badge i { font-size: 0.65rem; }

    .ds-badge--verified { background: #E6EEEC; color: #1F4B43; }
    .ds-badge--pending  { background: #F5EEE0; color: #8A6620; }
    .ds-badge--empty    { background: #F1F1EE; color: #8A968F; }

    .ds-btn-view {
        background: #1F4B43;
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 0.45rem 0.95rem;
        font-size: 0.83rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        text-decoration: none;
        transition: background 0.15s ease, transform .15s ease;
    }

    .ds-btn-view:hover { background: #16352F; color: #fff; transform: translateY(-1px); }
    .ds-btn-view:active { transform: translateY(0); }

    .ds-empty-state { padding: 3.5rem 1rem; text-align: center; }
    .ds-empty-state i { font-size: 2.4rem; color: #C7D1CC; }
    .ds-empty-state h6 { color: #8A968F; font-weight: 600; margin-top: 0.75rem; margin-bottom: 0; }

    /* ---------- Stat cards -- kini jadi tombol filter interaktif ---------- */
    .ds-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.9rem; margin-bottom: 1.5rem; }
    @media (max-width: 767.98px) { .ds-stats { grid-template-columns: repeat(2, 1fr); } }

    .ds-stat-card {
        background: #fff; border: 1.5px solid #E7E4DA; border-radius: 16px;
        padding: 1rem 1.15rem; display: flex; align-items: center; gap: 0.85rem;
        cursor: pointer; user-select: none; text-align: left; width: 100%;
        transition: border-color .16s ease, transform .16s ease, box-shadow .16s ease, background-color .16s ease;
        position: relative;
        opacity: 0; transform: translateY(10px);
        animation: dsFadeUp .45s ease both;
    }
    .ds-stat-card:nth-child(1) { animation-delay: .02s; }
    .ds-stat-card:nth-child(2) { animation-delay: .08s; }
    .ds-stat-card:nth-child(3) { animation-delay: .14s; }
    .ds-stat-card:nth-child(4) { animation-delay: .2s; }
    @keyframes dsFadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .ds-stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 22px rgba(31,75,67,.08); }
    .ds-stat-card.is-active { border-color: #1F4B43; background: #F3F8F6; box-shadow: 0 8px 20px rgba(31,75,67,.1); }
    .ds-stat-card.is-active .ds-stat-check {
        opacity: 1; transform: scale(1);
    }
    .ds-stat-check {
        position: absolute; top: 10px; right: 12px; color: #1F4B43; font-size: .85rem;
        opacity: 0; transform: scale(.5); transition: all .15s ease;
    }

    .ds-stat-icon {
        width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center;
        justify-content: center; font-size: 1.05rem; flex-shrink: 0; transition: transform .16s ease;
    }
    .ds-stat-card:hover .ds-stat-icon { transform: scale(1.08) rotate(-4deg); }
    .ds-stat-icon--total     { background: #E9EFF5; color: #5B7FA6; }
    .ds-stat-icon--verified  { background: #E6EEEC; color: #1F4B43; }
    .ds-stat-icon--pending   { background: #F5EEE0; color: #8A6620; }
    .ds-stat-icon--empty     { background: #F1F1EE; color: #8A968F; }
    .ds-stat-value { font-weight: 800; font-size: 1.3rem; color: #1B2B27; line-height: 1.1; }
    .ds-stat-label { font-size: 0.75rem; color: #8A968F; font-weight: 600; }

    .ds-search { max-width: 280px; }
    .ds-search .input-group-text { background: #FAF9F5; border-color: #E7E4DA; }
    .ds-search .form-control { border-color: #E7E4DA; }
    .ds-search .form-control:focus { box-shadow: none; border-color: #1F4B43; }

    .ds-filter-bar {
        display: flex; align-items: center; gap: .6rem; margin-bottom: .9rem; font-size: .82rem; color: #8A968F;
        flex-wrap: wrap;
    }
    .ds-filter-chip {
        display: inline-flex; align-items: center; gap: .35rem; background: #E6EEEC; color: #1F4B43;
        border-radius: 999px; padding: .3rem .75rem; font-weight: 700; font-size: .78rem;
    }
    .ds-filter-chip button {
        border: none; background: transparent; color: inherit; padding: 0; line-height: 1;
        display: inline-flex; opacity: .7;
    }
    .ds-filter-chip button:hover { opacity: 1; }

    .ds-card { animation: dsFadeUp .5s ease .22s both; }
</style>

<div class="container-fluid py-4 px-4 ds-page">

    @php
        $dsTotal = count($siswa);
        $dsVerified = collect($siswa)->where('is_wa_verified', 1)->count();
        $dsPending = collect($siswa)->where('is_wa_verified', 0)->count();
        $dsEmpty = $dsTotal - $dsVerified - $dsPending;
    @endphp

    <div class="ds-page-head">
        <div>
            <p class="ds-eyebrow mb-1">Bimbingan &amp; Konseling</p>
            <h3 class="ds-serif">Daftar Data Siswa</h3>
            <p>Kontak orang tua dan status verifikasi WhatsApp per siswa</p>
        </div>
        <div class="ds-search">
            <div class="input-group input-group-sm">
                <span class="input-group-text border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="dsSearchInput" class="form-control border-start-0" placeholder="Cari nama siswa/orang tua...">
            </div>
        </div>
    </div>

    {{-- Stat card sekaligus tombol filter -- klik untuk menyaring tabel di bawah,
         klik lagi untuk kembali ke "Semua". Ini yang bikin halaman jauh lebih
         interaktif dibanding sekadar tabel statis. --}}
    <div class="ds-stats" id="dsStats">
        <button type="button" class="ds-stat-card is-active" data-filter="all">
            <i class="bi bi-check2-circle ds-stat-check"></i>
            <div class="ds-stat-icon ds-stat-icon--total"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="ds-stat-value" data-count="{{ $dsTotal }}">0</div>
                <div class="ds-stat-label">Total Siswa</div>
            </div>
        </button>
        <button type="button" class="ds-stat-card" data-filter="verified">
            <i class="bi bi-check2-circle ds-stat-check"></i>
            <div class="ds-stat-icon ds-stat-icon--verified"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <div class="ds-stat-value" data-count="{{ $dsVerified }}">0</div>
                <div class="ds-stat-label">Terverifikasi</div>
            </div>
        </button>
        <button type="button" class="ds-stat-card" data-filter="pending">
            <i class="bi bi-check2-circle ds-stat-check"></i>
            <div class="ds-stat-icon ds-stat-icon--pending"><i class="bi bi-hourglass-split"></i></div>
            <div>
                <div class="ds-stat-value" data-count="{{ $dsPending }}">0</div>
                <div class="ds-stat-label">Menunggu Verifikasi</div>
            </div>
        </button>
        <button type="button" class="ds-stat-card" data-filter="empty">
            <i class="bi bi-check2-circle ds-stat-check"></i>
            <div class="ds-stat-icon ds-stat-icon--empty"><i class="bi bi-dash-circle"></i></div>
            <div>
                <div class="ds-stat-value" data-count="{{ $dsEmpty }}">0</div>
                <div class="ds-stat-label">Belum Mengisi</div>
            </div>
        </button>
    </div>

    <div id="dsFilterBar" class="ds-filter-bar" style="display:none;">
        Menampilkan:
        <span class="ds-filter-chip">
            <span id="dsFilterChipLabel">Semua</span>
            <button type="button" id="dsFilterClear" title="Hapus filter"><i class="bi bi-x-circle-fill"></i></button>
        </span>
    </div>

    <div class="ds-card">
        <div class="table-responsive">
            <table class="table ds-table align-middle mb-0" id="dsTable">
                <thead>
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th width="25%">Nama Siswa</th>
                        <th width="20%">Nama Orang Tua</th>
                        <th width="20%">No. WA Ortu</th>
                        <th width="15%" class="text-center">Status</th>
                        <th width="15%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($siswa as $index => $s)
                    @php
                        $dsStatus = $s->is_wa_verified === 1 ? 'verified' : ($s->is_wa_verified === 0 ? 'pending' : 'empty');
                    @endphp
                    <tr class="ds-row" data-status="{{ $dsStatus }}">
                        <td class="text-center fw-semibold text-muted">{{ $index + 1 }}</td>
                        <td>
                            <div class="ds-student-name ds-cell-nama">
                                <div class="ds-avatar">{{ strtoupper(substr($s->user->name ?? '-', 0, 1)) }}</div>
                                <span class="fw-semibold text-dark">{{ $s->user->name ?? '-' }}</span>
                            </div>
                        </td>
                        <td class="ds-muted-cell ds-cell-ortu">{{ $s->nama_ortu ?? '-' }}</td>
                        <td class="ds-muted-cell">{{ $s->no_wa_ortu ?? '-' }}</td>
                        <td class="text-center">
                            @if($dsStatus === 'verified')
                                <span class="ds-badge ds-badge--verified"><i class="bi bi-check-circle-fill"></i> Terverifikasi</span>
                            @elseif($dsStatus === 'pending')
                                <span class="ds-badge ds-badge--pending"><i class="bi bi-hourglass-split"></i> Menunggu</span>
                            @else
                                <span class="ds-badge ds-badge--empty"><i class="bi bi-dash-circle"></i> Belum Mengisi</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('gurubk.data-siswa.show', $s->id) }}" class="ds-btn-view">
                                <i class="bi bi-eye"></i> Lihat Data
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="ds-empty-state">
                                <i class="bi bi-inbox"></i>
                                <h6>Belum ada data siswa.</h6>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                    <tr id="dsNoMatchRow" style="display:none;">
                        <td colspan="6">
                            <div class="ds-empty-state">
                                <i class="bi bi-search"></i>
                                <h6>Tidak ada siswa yang cocok dengan pencarian/filter ini.</h6>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var searchInput = document.getElementById('dsSearchInput');
        var table = document.getElementById('dsTable');
        var statCards = document.querySelectorAll('#dsStats .ds-stat-card');
        var filterBar = document.getElementById('dsFilterBar');
        var filterChipLabel = document.getElementById('dsFilterChipLabel');
        var filterClearBtn = document.getElementById('dsFilterClear');
        var noMatchRow = document.getElementById('dsNoMatchRow');
        var currentFilter = 'all';

        var filterLabels = {
            all: 'Semua',
            verified: 'Terverifikasi',
            pending: 'Menunggu Verifikasi',
            empty: 'Belum Mengisi',
        };

        // ===== 1. Animasi hitung naik untuk angka statistik =====
        document.querySelectorAll('.ds-stat-value[data-count]').forEach(function (el) {
            var target = parseInt(el.getAttribute('data-count'), 10) || 0;
            var start = 0;
            var duration = 600;
            var startTime = null;

            function tick(ts) {
                if (!startTime) startTime = ts;
                var progress = Math.min((ts - startTime) / duration, 1);
                var value = Math.round(start + (target - start) * (1 - Math.pow(1 - progress, 3)));
                el.textContent = value;
                if (progress < 1) requestAnimationFrame(tick);
            }
            requestAnimationFrame(tick);
        });

        // ===== 2. Filter tabel (gabungan: status dari stat card + kata kunci pencarian) =====
        function applyFilters() {
            if (!table) return;
            var keyword = (searchInput ? searchInput.value : '').toLowerCase();
            var visibleCount = 0;

            table.querySelectorAll('tbody tr.ds-row').forEach(function (row) {
                var status = row.getAttribute('data-status');
                var namaEl = row.querySelector('.ds-cell-nama');
                var ortuEl = row.querySelector('.ds-cell-ortu');
                var nama = namaEl ? namaEl.textContent.toLowerCase() : '';
                var ortu = ortuEl ? ortuEl.textContent.toLowerCase() : '';

                var matchStatus = (currentFilter === 'all' || status === currentFilter);
                var matchKeyword = (keyword === '' || nama.indexOf(keyword) !== -1 || ortu.indexOf(keyword) !== -1);
                var shouldShow = matchStatus && matchKeyword;

                if (shouldShow) {
                    row.style.display = '';
                    row.classList.remove('ds-row-out');
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (noMatchRow) {
                var hasAnyRealRow = table.querySelectorAll('tbody tr.ds-row').length > 0;
                noMatchRow.style.display = (hasAnyRealRow && visibleCount === 0) ? '' : 'none';
            }
        }

        // ===== 3. Klik stat card => jadi filter aktif (toggle kalau diklik ulang) =====
        statCards.forEach(function (card) {
            card.addEventListener('click', function () {
                var filter = card.getAttribute('data-filter');

                if (currentFilter === filter && filter !== 'all') {
                    currentFilter = 'all';
                } else {
                    currentFilter = filter;
                }

                statCards.forEach(function (c) { c.classList.remove('is-active'); });
                var activeCard = document.querySelector('#dsStats .ds-stat-card[data-filter="' + currentFilter + '"]');
                if (activeCard) activeCard.classList.add('is-active');

                if (filterBar) filterBar.style.display = currentFilter === 'all' ? 'none' : 'flex';
                if (filterChipLabel) filterChipLabel.textContent = filterLabels[currentFilter] || 'Semua';

                applyFilters();
            });
        });

        if (filterClearBtn) {
            filterClearBtn.addEventListener('click', function () {
                currentFilter = 'all';
                statCards.forEach(function (c) { c.classList.remove('is-active'); });
                var allCard = document.querySelector('#dsStats .ds-stat-card[data-filter="all"]');
                if (allCard) allCard.classList.add('is-active');
                if (filterBar) filterBar.style.display = 'none';
                applyFilters();
            });
        }

        if (searchInput) {
            searchInput.addEventListener('keyup', applyFilters);
        }
    });
</script>
@endpush
@endsection
