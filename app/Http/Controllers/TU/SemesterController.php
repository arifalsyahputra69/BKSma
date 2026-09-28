<?php

namespace App\Http\Controllers\TU;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use Illuminate\Http\Request;

class SemesterController extends Controller
{
    public function index()
    {
        // Urutkan agar semester yang aktif (status_aktif = true) muncul di paling atas
        $semesters = Semester::orderByDesc('status_aktif')->latest()->get();
        return view('tu.semester.index', compact('semesters'));
    }

   public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
        ]);

        // Nonaktifkan semua semester yang sedang aktif
        Semester::where('status_aktif', true)->update(['status_aktif' => false]);

        Semester::create([
            'nama' => $request->nama,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'status_aktif' => true,
        ]);

        // 👇 LOGIKA RESET KONFIRMASI KELAS (KHUSUS GANJIL) 👇
        // Menggunakan stripos untuk mengecek kata 'ganjil' terlepas dari huruf besar/kecil
        if (stripos($request->nama, 'ganjil') !== false) {
            
            // Reset status agar semua siswa dipaksa memverifikasi kelas barunya
            \App\Models\Siswa::query()->update([
                'status_konfirmasi_kelas' => null,
                'waktu_tidak_konfirmasi' => null
            ]);
            
            return back()->with('success', 'Semester Ganjil berhasil dibuat! Status konfirmasi kelas seluruh siswa telah di-reset untuk tahun ajaran baru.');
        }

        return back()->with('success', 'Semester Genap berhasil dibuat. Semester sebelumnya otomatis diarsipkan.');
    }

    // OPSI 1: MENAMPILKAN DETAIL
    public function show($id)
    {
        $semester = Semester::findOrFail($id);
        
        return view('tu.semester.show', compact('semester'));
    }

    // OPSI 2: EDIT DATA SEMESTER
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
        ]);

        $semester = Semester::findOrFail($id);
        $semester->update([
            'nama' => $request->nama,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
        ]);

        return back()->with('success', 'Data rincian periode semester berhasil diperbarui.');
    }

    // OPSI 2: MENGAKTIFKAN KEMBALI
    public function toggleStatus($id)
    {
        $semester = Semester::findOrFail($id);

        // Nonaktifkan (arsipkan) semua semester lain terlebih dahulu
        Semester::where('id', '!=', $id)->update(['status_aktif' => false]);

        // Aktifkan semester pilihan
        $semester->update(['status_aktif' => true]);

        // 👇 TAMBAHKAN LOGIKA RESET DI SINI 👇
        // Cek apakah semester yang baru saja diaktifkan ini adalah semester Ganjil
        if (stripos($semester->nama, 'ganjil') !== false) {
            
            // Reset status agar semua siswa dipaksa memverifikasi kelas barunya
            \App\Models\Siswa::query()->update([
                'status_konfirmasi_kelas' => null,
                'waktu_tidak_konfirmasi' => null
            ]);
            
            return back()->with('success', "Semester {$semester->nama} berhasil diaktifkan. Status konfirmasi kelas seluruh siswa otomatis di-reset!");
        }

        // Pesan sukses jika yang diaktifkan adalah semester Genap
        return back()->with('success', "Semester {$semester->nama} berhasil diaktifkan kembali sebagai periode berjalan.");
    }
    public function destroy($id)
    {
        $semester = Semester::findOrFail($id);

        // Mencegah penghapusan semester yang sedang aktif berjalan
        if ($semester->status_aktif) {
            return back()->withErrors(['Semester yang sedang aktif tidak boleh dihapus. Silakan aktifkan semester lain terlebih dahulu jika ingin menghapusnya.']);
        }

        $nama_semester = $semester->nama;
        $semester->delete();

        return back()->with('success', "Data semester {$nama_semester} berhasil dihapus permanen.");
    }
}