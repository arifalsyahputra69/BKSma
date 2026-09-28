<?php

// LETAKKAN DI: app/Http/Controllers/Kepsek/KegiatanBkController.php

namespace App\Http\Controllers\Kepsek;

use App\Http\Controllers\Controller;
use App\Models\KegiatanBk;

class KegiatanBkController extends Controller
{
    // Monitoring read-only seluruh kegiatan BK lintas Guru BK, dikelompokkan
    // per bulan supaya Kepsek bisa melihat kepadatan/kekosongan agenda BK.
    public function index()
    {
        $kegiatanMendatang = KegiatanBk::with('guruBk')
            ->where('status', 'Direncanakan')
            ->orderBy('tanggal_mulai')
            ->get()
            ->groupBy(fn (KegiatanBk $k) => $k->tanggal_mulai->translatedFormat('F Y'));

        $kegiatanLalu = KegiatanBk::with('guruBk')
            ->whereIn('status', ['Terlaksana', 'Dibatalkan'])
            ->orderByDesc('tanggal_mulai')
            ->take(30)
            ->get();

        $totalMendatang = KegiatanBk::where('status', 'Direncanakan')->count();
        $totalTerlaksana = KegiatanBk::where('status', 'Terlaksana')->count();

        return view('kepsek.kegiatan-bk.index', compact(
            'kegiatanMendatang',
            'kegiatanLalu',
            'totalMendatang',
            'totalTerlaksana'
        ));
    }
}
