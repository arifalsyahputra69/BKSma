<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM BK - SMA Kartika I-5 Padang</title>
    <meta name="description" content="Sistem Informasi Manajemen Bimbingan Konseling SMA Kartika I-5 Padang. Chatbot BK, sesi konseling, absensi QR, dan monitoring siswa dalam satu platform.">
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Fraunces:ital,wght@0,600;1,600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary-color: #1f4b3f;
            --primary-dark: #14362d;
            --primary-deep: #0d241e;
            --primary-light: #e8f3ee;
            --gold: #c9974a;
        }

        * { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--primary-light);
            overflow-x: hidden;
        }

        .font-display { font-family: 'Fraunces', serif; }

        /* ============================================================
           HERO
           Latar memakai gradient yang bergeser pelan (background-position
           dianimasikan, bukan properti yang memicu layout) supaya ringan
           dan tidak bikin HP kelas bawah tersendat.
           ============================================================ */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            background: linear-gradient(140deg,
                var(--primary-dark) 0%,
                var(--primary-color) 35%,
                var(--primary-deep) 70%,
                var(--primary-dark) 100%);
            background-size: 220% 220%;
            animation: heroShift 22s ease-in-out infinite;
        }
        @keyframes heroShift {
            0%, 100% { background-position: 0% 50%; }
            50%      { background-position: 100% 50%; }
        }

        /* Lapisan pola titik halus di atas gradient — memberi tekstur
           supaya latar tidak terasa "kosong" begitu saja. */
        .hero-grid {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255,255,255,0.10) 1px, transparent 1px);
            background-size: 34px 34px;
            mask-image: radial-gradient(ellipse 80% 65% at 50% 40%, #000 40%, transparent 100%);
            -webkit-mask-image: radial-gradient(ellipse 80% 65% at 50% 40%, #000 40%, transparent 100%);
            pointer-events: none;
        }

        /* Bentuk lembut yang mengambang di latar. */
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: .38;
            pointer-events: none;
            animation: blobDrift 18s ease-in-out infinite;
        }
        .blob-1 { width: 460px; height: 460px; background: #2f7a63; top: -140px; right: -120px; }
        .blob-2 { width: 340px; height: 340px; background: var(--gold); bottom: -120px; left: -90px; opacity: .22; animation-delay: -7s; }
        .blob-3 { width: 260px; height: 260px; background: #3f9d7f; top: 45%; left: 42%; opacity: .18; animation-delay: -12s; }
        @keyframes blobDrift {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33%      { transform: translate(28px, -22px) scale(1.07); }
            66%      { transform: translate(-20px, 18px) scale(.95); }
        }

        /* Cincin dekoratif yang ikut kursor (parallax tipis, di-set via JS). */
        .hero-rings {
            position: absolute;
            right: -6%;
            top: 50%;
            width: 520px;
            height: 520px;
            transform: translateY(-50%);
            opacity: .5;
            pointer-events: none;
            transition: transform .35s cubic-bezier(.2,.7,.3,1);
        }
        .hero-rings circle { transform-origin: center; }
        .ring-spin { animation: ringSpin 40s linear infinite; transform-origin: center; }
        @keyframes ringSpin { to { transform: rotate(360deg); } }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: .75rem;
            padding: .5rem .95rem .5rem .5rem;
            background: rgba(255,255,255,0.10);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 999px;
            backdrop-filter: blur(8px);
            margin-bottom: 1.75rem;
        }
        .brand-badge img { width: 34px; height: 34px; object-fit: contain; background: #fff; border-radius: 50%; padding: 3px; }
        .brand-badge span { color: rgba(255,255,255,.9); font-size: .8rem; font-weight: 600; letter-spacing: .04em; }

        .headline {
            font-weight: 800;
            color: #fff;
            font-size: clamp(2rem, 4.6vw, 3.4rem);
            line-height: 1.12;
            letter-spacing: -0.02em;
        }
        .headline .accent {
            font-family: 'Fraunces', serif;
            font-style: italic;
            font-weight: 600;
            color: var(--gold);
            position: relative;
            white-space: nowrap;
        }
        /* Garis bawah yang "menggambar dirinya" saat halaman dibuka. */
        .headline .accent::after {
            content: '';
            position: absolute;
            left: 0; bottom: -.12em;
            height: 3px;
            width: 100%;
            background: linear-gradient(90deg, var(--gold), transparent);
            border-radius: 3px;
            transform: scaleX(0);
            transform-origin: left;
            animation: drawLine .9s cubic-bezier(.2,.7,.3,1) 1.1s forwards;
        }
        @keyframes drawLine { to { transform: scaleX(1); } }

        .subhead {
            color: rgba(255,255,255,0.82);
            font-size: 1.02rem;
            line-height: 1.75;
            max-width: 560px;
        }

        .btn-hero {
            background: #fff;
            color: var(--primary-dark);
            font-weight: 700;
            padding: .9rem 2.1rem;
            border-radius: 14px;
            border: none;
            position: relative;
            overflow: hidden;
            transition: transform .22s cubic-bezier(.2,.7,.3,1), box-shadow .22s ease;
        }
        .btn-hero:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 32px rgba(0,0,0,0.28);
            color: var(--primary-dark);
        }
        .btn-hero:active { transform: translateY(-1px); }
        /* Kilau yang menyapu tombol saat kursor lewat. */
        .btn-hero::before {
            content: '';
            position: absolute;
            top: 0; left: -120%;
            width: 60%; height: 100%;
            background: linear-gradient(100deg, transparent, rgba(31,75,63,.14), transparent);
            transition: left .6s ease;
        }
        .btn-hero:hover::before { left: 140%; }
        .btn-hero i { transition: transform .22s ease; }
        .btn-hero:hover i { transform: translateX(3px); }

        .btn-ghost {
            color: rgba(255,255,255,.85);
            font-weight: 600;
            padding: .9rem 1.5rem;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,.25);
            background: transparent;
            transition: all .22s ease;
        }
        .btn-ghost:hover { background: rgba(255,255,255,.10); color: #fff; border-color: rgba(255,255,255,.45); }

        /* ============================================================
           KARTU FITUR
           ============================================================ */
        .feature-card {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.14);
            border-radius: 18px;
            padding: 1.4rem;
            backdrop-filter: blur(10px);
            height: 100%;
            position: relative;
            overflow: hidden;
            transition: transform .3s cubic-bezier(.2,.7,.3,1), background .3s ease, border-color .3s ease;
        }
        .feature-card:hover {
            transform: translateY(-6px);
            background: rgba(255,255,255,0.13);
            border-color: rgba(201,151,74,.45);
        }
        /* Cahaya lembut yang mengikuti kursor di dalam kartu.
           Posisinya diisi lewat variabel CSS --mx/--my dari JS. */
        .feature-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(180px circle at var(--mx, 50%) var(--my, 50%), rgba(201,151,74,.20), transparent 65%);
            opacity: 0;
            transition: opacity .3s ease;
            pointer-events: none;
        }
        .feature-card:hover::before { opacity: 1; }

        .feature-icon {
            width: 44px; height: 44px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 12px;
            background: rgba(201,151,74,.16);
            color: var(--gold);
            font-size: 1.15rem;
            margin-bottom: .9rem;
            transition: transform .3s cubic-bezier(.2,.7,.3,1);
        }
        .feature-card:hover .feature-icon { transform: rotate(-8deg) scale(1.12); }
        .feature-card h6 { color: #fff; font-weight: 700; margin-bottom: .35rem; font-size: .95rem; }
        .feature-card p { color: rgba(255,255,255,0.68); font-size: .82rem; margin: 0; line-height: 1.6; }

        /* ============================================================
           SCROLL REVEAL
           Elemen mulai sedikit turun & transparan, lalu naik saat masuk
           layar. Dikendalikan IntersectionObserver di bagian bawah.
           ============================================================ */
        .reveal {
            opacity: 0;
            transform: translateY(22px);
            transition: opacity .7s cubic-bezier(.2,.7,.3,1), transform .7s cubic-bezier(.2,.7,.3,1);
        }
        .reveal.is-visible { opacity: 1; transform: translateY(0); }

        /* Petunjuk gulir di bawah hero. */
        .scroll-hint {
            position: absolute;
            bottom: 26px; left: 50%;
            transform: translateX(-50%);
            color: rgba(255,255,255,.55);
            font-size: .72rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .5rem;
            transition: color .25s ease;
        }
        .scroll-hint:hover { color: rgba(255,255,255,.9); }
        .scroll-hint i { animation: bobDown 2s ease-in-out infinite; }
        @keyframes bobDown {
            0%, 100% { transform: translateY(0); opacity: .6; }
            50%      { transform: translateY(6px); opacity: 1; }
        }

        /* ============================================================
           FOOTER
           ============================================================ */
        .site-footer {
            background: var(--primary-deep);
            color: rgba(255,255,255,.72);
            padding: 3.5rem 0 0;
            position: relative;
        }
        .site-footer::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--gold), var(--primary-color));
        }
        .site-footer h6 {
            color: #fff;
            font-weight: 700;
            font-size: .82rem;
            letter-spacing: .1em;
            text-transform: uppercase;
            margin-bottom: 1.1rem;
        }
        .footer-brand { display: flex; align-items: center; gap: .8rem; margin-bottom: 1rem; }
        .footer-brand img { width: 44px; height: 44px; object-fit: contain; background: #fff; border-radius: 10px; padding: 5px; }
        .footer-brand strong { color: #fff; display: block; font-size: 1rem; line-height: 1.3; }
        .footer-brand small { color: rgba(255,255,255,.55); font-size: .78rem; }

        .footer-list { list-style: none; padding: 0; margin: 0; font-size: .86rem; line-height: 1.9; }
        .footer-list li { display: flex; gap: .7rem; align-items: flex-start; }
        .footer-list i { color: var(--gold); width: 16px; margin-top: .38rem; flex-shrink: 0; font-size: .8rem; }
        .footer-list a { color: rgba(255,255,255,.72); text-decoration: none; transition: color .2s ease; }
        .footer-list a:hover { color: var(--gold); }

        .footer-hours { font-size: .86rem; line-height: 1.9; }
        .footer-hours div { display: flex; justify-content: space-between; gap: 1rem; border-bottom: 1px dashed rgba(255,255,255,.10); padding: .18rem 0; }
        .footer-hours span:last-child { color: #fff; font-weight: 600; white-space: nowrap; }

        .footer-bottom {
            margin-top: 2.75rem;
            border-top: 1px solid rgba(255,255,255,.10);
            padding: 1.25rem 0;
            font-size: .8rem;
            color: rgba(255,255,255,.5);
        }

        /* ============================================================
           AKSESIBILITAS
           Kalau pengguna menyalakan "kurangi gerak" di sistemnya, semua
           animasi dimatikan dan elemen scroll-reveal langsung terlihat --
           supaya halaman tetap bisa dipakai, bukan malah kosong.
           ============================================================ */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation: none !important;
                transition: none !important;
            }
            .reveal { opacity: 1; transform: none; }
            .headline .accent::after { transform: scaleX(1); }
        }

        @media (max-width: 991.98px) {
            .hero-rings { display: none; }
            .hero { min-height: auto; padding: 4rem 0 5.5rem; }
            .scroll-hint { display: none; }
        }
    </style>
</head>
<body>

    {{-- ============================ HERO ============================ --}}
    <section class="hero">
        <div class="hero-grid"></div>
        <span class="blob blob-1"></span>
        <span class="blob blob-2"></span>
        <span class="blob blob-3"></span>

        <svg class="hero-rings" id="heroRings" viewBox="0 0 400 400" fill="none" aria-hidden="true">
            <circle cx="200" cy="200" r="80"  stroke="rgba(255,255,255,0.13)" stroke-width="1.5"/>
            <circle cx="200" cy="200" r="130" stroke="rgba(255,255,255,0.10)" stroke-width="1.5"/>
            <circle cx="200" cy="200" r="180" stroke="rgba(255,255,255,0.07)" stroke-width="1.5"/>
            <circle class="ring-spin" cx="200" cy="200" r="196"
                    stroke="rgba(201,151,74,0.40)" stroke-width="2"
                    stroke-dasharray="6 14" stroke-linecap="round"/>
        </svg>

        <div class="container py-5 position-relative">
            <div class="row align-items-center g-5">

                <div class="col-lg-7">
                    <div class="brand-badge reveal">
                        <img src="{{ asset('images/logo-kartika.png') }}" alt="Logo SMA Kartika I-5 Padang">
                        <span>SMA KARTIKA I-5 PADANG</span>
                    </div>

                    <h1 class="headline mb-4 reveal" style="transition-delay:.08s">
                        Sistem Informasi<br>
                        Bimbingan &amp; <span class="accent">Konseling</span>
                    </h1>

                    <p class="subhead mb-4 reveal" style="transition-delay:.16s">
                        Ruang digital tempat Guru BK, wali kelas, dan siswa saling terhubung &mdash;
                        memantau layanan konseling, kegiatan, serta perkembangan siswa
                        dalam satu platform yang rapi dan mudah dipakai.
                    </p>

                    <div class="d-flex flex-wrap gap-3 reveal" style="transition-delay:.24s">
                        <a href="{{ route('login') }}" class="btn btn-hero">
                            Masuk ke SIM BK <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                        <a href="#fitur" class="btn btn-ghost">
                            <i class="fas fa-compass me-2"></i> Lihat Fitur
                        </a>
                    </div>
                </div>

                <div class="col-lg-5" id="fitur">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="feature-card reveal" style="transition-delay:.30s">
                                <div class="feature-icon"><i class="fas fa-comments"></i></div>
                                <h6>Chatbot BK</h6>
                                <p>Konsultasi awal &amp; informasi karir kapan saja.</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="feature-card reveal" style="transition-delay:.38s">
                                <div class="feature-icon"><i class="fas fa-calendar-check"></i></div>
                                <h6>Sesi Konseling</h6>
                                <p>Ajukan janji temu dengan Guru BK.</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="feature-card reveal" style="transition-delay:.46s">
                                <div class="feature-icon"><i class="fas fa-qrcode"></i></div>
                                <h6>Absensi QR</h6>
                                <p>Presensi cepat &amp; rekap otomatis.</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="feature-card reveal" style="transition-delay:.54s">
                                <div class="feature-icon"><i class="fas fa-chart-pie"></i></div>
                                <h6>Laporan &amp; Monitoring</h6>
                                <p>Pantau perkembangan siswa secara real-time.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <a href="#footer" class="scroll-hint">
            Informasi Sekolah
            <i class="fas fa-chevron-down"></i>
        </a>
    </section>

    {{-- =========================== FOOTER =========================== --}}
    <footer class="site-footer" id="footer">
        <div class="container">
            <div class="row g-4 g-lg-5">

                <div class="col-lg-4">
                    <div class="footer-brand reveal">
                        <img src="{{ asset('images/logo-kartika.png') }}" alt="Logo SMA Kartika I-5 Padang">
                        <div>
                            <strong>SIM BK</strong>
                            <small>SMA Kartika I-5 Padang</small>
                        </div>
                    </div>
                    <p class="reveal" style="font-size:.86rem; line-height:1.8; max-width:340px; transition-delay:.06s">
                        Sistem Informasi Manajemen Bimbingan Konseling &mdash;
                        mendampingi setiap langkah tumbuh siswa melalui layanan
                        konseling yang tercatat, terpantau, dan mudah diakses.
                    </p>
                </div>

                <div class="col-sm-6 col-lg-4">
                    <h6 class="reveal" style="transition-delay:.10s">Kontak</h6>
                    <ul class="footer-list reveal" style="transition-delay:.14s">
                        <li>
                            <i class="fas fa-location-dot"></i>
                            <span>Jl. Dr. Sutomo No. 4C, Simpang Haru,<br>Kec. Padang Timur, Kota Padang,<br>Sumatera Barat 25123</span>
                        </li>
                        <li>
                            <i class="fas fa-envelope"></i>
                            <a href="mailto:bk@bkkartika.my.id">bk@bkkartika.my.id</a>
                        </li>
                        <li>
                            <i class="fas fa-globe"></i>
                            <a href="https://bkkartika.my.id">bkkartika.my.id</a>
                        </li>
                    </ul>
                </div>

                <div class="col-sm-6 col-lg-4">
                    <h6 class="reveal" style="transition-delay:.18s">Jam Layanan BK</h6>
                    <div class="footer-hours reveal" style="transition-delay:.22s">
                        <div><span>Senin &ndash; Kamis</span><span>07.30 &ndash; 15.00</span></div>
                        <div><span>Jumat</span><span>07.30 &ndash; 11.30</span></div>
                        <div><span>Sabtu &amp; Minggu</span><span>Tutup</span></div>
                    </div>
                    <p class="reveal" style="font-size:.78rem; margin-top:.9rem; color:rgba(255,255,255,.5); transition-delay:.26s">
                        <i class="fas fa-circle-info me-1"></i>
                        Di luar jam layanan, gunakan Chatbot BK atau ajukan sesi konseling lewat aplikasi.
                    </p>
                </div>

            </div>

            <div class="footer-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span>&copy; {{ date('Y') }} SMA Kartika I-5 Padang. Seluruh hak cipta dilindungi.</span>
                <a href="{{ route('login') }}" style="color:var(--gold); text-decoration:none; font-weight:600;">
                    Masuk ke SIM BK <i class="fas fa-arrow-right ms-1" style="font-size:.7rem"></i>
                </a>
            </div>
        </div>
    </footer>

    <script>
    (function () {
        'use strict';

        var kurangiGerak = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        /* ------------------------------------------------------------
           1. SCROLL REVEAL
           Memakai IntersectionObserver, bukan event 'scroll'. Bedanya:
           observer hanya dipanggil browser saat elemen benar-benar
           melintasi batas layar, sedangkan listener 'scroll' menyala
           puluhan kali per detik walau tidak ada yang berubah.
           Setiap elemen dilepas (unobserve) setelah muncul supaya tidak
           dihitung ulang selamanya.
           ------------------------------------------------------------ */
        var elemenReveal = document.querySelectorAll('.reveal');

        if (kurangiGerak || !('IntersectionObserver' in window)) {
            // Browser lama atau pengguna minta gerak dikurangi:
            // tampilkan semuanya langsung supaya halaman tidak kosong.
            elemenReveal.forEach(function (el) { el.classList.add('is-visible'); });
        } else {
            var pengamat = new IntersectionObserver(function (entri) {
                entri.forEach(function (e) {
                    if (!e.isIntersecting) return;
                    e.target.classList.add('is-visible');
                    pengamat.unobserve(e.target);
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

            elemenReveal.forEach(function (el) { pengamat.observe(el); });
        }

        // Perangkat sentuh tidak punya kursor, jadi efek di bawah ini
        // dilewati seluruhnya -- percuma dihitung dan hanya membuang daya.
        if (kurangiGerak || !window.matchMedia('(pointer: fine)').matches) return;

        /* ------------------------------------------------------------
           2. PARALLAX CINCIN
           Cincin bergeser tipis mengikuti kursor. Nilai transform ditulis
           di dalam requestAnimationFrame supaya penulisan gaya menyatu
           dengan siklus gambar browser (tidak memaksa layout ulang
           berkali-kali dalam satu frame).
           ------------------------------------------------------------ */
        var cincin = document.getElementById('heroRings');
        if (cincin) {
            var frameCincin = null;
            window.addEventListener('mousemove', function (e) {
                if (frameCincin) return;
                frameCincin = requestAnimationFrame(function () {
                    var x = (e.clientX / window.innerWidth - 0.5) * -26;
                    var y = (e.clientY / window.innerHeight - 0.5) * -26;
                    // translateY(-50%) wajib ditulis ulang: properti transform
                    // bersifat satu kesatuan, jadi kalau hanya menulis
                    // translate() nilai pemusatan vertikal dari CSS akan hilang
                    // dan cincin melompat ke bawah.
                    cincin.style.transform = 'translateY(-50%) translate(' + x + 'px,' + y + 'px)';
                    frameCincin = null;
                });
            }, { passive: true });
        }

        /* ------------------------------------------------------------
           3. CAHAYA PADA KARTU FITUR
           Posisi kursor relatif terhadap kartu disimpan sebagai variabel
           CSS, lalu dipakai gradient di ::before. Cara ini memindahkan
           kerja menggambar ke CSS -- JS hanya mengirim dua angka.
           ------------------------------------------------------------ */
        document.querySelectorAll('.feature-card').forEach(function (kartu) {
            var frameKartu = null;
            kartu.addEventListener('mousemove', function (e) {
                if (frameKartu) return;
                frameKartu = requestAnimationFrame(function () {
                    var kotak = kartu.getBoundingClientRect();
                    kartu.style.setProperty('--mx', (e.clientX - kotak.left) + 'px');
                    kartu.style.setProperty('--my', (e.clientY - kotak.top) + 'px');
                    frameKartu = null;
                });
            }, { passive: true });
        });
    })();
    </script>
</body>
</html>
