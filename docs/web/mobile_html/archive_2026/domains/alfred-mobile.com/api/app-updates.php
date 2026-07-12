<?php
/**
 * App Updates API — Version checking and update distribution
 * 
 * Provides version info and update URLs for all GoSiteMe apps:
 *   - Veil Browser (Android APK)
 *   - Veil Desktop (Windows/Mac/Linux Electron)
 *   - GoCodeMe IDE (separate developer tool)
 *   - Chrome Extension
 * 
 * Endpoints:
 *   ?action=check&app=veil-android&version=3.0.0   — Check if update available
 *   ?action=latest&app=veil-desktop                 — Get latest version info
 *   ?action=manifest                                — All apps manifest
 *   ?action=download_stats                          — Download counters
 *   ?action=changelog&app=veil-android              — Version history
 *   ?action=electron_update&platform=win32          — electron-updater compatible endpoint
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Cache-Control: public, max-age=300');

$action = $_GET['action'] ?? 'manifest';

// ═══════════════════════════════════════════════════════════════
// APP REGISTRY — Single source of truth for all app versions
// ═══════════════════════════════════════════════════════════════
function getAppRegistry(): array {
    return [
        'veil-android' => [
            'name'         => 'Veil Browser for Android',
            'slug'         => 'veil-android',
            'version'      => '3.0.0',
            'build'        => 5,
            'min_os'       => 'Android 8.0+',
            'size'         => '986 KB',
            'download_url' => 'https://gositeme.com/downloads/Alfred-Browser.apk',
            'sha256'       => 'f4872c9ebed2138b2a02d2f5394f7a92dd552483c8974339ff9ffcedab39ddfa',
            'release_date' => '2026-03-08',
            'release_notes'=> 'Full WebView browser with mining bridge, pull-to-refresh, Alfred AI integration, bottom navigation.',
            'update_type'  => 'download', // APK must be downloaded manually
            'changelog'    => [
                ['version'=>'3.0.0','date'=>'2026-03-08','notes'=>'Full WebView browser replacing TWA. Mining JS bridge, file upload, camera/mic, pull-to-refresh, Alfred + Mining bottom nav.'],
                ['version'=>'2.2.0','date'=>'2026-03-01','notes'=>'TWA-based launcher, deep links, basic navigation.'],
                ['version'=>'1.0.0','date'=>'2026-02-15','notes'=>'Initial release — Chrome Custom Tab wrapper.'],
            ],
        ],
        'veil-desktop' => [
            'name'         => 'Veil Browser for Desktop',
            'slug'         => 'veil-desktop',
            'version'      => '3.0.0',
            'build'        => 3,
            'release_date' => '2026-03-08',
            'release_notes'=> 'Auto-update support, domain management sidebar, mining integration, Alfred AI panel, system tray with quick actions.',
            'update_type'  => 'electron-updater',
            'platforms'    => [
                'win32' => [
                    'url'  => 'https://gositeme.com/downloads/Alfred-Browser-3.0.0-win-x64.zip',
                    'size' => '~109 MB',
                    'min_os'=> 'Windows 10+',
                    'sha256'=> '69705c5ad02678bbbf5d4abacc01cb50021e58093871f891ca45316c7be34ed0',
                ],
                'darwin' => [
                    'url'  => 'https://gositeme.com/downloads/Alfred-Browser-3.0.0-mac-intel.zip',
                    'size' => '~95 MB',
                    'min_os'=> 'macOS 11+ (Intel x64)',
                    'sha256'=> '34c922eb95b73e7335576536212fb85c07bf97d603f9f7d6ec679c4e4e1748bc',
                ],
                'darwin-arm64' => [
                    'url'  => 'https://gositeme.com/downloads/Alfred-Browser-3.0.0-mac-arm64.zip',
                    'size' => '~91 MB',
                    'min_os'=> 'macOS 11+ (Apple Silicon M1/M2/M3/M4)',
                    'sha256'=> '2d8eca80830f115d0383290707b686f84bc17da55bb20f003f61e9101ba714da',
                ],
                'linux' => [
                    'url'  => 'https://gositeme.com/downloads/Alfred-Browser-3.0.0.AppImage',
                    'size' => '~105 MB',
                    'min_os'=> 'Ubuntu 20.04+ / Fedora 35+ / Any glibc 2.31+',
                    'sha256'=> '9aa4798357266925ae8643cb1022dcd5228c97f5e8a97a95286ea6f7a0e1cc8a',
                ],
                'linux-deb' => [
                    'url'  => 'https://gositeme.com/downloads/alfred-browser_3.0.0_amd64.deb',
                    'size' => '~73 MB',
                    'min_os'=> 'Ubuntu 20.04+ / Debian 11+',
                    'sha256'=> '1b5aedf1559af23db34aa7dd7bb2ab4d71032ddee01c65250c9f1a5c7c7acddd',
                ],
            ],
            'changelog' => [
                ['version'=>'3.0.0','date'=>'2026-03-08','notes'=>'Major upgrade: auto-update, domain sidebar, mining panel, Alfred chat drawer, new splash, system tray controls.'],
                ['version'=>'2.0.0','date'=>'2026-03-01','notes'=>'Electron wrapper with tray, splash screen, external link guard.'],
            ],
        ],
        'gocodeme-ide' => [
            'name'         => 'GoCodeMe IDE',
            'slug'         => 'gocodeme-ide',
            'version'      => '2.0.0',
            'build'        => 2,
            'release_date' => '2026-03-08',
            'release_notes'=> 'Cloud-based IDE. Access at gositeme.com/editor/ — no download needed.',
            'update_type'  => 'web',
            'platforms'    => [
                'web' => ['url'=>'https://gositeme.com/editor/','size'=>'Web App','min_os'=>'Any modern browser'],
            ],
            'changelog' => [
                ['version'=>'1.99.3','date'=>'2026-02-20','notes'=>'Initial release with AI integration, MCP tools, extension marketplace.'],
            ],
        ],
        'chrome-extension' => [
            'name'         => 'Alfred AI — Chrome Extension',
            'slug'         => 'chrome-extension',
            'version'      => '1.0.0',
            'build'        => 1,
            'release_date' => '2026-02-01',
            'release_notes'=> 'Alfred AI assistant in your browser. Quick access to search, chat, and platform tools.',
            'update_type'  => 'chrome-web-store',
            'download_url' => 'https://gositeme.com/downloads/alfred-chrome-extension/',
            'changelog'    => [
                ['version'=>'1.0.0','date'=>'2026-02-01','notes'=>'Initial release — Alfred sidebar, quick search, platform integration.'],
            ],
        ],
    ];
}

// ═══════════════════════════════════════════════════════════════
// ACTIONS
// ═══════════════════════════════════════════════════════════════

switch ($action) {

    // Check if update is available for a specific app
    case 'check':
        $appSlug = preg_replace('/[^a-z0-9\-]/', '', $_GET['app'] ?? '');
        $currentVersion = preg_replace('/[^0-9.]/', '', $_GET['version'] ?? '0.0.0');
        $platform = preg_replace('/[^a-z0-9\-]/', '', $_GET['platform'] ?? '');
        
        $registry = getAppRegistry();
        if (!isset($registry[$appSlug])) {
            http_response_code(404);
            echo json_encode(['error' => 'Unknown app']);
            exit;
        }
        
        $app = $registry[$appSlug];
        $hasUpdate = version_compare($app['version'], $currentVersion, '>');
        
        $response = [
            'app'             => $appSlug,
            'current_version' => $currentVersion,
            'latest_version'  => $app['version'],
            'update_available'=> $hasUpdate,
            'release_date'    => $app['release_date'],
            'release_notes'   => $app['release_notes'],
            'update_type'     => $app['update_type'],
        ];
        
        // Add download URL
        if ($hasUpdate) {
            if (isset($app['download_url'])) {
                $response['download_url'] = $app['download_url'];
            } elseif (isset($app['platforms'][$platform])) {
                $response['download_url'] = $app['platforms'][$platform]['url'];
            } elseif (isset($app['platforms'])) {
                $response['platforms'] = $app['platforms'];
            }
        }
        
        // Track check (no auth required)
        trackUpdateCheck($appSlug, $currentVersion, $platform);
        
        echo json_encode($response);
        break;

    // Get latest version info
    case 'latest':
        $appSlug = preg_replace('/[^a-z0-9\-]/', '', $_GET['app'] ?? '');
        $registry = getAppRegistry();
        
        if (!$appSlug) {
            echo json_encode(['error' => 'Specify ?app=veil-android|veil-desktop|gocodeme-ide|chrome-extension']);
            exit;
        }
        
        if (!isset($registry[$appSlug])) {
            http_response_code(404);
            echo json_encode(['error' => 'Unknown app']);
            exit;
        }
        
        echo json_encode($registry[$appSlug]);
        break;

    // Full manifest of all apps
    case 'manifest':
        $registry = getAppRegistry();
        $manifest = [];
        foreach ($registry as $slug => $app) {
            $manifest[$slug] = [
                'name'    => $app['name'],
                'version' => $app['version'],
                'date'    => $app['release_date'],
                'notes'   => $app['release_notes'],
            ];
        }
        echo json_encode([
            'platform'   => 'GoSiteMe',
            'generated'  => date('c'),
            'apps'       => $manifest,
        ]);
        break;

    // Changelog for a specific app
    case 'changelog':
        $appSlug = preg_replace('/[^a-z0-9\-]/', '', $_GET['app'] ?? '');
        $registry = getAppRegistry();
        if (!isset($registry[$appSlug])) {
            http_response_code(404);
            echo json_encode(['error' => 'Unknown app']);
            exit;
        }
        echo json_encode([
            'app'       => $appSlug,
            'name'      => $registry[$appSlug]['name'],
            'changelog' => $registry[$appSlug]['changelog'],
        ]);
        break;

    // Electron-updater compatible endpoint
    // electron-updater calls: GET /api/app-updates.php?action=electron_update&platform=win32
    // Must return: { "url": "...", "name": "...", "version": "..." } or 204 No Content
    case 'electron_update':
        $platform = preg_replace('/[^a-z0-9\-]/', '', $_GET['platform'] ?? 'win32');
        $currentVersion = preg_replace('/[^0-9.]/', '', $_GET['version'] ?? '0.0.0');
        
        $registry = getAppRegistry();
        $app = $registry['veil-desktop'];
        
        // Map electron platform names
        $platMap = ['win32'=>'win32','darwin'=>'darwin','linux'=>'linux'];
        $plat = $platMap[$platform] ?? 'win32';
        
        if (!isset($app['platforms'][$plat])) {
            http_response_code(204);
            exit;
        }
        
        // No update if already on latest
        if ($currentVersion && !version_compare($app['version'], $currentVersion, '>')) {
            http_response_code(204);
            exit;
        }
        
        $platInfo = $app['platforms'][$plat];
        echo json_encode([
            'url'          => $platInfo['url'],
            'name'         => 'Veil Browser v' . $app['version'],
            'version'      => $app['version'],
            'releaseDate'  => $app['release_date'],
            'releaseNotes' => $app['release_notes'],
            'sha256'       => $platInfo['sha256'] ?? '',
        ]);
        break;

    // Download statistics
    case 'download_stats':
        $db = getDB();
        $db->exec("CREATE TABLE IF NOT EXISTS app_update_checks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            app_slug VARCHAR(50) NOT NULL,
            from_version VARCHAR(20) DEFAULT '',
            platform VARCHAR(30) DEFAULT '',
            ip_hash VARCHAR(64) NOT NULL,
            checked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_app_slug (app_slug),
            INDEX idx_checked_at (checked_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $stats = [];
        $registry = getAppRegistry();
        foreach (array_keys($registry) as $slug) {
            $stmt = $db->prepare("SELECT COUNT(DISTINCT ip_hash) as unique_checks, COUNT(*) as total_checks 
                                  FROM app_update_checks WHERE app_slug = ?");
            $stmt->execute([$slug]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats[$slug] = [
                'unique_devices' => (int)($row['unique_checks'] ?? 0),
                'total_checks'   => (int)($row['total_checks'] ?? 0),
            ];
        }
        echo json_encode(['stats' => $stats]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action', 'actions' => ['check','latest','manifest','changelog','electron_update','download_stats']]);
}

// ═══════════════════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════════════════

function trackUpdateCheck(string $appSlug, string $version, string $platform): void {
    try {
        $db = getDB();
        $db->exec("CREATE TABLE IF NOT EXISTS app_update_checks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            app_slug VARCHAR(50) NOT NULL,
            from_version VARCHAR(20) DEFAULT '',
            platform VARCHAR(30) DEFAULT '',
            ip_hash VARCHAR(64) NOT NULL,
            checked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_app_slug (app_slug),
            INDEX idx_checked_at (checked_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $ipHash = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0') . date('Y-m-d'));
        $stmt = $db->prepare("INSERT INTO app_update_checks (app_slug, from_version, platform, ip_hash) VALUES (?, ?, ?, ?)");
        $stmt->execute([$appSlug, $version, $platform, $ipHash]);
    } catch (Exception $e) {
        // Non-critical — don't break the update check
    }
}
