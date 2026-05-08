CREATE TABLE IF NOT EXISTS `password_resets` (
  `password_reset_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `pengguna_id` bigint UNSIGNED NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `dibuat_pada` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`password_reset_id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `password_resets_pengguna_fk` (`pengguna_id`),
  KEY `password_resets_email_idx` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_pengguna_fk`
  FOREIGN KEY (`pengguna_id`) REFERENCES `pengguna` (`pengguna_id`)
  ON DELETE CASCADE;
