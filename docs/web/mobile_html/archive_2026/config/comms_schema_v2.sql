-- ============================================================================
-- GoSiteMe Veil v2.0 — The Jetsons Upgrade
-- Groups, reactions, threads, voice messages, multi-device, command center
-- Migration from v1.0 — 2026-03-06
-- ============================================================================

-- ── GROUP ROOMS ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS comms_groups (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    group_id        VARCHAR(64) NOT NULL UNIQUE,     -- Random public ID
    name            VARCHAR(200) NOT NULL,
    description     TEXT DEFAULT NULL,
    avatar_url      VARCHAR(500) DEFAULT NULL,
    creator_id      INT NOT NULL,
    group_type      ENUM('private','channel','broadcast') DEFAULT 'private',
    max_members     INT DEFAULT 256,
    invite_link     VARCHAR(64) DEFAULT NULL,         -- Random link for invites
    settings        JSON DEFAULT NULL,                -- {ephemeral_timer, admin_only_post, etc}
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_creator (creator_id),
    KEY idx_invite  (invite_link)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS comms_group_members (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    group_id        VARCHAR(64) NOT NULL,
    client_id       INT NOT NULL,
    role            ENUM('owner','admin','member','readonly') DEFAULT 'member',
    sender_key      TEXT DEFAULT NULL,                -- Sender Key for group encryption
    joined_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    muted_until     TIMESTAMP NULL,
    UNIQUE KEY uk_member (group_id, client_id),
    KEY idx_client  (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Group messages (separate from 1:1 for performance)
CREATE TABLE IF NOT EXISTS comms_group_messages (
    id                  BIGINT AUTO_INCREMENT PRIMARY KEY,
    group_id            VARCHAR(64) NOT NULL,
    sender_id           INT NOT NULL,
    ciphertext          MEDIUMTEXT NOT NULL,          -- Sender Key encrypted
    iv                  VARCHAR(32) NOT NULL,
    sender_key_id       VARCHAR(64) DEFAULT NULL,     -- Which sender key version
    message_type        TINYINT DEFAULT 0,            -- 0=text 1=file 2=voice 3=system 4=alfred
    reply_to            BIGINT DEFAULT NULL,          -- Thread/reply reference
    edited_at           TIMESTAMP NULL,
    expires_at          TIMESTAMP NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_group       (group_id, created_at),
    KEY idx_reply       (reply_to),
    KEY idx_expire      (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── REACTIONS ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS comms_reactions (
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,
    message_id      BIGINT NOT NULL,
    message_source  ENUM('dm','group') NOT NULL DEFAULT 'dm',
    client_id       INT NOT NULL,
    reaction        VARCHAR(32) NOT NULL,             -- Encrypted emoji (or plaintext emoji for groups)
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_reaction (message_id, message_source, client_id, reaction),
    KEY idx_message  (message_id, message_source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── THREADS ────────────────────────────────────────────────────────
-- reply_to field on comms_messages and comms_group_messages handles threading
-- Add reply_to to existing comms_messages if not exists
ALTER TABLE comms_messages ADD COLUMN reply_to BIGINT DEFAULT NULL AFTER message_type;
ALTER TABLE comms_messages ADD COLUMN edited_at TIMESTAMP NULL AFTER reply_to;
ALTER TABLE comms_messages ADD KEY idx_reply (reply_to);

-- ── POST-QUANTUM CRYPTO (Kyber-1024 Hybrid) ─────────────────────
-- PQ public key bundle: ECDH raw (65B) + Kyber-1024 PK (1568B), base64
ALTER TABLE comms_identity_keys ADD COLUMN pq_public MEDIUMTEXT DEFAULT NULL AFTER ecdsa_public;
-- Kyber ciphertext for hybrid PQ key exchange, base64
ALTER TABLE comms_messages ADD COLUMN kyber_ct MEDIUMTEXT DEFAULT NULL AFTER sender_ephemeral;

-- ── VOICE MESSAGES ─────────────────────────────────────────────────
-- voice messages use message_type=2, audio stored in comms_files
-- Metadata: duration, waveform (encrypted in ciphertext)
-- No schema change needed — uses existing infrastructure

-- ── MULTI-DEVICE ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS comms_devices (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    client_id       INT NOT NULL,
    device_id       VARCHAR(64) NOT NULL,             -- Random device identifier
    device_name     VARCHAR(100) DEFAULT NULL,        -- "Chrome on MacOS"
    ecdh_public     TEXT NOT NULL,                    -- Per-device ECDH public key
    ecdsa_public    TEXT NOT NULL,                    -- Per-device signing key
    is_primary      TINYINT(1) DEFAULT 0,
    last_seen       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_device (client_id, device_id),
    KEY idx_client  (client_id, is_primary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── READ RECEIPTS ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS comms_read_receipts (
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,
    message_id      BIGINT NOT NULL,
    message_source  ENUM('dm','group') NOT NULL DEFAULT 'dm',
    client_id       INT NOT NULL,
    read_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_receipt (message_id, message_source, client_id),
    KEY idx_message  (message_id, message_source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── TYPING INDICATORS (transient, cleaned frequently) ──────────────
CREATE TABLE IF NOT EXISTS comms_typing (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    client_id       INT NOT NULL,
    target_type     ENUM('dm','group') NOT NULL DEFAULT 'dm',
    target_id       VARCHAR(64) NOT NULL,             -- contact_id or group_id
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_typing (client_id, target_type, target_id),
    KEY idx_target  (target_type, target_id, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── COMMAND CENTER METRICS (encrypted) ─────────────────────────────
CREATE TABLE IF NOT EXISTS comms_dashboard_cards (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    client_id       INT NOT NULL,
    card_type       VARCHAR(50) NOT NULL,             -- site_health, traffic, security, billing, alfred_log
    position        INT DEFAULT 0,
    enabled         TINYINT(1) DEFAULT 1,
    config          JSON DEFAULT NULL,                -- Card-specific settings
    UNIQUE KEY uk_card (client_id, card_type),
    KEY idx_client  (client_id, enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── NOTIFICATION PREFERENCES ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS comms_notification_prefs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    client_id       INT NOT NULL,
    push_enabled    TINYINT(1) DEFAULT 1,
    push_endpoint   TEXT DEFAULT NULL,                -- Web Push subscription
    push_p256dh     TEXT DEFAULT NULL,
    push_auth       TEXT DEFAULT NULL,
    sound_enabled   TINYINT(1) DEFAULT 1,
    dm_notify       TINYINT(1) DEFAULT 1,
    group_notify    TINYINT(1) DEFAULT 1,
    alfred_notify   TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── CLEANUP PROCEDURE v2 ──────────────────────────────────────────
DROP PROCEDURE IF EXISTS comms_cleanup;
DELIMITER //
CREATE PROCEDURE comms_cleanup()
BEGIN
    DELETE FROM comms_messages WHERE expires_at IS NOT NULL AND expires_at < NOW();
    DELETE FROM comms_group_messages WHERE expires_at IS NOT NULL AND expires_at < NOW();
    DELETE FROM comms_signals WHERE created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE);
    DELETE FROM comms_typing WHERE updated_at < DATE_SUB(NOW(), INTERVAL 10 SECOND);
    SELECT ROW_COUNT() AS cleaned;
END //
DELIMITER ;
