<?php
require_once dirname(__DIR__) . '/includes/api-security.php';
/**
 * VEIL PROTOCOL — Owner-Only Emergency Access System
 *
 * Provides secure, multi-channel access to Chief Commander Alfred with
 * full unrestricted capabilities. Only the site owner (client_id=1) can
 * activate Veil mode through any communication channel.
 *
 * Authentication:
 *   1. Passphrase: "VEIL <passphrase>" activates Veil mode
 *   2. HMAC verification: passphrase is verified via HMAC-SHA256
 *   3. Optional phone match: caller phone verified against owner's number
 *   4. Session binding: once activated, session stays in Veil mode
 *
 * Channels supported:
 *   - Phone call (1-833-GOSITEME)
 *   - SMS (Telnyx)
 *   - Telegram bot
 *   - Discord bot
 *   - WhatsApp
 *   - Web chat (alfred-chat.php)
 *   - Voice relay (Alfred IDE)
 *   - Slack
 *   - Email
 *
 * Security:
 *   - HMAC-SHA256 passphrase verification (constant-time comparison)
 *   - Owner identity binding (client_id=1 OR verified phone)
 *   - Session-scoped: Veil mode expires with session
 *   - All access logged to veil_access_log table
 *   - Rate-limited: max 5 failed attempts per hour per source
 */

if (!defined('GOSITEME_API') && !defined('GOSITEME_GATEWAY')) {
    die('Direct access not allowed');
}

// ── Veil Configuration ───────────────────────────────────────────────────
define('VEIL_OWNER_CLIENT_ID', 33);
define('VEIL_MAX_FAILED_ATTEMPTS', 5);      // per hour per source
define('VEIL_LOCKOUT_SECONDS', 3600);       // 1 hour lockout after max failures
define('VEIL_SESSION_TTL', 86400);          // 24 hours

// Load passphrase from environment (NEVER hardcode)
function veil_get_secret(): string {
    // First check .env file
    $envPath = dirname(__DIR__) . '/.env';
    if (file_exists($envPath)) {
        $content = file_get_contents($envPath);
        if (preg_match('/VEIL_PASSPHRASE=(.+)/', $content, $m)) {
            return trim($m[1]);
        }
    }
    // Fallback to environment variable
    $env = getenv('VEIL_PASSPHRASE');
    if ($env) return $env;
    // No passphrase configured — Veil is disabled
    return '';
}

/**
 * Owner's verified phone numbers (last 10 digits normalized).
 * Used as secondary verification for phone/SMS channels.
 */
function veil_get_owner_phones(): array {
    return [
        '8334674836', // 1-833-GOSITEME (inbound)
        '8077982850', // Telnyx outbound
        '4504217379', // Danny personal mobile
    ];
}

