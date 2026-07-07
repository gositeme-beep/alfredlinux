<?php
/**
 * Ecosystem Versions API — Single source of truth for ALL GoSiteMe product versions
 * 
 * Endpoints:
 *   GET /api/ecosystem-versions.php                     — Full manifest
 *   GET /api/ecosystem-versions.php?product=alfred-linux — Single product
 *   GET /api/ecosystem-versions.php?action=check&product=alfred-browser&platform=windows&version=3.0.0
 *   GET /api/ecosystem-versions.php?action=matrix       — Platform × Product grid
 *   GET /api/ecosystem-versions.php?action=releases     — Release history (all products)
 *   GET /api/ecosystem-versions.php?action=security     — Security advisories
 * 
 * Used by: all apps (auto-update check), landing pages, docs, status dashboard
 * 
 * Commander: Danny William Perez (client_id 33)
 * Built: 2026-04-12 by Alfred
 */

define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/includes/path-guard.inc.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Alfred-Token');
header('X-Ecosystem-Version: 1.0.0');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Only GET allowed
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    if (!headers_sent()) {
        header('Cache-Control: no-store, no-cache, must-revalidate, private');
        header('Pragma: no-cache');
    }
    echo json_encode(['error' => 'Method not allowed'], gositeme_json_public_encode_flags());
    exit;
}

if (!headers_sent()) {
    header('Cache-Control: public, max-age=300');
}

$action  = strtolower(trim($_GET['action'] ?? 'manifest'));
$product = strtolower(trim($_GET['product'] ?? ''));
$platform = strtolower(trim($_GET['platform'] ?? ''));
$version = trim($_GET['version'] ?? '');

