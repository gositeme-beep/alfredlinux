<?php
/**
 * Alfred IDE — Database Migration
 * Creates the alfred_ide_users table for multi-user auth.
 * Run once: php alfred-ide-migrate.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('CLI only');
}

require_once __DIR__ . '/../includes/db-config.inc.php';

$db = getSharedDB();

$db->exec("
    CREATE TABLE IF NOT EXISTS alfred_ide_users (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        client_id       INT UNSIGNED NULL COMMENT 'FK to clients table if linked',
        google_email    VARCHAR(255) NOT NULL,
        google_name     VARCHAR(255) DEFAULT '',
        google_avatar   VARCHAR(512) DEFAULT '',
        pin_hash        VARCHAR(255) NULL COMMENT 'Argon2id hash of PIN',
        pin_set_at      DATETIME NULL,
        failed_attempts INT UNSIGNED DEFAULT 0,
        lockout_until   DATETIME NULL,
        frozen_until    DATETIME NULL,
        session_token   VARCHAR(255) NULL COMMENT 'Current active session token hash',
        token_expires   DATETIME NULL,
        last_login      DATETIME NULL,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_google_email (google_email),
        KEY idx_client_id (client_id),
        KEY idx_session_token (session_token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

echo "Table alfred_ide_users created (or already exists).\n";

$count = $db->query("SELECT COUNT(*) FROM alfred_ide_users")->fetchColumn();
echo "Current rows: $count\n";

echo "Migration complete.\n";
