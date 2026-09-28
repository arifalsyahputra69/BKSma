<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        // Cek role user yang login
        $user = $request->user();

        // PERBAIKAN: sebelumnya pakai redirect()->intended(route(...)).
        // intended() akan mengabaikan route tujuan di atas dan malah
        // redirect ke URL "intended" yang tersimpan di session (URL
        // terakhir yang coba diakses user sebelum diminta login). Kalau
        // URL tersimpan itu kebetulan '/dashboard' (halaman dashboard
        // bawaan Laravel/Breeze), user jadi nyasar ke sana walaupun
        // role-nya sudah jelas. Makanya diganti redirect()->route()
        // biasa supaya SELALU ke dashboard sesuai role, konsisten.
        if ($user->hasRole('TU/Admin')) {
            return redirect()->route('tu.dashboard');
        } elseif ($user->hasRole('Kepala Sekolah')) {
            return redirect()->route('kepsek.dashboard');
        } elseif ($user->hasRole('Guru BK')) {
            return redirect()->route('gurubk.dashboard');
        } elseif ($user->hasRole('Siswa')) {
            return redirect()->route('siswa.dashboard');
        } elseif ($user->hasRole('Wali Kelas')) {
            return redirect()->route('walikelas.dashboard');
        } elseif ($user->hasRole('Guru Mapel')) {
            return redirect()->route('gurumapel.dashboard');
        }

        // Fallback kalau user tidak punya role apapun (seharusnya tidak
        // terjadi kalau data role rapi). Dulu fallback-nya ke halaman
        // dashboard bawaan Laravel yang sudah dihapus -- sekarang logout
        // paksa + kembali ke login dengan pesan error, supaya tidak ada
        // lagi kemungkinan nyasar ke halaman bawaan framework.
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'login' => 'Akun Anda belum punya role/peran. Hubungi Admin TU.',
        ]);
    }
    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}