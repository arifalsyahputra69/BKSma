<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\SesiAbsensi;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;

/**
 * KETERANGAN IZIN/SAKIT BERLAKU SEHARI PENUH (9 Agustus 2026)
 *
 * Masalah yang diselesaikan: siswa yang sakit tetap sakit sepanjang hari, tapi
 * sistem menanyakannya berulang-ulang. Guru jam pertama mencatat "Sakit" dan
 * melampirkan foto surat dokter; guru jam kedua membuka QR-nya sendiri dan
 * mendapati siswa yang sama kembali "Belum Absen", lalu harus mengetik ulang
 * keterangan dan mengunggah ulang surat yang sama. Kalau tidak dikerjakan --
 * dan biasanya memang tidak, karena gurunya tidak tahu -- cron penanda Alpha
 * akan mencatatnya bolos. Siswa yang sakit dengan surat dokter berakhir
 * tercatat Alpha di lima jam pelajaran.
 *
 * Kelas ini menyalin keterangan itu ke seluruh jam pelajaran pada tanggal yang
 * sama, lengkap dengan buktinya.
 *
 * ATURAN YANG DIPEGANG:
 *
 * 1. HANYA HARI ITU. Salinan tidak pernah melewati batas tanggal. Besok siswa
 *    kembali dianggap wajib hadir. Kalau sakitnya berlanjut, gurunya mencatat
 *    ulang -- lebih merepotkan sehari daripada meninggalkan status sakit yang
 *    menempel diam-diam berhari-hari setelah siswanya sembuh.
 *
 * 2. TIDAK MENIMPA KEPUTUSAN MANUSIA. Baris yang berasal dari scan QR, dari
 *    ketikan guru, dari cron Alpha, atau dari sebelum fitur ini ada (sumber
 *    null) tidak pernah disentuh. Yang boleh ditulis ulang hanya baris yang
 *    dibuat kelas ini sendiri. Konsekuensinya disengaja: jam yang sudah
 *    terlanjur Alpha TIDAK ikut diperbaiki, itu urusan guru yang bersangkutan,
 *    wali kelas, atau Guru BK.
 *
 * 3. BERKAS BUKTINYA DIPAKAI BERSAMA, TIDAK DIGANDAKAN. Semua baris menunjuk
 *    satu nama berkas yang sama. Menggandakan foto surat dokter sebanyak jam
 *    pelajaran hanya menghabiskan kuota hosting sekolah untuk isi yang identik.
 *    Ini punya akibat penting: berkas bukti TIDAK BOLEH dihapus hanya karena
 *    satu baris melepaskannya -- lihat AbsensiController::hapusBukti().
 */
class KeteranganHarianAbsensi
{
    /**
     * Sebarkan keterangan Izin/Sakit satu siswa ke seluruh sesi lain pada
     * tanggal & kelas yang sama.
     *
     * Dipanggil setelah guru menyimpan keterangan manual. Mengembalikan jumlah
     * baris yang dibuat atau diperbarui, supaya pemanggilnya bisa memberi tahu
     * gurunya bahwa jam-jam lain sudah ikut terisi.
     */
    public static function sebarkan(Absensi $asal, SesiAbsensi $sesiAsal): int
    {
        if (! in_array($asal->status, ['Izin', 'Sakit'], true)) {
            return 0;
        }

        $sesiLainIds = self::sesiLainPadaHariYangSama($sesiAsal);

        if ($sesiLainIds->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($asal, $sesiLainIds) {
            // Dikunci supaya dua guru yang kebetulan menyimpan keterangan siswa
            // yang sama pada detik yang sama tidak sama-sama merasa barisnya
            // belum ada, lalu membuat dua catatan untuk satu jam pelajaran.
            $tercatat = Absensi::whereIn('sesi_absensi_id', $sesiLainIds)
                ->where('siswa_id', $asal->siswa_id)
                ->lockForUpdate()
                ->get()
                ->keyBy('sesi_absensi_id');

            $tersentuh = 0;

            foreach ($sesiLainIds as $sesiId) {
                $baris = $tercatat->get($sesiId);

                if ($baris && ! $baris->berasalDariSalinanOtomatis()) {
                    continue;
                }

                if (! $baris) {
                    $baris = new Absensi();
                    $baris->sesi_absensi_id = $sesiId;
                    $baris->siswa_id = $asal->siswa_id;
                }

                $baris->status = $asal->status;
                $baris->keterangan = $asal->keterangan;
                $baris->bukti = $asal->bukti;
                $baris->waktu_scan = null;
                $baris->sumber = Absensi::SUMBER_OTOMATIS;
                $baris->save();

                $tersentuh++;
            }

            return $tersentuh;
        });
    }

