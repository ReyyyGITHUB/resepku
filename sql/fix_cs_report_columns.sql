SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `cs` ADD COLUMN `target_tipe` enum(''resep'',''pengguna'') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''resep'' AFTER `pelapor_id`',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'cs' AND COLUMN_NAME = 'target_tipe'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `cs` ADD COLUMN `target_resep_id` bigint UNSIGNED DEFAULT NULL AFTER `target_tipe`',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'cs' AND COLUMN_NAME = 'target_resep_id'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `cs` ADD COLUMN `target_pengguna_id` bigint UNSIGNED DEFAULT NULL AFTER `target_resep_id`',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'cs' AND COLUMN_NAME = 'target_pengguna_id'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `cs` ADD COLUMN `kategori_laporan` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''lainnya'' AFTER `target_pengguna_id`',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'cs' AND COLUMN_NAME = 'kategori_laporan'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `cs` ADD COLUMN `catatan_laporan` text COLLATE utf8mb4_unicode_ci AFTER `kategori_laporan`',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'cs' AND COLUMN_NAME = 'catatan_laporan'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `cs`
SET `kategori_laporan` = 'lainnya'
WHERE `kategori_laporan` IS NULL OR `kategori_laporan` = '';