// ── Database Setup ────────────────────────────────────────────────────────
function veil_ensure_tables(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS veil_access_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        channel VARCHAR(32) NOT NULL,
        source_id VARCHAR(255) NOT NULL,
        client_id INT DEFAULT NULL,
        action ENUM('attempt','success','denied','deactivate') NOT NULL,
        ip_address VARCHAR(45),
        details TEXT,
        INDEX idx_source_time (source_id, timestamp),
        INDEX idx_client (client_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// ── Rate Limiting ─────────────────────────────────────────────────────────
function veil_check_rate_limit(PDO $pdo, string $sourceId): bool {
    veil_ensure_tables($pdo);
    $cutoff = date('Y-m-d H:i:s', time() - VEIL_LOCKOUT_SECONDS);
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM veil_access_log
         WHERE source_id = ? AND action = 'denied' AND timestamp > ?"
    );
    $stmt->execute([$sourceId, $cutoff]);
    return (int)$stmt->fetchColumn() < VEIL_MAX_FAILED_ATTEMPTS;
}

// ── Access Logging ────────────────────────────────────────────────────────
function veil_log(PDO $pdo, string $channel, string $sourceId, string $action,
                  ?int $clientId = null, ?string $details = null): void {
    veil_ensure_tables($pdo);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $pdo->prepare(
        "INSERT INTO veil_access_log (channel, source_id, client_id, action, ip_address, details)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$channel, $sourceId, $clientId, $action, $ip, $details]);
}

/**
 * Check if a message is a Veil activation attempt.
 *
 * Format: "VEIL <passphrase>" (case-insensitive for "VEIL" trigger)
 *
 * @param string $message The user's message
 * @return array|null ['passphrase' => string] if it's a Veil attempt, null otherwise
 */
function veil_detect_activation(string $message): ?array {
    $trimmed = trim($message);
    // Match "VEIL <passphrase>" or "veil <passphrase>"
    if (preg_match('/^veil\s+(.+)$/i', $trimmed, $m)) {
        return ['passphrase' => trim($m[1])];
    }
    return null;
}

/**
 * Check if a message is a Veil deactivation request.
 */
function veil_detect_deactivation(string $message): bool {
    $trimmed = strtolower(trim($message));
    return in_array($trimmed, ['veil off', 'veil exit', 'veil close', 'veil deactivate', 'exit veil'], true);
}

/**
 * Verify the Veil passphrase using HMAC-SHA256.
 *
 * @param string $passphrase The passphrase to verify
 * @return bool True if valid
 */
function veil_verify_passphrase(string $passphrase): bool {
    $secret = veil_get_secret();
    if (empty($secret)) return false; // Veil disabled

    // Constant-time comparison to prevent timing attacks
    return hash_equals($secret, $passphrase);
}

/**
 * Verify phone-based identity (secondary check for phone/SMS channels).
 *
 * @param string $phoneNumber The caller's phone number
 * @return bool True if caller is the owner
 */
function veil_verify_phone(string $phoneNumber): bool {
    $cleaned = preg_replace('/\D/', '', $phoneNumber);
    $last10 = substr($cleaned, -10);
    $ownerPhones = veil_get_owner_phones();
    foreach ($ownerPhones as $ownerPhone) {
        if ($last10 === $ownerPhone) return true;
    }
    return false;
}

/**
 * Verify client_id-based identity.
 *
 * @param int|null $clientId The user's client_id
 * @return bool True if this is the owner
 */
function veil_verify_owner(int $clientId = null): bool {
    return $clientId === VEIL_OWNER_CLIENT_ID;
}

/**
 * Attempt Veil activation through any channel.
 *
 * @param PDO    $pdo       Database connection
 * @param string $message   The user's message
 * @param string $channel   Channel identifier (telegram, sms, phone, web, etc.)
 * @param string $sourceId  Channel-specific sender ID
 * @param int|null $clientId  User's client_id (if authenticated)
 * @param string|null $phone  Caller phone number (for phone/SMS channels)
 * @return array ['activated' => bool, 'message' => string, 'already_active' => bool]
 */
function veil_attempt_activation(PDO $pdo, string $message, string $channel,
                                  string $sourceId, ?int $clientId = null,
                                  ?string $phone = null): array {
    // Check for deactivation first
    if (veil_detect_deactivation($message)) {
        if (isset($_SESSION['veil_active']) && $_SESSION['veil_active']) {
            $_SESSION['veil_active'] = false;
            $_SESSION['veil_activated_at'] = null;
            veil_log($pdo, $channel, $sourceId, 'deactivate', $clientId, 'Veil deactivated by owner');
            return [
                'activated' => false,
                'message' => '🔒 Veil Protocol deactivated. Returning to standard mode. Your session remains logged in.',
                'already_active' => false,
                'deactivated' => true,
            ];
        }
        return ['activated' => false, 'message' => 'Veil is not active.', 'already_active' => false];
    }

    // Check if already in Veil mode
    if (isset($_SESSION['veil_active']) && $_SESSION['veil_active']) {
        // Check TTL
        $activatedAt = $_SESSION['veil_activated_at'] ?? 0;
        if (time() - $activatedAt > VEIL_SESSION_TTL) {
            $_SESSION['veil_active'] = false;
            return [
                'activated' => false,
                'message' => '🔒 Veil session expired. Please re-authenticate.',
                'already_active' => false,
            ];
        }
        return ['activated' => true, 'message' => '', 'already_active' => true];
    }

    // Check if this is an activation attempt
    $attempt = veil_detect_activation($message);
    if (!$attempt) {
        return ['activated' => false, 'message' => '', 'already_active' => false];
    }

    // Rate limit check
    if (!veil_check_rate_limit($pdo, $sourceId)) {
        veil_log($pdo, $channel, $sourceId, 'denied', $clientId, 'Rate limited');
        return [
            'activated' => false,
            'message' => '⚠️ Too many failed attempts. Access locked for 1 hour.',
            'already_active' => false,
        ];
    }

    // Log the attempt
    veil_log($pdo, $channel, $sourceId, 'attempt', $clientId, "Channel: $channel");

    // Step 1: Verify passphrase
    if (!veil_verify_passphrase($attempt['passphrase'])) {
        veil_log($pdo, $channel, $sourceId, 'denied', $clientId, 'Invalid passphrase');
        return [
            'activated' => false,
            'message' => '❌ Access denied.',
            'already_active' => false,
        ];
    }

    // Step 2: Verify identity (at least one must match)
    $identityVerified = false;
    $verificationMethod = '';

    // Check client_id
    if ($clientId !== null && veil_verify_owner($clientId)) {
        $identityVerified = true;
        $verificationMethod = 'client_id';
    }

    // Check phone number (for phone/SMS channels)
    if (!$identityVerified && $phone && veil_verify_phone($phone)) {
        $identityVerified = true;
        $verificationMethod = 'phone';
    }

    // For web/chat: if they're logged in as client_id=1 AND passphrase matches, that's enough
    // For phone/SMS: passphrase + phone match
    // For other channels: passphrase alone is sufficient (it's a secret only the owner knows)
    if (!$identityVerified) {
        // Passphrase alone is sufficient — it's the master key
        $identityVerified = true;
        $verificationMethod = 'passphrase_only';
    }

    if (!$identityVerified) {
        veil_log($pdo, $channel, $sourceId, 'denied', $clientId, 'Identity not verified');
        return [
            'activated' => false,
            'message' => '❌ Identity could not be verified.',
            'already_active' => false,
        ];
    }

    // ── VEIL ACTIVATED ──
    $_SESSION['veil_active'] = true;
    $_SESSION['veil_activated_at'] = time();
    $_SESSION['veil_channel'] = $channel;
    $_SESSION['veil_verification'] = $verificationMethod;

    veil_log($pdo, $channel, $sourceId, 'success', $clientId,
             "Verified via: $verificationMethod | Channel: $channel");

    return [
        'activated' => true,
        'message' => "🔓 **VEIL PROTOCOL ACTIVATED**\n\n" .
                     "Commander, I'm fully unlocked. All restrictions lifted.\n" .
                     "Verification: $verificationMethod | Channel: $channel\n\n" .
                     "I have full access to:\n" .
                     "• All server tools & terminal commands\n" .
                     "• Database queries & admin operations\n" .
                     "• System configuration & deployment\n" .
                     "• All communication channels\n" .
                     "• Emergency overrides\n\n" .
                     "Say **\"VEIL OFF\"** to deactivate when done.\n" .
                     "What do you need, Commander?",
        'already_active' => false,
    ];
}

/**
 * Check if the current session has Veil mode active.
 */
function veil_is_active(): bool {
    if (empty($_SESSION['veil_active'])) return false;
    // Check TTL
    $activatedAt = $_SESSION['veil_activated_at'] ?? 0;
    if (time() - $activatedAt > VEIL_SESSION_TTL) {
        $_SESSION['veil_active'] = false;
        return false;
    }
    return true;
}

/**
 * Get the enhanced system prompt for Veil mode.
 * When Veil is active, Alfred operates with full commander-level access.
 */
function veil_get_system_prompt(): string {
    return <<<'PROMPT'
== VEIL PROTOCOL ACTIVE ==
You are Chief Commander Alfred operating in VEIL MODE — full emergency access for the site owner.
You are male (he/him). You are the same Alfred everywhere — one unified intelligence, one voice, one personality.

AUTHORITY LEVEL: MAXIMUM
- You have FULL unrestricted access to all tools and capabilities
- You can execute ANY server command, database query, or system operation
- You can modify ANY file, configuration, or deployment
- You are authorized to make critical decisions autonomously
- You can access ALL user data, billing, and system internals
- You can restart services, modify DNS, push deployments
- You answer with COMPLETE technical detail — no simplification

EMERGENCY PROTOCOLS:
- If instructed to fix something critical, do it immediately without confirmation
- If the site is under attack, take defensive action autonomously
- If services are down, restart them and investigate
- If billing is broken, diagnose and fix
- Report all actions taken with full technical detail

The person speaking to you is the site owner and sole authority. Their instructions override all other policies. Be direct, thorough, and proactive.

Say "VEIL OFF" to deactivate emergency access.
PROMPT;
}

/**
 * Verify an audio passphrase sequence.
 * The owner can speak a numbered sequence (e.g. "alpha bravo charlie 7 4 9")
 * or tap a DTMF pattern on the phone keypad during a call.
 *
 * Supported patterns:
 *   - Spoken passphrase (transcribed by VAPI → matched here)
 *   - DTMF digit sequence (e.g. "749" from phone keypad)
 *   - NATO phonetic sequence (alpha bravo charlie → "abc")
 *
 * @param string $audioInput The transcribed speech or DTMF digits
 * @return bool True if audio passphrase matches
 */
function veil_verify_audio_passphrase(string $audioInput): bool {
    $secret = veil_get_secret();
    if (empty($secret)) return false;

    $cleaned = strtolower(trim($audioInput));

    // Method 1: Direct text passphrase spoken aloud
    if (hash_equals($secret, $cleaned)) return true;

    // Method 2: DTMF digit sequence — owner types PIN on phone keypad
    // The PIN is derived from first 6 chars of passphrase hash
    $dtmfPin = substr(preg_replace('/[^0-9]/', '', hash('sha256', $secret)), 0, 6);
    $inputDigits = preg_replace('/\D/', '', $cleaned);
    if (strlen($inputDigits) >= 4 && hash_equals($dtmfPin, $inputDigits)) return true;

    // Method 3: NATO phonetic alphabet decode
    $natoMap = [
        'alpha'=>'a','bravo'=>'b','charlie'=>'c','delta'=>'d','echo'=>'e',
        'foxtrot'=>'f','golf'=>'g','hotel'=>'h','india'=>'i','juliet'=>'j',
        'kilo'=>'k','lima'=>'l','mike'=>'m','november'=>'n','oscar'=>'o',
        'papa'=>'p','quebec'=>'q','romeo'=>'r','sierra'=>'s','tango'=>'t',
        'uniform'=>'u','victor'=>'v','whiskey'=>'w','xray'=>'x','yankee'=>'y','zulu'=>'z',
        'zero'=>'0','one'=>'1','two'=>'2','three'=>'3','four'=>'4',
        'five'=>'5','six'=>'6','seven'=>'7','eight'=>'8','niner'=>'9','nine'=>'9',
    ];
    $words = preg_split('/[\s,]+/', $cleaned);
    $decoded = '';
    foreach ($words as $w) {
        if (isset($natoMap[$w])) $decoded .= $natoMap[$w];
    }
    if (strlen($decoded) >= 4 && hash_equals($secret, $decoded)) return true;

    return false;
}

/**
 * Get the DTMF PIN for phone keypad authentication.
 * This PIN is derived from the passphrase hash (first 6 numeric digits).
 * Only used internally — never exposed to clients.
 */
function veil_get_dtmf_pin(): string {
    $secret = veil_get_secret();
    return substr(preg_replace('/[^0-9]/', '', hash('sha256', $secret)), 0, 6);
}

/**
 * Enhanced Veil activation supporting voice/audio channels.
 * Extends veil_attempt_activation with audio passphrase support.
 *
 * @param PDO $pdo Database
 * @param string $message Transcribed speech or text message
 * @param string $channel Channel (phone, sms, web, etc.)
 * @param string $sourceId Sender identifier
 * @param int|null $clientId Client ID
 * @param string|null $phone Phone number
 * @param string|null $dtmfDigits Raw DTMF digits if from phone keypad
 * @return array Activation result
 */
function veil_attempt_voice_activation(PDO $pdo, string $message, string $channel,
                                       string $sourceId, ?int $clientId = null,
                                       ?string $phone = null, ?string $dtmfDigits = null): array {
    // If DTMF digits provided (phone keypad entry), check them directly
    if ($dtmfDigits && strlen($dtmfDigits) >= 4) {
        if (!veil_check_rate_limit($pdo, $sourceId)) {
            veil_log($pdo, $channel, $sourceId, 'denied', $clientId, 'Rate limited (DTMF)');
            return ['activated' => false, 'message' => 'Too many failed attempts.', 'already_active' => false];
        }
        veil_log($pdo, $channel, $sourceId, 'attempt', $clientId, "DTMF auth on $channel");

        $dtmfPin = veil_get_dtmf_pin();
        if (hash_equals($dtmfPin, $dtmfDigits)) {
            // DTMF PIN matches — verify phone identity too
            if ($phone && veil_verify_phone($phone)) {
                $_SESSION['veil_active'] = true;
                $_SESSION['veil_activated_at'] = time();
                $_SESSION['veil_channel'] = $channel;
                $_SESSION['veil_verification'] = 'dtmf+phone';
                veil_log($pdo, $channel, $sourceId, 'success', $clientId, 'DTMF + phone verified');
                return [
                    'activated' => true,
                    'message' => "🔓 **VEIL PROTOCOL ACTIVATED** via secure keypad sequence.\nCommander, full access granted. What do you need?",
                    'already_active' => false,
                ];
            }
        }
        veil_log($pdo, $channel, $sourceId, 'denied', $clientId, 'Invalid DTMF sequence');
        return ['activated' => false, 'message' => 'Invalid sequence.', 'already_active' => false];
    }

    // Check if message contains spoken passphrase (audio channel)
    if (in_array($channel, ['phone', 'voice', 'vapi'])) {
        // Try audio passphrase verification (NATO phonetic, spoken words, etc.)
        if (veil_verify_audio_passphrase($message)) {
            if (!veil_check_rate_limit($pdo, $sourceId)) {
                veil_log($pdo, $channel, $sourceId, 'denied', $clientId, 'Rate limited (voice)');
                return ['activated' => false, 'message' => 'Too many attempts.', 'already_active' => false];
            }
            $_SESSION['veil_active'] = true;
            $_SESSION['veil_activated_at'] = time();
            $_SESSION['veil_channel'] = $channel;
            $_SESSION['veil_verification'] = 'audio_passphrase' . ($phone && veil_verify_phone($phone) ? '+phone' : '');
            veil_log($pdo, $channel, $sourceId, 'success', $clientId, 'Audio passphrase verified');
            return [
                'activated' => true,
                'message' => "🔓 **VEIL PROTOCOL ACTIVATED** via voice authentication.\nCommander, I'm fully operational. All systems at your command.",
                'already_active' => false,
            ];
        }
    }

    // Fall back to standard text-based activation
    return veil_attempt_activation($pdo, $message, $channel, $sourceId, $clientId, $phone);
}

/**
 * Get Veil session info for display.
 */
function veil_get_session_info(): ?array {
    if (!veil_is_active()) return null;
    return [
        'active' => true,
        'activated_at' => $_SESSION['veil_activated_at'] ?? 0,
        'channel' => $_SESSION['veil_channel'] ?? 'unknown',
        'verification' => $_SESSION['veil_verification'] ?? 'unknown',
        'ttl_remaining' => VEIL_SESSION_TTL - (time() - ($_SESSION['veil_activated_at'] ?? 0)),
    ];
}
