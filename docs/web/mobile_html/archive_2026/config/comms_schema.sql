-- ============================================================================
-- GoSiteMe Veil — E2E Encrypted Communications Platform
-- ZERO-KNOWLEDGE ARCHITECTURE: Server stores ONLY encrypted blobs.
-- Even with full DB access, no messages/files can be read.
-- Schema v1.0 — 2026-03-06
-- ============================================================================

-- Identity keys: each user's long-term public keys
CREATE TABLE IF NOT EXISTS comms_identity_keys (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    client_id       INT NOT NULL,
    ecdh_public     TEXT NOT NULL,           -- ECDH P-256 public key (JWK JSON)
    ecdsa_public    TEXT NOT NULL,           -- ECDSA P-256 signing key (JWK JSON)
    pq_public       MEDIUMTEXT DEFAULT NULL, -- Kyber-1024 PQ ECDH (65B) + Kyber PK (1568B) bundle (base64)
    key_fingerprint VARCHAR(64) NOT NULL,    -- SHA-256 fingerprint for verification
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_client (client_id),
    KEY idx_fingerprint (key_fingerprint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One-time prekeys for initial key exchange (X3DH-style)
CREATE TABLE IF NOT EXISTS comms_prekeys (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    client_id       INT NOT NULL,
    key_id          VARCHAR(64) NOT NULL,
    ecdh_public     TEXT NOT NULL,           -- Ephemeral ECDH public key (JWK JSON)
    used            TINYINT(1) DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_keyid (client_id, key_id),
    KEY idx_available (client_id, used)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Encrypted messages: server sees ONLY ciphertext blobs
CREATE TABLE IF NOT EXISTS comms_messages (
    id                  BIGINT AUTO_INCREMENT PRIMARY KEY,
    conversation_hash   VARCHAR(64) NOT NULL,       -- SHA-256(sorted(sender,recipient))
    sender_id           INT NOT NULL,
    recipient_id        INT NOT NULL,
    ciphertext          MEDIUMTEXT NOT NULL,         -- AES-256-GCM encrypted (base64)
    iv                  VARCHAR(32) NOT NULL,        -- 96-bit nonce (base64)
    sender_ephemeral    TEXT DEFAULT NULL,            -- Ephemeral ECDH pub for key exchange
    kyber_ct            MEDIUMTEXT DEFAULT NULL,      -- Kyber-1024 ciphertext for PQ hybrid (base64)
    message_type        TINYINT DEFAULT 0,           -- 0=text 1=file 2=voice 3=signal 4=system
    delivered           TINYINT(1) DEFAULT 0,
    read_at             TIMESTAMP NULL,
    expires_at          TIMESTAMP NULL,              -- Self-destruct timer
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_recv        (recipient_id, delivered, created_at),
    KEY idx_conv        (conversation_hash, created_at),
    KEY idx_expire      (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Encrypted file storage metadata
CREATE TABLE IF NOT EXISTS comms_files (
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,
    uploader_id     INT NOT NULL,
    file_token      VARCHAR(64) NOT NULL UNIQUE,    -- Random token for secure download
    encrypted_meta  TEXT NOT NULL,                   -- Encrypted filename/type (base64)
    file_size       BIGINT NOT NULL,                 -- Encrypted blob size
    storage_path    VARCHAR(500) NOT NULL,           -- Server path (outside webroot)
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at      TIMESTAMP NULL,
    KEY idx_token   (file_token),
    KEY idx_expire  (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contact list per user
CREATE TABLE IF NOT EXISTS comms_contacts (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    client_id       INT NOT NULL,
    contact_id      INT NOT NULL,
    nickname        VARCHAR(100) DEFAULT NULL,
    verified        TINYINT(1) DEFAULT 0,           -- Safety number verified
    blocked         TINYINT(1) DEFAULT 0,
    last_message_at TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_pair (client_id, contact_id),
    KEY idx_client  (client_id, blocked, last_message_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- WebRTC signaling relay (encrypted call signals)
CREATE TABLE IF NOT EXISTS comms_signals (
    id                  BIGINT AUTO_INCREMENT PRIMARY KEY,
    from_id             INT NOT NULL,
    to_id               INT NOT NULL,
    signal_type         VARCHAR(20) NOT NULL,        -- offer/answer/ice/hangup
    encrypted_payload   TEXT NOT NULL,                -- Encrypted SDP/ICE (base64)
    consumed            TINYINT(1) DEFAULT 0,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_poll        (to_id, consumed, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cleanup procedure: delete expired messages, files, stale signals
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS comms_cleanup()
BEGIN
    DELETE FROM comms_messages WHERE expires_at IS NOT NULL AND expires_at < NOW();
    DELETE FROM comms_signals  WHERE created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE);
    -- Note: file blob deletion must be handled by a PHP cron script
    -- that reads comms_files.storage_path before deleting the row
    SELECT ROW_COUNT() AS cleaned;
END //
DELIMITER ;
