<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ProfileController;

// ================= IMPORT CONTROLLERS =================

// Controller TU/Admin
use App\Http\Controllers\TU\SemesterController;
use App\Http\Controllers\TU\UserController;
use App\Http\Controllers\TU\KelasController;
use App\Http\Controllers\TU\KampusController; // FITUR BARU (19 Juli 2026): Kelola Informasi Kampus

// Controller Guru BK
use App\Http\Controllers\GuruBK\DashboardController as GuruBKDashboardController;
use App\Http\Controllers\GuruBK\ChatbotRuleController;
use App\Http\Controllers\GuruBK\AlurChatbotController;
use App\Http\Controllers\GuruBK\JurnalLayananController;
use App\Http\Controllers\GuruBK\SesiKonselingController;
use App\Http\Controllers\GuruBK\ProgramBkController as GuruBKProgramBkController;
use App\Http\Controllers\GuruBK\NotifikasiController as GuruBKNotifikasiController;
use App\Http\Controllers\Kepsek\DashboardController as KepsekDashboardController;
use App\Http\Controllers\Kepsek\ProgramBkController as KepsekProgramBkController;
use App\Http\Controllers\Kepsek\NotifikasiController as KepsekNotifikasiController;
use App\Http\Controllers\Kepsek\LaporanExportController;
use App\Http\Controllers\Kepsek\KunjunganRumahController as KepsekKunjunganRumahController;
use App\Http\Controllers\Kepsek\KasusDaruratController;
use App\Http\Controllers\Kepsek\KegiatanBkController as KepsekKegiatanBkController;
use App\Http\Controllers\GuruBK\KunjunganRumahController as GuruBKKunjunganRumahController;
use App\Http\Controllers\GuruBK\KegiatanBkController as GuruBKKegiatanBkController;
use App\Http\Controllers\GuruBK\AbsensiPantauController as GuruBKAbsensiPantauController; // PERBAIKAN AUDIT (Poin 2)
use App\Http\Controllers\GuruBK\ArtikelBkController; // FITUR BARU 20 Juli 2026 (dulu Batasan Penelitian)
use App\Http\Controllers\Siswa\ArtikelController as SiswaArtikelController; // FITUR BARU 20 Juli 2026
use App\Http\Controllers\GuruBK\AkpdController; // FITUR BARU (29 Juli 2026): AKPD
use App\Http\Controllers\Siswa\AkpdController as SiswaAkpdController; // FITUR BARU (29 Juli 2026)
use App\Http\Controllers\WaliKelas\DashboardController as WaliKelasDashboardController;
use App\Http\Controllers\WaliKelas\NotifikasiController as WaliKelasNotifikasiController;
use App\Http\Controllers\WaliKelas\RekapAbsensiController as WaliKelasRekapAbsensiController;

// Controller Siswa
use App\Http\Controllers\Siswa\DashboardController as SiswaDashboardController;
use App\Http\Controllers\Siswa\ChatbotController as SiswaChatbotController;
use App\Http\Controllers\Siswa\KonfirmasiKelasController;
use App\Http\Controllers\Siswa\SesiKonselingController as SiswaSesiController;
use App\Http\Controllers\Siswa\NotifikasiController as SiswaNotifikasiController;
use App\Http\Controllers\Siswa\AbsensiScanController;
use App\Http\Controllers\Siswa\InformasiKampusController; // PERBAIKAN (30 Juli 2026): ganti "Konsultasi Jurusan" yang error jadi halaman baca-saja

// Controller Guru Mapel
use App\Http\Controllers\GuruMapel\DashboardController as GuruMapelDashboardController;
use App\Http\Controllers\GuruMapel\AbsensiController as GuruMapelAbsensiController;
use App\Http\Controllers\GuruMapel\NotifikasiController as GuruMapelNotifikasiController;

// Controller Lainnya & Middleware
use App\Http\Controllers\FcmTokenController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\Auth\CustomPasswordController;
use App\Http\Middleware\CheckClassConfirmation;

// ======================================================

Route::get('/', function () {
    return view('welcome');
});

