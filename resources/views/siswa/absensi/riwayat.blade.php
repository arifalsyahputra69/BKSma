@extends('layouts.siswa')

@section('title', 'Absensi QR')

@section('content')

{{-- PERBAIKAN (26 Juli 2026): sebelumnya halaman ini hanya menampilkan
     riwayat absensi, siswa harus pakai aplikasi kamera/QR scanner
     terpisah untuk absen. Sekarang ditambahkan tab "Scan QR" yang
     langsung mengakses kamera perangkat dari dalam web (memakai jsQR),
     supaya siswa bisa absen langsung dari halaman ini. --}}

<div class="page-header-banner mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="page-header-icon"><i class="fas fa-qrcode"></i></div>
        <div>
            <h3 class="fw-bold mb-1 text-white">Absensi QR</h3>
            <small class="text-white-50">Scan QR absensi kelas langsung dari kamera, atau lihat riwayat kehadiranmu</small>
        </div>
    </div>
</div>

<ul class="nav nav-pills gap-2 mb-4" id="absensiTab">
    <li class="nav-item">
        <button class="nav-link active fw-semibold" id="tab-btn-scan" data-bs-toggle="pill" data-bs-target="#tab-scan" type="button">
            <i class="fas fa-camera me-1"></i> Scan Kamera
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold" id="tab-btn-riwayat" data-bs-toggle="pill" data-bs-target="#tab-riwayat" type="button">
            <i class="fas fa-clock-rotate-left me-1"></i> Riwayat Absensi
        </button>
    </li>
</ul>

