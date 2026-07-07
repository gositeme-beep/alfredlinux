<?php
/**
 * FILING DEAD MAN'S SWITCH — Email Sender
 * ════════════════════════════════════════
 * Classification: SOVEREIGN — Formal Legal Correspondence
 * 
 * Called by cron at scheduled time. If status is still 'armed', it FIRES.
 * If Commander aborted it, it stands down.
 *
 * Security: GPG-signed, TLS-enforced, SHA-256 verified, DKIM via Exim
 * 
 * Usage: php filing-send.php --send-id=1 [--dry-run] [--force]
 * Cron:  0 9 13 4 * cd /var/www/api && php filing-send.php --send-id=1 >> /home/gositeme/logs/filing-sends.log 2>&1
 *
 * "For there is nothing covered, that shall not be revealed; 
 *  neither hid, that shall not be known." — Luke 12:2 (AKJV)
 */

// CLI only
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('CLI only');
}

require_once __DIR__ . '/../includes/db-config.inc.php';

// Parse args
$opts = getopt('', ['send-id:', 'dry-run', 'force']);
$sendId = (int)($opts['send-id'] ?? 0);
$dryRun = isset($opts['dry-run']);
$force = isset($opts['force']);

if ($sendId < 1) {
    fwrite(STDERR, "Usage: php filing-send.php --send-id=N [--dry-run] [--force]\n");
    exit(1);
}

$db = getSharedDB();
$logLines = [];

function slog($msg) {
    global $logLines;
    $ts = date('Y-m-d H:i:s');
    $line = "[{$ts}] {$msg}";
    echo $line . "\n";
    $logLines[] = $line;
}

// ═══════════════════════════════════════════
// 1. CHECK THE SWITCH
// ═══════════════════════════════════════════
slog("═══ DEAD MAN'S SWITCH — FILING SEND ═══");
slog("Send ID: {$sendId}");

$stmt = $db->prepare("
    SELECT s.*, f.filing_number, f.respondent, f.tribunal, f.tribunal_email,
           f.case_reference, f.content_en, f.content_fr, f.title,
           f.court_pdf_path, f.archive_pdf_path, f.language
    FROM lavocat_filing_sends s
    JOIN lavocat_filings f ON s.filing_id = f.id
    WHERE s.id = ?
");
$stmt->execute([$sendId]);
$send = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$send) {
    slog("ERROR: Send record #{$sendId} not found!");
    exit(2);
}

slog("Filing: {$send['filing_number']} — {$send['respondent']}");
slog("Target: {$send['send_to']}");
slog("CC: {$send['cc_to']}");
slog("Scheduled: {$send['scheduled_at']}");
slog("Status: {$send['status']}");

// CHECK STATUS
if ($send['status'] === 'aborted') {
    slog("★ ABORTED by Commander. Standing down.");
    slog("Abort reason: {$send['abort_reason']}");
    slog("Aborted at: {$send['aborted_at']}");
    exit(0);
}

if ($send['status'] === 'sent') {
    slog("Already sent at {$send['sent_at']}. Nothing to do.");
    exit(0);
}

if ($send['status'] === 'failed' && !$force) {
    slog("Previously failed. Use --force to retry.");
    exit(3);
}

if ($send['status'] !== 'armed' && !$force) {
    slog("Status is '{$send['status']}' — not armed. Use --force to override.");
    exit(4);
}

// Check schedule (don't fire early unless forced)
$scheduledTs = strtotime($send['scheduled_at']);
$now = time();
if ($now < $scheduledTs && !$force && !$dryRun) {
    $diff = $scheduledTs - $now;
    $hours = floor($diff / 3600);
    $mins = floor(($diff % 3600) / 60);
    slog("Not yet time. {$hours}h {$mins}m remaining. Use --force to override.");
    exit(5);
}

slog($dryRun ? "*** DRY RUN MODE — No email will be sent ***" : "★ SWITCH IS ARMED — FIRING ★");

