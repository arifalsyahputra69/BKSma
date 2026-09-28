@php
    $layout = 'layouts.app';
    if (Auth::user()->hasRole('Guru BK') && view()->exists('layouts.guru')) {
        $layout = 'layouts.guru';
    } elseif (Auth::user()->hasRole('Siswa') && view()->exists('layouts.siswa')) {
        $layout = 'layouts.siswa';
    } elseif (Auth::user()->hasRole('Wali Kelas') && view()->exists('layouts.walikelas')) {
        $layout = 'layouts.walikelas';
    }

    // Ambil relasi data siswa
    $siswaData = Auth::user()->siswa;

    // Hitung mundur 3 bulan untuk UI (Email & No HP)
    $canUpdateContact = true;
    $nextUpdate = null;
    if($siswaData && $siswaData->waktu_update_kontak) {
        $lastUpdate = \Carbon\Carbon::parse($siswaData->waktu_update_kontak);
        if(now()->lessThan($lastUpdate->copy()->addMonths(3))) {
            $canUpdateContact = false;
            $nextUpdate = $lastUpdate->copy()->addMonths(3)->format('d-m-Y');
        }
    }
@endphp

@extends($layout)

@section('content')
<div class="container py-4 pf-page">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-7">

            {{-- ============== BANNER PROFIL ============== --}}
            <div class="pf-banner rounded-4 p-4 mb-4 position-relative overflow-hidden">
                <div class="d-flex align-items-center gap-3 position-relative" style="z-index:2;">
                    <div class="pf-avatar-lg">
                        @if(Auth::user()->foto)
                            <img id="pfAvatarImg" src="{{ asset('storage/profil/' . Auth::user()->foto) }}" alt="Foto profil">
                        @else
                            <div id="pfAvatarFallback" class="pf-avatar-fallback">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                            <img id="pfAvatarImg" src="" alt="Foto profil" style="display:none;">
                        @endif
                    </div>
                    <div>
                        <h4 class="fw-bold text-white mb-0">{{ Auth::user()->name }}</h4>
                        <span class="badge pf-role-badge mt-1"><i class="fas fa-user-graduate me-1"></i>{{ Auth::user()->roles->pluck('name')->first() }}</span>
                    </div>
                </div>
                <i class="fas fa-id-card pf-banner-deco"></i>
            </div>

            @if(session('success'))
                <div class="alert alert-success small rounded-3 text-center">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger small rounded-3 text-center">{{ session('error') }}</div>
            @endif

            {{-- ============== FOTO PROFIL ============== --}}
            <div class="card pf-card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="pf-card-title mb-3"><i class="fas fa-camera"></i> Foto Profil</div>
                    <div id="pfAlertFoto" class="pf-alert" style="display:none;"></div>

                    <form id="formFoto" action="{{ route('profil.foto.update') }}" method="POST" enctype="multipart/form-data" data-ajax-form class="d-flex flex-wrap align-items-center gap-3">
                        @csrf
                        <div class="input-group input-group-sm" style="max-width: 360px;">
                            <input type="file" id="pfFotoInput" class="form-control" name="foto" accept="image/*" required>
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="fas fa-upload me-1"></i>Upload
                            </button>
                        </div>
                        <small class="text-muted">Format JPG/PNG, maksimal 2MB.</small>
                    </form>
                </div>
            </div>

            {{-- ============== INFORMASI AKUN ============== --}}
            <div class="card pf-card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="pf-card-title mb-3"><i class="fas fa-id-badge"></i> Informasi Akun</div>
                    <div id="pfAlertInfo" class="pf-alert" style="display:none;"></div>

                    <form id="formInfo" action="{{ route('profil.info.update') }}" method="POST" data-ajax-form>
                        @csrf

                        <div class="mb-3">
                            <label class="pf-field-label">Nama Lengkap</label>
                            <input type="text" class="form-control bg-light text-muted" value="{{ Auth::user()->name }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="pf-field-label">NISN</label>
                            <input type="text" class="form-control bg-light text-muted" value="{{ $siswaData->nisn ?? '-' }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="pf-field-label">Tanggal Lahir</label>
                            <input type="date" name="tgl_lahir" id="pfTglLahir" class="form-control {{ ($siswaData && $siswaData->tgl_lahir) ? 'bg-light text-muted' : '' }}"
                                value="{{ $siswaData->tgl_lahir ?? '' }}"
                                {{ ($siswaData && $siswaData->tgl_lahir) ? 'readonly' : '' }}>
                            <div class="invalid-feedback" data-field-error="tgl_lahir"></div>
                        </div>

                        <div class="mb-3">
                            <label class="pf-field-label">Email</label>
                            <input type="email" name="email" id="pfEmail" class="form-control {{ !$canUpdateContact ? 'bg-light text-muted' : '' }}"
                                value="{{ Auth::user()->email }}"
                                {{ !$canUpdateContact ? 'readonly' : 'required' }}>
                            <div class="invalid-feedback" data-field-error="email"></div>
                        </div>

                        <div class="mb-2">
                            <label class="pf-field-label">No HP Siswa</label>
                            <input type="text" name="no_hp_siswa" id="pfNoHp" class="form-control {{ !$canUpdateContact ? 'bg-light text-muted' : '' }}"
                                value="{{ $siswaData->no_hp_siswa ?? '' }}"
                                placeholder="Contoh: 08123456789"
                                {{ !$canUpdateContact ? 'readonly' : '' }}>
                            <div class="invalid-feedback" data-field-error="no_hp_siswa"></div>
                        </div>

                        <small id="pfCooldownNote" class="text-danger fw-bold d-block mb-3" style="font-size: 11px; {{ $canUpdateContact ? 'display:none;' : '' }}">
                            *Email &amp; No HP baru bisa diubah lagi setelah: <span id="pfCooldownDate">{{ $nextUpdate }}</span>
                        </small>

                        <button type="submit" class="btn btn-primary w-100 rounded-3 py-2 fw-bold">Update Info Siswa</button>
                    </form>
                </div>
            </div>

            {{-- ============== DATA ORANG TUA ============== --}}
            <div class="card pf-card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="pf-card-title mb-3"><i class="fas fa-user-friends"></i> Data Orang Tua</div>
                    <div id="pfAlertOrtu" class="pf-alert" style="display:none;"></div>

                    <div id="pfOrtuStatus">
                        @if($siswaData && $siswaData->is_wa_verified === 1)
                            <div class="alert alert-success py-2 mb-4 text-center fw-bold border-0 shadow-sm">
                                <i class="fas fa-check-circle me-1"></i>Sudah Terverifikasi
                            </div>
                        @elseif($siswaData && $siswaData->is_wa_verified === 0)
                            <div class="alert alert-warning py-2 mb-4 text-center fw-bold border-0 shadow-sm text-dark">
                                <i class="fas fa-hourglass-half me-1"></i>Belum Terverifikasi (Menunggu ACC Guru BK)
                            </div>
                        @else
                            <div class="alert alert-info small py-2 mb-4 text-center">
                                <i class="fas fa-info-circle me-1"></i>Masukkan data orang tua/wali dengan benar. Setelah dikirim, data akan otomatis terkunci.
                            </div>
                        @endif
                    </div>

                    <form id="formOrtu" action="{{ route('profil.parent.submit') }}" method="POST" data-ajax-form>
                        @csrf

                        <div class="mb-3">
                            <label class="pf-field-label">Nama Orang Tua</label>
                            <input type="text" name="nama_ortu" id="pfNamaOrtu" class="form-control {{ ($siswaData && $siswaData->is_wa_verified !== null) ? 'bg-light text-muted' : '' }}"
                                value="{{ $siswaData->nama_ortu ?? '' }}"
                                {{ ($siswaData && $siswaData->is_wa_verified !== null) ? 'readonly' : 'required' }}>
                            <div class="invalid-feedback" data-field-error="nama_ortu"></div>
                        </div>

                        <div class="mb-4">
                            <label class="pf-field-label">No WA / HP Orang Tua</label>
                            <input type="text" name="no_wa_ortu" id="pfNoWaOrtu" class="form-control {{ ($siswaData && $siswaData->is_wa_verified !== null) ? 'bg-light text-muted' : '' }}"
                                value="{{ $siswaData->no_wa_ortu ?? '' }}"
                                {{ ($siswaData && $siswaData->is_wa_verified !== null) ? 'readonly' : 'required' }}>
                            <div class="invalid-feedback" data-field-error="no_wa_ortu"></div>
                        </div>

                        {{-- BUGFIX (30 Juli 2026): tombol ini HARUS benar-benar tidak
                             ada di DOM (bukan cuma disembunyikan lewat CSS) begitu
                             data orang tua sudah pernah dikirim (pending/terverifikasi).
                             Kalau cuma disembunyikan via style=display:none, tombol
                             itu tetap jadi "submit button" aktif milik form ini --
                             siswa masih bisa memicu submit ulang (mis. tekan Enter di
                             field), menimpa data yang sudah terverifikasi. --}}
                        @if(!$siswaData || $siswaData->is_wa_verified === null)
                        <button type="submit" id="pfBtnOrtu" class="btn btn-success w-100 rounded-3 py-2 fw-bold">
                            Kirim Data Untuk Diverifikasi
                        </button>
                        @endif
                    </form>
                </div>
            </div>

            {{-- ============== UBAH KATA SANDI ============== --}}
            <div class="card pf-card shadow-sm border-0 rounded-4 mb-5">
                <div class="card-body p-4">
                    <div class="pf-card-title mb-3"><i class="fas fa-lock"></i> Ubah Kata Sandi</div>
                    <div id="pfAlertPassword" class="pf-alert" style="display:none;"></div>

                    <form id="formPassword" action="{{ route('profil.password.update') }}" method="POST" data-ajax-form>
                        @csrf
                        <div class="mb-3">
                            <label class="pf-field-label">Password Lama</label>
                            <input type="password" name="password_lama" class="form-control" autocomplete="current-password" required>
                            <div class="invalid-feedback" data-field-error="password_lama"></div>
                        </div>
                        <div class="mb-3">
                            <label class="pf-field-label">Password Baru</label>
                            <input type="password" name="password_baru" class="form-control" autocomplete="new-password" required>
                            <div class="invalid-feedback" data-field-error="password_baru"></div>
                        </div>
                        <div class="mb-4">
                            <label class="pf-field-label">Konfirmasi Password Baru</label>
                            <input type="password" name="password_baru_confirmation" class="form-control" autocomplete="new-password" required>
                        </div>
                        <button type="submit" class="btn btn-dark w-100 rounded-3 py-2 fw-bold">Simpan Password Baru</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    .pf-banner { background: linear-gradient(135deg, var(--primary-color, #1f4b3f) 0%, var(--primary-dark, #14362d) 100%); }
    .pf-banner-deco { position: absolute; right: 1.25rem; top: 50%; transform: translateY(-50%); font-size: 5rem; color: rgba(255,255,255,.08); z-index: 1; pointer-events: none; }
    .pf-avatar-lg { width: 78px; height: 78px; border-radius: 50%; overflow: hidden; flex-shrink: 0; border: 3px solid rgba(255,255,255,.5); background: rgba(255,255,255,.15); }
    .pf-avatar-lg img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .pf-avatar-fallback { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 1.8rem; }
    .pf-role-badge { background: rgba(255,255,255,.18); color: #fff; font-weight: 600; padding: .4rem .75rem; border-radius: 999px; font-size: .75rem; }

    .pf-card-title { font-weight: 700; font-size: 1rem; color: var(--text-main, #1f2937); display: flex; align-items: center; gap: .5rem; }
    .pf-card-title i { color: var(--primary-color, #1f4b3f); width: 20px; text-align: center; }
    .pf-field-label { font-size: .8rem; font-weight: 600; color: var(--text-muted, #6b7280); margin-bottom: .25rem; display: block; }

    .pf-alert { border-radius: .75rem; padding: .6rem .9rem; font-size: .85rem; margin-bottom: 1rem; }
    .pf-alert-success { background: #e8f3ee; color: #14532d; border: 1px solid #bfe3cf; }
    .pf-alert-danger { background: #fdecec; color: #7f1d1d; border: 1px solid #f5c2c2; }

    .is-invalid { border-color: #dc3545 !important; }
    .invalid-feedback.d-block-manual { display: block !important; }

    .pf-page .form-control:disabled, .pf-page .form-control[readonly] { cursor: not-allowed; }
</style>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    function getToken(form) {
        var input = form.querySelector('input[name="_token"]');
        return input ? input.value : '';
    }

    function clearFieldErrors(form) {
        form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
        form.querySelectorAll('[data-field-error]').forEach(function (el) {
            el.textContent = '';
            el.classList.remove('d-block-manual');
        });
    }

    function showFieldErrors(form, errors) {
        if (!errors) return;
        Object.keys(errors).forEach(function (field) {
            var input = form.querySelector('[name="' + field + '"]');
            var feedback = form.querySelector('[data-field-error="' + field + '"]');
            var msg = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
            if (input) input.classList.add('is-invalid');
            if (feedback) {
                feedback.textContent = msg;
                feedback.classList.add('d-block-manual');
            }
        });
    }

    function showAlert(alertEl, type, message) {
        if (!alertEl) return;
        alertEl.className = 'pf-alert pf-alert-' + type;
        alertEl.textContent = message;
        alertEl.style.display = 'block';
        clearTimeout(alertEl._hideTimer);
        alertEl._hideTimer = setTimeout(function () {
            alertEl.style.display = 'none';
        }, 5000);
    }

    function setLoading(btn, loading) {
        if (!btn) return;
        btn.disabled = loading;
        btn.classList.toggle('btn-loading', loading);
    }

    /**
     * Submit sebuah <form data-ajax-form> lewat fetch, TANPA reload halaman
     * sama sekali -- ini kunci perbaikannya: form lain di halaman yang
     * belum sempat disubmit tidak akan pernah kehilangan isiannya, karena
     * halaman tidak pernah benar-benar reload.
     */
    function submitAjaxForm(form, alertEl, onSuccess) {
        var btn = form.querySelector('button[type="submit"]');
        clearFieldErrors(form);
        setLoading(btn, true);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getToken(form),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new FormData(form),
        })
        .then(function (res) {
            return res.json().then(function (data) { return { ok: res.ok, data: data }; });
        })
        .then(function (result) {
            setLoading(btn, false);
            if (result.ok) {
                showAlert(alertEl, 'success', result.data.message || 'Berhasil disimpan.');
                if (onSuccess) onSuccess(result.data);
            } else {
                showAlert(alertEl, 'danger', result.data.message || 'Terjadi kesalahan, silakan coba lagi.');
                showFieldErrors(form, result.data.errors);
            }
        })
        .catch(function () {
            setLoading(btn, false);
            showAlert(alertEl, 'danger', 'Gagal terhubung ke server. Periksa koneksi internet kamu.');
        });
    }

    // ===== Form Foto: preview instan + upload AJAX =====
    var formFoto = document.getElementById('formFoto');
    var fotoInput = document.getElementById('pfFotoInput');
    var avatarImg = document.getElementById('pfAvatarImg');
    var avatarFallback = document.getElementById('pfAvatarFallback');

    if (fotoInput) {
        fotoInput.addEventListener('change', function () {
            var file = fotoInput.files && fotoInput.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function (e) {
                avatarImg.src = e.target.result;
                avatarImg.style.display = 'block';
                if (avatarFallback) avatarFallback.style.display = 'none';
            };
            reader.readAsDataURL(file);
        });
    }

    if (formFoto) {
        formFoto.addEventListener('submit', function (e) {
            e.preventDefault();
            submitAjaxForm(formFoto, document.getElementById('pfAlertFoto'), function (data) {
                if (data.foto_url) {
                    avatarImg.src = data.foto_url;
                    avatarImg.style.display = 'block';
                    if (avatarFallback) avatarFallback.style.display = 'none';
                }
                formFoto.reset();
            });
        });
    }

    // ===== Form Informasi Akun =====
    var formInfo = document.getElementById('formInfo');
    if (formInfo) {
        formInfo.addEventListener('submit', function (e) {
            e.preventDefault();
            submitAjaxForm(formInfo, document.getElementById('pfAlertInfo'), function (data) {
                var emailInput = document.getElementById('pfEmail');
                var noHpInput = document.getElementById('pfNoHp');
                var tglInput = document.getElementById('pfTglLahir');
                var cooldownNote = document.getElementById('pfCooldownNote');
                var cooldownDate = document.getElementById('pfCooldownDate');

                if (data.tgl_lahir) {
                    tglInput.value = data.tgl_lahir;
                    tglInput.readOnly = true;
                    tglInput.classList.add('bg-light', 'text-muted');
                }

                if (data.can_update_contact === false) {
                    emailInput.readOnly = true;
                    noHpInput.readOnly = true;
                    emailInput.classList.add('bg-light', 'text-muted');
                    noHpInput.classList.add('bg-light', 'text-muted');
                    if (cooldownDate) cooldownDate.textContent = data.next_update || '';
                    if (cooldownNote) cooldownNote.style.display = 'block';
                }
            });
        });
    }

    // ===== Form Data Orang Tua =====
    var formOrtu = document.getElementById('formOrtu');
    if (formOrtu) {
        formOrtu.addEventListener('submit', function (e) {
            e.preventDefault();
            submitAjaxForm(formOrtu, document.getElementById('pfAlertOrtu'), function (data) {
                var namaInput = document.getElementById('pfNamaOrtu');
                var noWaInput = document.getElementById('pfNoWaOrtu');
                var btnOrtu = document.getElementById('pfBtnOrtu');
                var statusBox = document.getElementById('pfOrtuStatus');

                namaInput.readOnly = true;
                noWaInput.readOnly = true;
                namaInput.classList.add('bg-light', 'text-muted');
                noWaInput.classList.add('bg-light', 'text-muted');
                // BUGFIX (30 Juli 2026): hapus tombolnya dari DOM (bukan cuma
                // disembunyikan), supaya form ini tidak punya submit control
                // aktif lagi sama sekali setelah berhasil terkirim -- mencegah
                // submit ulang lewat Enter di field yang sudah readonly.
                if (btnOrtu) btnOrtu.remove();

                if (statusBox) {
                    statusBox.innerHTML = '<div class="alert alert-warning py-2 mb-4 text-center fw-bold border-0 shadow-sm text-dark">'
                        + '<i class="fas fa-hourglass-half me-1"></i>Belum Terverifikasi (Menunggu ACC Guru BK)</div>';
                }
            });
        });
    }

    // ===== Form Password =====
    var formPassword = document.getElementById('formPassword');
    if (formPassword) {
        formPassword.addEventListener('submit', function (e) {
            e.preventDefault();
            submitAjaxForm(formPassword, document.getElementById('pfAlertPassword'), function () {
                formPassword.reset();
            });
        });
    }
});
</script>
@endpush
