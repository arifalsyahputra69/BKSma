{{--
    FAVICON & IKON APLIKASI — SIM BK SMA Kartika I-5 Padang
    Ditambahkan 2 Agustus 2026.

    Sebelumnya tidak ada satu pun tag ikon di project ini, sehingga tab
    browser menampilkan ikon bola dunia bawaan. File public/favicon.ico
    yang ada pun berukuran 0 byte (kosong).

    Dipakai dengan @include('partials.favicon') di dalam <head> setiap
    layout. Ditaruh di satu berkas supaya kalau logonya diganti, cukup
    diubah di sini — tidak perlu menyunting sebelas file.

    Catatan: logo dipakai apa adanya dalam format PNG. Browser modern
    semuanya mendukung PNG untuk favicon, jadi tidak perlu konversi ke
    .ico. Ukuran aslinya juga sudah memadai untuk ikon tab maupun ikon
    layar utama saat aplikasi di-"Add to Home Screen" di HP.
--}}

<link rel="icon" type="image/png" href="{{ asset('images/logo-kartika.png') }}">
<link rel="shortcut icon" type="image/png" href="{{ asset('images/logo-kartika.png') }}">

{{-- Ikon saat siswa menambahkan situs ini ke layar utama HP --}}
<link rel="apple-touch-icon" href="{{ asset('images/logo-kartika.png') }}">

{{-- Warna bilah atas browser di HP, disamakan dengan hijau SIM BK --}}
<meta name="theme-color" content="#1F4B3F">
