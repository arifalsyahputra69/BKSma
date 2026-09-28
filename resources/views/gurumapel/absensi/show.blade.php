@extends('layouts.gurumapel')

@section('title', 'Detail Sesi Absensi')

@section('content')

{{--
    TATA LETAK DIUBAH (9 Agustus 2026).

    Sebelumnya QR ditaruh di kolom kiri selebar sepertiga layar dengan ukuran
    220px. Di ruang kelas, QR itu dipandang dari bangku paling belakang lewat
    proyektor atau layar laptop -- 220px di pojok kiri terlalu kecil untuk
    discan dari jarak beberapa meter, dan siswa harus berkerumun maju ke depan.

    Sekarang QR menempati kartu selebar halaman di bagian atas, berada tepat di
    tengah, dan ukurannya lebih dari dua kali lipat. Daftar kehadiran turun ke
    bawah selebar penuh -- justru lebih lapang daripada sebelumnya, karena
    tidak lagi berbagi baris dengan panel QR.
--}}

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        {{-- Lihat catatan di gurumapel/dashboard.blade.php: kelas yang sudah
             dihapus membuat $sesi->kelas null, dan tanpa pengaman ini seluruh
             halaman gagal dengan error 500. --}}
        <h3 class="fw-bold mb-1">{{ $sesi->kelas?->nama_kelas ?? '(kelas dihapus)' }} &middot; {{ $sesi->mapel }}</h3>
        <small class="text-muted">{{ $sesi->jam_ke }} &middot; {{ \Carbon\Carbon::parse($sesi->tanggal)->format('d-m-Y') }}</small>
    </div>
    <a href="{{ route('gurumapel.absensi.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i> Kembali
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    {{ session('success') }}
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    {{ session('error') }}
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- ============ PANEL QR ============ --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body text-center py-4">

        {{-- PERBAIKAN BUG (3 Agustus 2026): penyembunyian QR memakai class
             .d-none, BUKAN style="display:none" inline. Wadahnya memakai
             .d-flex yang isinya "display: flex !important", dan aturan
             ber-!important selalu menang atas style inline biasa -- dulu QR
             tidak pernah benar-benar hilang saat sesi berakhir, sehingga
             siswa masih bisa memotretnya. --}}
        <div id="qr-wrapper" class="justify-content-center {{ $sesi->isExpired() ? 'd-none' : 'd-flex' }}">
            <div id="qrcode" class="qr-kotak"></div>
        </div>

        <div id="qr-expired-box" class="text-muted py-5 {{ $sesi->isExpired() ? '' : 'd-none' }}">
            <i class="fas fa-clock fa-3x mb-3 opacity-50"></i>
            <p class="mb-0 fs-5">Sesi absensi ini sudah berakhir.</p>
        </div>

        <div id="countdown" class="fs-2 fw-bold text-warning mt-3 mb-2 {{ $sesi->isExpired() ? 'd-none' : '' }}"></div>

        <p class="text-muted mb-3">
            Minta siswa membuka kamera HP dan memindai QR di atas.
            Siswa harus sudah masuk ke aplikasi SIM BK lebih dulu.
        </p>

        @if(!$sesi->isExpired())
        <form action="{{ route('gurumapel.absensi.tutup', $sesi->id) }}" method="POST"
              onsubmit="return confirm('Tutup sesi absensi ini sekarang?');">
            @csrf
            <button type="submit" class="btn btn-outline-danger">
                <i class="fas fa-stop-circle me-1"></i> Tutup Sesi Sekarang
            </button>
        </form>
        @endif
    </div>
</div>

