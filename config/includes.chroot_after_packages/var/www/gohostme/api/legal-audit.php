<?php
/**
 * GoSiteMe Legal Compliance Audit Agent
 * Automated review of terms, privacy, app compliance
 * Reports issues to agent autonomy system
 * v1.0
 */

define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/includes/api-security.php';
require_once dirname(__DIR__) . '/api/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? 'status';
$secret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
$validSecret = '3996f0ac32cdfb8c3159b653f512efdf0dacf0582d7a75e30af4ef650c6d060d';

// ── Legal Requirements Checklist ────────────────────────────────
$complianceChecks = [
    'privacy_policy' => [
        'required_sections' => [
            'data_collection'   => ['collect', 'gather', 'information we collect', 'personal data'],
            'data_usage'        => ['use your', 'how we use', 'purpose of', 'processing'],
            'data_sharing'      => ['share', 'third party', 'disclose', 'transfer'],
            'data_retention'    => ['retain', 'retention', 'how long', 'delete', 'erasure'],
            'user_rights'       => ['your rights', 'access your', 'right to', 'opt out', 'withdraw consent'],
            'cookies'           => ['cookie', 'tracking', 'pixel', 'analytics'],
            'children'          => ['children', 'minor', 'age', 'coppa', 'under 13', 'under 16'],
            'security'          => ['security', 'protect', 'encryption', 'safeguard'],
            'contact'           => ['contact us', 'data protection officer', 'dpo', 'privacy@', 'email'],
            'updates'           => ['update', 'change', 'modify', 'revised'],
            'gdpr'              => ['gdpr', 'european', 'eu', 'data protection', 'lawful basis'],
            'ccpa'              => ['ccpa', 'california', 'do not sell', 'consumer'],
            'ai_data'           => ['artificial intelligence', 'machine learning', 'ai', 'model training'],
            'crypto_mining'     => ['mining', 'cryptocurrency', 'token', 'blockchain', 'gsm'],
            'voice_data'        => ['voice', 'audio', 'recording', 'speech'],
            'biometric'         => ['biometric', 'face', 'fingerprint', 'voice print'],
            'vr_data'           => ['virtual reality', 'vr', 'spatial', 'motion data'],
        ],
    ],
    'terms_of_service' => [
        'required_sections' => [
            'acceptance'        => ['accept', 'agree', 'by using', 'binding'],
            'eligibility'      => ['eligib', 'age', 'capacity', 'authority'],
            'user_conduct'     => ['conduct', 'prohibited', 'shall not', 'misuse'],
            'ip_rights'        => ['intellectual property', 'copyright', 'trademark', 'license'],
            'termination'      => ['terminat', 'suspend', 'cancel', 'discontinue'],
            'liability'        => ['liability', 'limitation', 'damages', 'warranty'],
            'indemnification'  => ['indemnif', 'hold harmless', 'defend'],
            'dispute'          => ['dispute', 'arbitration', 'governing law', 'jurisdiction'],
            'modifications'    => ['modif', 'change', 'update', 'amend'],
            'ai_services'      => ['ai', 'artificial intelligence', 'automated', 'agent'],
            'crypto_terms'     => ['cryptocurrency', 'token', 'mining', 'gsm', 'wallet'],
            'marketplace'      => ['marketplace', 'seller', 'buyer', 'transaction'],
            'hosting'          => ['hosting', 'server', 'uptime', 'sla'],
            'api_terms'        => ['api', 'developer', 'rate limit', 'access'],
            'vr_terms'         => ['virtual reality', 'vr', 'content', 'safety'],
        ],
    ],
];

