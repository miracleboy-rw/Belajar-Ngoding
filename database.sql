CREATE DATABASE IF NOT EXISTS cdss_med CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cdss_med;

SET FOREIGN_KEY_CHECKS = 0;
DROP VIEW IF EXISTS interaksi;
DROP TABLE IF EXISTS detail_resep;
DROP TABLE IF EXISTS resep;
DROP TABLE IF EXISTS aturan_interaksi;
DROP TABLE IF EXISTS alergi_pasien;
DROP TABLE IF EXISTS obat;
DROP TABLE IF EXISTS pasien;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE pasien (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(120) NOT NULL,
    usia INT NOT NULL,
    no_hp VARCHAR(25) NULL,
    riwayat_penyakit TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE obat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_obat VARCHAR(120) NOT NULL UNIQUE,
    kategori VARCHAR(80) NULL,
    harga DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE alergi_pasien (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pasien_id INT NOT NULL,
    obat_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_alergi_pasien FOREIGN KEY (pasien_id) REFERENCES pasien(id) ON DELETE CASCADE,
    CONSTRAINT fk_alergi_obat FOREIGN KEY (obat_id) REFERENCES obat(id) ON DELETE CASCADE,
    CONSTRAINT uq_alergi_pasien_obat UNIQUE (pasien_id, obat_id)
) ENGINE=InnoDB;

CREATE TABLE aturan_interaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    obat1_id INT NOT NULL,
    obat2_id INT NOT NULL,
    tingkat_bahaya ENUM('Kuning', 'Merah') NOT NULL,
    deskripsi_efek TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_interaksi_obat1 FOREIGN KEY (obat1_id) REFERENCES obat(id) ON DELETE CASCADE,
    CONSTRAINT fk_interaksi_obat2 FOREIGN KEY (obat2_id) REFERENCES obat(id) ON DELETE CASCADE,
    CONSTRAINT chk_interaksi_obat_berbeda CHECK (obat1_id <> obat2_id),
    CONSTRAINT uq_interaksi_pasangan UNIQUE (obat1_id, obat2_id)
) ENGINE=InnoDB;

CREATE OR REPLACE VIEW interaksi AS SELECT * FROM aturan_interaksi;

CREATE TABLE resep (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pasien_id INT NOT NULL,
    tanggal DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_biaya DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_resep_pasien FOREIGN KEY (pasien_id) REFERENCES pasien(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE detail_resep (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resep_id INT NOT NULL,
    obat_id INT NOT NULL,
    dosis VARCHAR(100) NOT NULL,
    aturan_pakai VARCHAR(180) NOT NULL,
    harga_satuan DECIMAL(12,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_detail_resep FOREIGN KEY (resep_id) REFERENCES resep(id) ON DELETE CASCADE,
    CONSTRAINT fk_detail_obat FOREIGN KEY (obat_id) REFERENCES obat(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO pasien (id, nama_lengkap, usia, no_hp, riwayat_penyakit) VALUES
(1, 'Budi Santoso', 45, '081234567890', 'Hipertensi'),
(2, 'Siti Aminah', 32, '081298765432', 'Riwayat gastritis'),
(3, 'Andi Pratama', 56, '081377788899', 'Asam urat');

INSERT INTO obat (id, nama_obat, kategori, harga) VALUES
(1, 'Paracetamol', 'Analgesik/Antipiretik', 8000),
(2, 'Amoxicillin', 'Antibiotik', 15000),
(3, 'Antasida', 'Antasida', 6000),
(4, 'Captopril', 'Antihipertensi ACE Inhibitor', 12000),
(5, 'Allopurinol', 'Antihiperurisemia', 14000);

INSERT INTO alergi_pasien (pasien_id, obat_id) VALUES
(1, 2),
(2, 1);

INSERT INTO aturan_interaksi (obat1_id, obat2_id, tingkat_bahaya, deskripsi_efek) VALUES
(2, 5, 'Kuning', 'Risiko ruam kulit meningkat'),
(4, 5, 'Merah', 'Risiko toksisitas parah, gunakan dengan hati-hati');
