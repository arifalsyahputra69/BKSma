<?php

namespace App\Console\Commands;

use App\Models\AlurChatbot;
use App\Models\PilihanAlurChatbot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * ISI AWAL ALUR PANDUAN JURUSAN & KARIER (13 Agustus 2026).
 *
 * Pasangan dari SeedChatbotRulesBk -- kalau itu mengisi tabel chatbot_rules
 * (Mode 2 / chat bebas per kategori: Belajar, Sosial, Pribadi, Lainnya),
 * command ini mengisi tabel alur_chatbot + pilihan_alur_chatbot (Mode 1,
 * tombol "Karier & Jurusan" -> loadAlur() di siswa/chatbot/index.blade.php).
 *
 * CATATAN PENTING soal cara kerja Mode 1 (dibaca dari ChatbotController::
 * getAlur() dan JS loadAlur()): SEMUA baris alur_chatbot ditarik sekaligus
 * dan ditampilkan berurutan sebagai pertanyaan reflektif mandiri -- bukan
 * alur bertingkat (klik pilihan tidak memuat pertanyaan lanjutan, cuma
 * menampilkan rekomendasi jurusan untuk pilihan itu). Karena itu di sini
 * dirancang sebagai beberapa pertanyaan minat/bakat yang saling melengkapi
 * (kegiatan disukai, waktu luang, mapel favorit, gambaran karier), supaya
 * siswa dapat gambaran dari beberapa sudut sekaligus dalam satu sesi.
 *
 * `tag_kampus` diisi nama kampus di Sumatera Barat yang relevan dengan
 * rumpun jurusan tersebut, sekadar contoh arah -- BUKAN klaim kerja sama
 * resmi sekolah dengan kampus tsb. `kampus_id` sengaja dibiarkan kosong
 * (tidak diisi otomatis) karena data master Kampus dikelola manual oleh TU
 * dan isinya bisa berbeda-beda di tiap server; Guru BK bisa mengaitkannya
 * sendiri lewat menu Alur Chatbot kalau datanya sudah ada.
 *
 * Jalankan sekali:  php artisan chatbot:seed-alur-karier
 * Aman diulang -- pertanyaan yang teksnya sudah ada dilewati (tidak dibuat
 * dobel), begitu juga pilihan yang teksnya sudah ada di bawah pertanyaan
 * yang sama.
 */
class SeedAlurKarier extends Command
{
    protected $signature = 'chatbot:seed-alur-karier';

    protected $description = 'Isi set awal pertanyaan & pilihan Alur Panduan Jurusan/Karier (tombol Karier & Jurusan di chatbot siswa)';

