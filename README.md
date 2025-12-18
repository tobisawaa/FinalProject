# FinalProject — Weather Notifications & Alerts (PHP)

**Ringkasan**

FinalProject adalah aplikasi web PHP sederhana untuk mengumpulkan data cuaca, menyimpan log cuaca, dan mengirim notifikasi push (Web Push) kepada pelanggan. Aplikasi ini berisi manajemen aktivitas, otentikasi pengguna, verifikasi email, manajemen profil, dan API publik untuk data cuaca dan langganan notifikasi.

---

## Fitur Utama

- Otentikasi pengguna (register/login) dengan verifikasi email
- Reset password via email
- Berlangganan dan pengiriman Web Push Notifications (VAPID)
- API publik untuk cuaca, kota, dan subscribe
- Dashboard admin untuk analitik dan manajemen
- Penyimpanan log cuaca untuk analisis
- Upload foto profil dan manajemen aktivitas

---

## Struktur Proyek (ringkas)

- `public/` — Frontend public-facing PHP pages, assets, service worker
- `src/` — Kode sumber (classes, helpers, middleware)
- `tools/` — Skrip maintenance (migrasi DB, generate VAPID, run alerts)
- `vendor/` — Dependensi Composer

---

## Persyaratan & Dependensis

- PHP 7.4+ atau lebih tinggi
- MySQL / MariaDB
- Composer
- Node/npm (untuk tooling jika diperlukan)
- Fitur Web Push (service worker di `public/sw.js`)

---

## Instalasi & Setup

1. Clone repository

```bash
git clone <repo-url> finalproject
cd finalproject
```

2. Install dependensi PHP

```bash
composer install
```

3. Konfigurasi database

- Salin atau sesuaikan `config/env.php` untuk menambahkan kredensial database dan pengaturan lain (lihat contoh di file tersebut).
- Jalankan migrasi (file migrasi ada di folder `tools/`):

```bash
php tools/migrate_db.php
php tools/migrate_email_verification.php
php tools/migrate_admin_guest.php
```

4. Konfigurasi VAPID keys (untuk Web Push)

- Jika belum punya VAPID keys, generate dengan skrip:

```bash
php tools/generate_vapid.php
```

- Salin hasil VAPID (public/private) ke `config/env.php` atau ke tempat konfigurasi yang sesuai.

5. Jalankan aplikasi di lingkungan lokal (Laragon, XAMPP, Docker, dsb.)

- Pastikan `public/` dipakai sebagai document root
- Akses `http://localhost/finalproject/public/` 

---

## Konfigurasi yang penting

- `config/env.php`:
  - DB_HOST, DB_NAME, DB_USER, DB_PASS
  - MAIL settings (SMTP) — diperlukan untuk verifikasi email dan reset password
  - VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY
  - BASE_URL — base path aplikasi

- `src/EmailVerification.php` dan `src/Auth.php` mengatur alur verifikasi dan otentikasi

---

## Menjalankan pekerjaan terjadwal dan notifikasi 

- `tools/run_alerts.php` — memeriksa kondisi cuaca dan membuat notifikasi jika diperlukan
- `public/auto_notification.php`, `public/auto_trigger.php` — endpoints yang dapat dipanggil via cron atau scheduler eksternal

Contoh crontab (setiap 10 menit):

```cron
*/10 * * * * /usr/bin/php /path/to/project/tools/run_alerts.php
```

---

## API Publik (ringkasan)

- `public/api/weather.php` — data cuaca (authed / public endpoints tersedia)
- `public/api/cities.php` — daftar kota
- `public/api/subscribe.php` — endpoint untuk menambah subscriber push
- `public/api/notification.php` — kontrol notifikasi (internal)

Gunakan tools di `src/ApiClientWeather.php` untuk mengakses sumber data cuaca eksternal.

---

## Pengembangan & Testing

- Gunakan `composer lint` / `composer test` jika ada (tidak tersedia default — tambahkan sesuai kebijakan proyek)
- Periksa `public/sw.js` dan `assets/subscribe.js` untuk alur subscribe-notify di sisi klien

---

## Troubleshooting & Tips

- Lihat `TROUBLESHOOTING_ADVANCED.md` untuk masalah advanced
- Untuk masalah verifikasi email, lihat `README_EMAIL_VERIFICATION.md`
- Pastikan waktu server (timezone) sinkron saat menggunakan token/expiration

> Tip: Jika notifikasi push tidak muncul, periksa VAPID keys, HTTPS (service worker memerlukan HTTPS pada domain non-localhost), dan console/browser devtools untuk error.

---

## Kontribusi & Lisensi

- Pull requests welcome — buka issue terlebih dahulu untuk fitur besar
- Ikuti coding style yang ada dan dokumentasikan perubahan

**License:** (Tambahkan lisensi yang sesuai, contoh: MIT)

---

## Kontak / Maintainance

- Author: (if24.muhamadmaharandi@mhs.ubpkarawang.ac.id)

---

