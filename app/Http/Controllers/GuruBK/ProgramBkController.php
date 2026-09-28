<?php

namespace App\Http\Controllers\GuruBK;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Notifikasi;
use App\Models\ProgramBk;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProgramBkController extends Controller
{
    /**
     * Daftar program yang pernah diajukan Guru BK ini + form pengajuan baru.
     *
     * PERBAIKAN (26 Juli 2026): sebelumnya hanya ada satu daftar program
     * tanpa pembeda jenis, padahal program kerja BK di lapangan terdiri
     * dari Program Tahunan (payung besar 1 tahun ajaran) dan Program
     * Semester (turunan per semester). Ditambahkan filter `jenis` supaya
     * Guru BK bisa memisahkan tampilan keduanya.
     */
    public function index(Request $request)
    {
        $jenis = $request->input('jenis', 'Semua');

        $query = ProgramBk::with(['ditinjauOleh', 'semester'])
            ->where('guru_bk_id', Auth::id());

        if (in_array($jenis, ['Tahunan', 'Semesteran', 'Bulanan'])) {
            $query->where('jenis_program', $jenis);
        }

        $programs = $query->latest()->get();

        $totalTahunan = ProgramBk::where('guru_bk_id', Auth::id())->where('jenis_program', 'Tahunan')->count();
        $totalSemesteran = ProgramBk::where('guru_bk_id', Auth::id())->where('jenis_program', 'Semesteran')->count();
        $totalBulanan = ProgramBk::where('guru_bk_id', Auth::id())->where('jenis_program', 'Bulanan')->count();

        $semesterList = Semester::orderByDesc('tanggal_mulai')->get();
        $kelasList = Kelas::orderBy('nama_kelas')->get();

        return view('gurubk.program-bk.index', compact(
            'programs', 'jenis', 'totalTahunan', 'totalSemesteran', 'totalBulanan',
            'semesterList', 'kelasList'
        ));
    }

    /**
     * Guru BK mengajukan program baru -> notifikasi ke semua Kepala Sekolah
     */
    public function store(Request $request)
    {
        $request->validate([
            'jenis_program' => 'required|in:Tahunan,Semesteran,Bulanan',
            'judul' => 'required|string|max:255',
            'semester_id' => 'required|exists:semesters,id',
            'sasaran_kelas' => 'required|array|min:1',
            'sasaran_kelas.*' => 'string|max:100',
            'deskripsi' => 'required|string',
            'rps_file' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $semester = Semester::findOrFail($request->semester_id);

        $rpsPath = null;
        if ($request->hasFile('rps_file')) {
            $rpsPath = $request->file('rps_file')->store('program-bk/rps', 'public');
        }

        $program = ProgramBk::create([
            'guru_bk_id' => Auth::id(),
            'jenis_program' => $request->jenis_program,
            'semester_id' => $semester->id,
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'rps_file' => $rpsPath,
            'sasaran' => implode(', ', $request->sasaran_kelas),
            'tanggal_mulai' => $semester->tanggal_mulai,
            'tanggal_selesai' => $semester->tanggal_selesai,
            'status' => 'Diajukan',
        ]);

        // Beritahu semua Kepala Sekolah bahwa ada pengajuan baru
        $kepsekList = User::role('Kepala Sekolah')->get();
        foreach ($kepsekList as $kepsek) {
            Notifikasi::create([
                'user_id' => $kepsek->id,
                'judul' => 'Pengajuan Program BK Baru',
                'pesan' => Auth::user()->name . " mengajukan program \"{$program->judul}\" untuk ditinjau.",
                'link' => route('kepsek.program-bk.index'),
                'is_read' => false,
            ]);
        }

        return back()->with('success', 'Program BK berhasil diajukan. Menunggu persetujuan Kepala Sekolah.');
    }

    /**
     * Guru BK boleh menghapus pengajuan selama masih berstatus "Diajukan"
     * (belum ditinjau Kepsek)
     */
    public function destroy($id)
    {
        $program = ProgramBk::where('guru_bk_id', Auth::id())->findOrFail($id);

        if ($program->status !== 'Diajukan') {
            return back()->with('error', 'Program yang sudah ditinjau tidak bisa dihapus.');
        }

        $program->delete();

        return back()->with('success', 'Pengajuan program berhasil dibatalkan.');
    }

    /**
     * PERBAIKAN (26 Juli 2026): sebelumnya kolom Aksi pada tabel Pengajuan
     * Program BK hanya punya tombol Hapus, tidak ada Edit. Guru BK boleh
     * mengubah pengajuan selama masih berstatus "Diajukan" (belum ditinjau
     * Kepala Sekolah), sama seperti aturan pada destroy().
     */
    public function update(Request $request, $id)
    {
        $program = ProgramBk::where('guru_bk_id', Auth::id())->findOrFail($id);

        if ($program->status !== 'Diajukan') {
            return back()->with('error', 'Program yang sudah ditinjau tidak bisa diubah.');
        }

        $request->validate([
            'jenis_program' => 'required|in:Tahunan,Semesteran,Bulanan',
            'judul' => 'required|string|max:255',
            'semester_id' => 'required|exists:semesters,id',
            'sasaran_kelas' => 'required|array|min:1',
            'sasaran_kelas.*' => 'string|max:100',
            'deskripsi' => 'required|string',
            'rps_file' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $semester = Semester::findOrFail($request->semester_id);

        $data = [
            'jenis_program' => $request->jenis_program,
            'semester_id' => $semester->id,
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'sasaran' => implode(', ', $request->sasaran_kelas),
            'tanggal_mulai' => $semester->tanggal_mulai,
            'tanggal_selesai' => $semester->tanggal_selesai,
        ];

        if ($request->hasFile('rps_file')) {
            $data['rps_file'] = $request->file('rps_file')->store('program-bk/rps', 'public');
        }

        $program->update($data);

        return back()->with('success', 'Pengajuan program berhasil diperbarui.');
    }

    /**
     * PERBAIKAN (28 Juli 2026, revisi ke-2): dulu Guru BK harus manual
     * memilih dari dropdown "Belum Mulai / Berjalan / Selesai" -- padahal
     * "Belum Mulai" & "Berjalan" bisa dihitung otomatis dari tanggal
     * program (lihat ProgramBk::statusRealisasiLabel()). Sekarang Guru BK
     * cuma perlu 1 aksi: klik "Tandai Selesai" saat programnya benar-benar
     * rampung. Ini yang membedakan "Selesai" dari "Mangkrak" (lewat
     * tenggat tapi belum ditandai selesai).
     */
    public function tandaiSelesai($id)
    {
        $program = ProgramBk::where('guru_bk_id', Auth::id())->findOrFail($id);

        if ($program->status !== 'Disetujui') {
            return back()->with('error', 'Realisasi hanya bisa ditandai untuk program yang sudah disetujui.');
        }

        $program->update(['status_pelaksanaan' => 'Selesai']);

        return back()->with('success', 'Program ditandai selesai.');
    }
}