<div class="tab-content">

    {{-- ============ TAB: SCAN KAMERA ============ --}}
    <div class="tab-pane fade show active" id="tab-scan">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-center">
                    <div style="max-width: 420px; width: 100%;">

                        <div id="qrVideoWrapper" class="position-relative rounded-4 overflow-hidden bg-dark mb-3" style="aspect-ratio: 1/1;">
                            <video id="qrVideo" class="w-100 h-100" style="object-fit: cover; display:none;" playsinline muted></video>
                            <canvas id="qrCanvas" class="d-none"></canvas>
                            <div id="qrPlaceholder" class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-center text-white-50 p-4">
                                <i class="fas fa-camera fa-3x mb-3"></i>
                                <p class="mb-0 small">Kamera belum aktif. Tekan tombol di bawah untuk mulai scan QR absensi.</p>
                            </div>
                            <div id="qrFrame" class="position-absolute top-50 start-50 translate-middle d-none" style="width: 70%; height: 70%; border: 3px solid #22c55e; border-radius: 18px; box-shadow: 0 0 0 9999px rgba(0,0,0,0.35);"></div>
                        </div>

                        <div id="qrAlert" class="alert d-none mb-3" role="alert"></div>

                        <div class="d-grid gap-2">
                            <button id="btnStartScan" type="button" class="btn btn-success fw-bold py-2">
                                <i class="fas fa-camera me-2"></i> Aktifkan Kamera & Scan
                            </button>
                            <button id="btnStopScan" type="button" class="btn btn-outline-secondary fw-bold py-2 d-none">
                                <i class="fas fa-stop me-2"></i> Hentikan Kamera
                            </button>
                        </div>

                        <p class="text-muted small text-center mt-3 mb-0">
                            Arahkan kamera ke kode QR absensi yang ditampilkan guru mata pelajaran di depan kelas.
                            Pastikan kamu memberi izin akses kamera saat diminta browser.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ TAB: RIWAYAT ============ --}}
    <div class="tab-pane fade" id="tab-riwayat">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Mapel</th>
                            <th>Jam</th>
                            <th>Status</th>
                            <th>Waktu Scan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($riwayat as $r)
                        <tr>
                            <td>{{ optional($r->sesiAbsensi)->tanggal?->format('d-m-Y') }}</td>
                            <td>{{ optional($r->sesiAbsensi)->mapel }}</td>
                            <td>{{ optional($r->sesiAbsensi)->jam_ke }}</td>
                            <td>
                                @php
                                    $badge = match($r->status) {
                                        'Hadir' => 'bg-success',
                                        'Izin' => 'bg-info text-dark',
                                        'Sakit' => 'bg-warning text-dark',
                                        'Alpha' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $badge }}">{{ $r->status }}</span>
                            </td>
                            <td>{{ $r->waktu_scan?->format('H:i:s') ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada riwayat absensi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
            @if(method_exists($riwayat, 'hasPages') && $riwayat->hasPages())
            <div class="card-footer bg-white">
                {{ $riwayat->links() }}
            </div>
            @endif
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
<script>
(function () {
    const video = document.getElementById('qrVideo');
    const canvas = document.getElementById('qrCanvas');
    const placeholder = document.getElementById('qrPlaceholder');
    const frameBox = document.getElementById('qrFrame');
    const alertBox = document.getElementById('qrAlert');
    const btnStart = document.getElementById('btnStartScan');
    const btnStop = document.getElementById('btnStopScan');
    const ctx = canvas.getContext('2d', { willReadFrequently: true });

    let stream = null;
    let scanning = false;
    let rafId = null;

    function showAlert(message, type) {
        alertBox.className = 'alert alert-' + type + ' mb-3';
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
    }

    function hideAlert() {
        alertBox.classList.add('d-none');
    }

    async function startScan() {
        hideAlert();

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showAlert('Browser ini tidak mendukung akses kamera. Gunakan browser modern seperti Chrome terbaru.', 'danger');
            return;
        }

        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' },
                audio: false,
            });
        } catch (err) {
            showAlert('Tidak bisa mengakses kamera. Pastikan kamu mengizinkan akses kamera untuk halaman ini.', 'danger');
            return;
        }

        video.srcObject = stream;
        video.style.display = 'block';
        placeholder.classList.add('d-none');
        frameBox.classList.remove('d-none');
        btnStart.classList.add('d-none');
        btnStop.classList.remove('d-none');

        await video.play();

        scanning = true;
        tickScan();
    }

    function stopScan(silent) {
        scanning = false;
        if (rafId) cancelAnimationFrame(rafId);

        if (stream) {
            stream.getTracks().forEach(function (track) { track.stop(); });
            stream = null;
        }

        video.style.display = 'none';
        video.srcObject = null;
        placeholder.classList.remove('d-none');
        frameBox.classList.add('d-none');
        btnStart.classList.remove('d-none');
        btnStop.classList.add('d-none');

        if (!silent) hideAlert();
    }

    function tickScan() {
        if (!scanning) return;

        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const code = window.jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: 'dontInvert',
            });

            if (code && code.data) {
                handleDecoded(code.data);
                return;
            }
        }

        rafId = requestAnimationFrame(tickScan);
    }

    function handleDecoded(text) {
        stopScan(true);
        showAlert('QR terbaca. Memproses absensi...', 'info');

        // QR yang dibuat Guru Mapel berisi URL langsung ke rute
        // "siswa.absensi.scan". Isi QR tetap TIDAK BOLEH diikuti begitu saja
        // demi keamanan siswa -- tapi cara memeriksanya diperbaiki.
        //
        // PERBAIKAN BUG (3 Agustus 2026): dulu pemeriksaannya
        //     url.origin !== window.location.origin
        // yaitu membandingkan skema + host + port secara persis. Itu terlalu
        // kaku dan menolak QR yang sebenarnya sah:
        //
        //   - QR dibuat server memakai APP_URL (https://bkkartika.my.id),
        //     sementara siswa sering membuka situs lewat alamat ber-www
        //     (https://www.bkkartika.my.id). Host berbeda -> ditolak.
        //   - Kalau siswa terlanjur membuka lewat http://, skema berbeda
        //     dari https:// di QR -> ditolak juga.
        //
        // Gejalanya membingungkan: QR-nya asli, langsung dari layar guru,
        // tapi tetap dinyatakan "bukan QR absensi sekolah".
        //
        // Sekarang yang diperiksa adalah BENTUK ALAMATNYA: harus berpola
        // /siswa/absensi/scan/<token>. Kalau cocok, siswa diarahkan ke path
        // itu di origin yang sedang dibuka sekarang -- jadi tidak pernah
        // keluar dari situs sekolah, sekaligus tidak lagi mempermasalahkan
        // www / non-www / http / https.
        // Token dicari dari mana pun ia berada di dalam teks QR. Cara ini
        // sengaja longgar soal bentuk alamat (www / non-www, http / https,
        // ada tidaknya nama domain) tapi tetap ketat soal TUJUAN: siswa
        // selalu diarahkan ke path /siswa/absensi/scan/<token> di alamat yang
        // sedang dibuka -- tidak pernah ke domain yang tertulis di QR.
        var token = null;

        // Bentuk normal: .../siswa/absensi/scan/<token>
        var dariUrl = text.match(/\/siswa\/absensi\/scan\/([^\/\?\#\s]+)/i);
        if (dariUrl) {
            token = dariUrl[1];
        } else if (/^[A-Za-z0-9_-]{20,}$/.test(text.trim())) {
            // Cadangan: QR yang isinya token saja, tanpa alamat.
            token = text.trim();
        }

        if (!token) {
            // PERBAIKAN (3 Agustus 2026): pesan lama cuma bilang "bukan QR
            // absensi sekolah" tanpa menyebut apa yang sebenarnya terbaca,
            // sehingga saat ada yang tidak beres tidak ada petunjuk sama
            // sekali untuk menelusurinya. Sekarang isi QR ikut ditampilkan
            // (dipotong 120 karakter) supaya penyebabnya langsung kelihatan.
            var cuplikan = String(text || '(kosong)').slice(0, 120);
            showAlert(
                'QR ini bukan QR absensi sekolah. Isi QR yang terbaca: "' + cuplikan + '"',
                'warning'
            );
            return;
        }

        window.location.href = '/siswa/absensi/scan/' + encodeURIComponent(token);
    }

    btnStart.addEventListener('click', startScan);
    btnStop.addEventListener('click', function () { stopScan(false); });

    // Kalau siswa pindah ke tab "Riwayat", matikan kamera supaya hemat baterai.
    document.getElementById('tab-btn-riwayat').addEventListener('shown.bs.tab', function () {
        stopScan(true);
    });

    window.addEventListener('beforeunload', function () { stopScan(true); });
})();
</script>
@endpush