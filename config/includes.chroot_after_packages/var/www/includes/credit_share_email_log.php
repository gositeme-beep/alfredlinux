<?php
/**
 * Credit Share Email Logging
 *
 * Call this whenever you send a "Share Credits" (or similar) notification email.
 * Logs each send so you can verify delivery and resend if needed (e.g. Gmail spam).
 *
 * Usage (in your Share Credits / admin credits flow):
 *   require_once __DIR__ . '/includes/credit_share_email_log.php';
 *   log_credit_share_email('pascalavs@gmail.com', 33, 'Share Credits from admin');
 *
 * Optional: use get_credit_share_bcc() to BCC an admin address so you always have a copy.
 */

if (!defined('CREDIT_SHARE_LOG_FILE')) {
    define('CREDIT_SHARE_LOG_FILE', dirname(__DIR__) . '/logs/credit_share_emails.log');
}

/**
 * Log a credit-share email send. Call this right after (or before) sending the email.
 *
 * @param string $to_email   Recipient email
 * @param mixed  $amount     Credit amount (e.g. 33)
 * @param string $description Optional description (e.g. 'Share Credits')
 */
function log_credit_share_email($to_email, $amount, $description = 'Share Credits') {
    $logFile = CREDIT_SHARE_LOG_FILE;
    $dir = dirname($logFile);
    if (!is_writable($dir)) {
        return;
    }
    $line = date('Y-m-d H:i:s') . "\t" . $to_email . "\t" . $amount . "\t" . $description . "\n";
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Optional: return an admin BCC address so the sending code can BCC it.
 * That way you always have a copy even if the recipient's provider filters it.
 * Set this to your admin email or leave empty to disable.
 *
 * @return string BCC email or empty string
 */
function get_credit_share_bcc() {
    $bcc = defined('CREDIT_SHARE_BCC_EMAIL') ? CREDIT_SHARE_BCC_EMAIL : '';
    return is_string($bcc) ? trim($bcc) : '';
}