    /**
     * Isi sesi yang BARU dibuat dengan keterangan Izin/Sakit yang sudah
     * tercatat pada hari itu.
     *
     * Arah kebalikan dari sebarkan(): yang ini untuk guru jam berikutnya yang
     * baru membuka QR-nya. Tanpa ini, penyebaran hanya bekerja untuk sesi yang
     * kebetulan sudah ada saat keterangan disimpan -- padahal urutan
     * sebenarnya di sekolah justru terbalik: keterangan masuk pagi-pagi, sesi
     * jam ke-3 baru dibuat menjelang siang.
     */
    public static function isiSesiBaru(SesiAbsensi $sesiBaru): int
    {
        $sesiLainIds = self::sesiLainPadaHariYangSama($sesiBaru);

        if ($sesiLainIds->isEmpty()) {
            return 0;
        }

        $keteranganHariItu = self::keteranganAsliPadaSesi($sesiLainIds);

        if ($keteranganHariItu->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($sesiBaru, $keteranganHariItu) {
            $sudahAda = Absensi::where('sesi_absensi_id', $sesiBaru->id)
                ->lockForUpdate()
                ->pluck('siswa_id')
                ->all();

            $dibuat = 0;

            foreach ($keteranganHariItu as $siswaId => $sumberBaris) {
                if (in_array($siswaId, $sudahAda)) {
                    continue;
                }

                Absensi::create([
                    'sesi_absensi_id' => $sesiBaru->id,
                    'siswa_id' => $siswaId,
                    'status' => $sumberBaris->status,
                    'keterangan' => $sumberBaris->keterangan,
                    'bukti' => $sumberBaris->bukti,
                    'waktu_scan' => null,
                    'sumber' => Absensi::SUMBER_OTOMATIS,
                ]);

                $dibuat++;
            }

            return $dibuat;
        });
    }

    /**
     * Id sesi absensi lain di kelas & tanggal yang sama.
     *
     * Sengaja dibatasi per KELAS, bukan per siswa. Absensi terhubung ke kelas
     * lewat sesinya, dan seorang siswa hanya mengikuti sesi kelasnya sendiri --
     * memperlebar cakupannya hanya membuka peluang menulis ke kelas yang tidak
     * ada hubungannya.
     */
    private static function sesiLainPadaHariYangSama(SesiAbsensi $sesi)
    {
        return SesiAbsensi::where('kelas_id', $sesi->kelas_id)
            ->whereDate('tanggal', $sesi->tanggal)
            ->where('id', '!=', $sesi->id)
            ->pluck('id');
    }

    /**
     * Keterangan Izin/Sakit ASLI (bukan hasil salinan) pada sesi-sesi tersebut,
     * satu baris per siswa.
     *
     * Hasil salinan sengaja dibuang dari pertimbangan supaya tidak terjadi
     * penyalinan berantai: baris otomatis di jam ke-2 tidak boleh jadi rujukan
     * untuk mengisi jam ke-3. Kalau baris asalnya nanti dikoreksi guru, salinan
     * berantai itu tidak akan ikut terkoreksi dan datanya jadi berbeda-beda
     * antar jam pelajaran.
     *
     * Kalau seorang siswa punya lebih dari satu keterangan asli hari itu
     * (misalnya jam ke-1 Sakit lalu jam ke-4 diubah jadi Izin), yang dipakai
     * adalah YANG TERBARU -- keyBy() membuat entri belakangan menimpa yang
     * sebelumnya, dan urutannya sudah dinaikkan dari id terkecil.
     */
    private static function keteranganAsliPadaSesi($sesiIds): EloquentCollection
    {
        return Absensi::whereIn('sesi_absensi_id', $sesiIds)
            ->whereIn('status', ['Izin', 'Sakit'])
            ->where(function ($query) {
                $query->whereNull('sumber')
                    ->orWhere('sumber', '!=', Absensi::SUMBER_OTOMATIS);
            })
            ->orderBy('id')
            ->get()
            ->keyBy('siswa_id');
    }
}
