@extends('layouts.siswa')

@section('title', 'Chatbot BK')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-primary text-white p-3 rounded-top-4 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="fas fa-robot me-2"></i> Asisten BK Virtual</span>
                <button onclick="window.location.reload()" class="btn btn-sm btn-light rounded-pill"><i class="fas fa-sync-alt"></i> Reset Chat</button>
            </div>
            
            <div class="card-body p-4" id="chat-container" style="height: 450px; overflow-y: auto; background: #f8fafc;">
                <div class="d-flex mb-3">
                    <div class="bg-white border shadow-sm p-3 rounded-4 rounded-top-left-0" style="max-width: 80%;">
                        Halo! Saya Asisten BK SMA Kartika I-5 Padang.<br><br>
                        Kamu bisa langsung mengetik pertanyaanmu di bawah (misal: "bolos", "izin"), atau pilih salah satu topik di bawah ini supaya aku bisa bantu lebih terarah:
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <button onclick="loadAlur()" class="btn btn-sm btn-outline-primary rounded-pill fw-semibold chat-btn-choice">
                                <i class="fas fa-compass me-1"></i> Karier &amp; Jurusan
                            </button>
                            <button onclick="pilihKategori('belajar', 'Belajar / Akademik')" class="btn btn-sm btn-outline-primary rounded-pill fw-semibold chat-btn-choice">
                                <i class="fas fa-book me-1"></i> Belajar / Akademik
                            </button>
                            <button onclick="pilihKategori('sosial', 'Sosial')" class="btn btn-sm btn-outline-primary rounded-pill fw-semibold chat-btn-choice">
                                <i class="fas fa-users me-1"></i> Sosial
                            </button>
                            <button onclick="pilihKategori('pribadi', 'Pribadi')" class="btn btn-sm btn-outline-primary rounded-pill fw-semibold chat-btn-choice">
                                <i class="fas fa-user me-1"></i> Pribadi
                            </button>
                            <button onclick="pilihKategori('lainnya', 'Lainnya')" class="btn btn-sm btn-outline-primary rounded-pill fw-semibold chat-btn-choice">
                                <i class="fas fa-ellipsis-h me-1"></i> Lainnya
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-footer bg-white border-0 p-3 rounded-bottom-4 shadow-sm">
                <form id="chat-form" class="d-flex gap-2">
                    <input type="text" id="message-input" class="form-control rounded-pill px-4" placeholder="Ketik pesan bebas di sini..." required autocomplete="off">
                    <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .rounded-top-left-0 { border-top-left-radius: 0 !important; }
    .rounded-top-right-0 { border-top-right-radius: 0 !important; }
    .chat-btn-choice { transition: all 0.2s; white-space: normal; text-align: left; }
    .chat-btn-choice:hover { transform: translateY(-2px); }
</style>