{{-- ============ DAFTAR KEHADIRAN ============ --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-bold">Status Kehadiran Siswa</span>
        <span class="text-muted small" id="last-updated">memuat...</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table mb-0 align-middle" id="roster-table">
            <thead class="table-light">
                <tr>
                    <th>NISN</th>
                    <th>Nama</th>
                    <th>Status</th>
                    <th>Jam Scan</th>
                    <th>Keterangan &amp; Bukti</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="roster-body">
                @foreach($roster as $r)
                <tr data-siswa-id="{{ $r['siswa_id'] }}">
                    <td>{{ $r['nisn'] }}</td>
                    <td>{{ $r['nama'] }}</td>
                    <td class="status-cell">
                        @php
                            $badge = match($r['status']) {
                                'Hadir' => 'bg-success',
                                'Izin' => 'bg-info text-dark',
                                'Sakit' => 'bg-warning text-dark',
                                'Alpha' => 'bg-danger',
                                default => 'bg-secondary',
                            };
                        @endphp
                        <span class="badge {{ $badge }}">{{ $r['status'] }}</span>
                        @if($r['otomatis'])
                            <span class="badge bg-light text-secondary border d-block mt-1"
                                  title="Mengikuti keterangan yang sudah dicatat pada jam pelajaran lain hari ini. Anda tetap bisa mengubahnya.">
                                otomatis
                            </span>
                        @endif
                    </td>
                    <td class="waktu-cell">{{ $r['waktu_scan'] ?? '-' }}</td>
                    <td class="bukti-cell">
                        @if($r['keterangan'])
                            <div class="small text-muted">{{ $r['keterangan'] }}</div>
                        @endif
                        @if($r['bukti_url'])
                            <a href="{{ $r['bukti_url'] }}" target="_blank" rel="noopener"
                               class="badge bg-primary text-decoration-none mt-1">
                                <i class="fas {{ $r['bukti_gambar'] ? 'fa-image' : 'fa-file-pdf' }} me-1"></i>Lihat bukti
                            </a>
                        @endif
                        @if(! $r['keterangan'] && ! $r['bukti_url'])
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-end aksi-cell">
                        @if($r['status'] !== 'Hadir')
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalManual"
                            data-siswa-id="{{ $r['siswa_id'] }}" data-siswa-nama="{{ $r['nama'] }}">
                            Izin/Sakit
                        </button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    <div class="card-footer bg-white text-muted small">
        <i class="fas fa-circle-info me-1"></i>
        Siswa bertanda <span class="badge bg-light text-secondary border">otomatis</span>
        sudah dinyatakan Izin/Sakit pada jam pelajaran lain hari ini beserta buktinya,
        jadi Anda tidak perlu mencatatnya lagi. Kalau ternyata siswanya masuk, cukup
        minta dia memindai QR — statusnya berubah jadi Hadir.
    </div>
</div>

<!-- Modal Manual Izin/Sakit -->
<div class="modal fade" id="modalManual" tabindex="-1">
    <div class="modal-dialog">
        {{-- enctype WAJIB ada. Tanpa itu berkas tidak ikut terkirim dan
             unggahannya gagal diam-diam: form tetap tersimpan, tapi kolom
             buktinya kosong tanpa pesan error apa pun. --}}
        <form action="{{ route('gurumapel.absensi.manual', $sesi->id) }}" method="POST"
              enctype="multipart/form-data" class="modal-content">
            @csrf
            <input type="hidden" name="siswa_id" id="manual_siswa_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Input Izin / Sakit — <span id="manual_siswa_nama"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="Izin">Izin</option>
                        <option value="Sakit">Sakit</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Keterangan (opsional)</label>
                    <textarea name="keterangan" class="form-control" rows="2"
                              placeholder="Contoh: demam, ada surat dokter"></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold">Bukti Keterangan (opsional)</label>
                    <input type="file" name="bukti" class="form-control"
                           accept=".jpg,.jpeg,.png,.webp,.pdf">
                    <div class="form-text">
                        Foto surat dokter, tangkapan layar pesan orang tua, atau surat dalam
                        bentuk PDF. Maksimal 4 MB. Bukti ini bisa dilihat Guru BK dan wali kelas.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn text-white fw-bold" style="background:#b45309;">Simpan</button>
            </div>
        </form>
    </div>
</div>

<style>
    /* QR dibuat sebesar mungkin tapi tetap muat di layar HP. Gambar hasil
       qrcode.js dipaksa mengikuti lebar wadahnya supaya tidak meluber. */
    .qr-kotak {
        padding: 1rem;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        display: inline-block;
        line-height: 0;
    }
    .qr-kotak img,
    .qr-kotak canvas {
        width: 100% !important;
        height: auto !important;
        max-width: 340px;
    }
</style>

@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    const scanUrl = @json($scanUrl);
    const statusUrl = @json(route('gurumapel.absensi.status', $sesi->id));
    const waktuExpired = new Date(@json($sesi->waktu_expired->toIso8601String()));
    let alreadyExpired = @json($sesi->isExpired());

    // QR digambar di sisi browser, jadi tidak perlu memasang paket QR di server.
    // Ukurannya dinaikkan dari 220 ke 340 supaya terbaca dari bangku belakang;
    // CSS di atas yang menjaganya tetap muat di layar sempit.
    if (!alreadyExpired) {
        new QRCode(document.getElementById('qrcode'), {
            text: scanUrl,
            width: 340,
            height: 340,
        });
    }

    /**
     * Sembunyikan QR & hitung mundur, tampilkan kotak "sesi berakhir".
     *
     * Memakai class .d-none, BUKAN style.display -- lihat catatan di bagian
     * HTML di atas. Isi QR juga dikosongkan supaya gambarnya benar-benar
     * lenyap dari halaman, bukan sekadar tidak terlihat (kalau hanya
     * disembunyikan, gambarnya masih bisa ditemukan lewat Inspect Element).
     */
    function tandaiKedaluwarsa() {
        if (alreadyExpired) return;
        alreadyExpired = true;

        const wrapper = document.getElementById('qrcode');
        if (wrapper) wrapper.innerHTML = '';

        const qrBox = document.getElementById('qr-wrapper');
        if (qrBox) { qrBox.classList.remove('d-flex'); qrBox.classList.add('d-none'); }

        const hitung = document.getElementById('countdown');
        if (hitung) hitung.classList.add('d-none');

        const kotakHabis = document.getElementById('qr-expired-box');
        if (kotakHabis) kotakHabis.classList.remove('d-none');
    }

    // Countdown timer
    function tickCountdown() {
        const el = document.getElementById('countdown');
        if (!el || alreadyExpired) return;

        const now = new Date();
        const diff = Math.max(0, Math.floor((waktuExpired - now) / 1000));
        const m = String(Math.floor(diff / 60)).padStart(2, '0');
        const s = String(diff % 60).padStart(2, '0');
        el.textContent = m + ':' + s + ' tersisa';

        if (diff <= 0) {
            tandaiKedaluwarsa();
        }
    }
    setInterval(tickCountdown, 1000);
    tickCountdown();

    /**
     * Bangun ulang sel "Keterangan & Bukti" untuk satu baris.
     *
     * Semuanya dirakit lewat createElement + textContent, tidak sekali pun
     * memakai innerHTML dengan nilai dari server. Keterangan diketik manusia
     * dan bisa saja memuat tag HTML; kalau disisipkan mentah, isinya akan
     * dieksekusi di halaman guru.
     */
    function isiSelBukti(cell, r) {
        cell.replaceChildren();

        if (r.keterangan) {
            const ket = document.createElement('div');
            ket.className = 'small text-muted';
            ket.textContent = r.keterangan;
            cell.appendChild(ket);
        }

        if (r.bukti_url) {
            const tautan = document.createElement('a');
            tautan.href = r.bukti_url;
            tautan.target = '_blank';
            tautan.rel = 'noopener';
            tautan.className = 'badge bg-primary text-decoration-none mt-1';
            tautan.textContent = 'Lihat bukti';
            cell.appendChild(tautan);
        }

        if (!r.keterangan && !r.bukti_url) {
            const kosong = document.createElement('span');
            kosong.className = 'text-muted';
            kosong.textContent = '-';
            cell.appendChild(kosong);
        }
    }

    // Poll status kehadiran tiap 5 detik (live update tanpa reload)
    function refreshRoster() {
        fetch(statusUrl)
            .then(res => res.json())
            .then(data => {
                // PERBAIKAN (3 Agustus 2026): jangan hanya mengandalkan
                // hitung mundur di sisi browser. Jam di HP/laptop guru bisa
                // saja meleset beberapa menit, dan kalau kelebihan, QR masih
                // tampil padahal server sudah menolaknya. Endpoint status ini
                // mengirim is_expired hasil perhitungan SERVER -- itulah yang
                // dijadikan patokan akhir.
                if (data.is_expired) {
                    tandaiKedaluwarsa();
                }

                data.roster.forEach(r => {
                    const row = document.querySelector(`#roster-body tr[data-siswa-id="${r.siswa_id}"]`);
                    if (!row) return;

                    const badgeMap = {
                        'Hadir': 'bg-success',
                        'Izin': 'bg-info text-dark',
                        'Sakit': 'bg-warning text-dark',
                        'Alpha': 'bg-danger',
                        'Belum Absen': 'bg-secondary',
                    };

                    const statusCell = row.querySelector('.status-cell');
                    const badge = document.createElement('span');
                    badge.className = 'badge ' + (badgeMap[r.status] ?? 'bg-secondary');
                    badge.textContent = r.status;
                    statusCell.replaceChildren(badge);

                    if (r.otomatis) {
                        const tanda = document.createElement('span');
                        tanda.className = 'badge bg-light text-secondary border d-block mt-1';
                        tanda.title = 'Mengikuti keterangan yang sudah dicatat pada jam pelajaran lain hari ini. Anda tetap bisa mengubahnya.';
                        tanda.textContent = 'otomatis';
                        statusCell.appendChild(tanda);
                    }

                    row.querySelector('.waktu-cell').textContent = r.waktu_scan ?? '-';

                    isiSelBukti(row.querySelector('.bukti-cell'), r);

                    // Tombol dibuang HANYA kalau siswa sudah Hadir. Untuk Alpha,
                    // Izin, dan Sakit tombolnya dipertahankan -- surat dokter
                    // sering baru datang keesokan harinya, dan guru harus tetap
                    // bisa membetulkan status sekaligus melampirkan buktinya.
                    const btn = row.querySelector('button[data-bs-target="#modalManual"]');
                    if (btn && r.status === 'Hadir') {
                        btn.remove();
                    }
                });

                document.getElementById('last-updated').textContent =
                    'diperbarui ' + new Date().toLocaleTimeString('id-ID');
            })
            .catch(() => {});
    }
    setInterval(refreshRoster, 5000);
    refreshRoster();

    // Isi modal manual dengan siswa yang dipilih
    document.getElementById('modalManual').addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        document.getElementById('manual_siswa_id').value = button.getAttribute('data-siswa-id');
        document.getElementById('manual_siswa_nama').textContent = button.getAttribute('data-siswa-nama');
    });
</script>
@endsection
