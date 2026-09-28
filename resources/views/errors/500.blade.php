{{--
    PERBAIKAN (AUDIT, 2 Agustus 2026): halaman error 500 khusus.

    Dengan APP_DEBUG=false di produksi, Laravel menampilkan halaman "Server
    Error" bawaan yang polos dan membingungkan untuk guru/siswa. Halaman ini
    menggantikannya dengan penjelasan berbahasa Indonesia dan arahan yang
    jelas, tanpa membocorkan detail teknis apa pun.

    Berlaku juga untuk 503 (maintenance) lewat file terpisah bila diperlukan.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terjadi Kesalahan &mdash; SIM BK</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container" style="max-width: 640px;">
        <div class="text-center" style="padding-top: 12vh;">

            <div class="display-1 fw-bold text-secondary opacity-25">500</div>

            <h1 class="h3 fw-bold mt-3">Sistem sedang bermasalah</h1>

            <p class="text-muted mt-3 mb-4">
                Maaf, terjadi kesalahan saat memproses permintaan kamu.
                Kesalahan ini sudah otomatis dicatat dan akan diperiksa oleh
                pengelola sistem. Data yang sudah tersimpan sebelumnya
                <strong>tidak hilang</strong>.
            </p>

            <div class="alert alert-light border text-start small text-muted">
                <strong class="d-block mb-1 text-dark">Yang bisa kamu lakukan:</strong>
                <ul class="mb-0 ps-3">
                    <li>Muat ulang halaman ini.</li>
                    <li>Kembali ke halaman sebelumnya lalu coba lagi.</li>
                    <li>Kalau berulang terus, laporkan ke Guru BK atau TU/Admin
                        sambil menyebutkan halaman apa yang sedang dibuka.</li>
                </ul>
            </div>

            <div class="d-flex gap-2 justify-content-center mt-4">
                <a href="{{ url('/') }}" class="btn btn-primary rounded-pill px-4">
                    Kembali ke Beranda
                </a>
                <button onclick="location.reload()" class="btn btn-outline-secondary rounded-pill px-4">
                    Muat Ulang
                </button>
            </div>

            <p class="text-muted small mt-5">SIM BK &mdash; SMA Kartika I-5 Padang</p>
        </div>
    </div>
</body>
</html>