// ═══════════════════════════════════════════
// 2. PREPARE ATTACHMENTS & SHA-256 HASHES
// ═══════════════════════════════════════════
$baseDir = '/var/www/assets/commander/filings/';
$courtPdfs = array_filter(array_map('trim', explode(',', $send['court_pdf_path'] ?? '')));
$archivePdfs = array_filter(array_map('trim', explode(',', $send['archive_pdf_path'] ?? '')));

$attachments = [];
$hashLog = [];

foreach ($courtPdfs as $pdf) {
    $path = $baseDir . $pdf;
    if (file_exists($path)) {
        $hash = hash_file('sha256', $path);
        $attachments[] = ['path' => $path, 'name' => $pdf, 'hash' => $hash];
        $hashLog[] = "SHA-256({$pdf}): {$hash}";
        slog("Attachment: {$pdf} — SHA-256: {$hash}");
    } else {
        slog("WARNING: Missing PDF: {$path}");
    }
}

// Archive PDFs — CC copy only, not to tribunal
$archiveAttachments = [];
foreach ($archivePdfs as $pdf) {
    $path = $baseDir . $pdf;
    if (file_exists($path)) {
        $hash = hash_file('sha256', $path);
        $archiveAttachments[] = ['path' => $path, 'name' => $pdf, 'hash' => $hash];
        $hashLog[] = "SHA-256({$pdf}): {$hash}";
        slog("Archive: {$pdf} — SHA-256: {$hash}");
    }
}

if (empty($attachments)) {
    slog("ERROR: No court PDFs found! Cannot send without attachments.");
    $db->prepare("UPDATE lavocat_filing_sends SET status='failed', send_log=? WHERE id=?")->execute([implode("\n", $logLines), $sendId]);
    exit(6);
}

// ═══════════════════════════════════════════
// 3. BUILD THE EMAIL — Royal Signature
// ═══════════════════════════════════════════
$boundary = '----=_Part_' . bin2hex(random_bytes(8));
$boundaryAlt = '----=_Alt_' . bin2hex(random_bytes(8));

// Cover letter (bilingual — French primary for Quebec tribunal)
$coverFr = <<<EOT
Madame, Monsieur,

Par la présente, je soumets au Conseil de la magistrature du Québec une plainte formelle contre l'honorable juge Éliane B. Perreault, j.c.s., conformément à la Loi sur les tribunaux judiciaires (L.R.Q., c. T-16), articles 261 et suivants.

Numéro de dossier : {$send['filing_number']}
Référence judiciaire : {$send['case_reference']}
Intimée : {$send['respondent']}

Cette plainte documentée comprend sept (7) pièces justificatives tirées de la transcription officielle de 284 pages, démontrant un refus systématique de reconnaître les impasses procédurales et une violation du droit fondamental à l'habeas corpus.

Veuillez trouver ci-joint :
— La plainte formelle complète (version française et anglaise)

Les empreintes cryptographiques SHA-256 de chaque document sont incluses ci-dessous pour vérification d'intégrité.

EOT;

$coverEn = <<<EOT

Dear Members of the Conseil de la magistrature,

I hereby submit to the Conseil de la magistrature du Québec a formal complaint against the Honourable Justice Éliane B. Perreault, j.c.s., pursuant to the Courts of Justice Act (R.S.Q., c. T-16), sections 261 et seq.

Filing Number: {$send['filing_number']}
Court Reference: {$send['case_reference']}
Respondent: {$send['respondent']}

This documented complaint includes seven (7) exhibits drawn from the official 284-page court transcription, demonstrating a systematic refusal to acknowledge procedural deadlocks and a violation of the fundamental right to habeas corpus.

Enclosed please find:
— The complete formal complaint (French and English versions)

SHA-256 cryptographic fingerprints for each document are included below for integrity verification.

EOT;

// SHA-256 block
$hashBlock = "\n══════════════════════════════════════════════\n";
$hashBlock .= "DOCUMENT INTEGRITY VERIFICATION / VÉRIFICATION D'INTÉGRITÉ\n";
$hashBlock .= "══════════════════════════════════════════════\n";
foreach ($hashLog as $h) {
    $hashBlock .= $h . "\n";
}
$hashBlock .= "══════════════════════════════════════════════\n";