// PERBAIKAN: dulu route ini menampilkan halaman dashboard bawaan
// Laravel/Breeze (resources/views/dashboard.blade.php) yang tidak
// pernah dipakai/didesain untuk aplikasi ini. Beberapa alur bawaan
// Breeze (verifikasi email, konfirmasi password, dsb) masih redirect
// ke route bernama 'dashboard', jadi route ini TIDAK dihapus total --
// tapi diubah jadi "pengalih" otomatis ke dashboard sesuai role user
// yang login, supaya halaman bawaan Laravel itu tidak pernah tampil lagi.
Route::get('/dashboard', function () {
    /** @var \App\Models\User $user */
    $user = Auth::user();

    return match (true) {
        $user->hasRole('TU/Admin') => redirect()->route('tu.dashboard'),
        $user->hasRole('Kepala Sekolah') => redirect()->route('kepsek.dashboard'),
        $user->hasRole('Guru BK') => redirect()->route('gurubk.dashboard'),
        $user->hasRole('Siswa') => redirect()->route('siswa.dashboard'),
        $user->hasRole('Wali Kelas') => redirect()->route('walikelas.dashboard'),
        $user->hasRole('Guru Mapel') => redirect()->route('gurumapel.dashboard'),
        default => redirect()->route('login'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

// ==========================================
// ROUTE LUPA PASSWORD CUSTOM (NISN/NIP)
// ==========================================
// PERBAIKAN KEAMANAN (AUDIT): seluruh alur "Lupa Password" sebelumnya sama
// sekali tidak dibatasi jumlah percobaannya. Akibatnya:
// 1. /lupa-password/cek bisa dipanggil berulang-ulang untuk (a) menebak NISN
//    mana yang terdaftar, dan (b) membanjiri HP siswa dengan pesan WA OTP
//    (WA-bombing) yang juga menghabiskan kuota Fonnte sekolah.
// 2. /lupa-password/verifikasi bisa dibrute-force. Batas 5 percobaan yang ada
//    di controller disimpan di SESSION, jadi penyerang tinggal membuang
//    cookie sesinya untuk mereset hitungan itu.
// Throttle di level route memakai IP sehingga tidak bisa diakali dengan
// mengganti/menghapus cookie.
Route::middleware('guest')->group(function () {
    Route::get('/lupa-password', [CustomPasswordController::class, 'showNomorIndukForm'])->name('custom.password.request');
    Route::post('/lupa-password/cek', [CustomPasswordController::class, 'cekNomorInduk'])
        ->middleware('throttle:5,10')
        ->name('custom.password.cek');
    // PERBAIKAN KEAMANAN (30 Juli 2026): tahap verifikasi OTP (dikirim ke WA
    // orang tua terverifikasi) wajib dilewati sebelum siswa bisa mengatur
    // password baru -- lihat catatan di CustomPasswordController.
    Route::get('/lupa-password/verifikasi', [CustomPasswordController::class, 'showOtpForm'])->name('custom.password.otp');
    Route::post('/lupa-password/verifikasi', [CustomPasswordController::class, 'verifyOtp'])
        ->middleware('throttle:10,10')
        ->name('custom.password.otp.verify');
    Route::get('/reset-password', [CustomPasswordController::class, 'showResetForm'])->name('custom.password.reset');
    Route::post('/reset-password/update', [CustomPasswordController::class, 'updatePassword'])
        ->middleware('throttle:10,10')
        ->name('custom.password.update');
});

// Group Middleware Auth untuk Fitur Profile Bawaan Breeze
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // PERBAIKAN KEAMANAN (30 Juli 2026): sebelumnya route ini terdaftar DI
    // LUAR middleware 'auth', sehingga bisa diakses tanpa login. Kalau
    // diakses tanpa login, Auth::user() bernilai null dan
    // FcmTokenController::store() akan crash (fatal error "Call to a member
    // function update() on null"). Dipindah ke sini supaya konsisten dengan
    // fitur lain yang butuh login, dan controllernya tetap diberi
    // null-safety tambahan sebagai lapisan pengaman kedua.
    Route::post('/fcm/token', [FcmTokenController::class, 'store'])->name('fcm.token.store');
});

// ==========================================
// ROUTE DASHBOARD MULTI-ROLE (SPATIE)
// ==========================================

Route::middleware(['auth', 'role:TU/Admin', 'user.status'])->group(function () {
    // Dashboard TU
    Route::get('/tu/dashboard', function () {
        return view('tu.dashboard');
    })->name('tu.dashboard');

    // Manajemen Semester
    Route::get('/tu/semester', [SemesterController::class, 'index'])->name('tu.semester.index');
    Route::post('/tu/semester', [SemesterController::class, 'store'])->name('tu.semester.store');
    Route::get('/tu/semester/{id}', [SemesterController::class, 'show'])->name('tu.semester.show'); 
    Route::put('/tu/semester/{id}', [SemesterController::class, 'update'])->name('tu.semester.update'); 
    Route::patch('/tu/semester/{id}/status', [SemesterController::class, 'toggleStatus'])->name('tu.semester.status'); 
    Route::delete('/tu/semester/{id}', [SemesterController::class, 'destroy'])->name('tu.semester.destroy');

    // Kelola Pengguna
    Route::get('/tu/users', [UserController::class, 'index'])->name('tu.users.index');
    Route::post('/tu/users', [UserController::class, 'store'])->name('tu.users.store');
    Route::put('/tu/users/{id}', [UserController::class, 'update'])->name('tu.users.update');
    Route::delete('/tu/users/{id}', [UserController::class, 'destroy'])->name('tu.users.destroy');
    Route::patch('/tu/users/{id}/status', [UserController::class, 'toggleStatus'])->name('tu.users.status');
    // BUGFIX (AUDIT): UserController::resetPassword() sudah lama ada dan
    // disebut-sebut di CustomPasswordController sebagai SATU-SATUNYA jalur
    // reset password untuk akun staf/guru (self-service via NIP sengaja
    // dimatikan) -- tapi rutenya tidak pernah didaftarkan, jadi fitur itu
    // sebetulnya tidak bisa dipakai sama sekali dan staf yang lupa password
    // benar-benar terkunci. Rutenya ditambahkan di sini.
    Route::post('/tu/users/{id}/reset-password', [UserController::class, 'resetPassword'])->name('tu.users.reset-password');
    
    // Notifikasi Perbaikan Kelas
    Route::get('/tu/perbaikan-kelas', [UserController::class, 'perbaikanKelas'])->name('tu.users.perbaikan-kelas');

    // Kelola Kelas
    Route::get('/tu/kelas', [KelasController::class, 'index'])->name('tu.kelas.index');
    Route::post('/tu/kelas', [KelasController::class, 'store'])->name('tu.kelas.store');
    Route::put('/tu/kelas/{id}', [KelasController::class, 'update'])->name('tu.kelas.update');
    Route::delete('/tu/kelas/{id}', [KelasController::class, 'destroy'])->name('tu.kelas.destroy');
    Route::get('/tu/kelas/{id}/detail', [KelasController::class, 'show'])->name('tu.kelas.show');
    Route::post('/tu/kelas/{id}/assign', [KelasController::class, 'assignSiswa'])->name('tu.kelas.assign');
    Route::delete('/tu/kelas/{id}/remove/{siswa_id}', [KelasController::class, 'removeSiswa'])->name('tu.kelas.remove');

    // FITUR BARU (19 Juli 2026): Kelola Informasi Kampus
    Route::get('/tu/kampus', [KampusController::class, 'index'])->name('tu.kampus.index');
    Route::post('/tu/kampus', [KampusController::class, 'store'])->name('tu.kampus.store');
    Route::put('/tu/kampus/{id}', [KampusController::class, 'update'])->name('tu.kampus.update');
    Route::delete('/tu/kampus/{id}', [KampusController::class, 'destroy'])->name('tu.kampus.destroy');
    Route::put('/tu/update-kelas/{id}', [UserController::class, 'updateKelasSiswa'])->name('tu.users.update-kelas');
});

// PERBAIKAN KEAMANAN (AUDIT): middleware 'user.status' dulu HANYA dipasang di
// grup TU/Admin. Login memang sudah menolak akun nonaktif (LoginRequest), tapi
// user yang SUDAH terlanjur login lalu dinonaktifkan TU tetap bisa memakai
// sistem sampai sesinya kedaluwarsa (default 120 menit). Sekarang dipasang di
// semua grup, jadi penonaktifan akun langsung berlaku pada request berikutnya.
Route::middleware(['auth', 'role:Kepala Sekolah', 'user.status'])->prefix('kepsek')->name('kepsek.')->group(function () {
    Route::get('/dashboard', [KepsekDashboardController::class, 'index'])->name('dashboard');

    // PRIORITAS 7: Unduh laporan monitoring dalam bentuk PDF / Excel.
    // Memakai filter GET yang sama dengan dashboard (semester_id, kelas_id)
    // supaya laporan yang diunduh sesuai dengan tampilan yang sedang dilihat.
    Route::get('/laporan/pdf', [LaporanExportController::class, 'pdf'])->name('laporan.pdf');
    Route::get('/laporan/excel', [LaporanExportController::class, 'excel'])->name('laporan.excel');

    // Pengajuan & Persetujuan Program BK (dari Guru BK)
    Route::get('/program-bk', [KepsekProgramBkController::class, 'index'])->name('program-bk.index');
    Route::post('/program-bk/{id}/approve', [KepsekProgramBkController::class, 'approve'])->name('program-bk.approve');
    Route::post('/program-bk/{id}/reject', [KepsekProgramBkController::class, 'reject'])->name('program-bk.reject');

    // FASE 8: Monitoring Home Visit (Kunjungan Rumah) -- read-only, tanpa
    // hasil_observasi/kesepakatan_bersama demi kerahasiaan.
    Route::get('/kunjungan-rumah', [KepsekKunjunganRumahController::class, 'index'])->name('kunjungan-rumah.index');

    // FASE 8: Kasus darurat + approval usulan DO/skorsing dari Guru BK.
    Route::prefix('kasus-darurat')->name('kasus-darurat.')->group(function () {
        Route::get('/', [KasusDaruratController::class, 'index'])->name('index');
        Route::post('/{id}/approve', [KasusDaruratController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [KasusDaruratController::class, 'reject'])->name('reject');
    });

    // FASE 8: Monitoring Kalender Kegiatan BK (read-only, lintas Guru BK).
    Route::get('/kegiatan-bk', [KepsekKegiatanBkController::class, 'index'])->name('kegiatan-bk.index');

    // PERBAIKAN: sebelumnya belum ada rute untuk menandai notifikasi Kepsek
    // sebagai "sudah dibaca", sehingga notifikasi yang sudah diklik tetap
    // muncul terus di dropdown. Rute ini meniru pola yang sama dengan
    // notifikasi Siswa (lihat prefix('notifikasi') di grup Siswa di bawah).
    Route::prefix('notifikasi')->name('notifikasi.')->group(function () {
        Route::get('/{id}/baca', [KepsekNotifikasiController::class, 'baca'])->name('baca');
        Route::post('/baca-semua', [KepsekNotifikasiController::class, 'bacaSemua'])->name('baca-semua');
    });
});

Route::middleware(['auth', 'role:Guru BK', 'user.status'])->prefix('gurubk')->name('gurubk.')->group(function () {
    // Dashboard Guru BK
    Route::get('/dashboard', [GuruBKDashboardController::class, 'index'])->name('dashboard');
    
    // Chatbot Rule & Alur
    Route::get('/chatbot', [ChatbotRuleController::class, 'index'])->name('chatbot.index');
    Route::post('/chatbot', [ChatbotRuleController::class, 'store'])->name('chatbot.store');
    // FITUR BARU (7 Agustus 2026): aturan chatbot sebelumnya hanya bisa dihapus,
    // tidak bisa diubah -- salah ketik satu huruf pun berarti buat ulang.
    Route::put('/chatbot/{id}', [ChatbotRuleController::class, 'update'])->name('chatbot.update');
    Route::delete('/chatbot/{id}', [ChatbotRuleController::class, 'destroy'])->name('chatbot.destroy');
    Route::get('/chatbot/alur', [AlurChatbotController::class, 'index'])->name('chatbot.alur.index');
    Route::post('/chatbot/alur', [AlurChatbotController::class, 'storeAlur'])->name('chatbot.alur.store');
    Route::post('/chatbot/alur/{alur_id}/pilihan', [AlurChatbotController::class, 'storePilihan'])->name('chatbot.pilihan.store');
    Route::delete('/chatbot/alur/{id}', [AlurChatbotController::class, 'destroyAlur'])->name('chatbot.alur.destroy');

    // FITUR BARU (7 Agustus 2026): alur karir sebelumnya hanya bisa ditambah
    // dan dihapus seluruhnya. Satu tombol pilihan yang salah ketik memaksa
    // Guru BK membuang seluruh pertanyaan beserta pilihan lain yang sudah benar,
    // lalu menyusunnya ulang dari awal.
    Route::put('/chatbot/alur/{id}', [AlurChatbotController::class, 'updateAlur'])->name('chatbot.alur.update');
    Route::put('/chatbot/pilihan/{id}', [AlurChatbotController::class, 'updatePilihan'])->name('chatbot.pilihan.update');
    Route::delete('/chatbot/pilihan/{id}', [AlurChatbotController::class, 'destroyPilihan'])->name('chatbot.pilihan.destroy');
    
    // Verifikasi Data Ortu & Data Siswa
    Route::get('/verifikasi-ortu', [GuruBKDashboardController::class, 'verifikasiOrtuIndex'])->name('verifikasi.index');
    Route::post('/verifikasi-ortu/{id}/approve', [GuruBKDashboardController::class, 'approveOrtu'])->name('verifikasi.approve');
    Route::get('/data-siswa', [GuruBKDashboardController::class, 'dataSiswaIndex'])->name('data-siswa.index');
    Route::get('/data-siswa/{id}', [GuruBKDashboardController::class, 'dataSiswaShow'])->name('data-siswa.show');
    Route::post('/data-siswa/{id}/verify', [GuruBKDashboardController::class, 'approveOrtu'])->name('data-siswa.verify');
    // FITUR BARU (30 Juli 2026): Guru BK bisa mengoreksi langsung nama/No WA
    // orang tua (mis. nomor lama sudah tidak aktif dan siswa lapor ke Guru BK),
    // tanpa perlu menolak & memaksa siswa mengisi ulang dari awal.
    Route::put('/data-siswa/{id}/ortu', [GuruBKDashboardController::class, 'updateOrtu'])->name('data-siswa.update-ortu');

    // PERBAIKAN AUDIT (Poin 2): Pantau Absensi kelas binaan Guru BK
    Route::get('/absensi-pantau', [GuruBKAbsensiPantauController::class, 'index'])->name('absensi-pantau.index');
    // FITUR BARU (30 Juli 2026): unduh rekap absensi per kelas (PDF/Excel).
    Route::get('/absensi-pantau/pdf', [GuruBKAbsensiPantauController::class, 'pdf'])->name('absensi-pantau.pdf');
    Route::get('/absensi-pantau/excel', [GuruBKAbsensiPantauController::class, 'excel'])->name('absensi-pantau.excel');
    
    // Rekap Laporan & Jurnal (dibuat Guru BK setelah antrean sesi selesai dilayani)
    Route::get('/rekap-laporan', [JurnalLayananController::class, 'index'])->name('rekap.index');
    Route::post('/rekap-laporan/{antrian_id}/jurnal', [JurnalLayananController::class, 'storeJurnal'])->name('rekap.jurnal.store');
    Route::post('/rekap-laporan/jurnal-individu', [JurnalLayananController::class, 'storeManual'])->name('rekap.jurnal.store-manual');
    // PERBAIKAN: jurnal konseling yang sudah tersimpan sebelumnya tidak bisa
    // diedit/dihapus lagi lewat modal Detail -- dua route ini melengkapinya.
    Route::put('/rekap-laporan/jurnal/{id}', [JurnalLayananController::class, 'updateJurnal'])->name('rekap.jurnal.update');
    Route::delete('/rekap-laporan/jurnal/{id}', [JurnalLayananController::class, 'destroyJurnal'])->name('rekap.jurnal.destroy');
    Route::get('/rekap-laporan/siswa/{siswa}', [JurnalLayananController::class, 'riwayatSiswa'])->name('rekap.siswa');
    Route::post('/rekap-laporan/jurnal-harian', [JurnalLayananController::class, 'storeJurnalHarian'])->name('rekap.harian.store');
    Route::delete('/rekap-laporan/jurnal-harian/{id}', [JurnalLayananController::class, 'destroyJurnalHarian'])->name('rekap.harian.destroy');
    // PERBAIKAN (23 Juli 2026): tab Bimbingan Kelompok & Layanan Klasikal sebelumnya
    // cuma mockup tanpa route sama sekali sehingga tidak bisa disubmit.
    Route::post('/rekap-laporan/bimbingan-kelompok', [JurnalLayananController::class, 'storeBimbinganKelompok'])->name('rekap.kelompok.store');
    Route::delete('/rekap-laporan/bimbingan-kelompok/{id}', [JurnalLayananController::class, 'destroyBimbinganKelompok'])->name('rekap.kelompok.destroy');
    Route::post('/rekap-laporan/layanan-klasikal', [JurnalLayananController::class, 'storeLayananKlasikal'])->name('rekap.klasikal.store');
    Route::delete('/rekap-laporan/layanan-klasikal/{id}', [JurnalLayananController::class, 'destroyLayananKlasikal'])->name('rekap.klasikal.destroy');

    // PERBAIKAN (28 Juli 2026, revisi ke-3): "Data Pelanggaran" digabung ke
    // Jurnal BK (jenis_catatan = 'Pelanggaran' di jurnal_layanans).
    Route::post('/rekap-laporan/pelanggaran', [JurnalLayananController::class, 'storePelanggaran'])->name('rekap.pelanggaran.store');
    Route::put('/rekap-laporan/pelanggaran/{id}', [JurnalLayananController::class, 'updatePelanggaran'])->name('rekap.pelanggaran.update');
    Route::delete('/rekap-laporan/pelanggaran/{id}', [JurnalLayananController::class, 'destroyPelanggaran'])->name('rekap.pelanggaran.destroy');
    Route::prefix('sesi')->name('sesi.')->group(function () {
    Route::get('/', [SesiKonselingController::class,'index'])
        ->name('index');
    Route::post('/store', [SesiKonselingController::class,'store'])
        ->name('store');
    Route::post('/{id}/tutup', [SesiKonselingController::class,'tutup'])
        ->name('tutup');
    // Tombol Panggil Berikutnya
    Route::post('/{id}/berikutnya', [SesiKonselingController::class,'panggilBerikutnya'])
        ->name('berikutnya');
    Route::post('/{id}/batalkan', [SesiKonselingController::class,'batalkan'])
        ->name('batalkan');
    });

    // Pengajuan Program BK (ke Kepala Sekolah)
    Route::prefix('program-bk')->name('program-bk.')->group(function () {
        Route::get('/', [GuruBKProgramBkController::class, 'index'])->name('index');
        Route::post('/', [GuruBKProgramBkController::class, 'store'])->name('store');
        Route::put('/{id}', [GuruBKProgramBkController::class, 'update'])->name('update');
        Route::delete('/{id}', [GuruBKProgramBkController::class, 'destroy'])->name('destroy');
         Route::post('/{id}/tandai-selesai', [GuruBKProgramBkController::class, 'tandaiSelesai'])->name('tandai-selesai');
    });

    // FASE 8: Home Visit (Kunjungan Rumah). Tabnya ada di halaman Rekap
    // Laporan (rekap-laporan/index.blade.php, tab "Home Visit").
    Route::prefix('kunjungan-rumah')->name('kunjungan-rumah.')->group(function () {
        Route::post('/', [GuruBKKunjunganRumahController::class, 'store'])->name('store');
        Route::post('/{id}/terlaksana', [GuruBKKunjunganRumahController::class, 'tandaiTerlaksana'])->name('terlaksana');
        Route::post('/{id}/batalkan', [GuruBKKunjunganRumahController::class, 'batalkan'])->name('batalkan');
        // PERBAIKAN (23 Juli 2026): kolom Aksi Home Visit sebelumnya tidak
        // punya fitur Edit/Hapus sama sekali.
        Route::put('/{id}', [GuruBKKunjunganRumahController::class, 'update'])->name('update');
        Route::delete('/{id}', [GuruBKKunjunganRumahController::class, 'destroy'])->name('destroy');
    });

    // FASE 8: Kalender Kegiatan BK.
    Route::prefix('kegiatan-bk')->name('kegiatan-bk.')->group(function () {
        Route::get('/', [GuruBKKegiatanBkController::class, 'index'])->name('index');
        Route::post('/', [GuruBKKegiatanBkController::class, 'store'])->name('store');
        Route::put('/{id}', [GuruBKKegiatanBkController::class, 'update'])->name('update');
        Route::post('/{id}/status', [GuruBKKegiatanBkController::class, 'updateStatus'])->name('status');
        Route::delete('/{id}', [GuruBKKegiatanBkController::class, 'destroy'])->name('destroy');
    });

    // FITUR BARU 20 Juli 2026 (dulu Batasan Penelitian): Artikel Informasi BK
    Route::prefix('artikel-bk')->name('artikel-bk.')->group(function () {
        Route::get('/', [ArtikelBkController::class, 'index'])->name('index');
        Route::post('/', [ArtikelBkController::class, 'store'])->name('store');
        Route::put('/{id}', [ArtikelBkController::class, 'update'])->name('update');
        Route::delete('/{id}', [ArtikelBkController::class, 'destroy'])->name('destroy');
    });

    // FITUR BARU (29 Juli 2026): AKPD (Angket Kebutuhan Peserta Didik).
    Route::prefix('akpd')->name('akpd.')->group(function () {
        Route::get('/', [AkpdController::class, 'index'])->name('index');
        Route::post('/', [AkpdController::class, 'store'])->name('store');
        Route::put('/{id}', [AkpdController::class, 'update'])->name('update');
        Route::post('/{id}/toggle', [AkpdController::class, 'toggleActive'])->name('toggle');
        Route::delete('/{id}', [AkpdController::class, 'destroy'])->name('destroy');
    });

    // PERBAIKAN (28 Juli 2026, revisi ke-3): "Data Pelanggaran" (halaman
    // & tabel terpisah) sudah digabung ke halaman Jurnal BK / Rekap Laporan
    // (lihat rekap.pelanggaran.* di atas). Rute lama tetap ada supaya link
    // lama (bookmark, dsb) tidak 404 -- cuma diarahkan (redirect) ke
    // halaman Jurnal BK yang baru.
    Route::get('/pelanggaran', function () {
        return redirect()->route('gurubk.rekap.index');
    })->name('pelanggaran.index');

    // PERBAIKAN: sebelumnya belum ada rute untuk menandai notifikasi Guru BK
    // sebagai "sudah dibaca", sehingga notifikasi yang sudah diklik tetap
    // muncul terus di dropdown. Rute ini meniru pola yang sama dengan
    // notifikasi Siswa.
    Route::prefix('notifikasi')->name('notifikasi.')->group(function () {
        Route::get('/{id}/baca', [GuruBKNotifikasiController::class, 'baca'])->name('baca');
        Route::post('/baca-semua', [GuruBKNotifikasiController::class, 'bacaSemua'])->name('baca-semua');
    });
});


Route::middleware(['auth', 'role:Siswa', 'user.status', CheckClassConfirmation::class])->prefix('siswa')->name('siswa.')->group(function () {
    // Rute Dashboard Siswa
    Route::get('/dashboard', [SiswaDashboardController::class, 'index'])->name('dashboard');

    // Rute Chatbot Siswa
    Route::get('/chatbot', [SiswaChatbotController::class, 'index'])->name('chatbot.index');
    Route::get('/chatbot/alur', [SiswaChatbotController::class, 'getAlur'])->name('chatbot.alur');
    Route::post('/chatbot/send', [SiswaChatbotController::class, 'sendMessage'])->name('chatbot.send');
    // PERBAIKAN AUDIT (Bagian E, 22 Juli 2026): sapaan pembuka per kategori
    Route::get('/chatbot/kategori/{kategori}', [SiswaChatbotController::class, 'sapaanKategori'])->name('chatbot.kategori');
    
    // Rute Konfirmasi Kelas
    Route::get('/konfirmasi-kelas', [KonfirmasiKelasController::class, 'index'])->name('konfirmasi.index');
    Route::post('/konfirmasi-kelas', [KonfirmasiKelasController::class, 'store'])->name('konfirmasi.store');

    // =======================
    // SISTEM ANTREAN BARU
    // =======================

    Route::prefix('sesi')->name('sesi.')->group(function () {

        Route::get('/', [SiswaSesiController::class, 'index'])
            ->name('index');

        Route::post('/{id}/ambil', [SiswaSesiController::class, 'ambil'])
            ->name('ambil');

        Route::post('/{id}/batal', [SiswaSesiController::class, 'batal'])
            ->name('batal');

        // DAFTAR TUNGGU (6 Agustus 2026)
        // Dipakai saat Guru BK belum membuka sesi sama sekali -- misalnya siswa
        // membuka aplikasi malam hari setelah Chatbot BK menyarankan janji temu.
        // Permintaannya disimpan dulu, lalu otomatis jadi antrean bernomor
        // begitu Guru BK membuka sesi baru.
        Route::post('/daftar-tunggu', [SiswaSesiController::class, 'daftarTunggu'])
            ->name('daftar-tunggu');

        // Catatan: diletakkan SESUDAH /daftar-tunggu bukan tanpa sebab. Kalau
        // ditaruh sebelumnya, pola /{id}/batal-tunggu masih aman, tapi urutan
        // ini menjaga rute statis selalu didahulukan bila nanti ada penambahan.
        Route::post('/{id}/batal-tunggu', [SiswaSesiController::class, 'batalTunggu'])
            ->name('batal-tunggu');

    });

    // Rute Absensi QR Code
    Route::get('/absensi/scan/{token}', [AbsensiScanController::class, 'scan'])->name('absensi.scan');
    Route::get('/absensi/riwayat', [AbsensiScanController::class, 'riwayat'])->name('absensi.riwayat');

    // FITUR BARU 20 Juli 2026 (dulu Batasan Penelitian): Halaman Artikel Informasi BK
    Route::prefix('artikel')->name('artikel.')->group(function () {
        Route::get('/', [SiswaArtikelController::class, 'index'])->name('index');
        Route::get('/{slug}', [SiswaArtikelController::class, 'show'])->name('show');
    });

    // FITUR BARU (29 Juli 2026): AKPD (Angket Kebutuhan Peserta Didik).
    Route::prefix('akpd')->name('akpd.')->group(function () {
        Route::get('/', [SiswaAkpdController::class, 'index'])->name('index');
        Route::post('/', [SiswaAkpdController::class, 'store'])->name('store');
    });

    // PERBAIKAN (30 Juli 2026): rute "Konsultasi Jurusan" lama dihapus --
    // controllernya query JurnalLayanan::with('kampus'), padahal relasi
    // kampus() itu tidak pernah ada di model JurnalLayanan, dan kolom
    // jenis_konsultasi/kampus_id/minat_jurusan sudah dihapus total dari
    // tabel jurnal_layanans oleh migrasi
    // 2026_07_29_120000_hapus_fitur_konsultasi_jurusan_dan_alumni.php.
    // Diganti dengan halaman "Informasi Kampus" yang murni baca tabel
    // kampus, tidak menyentuh jurnal_layanans sama sekali.
    Route::prefix('informasi-kampus')->name('informasi-kampus.')->group(function () {
        Route::get('/', [InformasiKampusController::class, 'index'])->name('index');
    });

    // Rute Notifikasi Siswa
    Route::prefix('notifikasi')->name('notifikasi.')->group(function () {
        Route::get('/{id}/baca', [SiswaNotifikasiController::class, 'baca'])
            ->name('baca');
        Route::post('/baca-semua', [SiswaNotifikasiController::class, 'bacaSemua'])
            ->name('baca-semua');
    });

    // Rute Akun Dibatasi
    Route::get('/akun-dibatasi', function () {
        return view('siswa.dibatasi');
    })->name('dibatasi');
});

Route::middleware(['auth', 'role:Wali Kelas', 'user.status'])->group(function () {
    Route::get('/walikelas/dashboard', [WaliKelasDashboardController::class, 'index'])->name('walikelas.dashboard');

    // PERBAIKAN: sebelumnya belum ada rute untuk menandai notifikasi Wali Kelas
    // sebagai "sudah dibaca". Grup route ini tidak memakai prefix('walikelas')
    // & name('walikelas.') seperti grup lain, jadi nama rute ditulis lengkap
    // dengan awalan "walikelas." supaya konsisten dengan rute dashboard di atas.
    Route::prefix('walikelas/notifikasi')->name('walikelas.notifikasi.')->group(function () {
        Route::get('/{id}/baca', [WaliKelasNotifikasiController::class, 'baca'])->name('baca');
        Route::post('/baca-semua', [WaliKelasNotifikasiController::class, 'bacaSemua'])->name('baca-semua');
    });

    // FITUR BARU (9 Agustus 2026): halaman rekap absensi tersendiri untuk Wali
    // Kelas, lengkap dengan unduhan PDF & Excel. Sebelumnya rekap hanya berupa
    // kartu di dashboard dan tidak bisa dikeluarkan sebagai berkas.
    Route::prefix('walikelas/rekap-absensi')->name('walikelas.rekap-absensi.')->group(function () {
        Route::get('/', [WaliKelasRekapAbsensiController::class, 'index'])->name('index');
        Route::get('/pdf', [WaliKelasRekapAbsensiController::class, 'pdf'])->name('pdf');
        Route::get('/excel', [WaliKelasRekapAbsensiController::class, 'excel'])->name('excel');
    });
});

Route::middleware(['auth', 'role:Guru Mapel', 'user.status'])->prefix('gurumapel')->name('gurumapel.')->group(function () {
    Route::get('/dashboard', [GuruMapelDashboardController::class, 'index'])->name('dashboard');

    // Absensi QR Code
    Route::prefix('absensi')->name('absensi.')->group(function () {
        Route::get('/', [GuruMapelAbsensiController::class, 'index'])->name('index');
        Route::post('/', [GuruMapelAbsensiController::class, 'store'])->name('store');
        Route::get('/rekap', [GuruMapelAbsensiController::class, 'rekap'])->name('rekap');
        Route::get('/{id}', [GuruMapelAbsensiController::class, 'show'])->name('show');
        Route::get('/{id}/status', [GuruMapelAbsensiController::class, 'statusJson'])->name('status');
        Route::post('/{id}/tutup', [GuruMapelAbsensiController::class, 'tutup'])->name('tutup');
        Route::post('/{id}/manual', [GuruMapelAbsensiController::class, 'manualStore'])->name('manual');
    });

    // PERBAIKAN: sebelumnya belum ada rute untuk menandai notifikasi Guru
    // Mapel sebagai "sudah dibaca", sehingga notifikasi yang sudah diklik
    // tetap muncul terus di dropdown.
    Route::prefix('notifikasi')->name('notifikasi.')->group(function () {
        Route::get('/{id}/baca', [GuruMapelNotifikasiController::class, 'baca'])->name('baca');
        Route::post('/baca-semua', [GuruMapelNotifikasiController::class, 'bacaSemua'])->name('baca-semua');
    });
});

// ==========================================
// ROUTE PROFIL TERPADU (SEMUA ROLE KECUALI ADMIN)
// ==========================================
Route::middleware(['auth', 'role:Kepala Sekolah|Guru BK|Siswa|Wali Kelas|Guru Mapel', 'user.status'])->group(function () {
    Route::get('/pengaturan-profil', [UserProfileController::class, 'index'])->name('profil.umum.index');
    Route::post('/pengaturan-profil/info', [UserProfileController::class, 'updateInfo'])->name('profil.info.update');
    Route::post('/pengaturan-profil/foto', [UserProfileController::class, 'updateFoto'])->name('profil.foto.update');
    Route::post('/pengaturan-profil/password', [UserProfileController::class, 'updatePassword'])->name('profil.password.update');
    Route::post('/pengaturan-profil/parent', [UserProfileController::class, 'submitParentData'])->name('profil.parent.submit');
});

require __DIR__.'/auth.php';