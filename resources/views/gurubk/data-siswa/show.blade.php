@extends('layouts.guru')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

    .ds-page { font-family: 'Plus Jakarta Sans', sans-serif; color: #263238; }
    .ds-page .ds-serif { font-family: 'Fraunces', serif; }

    .ds-back-link {
        display: inline-flex; align-items: center; gap: 0.4rem; color: #6B7A73;
        font-size: 0.85rem; font-weight: 600; text-decoration: none; margin-bottom: 1rem;
        transition: gap .15s ease, color .15s ease;
    }
    .ds-back-link:hover { color: #1F4B43; gap: .6rem; }

    .ds-eyebrow {
        color: #7C8A86; font-size: 0.78rem; letter-spacing: 1.6px; text-transform: uppercase;
        font-weight: 700; margin-bottom: 0.35rem;
    }

    .ds-detail-head {
        display: flex; align-items: center; gap: 1rem; margin-bottom: 1.75rem; flex-wrap: wrap;
        opacity: 0; animation: dsFadeUp .45s ease both;
    }
    .ds-detail-avatar {
        width: 62px; height: 62px; border-radius: 50%; background: #E9EFF5; color: #5B7FA6;
        display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.5rem; flex-shrink: 0;
        transition: transform .2s ease;
    }
    .ds-detail-head:hover .ds-detail-avatar { transform: scale(1.05) rotate(-3deg); }
    .ds-detail-head h3 { font-weight: 600; color: #1B2B27; margin-bottom: 0.1rem; }
    .ds-detail-head p { color: #8A968F; margin-bottom: 0; font-size: 0.88rem; }

    @keyframes dsFadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .ds-card {
        background: #fff; border: 1px solid #E7E4DA; border-radius: 18px; overflow: hidden;
        opacity: 0; animation: dsFadeUp .45s ease both;
    }
    .row.g-3 > div:nth-child(1) .ds-card { animation-delay: .08s; }
    .row.g-3 > div:nth-child(2) .ds-card { animation-delay: .16s; }

    .ds-card-head {
        padding: 1.1rem 1.4rem; border-bottom: 1px solid #F2F0E9; font-weight: 700; color: #1B2B27;
        display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap;
    }
    .ds-card-head i { color: #1F4B43; margin-right: 0.5rem; }
    .ds-card-body { padding: 1.4rem; }

    .ds-info-row { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: 0.6rem 0; border-bottom: 1px dashed #F0EEE7; font-size: 0.9rem; }
    .ds-info-row:last-child { border-bottom: none; }
    .ds-info-label { color: #8A968F; font-weight: 600; }
    .ds-info-value { color: #263238; font-weight: 700; text-align: right; display: flex; align-items: center; gap: .4rem; justify-content: flex-end; }
    .ds-info-value a { color: #1F8A5A; text-decoration: none; font-weight: 700; }
    .ds-info-value a:hover { text-decoration: underline; }

    .ds-copy-btn {
        border: none; background: #F1F1EE; color: #6B7A73; width: 26px; height: 26px; border-radius: 8px;
        display: inline-flex; align-items: center; justify-content: center; font-size: .75rem; flex-shrink: 0;
        transition: background .15s ease, color .15s ease, transform .15s ease; position: relative;
    }
    .ds-copy-btn:hover { background: #E6EEEC; color: #1F4B43; }
    .ds-copy-btn:active { transform: scale(.9); }
    .ds-copy-btn.is-copied { background: #1F4B43; color: #fff; }
    .ds-copy-tip {
        position: absolute; bottom: 130%; left: 50%; transform: translateX(-50%) translateY(4px);
        background: #1B2B27; color: #fff; font-size: .68rem; font-weight: 600; padding: .2rem .5rem;
        border-radius: 6px; white-space: nowrap; opacity: 0; pointer-events: none; transition: all .15s ease;
    }
    .ds-copy-btn.is-copied .ds-copy-tip { opacity: 1; transform: translateX(-50%) translateY(0); }

    .ds-badge {
        display: inline-flex; align-items: center; gap: 0.35rem; border-radius: 999px;
        padding: 0.35rem 0.85rem; font-size: 0.78rem; font-weight: 700;
    }
    .ds-badge i { font-size: 0.65rem; }
    .ds-badge--verified { background: #E6EEEC; color: #1F4B43; }
    .ds-badge--pending  { background: #F5EEE0; color: #8A6620; }

    .ds-btn-koreksi {
        background: #fff; border: 1px solid #D8D4C8; color: #6B7A73; border-radius: 10px;
        padding: 0.35rem 0.85rem; font-size: 0.78rem; font-weight: 700; transition: all .15s ease;
    }
    .ds-btn-koreksi:hover { background: #FAF9F5; color: #1F4B43; border-color: #1F4B43; transform: translateY(-1px); }

    .ds-empty-note {
        background: #FAF9F5; border: 1px dashed #E7E4DA; border-radius: 14px;
        padding: 2rem 1rem; text-align: center; color: #8A968F; font-size: 0.9rem;
    }
    .ds-empty-note i { font-size: 1.8rem; color: #C7D1CC; display: block; margin-bottom: 0.5rem; }

    /* ---------- Stepper verifikasi (INTERAKTIF, 30 Juli 2026) ---------- */
    .ds-stepper { display: flex; align-items: flex-start; margin: .25rem 0 1.5rem; }
    .ds-step { flex: 1; text-align: center; position: relative; }
    .ds-step-circle {
        width: 34px; height: 34px; border-radius: 50%; background: #F1F1EE; color: #8A968F;
        display: flex; align-items: center; justify-content: center; font-size: .85rem; font-weight: 700;
        margin: 0 auto .4rem; position: relative; z-index: 2; transition: all .25s ease;
        border: 2px solid #F1F1EE;
    }
    .ds-step-line {
        position: absolute; top: 17px; left: -50%; width: 100%; height: 2px; background: #E7E4DA; z-index: 1;
        transition: background-color .25s ease;
    }
    .ds-step:first-child .ds-step-line { display: none; }
    .ds-step-label { font-size: .72rem; font-weight: 700; color: #B9C2BD; transition: color .25s ease; }

    .ds-step.is-done .ds-step-circle { background: #1F4B43; color: #fff; border-color: #1F4B43; }
    .ds-step.is-done .ds-step-line { background: #1F4B43; }
    .ds-step.is-done .ds-step-label { color: #1F4B43; }

    .ds-step.is-current .ds-step-circle { background: #fff; color: #8A6620; border-color: #8A6620; box-shadow: 0 0 0 4px rgba(138,102,32,.12); animation: dsPulse 1.8s ease infinite; }
    .ds-step.is-current .ds-step-label { color: #8A6620; }

    @keyframes dsPulse {
        0%, 100% { box-shadow: 0 0 0 4px rgba(138,102,32,.12); }
        50% { box-shadow: 0 0 0 7px rgba(138,102,32,.05); }
    }

    .ds-action-note { font-size: 0.82rem; color: #8A968F; text-align: center; margin-bottom: 0.9rem; }

    .ds-modal-icon {
        width: 52px; height: 52px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; margin: 0 auto 1rem;
    }
</style>

<div class="container py-4 ds-page">
    <a href="{{ route('gurubk.data-siswa.index') }}" class="ds-back-link">
        <i class="bi bi-arrow-left"></i> Kembali ke Daftar Siswa
    </a>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- PERBAIKAN BUG (5 Agustus 2026).
         Sebelum ini halaman HANYA menampilkan session('success'). Akibatnya
         setiap kegagalan -- error validasi maupun session('error') yang
         dikirim controller -- hilang tanpa jejak: Guru BK menekan tombol
         "Verifikasi", halaman dimuat ulang, dan tampilannya PERSIS SAMA
         seperti sebelumnya. Tidak ada yang menandakan permintaannya ditolak,
         sehingga tombolnya terkesan mati padahal servernya menjawab.
         Dua blok di bawah membuat kegagalan itu kelihatan. --}}
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger rounded-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <strong>Tindakan tidak diproses.</strong>
            <ul class="mb-0 mt-2 ps-3">
                @foreach($errors->all() as $pesanError)
                    <li>{{ $pesanError }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="ds-detail-head">
        <div class="ds-detail-avatar">{{ strtoupper(substr($siswa->user->name ?? '-', 0, 1)) }}</div>
        <div>
            <p class="ds-eyebrow mb-1">Detail Data Siswa</p>
            <h3 class="ds-serif">{{ $siswa->user->name ?? '-' }}</h3>
            <p>NISN {{ $siswa->nisn ?? '-' }}</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="ds-card h-100">
                <div class="ds-card-head"><i class="bi bi-person-badge"></i>Profil Siswa</div>
                <div class="ds-card-body">
                    <div class="ds-info-row">
                        <span class="ds-info-label">Nama Lengkap</span>
                        <span class="ds-info-value">{{ $siswa->user->name ?? '-' }}</span>
                    </div>
                    <div class="ds-info-row">
                        <span class="ds-info-label">NISN</span>
                        <span class="ds-info-value">{{ $siswa->nisn ?? '-' }}</span>
                    </div>
                    <div class="ds-info-row">
                        <span class="ds-info-label">Tanggal Lahir</span>
                        <span class="ds-info-value">{{ $siswa->tgl_lahir ?? '-' }}</span>
                    </div>
                    <div class="ds-info-row">
                        <span class="ds-info-label">Email</span>
                        <span class="ds-info-value">{{ $siswa->user->email ?? '-' }}</span>
                    </div>
                    <div class="ds-info-row">
                        <span class="ds-info-label">No HP Siswa</span>
                        <span class="ds-info-value">
                            {{ $siswa->no_hp_siswa ?? '-' }}
                            @if($siswa->no_hp_siswa)
                                <button type="button" class="ds-copy-btn" data-copy="{{ $siswa->no_hp_siswa }}" title="Salin nomor">
                                    <i class="bi bi-clipboard"></i>
                                    <span class="ds-copy-tip">Disalin!</span>
                                </button>
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="ds-card h-100">
                <div class="ds-card-head">
                    <span><i class="bi bi-people"></i>Data Orang Tua/Wali</span>
                    <div class="d-flex align-items-center gap-2">
                        @if($siswa->is_wa_verified === 1)
                            <span class="ds-badge ds-badge--verified"><i class="bi bi-check-circle-fill"></i> Terverifikasi</span>
                        @elseif($siswa->is_wa_verified === 0)
                            <span class="ds-badge ds-badge--pending"><i class="bi bi-hourglass-split"></i> Butuh Verifikasi</span>
                        @endif
                        {{-- FITUR BARU (30 Juli 2026): Guru BK bisa koreksi langsung
                             nama/No WA orang tua (mis. nomor lama sudah mati dan
                             siswa lapor langsung ke Guru BK), tanpa perlu menolak
                             data (yang akan menghapus semuanya). --}}
                        @if($siswa->is_wa_verified !== null)
                            <button type="button" class="ds-btn-koreksi" data-bs-toggle="modal" data-bs-target="#modalEditOrtu">
                                <i class="bi bi-pencil-square"></i> Koreksi
                            </button>
                        @endif
                    </div>
                </div>
                <div class="ds-card-body">

                    {{-- Stepper progres verifikasi -- murni visual, biar status
                         terasa seperti "proses" yang berjalan, bukan cuma teks. --}}
                    <div class="ds-stepper">
                        <div class="ds-step {{ $siswa->is_wa_verified !== null ? 'is-done' : 'is-current' }}">
                            <div class="ds-step-circle"><i class="bi bi-pencil-fill" style="font-size:.7rem;"></i></div>
                            <div class="ds-step-label">Diisi Siswa</div>
                        </div>
                        <div class="ds-step {{ $siswa->is_wa_verified === 1 ? 'is-done' : ($siswa->is_wa_verified === 0 ? 'is-current' : '') }}">
                            <div class="ds-step-line"></div>
                            <div class="ds-step-circle"><i class="bi bi-hourglass-split" style="font-size:.7rem;"></i></div>
                            <div class="ds-step-label">Verifikasi Guru BK</div>
                        </div>
                        <div class="ds-step {{ $siswa->is_wa_verified === 1 ? 'is-done' : '' }}">
                            <div class="ds-step-line"></div>
                            <div class="ds-step-circle"><i class="bi bi-check-lg" style="font-size:.8rem;"></i></div>
                            <div class="ds-step-label">Terverifikasi</div>
                        </div>
                    </div>

                    @if($siswa->is_wa_verified === null)
                        <div class="ds-empty-note">
                            <i class="bi bi-inbox"></i>
                            Siswa belum mengirimkan (atau sedang menginput ulang) data orang tua.
                        </div>
                    @else
                        <div class="ds-info-row">
                            <span class="ds-info-label">Nama Wali</span>
                            <span class="ds-info-value">{{ $siswa->nama_ortu }}</span>
                        </div>
                        <div class="ds-info-row mb-4">
                            <span class="ds-info-label">No WA Wali</span>
                            <span class="ds-info-value">
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $siswa->no_wa_ortu) }}" target="_blank">
                                    <i class="bi bi-whatsapp"></i> {{ $siswa->no_wa_ortu }}
                                </a>
                                <button type="button" class="ds-copy-btn" data-copy="{{ $siswa->no_wa_ortu }}" title="Salin nomor">
                                    <i class="bi bi-clipboard"></i>
                                    <span class="ds-copy-tip">Disalin!</span>
                                </button>
                            </span>
                        </div>

                        @if($siswa->is_wa_verified === 0)
                            <hr class="my-3" style="border-color:#F0EEE7;">
                            <p class="ds-action-note">Pastikan nama dan nomor WhatsApp sudah sesuai sebelum melakukan tindakan.</p>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-danger w-50 py-2 rounded-3 fw-bold" data-bs-toggle="modal" data-bs-target="#modalTolakData">
                                    <i class="bi bi-x-circle me-1"></i>Tolak Data
                                </button>
                                <button type="button" class="btn btn-success w-50 py-2 rounded-3 fw-bold" data-bs-toggle="modal" data-bs-target="#modalVerifikasiData">
                                    <i class="bi bi-check-circle me-1"></i>Verifikasi
                                </button>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Koreksi Data Orang Tua (FITUR BARU, 30 Juli 2026): dipakai Guru
         BK untuk mengoreksi langsung nama/No WA orang tua tanpa menghapus
         status verifikasi -- misalnya nomor WA lama sudah tidak aktif dan
         siswa melaporkannya langsung ke Guru BK. --}}
    @if($siswa->is_wa_verified !== null)
    <div class="modal fade" id="modalEditOrtu" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('gurubk.data-siswa.update-ortu', $siswa->id) }}" method="POST" class="modal-content border-0 shadow rounded-4">
                @csrf
                @method('PUT')
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold ds-serif"><i class="bi bi-pencil-square me-2"></i>Koreksi Data Orang Tua</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">Gunakan ini kalau ada kesalahan data atau nomor WA lama sudah tidak aktif, dan siswa sudah melaporkannya langsung ke Anda. Status verifikasi yang sudah ada TIDAK akan direset.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Orang Tua/Wali</label>
                        <input type="text" name="nama_ortu" class="form-control" value="{{ $siswa->nama_ortu }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold">No WA Orang Tua/Wali</label>
                        <input type="text" name="no_wa_ortu" class="form-control" value="{{ $siswa->no_wa_ortu }}" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Simpan Koreksi</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Modal konfirmasi Verifikasi/Tolak (INTERAKTIF, 30 Juli 2026): dulu
         pakai confirm() bawaan browser yang polos & tidak konsisten dengan
         gaya halaman. Sekarang pakai modal Bootstrap bergaya sama. --}}
    @if($siswa->is_wa_verified === 0)
    <div class="modal fade" id="modalVerifikasiData" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('gurubk.data-siswa.verify', $siswa->id) }}" method="POST" class="modal-content border-0 shadow rounded-4">
                @csrf
                {{-- PERBAIKAN BUG (5 Agustus 2026).
                     Dulu tindakannya dititipkan pada atribut tombol kirim:
                     <button type="submit" name="action" value="approve">.
                     Pasangan name/value milik tombol HANYA ikut terkirim kalau
                     browser menganggap form dikirim OLEH tombol itu. Begitu ada
                     yang mengirim form dengan cara lain -- skrip, ekstensi
                     browser, autofill, atau tombol Enter -- nilainya hilang,
                     form tetap sampai ke server tanpa 'action', dan permintaan
                     ditolak. Itulah yang terjadi: server menerima POST tapi
                     tidak tahu tindakan apa yang diminta.

                     Input tersembunyi SELALU ikut terkirim, apa pun cara form
                     dikirim. Jadi cara ini menghilangkan seluruh kelas masalah
                     itu, bukan cuma satu gejalanya. --}}
                <input type="hidden" name="action" value="approve">
                <div class="modal-body text-center pt-4">
                    <div class="ds-modal-icon" style="background:#E6EEEC;color:#1F4B43;"><i class="bi bi-check-circle"></i></div>
                    <h5 class="fw-bold ds-serif mb-2">Verifikasi data ini?</h5>
                    <p class="text-muted small mb-0">Nama <strong>{{ $siswa->nama_ortu }}</strong> dan nomor WA <strong>{{ $siswa->no_wa_ortu }}</strong> akan ditandai sebagai terverifikasi.</p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-3 px-4 fw-bold">
                        <i class="bi bi-check-circle me-1"></i>Ya, Verifikasi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalTolakData" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('gurubk.data-siswa.verify', $siswa->id) }}" method="POST" class="modal-content border-0 shadow rounded-4">
                @csrf
                {{-- Lihat penjelasan di modal Verifikasi di atas soal kenapa
                     tindakan dikirim lewat input tersembunyi, bukan lewat
                     atribut value milik tombol kirim. --}}
                <input type="hidden" name="action" value="reject">
                <div class="modal-body text-center pt-4">
                    <div class="ds-modal-icon" style="background:#FDECEC;color:#B91C1C;"><i class="bi bi-x-circle"></i></div>
                    <h5 class="fw-bold ds-serif mb-2">Tolak data ini?</h5>
                    <p class="text-muted small mb-0">Data nama & nomor WA orang tua akan <strong>dihapus</strong> dan siswa harus mengisi ulang dari awal.</p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-3 px-4 fw-bold">
                        <i class="bi bi-x-circle me-1"></i>Ya, Tolak Data
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.ds-copy-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var value = btn.getAttribute('data-copy');
                if (!value) return;

                var markCopied = function () {
                    btn.classList.add('is-copied');
                    var icon = btn.querySelector('i.bi-clipboard, i.bi-clipboard-check');
                    if (icon) icon.className = 'bi bi-clipboard-check';
                    setTimeout(function () {
                        btn.classList.remove('is-copied');
                        if (icon) icon.className = 'bi bi-clipboard';
                    }, 1500);
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(value).then(markCopied).catch(function () {
                        fallbackCopy(value);
                        markCopied();
                    });
                } else {
                    fallbackCopy(value);
                    markCopied();
                }
            });
        });

        function fallbackCopy(text) {
            var tmp = document.createElement('textarea');
            tmp.value = text;
            tmp.style.position = 'fixed';
            tmp.style.opacity = '0';
            document.body.appendChild(tmp);
            tmp.select();
            try { document.execCommand('copy'); } catch (e) { /* abaikan */ }
            document.body.removeChild(tmp);
        }
    });
</script>
@endpush
@endsection