// ── App Pages to Check ──────────────────────────────────────────
$appPages = [
    ['name' => 'Alfred AI',           'url' => '/alfred.php',          'requires' => ['privacy_link', 'terms_link']],
    ['name' => 'Veil Browser',        'url' => '/security.php',        'requires' => ['privacy_link', 'terms_link']],
    ['name' => 'Pulse Social',        'url' => '/pulse.php',           'requires' => ['privacy_link', 'terms_link', 'community_guidelines']],
    ['name' => 'Alfred IDE',        'url' => '/editor/',             'requires' => ['privacy_link', 'terms_link']],
    ['name' => 'Mining',              'url' => '/dashboard.php',       'requires' => ['privacy_link', 'terms_link', 'crypto_disclaimer']],
    ['name' => 'Marketplace',         'url' => '/marketplace.php',     'requires' => ['privacy_link', 'terms_link', 'seller_terms']],
    ['name' => 'VR Experience',       'url' => '/vr/hub/index.html',   'requires' => ['privacy_link', 'terms_link']],
    ['name' => 'Voice Services',      'url' => '/alfred-voice-live/',           'requires' => ['privacy_link', 'terms_link']],
    ['name' => 'Games',               'url' => '/games.php',           'requires' => ['privacy_link', 'terms_link']],
    ['name' => 'Developer Portal',    'url' => '/developer-portal.php','requires' => ['privacy_link', 'terms_link', 'api_terms']],
    ['name' => 'Agent Templates',     'url' => '/agent-templates.php', 'requires' => ['privacy_link', 'terms_link']],
    ['name' => 'Search Engine',       'url' => '/gocodeme.php',        'requires' => ['privacy_link']],
    ['name' => 'Voice Cloning',       'url' => '/voice-cloning.php',   'requires' => ['privacy_link', 'terms_link', 'consent_clause']],
    ['name' => 'IVR Builder',         'url' => '/ivr-builder.php',     'requires' => ['privacy_link', 'terms_link']],
    ['name' => 'Conference Room',     'url' => '/conference-room.php', 'requires' => ['privacy_link', 'terms_link']],
    ['name' => 'Investor Dashboard',  'url' => '/investor-dashboard.php','requires' => ['privacy_link', 'terms_link', 'financial_disclaimer']],
    ['name' => 'Enterprise',          'url' => '/enterprise.php',      'requires' => ['privacy_link', 'terms_link', 'sla']],
    ['name' => 'White Label',         'url' => '/white-label.php',     'requires' => ['privacy_link', 'terms_link']],
    ['name' => 'Pricing',             'url' => '/pricing.php',         'requires' => ['privacy_link', 'terms_link']],
    ['name' => 'Login',               'url' => '/login.php',           'requires' => ['privacy_link', 'terms_link']],
];

// ── Audit Functions ─────────────────────────────────────────────
function auditLegalPage(string $filename, array $requirements): array {
    $basePath = dirname(__DIR__);
    $filePath = $basePath . '/' . $filename;

    if (!file_exists($filePath)) {
        return ['exists' => false, 'issues' => ["File not found: {$filename}"], 'coverage' => []];
    }

    $content = strtolower(file_get_contents($filePath));
    $issues = [];
    $coverage = [];

    foreach ($requirements['required_sections'] as $section => $keywords) {
        $found = false;
        foreach ($keywords as $kw) {
            if (strpos($content, strtolower($kw)) !== false) {
                $found = true;
                break;
            }
        }
        $coverage[$section] = $found;
        if (!$found) {
            $issues[] = "Missing section: {$section} (expected keywords: " . implode(', ', $keywords) . ")";
        }
    }

    // Check last update date
    $hasDate = preg_match('/(?:last\s+updated|effective\s+date|revised).*?(\d{4})/i', $content, $m);
    if (!$hasDate) {
        $issues[] = "No clear 'Last Updated' date found";
    } elseif (isset($m[1]) && (int)$m[1] < (int)date('Y') - 1) {
        $issues[] = "Document may be outdated — last update year: {$m[1]}";
    }

    $totalSections = count($requirements['required_sections']);
    $coveredSections = count(array_filter($coverage));

    return [
        'exists'     => true,
        'file'       => $filename,
        'issues'     => $issues,
        'coverage'   => $coverage,
        'score'      => $totalSections > 0 ? round($coveredSections / $totalSections * 100, 1) : 0,
        'covered'    => $coveredSections,
        'total'      => $totalSections,
    ];
}

