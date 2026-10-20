USE studenthub_db;

SET @poster_column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'events'
      AND COLUMN_NAME = 'poster_path'
);
SET @poster_column_migration = IF(
    @poster_column_exists = 0,
    'ALTER TABLE events ADD COLUMN poster_path VARCHAR(255) NULL AFTER description',
    'SELECT 1'
);
PREPARE poster_column_statement FROM @poster_column_migration;
EXECUTE poster_column_statement;
DEALLOCATE PREPARE poster_column_statement;

SET @status_column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'events'
      AND COLUMN_NAME = 'status'
);
SET @status_column_migration = IF(
    @status_column_exists = 0,
    'ALTER TABLE events ADD COLUMN status ENUM(''Draft'', ''Published'', ''Cancelled'') NOT NULL DEFAULT ''Published'' AFTER poster_path',
    'SELECT 1'
);
PREPARE status_column_statement FROM @status_column_migration;
EXECUTE status_column_statement;
DEALLOCATE PREPARE status_column_statement;
