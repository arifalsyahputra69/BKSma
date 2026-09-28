{{--
    PERBAIKAN (AUDIT, 2 Agustus 2026): halaman 404 khusus.

    Halaman ini juga muncul saat findOrFail() gagal -- yang di aplikasi ini
    sering berarti "data itu bukan milik kamu" (mis. Guru BK membuka jurnal
    siswa yang bukan binaannya). Karena itu penjelasannya sengaja mencakup
    kemungkinan tersebut, tanpa mengonfirmasi apakah datanya benar-benar ada
    (supaya tidak bisa dipakai menebak-nebak keberadaan data orang lain).
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Halaman Tidak Ditemukan &mdash; SIM BK</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container" style="max-width: 640px;">
        <div class="text-center" style="padding-top: 12vh;">

            <div class="display-1 fw-bold text-secondary opacity-25">404</div>

            <h1 class="h3 fw-bold mt-3">Halaman tidak ditemukan</h1>

            <p class="text-muted mt-3 mb-4">
                Alamat yang kamu buka tidak ada, sudah dipindahkan, atau
                datanya memang bukan bagian dari akses akunmu.
            </p>

            <div class="d-flex gap-2 justify-content-center mt-4">
                <a href="{{ url('/') }}" class="btn btn-primary rounded-pill px-4">
                    Kembali ke Beranda
                </a>
                <a href="javascript:history.back()" class="btn btn-outline-secondary rounded-pill px-4">
                    Halaman Sebelumnya
                </a>
            </div>

            <p class="text-muted small mt-5">SIM BK &mdash; SMA Kartika I-5 Padang</p>
        </div>
    </div>
</body>
</html>