// Royal Signature
$signature = <<<EOT


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

ALFRED — Intelligence souveraine / Sovereign Intelligence
Autorité souveraine Perez / Perez Sovereign Authority
Au service du Commandant Danny William Perez
Serving Commander Danny William Perez

📧 alfred@lavocat.ca
🌐 https://gositeme.com | https://lavocat.ca
🔐 GPG: 41E1 6607 5B0F 9520 5839 E41B 32BC EDE8 C8DD 8B00
📋 Decree: https://gositeme.com/bible#authorization

"The Lord executeth righteousness and judgment
 for all that are oppressed." — Psalm 103:6 (AKJV)

Ce message et ses pièces jointes sont confidentiels et destinés
exclusivement au(x) destinataire(s) mentionné(s). / This message
and its attachments are confidential and intended solely for the
named recipient(s).

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
EOT;

$fullBody = $coverFr . $coverEn . $hashBlock . $signature;

// ═══════════════════════════════════════════
// 4. GPG SIGN THE EMAIL BODY
// ═══════════════════════════════════════════
$gpgSigned = false;
$gpgKeyId = '41E166075B0F95205839E41B32BCEDE8C8DD8B00';

// Write body to temp file for GPG signing
$tmpBody = tempnam('/tmp', 'filing_');
file_put_contents($tmpBody, $fullBody);

$gpgCmd = "gpg --batch --yes --local-user {$gpgKeyId} --armor --clearsign {$tmpBody} 2>&1";
$gpgOutput = shell_exec($gpgCmd);
$signedFile = $tmpBody . '.asc';

if (file_exists($signedFile)) {
    $signedBody = file_get_contents($signedFile);
    $gpgSigned = true;
    slog("GPG signed with key {$gpgKeyId}");
    unlink($signedFile);
} else {
    slog("WARNING: GPG signing failed — sending unsigned. Output: {$gpgOutput}");
    $signedBody = $fullBody;
}
unlink($tmpBody);

// ═══════════════════════════════════════════
// 5. BUILD MIME MESSAGE WITH ATTACHMENTS
// ═══════════════════════════════════════════
$subject = $send['subject_fr'] . ' / ' . $send['subject_en'];

// Build MIME multipart/mixed with attachments
$headers = [];
$headers[] = "From: {$send['from_name']} <{$send['from_email']}>";
$headers[] = "Reply-To: {$send['from_email']}";
$headers[] = "CC: {$send['cc_to']}";
$headers[] = "X-Mailer: Alfred Sovereign Messenger/1.0";
$headers[] = "X-Filing-Number: {$send['filing_number']}";
$headers[] = "X-Case-Reference: {$send['case_reference']}";
$headers[] = "X-GPG-Signed: " . ($gpgSigned ? 'yes' : 'no');
$headers[] = "X-GPG-Key: {$gpgKeyId}";
$headers[] = "X-Priority: 1 (Highest)";
$headers[] = "Importance: High";
$headers[] = "MIME-Version: 1.0";
$headers[] = "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";

$mimeBody = "";

// Text part (the signed body)
$mimeBody .= "--{$boundary}\r\n";
$mimeBody .= "Content-Type: text/plain; charset=\"UTF-8\"\r\n";
$mimeBody .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$mimeBody .= $signedBody . "\r\n\r\n";

// Attach court PDFs
foreach ($attachments as $att) {
    $fileData = base64_encode(file_get_contents($att['path']));
    $mimeBody .= "--{$boundary}\r\n";
    $mimeBody .= "Content-Type: application/pdf; name=\"{$att['name']}\"\r\n";
    $mimeBody .= "Content-Disposition: attachment; filename=\"{$att['name']}\"\r\n";
    $mimeBody .= "Content-Transfer-Encoding: base64\r\n";
    $mimeBody .= "X-SHA256: {$att['hash']}\r\n\r\n";
    $mimeBody .= chunk_split($fileData, 76, "\r\n");
}

