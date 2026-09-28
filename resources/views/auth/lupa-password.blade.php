<x-guest-layout>
    <div class="text-center mb-5">
        <h3 class="text-lg font-bold text-gray-800">Lupa Password</h3>
        <p class="text-sm text-gray-500 mt-1">Masukkan NISN atau NIP kamu untuk mengatur ulang password.</p>
    </div>

    @if(session('error'))
        <div class="mb-4 text-sm text-red-600 bg-red-100 p-3 rounded-lg text-center">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('custom.password.cek') }}" method="POST">
        @csrf
        <div class="mb-4">
            <x-input-label for="nomor_induk" value="NISN / NIP" />
            <x-text-input id="nomor_induk" name="nomor_induk" type="text" class="block mt-1 w-full" placeholder="Masukkan NISN / NIP..." required autofocus />
        </div>
        <x-primary-button class="w-full justify-center py-3">
            Cari Data
        </x-primary-button>
    </form>

    <div class="text-center mt-4">
        <a href="{{ route('login') }}" class="text-sm text-gray-500 hover:text-[#1f4b3f] underline">Kembali ke halaman Login</a>
    </div>
</x-guest-layout>