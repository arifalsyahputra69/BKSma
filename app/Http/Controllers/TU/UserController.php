<?php

namespace App\Http\Controllers\TU;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Siswa;
use App\Models\Kelas; // <-- Tambahkan model Kelas di sini
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Role-role yang ditampilkan sebagai tab TERPISAH per role (TU/Admin & Siswa
        // dikecualikan seperti sebelumnya; Wali Kelas & Guru Mapel JUGA dikecualikan
        // dari sini karena sekarang digabung jadi 1 tab "Guru" -- lihat $guruUsers
        // di bawah -- FITUR GABUNGAN TAB GURU, 22 Juli 2026).
        $rolesToShow = Role::whereNotIn('name', ['TU/Admin', 'Siswa', 'Wali Kelas', 'Guru Mapel'])
            ->orderBy('name')->get();

        // Kumpulkan daftar user per role, supaya di view tinggal di-loop per tab.
        $usersByRole = [];
        foreach ($rolesToShow as $role) {
            $usersByRole[$role->name] = User::role($role->name)->with('roles')->orderBy('name')->get();
        }

        // Tab gabungan "Guru": sebelumnya tab "Wali Kelas" & "Guru Mapel" terpisah,
        // sekarang 1 tab saja. Satu akun bisa pegang salah satu atau kedua jabatan
        // sekaligus (dipilih via checkbox saat tambah/edit), jadi query-nya cukup
        // 1 kali ambil semua user yang punya salah satu dari 2 role ini.
        $guruUsers = User::role(['Wali Kelas', 'Guru Mapel'])->with('roles')->orderBy('name')->get();

        // Daftar kelas untuk tab "Siswa": admin masuk ke kelas dulu,
        // baru menambahkan/mengelola siswa di kelas tersebut.
        $kelasList = Kelas::with('guruBk')->withCount('siswas')->orderBy('nama_kelas')->get();

        return view('tu.users.index', compact('rolesToShow', 'usersByRole', 'guruUsers', 'kelasList'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            // FITUR LOGIN PAKAI USERNAME (30 Juli 2026): TU sekarang cukup isi
            // nama, jenis kelamin, dan password saat menambah akun. Email TIDAK
            // lagi diminta di sini -- akan diisi belakangan oleh guru/siswa
            // sendiri lewat halaman profil. NIP/NISN jadi WAJIB karena itu yang
            // dipakai untuk login (kecuali TU/Admin yang tetap pakai email).
            'email' => 'nullable|email|unique:users,email',
            // PERBAIKAN KEAMANAN (AUDIT): minimum dinaikkan 6 -> 8 karakter,
            // konsisten dengan aturan di UserProfileController::updatePassword
            // dan CustomPasswordController::updatePassword yang sudah min:8.
            // Sebelumnya akun yang dibuat TU justru boleh lebih lemah daripada
            // password yang boleh dipilih sendiri oleh penggunanya.
            'password' => 'required|min:8',
            // FITUR GABUNGAN TAB GURU (22 Juli 2026): form tab "Guru" mengirim
            // jabatan[] (checkbox, bisa pilih Wali Kelas dan/atau Guru Mapel),
            // sementara tab role lain (Guru BK, Kepala Sekolah, Siswa, dst) masih
            // pakai field 'role' tunggal seperti sebelumnya. Salah satu wajib ada.
            // PERBAIKAN (AUDIT): 'role' sebelumnya diterima apa adanya lalu
            // langsung dilempar ke assignRole(). Nama role yang tidak dikenal
            // membuat Spatie melempar RoleDoesNotExist -> error 500 mentah.
            // Sekarang dibatasi ke daftar role yang memang ada di sistem.
            'role' => 'required_without:jabatan|nullable|in:TU/Admin,Kepala Sekolah,Guru BK,Wali Kelas,Guru Mapel,Siswa',
            'jabatan' => 'required_without:role|array',
            'jabatan.*' => 'in:Wali Kelas,Guru Mapel',
            'jenis_kelamin' => 'required|in:L,P',
            'nip' => 'required_unless:role,Siswa|nullable|string|unique:users,nip',
            'nisn' => 'nullable|string|unique:siswas,nisn|required_if:role,Siswa',
            'kelas_id' => 'nullable|exists:kelas,id|required_if:role,Siswa',
        ]);

        // Jabatan (khusus tab Guru gabungan) hanya boleh Wali Kelas / Guru Mapel,
        // dan mata pelajaran hanya relevan kalau Guru Mapel dipilih (1 guru = 1 mapel).
        $jabatan = array_values(array_intersect($request->input('jabatan', []), ['Wali Kelas', 'Guru Mapel']));
        if ($request->filled('jabatan') && empty($jabatan)) {
            return back()->withErrors(['jabatan' => 'Pilih minimal satu jabatan (Wali Kelas / Guru Mapel).'])->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            // Simpan null (bukan string kosong) kalau email tidak diisi, supaya
            // tidak bentrok dengan constraint unique saat ada beberapa akun
            // yang sama-sama belum punya email.
            'email' => $request->filled('email') ? $request->email : null,
            'password' => Hash::make($request->password),
            'jenis_kelamin' => $request->jenis_kelamin,
            'nip' => $request->nip,
            'mata_pelajaran' => in_array('Guru Mapel', $jabatan) ? $request->input('mata_pelajaran') : null,
            'is_active' => true,
        ]);

        // Tab "Guru" gabungan: jabatan datang dari checkbox jabatan[].
        // Tab role lain: jabatan tunggal seperti sebelumnya (field 'role').
        $rolesToAssign = !empty($jabatan) ? $jabatan : [$request->role];
        $user->assignRole($rolesToAssign);

        // LOGIKA KUNCI UNTUK SISWA
        if ($request->role == 'Siswa') {
            Siswa::create([
                'user_id' => $user->id,
                'nisn' => $request->nisn,
                'kelas_id' => $request->kelas_id, // Masukkan kelas dari form
                'status_konfirmasi_kelas' => null, // Biarkan null agar siswa diminta konfirmasi saat login
            ]);
        }

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255',
            // FITUR LOGIN PAKAI USERNAME (30 Juli 2026): email tidak lagi wajib
            // di sini -- guru/siswa mengisi sendiri lewat halaman profil.
            'email' => 'nullable|email|unique:users,email,' . $id,
            'jenis_kelamin' => 'required|in:L,P',
            // BUGFIX (23 Juli 2026): NIP sebelumnya cuma diproses saat Tambah, hilang
            // lagi setiap kali data di-Edit (field-nya ada di form Tambah tapi TIDAK
            // ada di form Edit & TIDAK ada di controller) -- sekarang diproses juga.
            // FITUR LOGIN PAKAI USERNAME (30 Juli 2026): NIP jadi wajib untuk semua
            // akun non-siswa karena dipakai sebagai username login.
            'nip' => $request->boolean('is_siswa_form')
                ? 'nullable|string'
                : 'required|string|unique:users,nip,' . $id,
            // BUGFIX (23 Juli 2026): edit data Siswa (nama/email/NISN) sebelumnya
            // tidak bisa sama sekali -- di halaman Kelola Siswa per Kelas cuma ada
            // tombol "Keluarkan dari Kelas", tidak ada modal Edit. Sekarang form
            // Edit Siswa mengirim is_siswa_form=1 supaya NISN ikut divalidasi & disimpan.
            'nisn' => $request->boolean('is_siswa_form')
                ? 'required|string|unique:siswas,nisn,' . optional($user->siswa)->id
                : 'nullable',
            // PERBAIKAN KEAMANAN (AUDIT): field "Reset Password (Opsional)" di
            // form Edit langsung di-hash & disimpan tanpa aturan panjang sama
            // sekali -- TU bisa (tanpa sadar) memberi akun guru/siswa password
            // 1 karakter. Sekarang tunduk pada aturan yang sama dengan form
            // Tambah dan form ganti password mandiri.
            'password' => 'nullable|min:8',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->filled('email') ? $request->email : null,
            'jenis_kelamin' => $request->jenis_kelamin,
            'nip' => $request->nip,
        ]);

        // BUGFIX (23 Juli 2026): simpan NISN ke tabel siswas (bukan users) kalau
        // form yang disubmit adalah form Edit Siswa.
        if ($request->boolean('is_siswa_form') && $user->siswa) {
            $user->siswa->update([
                'nisn' => $request->nisn,
            ]);
        }

        // Reset password opsional saat edit (field ini sudah ada di form edit,
        // tapi sebelumnya tidak diproses sama sekali oleh controller).
        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        // FITUR GABUNGAN TAB GURU (22 Juli 2026): edit dari tab "Guru" mengirim
        // jabatan[] (checkbox Wali Kelas / Guru Mapel, bisa dua-duanya). Admin
        // bebas centang/lepas kapan saja tanpa menyentuh role lain (Guru BK,
        // Kepala Sekolah, dst) yang mungkin juga dipegang akun yang sama.
        if ($request->boolean('is_guru_form')) {
            $jabatan = array_values(array_intersect($request->input('jabatan', []), ['Wali Kelas', 'Guru Mapel']));
            if (empty($jabatan)) {
                return back()->withErrors(['jabatan' => 'Pilih minimal satu jabatan (Wali Kelas / Guru Mapel).'])->withInput();
            }
            foreach (['Wali Kelas', 'Guru Mapel'] as $r) {
                if (in_array($r, $jabatan)) {
                    $user->assignRole($r);
                } else {
                    $user->removeRole($r);
                }
            }
            $user->update([
                'mata_pelajaran' => in_array('Guru Mapel', $jabatan) ? $request->input('mata_pelajaran') : null,
            ]);
        }

        return back()->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $user = User::findOrFail($id);
        if ($user->siswa) {
            $user->siswa->delete();
        }
        $user->delete();
        
        return back()->with('success', 'Pengguna berhasil dihapus.');
    }

    public function toggleStatus(int $id)
    {
        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();
        
        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Akun berhasil $status.");
    }

    public function resetPassword(Request $request, int $id)
    {
        // PERBAIKAN KEAMANAN (AUDIT): min:6 -> min:8, konsisten dengan aturan
        // password di seluruh aplikasi.
        $request->validate([
            'password' => 'required|min:8',
        ]);

        $user = User::findOrFail($id);
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password berhasil direset untuk ' . $user->name);
    }

    public function perbaikanKelas()
    {
        // Ubah query agar mencari data di dalam relasi tabel siswas
        $siswaBermasalah = User::role('Siswa')
            // Relasi dimuat sejak awal supaya halaman tidak menembak database
            // berulang kali untuk tiap baris (kelas lama, kelas yang diajukan).
            ->with(['siswa.kelas', 'siswa.kelasTujuan'])
            ->whereHas('siswa', function($query) {
                $query->where('status_konfirmasi_kelas', false)
                      ->whereNotNull('waktu_tidak_konfirmasi');
            })
            // Laporan paling lama tampil di atas: yang tenggatnya paling dekat
            // habis adalah yang paling mendesak ditangani.
            ->orderBy(
                Siswa::select('waktu_tidak_konfirmasi')
                    ->whereColumn('siswas.user_id', 'users.id')
                    ->limit(1)
            )
            ->get();

        return view('tu.perbaikan-kelas', compact('siswaBermasalah'));
    }

    public function updateKelasSiswa(Request $request, $id)
    {
        // PERBAIKAN (AUDIT): kelas_id sebelumnya disimpan tanpa divalidasi sama
        // sekali, sehingga id kelas yang tidak ada (atau string kosong) bisa
        // masuk ke tabel siswas dan membuat siswa "menggantung" di kelas yang
        // tidak eksis -- efeknya siswa itu hilang dari daftar Guru BK & Wali
        // Kelas manapun.
        $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
        ]);

        $user = \App\Models\User::findOrFail($id);

        // Yang di-update kelasnya adalah tabel siswas, bukan users!
        if ($user->siswa) {
            $user->siswa->update([
                'kelas_id' => $request->kelas_id,
                'status_konfirmasi_kelas' => true,
                'waktu_tidak_konfirmasi' => null,

                // Usulan siswa ikut dibersihkan setelah ditindaklanjuti.
                // Kalau ditinggal, laporan lama akan tetap terbaca sebagai
                // "sedang mengajukan pindah" saat siswa itu suatu hari
                // melapor lagi -- dan TU melihat kelas tujuan basi dari
                // semester sebelumnya sudah terpilih di dropdown.
                'kelas_tujuan_id' => null,
                'catatan_perbaikan_kelas' => null,
            ]);
        }

        // Tandai notifikasi terkait siswa ini sebagai sudah dibaca.
        //
        // PERBAIKAN (5 Agustus 2026): pencarian sebelumnya hanya mencocokkan
        // nama di dalam teks pesan. Nama yang umum atau sepenggal nama yang
        // kebetulan muncul di notifikasi lain (mis. laporan konseling atau
        // pengumuman) ikut tertandai terbaca padahal tidak ada hubungannya --
        // TU kehilangan pemberitahuan yang belum sempat dilihat.
        // Sekarang dibatasi ke notifikasi berjudul sama saja.
        \App\Models\Notifikasi::where('judul', 'Perbaikan Data Kelas')
            ->where('pesan', 'LIKE', "%{$user->name}%")
            ->update(['is_read' => true]);

        return redirect()->route('tu.users.perbaikan-kelas')->with('success', 'Data kelas berhasil diupdate!');
    }
}