<script>
    const container = document.getElementById('chat-container');

    // PERBAIKAN KEAMANAN (AUDIT): seluruh balasan chatbot & teks yang diketik
    // siswa sebelumnya disisipkan mentah-mentah ke innerHTML. Isi rule chatbot,
    // alur, dan data kampus semuanya diinput manusia lewat panel Guru BK/TU,
    // jadi siapa pun yang bisa mengisi kolom-kolom itu bisa menitipkan tag
    // script / handler onerror yang lalu dieksekusi di browser SETIAP siswa
    // yang membuka chatbot (stored XSS). Helper di bawah meng-escape semua
    // teks dinamis; markup tombol/kartu tetap dibangun oleh kode kita sendiri.
    function esc(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Teks multi-baris: di-escape dulu, BARU newline diubah jadi <br>.
    function escMultiline(value) {
        return esc(value).replace(/\n/g, '<br>');
    }

    // URL dari database (mis. link_website kampus) hanya boleh http/https --
    // supaya tidak bisa diisi "javascript:..." yang jalan saat diklik siswa.
    function safeUrl(value) {
        const url = String(value ?? '').trim();
        return /^https?:\/\//i.test(url) ? esc(url) : '';
    }

    function scrollToBottom() {
        container.scrollTop = container.scrollHeight;
    }

    function appendUserMessage(text) {
        container.innerHTML += `
            <div class="d-flex justify-content-end mb-3">
                <div class="bg-primary text-white shadow-sm p-3 rounded-4 rounded-top-right-0" style="max-width: 80%;">
                    ${escMultiline(text)}
                </div>
            </div>`;
        scrollToBottom();
    }

    // isHtml = true HANYA boleh dipakai untuk markup yang dirakit di file ini
    // (dan yang bagian dinamisnya sudah lewat esc()/escMultiline()).
    function appendBotMessage(text, isHtml = false) {
        container.innerHTML += `
            <div class="d-flex mb-3">
                <div class="bg-white border shadow-sm p-3 rounded-4 rounded-top-left-0" style="max-width: 80%;">
                    ${isHtml ? text : escMultiline(text)}
                </div>
            </div>`;
        scrollToBottom();
    }

    // ==========================================
    // FUNGSI MODE 1 (ALUR JURUSAN & KARIER)
    // ==========================================
    // FITUR BARU (30 Juli 2026): simpan data tiap pilihan (termasuk objek
    // kampus penuh dari relasi kampus_id) di sini, key = id pilihan.
    // Dulu respons/tag_kampus dikirim sebagai string mentah lewat atribut
    // onclick (rawan rusak kalau ada tanda kutip, dan tidak bisa membawa
    // objek kampus). Sekarang tombol cuma kirim id pilihan, datanya diambil
    // dari map ini.
    const pilihanDataMap = {};

    function loadAlur() {
        appendUserMessage("Mulai Panduan Jurusan");
        
        fetch("{{ route('siswa.chatbot.alur') }}")
        .then(res => res.json())
        .then(res => {
            if(res.data.length === 0) {
                appendBotMessage("Maaf, Guru BK belum mengatur pertanyaan untuk panduan jurusan.");
                return;
            }

            res.data.forEach(alur => {
                let htmlContent = `<div class="fw-bold text-dark mb-2">${escMultiline(alur.pertanyaan)}</div><div class="d-flex flex-column gap-2 mt-2">`;

                alur.pilihan.forEach(pilihan => {
                    pilihanDataMap[pilihan.id] = pilihan;

                    htmlContent += `
                        <button onclick="handleChoiceClick(this, ${Number(pilihan.id)})"
                                class="btn btn-sm border chat-btn-choice text-primary bg-light fw-medium">
                            ${esc(pilihan.teks_pilihan)}
                        </button>`;
                });
                
                htmlContent += `</div>`;
                appendBotMessage(htmlContent, true);
            });
        });
    }

    function handleChoiceClick(buttonElement, pilihanId) {
        // Nonaktifkan tombol agar tidak diklik dua kali
        let parentDiv = buttonElement.parentElement;
        let allButtons = parentDiv.querySelectorAll('button');
        allButtons.forEach(btn => {
            btn.disabled = true;
            if(btn !== buttonElement) btn.classList.add('opacity-50');
            btn.classList.remove('text-primary');
        });
        buttonElement.classList.add('bg-primary', 'text-white');

        let pilihan = pilihanDataMap[pilihanId] || {};
        appendUserMessage(pilihan.teks_pilihan || '');
        
        setTimeout(() => {
            let botReply = `<strong>Rekomendasi:</strong><br>${escMultiline(pilihan.respons || '')}`;

            if (pilihan.kampus) {
                // Ada data kampus master (kampus_id) -> tampilkan kartu lengkap.
                let k = pilihan.kampus;
                let websiteUrl = safeUrl(k.link_website);
                let logoImg = k.logo
                    ? `<img src="/storage/kampus/${encodeURIComponent(k.logo)}" alt="${esc(k.nama_kampus)}" style="width:44px;height:44px;object-fit:cover;border-radius:8px;flex-shrink:0;">`
                    : `<div class="bg-white bg-opacity-25 rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;"><i class="fas fa-university"></i></div>`;

                botReply += `
                    <div class="mt-2 p-3 rounded-4 bg-info bg-opacity-10 border border-info border-opacity-25 d-flex gap-2">
                        ${logoImg}
                        <div style="min-width:0;">
                            <div class="fw-bold">${esc(k.nama_kampus)}</div>
                            ${k.deskripsi ? `<div class="small text-muted">${escMultiline(k.deskripsi)}</div>` : ''}
                            ${k.jurusan_unggulan ? `<div class="small"><span class="fw-semibold">Jurusan unggulan:</span> ${esc(k.jurusan_unggulan)}</div>` : ''}
                            ${k.jalur_beasiswa ? `<div class="small"><span class="fw-semibold">Jalur beasiswa:</span> ${esc(k.jalur_beasiswa)}</div>` : ''}
                            ${websiteUrl ? `<a href="${websiteUrl}" target="_blank" rel="noopener noreferrer" class="small">Kunjungi website <i class="fas fa-external-link-alt"></i></a>` : ''}
                        </div>
                    </div>`;
            } else if (pilihan.tag_kampus) {
                // Fallback lama: cuma teks bebas, belum dihubungkan ke data master Kampus.
                botReply += `<br><br><span class="badge bg-info text-white"><i class="fas fa-university"></i> ${esc(pilihan.tag_kampus)}</span>`;
            }

            appendBotMessage(botReply, true);
        }, 500);
    }


    // ==========================================
    // PERBAIKAN AUDIT (Bagian E, 22 Juli 2026):
    // FUNGSI SAPAAN PEMBUKA PER KATEGORI (Belajar/Sosial/Pribadi/Lainnya)
    // ==========================================
    function pilihKategori(kategori, label) {
        appendUserMessage(label);

        fetch(`{{ url('siswa/chatbot/kategori') }}/${kategori}`)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                appendBotMessage("Maaf, kategori ini belum tersedia.");
                return;
            }

            let botReply = escMultiline(data.sapaan);

            if (data.topik && data.topik.length > 0) {
                botReply += `<div class="d-flex flex-wrap gap-2 mt-3">`;
                data.topik.forEach(t => {
                    botReply += `
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill chat-btn-choice btn-opsi-chat fw-medium shadow-sm" data-keyword="${esc(t.keyword)}">
                            ${esc(t.keyword)}
                        </button>
                    `;
                });
                botReply += `</div>`;
            } else {
                botReply += `<div class="text-muted small mt-2">(Guru BK belum menambahkan topik khusus untuk kategori ini — silakan ketik pertanyaanmu langsung di kolom chat.)</div>`;
            }

            appendBotMessage(botReply, true);
        })
        .catch(() => {
            appendBotMessage("Terjadi kesalahan jaringan, silakan coba lagi.");
        });
    }

    // ==========================================
    // FUNGSI MODE 2 (KIRIM PESAN BEBAS & OPSI)
    // ==========================================

    // Fungsi utama untuk memanggil API chatbot
    function kirimPesanKeServer(message) {
        fetch("{{ route('siswa.chatbot.send') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({message: message})
        })
        .then(res => res.json())
        .then(data => {
            // Ubah enter (\n) menjadi <br> agar rapi
            let botReply = data.response ? escMultiline(data.response) : "Maaf, respon kosong.";

            // Cek apakah server mengirimkan data pilihan lanjutan (options)
            if (data.options && data.options.length > 0) {
                botReply += `<div class="d-flex flex-wrap gap-2 mt-3">`;

                // Looping untuk membuat tombol
                data.options.forEach(opsi => {
                    botReply += `
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill chat-btn-choice btn-opsi-chat fw-medium shadow-sm" data-keyword="${esc(opsi.keyword)}">
                            ${esc(opsi.keyword)}
                        </button>
                    `;
                });
                botReply += `</div>`;
            }

            // PERBAIKAN AUDIT: keyword jurusan terdeteksi -> tawarkan lompat ke Mode 1
            if (data.redirect_alur) {
                botReply += `
                    <div class="mt-3">
                        <button type="button" onclick="loadAlur()" class="btn btn-sm btn-primary rounded-pill fw-semibold">
                            <i class="fas fa-compass me-1"></i> Mulai Panduan Jurusan
                        </button>
                    </div>`;
            }

            // PERBAIKAN AUDIT: fallback tidak ada rule cocok -> tawarkan booking konsultasi
            if (data.offer_booking) {
                botReply += `
                    <div class="mt-3">
                        <a href="{{ route('siswa.sesi.index', ['dari' => 'chatbot']) }}" class="btn btn-sm btn-outline-success rounded-pill fw-semibold">
                            <i class="fas fa-calendar-check me-1"></i> Booking Konsultasi
                        </a>
                    </div>`;
            }

            // Tampilkan balasan (kirim true karena kita menyertakan HTML tombol)
            appendBotMessage(botReply, true);
        })
        .catch(err => {
            appendBotMessage("Terjadi kesalahan jaringan, silakan coba lagi.");
        });
    }

    // Event saat tombol KIRIM di form ditekan (Siswa ngetik manual)
    document.getElementById('chat-form').addEventListener('submit', function(e) {
        e.preventDefault();
        let input = document.getElementById('message-input');
        let message = input.value.trim();
        if(!message) return;
        
        appendUserMessage(message); // Tampilkan chat siswa
        input.value = ''; // Kosongkan input form
        
        kirimPesanKeServer(message); // Kirim ke server
    });

    // Event saat tombol OPSI lanjutan (anak) diklik oleh siswa
    document.addEventListener('click', function(e) {
        // Cari apakah yang diklik memiliki class 'btn-opsi-chat'
        let btn = e.target.closest('.btn-opsi-chat');
        if (btn) {
            let keyword = btn.getAttribute('data-keyword');

            // Agar rapi: ubah warna tombol yang diklik dan matikan tombol lainnya
            let parentDiv = btn.parentElement;
            let allButtons = parentDiv.querySelectorAll('.btn-opsi-chat');
            allButtons.forEach(b => {
                b.disabled = true;
                if(b !== btn) b.classList.add('opacity-50');
            });
            btn.classList.replace('btn-outline-primary', 'btn-primary');
            btn.classList.add('text-white');

            // Tampilkan pilihan tersebut di layar chat
            appendUserMessage(keyword);

            // Beri jeda sedikit agar natural, lalu kirim ke server
            setTimeout(() => {
                kirimPesanKeServer(keyword);
            }, 300);
        }
    });

</script>
@endsection