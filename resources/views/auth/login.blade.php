@php
    $jamSekarang = now()->hour;
    $sapaanWaktu = match (true) {
        $jamSekarang < 11 => 'Selamat pagi',
        $jamSekarang < 15 => 'Selamat siang',
        $jamSekarang < 19 => 'Selamat sore',
        default => 'Selamat malam',
    };
    // Ikon kecil di samping sapaan, ikut waktu setempat. Detail kecil,
    // tapi membuat halaman terasa menyapa alih-alih sekadar formulir.
    $ikonWaktu = $jamSekarang >= 6 && $jamSekarang < 18 ? 'matahari' : 'bulan';
@endphp

<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if(session('success'))
        <div class="login-alert login-alert--ok">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="login-alert login-alert--bad">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="login-reveal" style="animation-delay:.02s; margin-bottom:1.75rem">
        <p class="login-greet">
            @if($ikonWaktu === 'matahari')
                <svg class="login-greet-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            @else
                <svg class="login-greet-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
            @endif
            {{ $sapaanWaktu }}
        </p>
        <h1 class="font-display italic text-2xl" style="color: var(--simbk-primary-dark);">Selamat datang kembali</h1>
        <p class="text-sm text-gray-500 mt-1.5">Masuk untuk melanjutkan ke SIM BK.</p>
    </div>

    {{-- Status "sedang mengirim" TIDAK ditangani di sini. partials/ui-enhance
         sudah memasang spinner + mengunci tombol submit untuk setiap form di
         seluruh aplikasi (lengkap dengan jaring pengaman 12 detik). Menambah
         mekanisme kedua di sini justru bentrok: kelas .btn-loading membuat
         teks tombol jadi transparan, sehingga label buatan sendiri malah
         tidak terlihat dan spinner-nya dobel. --}}
    <form method="POST" action="{{ route('login') }}" class="space-y-5" x-data="{ showPassword: false, capsOn: false }">
        @csrf

        <div class="login-reveal" style="animation-delay:.08s">
            <x-input-label for="login" value="NIP/NISN" />
            <div class="relative mt-1.5 login-field">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 login-field-icon text-gray-400">
                    <svg style="width:1.1rem;height:1.1rem" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                </span>
                {{-- CATATAN KEAMANAN (30 Juli 2026): jangan tampilkan di UI bahwa
                     TU/Admin punya jalur login khusus via email -- cukup
                     ditangani diam-diam di backend (LoginRequest), supaya
                     tidak jadi petunjuk buat orang luar yang mau coba iseng. --}}
                <x-text-input id="login" class="block w-full pl-10" type="text" name="login" :value="old('login')" required autofocus autocomplete="username" placeholder="Masukkan NIP/NISN" />
            </div>
            <x-input-error :messages="$errors->get('login')" class="mt-2" />
        </div>

        <div class="login-reveal" style="animation-delay:.14s">
            <x-input-label for="password" value="Password" />
            <div class="relative mt-1.5 login-field">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 login-field-icon text-gray-400">
                    <svg style="width:1.1rem;height:1.1rem" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V7.5a4.5 4.5 0 1 0-9 0v3m-.75 0h10.5c.83 0 1.5.67 1.5 1.5v7.5c0 .83-.67 1.5-1.5 1.5H6.75a1.5 1.5 0 0 1-1.5-1.5V12c0-.83.67-1.5 1.5-1.5Z" /></svg>
                </span>
                <x-text-input id="password" class="block w-full pl-10 pr-10"
                    :type="'password'"
                    x-bind:type="showPassword ? 'text' : 'password'"
                    name="password"
                    required autocomplete="current-password" placeholder="••••••••"
                    x-on:keyup="capsOn = $event.getModifierState ? $event.getModifierState('CapsLock') : false" />
                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-gray-600 transition-colors" tabindex="-1">
                    <svg x-show="!showPassword" style="width:1.1rem;height:1.1rem" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                    <svg x-show="showPassword" style="width:1.1rem;height:1.1rem;display:none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.243 4.243L9.88 9.88" /></svg>
                </button>
            </div>
            <p x-show="capsOn" x-transition.duration.200ms x-cloak class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-amber-600">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                Caps Lock aktif
            </p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between pt-1 login-reveal" style="animation-delay:.2s">
            <label for="remember_me" class="inline-flex items-center select-none cursor-pointer group">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 shadow-sm transition-transform group-active:scale-90" style="accent-color: #1f4b3f;" name="remember">
                <span class="ms-2 text-sm text-gray-600">Ingat saya</span>
            </label>

            @if (Route::has('custom.password.request'))
                <a class="text-sm font-medium hover:underline rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors" style="color: var(--simbk-primary);" href="{{ route('custom.password.request') }}">
                    Lupa password?
                </a>
            @endif
        </div>

        <div class="login-reveal" style="animation-delay:.26s">
            <x-primary-button class="w-full justify-center py-3 text-[0.8rem] login-submit">
                <span class="relative z-10 inline-flex items-center gap-1.5">
                    Masuk
                    <svg class="w-3.5 h-3.5 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </span>
            </x-primary-button>
        </div>

        <p class="login-note login-reveal" style="animation-delay:.32s">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M12 2.25l7.5 3v6c0 4.5-3.1 8.6-7.5 9.75C7.6 19.85 4.5 15.75 4.5 11.25v-6l7.5-3Z"/></svg>
            Akun ini bersifat pribadi. Jangan bagikan sandi Anda kepada siapa pun.
        </p>
    </form>

    <style>
        /* =====================================================
           Semua tampilan di bawah ditulis sebagai CSS biasa, bukan
           utility Tailwind. Alasannya: Tailwind di proyek ini dibuang
           kelas-kelas yang tidak terpakai saat build, jadi kelas baru
           tidak akan punya gaya apa pun di server sampai aset di-build
           ulang -- sesuatu yang tidak bisa dilakukan dari cPanel.
           ===================================================== */

        .login-reveal { animation: loginFadeUp .55s cubic-bezier(.2,.7,.3,1) both; }
        @keyframes loginFadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* --- Sapaan waktu --- */
        .login-greet {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .09em;
            text-transform: uppercase;
            color: var(--simbk-gold, #c9974a);
            margin-bottom: .45rem;
        }
        .login-greet-icon {
            width: .95rem;
            height: .95rem;
            animation: loginGlow 4s ease-in-out infinite;
        }
        @keyframes loginGlow {
            0%, 100% { opacity: .65; transform: scale(1); }
            50%      { opacity: 1;   transform: scale(1.12); }
        }

        /* --- Kotak pesan (sukses / gagal) --- */
        .login-alert {
            display: flex;
            align-items: flex-start;
            gap: .55rem;
            font-size: .82rem;
            line-height: 1.5;
            padding: .7rem .85rem;
            border-radius: .75rem;
            border: 1px solid;
            margin-bottom: 1.25rem;
            animation: loginAlertIn .45s cubic-bezier(.2,.7,.3,1) both;
        }
        .login-alert svg { width: 1rem; height: 1rem; flex-shrink: 0; margin-top: .12rem; }
        .login-alert--ok  { color: #166534; background: #f0fdf4; border-color: #bbf7d0; }
        .login-alert--bad { color: #b91c1c; background: #fef2f2; border-color: #fecaca; }
        @keyframes loginAlertIn {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* --- Kolom isian --- */
        .login-field {
            transition: box-shadow .2s ease, transform .2s ease;
            border-radius: .5rem;
        }
        .login-field:focus-within {
            box-shadow: 0 0 0 4px rgba(31,75,63,.12);
            transform: translateY(-1px);
        }
        .login-field-icon { transition: color .2s ease, transform .2s ease; }
        .login-field:focus-within .login-field-icon {
            color: var(--simbk-primary, #1f4b3f);
            transform: scale(1.08);
        }

        /* --- Tombol Masuk --- */
        .login-submit {
            position: relative;
            overflow: hidden;
            transition: transform .15s ease, box-shadow .2s ease, opacity .2s ease;
        }
        .login-submit:hover:not(:disabled) {
            transform: scale(1.015);
            box-shadow: 0 10px 22px -10px rgba(31,75,63,.65);
        }
        .login-submit:active:not(:disabled) { transform: scale(.98); }
        /* Kilau yang menyapu tombol saat kursor lewat.
           Sengaja memakai ::before, BUKAN ::after. Alasannya: saat form
           dikirim, partials/ui-enhance menempelkan kelas .btn-loading yang
           menggambar spinner-nya sendiri di ::after. Kalau kilau ini juga
           memakai ::after, keduanya berebut pseudo-element yang sama dan
           spinner akan ikut mewarisi gradient kilau -- lingkaran spinner
           jadi tampak belang. */
        .login-submit::before {
            content: '';
            position: absolute;
            top: 0; left: -130%;
            width: 55%; height: 100%;
            background: linear-gradient(100deg, transparent, rgba(255,255,255,.22), transparent);
            transition: left .65s ease;
            pointer-events: none;
        }
        .login-submit:hover:not(:disabled)::before { left: 150%; }
        .login-submit:hover:not(:disabled) svg { transform: translateX(3px); }

        /* --- Catatan keamanan di bawah form --- */
        .login-note {
            display: flex;
            align-items: flex-start;
            gap: .45rem;
            font-size: .72rem;
            line-height: 1.55;
            color: #9ca3af;
            margin-top: .25rem;
        }
        .login-note svg { width: .85rem; height: .85rem; flex-shrink: 0; margin-top: .12rem; }

        @media (prefers-reduced-motion: reduce) {
            .login-reveal, .login-alert { animation: none; opacity: 1; transform: none; }
            .login-greet-icon { animation: none; }
            .login-submit, .login-field, .login-field-icon { transition: none; }
        }
    </style>
</x-guest-layout>
