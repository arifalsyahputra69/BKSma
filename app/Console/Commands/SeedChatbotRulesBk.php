<?php

namespace App\Console\Commands;

use App\Models\ChatbotRule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * ISI AWAL ATURAN CHATBOT BK (13 Agustus 2026).
 *
 * Setelah semua kata kunci chatbot direset (kosong), Guru BK minta chatbot
 * "berjalan selayaknya chatbot pada bimbingan konseling" -- jadi bukan
 * sekadar 1-2 contoh, tapi satu set awal topik yang wajar ditanyakan siswa
 * ke BK, dengan nada seorang konselor sekolah: empatik, tidak menghakimi,
 * memberi langkah kecil yang bisa langsung dicoba, dan SELALU mengarahkan
 * ke sesi konsultasi sungguhan untuk hal yang lebih berat -- karena chatbot
 * ini memang dirancang sebagai penyaring awal (triase), bukan pengganti
 * Guru BK.
 *
 * Struktur mengikuti tabel chatbot_rules yang sudah ada:
 *   - category : akademik | sosial | pribadi | umum
 *     (pemetaan ke label tombol ada di Siswa\ChatbotController::PETA_KATEGORI)
 *   - keyword  : SENGAJA dibuat pendek (1 kata/frasa singkat). Mesin
 *     pencocokan chatbot ini mencari rule yang keyword-nya MEMUAT teks
 *     yang diketik siswa (bukan sebaliknya) -- keyword pendek jauh lebih
 *     mungkin cocok dengan kata yang benar-benar diketik siswa.
 *   - parent_id : "Induk Percakapan". Topik dengan children dianggap
 *     punya cabang lanjutan (siswa akan disodori tombol pilihan setelah
 *     induknya cocok) -- dipakai untuk topik yang perlu digali lebih detail
 *     (perundungan).
 *
 * PENTING -- 2 topik krisis ("putus asa", "menyakiti diri") diberi respons
 * khusus: TIDAK memberi teknik/instruksi apa pun, hanya memvalidasi
 * perasaan siswa lalu SEGERA mengarahkan ke orang dewasa tepercaya /
 * Guru BK / layanan Sehat Jiwa Kemenkes 119 ext 8. Jangan diubah jadi
 * nada yang menyepelekan atau memberi "tips mengatasi sendiri".
 *
 * Jalankan sekali:  php artisan chatbot:seed-rules-bk
 * Aman diulang -- topik (kategori + keyword) yang sudah ada dilewati,
 * TIDAK menimpa respons yang mungkin sudah diubah manual oleh Guru BK.
 */
class SeedChatbotRulesBk extends Command
{
    protected $signature = 'chatbot:seed-rules-bk';

    protected $description = 'Isi set awal topik & kata kunci chatbot BK (akademik, sosial, pribadi, umum) yang profesional dan siap pakai';

