<?php

namespace App\Http\Controllers\TU;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\User;
use App\Models\Siswa;
use Illuminate\Http\Request;

class KelasController extends Controller
{
    public function index()
    {
        // Mengambil semua data kelas beserta relasi Guru BK & Wali Kelas
        $kelas = Kelas::with('guruBk', 'waliKelas')->latest()->get();

        // Mengambil daftar user yang HANYA memiliki role 'Guru BK'
        $guruBk = User::role('Guru BK')->get();

        // Mengambil daftar user yang HANYA memiliki role 'Wali Kelas'
        $waliKelas = User::role('Wali Kelas')->get();

        return view('tu.kelas.index', compact('kelas', 'guruBk', 'waliKelas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_kelas' => 'required|string|max:50|unique:kelas,nama_kelas',
            'id_guru_bk' => 'required|exists:users,id',
            'wali_kelas_id' => 'nullable|exists:users,id',
        ]);

        Kelas::create([
            'nama_kelas' => $request->nama_kelas,
            'id_guru_bk' => $request->id_guru_bk,
            'wali_kelas_id' => $request->wali_kelas_id,
        ]);

        return back()->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        $kelas = Kelas::findOrFail($id);

        $request->validate([
            'nama_kelas' => 'required|string|max:50|unique:kelas,nama_kelas,' . $id,
            'id_guru_bk' => 'required|exists:users,id',
            'wali_kelas_id' => 'nullable|exists:users,id',
        ]);

        $kelas->update([
            'nama_kelas' => $request->nama_kelas,
            'id_guru_bk' => $request->id_guru_bk,
            'wali_kelas_id' => $request->wali_kelas_id,
        ]);

        return back()->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $kelas = Kelas::findOrFail($id);
        $kelas->delete();

        return back()->with('success', 'Kelas berhasil dihapus.');
    }

    public function show(int $id)
    {
        // Mengambil detail kelas beserta siswa yang sudah terdaftar
        $kelas = Kelas::with('siswas.user', 'guruBk', 'waliKelas')->findOrFail($id);

        // PERBAIKAN (9 Agustus 2026): sebelumnya daftar ini hanya memuat siswa
        // dengan kelas_id NULL. Akibatnya memindahkan siswa antar kelas mustahil
        // dilakukan lewat halaman ini -- siswa yang sudah punya kelas tidak
        // pernah muncul di pencarian, dan TU hanya melihat pesan "semua siswa
        // sudah mendapatkan kelas" seolah tidak ada yang bisa dipilih.
        //
        // Sekarang yang dikecualikan cuma siswa yang MEMANG sudah ada di kelas
        // ini. Sisanya ditampilkan lengkap dengan keterangan kelas asalnya,
        // supaya TU sadar tindakan itu memindahkan, bukan menambahkan.
        $siswaTersedia = Siswa::with(['user', 'kelas'])
            ->where(function ($query) use ($id) {
                $query->whereNull('kelas_id')
                      ->orWhere('kelas_id', '!=', $id);
            })
            ->get()
            ->sortBy(fn ($siswa) => $siswa->user->name ?? '')
            ->values();

        return view('tu.kelas.show', compact('kelas', 'siswaTersedia'));
    }

    public function assignSiswa(Request $request, int $id)
    {
        $request->validate([
            'siswa_ids' => 'required|array',
            'siswa_ids.*' => 'exists:siswas,id',
        ]);

        // Mengubah kelas_id para siswa yang dipilih menjadi ID kelas ini
        Siswa::whereIn('id', $request->siswa_ids)->update([
            'kelas_id' => $id
        ]);

        return back()->with('success', 'Siswa berhasil dimasukkan ke dalam kelas.');
    }

    public function removeSiswa(int $id, int $siswa_id)
    {
        $siswa = Siswa::findOrFail($siswa_id);

        // Mengeluarkan siswa dengan mengubah kelas_id kembali menjadi NULL
        $siswa->update([
            'kelas_id' => null
        ]);

        return back()->with('success', 'Siswa berhasil dikeluarkan dari kelas.');
    }
}
