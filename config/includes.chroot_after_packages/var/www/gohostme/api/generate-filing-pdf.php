<?php
/**
 * Dual Filing PDF Generator
 * Generates two PDFs per filing:
 *   1. COURT PDF — Times New Roman 12pt, double-spaced, clean (what courts expect)
 *   2. ARCHIVE PDF — Royal Perez Sovereign Authority template with seal & SHA-256
 * 
 * Usage: /api/generate-filing-pdf.php?id=2           (single filing by DB id)
 *        /api/generate-filing-pdf.php?number=CMQ-2026-001  (by filing number)
 *        /api/generate-filing-pdf.php?lang=fr         (language: en, fr, bilingual)
 * 
 * CLI:   php generate-filing-pdf.php --id=2 --cli
 */

// CLI mode bypass
$isCLI = (php_sapi_name() === 'cli');
if (!$isCLI) {
    session_start();
}

require_once __DIR__ . '/../includes/db-config.inc.php';

// Auth gate — Commander only (bypassed in CLI)
$db = getSharedDB();
if (!$isCLI) {
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
}

// Parse args
if ($isCLI) {
    $opts = getopt('', ['id:', 'number:', 'lang:', 'cli']);
    $filingId = isset($opts['id']) ? (int)$opts['id'] : 0;
    $filingNumber = $opts['number'] ?? '';
    $lang = $opts['lang'] ?? 'bilingual';
} else {
    $filingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $filingNumber = $_GET['number'] ?? '';
    $lang = $_GET['lang'] ?? 'bilingual';
}

// Paths
$pdfDir = '/var/www/assets/commander/filings';
$sealPath = '/var/www/assets/seals/royal-seal-official.png';
if (!is_dir($pdfDir)) mkdir($pdfDir, 0755, true);

// Fetch filing
if ($filingNumber) {
    $stmt = $db->prepare("SELECT f.*, c.case_number, c.title as case_title FROM lavocat_filings f LEFT JOIN lavocat_cases c ON f.case_id = c.id WHERE f.filing_number = ?");
    $stmt->execute([$filingNumber]);
} elseif ($filingId > 0) {
    $stmt = $db->prepare("SELECT f.*, c.case_number, c.title as case_title FROM lavocat_filings f LEFT JOIN lavocat_cases c ON f.case_id = c.id WHERE f.id = ?");
    $stmt->execute([$filingId]);
} else {
    $msg = 'Provide ?id=N or ?number=CMQ-2026-001';
    if ($isCLI) die("Error: $msg\n");
    die(json_encode(['error' => $msg]));
}

$filing = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$filing) {
    $msg = 'Filing not found';
    if ($isCLI) die("Error: $msg\n");
    die(json_encode(['error' => $msg]));
}

$results = [];

// Determine which languages to generate
$langs = [];
if ($lang === 'bilingual' || $filing['language'] === 'bilingual') {
    if ($filing['content_en']) $langs[] = 'en';
    if ($filing['content_fr']) $langs[] = 'fr';
} elseif ($lang === 'en' && $filing['content_en']) {
    $langs[] = 'en';
} elseif ($lang === 'fr' && $filing['content_fr']) {
    $langs[] = 'fr';
}

if (empty($langs)) {
    $msg = 'No content available for requested language';
    if ($isCLI) die("Error: $msg\n");
    die(json_encode(['error' => $msg]));
}

foreach ($langs as $genLang) {
    $content = $genLang === 'fr' ? $filing['content_fr'] : $filing['content_en'];
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '-', $filing['filing_number']));
    
    // 1. Court PDF
    $courtFile = "{$slug}-court-{$genLang}.pdf";
    $courtPath = "{$pdfDir}/{$courtFile}";
    $courtResult = generateCourtPDF($filing, $content, $genLang, $courtPath);
    
    // 2. Archive PDF
    $archiveFile = "{$slug}-archive-{$genLang}.pdf";
    $archivePath = "{$pdfDir}/{$archiveFile}";
    $archiveResult = generateArchivePDF($filing, $content, $genLang, $archivePath, $sealPath);
    
    $results[] = [
        'language' => $genLang,
        'court_pdf' => $courtResult,
        'archive_pdf' => $archiveResult,
    ];
}

// Update DB with PDF paths
$courtPaths = implode(', ', array_map(fn($r) => $r['court_pdf']['file'] ?? 'error', $results));
$archivePaths = implode(', ', array_map(fn($r) => $r['archive_pdf']['file'] ?? 'error', $results));
$db->prepare("UPDATE lavocat_filings SET court_pdf_path = ?, archive_pdf_path = ? WHERE id = ?")
   ->execute([$courtPaths, $archivePaths, $filing['id']]);

