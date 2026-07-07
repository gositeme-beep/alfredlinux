<?php
/**
 * GoSiteMe Offline Contingency & Encryption Strategy API
 * Handles: Backup strategy, PGP encryption, server sync, offline mode
 * Commander requirement: Backup to $12K laptop, server-to-server sync
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

session_start();
$client_id = $_SESSION['client_id'] ?? null;
$internal = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? $_REQUEST['internal_secret'] ?? '';
$is_internal = !empty(INTERNAL_SECRET) && hash_equals(INTERNAL_SECRET, $internal);
if (!$client_id && !$is_internal) { echo json_encode(['error' => 'Auth required']); exit; }
require_once dirname(__DIR__) . '/includes/api-security.php';
if ($client_id != 33 && !$is_internal) { echo json_encode(['error' => 'Commander access only']); exit; }

$action = $_REQUEST['action'] ?? 'strategy';

switch ($action) {

case 'strategy':
    echo json_encode([
        'success' => true,
        'strategy' => [
            'classification' => 'COMMANDER EYES ONLY',
            'last_updated' => date('Y-m-d H:i:s'),

            'offline_contingency' => [
                'scenario' => 'Complete internet loss or server compromise',
                'immediate_actions' => [
                    '1. PWA Service Worker activates — cached pages remain functional offline',
                    '2. Ollama local AI (port 11434) continues functioning on server',
                    '3. Redis cache serves recent data without database',
                    '4. Local WebSocket server maintains any active local connections',
                    '5. PM2 processes auto-restart when connectivity returns'
                ],
                'pwa_offline_capability' => [
                    'sw.js already caches: index, dashboard, alfred chat, voice portal',
                    'Offline fallback page: /offline.html',
                    'IndexedDB stores recent conversations & settings locally',
                    'Service Worker intercepts failed requests gracefully'
                ],
                'recovery_procedure' => [
                    '1. Check DNS resolution — DirectAdmin > DNS Settings',
                    '2. Verify Nginx/Apache — restart via DirectAdmin',
                    '3. Check PM2 processes: pm2 status, pm2 restart all',
                    '4. Verify MySQL: mysqladmin status',
                    '5. Check Redis: redis-cli ping',
                    '6. Verify SSL: certbot certificates',
                    '7. Test API: curl https://gositeme.com/api/config.php?action=health',
                    '8. Verify all 9 PM2 services on correct ports'
                ]
            ],

            'encryption_strategy' => [
                'current_encryption' => [
                    'SSL/TLS' => 'Let\'s Encrypt certificates — auto-renewing',
                    'Passwords' => 'bcrypt hashing with cost 12',
                    'API_Auth' => 'HMAC-SHA256 via INTERNAL_SECRET',
                    'Sessions' => 'PHP sessions with SameSite=Strict',
                    'At_Rest_DB' => 'MySQL AES encryption available — not yet enabled site-wide'
                ],
                'pgp_implementation' => [
                    'recommendation' => 'YES — implement PGP for Commander communications',
                    'approach' => 'Generate GPG keypair on server for automated encryption',
                    'key_management' => [
                        'Commander public key stored on server for encrypting TO commander',
                        'Server private key for signing outbound intel/reports',
                        'Key rotation: Every 90 days recommended',
                        'Key backup: Encrypted export to commander laptop via secure transfer'
                    ],
                    'what_to_encrypt' => [
                        'Veil Vault documents (already sensitive)',
                        'Intel briefings (FLASH and URGENT priority)',
                        'Commander communications (agenda, reports)',
                        'ZPE Research data (critical research)',
                        'Crypto wallet data and transfer records',
                        'Authentication tokens and API keys'
                    ]
                ],
                'whole_site_encryption_analysis' => [
                    'question' => 'Is it too risky to encrypt the whole website?',
                    'answer' => 'Selective encryption is BETTER than whole-site',
                    'why' => [
                        'Public pages (marketing, pricing) need to be crawlable by search engines',
                        'Whole-site encryption adds latency to every request',
                        'Key loss = total data loss (catastrophic risk)',
                        'Recovery becomes impossible without decryption keys',
                        'Better approach: Encrypt sensitive data at rest, public content stays clear'
                    ],
                    'recommended_approach' => [
                        'Tier 1 — ENCRYPT: Veil data, vault, research, intel, crypto, auth tokens',
                        'Tier 2 — SIGN: API responses, agent communications, audit logs',
                        'Tier 3 — CLEAR: Public pages, marketing, blog, documentation',
                        'Database: AES-256 column-level encryption for sensitive fields'
                    ]
                ]
            ],

            'backup_strategy' => [
                'laptop_sync_plan' => [
                    'target' => '$12K Commander laptop as full backup',
                    'setup' => [
                        '1. Install rsync + SSH key authentication on laptop',
                        '2. Generate SSH keypair — add public key to server authorized_keys',
                        '3. Set up encrypted backup script with GPG',
                        '4. Schedule cron job for automated sync'
                    ],
                    'sync_command' => 'rsync -avz --delete -e "ssh -p PORT" user@15.235.50.60:/home/gositeme/domains/gositeme.com/ /backup/gositeme/ --exclude="cache" --exclude="logs"',
                    'database_backup' => 'mysqldump --single-transaction --routines --triggers DB_NAME | gpg --encrypt --recipient commander@gositeme.com > backup_$(date +%Y%m%d).sql.gpg',
                    'frequency' => 'Daily incremental, Weekly full, Monthly encrypted archive'
                ],
                'current_backups' => [
                    'DirectAdmin automated backups (server-level)',
                    'Database: Not currently backed up offsite — NEEDS ATTENTION',
                    'Files: Not currently synced offsite — NEEDS ATTENTION',
                    'Recommendation: Set up cron-based rsync to commander laptop ASAP'
                ],
                'disaster_recovery' => [
                    'scenario_1_server_loss' => [
                        'Restore from laptop backup to new server',
                        'Update DNS to new IP',
                        'Reconfigure PM2 services',
                        'Estimated recovery: 2-4 hours with backup ready'
                    ],
                    'scenario_2_data_breach' => [
                        'Rotate all API keys immediately',
                        'Regenerate INTERNAL_SECRET',
                        'Reset all user passwords',
                        'Review audit logs for scope of breach',
                        'Notify affected users'
                    ],
                    'scenario_3_ransomware' => [
                        'DO NOT pay ransom',
                        'Wipe server from DirectAdmin panel',
                        'Restore from clean laptop backup',
                        'Audit all restored files for integrity',
                        'Strengthen security post-recovery'
                    ]
                ]
            ],

            'server_to_server_sync' => [
                'current' => 'Single server at 15.235.50.60 (OVH)',
                'recommended_architecture' => [
                    'Primary: OVH server (current) — production',
                    'Secondary: Commander laptop — full backup + development',
                    'Optional: Second VPS as hot standby (if budget allows)',
                ],
                'sync_methods' => [
                    'rsync over SSH — file sync (battle-tested, reliable)',
                    'MySQL replication — database sync (master→slave)',
                    'rclone — encrypted cloud backup (to encrypted cloud storage)'
                ]
            ],

            'action_items' => [
                ['URGENT', 'Set up SSH key auth to commander laptop for rsync'],
                ['URGENT', 'Implement daily database backup with GPG encryption'],
                ['HIGH', 'Enable AES-256 encryption for Veil Vault documents at rest'],
                ['HIGH', 'Generate GPG keypair for commander communications'],
                ['MEDIUM', 'Encrypt ZPE research data at rest'],
                ['MEDIUM', 'Set up automated rsync cron job (daily at 3 AM)'],
                ['LOW', 'Consider secondary VPS as hot standby'],
                ['LOW', 'Implement PGP-signed intel briefings']
            ]
        ]
    ]);
    break;

case 'backup-status':
    // Check what backup infrastructure exists
    $checks = [];
    
    // Check if GPG is available
    $gpg = trim(shell_exec('which gpg 2>/dev/null') ?? '');
    $checks['gpg_available'] = !empty($gpg);
    
    // Check rsync
    $rsync = trim(shell_exec('which rsync 2>/dev/null') ?? '');
    $checks['rsync_available'] = !empty($rsync);
    
    // Check cron for backup jobs
    $cron = shell_exec('crontab -l 2>/dev/null') ?? '';
    $checks['backup_cron_exists'] = (strpos($cron, 'backup') !== false || strpos($cron, 'rsync') !== false);
    
    // Check SSH keys
    $ssh_keys = file_exists(getenv('HOME') . '/.ssh/authorized_keys');
    $checks['ssh_keys_configured'] = $ssh_keys;
    
    // Disk usage
    $disk_free = disk_free_space('/');
    $disk_total = disk_total_space('/');
    $checks['disk_free_gb'] = round($disk_free / 1073741824, 2);
    $checks['disk_total_gb'] = round($disk_total / 1073741824, 2);
    $checks['disk_used_pct'] = round(($disk_total - $disk_free) / $disk_total * 100, 1);
    
    echo json_encode(['success' => true, 'backup_status' => $checks]);
    break;

default:
    echo json_encode(['error' => 'Unknown action', 'actions' => ['strategy', 'backup-status']]);
}
