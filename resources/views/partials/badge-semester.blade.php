{{--
    PENANDA SEMESTER BERJALAN — SIM BK SMA Kartika I-5 Padang
    Ditambahkan 6 Agustus 2026.

    Dipasang di topbar setiap layout role lewat include partials.badge-semester
    supaya semua pengguna — siswa, Guru BK, wali kelas, guru mapel, kepala
    sekolah, maupun TU — selalu tahu sedang berada di semester mana, tanpa
    perlu membuka halaman lain.

    Data semester aktif tidak diambil di sini melainkan disuplai oleh view
    composer di AppServiceProvider (variabel $semesterAktif). Query di dalam
    view membuat halaman sulit diuji dan gampang terlewat saat ditelusuri.

    Gayanya ditulis sebagai CSS biasa dengan awalan .simbk-sem- dan bukan
    utility Tailwind, karena Tailwind di proyek ini di-compile lewat Vite dan
    kelas yang tidak terpakai dibuang saat build — kelas baru tidak akan
    berefek di server sampai aset di-build ulang.
--}}

<style>
    .simbk-sem-badge {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .32rem .8rem;
        border-radius: 999px;
        font-size: .76rem;
        font-weight: 600;
        line-height: 1.2;
        white-space: nowrap;
        text-decoration: none;
        border: 1px solid transparent;
    }

    .simbk-sem-aktif {
        background: #e8f3ee;
        color: #1f4b3f;
        border-color: rgba(31, 75, 63, .18);
    }

    .simbk-sem-kosong {
        background: #f3f4f6;
        color: #6b7280;
        border-color: #e5e7eb;
    }

    a.simbk-sem-kosong:hover {
        background: #e5e7eb;
        color: #374151;
    }

    .simbk-sem-badge i {
        font-size: .72rem;
        opacity: .85;
    }

    /* Di layar sempit, topbar sudah padat oleh judul halaman, lonceng
       notifikasi, dan nama pengguna. Label "Semester" dibuang dan hanya
       namanya yang tersisa supaya tidak mendorong elemen lain keluar. */
    @media (max-width: 575.98px) {
        .simbk-sem-badge {
            padding: .28rem .6rem;
            font-size: .7rem;
        }
        .simbk-sem-label {
            display: none;
        }
    }
</style>

@if($semesterAktif ?? null)
    <span class="simbk-sem-badge simbk-sem-aktif"
          title="Semester yang sedang berjalan">
        <i class="fas fa-calendar-day"></i>
        <span><span class="simbk-sem-label">Semester </span>{{ $semesterAktif->nama }}</span>
    </span>
@else
    {{-- Sengaja tetap ditampilkan, tidak disembunyikan. Kalau badge ini hilang
         begitu saja, tidak ada yang sadar bahwa datanya memang belum diisi —
         dan sejumlah fitur (AKPD, absensi, program BK) ikut tidak berjalan
         benar tanpa semester aktif. --}}
    @if(auth()->check() && auth()->user()->hasRole('TU/Admin'))
        <a href="{{ route('tu.semester.index') }}"
           class="simbk-sem-badge simbk-sem-kosong"
           title="Belum ada semester aktif — klik untuk mengaturnya">
            <i class="fas fa-triangle-exclamation"></i>
            <span><span class="simbk-sem-label">Semester </span>belum diatur</span>
        </a>
    @else
        <span class="simbk-sem-badge simbk-sem-kosong"
              title="Semester aktif belum ditetapkan oleh TU/Admin">
            <i class="fas fa-triangle-exclamation"></i>
            <span><span class="simbk-sem-label">Semester </span>belum diatur</span>
        </span>
    @endif
@endif
