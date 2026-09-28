// LETAKKAN DI: public/firebase-messaging-sw.js
// (HARUS di folder public/ paling luar, bukan di dalam subfolder, supaya
// scope service worker mencakup seluruh situs)
//
// File ini wajib pakai isi APAKAH KONSTAN (bukan Blade) karena diakses
// langsung sebagai file statis oleh browser -- jadi konfigurasi Firebase di
// bawah ini DIISI MANUAL dari Firebase Console (lihat README_Fase7.md bagian
// "Cara membuat kredensial Firebase"), bukan dari .env.

importScripts('https://www.gstatic.com/firebasejs/10.13.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.13.0/firebase-messaging-compat.js');

firebase.initializeApp({
  apiKey: "AIzaSyAbridiMT_t9DbGaHNmbcg7LG8ZISKhKDg",
  authDomain: "sim-bk-kartika.firebaseapp.com",
  projectId: "sim-bk-kartika",
  storageBucket: "sim-bk-kartika.firebasestorage.app",
  messagingSenderId: "252982486202",
  appId: "1:252982486202:web:ef34bc2316b3e85dd54e83"
});

const messaging = firebase.messaging();

// Notifikasi yang masuk saat tab SIM BK sedang tidak aktif / browser
// diminimize -- ditangani di sini (background).
messaging.onBackgroundMessage((payload) => {
  const judul = payload.notification?.title || 'SIM BK Kartika';
  const opsi = {
    body: payload.notification?.body || '',
    icon: '/favicon.ico',
    data: { link: payload.data?.link || '/' },
  };

  self.registration.showNotification(judul, opsi);
});

// Saat notifikasi diklik, buka halaman terkait (mis. link ke detail program/jurnal)
self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  const link = event.notification.data?.link || '/';
  event.waitUntil(clients.openWindow(link));
});