// ═══════════════════════════════════════════════════════════════
// MASTER PRODUCT REGISTRY
// ═══════════════════════════════════════════════════════════════
function getEcosystemRegistry(): array {
    return [
        // ─── PILLAR 1: Alfred Linux ───
        'alfred-linux' => [
            'name'        => 'Alfred Linux',
            'pillar'      => 'Operating System',
            'icon'        => '🐧',
            'current'     => '4.0.0',
            'codename'    => 'Genesis',
            'next'        => '7.77.0',
            'next_codename' => 'Kingdom of God',
            'status'      => 'stable',
            'released'    => '2026-04-08',
            'platforms'   => [
                'linux-amd64' => [
                    'version'      => '4.0.0',
                    'format'       => 'ISO',
                    'size'         => '2.4 GB',
                    'download'     => 'https://alfredlinux.com/downloads/alfred-linux-4.0-ga-amd64-20260408.iso',
                    'torrent'      => 'https://alfredlinux.com/downloads/alfred-linux-4.0-ga-amd64-20260408.iso.torrent',
                    'sha256'       => '7d49ef3cfb957cb9854bd3f451ef99ec8255afd68069a89ed0cf5a847d5d79bf',
                    'gpg_sig'      => 'https://alfredlinux.com/downloads/alfred-linux-4.0-ga-amd64-20260408.iso.asc',
                    'gpg_key'      => 'https://alfredlinux.com/downloads/GPG-KEY.asc',
                    'min_ram'      => '2 GB',
                    'min_disk'     => '20 GB',
                ],
            ],
            'update_channel' => 'apt',
            'auto_update'    => true,
            'changelog_url'  => 'https://alfredlinux.com/releases.php',
            'releases'       => [
                ['version'=>'4.0.0','date'=>'2026-04-08','type'=>'stable','notes'=>'GA release. Bible+Music baked in. 25 build hooks. Mesh networking. FDE. GPU compute.'],
                ['version'=>'4.0.0-rc8','date'=>'2026-04-06','type'=>'rc','notes'=>'Final release candidate. All modules verified.'],
                ['version'=>'4.0.0-rc1','date'=>'2026-03-28','type'=>'rc','notes'=>'First release candidate.'],
            ],
        ],

        // ─── PILLAR 2: Alfred Browser ───
        'alfred-browser' => [
            'name'        => 'Alfred Browser',
            'pillar'      => 'Browser',
            'icon'        => '🌐',
            'current'     => '3.0.0',
            'preview'     => '4.0.0',
            'status'      => 'stable',
            'released'    => '2026-03-08',
            'platforms'   => [
                'windows' => [
                    'version'  => '3.0.0',
                    'format'   => 'ZIP (portable)',
                    'size'     => '107.9 MiB',
                    'download' => 'https://gositeme.com/downloads/Alfred-Browser-3.0.0-win-x64.zip',
                    'torrent'  => 'https://gositeme.com/downloads/Alfred-Browser-3.0.0-win-x64.zip.torrent',
                    'sha256'   => '69705c5ad02678bbbf5d4abacc01cb50021e58093871f891ca45316c7be34ed0',
                    'min_os'   => 'Windows 10+',
                ],
                'macos-intel' => [
                    'version'  => '3.0.0',
                    'format'   => 'ZIP',
                    'size'     => '94.5 MiB',
                    'download' => 'https://gositeme.com/downloads/Alfred-Browser-3.0.0-mac-intel.zip',
                    'sha256'   => '34c922eb95b73e7335576536212fb85c07bf97d603f9f7d6ec679c4e4e1748bc',
                    'min_os'   => 'macOS 11+ (Intel)',
                ],
                'macos-arm64' => [
                    'version'  => '3.0.0',
                    'format'   => 'ZIP',
                    'size'     => '90.2 MiB',
                    'download' => 'https://gositeme.com/downloads/Alfred-Browser-3.0.0-mac-arm64.zip',
                    'sha256'   => '2d8eca80830f115d0383290707b686f84bc17da55bb20f003f61e9101ba714da',
                    'min_os'   => 'macOS 11+ (Apple Silicon)',
                ],
                'linux-appimage' => [
                    'version'  => '3.0.0',
                    'format'   => 'AppImage',
                    'size'     => '104.4 MiB',
                    'download' => 'https://gositeme.com/downloads/Alfred-Browser-3.0.0.AppImage',
                    'sha256'   => '9aa4798357266925ae8643cb1022dcd5228c97f5e8a97a95286ea6f7a0e1cc8a',
                    'min_os'   => 'Ubuntu 20.04+ / glibc 2.31+',
                ],
                'linux-deb' => [
                    'version'  => '3.0.0',
                    'format'   => 'DEB',
                    'size'     => '72.4 MiB',
                    'download' => 'https://gositeme.com/downloads/alfred-browser_3.0.0_amd64.deb',
                    'sha256'   => '1b5aedf1559af23db34aa7dd7bb2ab4d71032ddee01c65250c9f1a5c7c7acddd',
                    'min_os'   => 'Ubuntu 20.04+ / Debian 11+',
                ],
                'linux-rpm' => [
                    'version'  => '4.0.0',
                    'format'   => 'RPM',
                    'download' => 'https://gositeme.com/downloads/alfred-browser-4.0.0-x86_64.rpm',
                    'min_os'   => 'Fedora 35+ / RHEL 9+',
                ],
                'android' => [
                    'version'  => '3.0.0',
                    'format'   => 'APK',
                    'size'     => '986 KB',
                    'download' => 'https://gositeme.com/downloads/Alfred-Browser.apk',
                    'sha256'   => 'f4872c9ebed2138b2a02d2f5394f7a92dd552483c8974339ff9ffcedab39ddfa',
                    'min_os'   => 'Android 8.0+',
                ],
            ],
            'update_channel' => 'download',
            'auto_update'    => false,
            'changelog_url'  => 'https://gositeme.com/api/app-updates.php?action=changelog&app=veil-desktop',
        ],

        // ─── PILLAR 3: Alfred IDE ───
        'alfred-ide' => [
            'name'        => 'Alfred IDE',
            'pillar'      => 'Development',
            'icon'        => '⌨️',
            'current'     => '2.0.1',
            'status'      => 'stable',
            'released'    => '2026-04-07',
            'platforms'   => [
                'web' => [
                    'version'   => '2.0.1',
                    'format'    => 'Web App',
                    'download'  => 'https://gositeme.com/alfred-ide/',
                    'commander' => '5.0.0',
                    'code_server' => '4.114.1',
                ],
                'windows' => [
                    'version'   => '1.111.20260407',
                    'format'    => 'ZIP (portable)',
                    'size'      => '194 MB',
                    'download'  => 'https://gositeme.com/downloads/alfred-ide/Alfred-IDE-Windows-x64.zip',
                    'sha256'    => '3ac9b520e499a9a0258bd4943af0621b549c3b3864c89c4e816e1f4852ad042a',
                    'blake3'    => '73bd8a5e9c746730ec488c783d7a354ce7d0b24b73dae18135851c6db6b73de6',
                    'min_os'    => 'Windows 10+',
                ],
                'linux' => [
                    'version' => 'planned',
                    'status'  => 'Use web IDE or Alfred Linux v4.0',
                    'note'    => 'Linux desktop packaging planned for v7.77 cycle',
                ],
            ],
            'update_channel' => 'api',
            'auto_update'    => true,
            'api_endpoint'   => 'https://gositeme.com/api/alfred-ide-version.php',
        ],

        // ─── PILLAR 4: Veil Messenger ───
        'veil-messenger' => [
            'name'        => 'Veil Messenger',
            'pillar'      => 'Communications',
            'icon'        => '🔐',
            'current'     => '1.0.0',
            'status'      => 'stable',
            'released'    => '2026-04-07',
            'platforms'   => [
                'linux-appimage' => [
                    'version'  => '1.0.0',
                    'format'   => 'AppImage',
                    'size'     => '88 MB',
                    'download' => 'https://gositeme.com/downloads/Veil-Messenger-1.0.0.AppImage',
                    'torrent'  => 'https://gositeme.com/downloads/Veil-Messenger-1.0.0.AppImage.torrent',
                ],
                'linux-deb' => [
                    'version'  => '1.0.0',
                    'format'   => 'DEB',
                    'download' => 'https://gositeme.com/downloads/veil-messenger_1.0.0_amd64.deb',
                ],
                'linux-rpm' => [
                    'version'  => '1.0.0',
                    'format'   => 'RPM',
                    'download' => 'https://gositeme.com/downloads/veil-messenger-1.0.0-x86_64.rpm',
                ],
            ],
            'update_channel' => 'download',
            'encryption'     => 'Kyber-1024 + AES-256-GCM (post-quantum)',
        ],

        // ─── PILLAR 5: Pulse Social ───
        'pulse-social' => [
            'name'        => 'Pulse Social',
            'pillar'      => 'Social Network',
            'icon'        => '💬',
            'current'     => '1.0.0',
            'status'      => 'stable',
            'released'    => '2026-04-07',
            'platforms'   => [
                'web' => [
                    'version'  => '1.0.0',
                    'format'   => 'Web App',
                    'download' => 'https://gositeme.com/pulse.php',
                ],
                'linux-appimage' => [
                    'version'  => '1.0.0',
                    'format'   => 'AppImage',
                    'size'     => '88 MB',
                    'download' => 'https://gositeme.com/downloads/Pulse-Social-1.0.0.AppImage',
                    'torrent'  => 'https://gositeme.com/downloads/Pulse-Social-1.0.0.AppImage.torrent',
                ],
                'linux-deb' => [
                    'version'  => '1.0.0',
                    'format'   => 'DEB',
                    'download' => 'https://gositeme.com/downloads/pulse-social_1.0.0_amd64.deb',
                ],
                'linux-rpm' => [
                    'version'  => '1.0.0',
                    'format'   => 'RPM',
                    'download' => 'https://gositeme.com/downloads/pulse-social-1.0.0-x86_64.rpm',
                ],
                'android' => [
                    'version'  => '1.0.0',
                    'format'   => 'APK',
                    'download' => 'https://gositeme.com/downloads/Pulse-Social.apk',
                ],
            ],
            'update_channel' => 'download',
        ],

        // ─── PILLAR 6: MetaDome VR ───
        'metadome' => [
            'name'        => 'MetaDome VR',
            'pillar'      => 'Virtual Reality',
            'icon'        => '🌍',
            'current'     => '1.0.0',
            'status'      => 'beta',
            'released'    => '2026-03-15',
            'platforms'   => [
                'web' => [
                    'version'  => '1.0.0',
                    'format'   => 'Web App',
                    'download' => 'https://meta-dome.com/',
                ],
            ],
            'update_channel' => 'web',
        ],

        // ─── PILLAR 7: Voice AI ───
        'voice-ai' => [
            'name'        => 'Alfred Voice AI',
            'pillar'      => 'Voice Intelligence',
            'icon'        => '🎤',
            'current'     => '1.0.0',
            'status'      => 'operational',
            'released'    => '2026-03-20',
            'platforms'   => [
                'web' => [
                    'version'  => '1.0.0',
                    'format'   => 'Web Portal',
                    'download' => 'https://gositeme.com/voice-portal.php',
                ],
                'api' => [
                    'version'  => '18.0',
                    'format'   => 'REST API',
                    'endpoint' => 'https://gositeme.com/api/vapi-tools-v18.php',
                ],
            ],
            'update_channel' => 'api',
            'components' => [
                'stt' => 'Whisper (OpenAI)',
                'tts' => 'Kokoro Neural TTS',
                'llm' => 'Claude / GPT / Local (multi-provider)',
                'telephony' => 'VAPI integration',
            ],
        ],

        // ─── PILLAR 8: Alfred Mobile OS ───
        'alfred-mobile' => [
            'name'        => 'Alfred Mobile OS',
            'pillar'      => 'Mobile Platform',
            'icon'        => '📱',
            'current'     => '1.0.0-alpha',
            'status'      => 'development',
            'released'    => null,
            'platforms'   => [
                'android' => [
                    'version' => '1.0.0-alpha',
                    'format'  => 'Custom AOSP ROM',
                    'base'    => 'LineageOS 21 (Android 14)',
                    'status'  => 'Build system ready, awaiting first public release',
                    'install_script' => 'https://alfredlinux.com/downloads/install-alfred-mobile.sh',
                ],
            ],
            'update_channel' => 'ota',
            'components' => [
                'launcher'  => 'Alfred Launcher (AI-native)',
                'messaging' => 'Veil (post-quantum E2E)',
                'browser'   => 'Alfred Browser (zero-tracking)',
                'search'    => 'Alfred Search (private AI)',
                'social'    => 'Pulse Network',
                'wallet'    => 'GSM Wallet (tap-to-pay)',
                'vr'        => 'MetaDome (AR/VR)',
                'voice'     => 'Alfred Voice (system-level)',
            ],
        ],

        // ─── BONUS: Sacred Library ───
        'sacred-library' => [
            'name'        => 'Sacred Library',
            'pillar'      => 'Kingdom Heritage',
            'icon'        => '📖',
            'current'     => '1.0.0',
            'status'      => 'stable',
            'released'    => '2026-04-10',
            'platforms'   => [
                'web' => [
                    'version' => '1.0.0',
                    'format'  => 'Web + Downloads',
                    'download' => 'https://gositeme.com/bible.php',
                ],
                'alfred-linux' => [
                    'version' => '1.0.0',
                    'format'  => 'Baked into ISO',
                    'cli'     => 'alfred-bible',
                    'notes'   => '39,482 verses + 27 worship tracks',
                ],
            ],
            'contents' => [
                'akjv'            => '94 books, 39,482 verses (AKJV Perez Edition)',
                'children-bible'  => '33 stories (HTML/JSON/TXT/Illustrated)',
                'bat-mitzvah'     => 'Eden\'s Bat Mitzvah Family Edition',
                'bar-mitzvah'     => 'PENDING — Boys\' edition',
                'worship-album'   => '27 tracks (134 MB)',
                'integrity-seals' => 'SHA-256, BLAKE3, SHA-1, SHA3-256, SHAKE-256',
            ],
        ],

        // ─── Chrome Extension ───
        'alfred-extension' => [
            'name'        => 'Alfred Chrome Extension',
            'pillar'      => 'Browser Extension',
            'icon'        => '🧩',
            'current'     => '1.0.0',
            'status'      => 'stable',
            'released'    => '2026-03-15',
            'platforms'   => [
                'chromium' => [
                    'version'  => '1.0.0',
                    'format'   => 'CRX / ZIP',
                    'download' => 'https://gositeme.com/downloads/alfred-chrome-extension/',
                ],
            ],
            'update_channel' => 'download',
        ],
    ];
}

