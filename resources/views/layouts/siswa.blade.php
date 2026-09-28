<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Siswa') - SIM BK SMA KARTIKA I-5 PADANG</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        /* ===== Palet Hijau SIM BK (konsisten di semua role) ===== */
        :root {
            --primary-color: #1f4b3f;
            --primary-dark: #14362d;
            --primary-light: #e8f3ee;
            --bg-color: #f3f4f6;
            --text-main: #1f2937;
            --text-muted: #6b7280;
        }
        body { background-color: var(--bg-color); font-family: 'Inter', sans-serif; color: var(--text-main); overflow-x: hidden; }
        #wrapper { display: flex; min-height: 100vh; }

        #sidebar { width: 260px; flex-shrink: 0; background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%); z-index: 1040; transition: transform 0.3s ease; }
        .sidebar-brand { padding: 1.75rem 1.5rem; text-align: center; color: white; }
        .sidebar-brand .icon-box { display: inline-flex; align-items: center; justify-content: center; width: 45px; height: 45px; background: #ffffff; border-radius: 12px; margin-bottom: 10px; padding: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.15); }
        .sidebar-brand h5 { font-weight: 700; letter-spacing: 0.5px; margin: 0; font-size: 1.1rem; }
        .sidebar-brand p { font-size: 0.75rem; color: rgba(255,255,255,0.7); margin: 2px 0 0; }
        #sidebar .nav-item { margin: 0 0.75rem 0.25rem; }
        #sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 0.75rem 1rem; display: flex; align-items: center; gap: 12px; border-radius: 10px; font-weight: 500; font-size: 0.92rem; transition: all 0.2s; }
        #sidebar .nav-link i { width: 20px; text-align: center; font-size: 1.05rem; }
        #sidebar .nav-link:hover { color: white; background: rgba(255,255,255,0.12); }
        #sidebar .nav-link.active { color: var(--primary-dark); background: #ffffff; box-shadow: 0 4px 6px rgba(0,0,0,0.1); font-weight: 600; }

        #content-wrapper { flex: 1; min-width: 0; display: flex; flex-direction: column; overflow: hidden; }
        .topbar { background: rgba(255,255,255,0.97); min-height: 4.5rem; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; padding: 0 1.5rem; z-index: 1050; gap: 0.75rem; backdrop-filter: blur(6px); position: sticky; top: 0; }

        #sidebarToggle { display: none; background: none; border: none; font-size: 1.35rem; color: var(--text-main); padding: 0.4rem 0.6rem; border-radius: 8px; }
        #sidebarToggle:hover { background: #f3f4f6; }
        #sidebarOverlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 1030; }

        /* ===== PERBAIKAN (26 Juli 2026): banner header halaman, dipakai
           lewat class .page-header-banner supaya konsisten dengan Guru BK. ===== */
        .page-header-banner {
            background: linear-gradient(120deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border-radius: 16px;
            padding: 1.5rem 1.75rem;
            box-shadow: 0 8px 20px rgba(31,75,63,0.18);
        }
        .page-header-icon {
            width: 52px; height: 52px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.16); border-radius: 14px;
            color: #fff; font-size: 1.4rem; backdrop-filter: blur(4px);
        }

        @media (max-width: 991.98px) {
            #sidebarToggle { display: inline-flex; align-items: center; justify-content: center; }
            /* PERBAIKAN TAMPILAN HP (2 Agustus 2026): di layar kecil, .topbar
               memakai position:sticky dengan z-index 1050 -- lebih tinggi
               daripada #sidebar (1040). Akibatnya bar putih di atas menimpa
               bagian atas menu geser, tepat di posisi logo sekolah, sehingga
               logonya terlihat terpotong. Di layar lebar hal ini tidak
               kelihatan karena sidebar-nya menempel di samping, bukan
               menumpuk. Urutan lapisan ditata ulang khusus untuk mobile:
               sidebar (1060) di atas overlay (1055), dan overlay di atas
               topbar (1050) supaya bar ikut meredup saat menu dibuka. */
            #sidebar {
                position: fixed; top: 0; left: 0; height: 100vh;
                transform: translateX(-100%); overflow-y: auto;
                box-shadow: 0 0 30px rgba(0,0,0,0.25);
                z-index: 1060;
            }
            #sidebar.show { transform: translateX(0); }
            #sidebarOverlay { z-index: 1055; }
            #sidebarOverlay.show { display: block; }
            /* Beri jarak aman di atas logo untuk ponsel berponi (notch) dan
               bilah status yang menutupi sebagian layar. */
            .sidebar-brand { padding-top: calc(1.75rem + env(safe-area-inset-top, 0px)); }
            .topbar { padding: 0 1rem; }
            .topbar-title { font-size: 1.05rem !important; max-width: 50vw; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .container-fluid.p-4 { padding: 1rem !important; }
        }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <div id="wrapper">
        <div id="sidebarOverlay" onclick="toggleSimBkSidebar(false)"></div>
        <nav id="sidebar" class="d-flex flex-column">
            <div class="sidebar-brand">
                <div class="icon-box"><img src="{{ asset('images/logo-kartika.png') }}" alt="Logo SMA Kartika I-5 Padang" style="width:100%;height:100%;object-fit:contain;"></div>
                <h5>SIM BK</h5>
                <p>Dashboard Siswa</p>
            </div>
            <ul class="nav flex-column w-100">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('siswa.dashboard') ? 'active' : '' }}" href="{{ route('siswa.dashboard') }}">
                        <i class="fas fa-home"></i> Beranda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('siswa.chatbot.*') ? 'active' : '' }}" href="{{ route('siswa.chatbot.index') }}">
                        <i class="fas fa-robot"></i> Chatbot BK
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('siswa.akpd.*') ? 'active' : '' }}" href="{{ route('siswa.akpd.index') }}">
                        <i class="fas fa-poll-h"></i> AKPD
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('siswa.sesi.*') ? 'active' : '' }}" href="{{ route('siswa.sesi.index') }}">
                        <i class="fas fa-calendar-alt"></i> Janji Temu
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('siswa.informasi-kampus.*') ? 'active' : '' }}" href="{{ route('siswa.informasi-kampus.index') }}">
                        <i class="fas fa-university"></i> Informasi Kampus
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('siswa.absensi.*') ? 'active' : '' }}" href="{{ route('siswa.absensi.riwayat') }}">
                        <i class="fas fa-qrcode"></i> Absensi QR
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('siswa.artikel.*') ? 'active' : '' }}" href="{{ route('siswa.artikel.index') }}">
                        <i class="fas fa-newspaper"></i> Artikel BK
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('profil.umum.index') ? 'active' : '' }}" href="{{ route('profil.umum.index') }}">
                        <i class="fas fa-user-circle"></i> Profil Saya
                    </a>
                </li>
            </ul>
        </nav>

        <div id="content-wrapper">
            <header class="topbar">
                <div class="d-flex align-items-center gap-2" style="min-width: 0;">
                    <button type="button" id="sidebarToggle" onclick="toggleSimBkSidebar()" aria-label="Buka menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h5 class="m-0 fw-bold text-dark topbar-title">@yield('title')</h5>
                    @include('partials.badge-semester')
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="dropdown">
                        <a href="#" class="text-dark position-relative p-2" data-bs-toggle="dropdown" style="padding: 0;">
                            <i class="fas fa-bell text-muted fa-lg"></i>
                            @if(isset($notifikasi_user) && count($notifikasi_user) > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                                    {{ count($notifikasi_user) }}
                                </span>
                            @endif
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="width: 300px;">
                            <li class="d-flex justify-content-between align-items-center px-3">
                                <h6 class="dropdown-header p-0 m-0">Notifikasi Terbaru</h6>
                                @if(isset($notifikasi_user) && count($notifikasi_user) > 0)
                                    <form action="{{ route('siswa.notifikasi.baca-semua') }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-link btn-sm p-0 small">Tandai dibaca</button>
                                    </form>
                                @endif
                            </li>
                            @forelse($notifikasi_user ?? [] as $notif)
                                <li>
                                    <a class="dropdown-item" style="white-space: normal;" href="{{ route('siswa.notifikasi.baca', $notif->id) }}">
                                        <div class="fw-bold small">{{ $notif->judul }}</div>
                                        <small class="text-muted d-block small">{{ $notif->pesan }}</small>
                                    </a>
                                </li>
                            @empty
                                <li><span class="dropdown-item text-muted text-center small">Tidak ada notifikasi</span></li>
                            @endforelse
                        </ul>
                    </div>
                    <div style="height: 24px; width: 1px; background-color: #e5e7eb;" class="d-none d-sm-block"></div>
                    <div class="dropdown">
                        <div class="d-flex align-items-center gap-2 cursor-pointer" data-bs-toggle="dropdown">
                            <span class="fw-semibold d-none d-md-block" style="font-size: 0.85rem;">{{ Auth::user()->name }}</span>
                            @if(Auth::user()->foto)
                                <img class="rounded-circle shadow-sm" src="{{ asset('storage/profil/' . Auth::user()->foto) }}" width="40" height="40" style="object-fit: cover;">
                            @else
                                <img class="rounded-circle shadow-sm" src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=1f4b3f&color=fff&bold=true" width="40" height="40">
                            @endif
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 rounded-3">
                            <li><a class="dropdown-item py-2" href="{{ route('profil.umum.index') }}"><i class="fas fa-user-circle me-2"></i> Profil Saya</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Apakah Anda yakin ingin keluar?');">
                                        <i class="fas fa-sign-out-alt me-2"></i> Keluar
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>
            <div class="container-fluid p-4" style="overflow-y: auto;">@yield('content')</div>
        </div>
    </div>

    {{-- MODAL KONFIRMASI KELAS DIHAPUS DARI SINI (5 Agustus 2026).

         Dulu ada salinan formulir konfirmasi di layout ini yang tampil kalau
         session 'show_konfirmasi_modal' terisi. Salinan itu berbahaya: ia
         mengirim 'jawaban' lewat atribut value pada tombol submit dan TIDAK
         punya kolom kelas tujuan maupun catatan. Kalau ikut tampil bersama
         popup baru di dashboard, siswa bisa tanpa sadar mengirim laporan
         versi lama yang datanya tidak lengkap -- dan TU kembali menerima
         notifikasi tanpa keterangan kelas, persis masalah yang sedang
         diperbaiki.

         Formulir yang berlaku sekarang cuma satu, di
         resources/views/siswa/dashboard/index.blade.php --}}

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSimBkSidebar(forceOpen) {
            var sidebar = document.getElementById('sidebar');
            var overlay = document.getElementById('sidebarOverlay');
            var open = typeof forceOpen === 'boolean' ? forceOpen : !sidebar.classList.contains('show');
            sidebar.classList.toggle('show', open);
            overlay.classList.toggle('show', open);
            document.body.style.overflow = open ? 'hidden' : '';
        }
        document.querySelectorAll('#sidebar .nav-link').forEach(function (link) {
            link.addEventListener('click', function () { toggleSimBkSidebar(false); });
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 992) toggleSimBkSidebar(false);
        });
    </script>
    @include('partials.ui-enhance')
    @stack('scripts')
    @auth
        @include('partials.fcm-push')
    @endauth
</body>
</html>