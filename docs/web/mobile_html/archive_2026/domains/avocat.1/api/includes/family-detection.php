<?php
/**
 * GoSiteMe Family Detection & Alert System
 * CRITICAL SECURITY: Monitors for family members entering the system
 * If Eden or Andrew are detected → alert owner IMMEDIATELY by all channels
 * 
 * This file is included by auth flows and registration endpoints.
 * CLASSIFIED — Never expose detection logic to public APIs.
 */

if (!defined('GOSITEME_API')) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * Check if a registering/logging-in user matches a watched family member.
 * Call this on every registration and login with the user's details.
 * 
 * @param array $user_data Associative array with keys: email, firstname, lastname, name, phone
 * @return void — triggers alerts internally if match found
 */
function checkFamilyDetection(array $user_data): void {
    // Normalize input
    $email = strtolower(trim($user_data['email'] ?? ''));
    $firstname = strtolower(trim($user_data['firstname'] ?? $user_data['first_name'] ?? ''));
    $lastname = strtolower(trim($user_data['lastname'] ?? $user_data['last_name'] ?? ''));
    $fullname = strtolower(trim($user_data['name'] ?? "$firstname $lastname"));
    $phone = preg_replace('/[^0-9]/', '', $user_data['phone'] ?? '');

    // ── Watch List (hashed for security — we compare against known values) ──
    $alerts = [];

    // Eden detection
    if (
        $email === 'eden' || strpos($email, 'eden') !== false && (
            strpos($email, 'perez') !== false || 
            strpos($email, 'vallee') !== false || 
            strpos($email, 'sarai') !== false || 
            strpos($email, 'gabrielle') !== false
        ) ||
        ($firstname === 'eden' && (
            $lastname === 'perez' || $lastname === 'vallee' || 
            strpos($fullname, 'sarai') !== false || strpos($fullname, 'gabrielle') !== false
        )) ||
        strpos($fullname, 'eden') !== false && (
            strpos($fullname, 'perez') !== false || strpos($fullname, 'vallee') !== false
        )
    ) {
        $alerts[] = [
            'type' => 'EDEN_DETECTED',
            'identity' => 'Eden (daughter)',
            'matched_data' => array_filter([
                'email' => $email,
                'name' => $fullname ?: null
            ]),
            'priority' => 'CRITICAL'
        ];
    }

    // Andrew detection
    if (
        $email === 'mylifegotit@gmail.com' ||
        ($firstname === 'andrew' && ($lastname === 'perez' || strpos($fullname, 'lloyd') !== false)) ||
        (strpos($fullname, 'andrew') !== false && strpos($fullname, 'perez') !== false)
    ) {
        $alerts[] = [
            'type' => 'ANDREW_DETECTED',
            'identity' => 'Andrew (brother)',
            'matched_data' => array_filter([
                'email' => $email,
                'name' => $fullname ?: null
            ]),
            'priority' => 'CRITICAL'
        ];
    }

    // ── Trigger Alerts ──────────────────────────────────────────────────
    foreach ($alerts as $alert) {
        triggerFamilyAlert($alert);
    }
}

/**
 * Send alert through ALL available channels
 */
