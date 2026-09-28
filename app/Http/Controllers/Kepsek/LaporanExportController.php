<?php

namespace App\Http\Controllers\Kepsek;

use App\Exports\LaporanMonitoringExport;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LaporanExportController extends Controller
{
    /**
     * Prioritas 7: Unduh laporan PDF.
     *
     * Sengaja memanggil DashboardController::buildData() (bukan menulis query
     * baru) supaya angka di laporan PDF selalu identik dengan yang tampil di
     * dashboard Kepsek, mengikuti filter semester & kelas yang sedang aktif.
     */
    public function pdf(Request $request, DashboardController $dashboard)
    {
        $data = $dashboard->buildData($request);

        $pdf = Pdf::loadView('kepsek.laporan.pdf', $data)
            ->setPaper('a4', 'portrait');

        $namaFile = 'laporan-monitoring-bk-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($namaFile);
    }

    /**
     * Prioritas 7: Unduh laporan Excel (multi-sheet).
     *
     * Sama seperti pdf(), memakai data dari buildData() supaya konsisten
     * dengan dashboard & tidak menghitung ulang dengan logika terpisah.
     */
    public function excel(Request $request, DashboardController $dashboard)
    {
        $data = $dashboard->buildData($request);

        $namaFile = 'laporan-monitoring-bk-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new LaporanMonitoringExport($data), $namaFile);
    }
}