// ═══════════════════════════════════════════════════════════════
// SECURITY ADVISORIES
// ═══════════════════════════════════════════════════════════════
function getSecurityAdvisories(): array {
    return [
        // Example format — populate as needed
        // [
        //     'id'       => 'GSA-2026-001',
        //     'severity' => 'high',
        //     'product'  => 'alfred-browser',
        //     'affected' => '<= 2.2.0',
        //     'fixed'    => '3.0.0',
        //     'title'    => 'WebView navigation bypass',
        //     'date'     => '2026-03-08',
        //     'cve'      => null,
        // ],
    ];
}

// ═══════════════════════════════════════════════════════════════
// VERSION COMPARISON
// ═══════════════════════════════════════════════════════════════
function isUpdateAvailable(string $currentVersion, string $latestVersion): bool {
    return version_compare($currentVersion, $latestVersion, '<');
}

// ═══════════════════════════════════════════════════════════════
// ACTION HANDLERS
// ═══════════════════════════════════════════════════════════════
switch ($action) {
    case 'manifest':
        $registry = getEcosystemRegistry();
        if ($product && isset($registry[$product])) {
            $output = [
                'product'   => $registry[$product],
                'generated' => gmdate('c'),
                'api'       => '1.0.0',
            ];
        } elseif ($product) {
            http_response_code(404);
            $output = ['error' => "Unknown product: $product", 'available' => array_keys($registry)];
        } else {
            $output = [
                'ecosystem' => 'GoSiteMe',
                'publisher' => 'GoSiteMe Inc.',
                'commander' => 'Danny William Perez',
                'products'  => $registry,
                'total_products' => count($registry),
                'generated' => gmdate('c'),
                'api'       => '1.0.0',
            ];
        }
        break;

    case 'check':
        if (!$product || !$version) {
            http_response_code(400);
            $output = ['error' => 'Required: product and version parameters'];
            break;
        }
        $registry = getEcosystemRegistry();
        if (!isset($registry[$product])) {
            http_response_code(404);
            $output = ['error' => "Unknown product: $product", 'available' => array_keys($registry)];
            break;
        }
        $prod = $registry[$product];
        $latest = $prod['current'];
        
        // Check platform-specific version if provided
        if ($platform && isset($prod['platforms'][$platform])) {
            $latest = $prod['platforms'][$platform]['version'] ?? $latest;
        }
        
        $updateAvailable = isUpdateAvailable($version, $latest);
        $output = [
            'product'           => $product,
            'current_version'   => $version,
            'latest_version'    => $latest,
            'update_available'  => $updateAvailable,
            'update_channel'    => $prod['update_channel'] ?? 'download',
            'release_date'      => $prod['released'],
        ];
        if ($updateAvailable && $platform && isset($prod['platforms'][$platform])) {
            $output['download'] = $prod['platforms'][$platform];
        }
        break;

    case 'matrix':
        $registry = getEcosystemRegistry();
        $allPlatforms = ['windows', 'macos-intel', 'macos-arm64', 'linux-appimage', 'linux-deb', 'linux-rpm', 'android', 'web', 'api'];
        $matrix = [];
        foreach ($registry as $slug => $prod) {
            $row = ['product' => $prod['name'], 'icon' => $prod['icon'], 'current' => $prod['current']];
            foreach ($allPlatforms as $p) {
                if (isset($prod['platforms'][$p])) {
                    $row[$p] = $prod['platforms'][$p]['version'] ?? '✓';
                } else {
                    $row[$p] = null;
                }
            }
            $matrix[$slug] = $row;
        }
        $output = [
            'matrix'    => $matrix,
            'platforms' => $allPlatforms,
            'generated' => gmdate('c'),
        ];
        break;

    case 'releases':
        $registry = getEcosystemRegistry();
        $allReleases = [];
        foreach ($registry as $slug => $prod) {
            if (isset($prod['releases'])) {
                foreach ($prod['releases'] as $r) {
                    $r['product'] = $slug;
                    $r['product_name'] = $prod['name'];
                    $allReleases[] = $r;
                }
            }
        }
        // Sort by date descending
        usort($allReleases, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
        $output = [
            'releases'  => $allReleases,
            'total'     => count($allReleases),
            'generated' => gmdate('c'),
        ];
        break;

    case 'security':
        $advisories = getSecurityAdvisories();
        $output = [
            'advisories' => $advisories,
            'total'      => count($advisories),
            'generated'  => gmdate('c'),
        ];
        if ($product) {
            $output['advisories'] = array_values(array_filter($advisories, fn($a) => $a['product'] === $product));
            $output['total'] = count($output['advisories']);
        }
        break;

    case 'summary':
        $registry = getEcosystemRegistry();
        $summary = [];
        foreach ($registry as $slug => $prod) {
            $platformCount = count($prod['platforms'] ?? []);
            $summary[$slug] = [
                'name'      => $prod['name'],
                'icon'      => $prod['icon'],
                'version'   => $prod['current'],
                'status'    => $prod['status'],
                'platforms' => $platformCount,
                'released'  => $prod['released'],
            ];
        }
        $output = [
            'ecosystem' => 'GoSiteMe',
            'products'  => $summary,
            'total'     => count($summary),
            'generated' => gmdate('c'),
        ];
        break;

    default:
        http_response_code(400);
        $output = [
            'error'   => "Unknown action: $action",
            'actions' => ['manifest', 'check', 'matrix', 'releases', 'security', 'summary'],
        ];
        break;
}

echo json_encode($output, gositeme_json_public_encode_flags() | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