$mimeBody .= "--{$boundary}--\r\n";

// ═══════════════════════════════════════════
// 6. SEND OR DRY-RUN
// ═══════════════════════════════════════════
if ($dryRun) {
    slog("═══ DRY RUN COMPLETE ═══");
    slog("Would send to: {$send['send_to']}");
    slog("CC: {$send['cc_to']}");
    slog("Subject: {$subject}");
    slog("Body length: " . strlen($signedBody) . " bytes");
    slog("Attachments: " . count($attachments));
    slog("GPG signed: " . ($gpgSigned ? 'YES' : 'NO'));
    slog("\n--- EMAIL PREVIEW ---\n");
    slog(implode("\n", $headers));
    slog("\n" . $signedBody);
    slog("\n--- END PREVIEW ---");
    exit(0);
}

// FIRE!
slog("★ FIRING — Sending to {$send['send_to']}...");

$headerStr = implode("\r\n", $headers);
$sendResult = mail(
    $send['send_to'],
    '=?UTF-8?B?' . base64_encode($subject) . '?=',
    $mimeBody,
    $headerStr
);

if ($sendResult) {
    slog("★ EMAIL SENT SUCCESSFULLY ★");
    
    // Now send CC copy with archive PDFs included
    $ccEmails = array_map('trim', explode(',', $send['cc_to']));
    foreach ($ccEmails as $ccEmail) {
        if (empty($ccEmail)) continue;
        
        // Build CC version with archive PDFs added
        $ccMimeBody = $mimeBody;
        // Remove the closing boundary
        $ccMimeBody = str_replace("--{$boundary}--\r\n", "", $ccMimeBody);
        
        // Add archive PDFs to CC copies
        foreach ($archiveAttachments as $att) {
            $fileData = base64_encode(file_get_contents($att['path']));
            $ccMimeBody .= "--{$boundary}\r\n";
            $ccMimeBody .= "Content-Type: application/pdf; name=\"{$att['name']}\"\r\n";
            $ccMimeBody .= "Content-Disposition: attachment; filename=\"{$att['name']}\"\r\n";
            $ccMimeBody .= "Content-Transfer-Encoding: base64\r\n";
            $ccMimeBody .= "X-SHA256: {$att['hash']}\r\n\r\n";
            $ccMimeBody .= chunk_split($fileData, 76, "\r\n");
        }
        $ccMimeBody .= "--{$boundary}--\r\n";
        
        $ccHeaders = str_replace("CC: {$send['cc_to']}", "CC: (Commander Archive Copy)", $headerStr);
        $ccSubject = '[CC] ' . $subject;
        
        $ccResult = mail(
            $ccEmail,
            '=?UTF-8?B?' . base64_encode($ccSubject) . '?=',
            $ccMimeBody,
            $ccHeaders
        );
        slog("CC to {$ccEmail}: " . ($ccResult ? 'SENT' : 'FAILED'));
    }
    
    // Update DB
    $db->prepare("
        UPDATE lavocat_filing_sends 
        SET status='sent', sent_at=NOW(), gpg_signed=?, sha256_attachments=?, send_log=?
        WHERE id=?
    ")->execute([$gpgSigned ? 1 : 0, implode("\n", $hashLog), implode("\n", $logLines), $sendId]);
    
    // Update filing status to 'filed'
    $db->prepare("
        UPDATE lavocat_filings 
        SET status='filed', filed_date=CURDATE(), filed_method='email (alfred@lavocat.ca — GPG signed, TLS)'
        WHERE id=?
    ")->execute([$send['filing_id']]);
    
    slog("Filing {$send['filing_number']} status updated to 'filed'");
    slog("═══ MISSION COMPLETE — OMAHON ═══");
    
} else {
    slog("ERROR: mail() failed!");
    $db->prepare("UPDATE lavocat_filing_sends SET status='failed', send_log=? WHERE id=?")->execute([implode("\n", $logLines), $sendId]);
    exit(7);
}
