<x-guest-layout>
    <div class="text-center mb-5">
        <h3 class="text-lg font-bold text-gray-800">Buat Password Baru</h3>
        <p class="text-sm text-gray-500 mt-1">Data ditemukan! Silakan masukkan password baru kamu.</p>
    </div>

    <form action="{{ route('custom.password.update') }}" method="POST">
        @csrf
        <div class="mb-4">
            <x-input-label for="password" value="Password Baru" />
            <x-text-input id="password" name="password" type="password" class="block mt-1 w-full" placeholder="Minimal 8 karakter" required autofocus />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div class="mb-5">
            <x-input-label for="password_confirmation" value="Konfirmasi Password" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="block mt-1 w-full" placeholder="Ketik ulang password baru" required />
        </div>
        <x-primary-button class="w-full justify-center py-3">
            Simpan Password Baru
        </x-primary-button>
    </form>
</x-guest-layout>