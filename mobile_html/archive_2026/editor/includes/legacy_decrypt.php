<?php
/**
 * Decrypt legacy encrypted passwords (e.g. services.password).
 * Uses encryption hash from legacy billing system.
 * Only use server-side; never expose decrypted passwords to the client.
 */

if (!function_exists('legacy_decrypt_password')) {

/**
 * Get legacy encryption hash.
 */
function legacy_get_encryption_hash() {
    return 'GjauuoqVEfumGtTfLY6dYFLspDgRRJIqWPmgPQW2ebxys3pMznuTKKFg6m1riMmu';
}

/**
 * Decrypt a legacy encrypted string (e.g. hosting account password).
 *
 * @param string $encrypted Base64-encoded encrypted string from services.password
 * @return string Decrypted password or empty string on failure
 */
function legacy_decrypt_password($encrypted) {
    $encrypted = trim((string) $encrypted);
    if ($encrypted === '') {
        return '';
    }
    $hash = legacy_get_encryption_hash();
    if ($hash === '') {
        return '';
    }
    $key = md5(md5($hash)) . md5($hash);
    $keyLen = strlen($key);
    $decoded = base64_decode($encrypted, true);
    if ($decoded === false || $decoded === '') {
        return '';
    }
    // Try legacy style: IV at start (length = key length), then XOR with key
    if (strlen($decoded) > $keyLen) {
        $data = substr($decoded, $keyLen);
        $out = '';
        for ($i = 0; $i < strlen($data); $i++) {
            $out .= chr(ord($data[$i]) ^ ord($key[$i % $keyLen]));
        }
        if (preg_match('/^[\x20-\x7E]+$/', $out)) {
            return $out;
        }
    }
    // Fallback: XOR whole decoded string with key (some legacy versions)
    $out = '';
    for ($i = 0; $i < strlen($decoded); $i++) {
        $out .= chr(ord($decoded[$i]) ^ ord($key[$i % $keyLen]));
    }
    return preg_match('/^[\x20-\x7E]+$/', $out) ? $out : '';
}
}