function auditAppPage(array $app): array {
    $basePath = dirname(__DIR__);
    $url = $app['url'];
    $filePath = $basePath . $url;

    // Handle directory indexes
    if (substr($url, -1) === '/') {
        $filePath .= 'index.php';
        if (!file_exists($filePath)) {
            $filePath = $basePath . $url . 'index.html';
        }
    }

    if (!file_exists($filePath)) {
        return [
            'name'   => $app['name'],
            'url'    => $url,
            'exists' => false,
            'issues' => ["Page not found: {$url}"],
            'checks' => [],
        ];
    }

    $content = strtolower(file_get_contents($filePath));

    // Also check included files (site-footer, etc.) for legal links
    if (preg_match_all('/(?:include|require)(?:_once)?\s+[^;]*[\'"]([^"\']+)[\'"]/', $content, $incMatches)) {
        foreach ($incMatches[1] as $incFile) {
            $incPath = realpath(dirname($filePath) . '/' . str_replace('__dir__', dirname($filePath), $incFile));
            if (!$incPath) {
                // Try resolving __DIR__ style paths  
                $incPath = $basePath . '/' . ltrim(preg_replace('#.*?/(includes/)#', '$1', $incFile), '/');
            }
            if ($incPath && file_exists($incPath)) {
                $content .= "\n" . strtolower(file_get_contents($incPath));
            }
        }
    }
    // Specifically check site-footer since it's included via PHP variable __DIR__
    $footerPath = $basePath . '/includes/site-footer.inc.php';
    if (file_exists($footerPath) && strpos($content, 'site-footer') !== false) {
        $content .= "\n" . strtolower(file_get_contents($footerPath));
    }

    $issues = [];
    $checks = [];

    foreach ($app['requires'] as $req) {
        switch ($req) {
            case 'privacy_link':
                $found = (strpos($content, 'privacy') !== false && preg_match('/href=["\'][^"\']*privacy/i', $content));
                $checks['privacy_link'] = $found;
                if (!$found) $issues[] = "Missing link to Privacy Policy";
                break;

            case 'terms_link':
                $found = (strpos($content, 'terms') !== false && preg_match('/href=["\'][^"\']*terms/i', $content));
                $checks['terms_link'] = $found;
                if (!$found) $issues[] = "Missing link to Terms of Service";
                break;

            case 'community_guidelines':
                $found = (strpos($content, 'community') !== false || strpos($content, 'guidelines') !== false || strpos($content, 'acceptable use') !== false);
                $checks['community_guidelines'] = $found;
                if (!$found) $issues[] = "Missing community guidelines reference";
                break;

            case 'crypto_disclaimer':
                $found = (strpos($content, 'not financial advice') !== false || strpos($content, 'risk') !== false || strpos($content, 'disclaimer') !== false || strpos($content, 'volatil') !== false);
                $checks['crypto_disclaimer'] = $found;
                if (!$found) $issues[] = "Missing cryptocurrency risk disclaimer";
                break;

            case 'seller_terms':
                $found = (strpos($content, 'seller') !== false || strpos($content, 'merchant') !== false || strpos($content, 'vendor') !== false);
                $checks['seller_terms'] = $found;
                if (!$found) $issues[] = "Missing seller/merchant terms";
                break;

            case 'api_terms':
                $found = (strpos($content, 'api') !== false && (strpos($content, 'rate limit') !== false || strpos($content, 'terms') !== false));
                $checks['api_terms'] = $found;
                if (!$found) $issues[] = "Missing API usage terms";
                break;

            case 'consent_clause':
                $found = (strpos($content, 'consent') !== false || strpos($content, 'permission') !== false || strpos($content, 'authorize') !== false);
                $checks['consent_clause'] = $found;
                if (!$found) $issues[] = "Missing explicit consent clause";
                break;

            case 'financial_disclaimer':
                $found = (strpos($content, 'not financial advice') !== false || strpos($content, 'risk') !== false || strpos($content, 'investment risk') !== false);
                $checks['financial_disclaimer'] = $found;
                if (!$found) $issues[] = "Missing financial risk disclaimer";
                break;

            case 'sla':
                $found = (strpos($content, 'sla') !== false || strpos($content, 'service level') !== false || strpos($content, 'uptime') !== false || strpos($content, '99.') !== false);
                $checks['sla'] = $found;
                if (!$found) $issues[] = "Missing SLA/uptime guarantee reference";
                break;
        }
    }

    return [
        'name'   => $app['name'],
        'url'    => $url,
        'exists' => true,
        'issues' => $issues,
        'checks' => $checks,
        'compliance_score' => count($checks) > 0 ? round(count(array_filter($checks)) / count($checks) * 100, 1) : 100,
    ];
}

function runFullAudit(): array {
    global $complianceChecks, $appPages;

    $report = [
        'audit_id'   => 'LEGAL-AUDIT-' . date('Ymd-His'),
        'auditor'    => 'Legal Compliance Agent Fleet',
        'started_at' => date('c'),
        'legal_documents' => [],
        'app_compliance'  => [],
        'summary'    => [
            'total_issues'    => 0,
            'critical_issues' => [],
            'privacy_score'   => 0,
            'terms_score'     => 0,
            'app_avg_score'   => 0,
            'overall_grade'   => '',
        ],
    ];

    // Audit privacy policy
    $privacy = auditLegalPage('privacy-policy.php', $complianceChecks['privacy_policy']);
    $report['legal_documents']['privacy_policy'] = $privacy;
    $report['summary']['privacy_score'] = $privacy['score'] ?? 0;
    $report['summary']['total_issues'] += count($privacy['issues']);

    // Audit terms of service
    $terms = auditLegalPage('terms-of-service.php', $complianceChecks['terms_of_service']);
    $report['legal_documents']['terms_of_service'] = $terms;
    $report['summary']['terms_score'] = $terms['score'] ?? 0;
    $report['summary']['total_issues'] += count($terms['issues']);

    // Audit each app page
    $appScores = [];
    foreach ($appPages as $app) {
        $result = auditAppPage($app);
        $report['app_compliance'][] = $result;
        $appScores[] = $result['compliance_score'] ?? 0;
        $report['summary']['total_issues'] += count($result['issues']);

        // Track critical issues
        foreach ($result['issues'] as $issue) {
            if (strpos($issue, 'Missing link to Privacy') !== false || strpos($issue, 'Missing link to Terms') !== false) {
                $report['summary']['critical_issues'][] = [
                    'app'   => $result['name'],
                    'issue' => $issue,
                ];
            }
        }
    }

    $report['summary']['app_avg_score'] = count($appScores) > 0
        ? round(array_sum($appScores) / count($appScores), 1) : 0;

    // Calculate overall grade
    $overall = ($report['summary']['privacy_score'] + $report['summary']['terms_score'] + $report['summary']['app_avg_score']) / 3;
    $report['summary']['overall_grade'] =
        $overall >= 90 ? 'A' :
        ($overall >= 80 ? 'B' :
        ($overall >= 70 ? 'C' :
        ($overall >= 60 ? 'D' : 'F')));
    $report['summary']['overall_score'] = round($overall, 1);

    $report['completed_at'] = date('c');
    return $report;
}

