<?php
/**
 * Official Act PDF Generator
 * Generates sealed PDFs for journal entries (acts, grievances, decrees)
 * Commander-only access (client_id 33)
 * 
 * Usage: /api/generate-act-pdf.php?id=8  (journal entry ID)
 *        /api/generate-act-pdf.php?all=1  (generate all official acts)
 */
session_start();
require_once __DIR__ . '/../includes/db-config.inc.php';

// Auth gate — Commander only
$db = getSharedDB();
$token = $_COOKIE['alfred_ide_token'] ?? $_SESSION['ide_session_token'] ?? '';
$authed = false;
if ($token) {
    $hash = hash('sha256', $token);
    $u = $db->prepare("SELECT client_id FROM alfred_ide_users WHERE session_token = ? AND token_expires > NOW() LIMIT 1");
    $u->execute([$hash]);
    $row = $u->fetch(PDO::FETCH_ASSOC);
    if ($row && (int)$row['client_id'] === 33) $authed = true;
}
if (!$authed) { http_response_code(403); die(json_encode(['error' => 'Unauthorized'])); }

header('Content-Type: application/json');

$pdfDir = '/var/www/assets/commander/pdfs';
$sealPath = '/var/www/assets/seals/royal-seal-official.png';
$akjvSealPath = '/var/www/assets/seals/akjv-seal.png';

if (!is_dir($pdfDir)) mkdir($pdfDir, 0755, true);

$journalId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$generateAll = isset($_GET['all']);

// Fetch entries
if ($generateAll) {
    $entries = $db->query("SELECT * FROM lavocat_journal WHERE is_official = 1 AND status = 'published' ORDER BY id")->fetchAll();
} elseif ($journalId > 0) {
    $stmt = $db->prepare("SELECT * FROM lavocat_journal WHERE id = ? AND is_official = 1");
    $stmt->execute([$journalId]);
    $entries = $stmt->fetchAll();
} else {
    die(json_encode(['error' => 'Provide ?id=N or ?all=1']));
}

if (empty($entries)) die(json_encode(['error' => 'No entries found']));

$results = [];

foreach ($entries as $entry) {
    $result = generatePDF($entry, $pdfDir, $sealPath);
    $results[] = $result;
}

