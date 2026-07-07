<?php
/**
 * DIGITAL DOCUMENT SIGNING API
 * ═══════════════════════════════════════════════════════════
 * Upload PDF → Sign with signature specimen → SHA-3 verify → Notify lawyer
 *
 * Endpoints (via POST action parameter):
 *   upload   — Upload a PDF document to sign
 *   sign     — Apply signature specimen to the document
 *   verify   — Verify a signed document (public, by token)
 *   download — Download signed PDF (by token)
 *   status   — Check document status
 */

// Security: start session, require auth for non-public endpoints
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/db-config.inc.php';
require_once __DIR__ . '/../vendor/autoload.php';

use setasign\Fpdi\Fpdi;

header('Content-Type: application/json');

$db = getSharedDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Auth check — signing requires login (Commander or IDE user)
$userId = $_SESSION['ide_user_id'] ?? null;
$clientId = $_SESSION['client_id'] ?? $_SESSION['ide_client_id'] ?? null;
$isCommander = $clientId && (int)$clientId === 33;

// Public endpoints that don't need auth
$publicActions = ['verify', 'download'];

if (!in_array($action, $publicActions) && !$userId && !$isCommander) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Secure upload directory (outside public_html)
$uploadDir = '/home/gositeme/private-docs/signed-documents/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0750, true);
}
$signedDir = $uploadDir . 'signed/';
if (!is_dir($signedDir)) {
    mkdir($signedDir, 0750, true);
}

