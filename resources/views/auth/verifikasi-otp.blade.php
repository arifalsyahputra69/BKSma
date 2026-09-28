<x-guest-layout>
    <div class="text-center mb-5">
        <h3 class="text-lg font-bold text-gray-800">Verifikasi Kode</h3>
        <p class="text-sm text-gray-500 mt-1">Kode verifikasi 6 digit sudah dikirim lewat WhatsApp ke nomor HP kamu yang terdaftar. Masukkan kodenya di bawah ini.</p>
    </div>

    @if(session('error'))
        <div class="mb-4 text-sm text-red-600 bg-red-100 p-3 rounded-lg text-center">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('custom.password.otp.verify') }}" method="POST">
        @csrf
        <div class="mb-4">
            <x-input-label for="otp" value="Kode Verifikasi" />
            <x-text-input id="otp" name="otp" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" class="block mt-1 w-full text-center tracking-[0.5em]" placeholder="000000" required autofocus />
            <x-input-error :messages="$errors->get('otp')" class="mt-2" />
        </div>
        <p class="text-xs text-gray-400 mb-4">Kode berlaku selama 10 menit. Jangan berikan kode ini ke siapa pun.</p>
        <x-primary-button class="w-full justify-center py-3">
            Verifikasi
        </x-primary-button>
    </form>

    <div class="text-center mt-4">
        <a href="{{ route('custom.password.request') }}" class="text-sm text-gray-500 hover:text-[#1f4b3f] underline">Ulangi dari awal</a>
    </div>
</x-guest-layout>
