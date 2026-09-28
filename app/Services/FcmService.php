<?php

namespace App\Services;

// LETAKKAN DI: app/Services/FcmService.php (GANTI file lama -- revisi ke-2)
//
// PERUBAHAN BESAR: package kreait/laravel-firebase DIBUANG sepenuhnya.
// Alasan: versi terbaru package itu belum support Laravel 13
// (butuh illuminate/contracts ^11/^12, punyamu ^13.8), dan versi lama butuh
// PHP lama yang tidak cocok dengan PHP 8.5.3 kamu -- jadi composer require
// akan SELALU gagal, tidak peduli extension apa yang diaktifkan.
//
// Solusinya: panggil FCM HTTP v1 API langsung pakai HTTP client bawaan
// Laravel (Illuminate\Support\Facades\Http) + fungsi openssl bawaan PHP untuk
// bikin JWT ke Google OAuth2. TIDAK ADA package composer tambahan yang
// dibutuhkan sama sekali -- jadi tidak akan pernah bentrok dependency lagi.
//
// Cara kerja singkat:
// 1. Baca file service account JSON (client_email, private_key, project_id).
// 2. Bikin JWT ditandatangani pakai private key itu (openssl_sign, RS256).
// 3. Tukar JWT itu ke Google buat dapat access token (di-cache 55 menit).
// 4. Kirim push notification ke FCM pakai access token itu.

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /**
     * Kirim push notification ke satu token device.
     *
     * @param  string      $token   Token FCM milik device tujuan (users.fcm_token)
     * @param  string      $judul   Judul notifikasi
     * @param  string      $pesan   Isi notifikasi
     * @param  string|null $link    URL tujuan saat notifikasi diklik (opsional)
     * @return bool  true jika terkirim, false jika dilewati/gagal (sudah di-log)
     */
    public function kirimKeToken(string $token, string $judul, string $pesan, ?string $link = null): bool
    {
        if (blank($token)) {
            return false;
        }

        $credentialsPath = config('services.firebase.credentials');
        if (blank($credentialsPath) || (! is_file($credentialsPath) && ! is_file(base_path($credentialsPath)))) {
            // Kredensial belum diisi / file JSON belum ditaruh -> anggap FCM
            // belum aktif, jangan ganggu alur utama.
            return false;
        }

        try {
            $accessToken = $this->getAccessToken($credentialsPath);
            if (blank($accessToken)) {
                return false;
            }

            $serviceAccount = $this->readServiceAccount($credentialsPath);
            $projectId = $serviceAccount['project_id'];

            $response = Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $judul,
                            'body' => $pesan,
                        ],
                        'webpush' => [
                            'fcm_options' => [
                                'link' => $link ?: config('app.url'),
                            ],
                        ],
                        'data' => [
                            'link' => $link ?: config('app.url'),
                        ],
                    ],
                ]);

            if ($response->failed()) {
                // Token device sudah tidak valid (user logout/hapus izin notif) tergolong
                // wajar terjadi -- cukup dicatat sebagai info, bukan warning.
                $level = str_contains($response->body(), 'UNREGISTERED') ? 'info' : 'warning';
                Log::{$level}('FCM: gagal mengirim push notification.', ['status' => $response->status(), 'body' => $response->body()]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('FCM: exception saat mengirim push notification.', ['pesan' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Ambil access token OAuth2 dari Google, di-cache 55 menit (token asli
     * berlaku 60 menit) supaya tidak minta token baru di setiap request.
     */
    private function getAccessToken(string $credentialsPath): ?string
    {
        return Cache::remember('fcm_access_token', now()->addMinutes(55), function () use ($credentialsPath) {
            $serviceAccount = $this->readServiceAccount($credentialsPath);

            $now = time();
            $header = $this->base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claim = $this->base64url(json_encode([
                'iss' => $serviceAccount['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $now + 3600,
                'iat' => $now,
            ]));

            $signingInput = "{$header}.{$claim}";
            $privateKey = openssl_pkey_get_private($serviceAccount['private_key']);
            if (! $privateKey) {
                Log::warning('FCM: private key di file service account tidak valid.');

                return null;
            }

            openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
            $jwt = "{$signingInput}.".$this->base64url($signature);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->failed()) {
                Log::warning('FCM: gagal menukar JWT ke access token.', ['body' => $response->body()]);

                return null;
            }

            return $response->json('access_token');
        });
    }

    private function readServiceAccount(string $path): array
    {
        $fullPath = is_file($path) ? $path : base_path($path);

        return json_decode(file_get_contents($fullPath), true);
    }

    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}