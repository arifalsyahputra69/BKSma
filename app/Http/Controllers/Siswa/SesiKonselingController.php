<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\AntrianKonseling;
use App\Models\Notifikasi;
use App\Models\PermintaanKonseling;
use App\Models\SesiKonseling;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SesiKonselingController extends Controller
{
    /**
     * Halaman utama siswa
     */
    public function index(Request $request)
    {
        // ============================================================
        // PINTU MASUK DAFTAR TUNGGU (7 Agustus 2026)
        // ============================================================
        // Pendaftaran di luar sesi hanya ditawarkan kepada siswa yang tiba
        // lewat tombol "Booking Konsultasi" di Chatbot BK -- yaitu ketika
        // chatbot sudah mencoba membantu dan tidak sanggup. Kalau siswa
        // membuka menu Janji Temu langsung dari sidebar, formnya tidak
        // ditampilkan supaya tidak jadi jalan pintas bagi semua orang.
        //
        // Izinnya disimpan di session, bukan sekadar dibaca dari query string,
        // karena halaman ini menyegarkan dirinya sendiri tiap 15 detik saat
        // ada antrean berjalan dan juga kembali ke sini setelah form dikirim.
        // Kalau hanya mengandalkan ?dari=chatbot, formnya akan lenyap di
        // tengah jalan tepat saat siswa sedang mengisinya.
        // Membuka menu Janji Temu langsung dari sidebar SELALU menghapus izin
        // yang tersisa. Tanpa penghapusan ini, siswa yang sekali saja pernah
        // lewat chatbot akan terus melihat form itu setiap kali membuka menu
        // Janji Temu -- persis jalan pintas yang ingin ditutup.
        if ($request->query('dari') === 'chatbot') {
            session(['boleh_daftar_tunggu' => true]);
        } else {
            session()->forget('boleh_daftar_tunggu');
        }

        $bolehDaftarTunggu = (bool) session('boleh_daftar_tunggu', false);

        $siswa = Siswa::with('kelas')
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $guruBkId = $siswa->kelas->id_guru_bk ?? null;

        $sesis = collect();

        if ($guruBkId) {

    $sesis = SesiKonseling::with('antrians')

        ->where('guru_bk_id', $guruBkId)

        ->whereIn('status', [
            'Dibuka',
            'Ditutup',
            'Dibatalkan'
        ])

        ->whereDate('tanggal', '>=', Carbon::today())

        ->orderBy('tanggal')

        ->get();

}

        $antrianSaya = AntrianKonseling::with('sesi')
            ->where('siswa_id', $siswa->id)
            ->latest()
            ->get();

        // ============================================================
        // DAFTAR TUNGGU (6 Agustus 2026)
        // ============================================================
        // Pertanyaannya bukan "apakah sekarang jam sekolah", melainkan
        // "apakah ada sesi yang sedang menerima antrean". Guru BK bisa saja
        // tidak membuka sesi di jam sekolah, atau justru membuka di luar jam
        // biasa -- jadi keberadaan sesi terbukalah yang menentukan, bukan jam
        // dinding. Dengan begitu tidak ada tabel jam layanan yang harus
        // dirawat dan tidak ada yang meleset saat jadwal berubah atau libur.
        //
        // PERBAIKAN (7 Agustus 2026): tanggal WAJIB ikut diperiksa, bukan hanya
        // status. Versi pertama hanya melihat status = 'Dibuka', sehingga sesi
        // kemarin yang lupa ditutup Guru BK selamanya dianggap "masih terbuka".
        // Akibatnya siswa terjebak: sesi itu tidak muncul di daftar jadwal
        // (karena $sesis menyaring tanggal hari ini ke depan) sehingga tidak
        // ada yang bisa diantre, tapi form daftar tunggu juga ikut disembunyikan
        // karena sistem mengira sesi masih berjalan. Batas tanggalnya kini
        // disamakan dengan $sesis supaya keduanya tidak pernah berbeda pendapat.
        $adaSesiTerbuka = $guruBkId
            ? SesiKonseling::where('guru_bk_id', $guruBkId)
                ->where('status', 'Dibuka')
                ->whereDate('tanggal', '>=', Carbon::today())
                ->exists()
            : false;

        // Rapikan permintaan siswa ini yang sudah lewat masa berlaku sebelum
        // ditampilkan, supaya ia tidak melihat status "Menunggu" untuk sesuatu
        // yang sebenarnya sudah hangus.
        PermintaanKonseling::tandaiYangKedaluwarsa(
            PermintaanKonseling::where('siswa_id', $siswa->id)
        );

        $permintaanSaya = PermintaanKonseling::where('siswa_id', $siswa->id)
            ->where('status', 'Menunggu')
            ->latest()
            ->get();

        // Dipakai view untuk mengecek "apakah saya sudah mengantre di sesi ini",
        // per sesi. Dikirim eksplisit supaya view tidak perlu menebaknya dari
        // baris antrean mana pun yang kebetulan ada.
        $siswaId = $siswa->id;

        return view(
            'siswa.sesi.index',
            compact(
                'sesis',
                'antrianSaya',
                'adaSesiTerbuka',
                'permintaanSaya',
                'siswaId',
                'bolehDaftarTunggu'
            )
        );
    }

    /**
     * Siswa mendaftar ke daftar tunggu saat belum ada sesi yang dibuka.
     *
     * Permintaan ini belum berupa antrean bernomor -- nomornya baru diberikan
     * saat Guru BK membuka sesi, lewat konversi otomatis di
     * GuruBK\SesiKonselingController::store().
     */
    public function daftarTunggu(Request $request)
    {
        $request->validate([
            'keperluan' => 'required|string|max:255',
        ], [
            'keperluan.required' => 'Ceritakan dulu keperluan konselingmu.',
        ]);

        // Penjagaan yang sama seperti di index(): pendaftaran di luar sesi hanya
        // untuk siswa yang datang lewat Chatbot BK. Diperiksa lagi di sini,
        // bukan cukup disembunyikan di tampilan, supaya aturannya benar-benar
        // berlaku dan bukan sekadar formnya tidak terlihat.
        if (! session('boleh_daftar_tunggu', false)) {
            return back()->with(
                'error',
                'Pendaftaran konseling di luar sesi dilakukan lewat Chatbot BK. ' .
                'Ceritakan dulu keperluanmu di sana, ya.'
            );
        }

        $siswa = Siswa::where('user_id', Auth::id())->firstOrFail();

        $guruBkId = $siswa->kelas->id_guru_bk ?? null;

        if (! $guruBkId) {
            return back()->with(
                'error',
                'Kelasmu belum terhubung ke Guru BK. Silakan hubungi TU/Admin.'
            );
        }

        // Kalau ternyata Guru BK baru saja membuka sesi (misalnya siswa membuka
        // halaman ini sejak tadi lalu menekan tombolnya sekarang), arahkan ke
        // jalur normal supaya ia tidak menunggu tanpa alasan.
        // Batasan tanggalnya harus sama persis dengan yang dipakai index(),
        // kalau tidak siswa bisa melihat formnya tapi ditolak saat mengirim.
        $adaSesiTerbuka = SesiKonseling::where('guru_bk_id', $guruBkId)
            ->where('status', 'Dibuka')
            ->whereDate('tanggal', '>=', Carbon::today())
            ->exists();

        if ($adaSesiTerbuka) {
            return back()->with(
                'error',
                'Guru BK sedang membuka sesi konseling. Silakan langsung ambil nomor antrean di halaman ini.'
            );
        }

        // Satu siswa cukup satu permintaan yang menunggu.
        $sudahAda = PermintaanKonseling::where('siswa_id', $siswa->id)
            ->aktif()
            ->exists();

        if ($sudahAda) {
            return back()->with(
                'error',
                'Kamu sudah terdaftar di daftar tunggu. Tunggu sampai Guru BK membuka sesi, ya.'
            );
        }

        $permintaan = PermintaanKonseling::create([
            'siswa_id'   => $siswa->id,
            'guru_bk_id' => $guruBkId,
            'keperluan'  => $request->keperluan,
            'status'     => 'Menunggu',
        ]);

        // Beri tahu Guru BK. Notifikasi dalam aplikasi saja -- tidak lewat
        // WhatsApp, karena permintaan seperti ini justru sering masuk malam
        // hari dan tidak perlu membangunkan siapa pun.
        try {
            Notifikasi::create([
                'user_id' => $guruBkId,
                'judul'   => 'Permintaan Konseling Baru',
                'pesan'   =>
                    ($siswa->user->name ?? 'Seorang siswa') .
                    ' (' . ($siswa->kelas->nama_kelas ?? 'kelas belum diatur') . ') ' .
                    "mendaftar konseling saat belum ada sesi yang dibuka.\n\n" .
                    "Keperluan:\n" . $request->keperluan . "\n\n" .
                    'Ia akan otomatis mendapat nomor antrean begitu Anda membuka sesi baru.',
                'link'    => route('gurubk.sesi.index'),
                'is_read' => false,
            ]);
        } catch (\Throwable $e) {
            // Notifikasi gagal bukan alasan untuk membatalkan pendaftaran
            // siswa -- permintaannya sudah tersimpan dan tetap akan dialihkan.
            \Illuminate\Support\Facades\Log::error(
                'Gagal membuat notifikasi permintaan konseling: ' . $e->getMessage()
            );
        }

        // Izinnya dipakai sekali. Kalau siswa ingin mendaftar lagi setelah ini,
        // ia melewati chatbot lagi -- sama seperti pertama kali.
        session()->forget('boleh_daftar_tunggu');

        $batas = $permintaan->kedaluwarsaPada();

        return back()->with(
            'success',
            'Kamu sudah masuk daftar tunggu. Begitu Guru BK membuka sesi, nomor antreanmu ' .
            'muncul otomatis tanpa perlu mendaftar ulang' .
            ($batas ? ' (berlaku sampai ' . $batas->translatedFormat('d F Y') . ').' : '.')
        );
    }

    /**
     * Siswa membatalkan permintaannya sendiri selagi masih menunggu.
     */
    public function batalTunggu($id)
    {
        $siswa = Siswa::where('user_id', Auth::id())->firstOrFail();

        $permintaan = PermintaanKonseling::where('id', $id)
            ->where('siswa_id', $siswa->id)
            ->firstOrFail();

        if ($permintaan->status !== 'Menunggu') {
            return back()->with(
                'error',
                'Permintaan ini sudah tidak bisa dibatalkan.'
            );
        }

        $permintaan->update(['status' => 'Dibatalkan']);

        return back()->with(
            'success',
            'Permintaan konseling dibatalkan.'
        );
    }

    /**
     * Ambil nomor antrean
     */
    public function ambil(Request $request, $id)
    {
        $request->validate([
            'keperluan' => 'required|string|max:255',
        ]);

        $siswa = Siswa::where('user_id', Auth::id())->firstOrFail();

        $guruBkId = $siswa->kelas->id_guru_bk ?? null;

        if (! $guruBkId) {
            return back()->with(
                'error',
                'Kelasmu belum terhubung ke Guru BK. Silakan hubungi TU/Admin.'
            );
        }

        $sesiCek = SesiKonseling::where('guru_bk_id', $guruBkId)->findOrFail($id);
        if ($sesiCek->status != 'Dibuka') {

            return back()->with(
                'error',
                'Sesi konseling ini sudah tidak menerima antrean.'
            );

}

        // PERBAIKAN (30 Juli 2026): sebelumnya nomor antrean dihitung dengan
        // max('nomor_antrian') + 1 di luar transaction/lock. Kalau dua siswa
        // menekan "Ambil Antrean" nyaris bersamaan, keduanya bisa membaca
        // nilai max yang sama sebelum salah satu sempat menyimpan --
        // menghasilkan dua antrean dengan nomor yang sama (race condition).
        // Sekarang seluruh proses cek + hitung nomor + simpan dibungkus
        // dalam satu database transaction dengan row lock (lockForUpdate) di
        // baris sesi, supaya permintaan yang datang bersamaan untuk sesi yang
        // sama diproses berurutan (serial), bukan paralel.
        $nomor = DB::transaction(function () use ($id, $siswa, $request) {
            // Kunci baris sesi ini -- request lain untuk sesi yang sama akan
            // menunggu sampai transaction ini selesai.
            $sesi = SesiKonseling::where('id', $id)->lockForUpdate()->firstOrFail();

            if ($sesi->status != 'Dibuka') {
                abort(back()->with(
                    'error',
                    'Sesi konseling ini sudah tidak menerima antrean.'
                ));
            }

            /*
            Cegah siswa mengambil nomor dua kali
            */
            $cek = AntrianKonseling::where('sesi_konseling_id', $id)
                ->where('siswa_id', $siswa->id)
                ->whereIn('status', [
                    'Menunggu',
                    'Dipanggil'
                ])
                ->first();

            if ($cek) {
                abort(back()->with(
                    'error',
                    'Kamu sudah memiliki nomor antrean pada sesi ini.'
                ));
            }

            /*
            Nomor antrean otomatis.
            PERBAIKAN (27 Juli 2026): antrean berstatus 'Batal' dikecualikan
            dari perhitungan nomor terbesar, supaya nomor antrean mengikuti
            antrean yang masih aktif saja. Sebelumnya nomor yang sudah
            dibatalkan tetap "menempel" dan membuat nomor berikutnya terus
            melompat walau tidak ada siswa lain yang mengantre.
            */
            $nomorTerbesar = AntrianKonseling::where('sesi_konseling_id', $id)
                ->where('status', '!=', 'Batal')
                ->lockForUpdate()
                ->max('nomor_antrian');

            $nomor = $nomorTerbesar ? $nomorTerbesar + 1 : 1;

            AntrianKonseling::create([
                'sesi_konseling_id' => $id,
                'siswa_id' => $siswa->id,
                'nomor_antrian' => $nomor,
                'keperluan' => $request->keperluan,
                'status' => 'Menunggu',
                'waktu_booking' => now(),
            ]);

            return $nomor;
        });

        return back()->with(
            'success',
            'Berhasil mengambil nomor antrean ' . $nomor
        );
    }

    /**
     * Batalkan antrean
     */
    public function batal($id)
    {
        $siswa = Siswa::where('user_id', Auth::id())->firstOrFail();

        $antrian = AntrianKonseling::where('id', $id)
            ->where('siswa_id', $siswa->id)
            ->firstOrFail();

        if ($antrian->status != 'Menunggu' || $antrian->sesi->status != 'Dibuka') {
            return back()->with(
                'error',
                'Antrean sudah dipanggil sehingga tidak bisa dibatalkan.'
            );
        }

        $antrian->update([
            'status' => 'Batal'
        ]);

        return back()->with(
            'success',
            'Antrean berhasil dibatalkan.'
        );
    }
}