    private function data(): array
    {
        return [
            [
                'pertanyaan' => 'Kamu lebih suka kegiatan yang seperti apa?',
                'pilihan' => [
                    [
                        'teks_pilihan' => 'Menghitung & memecahkan soal logika',
                        'respons' => 'Kamu mungkin cocok di rumpun Sains & Teknik — misalnya Matematika, Statistika, Teknik, atau Akuntansi/Ekonomi. Jurusan-jurusan ini butuh ketelitian dan senang memecahkan masalah, mirip seperti yang kamu sukai. Kalau mau tahu lebih detail soal prospeknya, yuk diskusi lebih lanjut dengan Guru BK.',
                        'tag_kampus' => 'Universitas Andalas (UNAND)',
                    ],
                    [
                        'teks_pilihan' => 'Menggambar & berkreasi visual',
                        'respons' => 'Bakat visualmu bisa berkembang di jurusan seperti Desain Komunikasi Visual, Arsitektur, atau Seni Rupa. Bidang ini menggabungkan kreativitas dengan keterampilan teknis. Kalau tertarik, ceritakan lebih lanjut ke Guru BK supaya bisa dibantu eksplorasi pilihannya.',
                        'tag_kampus' => 'Institut Seni Indonesia Padang Panjang (ISI Padang Panjang)',
                    ],
                    [
                        'teks_pilihan' => 'Berbicara & meyakinkan orang lain',
                        'respons' => 'Kemampuan komunikasimu bisa jadi modal besar di jurusan seperti Ilmu Komunikasi, Hubungan Internasional, atau Hukum. Bidang-bidang ini cocok buat kamu yang senang berdiskusi dan meyakinkan orang lain. Yuk obrolkan lebih lanjut dengan Guru BK untuk melihat pilihan yang paling sesuai.',
                        'tag_kampus' => 'Universitas Negeri Padang (UNP)',
                    ],
                    [
                        'teks_pilihan' => 'Merawat & membantu orang lain',
                        'respons' => 'Kepedulianmu terhadap orang lain bisa tersalur di jurusan seperti Kedokteran, Keperawatan, Kebidanan, atau Psikologi. Bidang ini memang menuntut kesabaran, tapi dampaknya besar buat orang lain. Diskusikan lebih lanjut dengan Guru BK untuk tahu jalur dan persiapannya.',
                        'tag_kampus' => 'Universitas Andalas (UNAND)',
                    ],
                ],
            ],
            [
                'pertanyaan' => 'Kalau ada waktu luang, kamu paling sering menghabiskannya untuk...?',
                'pilihan' => [
                    [
                        'teks_pilihan' => 'Otak-atik komputer, gadget, atau coding',
                        'respons' => 'Ketertarikanmu pada teknologi cocok diarahkan ke Ilmu Komputer, Teknik Informatika, atau Sistem Informasi. Bidang ini berkembang pesat dan banyak dicari di dunia kerja saat ini. Yuk cerita lebih lanjut ke Guru BK untuk tahu persiapan yang perlu disiapkan.',
                        'tag_kampus' => 'Politeknik Negeri Padang (PNP)',
                    ],
                    [
                        'teks_pilihan' => 'Membaca buku & menulis cerita',
                        'respons' => 'Kesukaanmu pada bahasa dan cerita bisa berkembang di jurusan Sastra, Jurnalistik, atau Ilmu Komunikasi. Bidang ini cocok buat kamu yang senang mengolah kata dan ide. Diskusikan lebih lanjut dengan Guru BK untuk eksplorasi pilihannya.',
                        'tag_kampus' => 'Universitas Negeri Padang (UNP)',
                    ],
                    [
                        'teks_pilihan' => 'Olahraga & aktivitas fisik',
                        'respons' => 'Minatmu pada olahraga bisa diarahkan ke Pendidikan Jasmani, Ilmu Keolahragaan, atau Fisioterapi. Bidang ini cocok buat kamu yang aktif dan senang bergerak. Yuk obrolkan lebih lanjut dengan Guru BK untuk tahu jalur dan peluangnya.',
                        'tag_kampus' => 'Universitas Negeri Padang (UNP)',
                    ],
                    [
                        'teks_pilihan' => 'Berorganisasi & mengatur kegiatan',
                        'respons' => 'Kemampuan mengaturmu bisa berkembang di jurusan Manajemen, Administrasi Publik, atau Ilmu Pemerintahan. Bidang ini cocok buat kamu yang senang memimpin dan mengorganisir. Diskusikan lebih lanjut dengan Guru BK untuk melihat pilihan yang sesuai.',
                        'tag_kampus' => 'Universitas Andalas (UNAND)',
                    ],
                ],
            ],
            [
                'pertanyaan' => 'Mata pelajaran apa yang paling kamu nikmati di sekolah?',
                'pilihan' => [
                    [
                        'teks_pilihan' => 'Matematika & Fisika',
                        'respons' => 'Kesukaanmu pada Matematika dan Fisika cocok diarahkan ke jurusan Teknik (Sipil, Mesin, Elektro, Informatika) atau Ilmu Komputer. Bidang ini membutuhkan logika kuat yang sudah kamu latih di sekolah. Yuk diskusikan lebih lanjut dengan Guru BK.',
                        'tag_kampus' => 'Institut Teknologi Padang (ITP)',
                    ],
                    [
                        'teks_pilihan' => 'Biologi & Kimia',
                        'respons' => 'Ketertarikanmu pada Biologi dan Kimia bisa mengarah ke Kedokteran, Farmasi, Gizi, atau Pertanian. Bidang-bidang ini berkaitan erat dengan sains kehidupan yang kamu sukai. Diskusikan lebih lanjut dengan Guru BK untuk tahu jalur persiapannya.',
                        'tag_kampus' => 'Universitas Andalas (UNAND)',
                    ],
                    [
                        'teks_pilihan' => 'Ekonomi & Akuntansi',
                        'respons' => 'Kesukaanmu pada Ekonomi dan Akuntansi cocok diarahkan ke Manajemen, Akuntansi, atau Ekonomi Pembangunan. Bidang ini membuka banyak peluang di dunia bisnis dan keuangan. Yuk cerita lebih lanjut ke Guru BK.',
                        'tag_kampus' => 'Universitas Negeri Padang (UNP)',
                    ],
                    [
                        'teks_pilihan' => 'Sejarah & Sosiologi',
                        'respons' => 'Minatmu pada Sejarah dan Sosiologi bisa berkembang di jurusan Ilmu Sejarah, Sosiologi, Antropologi, atau Hubungan Internasional. Bidang ini cocok buat kamu yang senang memahami masyarakat dan dinamikanya. Diskusikan lebih lanjut dengan Guru BK.',
                        'tag_kampus' => 'Universitas Andalas (UNAND)',
                    ],
                ],
            ],
            [
                'pertanyaan' => 'Setelah lulus SMA, gambaran karier seperti apa yang paling menarik buatmu?',
                'pilihan' => [
                    [
                        'teks_pilihan' => 'Bekerja di kantor/perusahaan besar',
                        'respons' => 'Kamu mungkin cocok dengan jurusan Manajemen, Akuntansi, atau Bisnis Digital yang banyak membuka peluang kerja di perusahaan. Diskusikan lebih lanjut dengan Guru BK untuk tahu persiapannya.',
                        'tag_kampus' => 'Universitas Negeri Padang (UNP)',
                    ],
                    [
                        'teks_pilihan' => 'Membuka usaha/bisnis sendiri',
                        'respons' => 'Semangat wirausahamu bisa didukung lewat jurusan Manajemen Bisnis, Kewirausahaan, atau Digital Marketing. Banyak keterampilan praktis yang bisa langsung dipakai membangun usaha. Yuk cerita lebih lanjut ke Guru BK.',
                        'tag_kampus' => 'Universitas Andalas (UNAND)',
                    ],
                    [
                        'teks_pilihan' => 'Bekerja membantu masyarakat langsung (guru, dokter, dsb)',
                        'respons' => 'Keinginanmu membantu orang lain bisa tersalur lewat jurusan Pendidikan, Kedokteran, Keperawatan, atau Pekerjaan Sosial. Profesi-profesi ini punya dampak langsung ke masyarakat. Diskusikan lebih lanjut dengan Guru BK untuk tahu jalurnya.',
                        'tag_kampus' => 'Universitas Negeri Padang (UNP)',
                    ],
                    [
                        'teks_pilihan' => 'Berkarya di bidang kreatif/teknologi',
                        'respons' => 'Ketertarikanmu pada bidang kreatif dan teknologi cocok diarahkan ke Desain, Film/Multimedia, atau Ilmu Komputer. Bidang ini terus berkembang dan banyak dicari saat ini. Yuk obrolkan lebih lanjut dengan Guru BK.',
                        'tag_kampus' => 'Politeknik Negeri Padang (PNP)',
                    ],
                ],
            ],
        ];
    }