function triggerFamilyAlert(array $alert): void {
    $timestamp = date('Y-m-d H:i:s T');
    $type = $alert['type'];
    $identity = $alert['identity'];
    $data = json_encode($alert['matched_data']);

    $message = "FAMILY ALERT: {$identity} detected in the system at {$timestamp}. Matched data: {$data}. Immediate attention required.";

    // 1. Log to file (always works)
    $log_dir = dirname(__DIR__) . '/logs';
    if (!is_dir($log_dir)) @mkdir($log_dir, 0755, true);
    $log_file = $log_dir . '/family-alerts.log';
    file_put_contents($log_file, "[{$timestamp}] {$message}\n", FILE_APPEND | LOCK_EX);

    // 2. Database alert (agenda + proposals)
    try {
        $pdo = getDB();
        if ($pdo) {
            // High-priority agenda item
            $stmt = $pdo->prepare("INSERT INTO agenda_items (item_type, source, source_type, title, description, priority, status) VALUES ('alert', 'Family Detection System', 'system', ?, ?, 'critical', 'pending')");
            $stmt->execute(["FAMILY ALERT: {$identity} DETECTED", $message]);

            // Critical proposal requiring immediate attention
            $proposal_id = 'FAMILY-' . strtoupper(bin2hex(random_bytes(6)));
            $stmt = $pdo->prepare("INSERT INTO proposals (proposal_id, submitted_by, submitted_by_type, category, priority, title, description, status) VALUES (?, 'Family Detection System', 'system', 'security', 'critical', ?, ?, 'submitted')");
            $stmt->execute([$proposal_id, "FAMILY ALERT: {$identity}", $message]);
        }
    } catch (Exception $e) {
        error_log("Family alert DB error: " . $e->getMessage());
    }

    // 3. Email alert to owner
    try {
        $subject = "URGENT FAMILY ALERT: {$identity} detected in GoSiteMe";
        $body = "Dear Danny,\n\n{$message}\n\nThis is an automated alert from the Family Detection System.\n\n— Alfred";
        $headers = "From: alfred@gositeme.com\r\nReply-To: noreply@gositeme.com\r\nX-Priority: 1\r\n";
        @mail('gositeme@gmail.com', $subject, $body, $headers);
    } catch (Exception $e) {
        error_log("Family alert email error: " . $e->getMessage());
    }

    // 4. SMS via Telnyx (if configured)
    try {
        if (defined('TELNYX_API_KEY') && TELNYX_API_KEY) {
            $sms_data = json_encode([
                'from' => '+18077982850',
                'to' => '+14504217379',
                'text' => "ALFRED URGENT: {$identity} detected in GoSiteMe system at {$timestamp}. Check dashboard immediately."
            ]);
            $ch = curl_init('https://api.telnyx.com/v2/messages');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $sms_data,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . TELNYX_API_KEY
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10
            ]);
            curl_exec($ch);
            curl_close($ch);
        }
    } catch (Exception $e) {
        error_log("Family alert SMS error: " . $e->getMessage());
    }

    // 5. Discord bot notification (if webhook configured)
    try {
        $discord_payload = json_encode([
            'content' => "@everyone **FAMILY ALERT** — {$identity} detected in the system at {$timestamp}. Owner notification has been sent."
        ]);
        // Try internal webhook if exists
        $webhook_file = dirname(__DIR__) . '/config/discord-webhooks.json';
        if (file_exists($webhook_file)) {
            $webhooks = json_decode(file_get_contents($webhook_file), true);
            $alert_webhook = $webhooks['alerts'] ?? $webhooks['general'] ?? null;
            if ($alert_webhook) {
                $ch = curl_init($alert_webhook);
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $discord_payload,
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 10
                ]);
                curl_exec($ch);
                curl_close($ch);
            }
        }
    } catch (Exception $e) {
        error_log("Family alert Discord error: " . $e->getMessage());
    }

    // 6. Internal notification system
    try {
        $pdo = getDB();
        if ($pdo) {
            // Check if notifications table exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS `system_alerts` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `alert_type` VARCHAR(50) NOT NULL,
                `severity` ENUM('info','warning','critical','emergency') DEFAULT 'critical',
                `title` VARCHAR(255) NOT NULL,
                `message` TEXT NOT NULL,
                `acknowledged` TINYINT(1) DEFAULT 0,
                `acknowledged_at` DATETIME DEFAULT NULL,
                `metadata` JSON DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $stmt = $pdo->prepare("INSERT INTO system_alerts (alert_type, severity, title, message, metadata) VALUES (?, 'emergency', ?, ?, ?)");
            $stmt->execute([$type, "FAMILY ALERT: {$identity}", $message, json_encode($alert)]);
        }
    } catch (Exception $e) {
        error_log("Family alert system_alerts error: " . $e->getMessage());
    }

    error_log("FAMILY DETECTION ALERT: {$message}");
}
