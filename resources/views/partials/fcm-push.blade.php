{{--
    LETAKKAN DI: resources/views/partials/fcm-push.blade.php

    CARA PAKAI: tambahkan satu baris ini SEBELUM </body> di setiap layout
    role yang perlu terima push notification (kepsek, guru, walikelas, tu,
    gurumapel, siswa) -- CUKUP di layout, karena semua halaman @extends dari
    layout tersebut:

        @auth
            @include('partials.fcm-push')
        @endauth

    Konfigurasi apiKey/projectId dsb di bawah HARUS SAMA PERSIS dengan yang
    ditulis di public/firebase-messaging-sw.js (keduanya dari Firebase
    Console > Project Settings > General > "Your apps" > Web app).
--}}
<script type="module">
    import { initializeApp } from "https://www.gstatic.com/firebasejs/10.13.0/firebase-app.js";
    import { getMessaging, getToken, onMessage } from "https://www.gstatic.com/firebasejs/10.13.0/firebase-messaging.js";

    const firebaseConfig = {
        apiKey: "{{ config('services.firebase.web.api_key') }}",
        authDomain: "{{ config('services.firebase.web.auth_domain') }}",
        projectId: "{{ config('services.firebase.web.project_id') }}",
        storageBucket: "{{ config('services.firebase.web.storage_bucket') }}",
        messagingSenderId: "{{ config('services.firebase.web.messaging_sender_id') }}",
        appId: "{{ config('services.firebase.web.app_id') }}",
    };

    // Kalau kredensial belum diisi di .env, jangan lanjut (hindari error di console browser).
    //
    // PERBAIKAN BUG (30 Juli 2026): seluruh blok ini dibungkus try/catch dan
    // SETIAP promise diberi .catch() sendiri-sendiri. Sebelumnya kalau SDK
    // Firebase gagal di tengah jalan (mis. error internal
    // "heartbeatService" -- biasanya muncul kalau IndexedDB browser
    // diblokir/tidak tersedia, umum terjadi di mode Incognito atau saat
    // situs diakses dari domain yang tidak terdaftar di Firebase Console),
    // errornya jadi "unhandled promise rejection" yang cuma tercatat di
    // console TANPA merusak apa pun di halaman lain -- termasuk supaya jelas
    // fitur push notification ini gagal tanpa gejala aneh di bagian lain
    // halaman (mis. form Ubah Password yang sama sekali tidak berhubungan
    // dengan fitur ini).
    if (firebaseConfig.apiKey) {
        try {
            const app = initializeApp(firebaseConfig);
            const messaging = getMessaging(app);

            navigator.serviceWorker.register('/firebase-messaging-sw.js').then((registration) => {
                Notification.requestPermission().then((permission) => {
                    if (permission !== 'granted') return;

                    getToken(messaging, {
                        vapidKey: "{{ config('services.firebase.web.vapid_key') }}",
                        serviceWorkerRegistration: registration,
                    }).then((token) => {
                        if (!token) return;

                        fetch("{{ route('fcm.token.store') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                            },
                            body: JSON.stringify({ token }),
                        }).catch((err) => console.warn('FCM: gagal kirim token ke server:', err));
                    }).catch((err) => console.warn('FCM getToken gagal:', err));
                }).catch((err) => console.warn('FCM: gagal minta izin notifikasi:', err));
            }).catch((err) => console.warn('FCM: gagal daftarkan service worker:', err));

            // Notifikasi yang masuk SAAT tab sedang aktif/terbuka (foreground)
            onMessage(messaging, (payload) => {
                const judul = payload.notification?.title || 'SIM BK Kartika';
                const isi = payload.notification?.body || '';
                // Tampilan sederhana; silakan diganti dengan toast/alert sesuai desain yang sudah ada.
                if (Notification.permission === 'granted') {
                    new Notification(judul, { body: isi });
                }
            });
        } catch (err) {
            // SDK Firebase gagal diinisialisasi sama sekali (mis. error
            // "heartbeatService" dari @firebase/app) -- gagalkan diam-diam,
            // fitur push notification memang jadi tidak aktif tapi TIDAK
            // boleh mengganggu apa pun di bagian lain halaman.
            console.warn('FCM: gagal inisialisasi Firebase, push notification dinonaktifkan.', err);
        }
    }
</script>
