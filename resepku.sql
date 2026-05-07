CREATE DATABASE IF NOT EXISTS resepku CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE resepku;

CREATE TABLE IF NOT EXISTS pengguna (
    pengguna_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_pengguna VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    kata_sandi VARCHAR(255) NOT NULL,
    foto_profil VARCHAR(255) NULL,
    bio TEXT NULL,
    role ENUM('pengguna','admin') NOT NULL DEFAULT 'pengguna',
    status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recipes (
    resep_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pengguna_id BIGINT UNSIGNED NOT NULL,
    nama_resep VARCHAR(255) NOT NULL,
    deskripsi TEXT,
    langkah_resep TEXT,
    waktu_memasak INT,
    porsi INT,
    foto_resep VARCHAR(255) NULL,
    kategori VARCHAR(100) NULL,
    tingkat_kesulitan ENUM('mudah','sedang','sulit') NOT NULL DEFAULT 'sedang',
    dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT recipes_pengguna_fk FOREIGN KEY (pengguna_id) REFERENCES pengguna(pengguna_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bahan_resep (
    bahan_resep_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resep_id BIGINT UNSIGNED NOT NULL,
    nama_bahan VARCHAR(150) NOT NULL,
    jumlah DECIMAL(8,2) NULL,
    satuan VARCHAR(30) NULL,
    keterangan VARCHAR(80) NULL,
    CONSTRAINT bahan_resep_resep_fk FOREIGN KEY (resep_id) REFERENCES recipes(resep_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS peralatan_resep (
    peralatan_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resep_id BIGINT UNSIGNED NOT NULL,
    nama_peralatan VARCHAR(150) NOT NULL,
    CONSTRAINT peralatan_resep_resep_fk FOREIGN KEY (resep_id) REFERENCES recipes(resep_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kategori_resep (
    kategori_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS favorite (
    favorite_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pengguna_id BIGINT UNSIGNED NOT NULL,
    resep_id BIGINT UNSIGNED NOT NULL,
    dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY favorite_unique (pengguna_id, resep_id),
    CONSTRAINT favorite_pengguna_fk FOREIGN KEY (pengguna_id) REFERENCES pengguna(pengguna_id) ON DELETE CASCADE,
    CONSTRAINT favorite_resep_fk FOREIGN KEY (resep_id) REFERENCES recipes(resep_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ratings (
    rating_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pengguna_id BIGINT UNSIGNED NOT NULL,
    resep_id BIGINT UNSIGNED NOT NULL,
    rating_value DECIMAL(2,1) NOT NULL,
    dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY ratings_unique (pengguna_id, resep_id),
    CONSTRAINT ratings_pengguna_fk FOREIGN KEY (pengguna_id) REFERENCES pengguna(pengguna_id) ON DELETE CASCADE,
    CONSTRAINT ratings_resep_fk FOREIGN KEY (resep_id) REFERENCES recipes(resep_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS likes (
    like_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pengguna_id BIGINT UNSIGNED NOT NULL,
    resep_id BIGINT UNSIGNED NOT NULL,
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (pengguna_id, resep_id),
    CONSTRAINT likes_pengguna_fk FOREIGN KEY (pengguna_id) REFERENCES pengguna(pengguna_id) ON DELETE CASCADE,
    CONSTRAINT likes_resep_fk FOREIGN KEY (resep_id) REFERENCES recipes(resep_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS komentar (
    komentar_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pengguna_id BIGINT UNSIGNED NOT NULL,
    resep_id BIGINT UNSIGNED NOT NULL,
    isi_komentar TEXT NOT NULL,
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT komentar_pengguna_fk FOREIGN KEY (pengguna_id) REFERENCES pengguna(pengguna_id) ON DELETE CASCADE,
    CONSTRAINT komentar_resep_fk FOREIGN KEY (resep_id) REFERENCES recipes(resep_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS following (
    following_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    follower_id BIGINT UNSIGNED NOT NULL,
    following_id_user BIGINT UNSIGNED NOT NULL,
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_follow (follower_id, following_id_user),
    CONSTRAINT following_follower_fk FOREIGN KEY (follower_id) REFERENCES pengguna(pengguna_id) ON DELETE CASCADE,
    CONSTRAINT following_target_fk FOREIGN KEY (following_id_user) REFERENCES pengguna(pengguna_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cs (
    ticket_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pelapor_id BIGINT UNSIGNED NULL,
    target_tipe ENUM('resep','pengguna') NOT NULL,
    target_resep_id BIGINT UNSIGNED NULL,
    target_pengguna_id BIGINT UNSIGNED NULL,
    alasan TEXT,
    status ENUM('menunggu','ditolak','selesai') DEFAULT 'menunggu',
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT cs_pelapor_fk FOREIGN KEY (pelapor_id) REFERENCES pengguna(pengguna_id) ON DELETE SET NULL,
    CONSTRAINT cs_resep_fk FOREIGN KEY (target_resep_id) REFERENCES recipes(resep_id) ON DELETE SET NULL,
    CONSTRAINT cs_pengguna_fk FOREIGN KEY (target_pengguna_id) REFERENCES pengguna(pengguna_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO kategori_resep (nama_kategori) VALUES
('ayam'), ('vegetarian'), ('dessert'), ('drinks'), ('salad'), ('seafood');
