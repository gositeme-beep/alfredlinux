<?php
/**
 * Alfred IDE — Version Manifest
 * Public version/changelog endpoint for desktop auto-updaters and the website.
 *
 * GET /api/alfred-ide-version.php
 * GET /api/alfred-ide-version.php?platform=windows
 * GET /api/alfred-ide-version.php?platform=web
 * GET /api/alfred-ide-version.php?platform=linux
 */

require_once dirname(__DIR__) . '/includes/path-guard.inc.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    if (!headers_sent()) {
        header('Cache-Control: no-store, no-cache, must-revalidate, private');
        header('Pragma: no-cache');
    }
    echo json_encode(['error' => 'Method not allowed'], gositeme_json_public_encode_flags());
    exit;
}

$platform = strtolower(trim($_GET['platform'] ?? 'all'));

$jsonFlags = gositeme_json_public_encode_flags() | JSON_PRETTY_PRINT;

// --- Current release versions ---
$releases = [
    'web' => [
        'platform'    => 'web',
        'version'     => '2.1.2',
        'codeServer'  => '4.115.0',
        'vscode'      => '1.115.0',
        'commander'   => '2.1.0',
        'releaseDate' => '2026-04-23',
        'status'      => 'stable',
        'launchUrl'   => 'https://gositeme.com/alfred-ide/',
        'changelog'   => 'v2.1.2 (2026-04-23): GitHub Copilot extension removed from the bundled IDE. Alfred-Commander is the integrated AI assistant; usage is metered and billed per user via the Alfred IDE billing system. v2.1.1: code-server 4.115.0 (VS Code 1.115.0) runtime upgrade; auto-save delay tightened to 1.5s.',
    ],
    'windows' => [
        'platform'     => 'windows',
        'version'      => '1.111.20260423',
        'commander'    => '2.1.0',
        'releaseDate'  => '2026-04-23',
        'status'       => 'stable',
        'downloadUrl'  => 'https://gositeme.com/downloads/alfred-ide/Alfred-IDE-Windows-x64.zip',
        'downloadSize' => '195 MB',
        'blake3'       => 'd0948aaaebd04fbe3770595285e030d7c0eb7db76495a508f511312cf8bfd0bd',
        'sha256'       => 'db3a2b6a255f5429a933add1719a39d2ce175c9476040ad6f22cbfdb19db3dc1',
        'changelog'    => 'v1.111.20260423: GitHub Copilot extension removed from the Windows build. Use Alfred-Commander for AI assistance. Previous: Commander with update-check + pair-desktop tools, full rebrand, telemetry disabled, bloat removed.',
    ],
    'linux' => [
        'platform'     => 'linux',
        'version'      => '2.1.2',
        'commander'    => '2.1.0',
        'releaseDate'  => '2026-04-23',
        'status'       => 'stable',
        'downloadUrl'  => 'https://gositeme.com/downloads/alfred-ide/alfred-ide-2.1.2-linux-amd64.deb',
        'downloadSize' => '80 MB',
        'blake3'       => '21d71d7684a8b0cf11820a0b83446c0fb34f4d4e4ea3a9666e709c50451cbf9b',
        'sha256'       => '28297831e0a183e2344d4d9395db7ba8effde9e84265cc6a29515961bd136091',
        'changelog'    => 'v2.1.2 (2026-04-23): First published Linux .deb. GitHub Copilot extension removed; Alfred-Commander is the integrated AI assistant. For an Alfred-branded full OS see Alfred Linux v4.0 live ISO (separate product).',
    ],
];

if ($platform === 'all') {
    if (!headers_sent()) {
        header('Cache-Control: public, max-age=300');
    }
    echo json_encode([
        'product'  => 'Alfred IDE',
        'publisher' => 'GoSiteMe',
        'releases' => $releases,
        'updated'  => '2026-04-23T13:10:00Z',
    ], $jsonFlags);
} elseif (isset($releases[$platform])) {
    if (!headers_sent()) {
        header('Cache-Control: public, max-age=300');
    }
    echo json_encode([
        'product' => 'Alfred IDE',
        'release' => $releases[$platform],
    ], $jsonFlags);
} else {
    http_response_code(404);
    if (!headers_sent()) {
        header('Cache-Control: no-store, no-cache, must-revalidate, private');
        header('Pragma: no-cache');
    }
    echo json_encode(['error' => 'Unknown platform. Use: web, windows, linux, or all'], gositeme_json_public_encode_flags());
}
