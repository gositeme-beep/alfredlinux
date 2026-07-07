<?php
/**
 * Alfred Document Processing API
 * ───────────────────────────────
 * Parse, extract, and process documents (PDF, DOCX, images, code).
 * Uses server-side tools for extraction, AI for analysis.
 *
 * Endpoints:
 *   POST ?action=parse          → Upload & parse a document
 *   POST ?action=ocr            → OCR an image
 *   POST ?action=summarize      → Summarize a document
 *   POST ?action=extract        → Extract structured data
 *   GET  ?action=formats        → List supported formats
 *   GET  ?action=history        → User's processing history
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

define('UPLOAD_DIR', dirname(__DIR__) . '/cache/documents/');
define('MAX_FILE_SIZE', 25 * 1024 * 1024); // 25MB
define('OLLAMA_HOST', getenv('OLLAMA_HOST') ?: 'http://localhost:11434');

// Supported formats
$SUPPORTED_FORMATS = [
    'pdf'  => ['mime' => ['application/pdf'], 'parser' => 'parsePDF'],
    'docx' => ['mime' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'], 'parser' => 'parseDOCX'],
    'txt'  => ['mime' => ['text/plain'], 'parser' => 'parseTXT'],
    'csv'  => ['mime' => ['text/csv', 'application/csv'], 'parser' => 'parseCSV'],
    'json' => ['mime' => ['application/json'], 'parser' => 'parseJSON'],
    'md'   => ['mime' => ['text/markdown', 'text/x-markdown'], 'parser' => 'parseTXT'],
    'html' => ['mime' => ['text/html'], 'parser' => 'parseHTML'],
    'xml'  => ['mime' => ['application/xml', 'text/xml'], 'parser' => 'parseXML'],
    'png'  => ['mime' => ['image/png'], 'parser' => 'parseImage'],
    'jpg'  => ['mime' => ['image/jpeg'], 'parser' => 'parseImage'],
    'webp' => ['mime' => ['image/webp'], 'parser' => 'parseImage'],
];

// ── Database Setup ──────────────────────────────────────────────
function ensureDocTable() {
    $db = getDB();
    if (!$db) return;
    $db->exec("CREATE TABLE IF NOT EXISTS alfred_documents (
        id VARCHAR(32) PRIMARY KEY,
        client_id INT DEFAULT NULL,
        filename VARCHAR(255) NOT NULL,
        format VARCHAR(10) NOT NULL,
        file_size INT DEFAULT 0,
        page_count INT DEFAULT 0,
        word_count INT DEFAULT 0,
        content_preview TEXT DEFAULT NULL,
        metadata JSON DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_client (client_id),
        KEY idx_format (format)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function getAuthUser() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return $_SESSION['client_id'] ?? null;
}

// ── Document Parsers ────────────────────────────────────────────

function parsePDF($filePath) {
    $text = '';
    $pageCount = 0;

    // Method 1: pdftotext (poppler-utils)
    $escaped = escapeshellarg($filePath);
    $output = shell_exec("pdftotext {$escaped} - 2>/dev/null");
    if ($output && strlen(trim($output)) > 10) {
        $text = $output;
        // Count pages
        $pagesOutput = shell_exec("pdfinfo {$escaped} 2>/dev/null");
        if (preg_match('/Pages:\s*(\d+)/', $pagesOutput ?? '', $m)) {
            $pageCount = intval($m[1]);
        }
    }

    // Method 2: PHP-based basic PDF text extraction
    if (!$text) {
        $content = file_get_contents($filePath);
        // Extract text between BT and ET tags
        preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $matches);
        foreach ($matches[1] as $block) {
            preg_match_all('/\((.*?)\)/', $block, $textMatches);
            $text .= implode(' ', $textMatches[1]);
        }
        // Count pages
        $pageCount = preg_match_all('/\/Type\s*\/Page[^s]/', $content);
    }

    return [
        'text' => trim($text),
        'pages' => $pageCount,
        'method' => $output ? 'pdftotext' : 'php_native',
    ];
}

function parseDOCX($filePath) {
    $text = '';

    $zip = new ZipArchive();
    if ($zip->open($filePath) === true) {
        $content = $zip->getFromName('word/document.xml');
        if ($content) {
            // Strip XML tags, keep text content
            $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NONET);
            if ($xml) {
                $text = strip_tags($xml->asXML());
            } else {
                $text = strip_tags($content);
            }
            $text = preg_replace('/\s+/', ' ', $text);
        }
        $zip->close();
    }

    return ['text' => trim($text), 'pages' => 0, 'method' => 'ziparchive'];
}

function parseTXT($filePath) {
    $text = file_get_contents($filePath);
    // Detect and convert encoding if needed
    $encoding = mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
    if ($encoding && $encoding !== 'UTF-8') {
        $text = mb_convert_encoding($text, 'UTF-8', $encoding);
    }
    return ['text' => $text, 'pages' => 0, 'method' => 'direct'];
}

function parseCSV($filePath) {
    $rows = [];
    $handle = fopen($filePath, 'r');
    if (!$handle) return ['text' => '', 'pages' => 0, 'method' => 'csv'];

    $headers = fgetcsv($handle);
    $rowCount = 0;
    while (($row = fgetcsv($handle)) !== false && $rowCount < 1000) {
        if ($headers) {
            $named = [];
            foreach ($headers as $i => $h) {
                $named[trim($h)] = $row[$i] ?? '';
            }
            $rows[] = $named;
        } else {
            $rows[] = $row;
        }
        $rowCount++;
    }
    fclose($handle);

    $text = "CSV Data (" . count($rows) . " rows, " . count($headers ?: []) . " columns)\n";
    $text .= "Headers: " . implode(', ', $headers ?: []) . "\n\n";
    foreach (array_slice($rows, 0, 20) as $row) {
        $text .= implode(' | ', is_array($row) ? array_values($row) : [$row]) . "\n";
    }
    if (count($rows) > 20) $text .= "... (" . (count($rows) - 20) . " more rows)\n";

    return ['text' => $text, 'pages' => 0, 'method' => 'csv', 'data' => ['rows' => count($rows), 'columns' => count($headers ?: []), 'headers' => $headers]];
}

function parseJSON($filePath) {
    $content = file_get_contents($filePath);
    $data = json_decode($content, true);
    if ($data === null) {
        return ['text' => $content, 'pages' => 0, 'method' => 'json_raw'];
    }
    $text = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return ['text' => $text, 'pages' => 0, 'method' => 'json', 'data' => ['type' => is_array($data) ? (array_is_list($data) ? 'array' : 'object') : gettype($data)]];
}

function parseHTML($filePath) {
    $content = file_get_contents($filePath);
    // Remove scripts, styles
    $content = preg_replace('/<script[^>]*>.*?<\/script>/si', '', $content);
    $content = preg_replace('/<style[^>]*>.*?<\/style>/si', '', $content);
    $text = strip_tags($content);
    $text = preg_replace('/\s+/', ' ', $text);
    return ['text' => trim($text), 'pages' => 0, 'method' => 'strip_tags'];
}

function parseXML($filePath) {
    $content = file_get_contents($filePath);
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NONET);
    $text = $xml ? strip_tags($xml->asXML()) : strip_tags($content);
    return ['text' => trim($text), 'pages' => 0, 'method' => 'simplexml'];
}

function parseImage($filePath) {
    $text = '';

    // Method 1: Tesseract OCR
    $escaped = escapeshellarg($filePath);
    $output = shell_exec("tesseract {$escaped} stdout 2>/dev/null");
    if ($output && strlen(trim($output)) > 2) {
        $text = trim($output);
    }

    // Method 2: AI vision (Ollama with vision model)
    if (!$text) {
        $imageData = base64_encode(file_get_contents($filePath));
        $ch = curl_init(OLLAMA_HOST . '/api/generate');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'llava',
                'prompt' => 'Describe this image in detail. If there is text, transcribe it.',
                'images' => [$imageData],
                'stream' => false,
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);
        if (!empty($data['response'])) {
            $text = $data['response'];
        }
    }

    return ['text' => $text ?: '[Image - no text extracted]', 'pages' => 1, 'method' => $output ? 'tesseract' : 'ai_vision'];
}

// ── AI Processing ───────────────────────────────────────────────
function aiSummarize($text, $maxLength = 500) {
    $truncated = substr($text, 0, 15000);
    $prompt = "Summarize the following document concisely in {$maxLength} words or less. Focus on key points, findings, and conclusions.\n\nDocument:\n{$truncated}";

    $ch = curl_init(OLLAMA_HOST . '/api/generate');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'llama3.1',
            'prompt' => $prompt,
            'stream' => false,
            'options' => ['num_predict' => 1000, 'temperature' => 0.3],
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['response'] ?? substr($text, 0, $maxLength * 5); // fallback to truncation
}

function aiExtract($text, $schema) {
    $truncated = substr($text, 0, 15000);
    $schemaJson = json_encode($schema);
    $prompt = "Extract structured data from this document according to this schema: {$schemaJson}\n\nReturn ONLY valid JSON matching the schema.\n\nDocument:\n{$truncated}";

    $ch = curl_init(OLLAMA_HOST . '/api/generate');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'llama3.1',
            'prompt' => $prompt,
            'stream' => false,
            'options' => ['num_predict' => 2000, 'temperature' => 0.1],
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    $extracted = $data['response'] ?? '';

    // Try to parse JSON from response
    if (preg_match('/\{.*\}/s', $extracted, $match)) {
        $parsed = json_decode($match[0], true);
        if ($parsed) return $parsed;
    }

    return ['raw' => $extracted];
}

// ── File Upload Handler ─────────────────────────────────────────
function handleUpload() {
    global $SUPPORTED_FORMATS;

    if (!isset($_FILES['file'])) {
        jsonResponse(['error' => 'No file uploaded'], 400);
    }

    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [1 => 'File too large', 2 => 'File too large', 3 => 'Partial upload', 4 => 'No file'];
        jsonResponse(['error' => $errors[$file['error']] ?? 'Upload error'], 400);
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        jsonResponse(['error' => 'File exceeds 25MB limit'], 400);
    }

    // Validate extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!isset($SUPPORTED_FORMATS[$ext])) {
        jsonResponse(['error' => "Unsupported format: {$ext}", 'supported' => array_keys($SUPPORTED_FORMATS)], 400);
    }

    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = $SUPPORTED_FORMATS[$ext]['mime'];
    if (!in_array($mime, $allowedMimes) && $mime !== 'application/octet-stream') {
        jsonResponse(['error' => 'MIME type mismatch'], 400);
    }

    // Save to temp location
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0750, true);
    }

    $docId = bin2hex(random_bytes(16));
    $safeFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
    $destPath = UPLOAD_DIR . $docId . '_' . $safeFilename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        jsonResponse(['error' => 'Failed to save file'], 500);
    }

    return [
        'id' => $docId,
        'path' => $destPath,
        'name' => $safeFilename,
        'ext' => $ext,
        'size' => $file['size'],
        'mime' => $mime,
    ];
}

// ── Router ──────────────────────────────────────────────────────
$action = sanitize($_GET['action'] ?? '', 30);

switch ($action) {

    // ── Parse document ──────────────────────────────────────────
    case 'parse':
        $clientId = getAuthUser();
        if (!$clientId) jsonResponse(['error' => 'Authentication required'], 401);

        $upload = handleUpload();
        $parser = $SUPPORTED_FORMATS[$upload['ext']]['parser'];
        $result = $parser($upload['path']);

        $wordCount = str_word_count($result['text']);
        $preview = substr($result['text'], 0, 2000);

        // Store in database
        $db = getDB();
        ensureDocTable();
        $stmt = $db->prepare("INSERT INTO alfred_documents (id, client_id, filename, format, file_size, page_count, word_count, content_preview, metadata)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $upload['id'], $clientId, $upload['name'], $upload['ext'],
            $upload['size'], $result['pages'], $wordCount, $preview,
            json_encode(['method' => $result['method'], 'data' => $result['data'] ?? null]),
        ]);

        // Clean up uploaded file
        @unlink($upload['path']);

        jsonResponse([
            'success' => true,
            'document' => [
                'id' => $upload['id'],
                'filename' => $upload['name'],
                'format' => $upload['ext'],
                'file_size' => $upload['size'],
                'pages' => $result['pages'],
                'words' => $wordCount,
                'method' => $result['method'],
                'text' => $result['text'],
                'preview' => $preview,
            ],
        ]);
        break;

    // ── OCR an image ────────────────────────────────────────────
    case 'ocr':
        $clientId = getAuthUser();
        if (!$clientId) jsonResponse(['error' => 'Authentication required'], 401);

        $upload = handleUpload();
        if (!in_array($upload['ext'], ['png', 'jpg', 'webp'])) {
            @unlink($upload['path']);
            jsonResponse(['error' => 'OCR requires an image (png, jpg, webp)'], 400);
        }

        $result = parseImage($upload['path']);
        @unlink($upload['path']);

        jsonResponse([
            'success' => true,
            'text' => $result['text'],
            'method' => $result['method'],
        ]);
        break;

    // ── Summarize a document ────────────────────────────────────
    case 'summarize':
        $clientId = getAuthUser();
        if (!$clientId) jsonResponse(['error' => 'Authentication required'], 401);

        $input = json_decode(file_get_contents('php://input'), true);

        // Either provide document_id or text directly
        if (!empty($input['document_id'])) {
            $db = getDB();
            ensureDocTable();
            $stmt = $db->prepare("SELECT content_preview FROM alfred_documents WHERE id = ? AND client_id = ?");
            $stmt->execute([sanitize($input['document_id'], 32), $clientId]);
            $doc = $stmt->fetch();
            if (!$doc) jsonResponse(['error' => 'Document not found'], 404);
            $text = $doc['content_preview'];
        } elseif (!empty($input['text'])) {
            $text = substr($input['text'], 0, 50000);
        } else {
            jsonResponse(['error' => 'Provide document_id or text'], 400);
        }

        $maxLength = intval($input['max_length'] ?? 500);
        $summary = aiSummarize($text, min($maxLength, 2000));

        jsonResponse(['success' => true, 'summary' => $summary]);
        break;

    // ── Extract structured data ─────────────────────────────────
    case 'extract':
        $clientId = getAuthUser();
        if (!$clientId) jsonResponse(['error' => 'Authentication required'], 401);

        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['text']) && empty($input['document_id'])) {
            jsonResponse(['error' => 'Provide document_id or text'], 400);
        }

        $schema = $input['schema'] ?? ['title' => '', 'date' => '', 'key_points' => [], 'entities' => []];

        if (!empty($input['document_id'])) {
            $db = getDB();
            ensureDocTable();
            $stmt = $db->prepare("SELECT content_preview FROM alfred_documents WHERE id = ? AND client_id = ?");
            $stmt->execute([sanitize($input['document_id'], 32), $clientId]);
            $doc = $stmt->fetch();
            if (!$doc) jsonResponse(['error' => 'Document not found'], 404);
            $text = $doc['content_preview'];
        } else {
            $text = substr($input['text'], 0, 50000);
        }

        $extracted = aiExtract($text, $schema);
        jsonResponse(['success' => true, 'extracted' => $extracted, 'schema' => $schema]);
        break;

    // ── Supported formats ───────────────────────────────────────
    case 'formats':
        $formats = [];
        foreach ($SUPPORTED_FORMATS as $ext => $config) {
            $formats[] = [
                'extension' => $ext,
                'mime_types' => $config['mime'],
            ];
        }
        jsonResponse(['success' => true, 'formats' => $formats, 'max_file_size' => MAX_FILE_SIZE]);
        break;

    // ── Processing history ──────────────────────────────────────
    case 'history':
        $clientId = getAuthUser();
        if (!$clientId) jsonResponse(['error' => 'Authentication required'], 401);

        $db = getDB();
        ensureDocTable();

        $limit = min(intval($_GET['limit'] ?? 20), 50);
        $stmt = $db->prepare("SELECT id, filename, format, file_size, page_count, word_count, created_at
            FROM alfred_documents WHERE client_id = ?
            ORDER BY created_at DESC LIMIT ?");
        dbExecute($stmt, [$clientId, $limit]);

        jsonResponse(['success' => true, 'documents' => $stmt->fetchAll()]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action. Use: parse, ocr, summarize, extract, formats, history'], 400);
}
