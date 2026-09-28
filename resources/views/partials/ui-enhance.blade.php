{{--
    LETAKKAN DI: resources/views/partials/ui-enhance.blade.php

    PERCANTIK UI (30 Juli 2026): lapisan animasi & interaksi halus yang
    otomatis berlaku di SEMUA halaman lewat layout bersama (tu/guru/kepsek/
    walikelas/gurumapel/siswa + guest/auth), tanpa perlu ubah satu-satu file
    halaman. Sengaja ditulis murni CSS + JS vanilla (tidak bergantung
    Bootstrap/Tailwind/Alpine tertentu) supaya aman di-include di layout
    mana pun, termasuk yang tidak memuat Bootstrap sama sekali (guest.blade.php).

    Cara pakai: cukup @include('partials.ui-enhance') sekali per layout,
    taruh di dekat penutup </body> (setelah script Bootstrap kalau ada).

    Cara opt-out per elemen (kalau suatu saat perlu):
    - Form   : tambahkan atribut `data-no-loading` di tag <form>.
    - Alert  : tambahkan atribut `data-simbk-persist` di div alert-nya
               (supaya tidak auto-hilang, misal untuk pesan penting).
--}}
<style>
    @media (prefers-reduced-motion: no-preference) {
        .card, .page-header-banner {
            animation: simbkFadeUp .4s cubic-bezier(.2,.7,.3,1) both;
        }
        .dropdown-menu.show {
            animation: simbkFadeOpacity .15s ease-out both;
        }
        .alert {
            animation: simbkFadeUp .3s ease-out both;
        }
    }
    @keyframes simbkFadeUp {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes simbkFadeOpacity {
        from { opacity: 0; }
        to   { opacity: 1; }
    }

    /* Hover halus untuk kartu & tombol -- dijalankan lewat transition biasa
       (bukan animation) supaya tidak bentrok dengan animasi masuk di atas. */
    .card, .page-header-banner, .btn {
        transition: transform .18s ease, box-shadow .18s ease, filter .15s ease;
    }
    .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 26px rgba(15,23,42,.08);
    }
    .btn:hover:not(:disabled) { transform: translateY(-1px); filter: brightness(1.04); }
    .btn:active:not(:disabled) { transform: translateY(0); filter: brightness(.98); }

    /* PERBAIKAN BUG (30 Juli 2026): modal Bootstrap (mis. "Lihat Detail" di
       Jurnal BK) jadi TIDAK BISA DIKLIK SAMA SEKALI (harus refresh halaman)
       kalau tombol pemicunya ada di dalam sebuah .card. Penyebabnya:
       - Klik tidak menggerakkan mouse (mousedown+mouseup di titik yang
         sama), jadi begitu modal & backdrop muncul, browser TIDAK
         langsung meng-update status :hover -- .card tempat tombol tadi
         berada masih dianggap ":hover" (transform: translateY(-3px) di
         atas masih aktif) sampai mouse benar-benar digerakkan.
       - CSS "transform" pada elemen ancestor membuat elemen itu jadi
         containing block BARU untuk descendant "position: fixed" --
         termasuk modal Bootstrap, yang di beberapa halaman (mis. Jurnal
         BK) dirender sebagai descendant dari .card tersebut.
       - Akibatnya posisi/area klik modal jadi salah hitung relatif ke
         .card yang sedang "terangkat", bukan ke viewport -- modal
         terlihat tapi tombol X/Tutup/dsb di dalamnya tidak bisa diklik.
       Perbaikan: begitu ada modal Bootstrap yang terbuka (body dapat
       class "modal-open"), batalkan transform hover pada SEMUA .card,
       supaya tidak ada containing block yang salah lagi. */
    body.modal-open .card,
    body.modal-open .card:hover {
        transform: none;
    }

    #sidebar .nav-link { transition: background-color .18s ease, color .18s ease, transform .12s ease; }
    #sidebar .nav-link:hover { transform: translateX(2px); }

    #sidebarOverlay { transition: opacity .25s ease; opacity: 0; }
    #sidebarOverlay.show { opacity: 1; }

    table tbody tr { transition: background-color .15s ease; }

    a, .nav-link, .dropdown-item { transition: color .15s ease, background-color .15s ease; }

    :focus-visible { outline: 2px solid var(--primary-color, #1f4b3f); outline-offset: 2px; }

    /* ===== Notifikasi (alert) bisa ditutup & hilang otomatis ===== */
    .alert { position: relative; }
    .alert.simbk-alert-hide { animation: simbkAlertOut .35s ease-in forwards; }
    @keyframes simbkAlertOut {
        to {
            opacity: 0; transform: translateY(-6px); max-height: 0;
            margin: 0; padding-top: 0; padding-bottom: 0; overflow: hidden;
        }
    }
    .simbk-alert-close {
        position: absolute; top: 2px; right: 6px; background: none; border: none;
        font-size: 1.15rem; line-height: 1; cursor: pointer; opacity: .5; padding: 4px 8px;
    }
    .simbk-alert-close:hover { opacity: .85; }

    /* ===== Tombol otomatis kasih spinner saat form disubmit ===== */
    .btn-loading { position: relative; color: transparent !important; pointer-events: none; }
    .btn-loading::after {
        content: ""; position: absolute; top: 50%; left: 50%; width: 1em; height: 1em;
        margin: -0.5em 0 0 -0.5em; border-radius: 50%;
        border: 2px solid rgba(255,255,255,.55); border-top-color: #fff;
        animation: simbkSpin .6s linear infinite;
    }
    .btn-outline-success.btn-loading::after,
    .btn-outline-danger.btn-loading::after,
    .btn-outline-primary.btn-loading::after,
    .btn-outline-secondary.btn-loading::after,
    .btn-outline-dark.btn-loading::after,
    .btn-outline-warning.btn-loading::after,
    .btn-outline-info.btn-loading::after,
    .btn-link.btn-loading::after,
    .btn-light.btn-loading::after {
        border-color: rgba(31,75,63,.3);
        border-top-color: var(--primary-color, #1f4b3f);
    }
    @keyframes simbkSpin { to { transform: rotate(360deg); } }

    /* ===== PERBAIKAN (30 Juli 2026): backdrop modal dulu polos gelap
       bawaan Bootstrap (hitam opacity .5) sehingga tampilan di belakang
       modal terlihat "kusam/pudar" tidak konsisten dengan gaya halus &
       profesional di halaman lain. Sekarang pakai efek kaca buram (blur)
       + tint warna brand supaya tetap terlihat elegan, bukan sekadar gelap. ===== */
    .modal-backdrop { background-color: #0b1a15; }
    .modal-backdrop.show { opacity: .62; }
    @supports ((backdrop-filter: blur(2px)) or (-webkit-backdrop-filter: blur(2px))) {
        .modal-backdrop.show { backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px); }
    }
    @media (prefers-reduced-motion: no-preference) {
        .modal.show .modal-dialog { animation: simbkModalPop .25s cubic-bezier(.2,.9,.3,1.2) both; }
    }
    @keyframes simbkModalPop {
        from { opacity: 0; transform: scale(.95) translateY(10px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }

    /* ===== PERBAIKAN (30 Juli 2026): indikator loading global (progress
       bar tipis di atas halaman) supaya SEMUA proses reload/navigasi --
       termasuk form upload gambar (mis. Artikel BK) yang secara alami
       makan waktu lebih lama karena mengunggah file -- terasa konsisten
       dan jelas sedang berjalan, bukan terkesan "macet"/beda dari halaman
       lain yang tanpa upload file terasa instan. ===== */
    #simbkProgressBar {
        position: fixed; top: 0; left: 0; height: 3px; width: 0%;
        background: linear-gradient(90deg, #1f4b3f, #4caf82, #1f4b3f);
        background-size: 200% 100%;
        z-index: 2000; opacity: 0;
        transition: width .4s ease, opacity .2s ease;
        pointer-events: none;
    }
    #simbkProgressBar.simbk-active {
        opacity: 1;
        animation: simbkProgressShimmer 1.2s linear infinite;
    }
    @keyframes simbkProgressShimmer { to { background-position: -200% 0; } }
</style>
<script>
(function () {
    // 0. Progress bar tipis di atas halaman -- muncul tiap kali ada form
    // disubmit (termasuk upload file) atau link navigasi biasa diklik,
    // supaya setiap "reload" di seluruh halaman terasa konsisten.
    var bar = document.createElement('div');
    bar.id = 'simbkProgressBar';
    document.body.appendChild(bar);

    function mulaiProgress() {
        bar.classList.add('simbk-active');
        bar.style.width = '15%';
        requestAnimationFrame(function () {
            setTimeout(function () { bar.style.width = '75%'; }, 60);
        });
    }

    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            if (form.checkValidity && !form.checkValidity()) return;
            mulaiProgress();
        });
    });

    document.addEventListener('click', function (e) {
        var link = e.target.closest('a[href]');
        if (!link) return;
        var href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
        if (link.hasAttribute('data-bs-toggle') || link.hasAttribute('download')) return;
        if (link.target === '_blank') return;
        mulaiProgress();
    });

    // Jaga-jaga: kalau halaman dipulihkan dari cache back-forward browser
    // (tombol Back), progress bar tidak boleh nyangkut di tengah jalan.
    window.addEventListener('pageshow', function (evt) {
        if (evt.persisted) {
            bar.classList.remove('simbk-active');
            bar.style.width = '0%';
        }
    });

    // 1. Notifikasi (flash alert) muncul halus & otomatis hilang setelah
    // beberapa detik, lengkap dengan tombol tutup manual.
    document.querySelectorAll('.alert-success, .alert-danger, .alert-warning, .alert-info').forEach(function (alert) {
        if (alert.hasAttribute('data-simbk-persist')) return;

        if (!alert.querySelector('.simbk-alert-close') && !alert.querySelector('.btn-close')) {
            var closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'simbk-alert-close';
            closeBtn.setAttribute('aria-label', 'Tutup notifikasi');
            closeBtn.innerHTML = '&times;';
            closeBtn.addEventListener('click', function () { hideAlert(alert); });
            alert.appendChild(closeBtn);
        }

        var timer = setTimeout(function () { hideAlert(alert); }, 5000);
        alert.addEventListener('mouseenter', function () { clearTimeout(timer); });
    });

    function hideAlert(el) {
        if (!el || !el.parentNode) return;
        el.classList.add('simbk-alert-hide');
        setTimeout(function () { el.remove(); }, 400);
    }

    // 2. Tombol submit otomatis kasih spinner + nonaktif sesaat saat form
    // dikirim, supaya user tau prosesnya jalan & tidak klik dobel.
    document.querySelectorAll('form').forEach(function (form) {
        if (form.hasAttribute('data-no-loading')) return;

        form.addEventListener('submit', function () {
            if (form.checkValidity && !form.checkValidity()) return;

            var submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.classList.add('btn-loading');
                submitBtn.disabled = true;

                // Jaring pengaman: kalau 12 detik belum ada navigasi/reload
                // (mis. koneksi lambat/gagal), tombol diaktifkan lagi supaya
                // user tidak terjebak tombol yang disabled selamanya.
                setTimeout(function () {
                    submitBtn.classList.remove('btn-loading');
                    submitBtn.disabled = false;
                }, 12000);
            }
        });
    });
})();
</script>