function saveAuditReport(array $report): string {
    $dir = dirname(__DIR__) . '/logs/legal-audit';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $file = $dir . '/report-' . date('Y-m-d-His') . '.json';
    file_put_contents($file, json_encode($report, JSON_PRETTY_PRINT));
    return $file;
}

function fileAutonomyReport(array $report): void {
    $s = $report['summary'];
    $payload = json_encode([
        'action'      => 'agent_report',
        'agent_id'    => 'LEGAL-AUDIT-AGENT',
        'report_type' => 'legal_compliance',
        'severity'    => $s['overall_score'] < 60 ? 'critical' : ($s['overall_score'] < 80 ? 'warning' : 'info'),
        'title'       => "Legal Compliance Audit — Grade: {$s['overall_grade']} ({$s['overall_score']}%)",
        'details'     => json_encode([
            'privacy_score'  => $s['privacy_score'],
            'terms_score'    => $s['terms_score'],
            'app_avg_score'  => $s['app_avg_score'],
            'total_issues'   => $s['total_issues'],
            'critical_count' => count($s['critical_issues']),
        ]),
        'metrics'     => json_encode([
            'overall_score' => $s['overall_score'],
            'grade'         => $s['overall_grade'],
        ]),
    ]);

    $ch = curl_init('https://gositeme.com/api/agent-autonomy.php');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Internal-Secret: 3996f0ac32cdfb8c3159b653f512efdf0dacf0582d7a75e30af4ef650c6d060d'
        ],
        CURLOPT_TIMEOUT => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// ── API Handler ─────────────────────────────────────────────────
switch ($action) {
    case 'status':
        echo json_encode([
            'success' => true,
            'agent'   => 'Legal Compliance Audit Agent',
            'version' => '1.0',
            'checks'  => [
                'privacy_sections' => count($complianceChecks['privacy_policy']['required_sections']),
                'terms_sections'   => count($complianceChecks['terms_of_service']['required_sections']),
                'app_pages'        => count($appPages),
            ],
            'status'  => 'ready',
        ]);
        break;

    case 'run':
        if (!hash_equals($validSecret, $secret)) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $report = runFullAudit();
        $file = saveAuditReport($report);
        fileAutonomyReport($report);

        echo json_encode([
            'success' => true,
            'audit'   => [
                'audit_id'     => $report['audit_id'],
                'overall_grade'=> $report['summary']['overall_grade'],
                'overall_score'=> $report['summary']['overall_score'],
                'privacy_score'=> $report['summary']['privacy_score'],
                'terms_score'  => $report['summary']['terms_score'],
                'app_avg_score'=> $report['summary']['app_avg_score'],
                'total_issues' => $report['summary']['total_issues'],
                'critical'     => $report['summary']['critical_issues'],
            ],
            'apps' => array_map(fn($a) => [
                'name'  => $a['name'],
                'score' => $a['compliance_score'],
                'issues'=> count($a['issues']),
            ], $report['app_compliance']),
            'report_file' => basename($file),
        ]);
        break;

    case 'privacy':
        $result = auditLegalPage('privacy-policy.php', $complianceChecks['privacy_policy']);
        echo json_encode(['success' => true, 'privacy_policy' => $result]);
        break;

    case 'terms':
        $result = auditLegalPage('terms-of-service.php', $complianceChecks['terms_of_service']);
        echo json_encode(['success' => true, 'terms_of_service' => $result]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action. Use: status, run, privacy, terms']);
}
