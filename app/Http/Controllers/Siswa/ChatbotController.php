<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\ChatbotRule;
use App\Models\AlurChatbot;
use App\Models\LogAktivitas; // Import model LogAktivitas
use App\Models\Semester; // PERBAIKAN AUDIT (Bagian I, 22 Juli 2026): untuk kolom semester_id di log
use Illuminate\Support\Facades\Auth; // Import Auth untuk mendapatkan ID Siswa
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    /**
     * PERBAIKAN AUDIT (Poin 1 - Wajib):
     * Daftar kata kunci yang mengindikasikan siswa sedang bertanya soal
     * jurusan/karier/kuliah lewat chat bebas (Mode 2), padahal harusnya
     * diarahkan ke Mode 1 (Alur Jurusan & Karier) yang sudah terstruktur.
     * Sebelumnya Mode 1 & Mode 2 berjalan terpisah tanpa saling terhubung.
     */
    protected array $keywordJurusan = [
        'jurusan',
        'kuliah',
        'universitas',
        'kampus',
        'prodi',
        'program studi',
        'karier',
        'karir',
        'cita-cita',
        'cita cita',
        'fakultas',
        'snbp',
        'snbt',
        'ptn',
        'pts',
        'beasiswa',
        'mau jadi apa',
        'lanjut kuliah',
    ];

    /**
     * FITUR BARU (30 Juli 2026): kata kunci sapaan. Kalau siswa mengetik salah
     * satu dari ini, chatbot balas sapaan yang menyebut nama siswa langsung,
     * bukan langsung dicek ke tabel chatbot_rules / fallback "tidak mengerti".
     * Key = kata kunci yang dicari, value = kata sapaan yang dipakai di respons.
     */
    protected array $keywordSapaan = [
        'pagi'  => 'Selamat pagi',
        'siang' => 'Selamat siang',
        'sore'  => 'Selamat sore',
        'malam' => 'Selamat malam',
        'hai'   => 'Hai',
        'halo'  => 'Halo',
        'hallo' => 'Halo',
        'hi'    => 'Hai',
        'hello' => 'Halo',
        'assalamualaikum' => 'Waalaikumsalam',
    ];

    /**
     * PERBAIKAN AUDIT (Bagian E - Halaman Chatbot, 22 Juli 2026):
     * Sebelumnya tampilan awal cuma punya 1 tombol ("Mulai Panduan Jurusan"),
     * padahal spec outline minta 5 tombol kategori dengan sapaan pembuka
     * yang beda-beda per kategori (Karier sudah ditangani lewat Mode 1 /
     * loadAlur(), jadi 4 kategori sisanya untuk Mode 2 chat bebas didaftarkan
     * di sini). Key di array = slug yang dipakai di URL
     * (siswa.chatbot.kategori), db_category = nilai kolom `category` di
     * tabel chatbot_rules yang dipetakan ke kategori ini.
     */
    protected const PETA_KATEGORI = [
        'belajar' => [
            'label' => 'Belajar / Akademik',
            'db_category' => 'akademik',
            'sapaan' => "Yuk cerita soal urusan belajar atau akademikmu — jadwal, nilai, tugas, atau kesulitan belajar tertentu. Ada topik yang mau ditanyakan?",
        ],
        'sosial' => [
            'label' => 'Sosial',
            'db_category' => 'sosial',
            'sapaan' => "Ada masalah pertemanan, konflik dengan teman, atau hal lain soal kehidupan sosial di sekolah? Cerita aja, aku bantu carikan solusinya.",
        ],
        'pribadi' => [
            'label' => 'Pribadi',
            'db_category' => 'pribadi',
            'sapaan' => "Ini ruang aman buat cerita hal-hal pribadi — perasaan, masalah keluarga, atau apapun yang lagi kamu pikirkan. Silakan ceritakan, ya.",
        ],
        'lainnya' => [
            'label' => 'Lainnya',
            'db_category' => 'umum',
            'sapaan' => "Oke, kamu bisa tanya apa saja di luar kategori Belajar, Sosial, atau Pribadi. Ketik langsung pertanyaanmu, ya.",
        ],
    ];

    // ==================================================
    // 1. FUNGSI MENAMPILKAN HALAMAN CHATBOT
    // ==================================================
    public function index()
    {
        return view('siswa.chatbot.index');
    }

    // ==================================================
    // 1b. FUNGSI SAPAAN PEMBUKA PER KATEGORI (PERBAIKAN AUDIT Bagian E)
    // ==================================================
    public function sapaanKategori(string $kategori)
    {
        $kategori = strtolower($kategori);

        if (!array_key_exists($kategori, self::PETA_KATEGORI)) {
            return response()->json(['error' => 'Kategori tidak dikenal'], 404);
        }

        $meta = self::PETA_KATEGORI[$kategori];

        // Ambil topik (rule level atas, parent_id kosong) yang sudah
        // dikategorikan Guru BK sesuai kategori ini, untuk ditampilkan
        // sebagai tombol pilihan lanjutan -- reuse alur options yang sama
        // persis seperti tombol "options" di sendMessage(), supaya siswa
        // tinggal klik lalu tetap lewat kirimPesanKeServer() di frontend.
        $topikList = ChatbotRule::where('category', $meta['db_category'])
            ->whereNull('parent_id')
            ->get(['id', 'keyword']);

        // PERBAIKAN AUDIT (Bagian I, 22 Juli 2026): pemilihan kategori juga
        // dianggap 1 percakapan, dicatat lengkap dengan kategori & sapaan-nya.
        $this->catatLog($meta['label'], $meta['db_category'], $meta['sapaan']);

        return response()->json([
            'label'  => $meta['label'],
            'sapaan' => $meta['sapaan'],
            'topik'  => $topikList,
        ]);
    }

    // ==================================================
    // PERBAIKAN AUDIT (Bagian I - Chatbot Rule-Based Engine, 22 Juli 2026):
    // Sebelumnya log_aktivitas cuma mencatat 1 baris generik per siswa per
    // hari (anti-spam), tanpa detail kategori/topik/respons/semester per
    // percakapan -- tidak sesuai spec outline ("Semua percakapan tercatat
    // di log: siswa, waktu, kategori, topik, respons, semester").
    // Helper ini menggantikan mekanisme lama: SETIAP kali chatbot membalas
    // (baik lewat rule, redirect jurusan, fallback, maupun pemilihan
    // kategori), 1 baris log baru dibuat dengan detail lengkap.
    // ==================================================
    private function catatLog(string $topik, ?string $kategori, string $respons): void
    {
        $semesterAktifId = Semester::where('status_aktif', true)->value('id');

        LogAktivitas::create([
            'user_id'     => Auth::id(),
            'aktivitas'   => 'melakukan percakapan dengan Chatbot',
            'kategori'    => $kategori,
            'topik'       => Str::limit($topik, 250, ''),
            'respons'     => $respons,
            'semester_id' => $semesterAktifId,
        ]);
    }

    // ==================================================
    // 2. FUNGSI MEMPROSES PESAN CHAT & OPSI TOMBOL
    // ==================================================
    public function sendMessage(Request $request)
    {
        // PERBAIKAN (AUDIT): 'message' sebelumnya dipakai langsung tanpa
        // divalidasi. Kalau klien mengirim array atau tidak mengirim field ini
        // sama sekali, trim() melempar TypeError -- dan TypeError itu turunan
        // Error, BUKAN Exception, jadi lolos dari blok catch di bawah dan
        // berujung error 500 mentah. Validasi di depan menutup celah itu
        // sekaligus membatasi panjang pesan.
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        try {
            $input = strtolower(trim($validated['message']));

            // --- FITUR BARU (30 Juli 2026): deteksi sapaan -> balas sebut nama siswa ---
            // Dibatasi maksimal 4 kata supaya pesan panjang yang cuma kebetulan
            // mengandung kata "pagi"/"sore" dsb (misal "pagi ini aku ada tugas
            // menumpuk") tidak salah dianggap sapaan biasa.
            if (str_word_count($input) <= 4) {
                foreach ($this->keywordSapaan as $kw => $sapaan) {
                    if (str_contains($input, $kw)) {
                        $nama = Auth::user()->name ?? 'Kamu';
                        $namaDepan = trim(Str::before($nama, ' ')) ?: $nama;

                        $responsSapaan = "{$sapaan}, {$namaDepan}! Aku Asisten BK SMA Kartika I-5 Padang. "
                            . "Ada yang bisa aku bantu hari ini? Kamu bisa cerita langsung, atau pilih salah satu topik di atas (Karier & Jurusan, Belajar, Sosial, Pribadi, Lainnya).";

                        $this->catatLog($input, 'sapaan', $responsSapaan);

                        return response()->json([
                            'response' => $responsSapaan,
                            'options'  => [],
                        ]);
                    }
                }
            }

            // PERBAIKAN (AUDIT): karakter '%' dan '_' punya arti khusus di
            // klausa LIKE. Tanpa di-escape, siswa yang mengetik "%" saja akan
            // mencocoki SEMUA rule dan chatbot membalas dengan rule pertama
            // yang kebetulan ada di database. Di-escape supaya diperlakukan
            // sebagai teks biasa.
            $inputLike = addcslashes($input, '%_\\');
            $rule = ChatbotRule::where('keyword', 'LIKE', "%{$inputLike}%")->first();

            if ($rule) {
                $options = ChatbotRule::where('parent_id', $rule->id)->get();

                // PERBAIKAN AUDIT (Bagian I, 22 Juli 2026): catat percakapan
                // dengan kategori dari rule yang cocok (kolom category di chatbot_rules).
                $this->catatLog($rule->keyword, $rule->category, $rule->response);

                return response()->json([
                    'response' => $rule->response,
                    'options'  => $options,
                ]);
            }

            // --- PERBAIKAN AUDIT: Deteksi keyword jurusan -> redirect Mode 1 ---
            foreach ($this->keywordJurusan as $kw) {
                if (str_contains($input, $kw)) {
                    $responsRedirect = "Sepertinya kamu ingin tahu soal jurusan, kuliah, atau karier ya? "
                        . "Untuk itu, lebih baik pakai fitur Panduan Jurusan supaya rekomendasinya lebih terarah.";

                    // PERBAIKAN AUDIT (Bagian I, 22 Juli 2026): kategori 'karier'
                    // dipakai khusus untuk redirect ke Mode 1 (tidak ada di kolom
                    // category chatbot_rules karena alur jurusan tabelnya terpisah).
                    $this->catatLog($input, 'karier', $responsRedirect);

                    return response()->json([
                        'response' => $responsRedirect,
                        'options'      => [],
                        'redirect_alur' => true, // frontend akan menampilkan tombol "Mulai Panduan Jurusan"
                    ]);
                }
            }

            // --- PERBAIKAN AUDIT: Fallback -> tawarkan booking konsultasi ---
            $responsFallback = "Maaf, saya tidak mengerti maksud kamu. Coba gunakan kata kunci lain, "
                . "atau kalau butuh bantuan langsung, kamu bisa booking konsultasi dengan Guru BK.";

            // PERBAIKAN AUDIT (Bagian I, 22 Juli 2026): kategori null karena
            // pesan ini tidak cocok dengan kategori/rule manapun (unclassified).
            $this->catatLog($input, null, $responsFallback);

            return response()->json([
                'response' => $responsFallback,
                'options'       => [],
                'offer_booking' => true, // frontend akan menampilkan tombol "Booking Konsultasi"
            ]);

        } catch (\Exception $e) {
            // PERBAIKAN KEAMANAN (30 Juli 2026): sebelumnya pesan exception
            // asli ($e->getMessage()) dikirim langsung ke response JSON --
            // ini bisa membocorkan detail internal server (nama tabel/kolom
            // database, path file, dsb) ke siapa pun yang melihat response
            // di browser. Detail errornya sekarang dicatat ke log server
            // saja, dan yang dikirim ke klien cuma pesan generik.
            Log::error('Chatbot: gagal memproses pesan siswa.', [
                'user_id' => Auth::id(),
                'pesan' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan pada server. Silakan coba lagi beberapa saat lagi.'
            ], 500);
        }
    }

    // ==================================================
    // 3. FUNGSI MENGAMBIL DATA ALUR (dipakai frontend, route: siswa.chatbot.alur)
    // ==================================================
    public function getAlur()
    {
        try {
            $alur = AlurChatbot::with(['pilihan', 'pilihan.kampus'])->get();

            return response()->json([
                'data' => $alur
            ]);
        } catch (\Exception $e) {
            return response()->json(['data' => []]);
        }
    }

    // CATATAN AUDIT: method alur() yang lama dihapus.
    // Method itu selalu mengembalikan array kosong ('data' => []) dan
    // TIDAK terdaftar di routes/web.php (rute /chatbot/alur memanggil getAlur(),
    // bukan alur()) -- jadi itu murni dead code, bukan fitur yang dipakai.
}