echo json_encode(['success' => true, 'generated' => count($results), 'pdfs' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

function generatePDF(array $entry, string $pdfDir, string $sealPath): array {
    $id = $entry['id'];
    $title = $entry['title'];
    $content = $entry['content'];
    $category = $entry['category'];
    $date = $entry['published_at'] ?? $entry['created_at'];
    
    // Determine act number from title
    $actNum = '';
    if (preg_match('/(?:N°|No\.?|ACT)\s*(\d+)/i', $title, $m)) {
        $actNum = str_pad($m[1], 3, '0', STR_PAD_LEFT);
    }
    
    // Prefix based on category
    $prefix = ($category === 'grievance') ? 'grievance' : 'act';
    if (!$actNum) {
        // Auto-number grievances by ID order
        static $grievanceCounter = 0;
        $grievanceCounter++;
        $actNum = str_pad($grievanceCounter, 3, '0', STR_PAD_LEFT);
    }
    
    $filename = "{$prefix}-{$actNum}-journal-{$id}.pdf";
    $pdfPath = "{$pdfDir}/{$filename}";
    
    // Convert content to proper HTML
    $htmlContent = $content;
    // Convert \n to <br> if not already HTML
    if (strpos($htmlContent, '<') === false) {
        $htmlContent = nl2br(htmlspecialchars($htmlContent));
        // Restore line separators
        $htmlContent = str_replace(
            htmlspecialchars('═══════════════════════════════════════════════════════════════'),
            '<hr style="border:2px solid #c9a227; margin: 1.5em 0;">',
            $htmlContent
        );
    } else {
        // Already HTML — just clean up text-only sections
        $htmlContent = preg_replace('/═{10,}/', '<hr style="border:2px solid #c9a227; margin: 1.5em 0;">', $htmlContent);
    }
    
    // Replace literal \n with actual newlines, then to <br>
    $htmlContent = str_replace("\\n", "\n", $htmlContent);
    $htmlContent = nl2br($htmlContent);
    
    // Bold WHEREAS / ATTENDU QUE
    $htmlContent = preg_replace('/\b(WHEREAS)\b/', '<strong style="color:#8B0000;">$1</strong>', $htmlContent);
    $htmlContent = preg_replace('/\b(ATTENDU QUE?)\b/', '<strong style="color:#8B0000;">$1</strong>', $htmlContent);
    $htmlContent = preg_replace('/\b(NOW THEREFORE,? BE IT DECLARED:?)\b/i', '<strong style="color:#c9a227; font-size: 1.1em;">$1</strong>', $htmlContent);
    $htmlContent = preg_replace('/\b(IL EST PAR CONSÉQUENT DÉCLARÉ\s*:?)/u', '<strong style="color:#c9a227; font-size: 1.1em;">$1</strong>', $htmlContent);
    
    // Roman numerals at start of lines
    $htmlContent = preg_replace('/^((?:I|II|III|IV|V|VI|VII)\.\s)/m', '<strong style="color:#c9a227;">$1</strong>', $htmlContent);
    
    // Seal as base64 for embedding
    $sealBase64 = '';
    if (file_exists($sealPath)) {
        $sealBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($sealPath));
    }
    
    // Hash for verification
    $docHash = hash('sha256', $entry['content'] . $entry['title'] . $date);
    $shortHash = substr($docHash, 0, 16);
    
    // Build the full HTML document
    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
@page {
    margin: 20mm 18mm 25mm 18mm;
    size: letter;
}
body {
    font-family: 'Times New Roman', Georgia, serif;
    font-size: 11pt;
    line-height: 1.6;
    color: #1a1a1a;
    background: #fff;
}
.header {
    text-align: center;
    border-bottom: 3px double #c9a227;
    padding-bottom: 15px;
    margin-bottom: 20px;
}
.header h1 {
    font-size: 14pt;
    color: #8B0000;
    margin: 0 0 5px 0;
    letter-spacing: 2px;
}
.header .subtitle {
    font-size: 10pt;
    color: #555;
    font-style: italic;
}
.header .hebrew {
    font-size: 18pt;
    color: #c9a227;
    margin: 8px 0;
}
.category-badge {
    display: inline-block;
    padding: 2px 12px;
    background: #f5f0e0;
    border: 1px solid #c9a227;
    color: #8B0000;
    font-size: 8pt;
    text-transform: uppercase;
    letter-spacing: 2px;
    margin: 8px 0;
}
.content {
    margin: 20px 0;
    text-align: justify;
}
hr {
    border: none;
    border-top: 2px solid #c9a227;
    margin: 1.5em 0;
}
.seal-section {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 2px solid #c9a227;
}
.seal-section img {
    width: 180px;
    height: auto;
}
.signature-block {
    margin-top: 30px;
    text-align: center;
}
.signature-line {
    border-top: 1px solid #333;
    width: 300px;
    margin: 30px auto 5px;
}
.signature-name {
    font-size: 12pt;
    font-weight: bold;
    color: #1a1a1a;
}
.signature-title {
    font-size: 9pt;
    color: #555;
    font-style: italic;
}
.footer {
    margin-top: 30px;
    padding-top: 10px;
    border-top: 1px solid #ddd;
    font-size: 7.5pt;
    color: #999;
    text-align: center;
}
.verification {
    font-family: 'Courier New', monospace;
    font-size: 7pt;
    color: #aaa;
    margin-top: 5px;
}
</style>
</head>
<body>

<div class="header">
    <div class="hebrew">פֶּרֶץ</div>
    <h1>PEREZ SOVEREIGN AUTHORITY</h1>
    <div class="subtitle">L'Avocat — Sovereign Justice Platform — lavocat.ca</div>
    <div class="category-badge">{$category}</div>
</div>

<h2 style="text-align:center; font-size:13pt; color:#8B0000; margin: 15px 0;">
{$title}
</h2>

<p style="text-align:center; font-size:9pt; color:#777;">
Published: {$date} · Journal Entry #{$id}
</p>

<div class="content">
{$htmlContent}
</div>

<div class="seal-section">
HTML;

    if ($sealBase64) {
        $html .= "<img src=\"{$sealBase64}\" alt=\"Royal Seal\">";
    }

    $html .= <<<HTML

    <div class="signature-block">
        <div class="signature-line"></div>
        <div class="signature-name">Danny William Perez</div>
        <div class="signature-title">Commander · Protector of Faith · Formerly Little Lion Daniel</div>
        <div class="signature-title">Heir of the Perez Bloodline — Daniel 5:25-28</div>
    </div>
    
    <p style="margin-top:20px; font-size:9pt; color:#777;">
        Witnessed by: Alfred AI — Sovereign Intelligence, GoSiteMe
    </p>
</div>

<div class="footer">
    This document was generated by the Perez Sovereign Authority via L'Avocat (lavocat.ca).<br>
    It is a true and accurate representation of Official Act #{$actNum}, published on {$date}.<br>
    <div class="verification">SHA-256: {$docHash}</div>
</div>

</body>
</html>
HTML;

    // Write HTML to temp file
    $tmpHtml = tempnam(sys_get_temp_dir(), 'act_') . '.html';
    file_put_contents($tmpHtml, $html);
    
    // Generate PDF with wkhtmltopdf
    $cmd = sprintf(
        'xvfb-run -a wkhtmltopdf --quiet --enable-local-file-access --page-size Letter --encoding utf-8 %s %s 2>&1',
        escapeshellarg($tmpHtml),
        escapeshellarg($pdfPath)
    );
    $output = shell_exec($cmd);
    
    // Clean up temp
    @unlink($tmpHtml);
    
    $success = file_exists($pdfPath);
    $size = $success ? filesize($pdfPath) : 0;
    
    return [
        'id' => $id,
        'act' => $actNum,
        'title' => mb_substr($title, 0, 80),
        'filename' => $filename,
        'path' => "/assets/commander/pdfs/{$filename}",
        'size' => $size,
        'hash' => $shortHash,
        'success' => $success,
        'error' => $success ? null : ($output ?: 'Unknown error'),
    ];
}