    /**
     * category => daftar topik.
     * Tiap topik: keyword, response, dan opsional children (array serupa,
     * otomatis di-set parent_id ke topik induknya).
     */
    private function data(): array
    {
        return [
            'akademik' => [
                [
                    'keyword' => 'nilai',
                    'response' => 'Nilai turun memang bisa bikin cemas, tapi ini bukan akhir dari segalanya. Coba cari tahu dulu bagian mana yang paling sulit kamu pahami, lalu diskusikan dengan guru mata pelajaran atau belajar kelompok dengan teman. Kalau kamu ingin menyusun rencana belajar yang lebih terarah bersama Guru BK, silakan booking sesi konsultasi, ya.',
                ],
                [
                    'keyword' => 'malas',
                    'response' => 'Rasa malas belajar itu wajar dan hampir semua orang pernah mengalaminya. Coba pecah tugas besar jadi langkah-langkah kecil, belajar 25 menit lalu istirahat sebentar, dan ingat lagi alasan pribadimu kenapa pelajaran ini penting. Kalau rasa malas ini terus-menerus mengganggu, yuk cerita ke Guru BK supaya dicari akar masalahnya bersama.',
                ],
                [
                    'keyword' => 'ujian',
                    'response' => 'Wajar merasa cemas menjelang ujian. Coba siapkan jadwal belajar bertahap dari sekarang (bukan sistem kebut semalam), latihan soal-soal lama, dan tarik napas dalam beberapa kali kalau mulai gugup. Kalau kecemasannya terasa berat sampai mengganggu tidur atau konsentrasi, cerita ke Guru BK ya supaya bisa dibantu strateginya.',
                ],
                [
                    'keyword' => 'tugas',
                    'response' => 'Tugas menumpuk memang bikin pusing, tapi bisa diatasi pelan-pelan. Coba buat daftar semua tugas beserta tenggat waktunya, lalu kerjakan yang paling mendesak dulu satu per satu. Kalau kamu kesulitan mengatur waktu secara konsisten, Guru BK bisa bantu susun strategi manajemen waktu yang sesuai untukmu.',
                ],
                [
                    'keyword' => 'konsentrasi',
                    'response' => 'Sulit fokus itu sering terjadi, apalagi kalau banyak distraksi di sekitar (HP, kebisingan, dll). Coba belajar di tempat yang lebih tenang, jauhkan HP sementara waktu, dan beri jeda istirahat tiap 25-30 menit. Kalau ini sudah berlangsung lama, cerita ke Guru BK ya supaya penyebabnya bisa dicari bersama.',
                ],
            ],

            'sosial' => [
                [
                    'keyword' => 'berantem',
                    'response' => 'Berantem dengan teman memang bikin tidak nyaman. Coba beri jeda dulu supaya emosi mereda, lalu ajak bicara baik-baik untuk saling memahami sudut pandang masing-masing. Kalau butuh bantuan menjembatani, atau situasinya berat buat kamu sendiri, Guru BK siap membantu — tinggal booking sesi konsultasi.',
                ],
                [
                    'keyword' => 'bully',
                    'response' => 'Terima kasih sudah berani cerita — apa pun bentuknya, perundungan bukan salahmu dan kamu berhak merasa aman di sekolah. Boleh cerita lebih detail, kejadian yang kamu alami yang mana?',
                    'children' => [
                        [
                            'keyword' => 'diejek',
                            'response' => 'Diejek terus-menerus itu menyakitkan dan tidak boleh dianggap biasa. Jangan balas dengan ejekan juga — coba catat kapan dan siapa yang melakukannya, lalu segera laporkan ke wali kelas atau Guru BK supaya bisa ditindaklanjuti. Kamu tidak sendirian menghadapi ini.',
                        ],
                        [
                            'keyword' => 'dikucilkan',
                            'response' => 'Dikucilkan dari pertemanan itu berat rasanya. Coba cari satu-dua teman lain yang suportif, ikut kegiatan atau ekskul baru untuk memperluas circle, dan jangan ragu cerita ke Guru BK supaya situasinya bisa ditangani dari sisi sekolah juga.',
                        ],
                        [
                            'keyword' => 'diancam',
                            'response' => 'Kalau kamu diancam oleh siapa pun, ini serius dan harus segera dilaporkan. Jangan hadapi sendirian — segera ceritakan ke Guru BK, wali kelas, atau orang tua secepatnya supaya keselamatanmu bisa dipastikan.',
                        ],
                    ],
                ],
                [
                    'keyword' => 'sendirian',
                    'response' => 'Merasa sendirian di sekolah itu berat, tapi ini bisa perlahan diperbaiki. Coba mulai dari hal kecil seperti menyapa atau mengobrol singkat dengan teman sekelas, atau ikut ekskul sesuai minatmu supaya bertemu orang-orang baru. Kalau kamu ingin bantuan lebih lanjut, cerita saja ke Guru BK, ya.',
                ],
                [
                    'keyword' => 'tekanan',
                    'response' => 'Merasa tertekan untuk ikut-ikutan sesuatu yang sebenarnya tidak kamu inginkan itu wajar terjadi, apalagi dari circle pertemanan. Kamu berhak menolak dengan tegas tanpa harus merasa bersalah — teman yang baik akan menghargai batasanmu. Kalau situasinya sulit dihadapi sendiri, yuk cerita ke Guru BK.',
                ],
            ],

            'pribadi' => [
                [
                    'keyword' => 'keluarga',
                    'response' => 'Masalah di rumah memang bisa terasa berat dan melelahkan secara emosional. Perasaanmu valid, dan kamu tidak harus menghadapinya sendirian. Guru BK bisa jadi tempat cerita yang aman dan rahasia — yuk booking sesi konsultasi kalau kamu siap.',
                ],
                [
                    'keyword' => 'stres',
                    'response' => 'Stres berlebihan bisa memengaruhi tubuh dan pikiranmu. Coba luangkan waktu istirahat yang cukup, olahraga ringan, dan bicarakan apa yang kamu rasakan ke orang yang kamu percaya. Kalau stresnya sudah mengganggu keseharianmu, sebaiknya cerita ke Guru BK supaya bisa dicari solusinya bersama.',
                ],
                [
                    'keyword' => 'cemas',
                    'response' => 'Rasa cemas yang muncul terus-menerus memang tidak nyaman. Coba kenali dulu apa yang memicunya, lalu latihan tarik napas perlahan saat rasa cemas itu datang. Kalau kecemasannya terasa berat atau sering muncul, jangan ragu cerita ke Guru BK, ya.',
                ],
                [
                    'keyword' => 'marah',
                    'response' => 'Marah adalah emosi yang wajar, yang penting adalah cara menyalurkannya. Coba beri jeda sebelum bereaksi, tarik napas dalam, atau jalan sebentar untuk menenangkan diri sebelum bicara. Kalau amarahnya terasa sulit dikendalikan, Guru BK bisa bantu cari caranya bersama.',
                ],
                [
                    'keyword' => 'minder',
                    'response' => 'Kurang percaya diri itu dialami banyak orang, kamu tidak sendirian. Coba mulai catat hal-hal kecil yang berhasil kamu lakukan setiap hari, dan hindari membandingkan diri dengan orang lain. Kalau rasa minder ini mengganggu keseharianmu, cerita ke Guru BK, ya.',
                ],
                [
                    'keyword' => 'pacar',
                    'response' => 'Urusan hati memang bisa memengaruhi suasana hati dan fokus belajar. Wajar merasa sedih atau bingung — beri waktu untuk dirimu sendiri dan jangan terburu-buru mengambil keputusan besar. Kalau kamu butuh teman cerita, Guru BK siap mendengarkan tanpa menghakimi.',
                ],
                // --- TOPIK KRISIS: validasi perasaan + arahkan ke bantuan nyata, TANPA teknik/instruksi apa pun ---
                [
                    'keyword' => 'putus asa',
                    'response' => 'Terima kasih sudah mau cerita. Perasaan putus asa itu berat, dan penting buat kamu tahu bahwa kamu tidak sendirian menghadapinya. Tolong segera ceritakan perasaanmu ke Guru BK, wali kelas, atau orang tua/orang dewasa yang kamu percaya SEKARANG juga — jangan ditunda. Kamu juga bisa menghubungi layanan Sehat Jiwa Kemenkes di 119 ext 8 kapan saja kalau butuh bicara segera. Kamu berharga, dan bantuan itu ada.',
                ],
                [
                    'keyword' => 'menyakiti diri',
                    'response' => 'Kalau kamu punya pikiran untuk menyakiti diri sendiri, tolong jangan hadapi ini sendirian. Segera hubungi Guru BK, orang tua, atau orang dewasa yang kamu percaya sekarang juga. Kamu juga bisa menghubungi layanan Sehat Jiwa Kemenkes di 119 ext 8 untuk bicara dengan konselor kapan saja. Perasaanmu penting, dan ada orang yang siap membantu.',
                ],
            ],

            'umum' => [
                [
                    'keyword' => 'bolos',
                    'response' => 'Kalau kamu berpikir untuk bolos, boleh cerita dulu apa alasannya? Sering kali ada masalah lain di baliknya (bosan, capek, masalah pribadi, dll) yang sebenarnya bisa dibantu penyelesaiannya. Sebaiknya sampaikan dulu ke wali kelas atau Guru BK sebelum memutuskan bolos, ya — supaya masalahnya bisa dicari solusi yang lebih baik.',
                ],
                [
                    'keyword' => 'izin',
                    'response' => 'Untuk izin tidak masuk sekolah, sampaikan ke wali kelas atau guru piket sebelum/pada hari izin, disertai keterangan dari orang tua/wali. Guru akan mencatat status kehadiranmu di sistem. Kalau izinnya karena ada masalah yang ingin kamu ceritakan juga, Guru BK selalu terbuka untuk mendengarkan.',
                ],
                [
                    'keyword' => 'sakit',
                    'response' => 'Semoga cepat sembuh! Kalau kamu tidak bisa masuk sekolah karena sakit, minta orang tua/walimu untuk menginformasikan ke wali kelas atau guru piket, ya, supaya kehadiranmu tercatat sebagai sakit, bukan alpa.',
                ],
                [
                    'keyword' => 'konsultasi',
                    'response' => 'Untuk booking sesi konsultasi dengan Guru BK, kamu bisa buka menu Booking Konsultasi di aplikasi ini dan pilih jadwal yang tersedia. Semua yang kamu ceritakan akan dijaga kerahasiaannya.',
                ],
                [
                    'keyword' => 'rahasia',
                    'response' => 'Semua yang kamu ceritakan ke Guru BK bersifat rahasia dan tidak akan disebarkan ke teman atau pihak lain tanpa izinmu. Pengecualian hanya berlaku kalau ada risiko terhadap keselamatanmu atau orang lain — di situasi itu Guru BK wajib melibatkan pihak yang bisa membantu, semata-mata demi keamananmu.',
                ],
            ],
        ];
    }

