<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * FITUR LOGIN PAKAI USERNAME (30 Juli 2026): field login sebelumnya
     * bernama 'email'. Sekarang satu input generik 'login' dipakai untuk:
     * - TU/Admin: tetap boleh pakai email (dikecualikan sesuai permintaan).
     * - Role lain (Guru BK, Kepsek, Wali Kelas, Guru Mapel, Siswa): login
     *   pakai NIP (guru/staf) atau NISN (siswa), BUKAN email lagi.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Cari user berdasarkan input 'login'.
     *
     * PERBAIKAN (30 Juli 2026): sebelumnya SEMUA role bisa login pakai email
     * kalau kebetulan email itu ada di data mereka (mis. Guru BK yang sudah
     * mengisi email sendiri lewat profil, tetap bisa login pakai email itu).
     * Itu salah -- login via email HARUS eksklusif untuk TU/Admin saja. Role
     * lain WAJIB pakai NIP/NISN, walaupun email mereka cocok.
     */
    protected function findUserForLogin(string $login): ?User
    {
        // Jalur email: HANYA berlaku untuk akun TU/Admin.
        $user = User::where('email', $login)->first();

        if ($user && $user->hasRole('TU/Admin')) {
            return $user;
        }

        // Jalur NIP: guru/staf non-TU/Admin.
        $user = User::where('nip', $login)->first();

        if ($user) {
            return $user;
        }

        // Jalur NISN: siswa (lewat relasi ke tabel siswas).
        $siswa = Siswa::where('nisn', $login)->first();

        return $siswa?->user;
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = trim((string) $this->string('login'));
        $user = $this->findUserForLogin($login);

        if (! $user || ! Hash::check((string) $this->string('password'), $user->password)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        Auth::login($user, $this->boolean('remember'));

        // BUGFIX: sebelumnya status is_active tidak pernah dicek saat login.
        // Middleware 'user.status' hanya dipasang di grup route TU/Admin,
        // sehingga akun Guru BK/Wali Kelas/Guru Mapel/Kepsek/Siswa yang sudah
        // dinonaktifkan TU tetap bisa login dan memakai sistem seperti biasa.
        // Cek eksplisit di sini supaya nonaktif berlaku untuk SEMUA role.
        if (! Auth::user()->is_active) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => 'Akun Anda telah dinonaktifkan. Hubungi Admin.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }
}
