<?php
/**
 * GoSiteMe Discord Bot — Voice/Telecom Module
 * ═════════════════════════════════════════════
 * /call    — Make a real phone call from Discord (VAPI/Telnyx)
 * /fax     — Send a fax from Discord (Telnyx)
 * /email   — Send an email from Discord
 *
 * UNIQUE: No other Discord bot can make actual phone calls or send faxes.
 */

function handleCall(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $phone = ''; $greeting = '';
    foreach ($opts as $o) {
        if ($o['name'] === 'phone') $phone = $o['value'];
        if ($o['name'] === 'greeting') $greeting = $o['value'];
    }

    $userId = $data['member']['user']['id'] ?? '0';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';

    if (!$phone) { respondEphemeral("📞 Usage: `/call phone:+15551234567 greeting:Hello, I'm calling about...`"); return; }
    if (!$greeting || strlen($greeting) < 5) { respondEphemeral("Please provide a greeting message (5+ chars)."); return; }
    if (strlen($greeting) > 500) { respondEphemeral("Greeting too long! Max 500 characters."); return; }

    // Sanitize phone
    $phone = preg_replace('/[^+\d]/', '', $phone);
    if (!preg_match('/^\+?1?\d{10,15}$/', $phone)) {
        respondEphemeral("❌ Invalid phone number. Use format: +15551234567");
        return;
    }
    if ($phone[0] !== '+') $phone = '+1' . $phone;

    // Rate limit: 2 calls per user per day
    $pdo = getDiscordDB();
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM discord_economy WHERE discord_id = ? AND reason LIKE 'Phone call%' AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $stmt->execute([$userId]);
        if ($stmt->fetchColumn() >= 2) {
            respondEphemeral("📞 Daily call limit reached (2/day). Resets in 24h.");
            return;
        }
    }

    // Check balance (10 KGD)
    $user = getOrCreateUser($userId, $globalName);
    if (($user['kgd_balance'] ?? 0) < 10) {
        respondEphemeral("📞 Making calls costs 10 KGD. Your balance: {$user['kgd_balance']} KGD. Use `/daily` to earn more!");
        return;
    }

    deferResponse(true);

    $telnyxKey = getenv('TELNYX_API_KEY') ?: '';
    $fromNumber = getenv('TELNYX_FROM_NUMBER') ?: '';
    $connectionId = getenv('TELNYX_CONNECTION_ID') ?: '';

    if (!$telnyxKey || !$fromNumber) {
        followUp($appId, $token, '⚠️ Phone service temporarily unavailable.', [], [], 64);
        return;
    }

    $fromNumber = preg_replace('/[^\d+]/', '', $fromNumber);
    if ($fromNumber[0] !== '+') $fromNumber = '+1' . ltrim($fromNumber, '1');

    $payload = ['to' => $phone, 'from' => $fromNumber];
    if ($connectionId) $payload['connection_id'] = $connectionId;

    $ch = curl_init('https://api.telnyx.com/v2/calls');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $telnyxKey", "Content-Type: application/json"],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $resp = json_decode($result, true);

    if ($httpCode >= 200 && $httpCode < 300) {
        // Deduct KGD
        if ($pdo) {
            $pdo->prepare("UPDATE discord_users SET kgd_balance = kgd_balance - 10, total_spent = total_spent + 10 WHERE discord_id = ?")
                ->execute([$userId]);
            $masked = substr($phone, 0, 4) . '****' . substr($phone, -4);
            $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'spend', 10, ?)")
                ->execute([$userId, "Phone call to $masked"]);
        }

        $callId = $resp['data']['call_control_id'] ?? 'unknown';
        $masked = substr($phone, 0, 4) . '****' . substr($phone, -4);

        followUp($appId, $token, '', [embed(
            "📞 Call Initiated!",
            "**To:** $masked\n**Greeting:** " . truncate($greeting, 200) . "\n**Cost:** 10 KGD\n**Call ID:** `$callId`\n\n*The recipient will receive the call shortly.*",
            0x57F287,
            [],
            ['footer' => ['text' => 'Powered by GoSiteMe Telecom']]
        )], [], 64);

        awardXP($userId, 10, $appId, $token, $data['channel_id'] ?? '');
    } else {
        $error = $resp['errors'][0]['detail'] ?? 'Connection failed';
        followUp($appId, $token, "❌ Call failed: $error", [], [], 64);
    }
}


