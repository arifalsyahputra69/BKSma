<?php

namespace App\Http\Controllers\GuruMapel;

use App\Http\Controllers\Controller;
use App\Models\SesiAbsensi;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $sesiHariIni = SesiAbsensi::with('kelas')
            ->where('guru_id', Auth::id())
            ->whereDate('tanggal', today())
            ->latest()
            ->get();

        $totalSesiBulanIni = SesiAbsensi::where('guru_id', Auth::id())
            ->whereMonth('tanggal', now()->month)
            ->whereYear('tanggal', now()->year)
            ->count();

        return view('gurumapel.dashboard', compact('sesiHariIni', 'totalSesiBulanIni'));
    }
}
