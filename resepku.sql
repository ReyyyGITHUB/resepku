-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 08, 2026 at 01:02 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `resepku`
--

-- --------------------------------------------------------

--
-- Table structure for table `bahan_resep`
--

CREATE TABLE `bahan_resep` (
  `bahan_resep_id` bigint UNSIGNED NOT NULL,
  `resep_id` bigint UNSIGNED NOT NULL,
  `nama_bahan` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jumlah` decimal(8,2) DEFAULT NULL,
  `satuan` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keterangan` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bahan_resep`
--

INSERT INTO `bahan_resep` (`bahan_resep_id`, `resep_id`, `nama_bahan`, `jumlah`, `satuan`, `keterangan`) VALUES
(1, 1, 'Chicken breast fillet', 2.00, 'pcs', NULL),
(2, 1, 'Romaine lettuce', 2.00, 'cups', NULL),
(3, 1, 'Cherry tomatoes', 1.00, 'cup', NULL),
(4, 1, 'Cucumber', 1.00, 'pcs', 'sliced'),
(5, 1, 'Red onion', 0.25, 'pcs', NULL),
(6, 1, 'Salad dressing', 2.00, 'tbsp', NULL),
(7, 2, 'Matcha powder', 1.00, 'tsp', NULL),
(8, 2, 'Hot water', 2.00, 'tsp', NULL),
(9, 2, 'Milk', 200.00, 'ml', NULL),
(10, 2, 'Sugar or honey', 1.00, 'tsp', NULL),
(11, 3, 'Eggs', 2.00, 'pcs', NULL),
(12, 3, 'Flour', 100.00, 'g', NULL),
(13, 3, 'Sugar', 80.00, 'g', NULL),
(14, 3, 'Whipped cream', 150.00, 'ml', NULL),
(15, 3, 'Fresh strawberries', NULL, NULL, NULL),
(16, 4, 'Tomatoes', 5.00, 'pcs', 'ripe'),
(17, 4, 'Onion', 1.00, 'pcs', NULL),
(18, 4, 'Garlic', 2.00, 'cloves', NULL),
(19, 4, 'Broth', 200.00, 'ml', NULL),
(20, 4, 'Cream', 100.00, 'ml', NULL),
(21, 5, 'Rice', 1.00, 'cup', NULL),
(22, 5, 'Chicken thighs', 2.00, 'pcs', NULL),
(23, 5, 'Fresh parsley', NULL, NULL, NULL),
(24, 5, 'Garlic sauce', NULL, NULL, NULL),
(25, 6, 'Yogurt', 1.00, 'cup', NULL),
(26, 6, 'Banana slices', NULL, NULL, NULL),
(27, 6, 'Strawberries', NULL, NULL, NULL),
(28, 6, 'Granola', NULL, NULL, NULL),
(37, 1, 'Chicken breast fillet', 2.00, 'pcs', NULL),
(38, 1, 'Romaine lettuce', 2.00, 'cups', NULL),
(39, 1, 'Cherry tomatoes', 1.00, 'cup', NULL),
(40, 1, 'Cucumber', 1.00, 'pcs', 'sliced'),
(41, 1, 'Red onion', 0.25, 'pcs', NULL),
(42, 1, 'Salad dressing', 2.00, 'tbsp', NULL),
(43, 2, 'Matcha powder', 1.00, 'tsp', NULL),
(44, 2, 'Hot water', 2.00, 'tsp', NULL),
(45, 2, 'Milk', 200.00, 'ml', NULL),
(46, 2, 'Sugar or honey', 1.00, 'tsp', NULL),
(47, 3, 'Eggs', 2.00, 'pcs', NULL),
(48, 3, 'Flour', 100.00, 'g', NULL),
(49, 3, 'Sugar', 80.00, 'g', NULL),
(50, 3, 'Whipped cream', 150.00, 'ml', NULL),
(51, 3, 'Fresh strawberries', NULL, NULL, NULL),
(52, 4, 'Tomatoes', 5.00, 'pcs', 'ripe'),
(53, 4, 'Onion', 1.00, 'pcs', NULL),
(54, 4, 'Garlic', 2.00, 'cloves', NULL),
(55, 4, 'Broth', 200.00, 'ml', NULL),
(56, 4, 'Cream', 100.00, 'ml', NULL),
(57, 5, 'Rice', 1.00, 'cup', NULL),
(58, 5, 'Chicken thighs', 2.00, 'pcs', NULL),
(59, 5, 'Fresh parsley', NULL, NULL, NULL),
(60, 5, 'Garlic sauce', NULL, NULL, NULL),
(61, 6, 'Yogurt', 1.00, 'cup', NULL),
(62, 6, 'Banana slices', NULL, NULL, NULL),
(63, 6, 'Strawberries', NULL, NULL, NULL),
(64, 6, 'Granola', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cs`
--

CREATE TABLE `cs` (
  `ticket_id` bigint UNSIGNED NOT NULL,
  `pelapor_id` bigint UNSIGNED DEFAULT NULL,
  `target_tipe` enum('resep','pengguna') COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_resep_id` bigint UNSIGNED DEFAULT NULL,
  `target_pengguna_id` bigint UNSIGNED DEFAULT NULL,
  `kategori_laporan` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'lainnya',
  `catatan_laporan` text COLLATE utf8mb4_unicode_ci,
  `alasan` text COLLATE utf8mb4_unicode_ci,
  `status` enum('menunggu','ditolak','selesai') COLLATE utf8mb4_unicode_ci DEFAULT 'menunggu',
  `dibuat_pada` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `favorite`
--

CREATE TABLE `favorite` (
  `favorite_id` bigint UNSIGNED NOT NULL,
  `pengguna_id` bigint UNSIGNED NOT NULL,
  `resep_id` bigint UNSIGNED NOT NULL,
  `dibuat_pada` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `following`
--

CREATE TABLE `following` (
  `following_id` bigint UNSIGNED NOT NULL,
  `follower_id` bigint UNSIGNED NOT NULL,
  `following_id_user` bigint UNSIGNED NOT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kategori_resep`
--

CREATE TABLE `kategori_resep` (
  `kategori_id` bigint UNSIGNED NOT NULL,
  `nama_kategori` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kategori_resep`
--

INSERT INTO `kategori_resep` (`kategori_id`, `nama_kategori`) VALUES
(1, 'ayam'),
(3, 'dessert'),
(4, 'drinks'),
(5, 'salad'),
(6, 'seafood'),
(2, 'vegetarian');

-- --------------------------------------------------------

--
-- Table structure for table `komentar`
--

CREATE TABLE `komentar` (
  `komentar_id` bigint UNSIGNED NOT NULL,
  `pengguna_id` bigint UNSIGNED NOT NULL,
  `resep_id` bigint UNSIGNED NOT NULL,
  `isi_komentar` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

CREATE TABLE `likes` (
  `like_id` bigint UNSIGNED NOT NULL,
  `pengguna_id` bigint UNSIGNED NOT NULL,
  `resep_id` bigint UNSIGNED NOT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengguna`
--

CREATE TABLE `pengguna` (
  `pengguna_id` bigint UNSIGNED NOT NULL,
  `nama_pengguna` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kata_sandi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `foto_profil` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `role` enum('pengguna','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pengguna',
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `dibuat_pada` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengguna`
--

INSERT INTO `pengguna` (`pengguna_id`, `nama_pengguna`, `email`, `kata_sandi`, `foto_profil`, `bio`, `role`, `status`, `dibuat_pada`) VALUES
(1, 'resepku', 'resepku@resepku.test', 'resepku123', 'assets/img/home-profile.png', 'ResepKu admin account for demo content.', 'admin', 'aktif', '2026-05-07 16:22:38'),
(2, 'jembut', 'jembutlebat@gmail.com', 'jembut123', NULL, NULL, 'pengguna', 'aktif', '2026-05-07 14:15:17'),
(3, 'tempiks', 'tempe123@gmail.com', 'tempe123', NULL, NULL, 'pengguna', 'aktif', '2026-05-07 14:37:34');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `password_reset_id` bigint UNSIGNED NOT NULL,
  `pengguna_id` bigint UNSIGNED NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `dibuat_pada` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `peralatan_resep`
--

CREATE TABLE `peralatan_resep` (
  `peralatan_id` bigint UNSIGNED NOT NULL,
  `resep_id` bigint UNSIGNED NOT NULL,
  `nama_peralatan` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `peralatan_resep`
--

INSERT INTO `peralatan_resep` (`peralatan_id`, `resep_id`, `nama_peralatan`) VALUES
(1, 1, 'Mixing bowl'),
(2, 1, 'Sharp knife'),
(3, 1, 'Frying pan'),
(4, 1, 'Serving plate'),
(5, 2, 'Whisk'),
(6, 2, 'Glass'),
(7, 2, 'Small bowl'),
(8, 3, 'Mixing bowl'),
(9, 3, 'Oven'),
(10, 3, 'Spatula'),
(11, 4, 'Pot'),
(12, 4, 'Blender'),
(13, 4, 'Wooden spoon'),
(14, 5, 'Rice cooker'),
(15, 5, 'Pan'),
(16, 5, 'Serving bowl'),
(17, 6, 'Glass cup'),
(18, 6, 'Spoon'),
(25, 1, 'Mixing bowl'),
(26, 1, 'Sharp knife'),
(27, 1, 'Frying pan'),
(28, 1, 'Serving plate'),
(29, 2, 'Whisk'),
(30, 2, 'Glass'),
(31, 2, 'Small bowl'),
(32, 3, 'Mixing bowl'),
(33, 3, 'Oven'),
(34, 3, 'Spatula'),
(35, 4, 'Pot'),
(36, 4, 'Blender'),
(37, 4, 'Wooden spoon'),
(38, 5, 'Rice cooker'),
(39, 5, 'Pan'),
(40, 5, 'Serving bowl'),
(41, 6, 'Glass cup'),
(42, 6, 'Spoon');

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `rating_id` bigint UNSIGNED NOT NULL,
  `pengguna_id` bigint UNSIGNED NOT NULL,
  `resep_id` bigint UNSIGNED NOT NULL,
  `rating_value` decimal(2,1) NOT NULL,
  `dibuat_pada` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recipes`
--

CREATE TABLE `recipes` (
  `resep_id` bigint UNSIGNED NOT NULL,
  `pengguna_id` bigint UNSIGNED NOT NULL,
  `nama_resep` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `langkah_resep` text COLLATE utf8mb4_unicode_ci,
  `waktu_memasak` int DEFAULT NULL,
  `porsi` int DEFAULT NULL,
  `foto_resep` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kategori` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tingkat_kesulitan` enum('mudah','sedang','sulit') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sedang',
  `dibuat_pada` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recipes`
--

INSERT INTO `recipes` (`resep_id`, `pengguna_id`, `nama_resep`, `deskripsi`, `langkah_resep`, `waktu_memasak`, `porsi`, `foto_resep`, `kategori`, `tingkat_kesulitan`, `dibuat_pada`) VALUES
(1, 1, 'Testing 1', 'testing', 'Season the chicken with salt, pepper, and a little olive oil.\nGrill the chicken until cooked through and slice it into strips.\nWash the vegetables and arrange them in a bowl or plate.\nAdd the chicken on top, pour the dressing, and serve immediately.', 20, 2, 'assets/img/recipe-salad-hero.png', 'salad', 'mudah', '2026-05-07 16:22:38'),
(2, 2, 'Testing 2', 'testing', 'Mix matcha powder with hot water until smooth.\nHeat or froth the milk as desired.\nCombine the milk with the matcha mixture.\nAdd sweetener and serve chilled or warm.', 15, 1, 'assets/img/recipe-salad-card.png', 'drinks', 'mudah', '2026-05-07 16:22:38'),
(3, 2, 'Testing 3', 'testing', 'Whisk eggs and sugar until fluffy.\nFold in flour gently and bake the sponge.\nWhip the cream until soft peaks form.\nLayer the sponge, cream, and strawberries.', 45, 6, 'assets/img/recipe-salad-card.png', 'dessert', 'sedang', '2026-05-07 16:22:38'),
(4, 2, 'Testing 4', 'testing', 'Saute onion and garlic until fragrant.\nAdd tomatoes and broth, then simmer.\nBlend until smooth and creamy.\nFinish with cream and season to taste.', 30, 4, 'assets/img/recipe-salad-card.png', 'vegetarian', 'mudah', '2026-05-07 16:22:38'),
(5, 1, 'Testing 5', 'testing', 'Cook the rice until fluffy.\nPan-fry the chicken with herbs and seasoning.\nPlace rice in a bowl and top with chicken.\nFinish with sauce and fresh herbs.', 25, 2, 'assets/img/recipe-salad-card.png', 'ayam', 'mudah', '2026-05-07 16:22:38'),
(6, 2, 'Testing 6', 'testing', 'Add yogurt to the bottom of a glass.\nLayer with fruit and granola.\nRepeat the layers until full.\nChill briefly and serve.', 10, 1, 'assets/img/recipe-salad-card.png', 'dessert', 'mudah', '2026-05-07 16:22:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bahan_resep`
--
ALTER TABLE `bahan_resep`
  ADD PRIMARY KEY (`bahan_resep_id`),
  ADD KEY `bahan_resep_resep_fk` (`resep_id`);

--
-- Indexes for table `cs`
--
ALTER TABLE `cs`
  ADD PRIMARY KEY (`ticket_id`),
  ADD KEY `cs_pelapor_fk` (`pelapor_id`),
  ADD KEY `cs_resep_fk` (`target_resep_id`),
  ADD KEY `cs_pengguna_fk` (`target_pengguna_id`);

--
-- Indexes for table `favorite`
--
ALTER TABLE `favorite`
  ADD PRIMARY KEY (`favorite_id`),
  ADD UNIQUE KEY `favorite_unique` (`pengguna_id`,`resep_id`),
  ADD KEY `favorite_resep_fk` (`resep_id`);

--
-- Indexes for table `following`
--
ALTER TABLE `following`
  ADD PRIMARY KEY (`following_id`),
  ADD UNIQUE KEY `unique_follow` (`follower_id`,`following_id_user`),
  ADD KEY `following_target_fk` (`following_id_user`);

--
-- Indexes for table `kategori_resep`
--
ALTER TABLE `kategori_resep`
  ADD PRIMARY KEY (`kategori_id`),
  ADD UNIQUE KEY `nama_kategori` (`nama_kategori`);

--
-- Indexes for table `komentar`
--
ALTER TABLE `komentar`
  ADD PRIMARY KEY (`komentar_id`),
  ADD KEY `komentar_pengguna_fk` (`pengguna_id`),
  ADD KEY `komentar_resep_fk` (`resep_id`);

--
-- Indexes for table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`like_id`),
  ADD UNIQUE KEY `unique_like` (`pengguna_id`,`resep_id`),
  ADD KEY `likes_resep_fk` (`resep_id`);

--
-- Indexes for table `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`pengguna_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`password_reset_id`),
  ADD UNIQUE KEY `token_hash` (`token_hash`),
  ADD KEY `password_resets_pengguna_fk` (`pengguna_id`),
  ADD KEY `password_resets_email_idx` (`email`);

--
-- Indexes for table `peralatan_resep`
--
ALTER TABLE `peralatan_resep`
  ADD PRIMARY KEY (`peralatan_id`),
  ADD KEY `peralatan_resep_resep_fk` (`resep_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD UNIQUE KEY `ratings_unique` (`pengguna_id`,`resep_id`),
  ADD KEY `ratings_resep_fk` (`resep_id`);

--
-- Indexes for table `recipes`
--
ALTER TABLE `recipes`
  ADD PRIMARY KEY (`resep_id`),
  ADD KEY `recipes_pengguna_fk` (`pengguna_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bahan_resep`
--
ALTER TABLE `bahan_resep`
  MODIFY `bahan_resep_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `cs`
--
ALTER TABLE `cs`
  MODIFY `ticket_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `favorite`
--
ALTER TABLE `favorite`
  MODIFY `favorite_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `following`
--
ALTER TABLE `following`
  MODIFY `following_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kategori_resep`
--
ALTER TABLE `kategori_resep`
  MODIFY `kategori_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `komentar`
--
ALTER TABLE `komentar`
  MODIFY `komentar_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `likes`
--
ALTER TABLE `likes`
  MODIFY `like_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pengguna`
--
ALTER TABLE `pengguna`
  MODIFY `pengguna_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `password_reset_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `peralatan_resep`
--
ALTER TABLE `peralatan_resep`
  MODIFY `peralatan_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `rating_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recipes`
--
ALTER TABLE `recipes`
  MODIFY `resep_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bahan_resep`
--
ALTER TABLE `bahan_resep`
  ADD CONSTRAINT `bahan_resep_resep_fk` FOREIGN KEY (`resep_id`) REFERENCES `recipes` (`resep_id`) ON DELETE CASCADE;

--
-- Constraints for table `cs`
--
ALTER TABLE `cs`
  ADD CONSTRAINT `cs_pelapor_fk` FOREIGN KEY (`pelapor_id`) REFERENCES `pengguna` (`pengguna_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cs_pengguna_fk` FOREIGN KEY (`target_pengguna_id`) REFERENCES `pengguna` (`pengguna_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cs_resep_fk` FOREIGN KEY (`target_resep_id`) REFERENCES `recipes` (`resep_id`) ON DELETE SET NULL;

--
-- Constraints for table `favorite`
--
ALTER TABLE `favorite`
  ADD CONSTRAINT `favorite_pengguna_fk` FOREIGN KEY (`pengguna_id`) REFERENCES `pengguna` (`pengguna_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorite_resep_fk` FOREIGN KEY (`resep_id`) REFERENCES `recipes` (`resep_id`) ON DELETE CASCADE;

--
-- Constraints for table `following`
--
ALTER TABLE `following`
  ADD CONSTRAINT `following_follower_fk` FOREIGN KEY (`follower_id`) REFERENCES `pengguna` (`pengguna_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `following_target_fk` FOREIGN KEY (`following_id_user`) REFERENCES `pengguna` (`pengguna_id`) ON DELETE CASCADE;

--
-- Constraints for table `komentar`
--
ALTER TABLE `komentar`
  ADD CONSTRAINT `komentar_pengguna_fk` FOREIGN KEY (`pengguna_id`) REFERENCES `pengguna` (`pengguna_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `komentar_resep_fk` FOREIGN KEY (`resep_id`) REFERENCES `recipes` (`resep_id`) ON DELETE CASCADE;

--
-- Constraints for table `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `likes_pengguna_fk` FOREIGN KEY (`pengguna_id`) REFERENCES `pengguna` (`pengguna_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `likes_resep_fk` FOREIGN KEY (`resep_id`) REFERENCES `recipes` (`resep_id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_pengguna_fk` FOREIGN KEY (`pengguna_id`) REFERENCES `pengguna` (`pengguna_id`) ON DELETE CASCADE;

--
-- Constraints for table `peralatan_resep`
--
ALTER TABLE `peralatan_resep`
  ADD CONSTRAINT `peralatan_resep_resep_fk` FOREIGN KEY (`resep_id`) REFERENCES `recipes` (`resep_id`) ON DELETE CASCADE;

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_pengguna_fk` FOREIGN KEY (`pengguna_id`) REFERENCES `pengguna` (`pengguna_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_resep_fk` FOREIGN KEY (`resep_id`) REFERENCES `recipes` (`resep_id`) ON DELETE CASCADE;

--
-- Constraints for table `recipes`
--
ALTER TABLE `recipes`
  ADD CONSTRAINT `recipes_pengguna_fk` FOREIGN KEY (`pengguna_id`) REFERENCES `pengguna` (`pengguna_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
