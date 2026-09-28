<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Siswa; // Import model Siswa

class UserProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Halaman profil siswa (NISN, tanggal lahir, data orang tua) HANYA untuk role Siswa.
        // Role staff (Guru BK, Kepala Sekolah, Wali Kelas, Guru Mapel) memakai
        // tampilan profil terpisah yang cuma berisi foto & ganti password.
        if ($user->hasRole('Siswa')) {
            return view('profil.index', compact('user'));
        }

        return view('profil.staff', compact('user'));
    }

    /**
     * PERCANTIK HALAMAN PROFIL + PERBAIKAN UX (30 Juli 2026): halaman profil
     * punya beberapa form terpisah (foto, info akun, data ortu, password).
     * Sebelumnya tiap form itu <form> biasa -- begitu salah satu disubmit,
     * SELURUH halaman reload (POST-redirect-GET), jadi kalau siswa sudah
     * sempat mengetik sesuatu di form LAIN yang belum sempat disubmit,
     * ketikannya hilang total saat halaman reload.
     *
     * Perbaikannya: semua form sekarang disubmit lewat fetch/AJAX (lihat
     * script di resources/views/profil/index.blade.php & profil/staff.blade.php)
     * dengan header "Accept: application/json", jadi TIDAK ADA reload sama
     * sekali -- form lain yang belum disubmit otomatis aman. Helper respond()
     * di bawah ini yang memutuskan: kalau request minta JSON (dari fetch),
     * balas JSON; kalau tidak (browser lama/JS mati), tetap fallback ke
     * perilaku lama (redirect back dengan flash message) supaya tidak patah.
     */
    private function respond(Request $request, bool $success, string $message, array $data = [], array $fieldErrors = [], int $errorStatus = 422)
    {
        if ($request->expectsJson()) {
            if (! $success) {
                $payload = ['message' => $message];
                if (! empty($fieldErrors)) {
                    $payload['errors'] = $fieldErrors;
                }

                return response()->json($payload, $errorStatus);
            }

            return response()->json(array_merge(['success' => true, 'message' => $message], $data));
        }

        if (! $success) {
            return back()->withErrors($fieldErrors)->with('error', $message)->withInput();
        }

        return back()->with('success', $message);
    }

    // UPDATE DATA PROFIL (Email & Data Tambahan Siswa)
    public function updateInfo(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $siswa = Siswa::where('user_id', $user->id)->first();

        // 1. Cek apakah Email & No HP sedang dalam masa cooldown 3 bulan
        $canUpdateContact = true;
        $nextUpdate = null;
        if ($siswa && $siswa->waktu_update_kontak) {
            $lastUpdate = \Carbon\Carbon::parse($siswa->waktu_update_kontak);
            if (now()->lessThan($lastUpdate->copy()->addMonths(3))) {
                $canUpdateContact = false;
                $nextUpdate = $lastUpdate->copy()->addMonths(3)->format('d-m-Y');
            }
        }

        // 2. Validasi Input
        $rules = [
            'email'       => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'no_hp_siswa' => ['nullable', 'string', 'max:20'],
        ];

        // Validasi Tanggal Lahir HANYA JIKA di database masih kosong
        if (!$siswa || !$siswa->tgl_lahir) {
            $rules['tgl_lahir'] = ['nullable', 'date'];
        }

        $request->validate($rules);

        // 3. Simpan Tanggal Lahir (Sekali Seumur Hidup)
        if ($siswa && !$siswa->tgl_lahir && $request->tgl_lahir) {
            $siswa->tgl_lahir = $request->tgl_lahir;
        }

        // 4. Simpan Email & No HP (Jika tidak cooldown)
        if ($canUpdateContact) {
            $isContactChanged = ($user->email !== $request->email) || ($siswa && $siswa->no_hp_siswa !== $request->no_hp_siswa);

            if ($isContactChanged) {
                $user->email = $request->email;
                $user->save();

                if ($siswa) {
                    $siswa->no_hp_siswa = $request->no_hp_siswa;
                    $siswa->waktu_update_kontak = now(); // Catat waktu perubahan
                }

                // Cooldown baru saja mulai detik ini -- kabari front-end supaya
                // field email/no HP langsung dikunci tanpa perlu reload.
                $canUpdateContact = false;
                $nextUpdate = now()->addMonths(3)->format('d-m-Y');
            }
        } elseif (($user->email !== $request->email) || ($siswa && $siswa->no_hp_siswa !== $request->no_hp_siswa)) {
            // Jika user memaksa ubah saat cooldown, kembalikan error
            $pesan = 'Email dan No HP hanya bisa diubah setiap 3 bulan sekali. (Bisa diubah lagi pada ' . $nextUpdate . ')';

            return $this->respond($request, false, $pesan, [], [
                'email' => [$pesan],
                'no_hp_siswa' => [$pesan],
            ]);
        }

        if ($siswa) {
            $siswa->save();
        }

        return $this->respond($request, true, 'Informasi akun berhasil diperbarui.', [
            'email' => $user->email,
            'no_hp_siswa' => $siswa->no_hp_siswa ?? null,
            'tgl_lahir' => $siswa->tgl_lahir ?? null,
            'can_update_contact' => $canUpdateContact,
            'next_update' => $nextUpdate,
        ]);
    }

    // SUBMIT DATA ORANG TUA (Menunggu Verifikasi)
    public function submitParentData(Request $request)
    {
        $request->validate([
            'nama_ortu'  => 'required|string|max:255',
            'no_wa_ortu' => 'required|string|max:20',
        ]);

        $siswa = Siswa::where('user_id', Auth::id())->first();

        // BUGFIX (30 Juli 2026): sebelumnya tidak ada penjagaan sama sekali di
        // sini -- kalau form berhasil ter-submit lagi (mis. tombol yang
        // seharusnya hilang tapi masih ada di DOM, atau submit paksa lewat
        // Enter/DevTools), data orang tua yang SUDAH terverifikasi/menunggu
        // verifikasi bisa tertimpa & is_wa_verified ke-reset ke 0 lagi.
        // Sekarang dikunci di server: kalau sudah pernah dikirim (pending
        // ATAU sudah terverifikasi), tolak dan minta hubungi Guru BK/TU/Admin
        // kalau memang perlu koreksi data.
        if ($siswa && $siswa->is_wa_verified !== null) {
            return $this->respond(
                $request,
                false,
                'Data orang tua sudah pernah dikirim dan tidak bisa diubah sendiri. Hubungi Guru BK atau TU/Admin jika data perlu dikoreksi.'
            );
        }

        if ($siswa) {
            $siswa->nama_ortu = $request->nama_ortu;
            $siswa->no_wa_ortu = $request->no_wa_ortu;

            // PERBAIKAN: Gunakan angka 0 (Integer) alih-alih teks 'pending'
            $siswa->is_wa_verified = 0;

            $siswa->save();
        }

        return $this->respond($request, true, 'Data orang tua berhasil dikirim untuk diverifikasi Guru BK.', [
            'nama_ortu' => $siswa->nama_ortu ?? null,
            'no_wa_ortu' => $siswa->no_wa_ortu ?? null,
            'is_wa_verified' => $siswa->is_wa_verified ?? null,
        ]);
    }

    // UPDATE FOTO
    public function updateFoto(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        /** @var User $user */
        $user = Auth::user();

        // 1. Cek dan hapus foto lama menggunakan disk 'public'
        if ($user->foto && Storage::disk('public')->exists('profil/' . $user->foto)) {
            Storage::disk('public')->delete('profil/' . $user->foto);
        }

        $file = $request->file('foto');

        // PERBAIKAN KEAMANAN (AUDIT): nama file sebelumnya memakai
        // getClientOriginalName() mentah, yaitu string yang 100% dikendalikan
        // pengunggah. File ini disimpan di disk 'public' (bisa diakses langsung
        // lewat URL), jadi nama yang aneh-aneh ("foo.php.jpg", karakter aneh,
        // spasi, dsb) berisiko & bikin URL rusak. Sekarang nama file dibuat
        // acak oleh server dan ekstensinya diambil dari hasil deteksi isi file
        // (guessExtension), bukan dari teks yang dikirim klien.
        $filename = Str::random(32) . '.' . ($file->guessExtension() ?: 'jpg');

        // 2. PERBAIKAN UTAMA: Tambahkan 'public' agar masuk ke storage/app/public/profil
        $file->storeAs('profil', $filename, 'public');

        $user->foto = $filename;
        $user->save();

        return $this->respond($request, true, 'Foto profil berhasil diperbarui.', [
            'foto_url' => asset('storage/profil/' . $filename),
        ]);
    }

    // UPDATE PASSWORD
    public function updatePassword(Request $request)
    {
        $request->validate([
            'password_lama' => 'required',
            'password_baru' => 'required|min:8|confirmed',
        ]);

        // Memberi tahu VS Code bahwa ini adalah Model User agar tidak error 'save'
        /** @var User $user */
        $user = Auth::user();

        if (!Hash::check($request->password_lama, $user->password)) {
            return $this->respond($request, false, 'Password lama tidak sesuai.', [], [
                'password_lama' => ['Password lama tidak sesuai.'],
            ]);
        }

        $user->password = Hash::make($request->password_baru);
        $user->save();

        return $this->respond($request, true, 'Password berhasil diperbarui.');
    }
}
