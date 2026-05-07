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

INSERT IGNORE INTO pengguna (
    pengguna_id, nama_pengguna, email, kata_sandi, foto_profil, bio, role, status
) VALUES
(1, 'Nayaka', 'nayaka@resepku.test', '$2y$10$dummyhashdummyhashdummyhashdummyhashdummyhashdummyhashdum', 'assets/img/home-profile.png', 'ResepKu team account for demo content.', 'admin', 'aktif'),
(2, 'ResepKu Team', 'team@resepku.test', '$2y$10$dummyhashdummyhashdummyhashdummyhashdummyhashdummyhashdum', 'assets/img/home-profile.png', 'Editorial recipe account.', 'pengguna', 'aktif');

INSERT IGNORE INTO recipes (
    resep_id, pengguna_id, nama_resep, deskripsi, langkah_resep, waktu_memasak, porsi, foto_resep, kategori, tingkat_kesulitan
) VALUES
(1, 1, 'Testing 1', 'testing', 'testing', 20, 2, 'assets/img/recipe-salad-hero.png', 'salad', 'mudah'),
(2, 2, 'Testing 2', 'testing', 'testing', 15, 1, 'assets/img/recipe-salad-card.png', 'drinks', 'mudah'),
(3, 2, 'Testing 3', 'testing', 'testing', 45, 6, 'assets/img/recipe-salad-card.png', 'dessert', 'sedang'),
(4, 2, 'Testing 4', 'testing', 'testing', 30, 4, 'assets/img/recipe-salad-card.png', 'vegetarian', 'mudah'),
(5, 1, 'Testing 5', 'testing', 'testing', 25, 2, 'assets/img/recipe-salad-card.png', 'ayam', 'mudah'),
(6, 2, 'Testing 6', 'testing', 'testing', 10, 1, 'assets/img/recipe-salad-card.png', 'dessert', 'mudah'),
(7, 1, 'Testing 7', 'testing', 'testing', 22, 2, 'assets/img/recipe-salad-card.png', 'seafood', 'sedang'),
(8, 2, 'Testing 8', 'testing', 'testing', 18, 2, 'assets/img/recipe-salad-card.png', 'vegetarian', 'mudah');

INSERT IGNORE INTO bahan_resep (resep_id, nama_bahan, jumlah, satuan, keterangan) VALUES
(1, 'Chicken breast fillet', 2, 'pcs', NULL),
(1, 'Romaine lettuce', 2, 'cups', NULL),
(1, 'Cherry tomatoes', 1, 'cup', NULL),
(1, 'Cucumber', 1, 'pcs', 'sliced'),
(1, 'Red onion', 0.25, 'pcs', NULL),
(1, 'Salad dressing', 2, 'tbsp', NULL),
(2, 'Matcha powder', 1, 'tsp', NULL),
(2, 'Hot water', 2, 'tsp', NULL),
(2, 'Milk', 200, 'ml', NULL),
(2, 'Sugar or honey', 1, 'tsp', NULL),
(3, 'Eggs', 2, 'pcs', NULL),
(3, 'Flour', 100, 'g', NULL),
(3, 'Sugar', 80, 'g', NULL),
(3, 'Whipped cream', 150, 'ml', NULL),
(3, 'Fresh strawberries', NULL, NULL, NULL),
(4, 'Tomatoes', 5, 'pcs', 'ripe'),
(4, 'Onion', 1, 'pcs', NULL),
(4, 'Garlic', 2, 'cloves', NULL),
(4, 'Broth', 200, 'ml', NULL),
(4, 'Cream', 100, 'ml', NULL),
(5, 'Rice', 1, 'cup', NULL),
(5, 'Chicken thighs', 2, 'pcs', NULL),
(5, 'Fresh parsley', NULL, NULL, NULL),
(5, 'Garlic sauce', NULL, NULL, NULL),
(6, 'Yogurt', 1, 'cup', NULL),
(6, 'Banana slices', NULL, NULL, NULL),
(6, 'Strawberries', NULL, NULL, NULL),
(6, 'Granola', NULL, NULL, NULL),
(7, 'Warm rice', 1, 'cup', NULL),
(7, 'Tuna', 1, 'can', NULL),
(7, 'Chili sauce', NULL, NULL, NULL),
(7, 'Green onion', NULL, NULL, NULL),
(8, 'Pasta', 200, 'g', NULL),
(8, 'Garlic', 3, 'cloves', NULL),
(8, 'Olive oil', NULL, NULL, NULL),
(8, 'Parsley', NULL, NULL, NULL);

INSERT IGNORE INTO peralatan_resep (resep_id, nama_peralatan) VALUES
(1, 'Mixing bowl'),
(1, 'Sharp knife'),
(1, 'Frying pan'),
(1, 'Serving plate'),
(2, 'Whisk'),
(2, 'Glass'),
(2, 'Small bowl'),
(3, 'Mixing bowl'),
(3, 'Oven'),
(3, 'Spatula'),
(4, 'Pot'),
(4, 'Blender'),
(4, 'Wooden spoon'),
(5, 'Rice cooker'),
(5, 'Pan'),
(5, 'Serving bowl'),
(6, 'Glass cup'),
(6, 'Spoon'),
(7, 'Bowl'),
(7, 'Spoon'),
(7, 'Pan'),
(8, 'Pot'),
(8, 'Pan'),
(8, 'Tongs');