    public function handle(): int
    {
        $dibuat = 0;
        $dilewati = 0;

        foreach ($this->data() as $item) {
            DB::transaction(function () use ($item, &$dibuat, &$dilewati) {
                $alur = AlurChatbot::firstOrCreate(['pertanyaan' => $item['pertanyaan']]);

                if ($alur->wasRecentlyCreated) {
                    $this->info("Ditambahkan pertanyaan: {$item['pertanyaan']}");
                    $dibuat++;
                } else {
                    $this->line("Lewati (sudah ada) pertanyaan: {$item['pertanyaan']}");
                    $dilewati++;
                }

                foreach ($item['pilihan'] as $p) {
                    $pilihan = PilihanAlurChatbot::firstOrCreate(
                        ['alur_id' => $alur->id, 'teks_pilihan' => $p['teks_pilihan']],
                        ['respons' => $p['respons'], 'tag_kampus' => $p['tag_kampus'], 'kampus_id' => null]
                    );

                    if ($pilihan->wasRecentlyCreated) {
                        $this->info("  \u{21B3} Ditambahkan pilihan: {$p['teks_pilihan']}");
                        $dibuat++;
                    } else {
                        $this->line("  \u{21B3} Lewati (sudah ada) pilihan: {$p['teks_pilihan']}");
                        $dilewati++;
                    }
                }
            });
        }

        $this->newLine();
        $this->info("Selesai. Ditambahkan: {$dibuat}, dilewati (sudah ada): {$dilewati}.");
        $this->line('Semua pertanyaan, pilihan, dan tag kampus ini bisa diubah kapan saja lewat menu Alur Chatbot di halaman Guru BK.');

        return self::SUCCESS;
    }
}