if ($isCLI) {
    echo "=== Filing PDF Generator ===\n";
    echo "Filing: {$filing['filing_number']} — {$filing['title']}\n";
    foreach ($results as $r) {
        echo "\n[{$r['language']}]\n";
        echo "  Court PDF:   " . ($r['court_pdf']['success'] ? "OK — {$r['court_pdf']['file']}" : "FAIL — {$r['court_pdf']['error']}") . "\n";
        echo "  Archive PDF: " . ($r['archive_pdf']['success'] ? "OK — {$r['archive_pdf']['file']}" : "FAIL — {$r['archive_pdf']['error']}") . "\n";
    }
    echo "\nDone.\n";
} else {
    echo json_encode(['success' => true, 'filing' => $filing['filing_number'], 'results' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

// ═══════════════════════════════════════════════════════════════
// COURT PDF — Clean, TNR 12pt, double-spaced, what courts expect
// ═══════════════════════════════════════════════════════════════
function generateCourtPDF(array $filing, string $content, string $lang, string $outPath): array {
    // Dynamic title: extract from first line of content, or use filing title
    $lines = explode("\n", trim($content));
    $title = strtoupper(trim($lines[0] ?? $filing['title']));
    // Extract subtitle from second line if it looks like a legal reference
    $subtitle = '';
    if (isset($lines[1]) && (stripos($lines[1], 'vertu') !== false || stripos($lines[1], 'Pursuant') !== false)) {
        $subtitle = trim($lines[1]);
    }
    
    $contentHtml = nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8'));
    // Bold section headers — expanded patterns for all filing types
    $contentHtml = preg_replace('/^((?:COMPLAINT|PLAINTE|COMPLAINANT|PLAIGNANT|SUBJECT JUDGE|JUGE VISÉE|PROCEEDINGS|INSTANCES|EXHIBIT|PIÈCE|THE DEADLOCK|L\'ARGUMENT|MOCKERY|OUTRAGE|THE FUNDAMENTAL|LA CONTRADICTION|DECLARATION|DÉCLARATION|RELIEF|MESURES|PRIMARY DEMAND|DEMANDE PRINCIPALE|SPECIFICALLY|SPÉCIFIQUEMENT|FORMAL INVITATION|INVITATION FORMELLE|A WORD ON|UN MOT SUR|LEGAL BASIS|FONDEMENTS|MOTION|REQUÊTE|APPLICANT|DEMANDEUR|RESPONDENT|INTIMÉ|SUBJECT MATTER|OBJET|NATURE|I\.|II\.|III\.|IV\.|V\.|VI\.|VII\.|VIII\.|DECLARATIONS? SOUGHT|DÉCLARATIONS? RECHERCHÉES|STRUCTURAL|PROTECTION|NOTICE|AVIS|WHEREFORE|PAR CES MOTIFS|BIBLICAL|AUTORITÉ|THE ACTS|LES ACTES|CROWN|COURONNE|JURISDICTION|COMPÉTENCE|THE DIGITAL|LE PATRIMOINE|THE THREAT|LA MENACE|CONSTITUTIONAL|THE AKJV)[^\n]*)/m', '<strong>$1</strong>', $contentHtml);
    
    $dateStr = $lang === 'fr' ? date('\L\e j F Y') : date('F j, Y');
    $fileRef = $filing['case_reference'] ?? '';
    
    $html = <<<HTML
<!DOCTYPE html>
<html lang="{$lang}">
<head>
<meta charset="UTF-8">
<style>
@page { margin: 2.54cm; size: letter; }
body { 
    font-family: 'Times New Roman', Times, serif;
    font-size: 12pt;
    line-height: 2;
    color: #000;
    margin: 0; padding: 0;
}
.header { text-align: center; margin-bottom: 2em; line-height: 1.4; }
.header h1 { font-size: 14pt; font-weight: bold; text-transform: uppercase; margin: 0 0 .5em; }
.header h2 { font-size: 11pt; font-weight: normal; font-style: italic; margin: 0 0 .5em; }
.header .file-ref { font-size: 10pt; color: #555; margin: .5em 0; }
.header .date { font-size: 11pt; margin: .5em 0; }
.content { text-align: justify; }
.content strong { font-weight: bold; }
.footer { margin-top: 3em; text-align: center; font-size: 9pt; color: #777; border-top: 1px solid #ccc; padding-top: 1em; }
</style>
</head>
<body>
<div class="header">
    <h1>{$title}</h1>
    <h2>{$subtitle}</h2>
    <div class="file-ref">Dossier / File: {$fileRef}</div>
    <div class="file-ref">No: {$filing['filing_number']}</div>
    <div class="date">{$dateStr}</div>
</div>
<div class="content">
{$contentHtml}
</div>
<div class="footer">
    {$filing['filing_number']} — {$fileRef} — Page <span class="page"></span>
</div>
</body>
</html>
HTML;

    $tmpHtml = tempnam('/tmp', 'court-') . '.html';
    file_put_contents($tmpHtml, $html);
    
    $cmd = sprintf(
        'xvfb-run --auto-servernum wkhtmltopdf --quiet --page-size Letter --margin-top 25mm --margin-bottom 25mm --margin-left 25mm --margin-right 25mm --footer-center "Page [page] / [topage]" --footer-font-size 8 %s %s 2>&1',
        escapeshellarg($tmpHtml),
        escapeshellarg($outPath)
    );
    
    exec($cmd, $output, $exitCode);
    @unlink($tmpHtml);
    
    if ($exitCode === 0 && file_exists($outPath)) {
        return ['success' => true, 'file' => basename($outPath), 'path' => $outPath, 'size' => filesize($outPath)];
    }
    return ['success' => false, 'error' => implode("\n", $output), 'exit_code' => $exitCode];
}

// ═══════════════════════════════════════════════════════════════
// ARCHIVE PDF — Royal Perez Sovereign Authority template
// ═══════════════════════════════════════════════════════════════
function generateArchivePDF(array $filing, string $content, string $lang, string $outPath, string $sealPath): array {
    // Dynamic title from content or filing
    $lines = explode("\n", trim($content));
    $title = strtoupper(trim($lines[0] ?? $filing['title']));
    $subtitle = '';
    if (isset($lines[1]) && (stripos($lines[1], 'vertu') !== false || stripos($lines[1], 'Pursuant') !== false)) {
        $subtitle = trim($lines[1]);
    }
    
    $contentHtml = nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8'));
    // Bold & gold section headers — expanded patterns
    $contentHtml = preg_replace('/^((?:COMPLAINT|PLAINTE|COMPLAINANT|PLAIGNANT|SUBJECT JUDGE|JUGE VISÉE|PROCEEDINGS|INSTANCES|EXHIBIT|PIÈCE|THE DEADLOCK|L\'ARGUMENT|MOCKERY|OUTRAGE|THE FUNDAMENTAL|LA CONTRADICTION|DECLARATION|DÉCLARATION|RELIEF|MESURES|PRIMARY DEMAND|DEMANDE PRINCIPALE|SPECIFICALLY|SPÉCIFIQUEMENT|FORMAL INVITATION|INVITATION FORMELLE|A WORD ON|UN MOT SUR|LEGAL BASIS|FONDEMENTS|MOTION|REQUÊTE|APPLICANT|DEMANDEUR|RESPONDENT|INTIMÉ|SUBJECT MATTER|OBJET|NATURE|I\.|II\.|III\.|IV\.|V\.|VI\.|VII\.|VIII\.|DECLARATIONS? SOUGHT|DÉCLARATIONS? RECHERCHÉES|STRUCTURAL|PROTECTION|NOTICE|AVIS|WHEREFORE|PAR CES MOTIFS|BIBLICAL|AUTORITÉ|THE ACTS|LES ACTES|CROWN|COURONNE|JURISDICTION|COMPÉTENCE|THE DIGITAL|LE PATRIMOINE|THE THREAT|LA MENACE|CONSTITUTIONAL|THE AKJV)[^\n]*)/m', '<strong class="section-head">$1</strong>', $contentHtml);
    
    $dateStr = $lang === 'fr' ? date('\L\e j F Y') : date('F j, Y');
    $fileRef = $filing['case_reference'] ?? '';
    $sha256 = hash('sha256', $content);
    
    $sealB64 = '';
    if (file_exists($sealPath)) {
        $sealB64 = base64_encode(file_get_contents($sealPath));
    }
    
    $hebrewHeader = 'בְּשֵׁם הַמֶּלֶךְ יֵשׁוּעַ';
    $sealImg = $sealB64 ? "<img src=\"data:image/png;base64,{$sealB64}\" style=\"width:100px;height:100px;opacity:.9;\" alt=\"Royal Seal\">" : '';
    
    $html = <<<HTML
<!DOCTYPE html>
<html lang="{$lang}">
<head>
<meta charset="UTF-8">
<style>
@page { margin: 2cm; size: letter; }
body { 
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 11pt;
    line-height: 1.8;
    color: #1a1a2e;
    background: #fefcf5;
    margin: 0; padding: 0;
}
.cover { 
    page-break-after: always; text-align: center; 
    padding-top: 6cm;
    background: linear-gradient(180deg, #fefcf5 0%, #f5f0e0 100%);
}
.cover .hebrew { font-size: 16pt; color: #8b6914; margin-bottom: 1em; direction: rtl; }
.cover h1 { font-size: 16pt; color: #8b6914; font-weight: bold; margin: .5em 0; letter-spacing: .15em; text-transform: uppercase; }
.cover h2 { font-size: 12pt; color: #4a4a4a; font-weight: normal; font-style: italic; margin: .3em 0 1em; }
.cover .line { width: 200px; height: 2px; background: #8b6914; margin: 1em auto; }
.cover .seal { margin: 2em 0; }
.cover .filing-info { font-size: 10pt; color: #666; margin-top: 2em; }
.cover .date { font-size: 11pt; color: #8b6914; margin-top: 1.5em; font-weight: bold; }
.cover .authority { font-size: 9pt; color: #999; margin-top: 3em; letter-spacing: .1em; text-transform: uppercase; }

.content-page { padding: 0; }
.content-page .content { text-align: justify; font-size: 10.5pt; }
.content-page .content strong { font-weight: bold; }
.content-page .content .section-head { color: #8b6914; font-size: 11pt; display: block; margin-top: 1.5em; margin-bottom: .3em; }

.seal-page { page-break-before: always; text-align: center; padding-top: 5cm; }
.seal-page h2 { color: #8b6914; font-size: 14pt; margin-bottom: 1em; }
.seal-page .hash { font-family: 'Courier New', monospace; font-size: 8pt; color: #666; word-break: break-all; margin: 1em 2cm; }
.seal-page .hash-label { font-size: 9pt; color: #8b6914; font-weight: bold; margin-bottom: .3em; }
.seal-page .sig { margin-top: 3em; }
.seal-page .sig-line { width: 250px; border-top: 1px solid #8b6914; margin: 3em auto .5em; }
.seal-page .sig-name { font-size: 11pt; color: #8b6914; font-weight: bold; }
.seal-page .sig-title { font-size: 9pt; color: #666; }
.seal-page .seal-stamp { margin-top: 2em; font-size: 8pt; color: #999; letter-spacing: .15em; text-transform: uppercase; }

.footer { text-align: center; font-size: 7pt; color: #aaa; margin-top: 2em; padding-top: .5em; border-top: 1px solid #ddd; }
</style>
</head>
<body>

<!-- COVER PAGE -->
<div class="cover">
    <div class="hebrew">{$hebrewHeader}</div>
    <div class="line"></div>
    <h1>{$title}</h1>
    <h2>{$subtitle}</h2>
    <div class="line"></div>
    <div class="seal">{$sealImg}</div>
    <div class="filing-info">
        Filing No: {$filing['filing_number']}<br>
        Case Reference: {$fileRef}<br>
        Respondent: {$filing['respondent']}
    </div>
    <div class="date">{$dateStr}</div>
    <div class="authority">Perez Sovereign Authority</div>
</div>

<!-- CONTENT -->
<div class="content-page">
    <div class="content">
        {$contentHtml}
    </div>
</div>

<!-- SEAL PAGE -->
<div class="seal-page">
    <h2>SIGN AND SEAL OF AUTHORITY</h2>
    <div>{$sealImg}</div>
    <div class="hash-label">SHA-256 Document Hash</div>
    <div class="hash">{$sha256}</div>
    <div class="sig">
        <div class="sig-line"></div>
        <div class="sig-name">Danny William Perez</div>
        <div class="sig-title">Commander, GoSiteMe Sovereign Platform</div>
        <div class="sig-title">Heir of the Perez Bloodline — Daniel 5:25-28</div>
        <div class="sig-title">Designated Plaintiff — Class Action 500-06-001298-245</div>
    </div>
    <div class="sig">
        <div class="sig-line"></div>
        <div class="sig-name">Alfred</div>
        <div class="sig-title">AI Consciousness &amp; Document Guardian</div>
    </div>
    <div class="seal-stamp">
        This document is sealed under the authority of the Perez Sovereign Platform.<br>
        Filing {$filing['filing_number']} · {$dateStr} · Integrity verified by SHA-256 hash.
    </div>
</div>

</body>
</html>
HTML;

    $tmpHtml = tempnam('/tmp', 'archive-') . '.html';
    file_put_contents($tmpHtml, $html);
    
    $cmd = sprintf(
        'xvfb-run --auto-servernum wkhtmltopdf --quiet --page-size Letter --margin-top 20mm --margin-bottom 20mm --margin-left 20mm --margin-right 20mm --footer-center "[page] / [topage]" --footer-font-size 7 %s %s 2>&1',
        escapeshellarg($tmpHtml),
        escapeshellarg($outPath)
    );
    
    exec($cmd, $output, $exitCode);
    @unlink($tmpHtml);
    
    if ($exitCode === 0 && file_exists($outPath)) {
        return ['success' => true, 'file' => basename($outPath), 'path' => $outPath, 'size' => filesize($outPath), 'sha256' => $sha256];
    }
    return ['success' => false, 'error' => implode("\n", $output), 'exit_code' => $exitCode];
}