function handleFax(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $phone = ''; $docUrl = '';
    foreach ($opts as $o) {
        if ($o['name'] === 'number') $phone = $o['value'];
        if ($o['name'] === 'document') $docUrl = $o['value'];
    }

    $userId = $data['member']['user']['id'] ?? '0';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';

    if (!$phone || !$docUrl) { respondEphemeral("📠 Usage: `/fax number:+15551234567 document:https://example.com/file.pdf`"); return; }

    // Validate phone
    $phone = preg_replace('/[^+\d]/', '', $phone);
    if (!preg_match('/^\+?1?\d{10,15}$/', $phone)) {
        respondEphemeral("❌ Invalid fax number format.");
        return;
    }
    if ($phone[0] !== '+') $phone = '+1' . $phone;

    // Validate document URL
    if (!filter_var($docUrl, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//', $docUrl)) {
        respondEphemeral("❌ Document must be a valid HTTP/HTTPS URL (PDF, TIFF, or image).");
        return;
    }

    // Rate limit: 3 faxes per day
    $pdo = getDiscordDB();
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM discord_economy WHERE discord_id = ? AND reason LIKE 'Fax sent%' AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $stmt->execute([$userId]);
        if ($stmt->fetchColumn() >= 3) {
            respondEphemeral("📠 Daily fax limit reached (3/day).");
            return;
        }
    }

    // Check balance (15 KGD)
    $user = getOrCreateUser($userId, $globalName);
    if (($user['kgd_balance'] ?? 0) < 15) {
        respondEphemeral("📠 Sending fax costs 15 KGD. Balance: {$user['kgd_balance']} KGD");
        return;
    }

    deferResponse(true);

    $telnyxKey = getenv('TELNYX_API_KEY') ?: '';
    $fromNumber = getenv('TELNYX_FROM_NUMBER') ?: '';
    $connectionId = getenv('TELNYX_CONNECTION_ID') ?: '';

    if (!$telnyxKey) {
        followUp($appId, $token, '⚠️ Fax service temporarily unavailable.', [], [], 64);
        return;
    }

    $fromNumber = preg_replace('/[^\d+]/', '', $fromNumber);
    if ($fromNumber[0] !== '+') $fromNumber = '+1' . ltrim($fromNumber, '1');

    $payload = ['to' => $phone, 'from' => $fromNumber, 'media_url' => $docUrl];
    if ($connectionId) $payload['connection_id'] = $connectionId;

    $ch = curl_init('https://api.telnyx.com/v2/faxes');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $telnyxKey", "Content-Type: application/json"],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $resp = json_decode($result, true);

    if ($httpCode >= 200 && $httpCode < 300) {
        if ($pdo) {
            $pdo->prepare("UPDATE discord_users SET kgd_balance = kgd_balance - 15, total_spent = total_spent + 15 WHERE discord_id = ?")
                ->execute([$userId]);
            $masked = substr($phone, 0, 4) . '****' . substr($phone, -4);
            $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'spend', 15, ?)")
                ->execute([$userId, "Fax sent to $masked"]);
        }

        $faxId = $resp['data']['id'] ?? 'unknown';
        $masked = substr($phone, 0, 4) . '****' . substr($phone, -4);

        followUp($appId, $token, '', [embed(
            "📠 Fax Sent!",
            "**To:** $masked\n**Document:** [View]($docUrl)\n**Cost:** 15 KGD\n**Fax ID:** `$faxId`\n**Status:** Queued",
            0x57F287,
            [],
            ['footer' => ['text' => 'Powered by GoSiteMe Telecom']]
        )], [], 64);

        awardXP($userId, 10, $appId, $token, $data['channel_id'] ?? '');
    } else {
        $error = $resp['errors'][0]['detail'] ?? 'Fax failed';
        followUp($appId, $token, "❌ Fax failed: $error", [], [], 64);
    }
}


function handleEmail(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $to = ''; $subject = ''; $body = '';
    foreach ($opts as $o) {
        if ($o['name'] === 'to') $to = $o['value'];
        if ($o['name'] === 'subject') $subject = $o['value'];
        if ($o['name'] === 'body') $body = $o['value'];
    }

    $userId = $data['member']['user']['id'] ?? '0';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';

    if (!$to || !$subject || !$body) {
        respondEphemeral("📧 Usage: `/email to:user@example.com subject:Hello body:Your message here`");
        return;
    }

    // Validate email
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        respondEphemeral("❌ Invalid email address.");
        return;
    }

    // Sanitize
    $subject = strip_tags($subject);
    $body = strip_tags($body);
    if (strlen($subject) > 200) { respondEphemeral("Subject too long (max 200 chars)."); return; }
    if (strlen($body) > 2000) { respondEphemeral("Message too long (max 2000 chars)."); return; }

    // Rate limit: 3 emails per day
    $pdo = getDiscordDB();
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM discord_economy WHERE discord_id = ? AND reason LIKE 'Email sent%' AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $stmt->execute([$userId]);
        if ($stmt->fetchColumn() >= 3) {
            respondEphemeral("📧 Daily email limit reached (3/day).");
            return;
        }
    }

    // Check balance (3 KGD)
    $user = getOrCreateUser($userId, $globalName);
    if (($user['kgd_balance'] ?? 0) < 3) {
        respondEphemeral("📧 Sending email costs 3 KGD. Balance: {$user['kgd_balance']} KGD");
        return;
    }

    deferResponse(true);

    $fullBody = "Message from $globalName via GoSiteMe Discord:\n\n"
        . $body . "\n\n"
        . "---\n"
        . "Sent via GoSiteMe Discord Bot\n"
        . "https://gositeme.com";

    $headers = "From: noreply@gositeme.com\r\n"
        . "Reply-To: noreply@gositeme.com\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "X-Mailer: GoSiteMe-Discord-Bot";

    $sent = mail($to, $subject, $fullBody, $headers);

    if ($sent) {
        if ($pdo) {
            $pdo->prepare("UPDATE discord_users SET kgd_balance = kgd_balance - 3, total_spent = total_spent + 3 WHERE discord_id = ?")
                ->execute([$userId]);
            $maskedEmail = substr($to, 0, 3) . '***@' . explode('@', $to)[1];
            $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'spend', 3, ?)")
                ->execute([$userId, "Email sent to $maskedEmail"]);
        }

        $maskedEmail = substr($to, 0, 3) . '***@' . explode('@', $to)[1];
        followUp($appId, $token, '', [embed(
            "📧 Email Sent!",
            "**To:** $maskedEmail\n**Subject:** $subject\n**Cost:** 3 KGD",
            0x57F287,
            [],
            ['footer' => ['text' => 'Powered by GoSiteMe']]
        )], [], 64);

        awardXP($userId, 5, $appId, $token, $data['channel_id'] ?? '');
    } else {
        followUp($appId, $token, "❌ Email delivery failed. Please try again later.", [], [], 64);
    }
}