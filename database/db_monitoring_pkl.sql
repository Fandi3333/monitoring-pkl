CREATE DATABASE IF NOT EXISTS db_monitoring_pkl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_monitoring_pkl;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS logbook;
DROP TABLE IF EXISTS presensi;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS kelas_pkl;
DROP TABLE IF EXISTS tempat_pkl;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE tempat_pkl (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_industri VARCHAR(150) NOT NULL,
    alamat TEXT NOT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    radius_meter SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE kelas_pkl (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(100) NOT NULL,
    kode_kelas VARCHAR(40) NOT NULL UNIQUE,
    tempat_pkl_id BIGINT UNSIGNED NOT NULL,
    kuota_guru TINYINT UNSIGNED NOT NULL DEFAULT 1,
    kuota_siswa SMALLINT UNSIGNED NOT NULL DEFAULT 20,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_kelas_tempat FOREIGN KEY (tempat_pkl_id) REFERENCES tempat_pkl(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(190) NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'guru', 'siswa') NOT NULL,
    kelas_id BIGINT UNSIGNED NULL,
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_kelas FOREIGN KEY (kelas_id) REFERENCES kelas_pkl(id) ON DELETE SET NULL,
    INDEX idx_users_kelas_role (kelas_id, role, aktif)
) ENGINE=InnoDB;

CREATE TABLE presensi (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    siswa_id BIGINT UNSIGNED NOT NULL,
    kelas_pkl_id BIGINT UNSIGNED NOT NULL,
    tanggal DATE NOT NULL,
    jam TIME NOT NULL,
    latitude_siswa DECIMAL(10,7) NOT NULL,
    longitude_siswa DECIMAL(10,7) NOT NULL,
    jarak_meter DECIMAL(10,2) NOT NULL,
    status ENUM('valid', 'luar_radius') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_presensi_siswa FOREIGN KEY (siswa_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_presensi_kelas FOREIGN KEY (kelas_pkl_id) REFERENCES kelas_pkl(id) ON DELETE CASCADE,
    UNIQUE KEY uq_presensi_siswa_tanggal (siswa_id, tanggal),
    INDEX idx_presensi_kelas_tanggal (kelas_pkl_id, tanggal)
) ENGINE=InnoDB;

CREATE TABLE logbook (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    siswa_id BIGINT UNSIGNED NOT NULL,
    kelas_pkl_id BIGINT UNSIGNED NOT NULL,
    tanggal DATE NOT NULL,
    kegiatan TEXT NOT NULL,
    foto_bukti VARCHAR(255) NULL,
    nilai DECIMAL(5,2) NULL,
    catatan_guru TEXT NULL,
    status ENUM('pending', 'approved', 'revision') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_logbook_siswa FOREIGN KEY (siswa_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_logbook_kelas FOREIGN KEY (kelas_pkl_id) REFERENCES kelas_pkl(id) ON DELETE CASCADE,
    UNIQUE KEY uq_logbook_siswa_tanggal (siswa_id, tanggal),
    INDEX idx_logbook_kelas_status (kelas_pkl_id, status)
) ENGINE=InnoDB;