    public function handle(): int
    {
        $dibuat = 0;
        $dilewati = 0;

        foreach ($this->data() as $category => $topikList) {
            foreach ($topikList as $topik) {
                DB::transaction(function () use ($category, $topik, &$dibuat, &$dilewati) {
                    $parent = ChatbotRule::firstOrCreate(
                        ['category' => $category, 'keyword' => $topik['keyword']],
                        ['response' => $topik['response'], 'parent_id' => null]
                    );

                    if ($parent->wasRecentlyCreated) {
                        $this->info("Ditambahkan [{$category}]: {$topik['keyword']}");
                        $dibuat++;
                    } else {
                        $this->line("Lewati (sudah ada) [{$category}]: {$topik['keyword']}");
                        $dilewati++;
                    }

                    foreach ($topik['children'] ?? [] as $anak) {
                        $childRule = ChatbotRule::firstOrCreate(
                            ['category' => $category, 'keyword' => $anak['keyword']],
                            ['response' => $anak['response'], 'parent_id' => $parent->id]
                        );

                        if ($childRule->wasRecentlyCreated) {
                            $this->info("  \u{21B3} Ditambahkan turunan dari \"{$topik['keyword']}\": {$anak['keyword']}");
                            $dibuat++;
                        } else {
                            $this->line("  \u{21B3} Lewati (sudah ada) turunan: {$anak['keyword']}");
                            $dilewati++;
                        }
                    }
                });
            }
        }

        $this->newLine();
        $this->info("Selesai. Ditambahkan: {$dibuat}, dilewati (sudah ada): {$dilewati}.");
        $this->line('Semua topik & respons ini bisa diubah kapan saja lewat menu Aturan Chatbot di halaman Guru BK.');

        return self::SUCCESS;
    }
}