switch ($action) {

    // ═══════════════════════════════════════════════
    // UPLOAD — Accept a PDF to be signed
    // ═══════════════════════════════════════════════
    case 'upload':
        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'No file uploaded or upload error']);
            exit;
        }

        $file = $_FILES['document'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if ($mime !== 'application/pdf') {
            http_response_code(400);
            echo json_encode(['error' => 'Only PDF files are accepted']);
            exit;
        }

        // 20MB max
        if ($file['size'] > 20 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['error' => 'File too large (max 20MB)']);
            exit;
        }

        // Sanitize filename
        $origName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
        $storedName = time() . '_' . bin2hex(random_bytes(8)) . '_' . $origName;
        $destPath = $uploadDir . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to store file']);
            exit;
        }

        // SHA3-256 hash of original
        $docHash = hash_file('sha3-256', $destPath);

        // Recipient info
        $recipientEmail = trim($_POST['recipient_email'] ?? '');
        $recipientName = trim($_POST['recipient_name'] ?? '');

        // Validate email if provided
        if ($recipientEmail && !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid recipient email']);
            exit;
        }

        // Verification token
        $verifyToken = bin2hex(random_bytes(32));

        $stmt = $db->prepare("INSERT INTO signed_documents
            (doc_hash_sha3, signer_name, signer_client_id, recipient_email, recipient_name,
             original_filename, original_path, upload_ip, verification_token)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $docHash,
            $_POST['signer_name'] ?? 'Commander',
            $clientId,
            $recipientEmail ?: null,
            $recipientName ?: null,
            $origName,
            $destPath,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $verifyToken,
        ]);

        $docId = $db->lastInsertId();

        // Count pages
        try {
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($destPath);
        } catch (Exception $e) {
            $pageCount = 0;
        }

        echo json_encode([
            'success' => true,
            'doc_id' => (int)$docId,
            'filename' => $origName,
            'sha3_256' => $docHash,
            'pages' => $pageCount,
            'size_bytes' => filesize($destPath),
        ]);
        break;

    // ═══════════════════════════════════════════════
    // SIGN — Apply signature to the document
    // ═══════════════════════════════════════════════
    case 'sign':
        $docId = (int)($_POST['doc_id'] ?? 0);
        $signatureData = $_POST['signature'] ?? ''; // Base64 PNG

        if (!$docId || !$signatureData) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing doc_id or signature']);
            exit;
        }

        // Validate base64 image
        if (!preg_match('/^data:image\/png;base64,/', $signatureData)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid signature format (need base64 PNG)']);
            exit;
        }

        // Fetch document
        $stmt = $db->prepare("SELECT * FROM signed_documents WHERE id = ? AND status = 'pending'");
        $stmt->execute([$docId]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doc) {
            http_response_code(404);
            echo json_encode(['error' => 'Document not found or already signed']);
            exit;
        }

        // Verify ownership
        if ($doc['signer_client_id'] && (int)$doc['signer_client_id'] !== (int)$clientId) {
            http_response_code(403);
            echo json_encode(['error' => 'Not authorized to sign this document']);
            exit;
        }

        // Save signature image
        $sigPng = base64_decode(preg_replace('/^data:image\/png;base64,/', '', $signatureData));
        $sigPath = $uploadDir . 'sig_' . $docId . '_' . time() . '.png';
        file_put_contents($sigPath, $sigPng);

        // Signature placement
        $sigPage = (int)($_POST['sig_page'] ?? 0); // 0 = last page
        $sigX = (float)($_POST['sig_x'] ?? 20);
        $sigY = (float)($_POST['sig_y'] ?? 240);
        $sigW = (float)($_POST['sig_w'] ?? 60);

        try {
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($doc['original_path']);

            // Target page for signature (0 or out of range = last page)
            $targetPage = ($sigPage > 0 && $sigPage <= $pageCount) ? $sigPage : $pageCount;

            for ($p = 1; $p <= $pageCount; $p++) {
                $tpl = $pdf->importPage($p);
                $size = $pdf->getTemplateSize($tpl);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tpl);

                if ($p === $targetPage) {
                    // Stamp signature
                    $pdf->Image($sigPath, $sigX, $sigY, $sigW);

                    // Add verification text below signature
                    $pdf->SetFont('Helvetica', '', 7);
                    $pdf->SetTextColor(100, 100, 100);
                    $pdf->SetXY($sigX, $sigY + ($sigW * 0.4) + 2);

                    $now = new DateTime('now', new DateTimeZone('America/Montreal'));
                    $dateStr = $now->format('F j, Y \\a\\t g:i A T');
                    $pdf->Cell($sigW + 20, 3, 'Digitally signed: ' . $dateStr, 0, 1);
                    $pdf->SetX($sigX);
                    $pdf->Cell($sigW + 20, 3, 'SHA3-256: ' . substr($doc['doc_hash_sha3'], 0, 32) . '...', 0, 1);
                    $pdf->SetX($sigX);
                    $pdf->Cell($sigW + 20, 3, 'Verify: gositeme.com/sign/verify/' . substr($doc['verification_token'], 0, 16), 0, 1);
                }
            }

            // Save signed PDF
            $signedName = 'signed_' . $docId . '_' . time() . '.pdf';
            $signedPath = $signedDir . $signedName;
            $pdf->Output('F', $signedPath);

            // SHA3-256 of signed document
            $signedHash = hash_file('sha3-256', $signedPath);

            // Update DB
            $stmt = $db->prepare("UPDATE signed_documents SET
                signed_hash_sha3 = ?,
                signed_path = ?,
                signature_image = ?,
                status = 'signed',
                signed_at = NOW()
                WHERE id = ?");
            $stmt->execute([$signedHash, $signedPath, $signatureData, $docId]);

            // Clean up signature PNG
            unlink($sigPath);

            // Send notification to lawyer
            $notified = false;
            if ($doc['recipient_email']) {
                $notified = sendSignatureNotification($doc, $signedHash, $db);
                if ($notified) {
                    $db->prepare("UPDATE signed_documents SET status = 'notified', notified_at = NOW() WHERE id = ?")->execute([$docId]);
                }
            }

            echo json_encode([
                'success' => true,
                'doc_id' => $docId,
                'signed_hash_sha3' => $signedHash,
                'original_hash_sha3' => $doc['doc_hash_sha3'],
                'signed_at' => $now->format('Y-m-d H:i:s T'),
                'pages' => $pageCount,
                'signature_page' => $targetPage,
                'notified' => $notified,
                'verify_url' => 'https://gositeme.com/sign/verify/' . $doc['verification_token'],
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'PDF signing failed: ' . $e->getMessage()]);
        }
        break;

    // ═══════════════════════════════════════════════
    // VERIFY — Public verification of signed document
    // ═══════════════════════════════════════════════
    case 'verify':
        $token = preg_replace('/[^a-f0-9]/', '', $_GET['token'] ?? '');

        if (strlen($token) < 16) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid verification token']);
            exit;
        }

        $stmt = $db->prepare("SELECT id, doc_hash_sha3, signed_hash_sha3, signer_name,
            original_filename, status, signed_at, created_at
            FROM signed_documents WHERE verification_token = ?");
        $stmt->execute([$token]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doc) {
            http_response_code(404);
            echo json_encode(['error' => 'Document not found']);
            exit;
        }

        echo json_encode([
            'verified' => true,
            'document' => [
                'filename' => $doc['original_filename'],
                'signer' => $doc['signer_name'],
                'status' => $doc['status'],
                'original_sha3' => $doc['doc_hash_sha3'],
                'signed_sha3' => $doc['signed_hash_sha3'],
                'signed_at' => $doc['signed_at'],
                'uploaded_at' => $doc['created_at'],
            ],
        ]);
        break;

    // ═══════════════════════════════════════════════
    // DOWNLOAD — Download signed PDF (by token)
    // ═══════════════════════════════════════════════
    case 'download':
        $token = preg_replace('/[^a-f0-9]/', '', $_GET['token'] ?? '');

        $stmt = $db->prepare("SELECT * FROM signed_documents WHERE verification_token = ? AND status IN ('signed','notified','downloaded')");
        $stmt->execute([$token]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doc || !$doc['signed_path'] || !file_exists($doc['signed_path'])) {
            http_response_code(404);
            echo json_encode(['error' => 'Signed document not found']);
            exit;
        }

        $db->prepare("UPDATE signed_documents SET status = 'downloaded' WHERE id = ?")->execute([$doc['id']]);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="signed_' . $doc['original_filename'] . '"');
        header('Content-Length: ' . filesize($doc['signed_path']));
        readfile($doc['signed_path']);
        exit;

    // ═══════════════════════════════════════════════
    // STATUS — Check document status (authenticated)
    // ═══════════════════════════════════════════════
    case 'status':
        $docId = (int)($_GET['doc_id'] ?? 0);
        if (!$docId) {
            // List all documents for this user
            $stmt = $db->prepare("SELECT id, original_filename, signer_name, recipient_name, recipient_email, status, created_at, signed_at, notified_at
                FROM signed_documents WHERE signer_client_id = ? ORDER BY created_at DESC LIMIT 50");
            $stmt->execute([$clientId]);
            echo json_encode(['documents' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } else {
            $stmt = $db->prepare("SELECT id, doc_hash_sha3, signed_hash_sha3, original_filename, signer_name, recipient_name, recipient_email, status, created_at, signed_at, notified_at
                FROM signed_documents WHERE id = ? AND signer_client_id = ?");
            $stmt->execute([$docId, $clientId]);
            $doc = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($doc ?: ['error' => 'Not found']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action. Use: upload, sign, verify, download, status']);
}


/**
 * Send email notification to the lawyer/recipient
 */
function sendSignatureNotification(array $doc, string $signedHash, PDO $db): bool {
    $to = $doc['recipient_email'];
    $recipientName = $doc['recipient_name'] ?: 'Counsel';
    $signerName = $doc['signer_name'] ?: 'the signer';
    $filename = $doc['original_filename'];
    $verifyUrl = 'https://gositeme.com/sign/verify/' . $doc['verification_token'];
    $downloadUrl = 'https://gositeme.com/api/sign.php?action=download&token=' . $doc['verification_token'];

    $subject = "Document Signed: {$filename} — {$signerName}";

    $body = <<<EMAIL
Dear {$recipientName},

This is to notify you that the following document has been digitally signed:

  Document: {$filename}
  Signed by: {$signerName}
  Date: {$doc['signed_at']}

  Original SHA3-256: {$doc['doc_hash_sha3']}
  Signed SHA3-256:   {$signedHash}

You may verify this signature and download the signed document at:
  {$verifyUrl}

Direct download:
  {$downloadUrl}

This document was signed using cryptographic verification (SHA3-256).
The signature is tamper-evident — any modification to the PDF will
invalidate the hash.

—
GoSiteMe Digital Signing Service
gositeme.com/sign
EMAIL;

    $headers = [
        'From: GoSiteMe Signing <noreply@gositeme.com>',
        'Reply-To: danny@gositeme.com',
        'X-Mailer: GoSiteMe-Sign/1.0',
        'Content-Type: text/plain; charset=UTF-8',
    ];

    return mail($to, $subject, $body, implode("\r\n", $headers));
}
