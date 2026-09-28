<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SIM BK') }} - SMA Kartika I-5 Padang</title>
        @include('partials.favicon')

        <!-- Fonts: Inter untuk UI, Fraunces untuk aksen judul -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|fraunces:500,600i,700i&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- CATATAN PENTING UNTUK PERUBAHAN BERIKUTNYA:
             Tailwind di proyek ini di-compile lewat Vite dan kelas yang tidak
             terpakai dibuang saat build. Jadi menambah kelas Tailwind BARU di
             file ini tidak akan berefek apa-apa di server sampai aset di-build
             ulang. Karena itu seluruh tampilan baru di bawah ditulis sebagai
             CSS biasa (kelas ber-awalan .simbk-), bukan utility Tailwind. --}}
        <style>
            :root {
                --simbk-primary: #1f4b3f;
                --simbk-primary-dark: #123028;
                --simbk-primary-deep: #0d241e;
                --simbk-primary-light: #eaf3ee;
                --simbk-gold: #c9974a;
                --simbk-paper: #fbfaf7;
            }
            .font-display { font-family: 'Fraunces', serif; }

            /* =========================================================
               PANEL HIJAU (kiri)
               Gradient bergerak pelan lewat background-position. Properti
               ini tidak memicu perhitungan tata letak ulang, jadi aman
               dibiarkan berjalan terus tanpa membebani perangkat.
               ========================================================= */
            .simbk-panel {
                background: linear-gradient(145deg,
                    var(--simbk-primary-dark) 0%,
                    var(--simbk-primary) 40%,
                    var(--simbk-primary-deep) 72%,
                    var(--simbk-primary-dark) 100%);
                background-size: 220% 220%;
                animation: simbk-panel-shift 24s ease-in-out infinite;
            }
            @keyframes simbk-panel-shift {
                0%, 100% { background-position: 0% 50%; }
                50%      { background-position: 100% 50%; }
            }

            /* Pola titik halus — memberi tekstur supaya panel tidak polos. */
            .simbk-grid {
                position: absolute;
                inset: 0;
                background-image: radial-gradient(rgba(255,255,255,0.10) 1px, transparent 1px);
                background-size: 30px 30px;
                mask-image: radial-gradient(ellipse 75% 70% at 40% 35%, #000 35%, transparent 100%);
                -webkit-mask-image: radial-gradient(ellipse 75% 70% at 40% 35%, #000 35%, transparent 100%);
                pointer-events: none;
            }

            /* Gumpalan cahaya lembut yang mengambang. */
            .simbk-blob {
                position: absolute;
                border-radius: 50%;
                filter: blur(55px);
                pointer-events: none;
                animation: simbk-blob-drift 20s ease-in-out infinite;
            }
            .simbk-blob--a { width: 320px; height: 320px; background: #2f7a63; opacity: .40; top: -90px; left: -70px; }
            .simbk-blob--b { width: 260px; height: 260px; background: var(--simbk-gold); opacity: .20; bottom: 12%; right: -60px; animation-delay: -8s; }
            @keyframes simbk-blob-drift {
                0%, 100% { transform: translate(0,0) scale(1); }
                33%      { transform: translate(22px,-18px) scale(1.08); }
                66%      { transform: translate(-16px,14px) scale(.94); }
            }

            /* Posisi & parallax kursor dipisah ke wrapper, sementara animasi
               "napas" tetap di elemen svg. Kalau keduanya ditaruh di elemen
               yang sama, animation CSS akan selalu menimpa transform inline
               yang di-set JS, dan efek parallax jadi tidak terlihat. */
            .simbk-rings-wrap {
                position: absolute;
                right: -18%;
                bottom: -22%;
                width: 65%;
                aspect-ratio: 1 / 1;
                transition: transform .3s cubic-bezier(.2,.7,.3,1);
            }
            .simbk-rings {
                display: block;
                width: 100%;
                height: 100%;
                opacity: 0.9;
                animation: simbk-breathe 14s ease-in-out infinite;
                transform-origin: center;
            }
            @keyframes simbk-breathe {
                0%, 100% { transform: scale(1) rotate(0deg); }
                50% { transform: scale(1.045) rotate(2.5deg); }
            }
            .simbk-ring-spin { animation: simbk-ring-spin 45s linear infinite; transform-origin: center; }
            @keyframes simbk-ring-spin { to { transform: rotate(360deg); } }

            /* Titik-titik dekoratif yang mengambang pelan di panel hijau */
            .simbk-dot {
                position: absolute;
                border-radius: 9999px;
                background: rgba(255,255,255,0.35);
                animation: simbk-float 7s ease-in-out infinite;
            }
            .simbk-dot--gold { background: rgba(201,151,74,0.55); }
            @keyframes simbk-float {
                0%, 100% { transform: translateY(0) translateX(0); opacity: .55; }
                50% { transform: translateY(-14px) translateX(6px); opacity: 1; }
            }

            .simbk-logo-box { transition: transform .35s cubic-bezier(.2,.7,.3,1); }
            .simbk-logo-box:hover { transform: rotate(-6deg) scale(1.08); }

            /* Kutipan: garis emas di kiri yang "tumbuh" saat halaman dibuka. */
            .simbk-quote { position: relative; padding-left: 1.1rem; }
            .simbk-quote::before {
                content: '';
                position: absolute;
                left: 0; top: .35rem; bottom: .35rem;
                width: 3px;
                border-radius: 3px;
                background: linear-gradient(180deg, var(--simbk-gold), transparent);
                transform: scaleY(0);
                transform-origin: top;
                animation: simbk-grow-line .9s cubic-bezier(.2,.7,.3,1) .5s forwards;
            }
            @keyframes simbk-grow-line { to { transform: scaleY(1); } }

            /* Munculnya isi panel, satu per satu dari bawah. */
            .simbk-rise { animation: simbk-rise .7s cubic-bezier(.2,.7,.3,1) both; }
            @keyframes simbk-rise {
                from { opacity: 0; transform: translateY(14px); }
                to   { opacity: 1; transform: translateY(0); }
            }

            /* =========================================================
               SISI FORM (kanan)
               ========================================================= */
            .simbk-stage {
                background-color: var(--simbk-paper);
                background-image:
                    radial-gradient(circle at 88% 8%,  rgba(31,75,63,.07), transparent 42%),
                    radial-gradient(circle at 6%  94%, rgba(201,151,74,.09), transparent 45%);
            }

            /* Kartu form: sedikit terangkat dari latar, dengan garis emas
               tipis di sisi atas sebagai penanda identitas. */
            .simbk-card {
                position: relative;
                background: #fff;
                border: 1px solid rgba(31,75,63,.09);
                border-radius: 20px;
                padding: 2.1rem 1.9rem;
                box-shadow: 0 1px 2px rgba(18,48,40,.04), 0 18px 42px -22px rgba(18,48,40,.28);
                overflow: hidden;
            }
            .simbk-card::before {
                content: '';
                position: absolute;
                top: 0; left: 0; right: 0;
                height: 3px;
                background: linear-gradient(90deg, var(--simbk-primary), var(--simbk-gold), var(--simbk-primary));
                background-size: 200% 100%;
                animation: simbk-sheen 6s linear infinite;
            }
            @keyframes simbk-sheen {
                0%   { background-position: 0% 0; }
                100% { background-position: 200% 0; }
            }

            .simbk-fade-in { animation: simbk-fade-in .55s cubic-bezier(.2,.7,.3,1) both; }
            @keyframes simbk-fade-in {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }

            /* Header versi mobile (panel hijau disembunyikan di layar kecil). */
            /* Padding & jarak ditulis di sini, bukan sebagai utility Tailwind,
               karena kelas Tailwind yang belum pernah dipakai di tempat lain
               akan terbuang saat build dan tidak berefek apa-apa. */
            .simbk-mobile-head {
                background: linear-gradient(135deg, var(--simbk-primary) 0%, var(--simbk-primary-dark) 100%);
                border-radius: 0 0 22px 22px;
                position: relative;
                overflow: hidden;
                padding: 2.25rem 1.5rem 1.75rem;
                margin-bottom: 1.5rem;
            }
            .simbk-mobile-head::after {
                content: '';
                position: absolute;
                width: 180px; height: 180px;
                border-radius: 50%;
                background: rgba(255,255,255,.07);
                top: -90px; right: -50px;
            }

            /* =========================================================
               AKSESIBILITAS
               Kalau pengguna menyalakan "kurangi gerak" di perangkatnya,
               semua animasi dimatikan. Elemen yang tampilnya bergantung
               pada animasi (…-rise, …-fade-in, garis kutipan) dipaksa ke
               keadaan akhir supaya tidak ada yang tertinggal tak terlihat.
               ========================================================= */
            @media (prefers-reduced-motion: reduce) {
                *, *::before, *::after {
                    animation: none !important;
                    transition: none !important;
                }
                .simbk-rise, .simbk-fade-in { opacity: 1; transform: none; }
                .simbk-quote::before { transform: scaleY(1); }
            }
        </style>
    </head>
    <body class="antialiased" style="font-family: 'Inter', sans-serif;">
        <div class="min-h-screen lg:flex simbk-stage">

            {{-- Panel hijau bermerek — hanya tampil di layar lg ke atas --}}
            <div id="simbkPanel" class="hidden lg:flex lg:w-[44%] xl:w-[40%] relative overflow-hidden flex-col justify-between px-12 py-12 simbk-panel">

                <div class="simbk-grid"></div>
                <span class="simbk-blob simbk-blob--a"></span>
                <span class="simbk-blob simbk-blob--b"></span>

                <div id="simbkRingsWrap" class="simbk-rings-wrap">
                    <svg class="simbk-rings" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <circle cx="200" cy="200" r="70" stroke="rgba(255,255,255,0.14)" stroke-width="1.5"/>
                        <circle cx="200" cy="200" r="120" stroke="rgba(255,255,255,0.11)" stroke-width="1.5"/>
                        <circle cx="200" cy="200" r="170" stroke="rgba(255,255,255,0.08)" stroke-width="1.5"/>
                        <circle class="simbk-ring-spin" cx="200" cy="200" r="198"
                                stroke="rgba(201,151,74,0.38)" stroke-width="2"
                                stroke-dasharray="5 13" stroke-linecap="round"/>
                    </svg>
                </div>

                <span class="simbk-dot" style="width:8px;height:8px;top:18%;left:62%;animation-delay:.2s;"></span>
                <span class="simbk-dot simbk-dot--gold" style="width:5px;height:5px;top:32%;left:78%;animation-delay:1.4s;"></span>
                <span class="simbk-dot" style="width:6px;height:6px;top:65%;left:20%;animation-delay:.8s;"></span>
                <span class="simbk-dot simbk-dot--gold" style="width:4px;height:4px;top:48%;left:52%;animation-delay:2.1s;"></span>
                <span class="simbk-dot" style="width:5px;height:5px;top:76%;left:70%;animation-delay:3s;"></span>

                <a href="/" class="relative flex items-center gap-3 simbk-rise">
                    <span class="simbk-logo-box flex items-center justify-center w-11 h-11 rounded-xl bg-white p-1.5 shadow-sm">
                        <img src="{{ asset('images/logo-kartika.png') }}" alt="Logo SMA Kartika I-5 Padang" class="w-full h-full object-contain" />
                    </span>
                    <span class="text-white font-semibold tracking-wide">SIM BK</span>
                </a>

                <div class="relative max-w-sm simbk-rise" style="animation-delay:.14s">
                    <div class="simbk-quote">
                        <p class="font-display italic text-white text-[1.65rem] leading-snug">
                            &ldquo;Mendampingi setiap langkah tumbuhmu.&rdquo;
                        </p>
                        <p class="mt-4 text-sm text-white/65 leading-relaxed">
                            Sistem Informasi Bimbingan Konseling — ruang digital Guru BK, wali kelas, dan siswa SMA Kartika I-5 Padang untuk saling terhubung.
                        </p>
                    </div>
                </div>

                <p class="relative text-xs text-white/45 simbk-rise" style="animation-delay:.24s">© {{ date('Y') }} SMA Kartika I-5 Padang</p>
            </div>

            {{-- Header ringkas — hanya tampil di mobile, pengganti panel hijau --}}
            <div class="lg:hidden simbk-mobile-head">
                <div class="relative flex items-center gap-3">
                    <span class="flex items-center justify-center w-11 h-11 rounded-xl bg-white p-1.5 shadow-sm">
                        <img src="{{ asset('images/logo-kartika.png') }}" alt="Logo SMA Kartika I-5 Padang" class="w-full h-full object-contain" />
                    </span>
                    <div>
                        <p class="font-semibold text-sm text-white">SIM BK</p>
                        <p class="text-xs text-white/60 -mt-0.5">SMA Kartika I-5 Padang</p>
                    </div>
                </div>
            </div>

            {{-- Panel form --}}
            <div class="flex-1 flex items-center justify-center px-6 pb-12 lg:p-12">
                <div class="w-full max-w-sm simbk-fade-in">
                    <div class="simbk-card">
                        {{ $slot }}
                    </div>
                    <p class="text-center text-xs text-gray-400 lg:hidden" style="margin-top:1.25rem">
                        © {{ date('Y') }} SMA Kartika I-5 Padang
                    </p>
                </div>
            </div>
        </div>

        <script>
            // Efek parallax halus: cincin dekoratif di panel hijau ikut
            // bergeser tipis mengikuti posisi kursor, memberi kesan "hidup".
            // Dilewati kalau perangkat tidak punya mouse presisi (touch) atau
            // user mengaktifkan "prefers-reduced-motion".
            (function () {
                var panel = document.getElementById('simbkPanel');
                var ringsWrap = document.getElementById('simbkRingsWrap');
                if (!panel || !ringsWrap) return;
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                if (!window.matchMedia('(pointer: fine)').matches) return;

                // Penulisan gaya ditunda ke requestAnimationFrame supaya
                // menyatu dengan siklus gambar browser: mousemove bisa
                // menyala ratusan kali per detik, sedangkan layar hanya
                // digambar ~60 kali. Tanpa ini, sebagian besar perhitungan
                // terbuang percuma.
                var frame = null;
                panel.addEventListener('mousemove', function (e) {
                    if (frame) return;
                    frame = requestAnimationFrame(function () {
                        var rect = panel.getBoundingClientRect();
                        var relX = (e.clientX - rect.left) / rect.width - 0.5;
                        var relY = (e.clientY - rect.top) / rect.height - 0.5;
                        ringsWrap.style.transform = 'translate(' + (relX * -16) + 'px,' + (relY * -16) + 'px)';
                        frame = null;
                    });
                }, { passive: true });

                panel.addEventListener('mouseleave', function () {
                    ringsWrap.style.transform = 'translate(0,0)';
                });
            })();
        </script>
        @include('partials.ui-enhance')
    </body>
</html>
