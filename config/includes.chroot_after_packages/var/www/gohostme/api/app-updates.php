<?php
/**
 * App Updates API — Version checking and update distribution
 * 
 * Provides version info and update URLs for all GoSiteMe apps:
 *   - Veil Browser (Android APK)
 *   - Veil Desktop (Windows/Mac/Linux Electron)
 *   - Alfred IDE (separate developer tool)
 *   - Chrome Extension
 * 
 * Endpoints:
 *   ?action=check&app=veil-android&version=3.0.0   — Check if update available
 *   ?action=latest&app=veil-desktop                 — Get latest version info
 *   ?action=manifest                                — All apps manifest
 *   ?action=download_stats                          — Download counters
 *   ?action=changelog&app=veil-android              — Version history
 *   ?action=electron_update&platform=win32          — electron-updater compatible endpoint
 *   ?action=tauri_update&target=x86_64-pc-windows-msvc — tauri updater manifest endpoint
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
            'name'         => 'Alfred Browser for Desktop',
            'slug'         => 'veil-desktop',
            'version'      => '3.0.0',
            'build'        => 5,
            'release_date' => '2026-03-08',
            'release_notes'=> 'Current public stable release for Alfred Browser desktop. Cross-platform stable packages remain on 3.0.0, and Linux testers can now download 4.0.0 preview bundles from the public browser page while Windows and macOS 4.0 packages remain unpublished.',
            'update_type'  => 'desktop-release',
            'platforms'    => [
                'win32' => [
                    'url'  => 'https://gositeme.com/downloads/Alfred-Browser-3.0.0-win-x64.zip',
                    'size' => '107.9 MiB',
                    'min_os'=> 'Windows 10+',
                    'sha256'=> '69705c5ad02678bbbf5d4abacc01cb50021e58093871f891ca45316c7be34ed0',
                    'signature'=> '',
                ],
                'darwin' => [
                    'url'  => 'https://gositeme.com/downloads/Alfred-Browser-3.0.0-mac-intel.zip',
                    'size' => '94.5 MiB',
                    'min_os'=> 'macOS 11+ (Intel x64)',
                    'sha256'=> '34c922eb95b73e7335576536212fb85c07bf97d603f9f7d6ec679c4e4e1748bc',
                    'signature'=> '',
                ],
                'darwin-arm64' => [
                    'url'  => 'https://gositeme.com/downloads/Alfred-Browser-3.0.0-mac-arm64.zip',
                    'size' => '90.2 MiB',
                    'min_os'=> 'macOS 11+ (Apple Silicon M1/M2/M3/M4)',
                    'sha256'=> '2d8eca80830f115d0383290707b686f84bc17da55bb20f003f61e9101ba714da',
                    'signature'=> '',
                ],
                'linux' => [
                    'url'  => 'https://gositeme.com/downloads/Alfred-Browser-3.0.0.AppImage',
                    'size' => '104.4 MiB',
                    'min_os'=> 'Ubuntu 20.04+ / Fedora 35+ / Any glibc 2.31+',
                    'sha256'=> '9aa4798357266925ae8643cb1022dcd5228c97f5e8a97a95286ea6f7a0e1cc8a',
                    'signature'=> '',
                ],
                'linux-deb' => [
                    'url'  => 'https://gositeme.com/downloads/alfred-browser_3.0.0_amd64.deb',
                    'size' => '72.4 MiB',
                    'min_os'=> 'Ubuntu 20.04+ / Debian 11+',
                    'sha256'=> '1b5aedf1559af23db34aa7dd7bb2ab4d71032ddee01c65250c9f1a5c7c7acddd',
                    'signature'=> '',
                ],
            ],
            'tauri_targets' => [
                'x86_64-pc-windows-msvc' => 'win32',
                'x86_64-apple-darwin' => 'darwin',
                'aarch64-apple-darwin' => 'darwin-arm64',
                'x86_64-unknown-linux-gnu' => 'linux',
            ],
            'preview' => [
                'version'      => '4.0.0',
                'build'        => 1,
                'release_date' => '2026-04-02',
                'release_notes'=> 'First public Linux preview of Alfred Browser 4.0.0 built from the Tauri sovereign-browser work. Package integrity is verified on the live server; broader runtime smoke testing is still in progress before any stable promotion.',
                'update_type'  => 'preview-release',
                'platforms'    => [
                    'linux' => [
                        'url'  => 'https://gositeme.com/downloads/Alfred-Browser-4.0.0.AppImage',
                        'size' => '89.2 MiB',
                        'min_os'=> 'Ubuntu 22.04+ / Fedora 38+ / Any glibc 2.35+ host with AppImage support',
                        'sha256'=> 'd50158df0a53d6695cb1a13a5e398c05ca978acc28ccc6478fec717eab6adf0d',
                        'signature'=> '',
                    ],
                    'linux-deb' => [
                        'url'  => 'https://gositeme.com/downloads/alfred-browser_4.0.0_amd64.deb',
                        'size' => '4.5 MiB',
                        'min_os'=> 'Ubuntu 22.04+ / Debian 12+',
                        'sha256'=> '8f88949579f0074ae1dfb12b10788009cd7fb0444a1b5292571bba8ac07875e4',
                        'signature'=> '',
                    ],
                    'linux-rpm' => [
                        'url'  => 'https://gositeme.com/downloads/alfred-browser-4.0.0-x86_64.rpm',
                        'size' => '4.5 MiB',
                        'min_os'=> 'Fedora 38+ / RHEL 9+ / openSUSE Tumbleweed x86_64',
                        'sha256'=> '6e33ee7d2f80f4ffe753b0986fcf4b1d1409035f15ffe904848c2e289b1e50ba',
                        'signature'=> '',
                    ],
                ],
            ],
            'changelog' => [
                ['version'=>'3.0.0','date'=>'2026-03-08','notes'=>'Major upgrade: auto-update, domain sidebar, mining panel, Alfred chat drawer, new splash, system tray controls.'],
                ['version'=>'2.0.0','date'=>'2026-03-01','notes'=>'Electron wrapper with tray, splash screen, external link guard.'],
            ],
        ],
        'gocodeme-ide' => [
            'name'         => 'Alfred IDE',
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

function getRequestedReleaseChannel(): string {
    return strtolower($_GET['channel'] ?? '') === 'preview' ? 'preview' : 'stable';
}

function resolveReleaseTrack(array $app, string $channel, string $platform = ''): array {
    $release = $app;
    $servedChannel = 'stable';

    if ($channel === 'preview' && isset($app['preview']) && is_array($app['preview'])) {
        $preview = $app['preview'];
        if ($platform === '' || isset($preview['platforms'][$platform])) {
            $release = array_merge($app, $preview);
            $servedChannel = 'preview';
        }
    }

    $release['channel_requested'] = $channel;
    $release['channel_served'] = $servedChannel;
    $release['stable_version'] = $app['version'];
    $release['stable_release_date'] = $app['release_date'];
    $release['stable_release_notes'] = $app['release_notes'];

    return $release;
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
        $requestedChannel = getRequestedReleaseChannel();
        
        $registry = getAppRegistry();
        if (!isset($registry[$appSlug])) {
            http_response_code(404);
            echo json_encode(['error' => 'Unknown app']);
            exit;
        }
        
        $app = $registry[$appSlug];
        $release = resolveReleaseTrack($app, $requestedChannel, $platform);
        $hasUpdate = version_compare($release['version'], $currentVersion, '>');
        
        $response = [
            'app'             => $appSlug,
            'current_version' => $currentVersion,
            'latest_version'  => $release['version'],
            'update_available'=> $hasUpdate,
            'release_date'    => $release['release_date'],
            'release_notes'   => $release['release_notes'],
            'update_type'     => $release['update_type'],
            'channel_requested' => $requestedChannel,
            'channel_served'  => $release['channel_served'],
        ];
        
        // Add download URL
        if ($hasUpdate) {
            if (isset($release['download_url'])) {
                $response['download_url'] = $release['download_url'];
            } elseif (isset($release['platforms'][$platform])) {
                $response['download_url'] = $release['platforms'][$platform]['url'];
            } elseif (isset($release['platforms'])) {
                $response['platforms'] = $release['platforms'];
            }
        }
        
        // Track check (no auth required)
        trackUpdateCheck($appSlug, $currentVersion, $platform);
        
        echo json_encode($response);
        break;

    // Get latest version info
    case 'latest':
        $appSlug = preg_replace('/[^a-z0-9\-]/', '', $_GET['app'] ?? '');
        $requestedChannel = getRequestedReleaseChannel();
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
        
        echo json_encode(resolveReleaseTrack($registry[$appSlug], $requestedChannel));
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
            if (isset($app['preview'])) {
                $manifest[$slug]['preview'] = [
                    'version' => $app['preview']['version'],
                    'date' => $app['preview']['release_date'],
                    'notes' => $app['preview']['release_notes'],
                ];
            }
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
        $requestedChannel = getRequestedReleaseChannel();
        
        $registry = getAppRegistry();
        $app = $registry['veil-desktop'];
        
        // Map electron platform names
        $platMap = ['win32'=>'win32','darwin'=>'darwin','linux'=>'linux'];
        $plat = $platMap[$platform] ?? 'win32';
        $release = resolveReleaseTrack($app, $requestedChannel, $plat);
        
        if (!isset($release['platforms'][$plat])) {
            http_response_code(204);
            exit;
        }
        
        // No update if already on latest
        if ($currentVersion && !version_compare($release['version'], $currentVersion, '>')) {
            http_response_code(204);
            exit;
        }
        
        $platInfo = $release['platforms'][$plat];
        echo json_encode([
            'url'          => $platInfo['url'],
            'name'         => 'Veil Browser v' . $release['version'],
            'version'      => $release['version'],
            'releaseDate'  => $release['release_date'],
            'releaseNotes' => $release['release_notes'],
            'sha256'       => $platInfo['sha256'] ?? '',
            'channel'      => $release['channel_served'],
        ]);
        break;

    // Tauri updater manifest endpoint
    // tauri-plugin-updater calls: /api/app-updates.php?action=tauri_update&target=<triple>&arch=<arch>&current_version=<version>
    case 'tauri_update':
        $target = preg_replace('/[^a-z0-9_\-\.]/', '', $_GET['target'] ?? 'x86_64-pc-windows-msvc');
        $currentVersion = preg_replace('/[^0-9.]/', '', $_GET['current_version'] ?? ($_GET['version'] ?? '0.0.0'));
        $requestedChannel = getRequestedReleaseChannel();

        $registry = getAppRegistry();
        $app = $registry['veil-desktop'];
        $platformKey = $app['tauri_targets'][$target] ?? null;
        $release = resolveReleaseTrack($app, $requestedChannel, $platformKey ?? '');

        if (!$platformKey || !isset($release['platforms'][$platformKey])) {
            http_response_code(204);
            exit;
        }

        if ($currentVersion && !version_compare($release['version'], $currentVersion, '>')) {
            http_response_code(204);
            exit;
        }

        $platform = $release['platforms'][$platformKey];
        echo json_encode([
            'version' => $release['version'],
            'notes' => $release['release_notes'],
            'pub_date' => date(DATE_ATOM, strtotime($release['release_date'] . ' 00:00:00 UTC')),
            'channel' => $release['channel_served'],
            'platforms' => [
                $target => [
                    'url' => $platform['url'],
                    'signature' => $platform['signature'] ?? '',
                ],
            ],
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
        echo json_encode(['error' => 'Unknown action', 'actions' => ['check','latest','manifest','changelog','electron_update','tauri_update','download_stats']]);
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
