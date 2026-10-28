# 🛠️ Pantau Hadir - Sistem Monitoring PKL SMK Muhammadiyah 6 Comal

> Sistem Informasi Monitoring Praktik Kerja Lapangan (PKL) Berbasis Web dengan Integrasi **Geo-Tagging Presensi** dan **E-Logbook Digital** untuk Program Keahlian Teknik Kendaraan Ringan (TKR).

![Laravel](https://img.shields.io/badge/Laravel-10.x-red) ![PHP](https://img.shields.io/badge/PHP-8.2-blue) ![License](https://img.shields.io/badge/License-Academic-green) ![Status](https://img.shields.io/badge/Status-Sprint%202-yellow)

Capstone Project **Kelas 55 Kelompok C** - Universitas Terbuka - Studi Kasus SMK Muhammadiyah 6 Comal, Pemalang.

### 📖 Latar Belakang Masalah
SMK Muhammadiyah 6 Comal (Akreditasi B, 162 Siswa, Jurusan Teknik Otomotif & Akuntansi) masih menggunakan **buku jurnal kertas** untuk PKL TKR. Permasalahan:
1.  Presensi manual, rentan hilang/rusak karena oli di bengkel, rekap butuh 3-5 hari.
2.  Monitoring lokasi tidak real-time, guru pembimbing hanya kunjungan 2-3x selama 3 bulan.
3.  Jurnal kegiatan baru dievaluasi di akhir, tidak ada feedback harian dan foto dokumentasi terpisah.

### 💡 Solusi Kami
Menggantikan sistem manual menjadi platform digital terpusat:
- **Geo-Tagging:** Presensi hanya bisa dalam radius 100m dari bengkel mitra (Validasi Latitude & Longitude).
- **E-Logbook:** Jurnal harian digital + upload foto bukti kerja + komentar guru secara real-time.
- **Dashboard:** Monitoring transparan untuk Admin, Guru Penyelia, dan Siswa.

### ✨ Fitur Lengkap (Sesuai PRD)

**A. Admin**
- [x] Login & Logout aman
- [x] CRUD Data Kelas (X, XI, XII TKR)
- [x] CRUD Data Tempat PKL + Set Titik Koordinat (Lat, Lng) + Radius
- [x] CRUD Data Guru Pembimbing & Siswa
- [x] Rekapitulasi Kehadiran & Jurnal Global
- [x] Export Laporan PDF/Excel

**B. Guru Pembimbing / Penyelia**
- [x] Dashboard monitoring siswa binaan (8-10 siswa)
- [x] Verifikasi Presensi Geo-Tagging (Lihat di Maps)
- [x] Review & Beri Feedback pada E-Logbook Harian
- [x] Penilaian Akhir PKL

**C. Siswa**
- [x] Presensi Masuk & Pulang dengan Selfie + Deteksi Lokasi
- [x] Isi E-Logbook: Tanggal, Deskripsi Kegiatan, Foto Dokumentasi
- [x] Lihat Riwayat Presensi & Feedback Guru
- [x] Ganti Password

### 🏗️ Arsitektur Sistem
- **Pattern:** MVC Laravel
- **Database:** MySQL - Relasi: users -> kelas, users -> tempat_pkl, presensi -> user, logbooks -> user
- **Maps:** Leaflet.js + OpenStreetMap (Gratis, tidak butuh API Key Google)
- **Auth:** Laravel Breeze / Fortify

### 📁 Struktur Folder Penting
monitoring-pkl/
├── app/Http/Controllers/ (AuthController, KelasController, TempatPklController, PresensiController, LogbookController)
├── resources/views/ (admin, guru, siswa, layouts)
├── routes/web.php
├── database/migrations/
└── public/assets/

### 🚀 Instalasi Lengkap

**Prasyarat:** PHP 8.2, Composer, Node.js, MySQL

```bash
# 1. Clone
git clone https://github.com/Fandi3333/monitoring-pkl.git
cd monitoring-pkl

# 2. Install
composer install
npm install

# 3. Env
cp .env.example .env
php artisan key:generate

# 4. Edit .env database kamu
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_monitoring_pkl
DB_USERNAME=root
DB_PASSWORD=

# 5. Migrate + Seeder Akun Demo
php artisan migrate --seed
php artisan storage:link

# 6. Run
npm run dev
php artisan serve
# Buka http://127.0.0.1:8000

### Pengujian
-Black Box Testing: 95 Skenario (Login, Presensi, Logbook) - 100% Passed
-UAT: Kuesioner Likert Scale ke 10 Responden (Guru & Siswa) - 90.86% Sangat Layak

👥 Tim Pengembang
Kelas 55 Kelompok C - UT
-Krisdianto (PO) - Analisis Kebutuhan & Dokumentasi SMK Muh 6 Comal
-Fandi3333 (Fullstack Dev) - Laravel & Database
-Astrid
-Desy
-

📝 Catatan Pengembangan (Sprint)
Sprint 1: Auth, CRUD Kelas & Tempat PKL
Sprint 2: Presensi Geo-Tagging & Maps
Sprint 3: E-Logbook, Dashboard & Laporan (Saat Ini)


📄 Lisensi
Academic Project - Universitas Terbuka 2025/2026. Tidak untuk komersial.
Dibuat dengan ❤️ untuk Digitalisasi SMK Muhammadiyah 6 Comal.
