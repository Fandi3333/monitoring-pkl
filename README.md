# 📋 Monitoring PKL - Sistem Monitoring Praktik Kerja Lapangan

Aplikasi monitoring PKL berbasis **PHP Native (PHP 8+)** untuk SMK. Fokus utama: Presensi berbasis radius lokasi (Geofencing), Jurnal Harian, dan Monitoring Guru Pembimbing.

**Capstone Project - Kelompok C - Universitas Terbuka**

### 👥 Anggota Kelompok C
| NIM | Nama | Role di Project Ini |
| :--- | :--- | :--- |
| 048140247 | DESY RATNATIKA | UI/UX Dashboard RuangPKL & Dokumentasi |
| 049615361 | AHMAD RIDHAN | Modul Siswa (Presensi & Jurnal) |
| 050068852 | FANDI APRIADI | Project Starter, Auth & CRUD Tempat PKL |
| 050482927 | KRISDIANTO | Dashboard Logic, Geofencing & Laporan |
| 051728992 | ASTRID EKA DYASHA | Testing, Validasi & Export Laporan |

### ✨ Fitur yang Sudah Jadi
- [x] **Auth Aman:** `session`, `csrf_token()`, `verify_csrf()`, `require_role(['admin','guru','siswa'])`
- [x] **Dashboard 3 Role:** 
    - Admin: Ringkasan statistik + Aktivitas Terbaru + Grafik Kehadiran
    - Guru: Daftar Siswa Bimbingan + Validasi Jurnal
    - Siswa: Kartu Status PKL + Presensi Hari Ini
- [x] **Presensi Geofencing:** Validasi radius pakai fungsi `haversine_meters()` - status `valid` / `luar_radius`
- [x] **Jurnal Harian:** Status `pending`, `approved`, `revision`
- [x] **Helper Lengkap:** `e()` anti XSS, `flash()` notifikasi, `id_date()`, `role_label()`

### 🛠️ Tech Stack
- PHP Native 8.1+ (Strict Types)
- MySQL / MariaDB
- TailwindCSS (UI RuangPKL Sage Green #e8f5e9)
- PHPMailer (di `includes/mailer.php`) - ada panduan di `SMTP-SETUP.md`

### 📁 Struktur Folder monitoring-pkl-main/
├── assets/              # CSS & JS RuangPKL
├── config/
│   └── config.php       # Koneksi PDO $pdo
├── database/
│   └── database.sql     # Import DB awal
├── includes/
│   ├── functions.php    # Core: e(), redirect(), require_login(), haversine_meters()
│   └── mailer.php       # Kirim email notifikasi
├── uploads/             # Bukti foto presensi
├── index.php            # Halaman Login
├── auth_action.php      # Proses login & set $_SESSION['user']
├── dashboard.php        # 1 file untuk 3 dashboard (cek role)
└── SMTP-SETUP.md

### 🚀 Cara Install (PHP Native - Bukan Laravel)
1. Clone: `git clone https://github.com/Fandi3333/monitoring-pkl-main.git`
2. Buat database `monitoring_pkl` di phpMyAdmin
3. Import `database/database.sql`
4. Setting koneksi DB di `config/config.php`
5. Setting SMTP di `includes/mailer.php` (lihat `SMTP-SETUP.md`)
6. Jalankan di XAMPP: `http://localhost/monitoring-pkl-main/`

Tidak perlu `composer install` dan `php artisan` karena ini PHP Native.

### 🔑 Akun Demo (dari `auth_action.php`)
| Role | Email / Username | Password |
| :--- | :--- | :--- |
| Admin / Kaprog | admin@smk.sch.id | admin123 |
| Guru Pembimbing | guru@smk.sch.id | guru123 |
| Siswa PKL | siswa@smk.sch.id | siswa123 |

### 📌 Roadmap Capstone (Tugas Kelompok)
- [x] Base Project oleh Fandi
- [x] Selesaikan CRUD Tempat PKL (latitude, longitude, radius_meter, kuota) oleh Krisdianto
- [ ] Perbaiki Edit/Hapus Kelas
- [ ] Laporan Akhir & Presentasi

---
Dibuat untuk Tugas Capstone 55 Kelompok C - 2026

