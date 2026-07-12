<?php
/**
 * GoSiteMe Self-Governance Engine
 * Advisory panel & monitoring fleet auto-manage the ecosystem
 * Posts problems, proposes solutions, executes approved fixes
 * v1.0
 */

define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? 'status';
$secret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
$validSecret = '3996f0ac32cdfb8c3159b653f512efdf0dacf0582d7a75e30af4ef650c6d060d';

// ── Advisory Panel Members ──────────────────────────────────────
$advisoryPanel = [
    'SAGE'     => ['role' => 'Chief Strategist',     'domain' => 'strategy',    'weight' => 1.0],
    'SENTINEL' => ['role' => 'Security Director',    'domain' => 'security',    'weight' => 1.0],
    'ATLAS'    => ['role' => 'Infrastructure Lead',  'domain' => 'infrastructure', 'weight' => 1.0],
    'NOVA'     => ['role' => 'Innovation Director',  'domain' => 'innovation',  'weight' => 0.9],
    'CIPHER'   => ['role' => 'Privacy Guardian',     'domain' => 'privacy',     'weight' => 1.0],
];

// ── Governance Rules ────────────────────────────────────────────
$governanceRules = [
    'auto_approve_threshold'   => 0.8,   // 80% panel agreement = auto-approve
    'escalate_threshold'       => 0.5,   // < 50% = escalate to human
    'max_auto_actions_per_hour'=> 10,    // Safety circuit breaker
    'severity_weights' => [
        'critical' => 3.0,
        'warning'  => 2.0,
        'info'     => 1.0,
    ],
    'auto_fixable_categories' => [
        'performance',      // Can restart services, clear caches
        'health_check',     // Can re-check endpoints
        'search_quality',   // Can trigger re-index
        'content_moderation', // Can flag/hide content
    ],
    'human_required_categories' => [
        'legal',            // Legal changes need human review
        'financial',        // Money moves need human approval
        'data_deletion',    // User data operations
        'security_breach',  // Security incidents
    ],
];

// ── Problem Detection Engine ────────────────────────────────────
function detectProblems(): array {
    $problems = [];

    // 1. Check monitoring fleet for issues
    $monitoringUrl = 'https://gositeme.com/api/monitoring-fleet.php?action=status';
    $ch = curl_init($monitoringUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['X-Internal-Secret: 3996f0ac32cdfb8c3159b653f512efdf0dacf0582d7a75e30af4ef650c6d060d'],
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $monData = json_decode($response, true);

    if (!empty($monData['divisions'])) {
        foreach ($monData['divisions'] as $div) {
            if (isset($div['health'])) {
                $healthyPct = ($div['health']['healthy'] ?? 0) / max(($div['health']['total'] ?? 1), 1) * 100;
                if ($healthyPct < 90) {
                    $problems[] = [
                        'id'       => 'MON-' . strtoupper($div['name'] ?? 'UNKNOWN'),
                        'type'     => 'health_check',
                        'severity' => $healthyPct < 50 ? 'critical' : 'warning',
                        'title'    => "Division '{$div['name']}' health at {$healthyPct}%",
                        'details'  => "Degraded agents: " . ($div['health']['degraded'] ?? 0) . ", Down: " . ($div['health']['down'] ?? 0),
                        'source'   => 'monitoring_fleet',
                        'auto_fix' => 'restart_health_checks',
                    ];
                }
            }
        }
    }

    // 2. Check search quality (latest report)
    $searchReportDir = dirname(__DIR__) . '/logs/search-quality';
    if (is_dir($searchReportDir)) {
        $files = glob($searchReportDir . '/report-*.json');
        if (!empty($files)) {
            rsort($files);
            $latestReport = json_decode(file_get_contents($files[0]), true);
            if ($latestReport && isset($latestReport['summary']['avg_score'])) {
                if ($latestReport['summary']['avg_score'] < 60) {
                    $problems[] = [
                        'id'       => 'SEARCH-QUALITY',
                        'type'     => 'search_quality',
                        'severity' => $latestReport['summary']['avg_score'] < 40 ? 'critical' : 'warning',
                        'title'    => "Search quality score: {$latestReport['summary']['avg_score']}%",
                        'details'  => "Failed: {$latestReport['summary']['failed']} tests, Critical issues: " . count($latestReport['summary']['critical_issues'] ?? []),
                        'source'   => 'search_fleet',
                        'auto_fix' => 'trigger_reindex',
                    ];
                }
            }
        }
    }

    // 3. Check legal compliance (latest report)
    $legalReportDir = dirname(__DIR__) . '/logs/legal-audit';
    if (is_dir($legalReportDir)) {
        $files = glob($legalReportDir . '/report-*.json');
        if (!empty($files)) {
            rsort($files);
            $latestAudit = json_decode(file_get_contents($files[0]), true);
            if ($latestAudit && isset($latestAudit['summary']['overall_score'])) {
                if ($latestAudit['summary']['overall_score'] < 80) {
                    $problems[] = [
                        'id'       => 'LEGAL-COMPLIANCE',
                        'type'     => 'legal',
                        'severity' => $latestAudit['summary']['overall_score'] < 50 ? 'critical' : 'warning',
                        'title'    => "Legal compliance: Grade {$latestAudit['summary']['overall_grade']} ({$latestAudit['summary']['overall_score']}%)",
                        'details'  => "Issues found: {$latestAudit['summary']['total_issues']}, Critical: " . count($latestAudit['summary']['critical_issues'] ?? []),
                        'source'   => 'legal_audit',
                        'auto_fix' => null, // Requires human
                    ];
                }
            }
        }
    }

    // 4. Check PM2 services (may be unavailable in web context)
    $shellAvailable = function_exists('shell_exec') && !in_array('shell_exec', array_map('trim', explode(',', ini_get('disable_functions') ?: '')));
    if ($shellAvailable) {
        $pm2Bin = '/home/gositeme/.local/node_modules/.bin/pm2';
        if (!is_executable($pm2Bin)) $pm2Bin = trim(@shell_exec('which pm2 2>/dev/null') ?: 'pm2');
        $pm2Output = @shell_exec($pm2Bin . ' jlist 2>/dev/null');
        if ($pm2Output) {
            $pm2Data = json_decode($pm2Output, true);
            if (is_array($pm2Data)) {
                foreach ($pm2Data as $proc) {
                    $status = $proc['pm2_env']['status'] ?? 'unknown';
                    $name = $proc['name'] ?? 'unknown';
                    $safeName = preg_replace('/[^A-Z0-9]/i', '-', $name);
                    if ($status !== 'online') {
                        $problems[] = [
                            'id'       => 'PM2-' . strtoupper($safeName),
                            'type'     => 'performance',
                            'severity' => 'critical',
                            'title'    => "PM2 service '{$name}' is {$status}",
                            'details'  => "Process ID: " . ($proc['pm_id'] ?? 'N/A'),
                            'source'   => 'pm2_monitor',
                            'auto_fix' => 'pm2_restart_' . $name,
                        ];
                    }
                    $restarts = $proc['pm2_env']['restart_time'] ?? 0;
                    if ($restarts > 10) {
                        $problems[] = [
                            'id'       => 'PM2-UNSTABLE-' . strtoupper($safeName),
                            'type'     => 'performance',
                            'severity' => 'warning',
                            'title'    => "PM2 service '{$name}' has restarted {$restarts} times",
                            'details'  => "High restart count indicates instability",
                            'source'   => 'pm2_monitor',
                            'auto_fix' => null,
                        ];
                    }
                }
            }
        }
    }

    // 5. Check disk space
    $diskFree = @disk_free_space('/home');
    $diskTotal = @disk_total_space('/home');
    if ($diskFree && $diskTotal && $diskTotal > 0) {
        $usedPct = round((1 - $diskFree / $diskTotal) * 100, 1);
        if ($usedPct > 85) {
            $problems[] = [
                'id'       => 'DISK-SPACE',
                'type'     => 'infrastructure',
                'severity' => $usedPct > 95 ? 'critical' : 'warning',
                'title'    => "Disk usage at {$usedPct}%",
                'details'  => "Free: " . round($diskFree / 1073741824, 1) . " GB",
                'source'   => 'system_monitor',
                'auto_fix' => 'clear_temp_files',
            ];
        }
    }

    // 6. Check error logs
    $errorLog = dirname(__DIR__) . '/logs/errors.log';
    if (file_exists($errorLog)) {
        $logSize = @filesize($errorLog);
        if ($logSize && $logSize > 10485760) {
            $problems[] = [
                'id'       => 'LOG-SIZE',
                'type'     => 'performance',
                'severity' => 'info',
                'title'    => "Error log is " . round($logSize / 1048576, 1) . " MB",
                'details'  => "Large error logs may indicate ongoing issues",
                'source'   => 'system_monitor',
                'auto_fix' => 'rotate_logs',
            ];
        }
    }

    return $problems;
}

// ── Solution Generator ──────────────────────────────────────────
function generateSolution(array $problem): array {
    $solutions = [
        'restart_health_checks' => [
            'action'      => 'Trigger monitoring fleet re-check',
            'steps'       => ['Call monitoring-fleet.php?action=check', 'Verify results improve'],
            'risk'        => 'low',
            'auto_execute'=> true,
        ],
        'trigger_reindex' => [
            'action'      => 'Trigger MeiliSearch re-index for low-quality results',
            'steps'       => ['Update MeiliSearch index settings', 'Reprocess quality_score'],
            'risk'        => 'low',
            'auto_execute'=> true,
        ],
        'clear_temp_files' => [
            'action'      => 'Remove temporary and cache files',
            'steps'       => ['Clear /cache/ directory', 'Clear old logs > 30 days'],
            'risk'        => 'low',
            'auto_execute'=> true,
        ],
        'rotate_logs' => [
            'action'      => 'Rotate error logs',
            'steps'       => ['Archive current log', 'Create new empty log'],
            'risk'        => 'low',
            'auto_execute'=> true,
        ],
    ];

    foreach (['pm2_restart_'] as $prefix) {
        if ($problem['auto_fix'] && strpos($problem['auto_fix'], $prefix) === 0) {
            $service = str_replace($prefix, '', $problem['auto_fix']);
            $solutions[$problem['auto_fix']] = [
                'action'      => "Restart PM2 service: {$service}",
                'steps'       => ["Run: pm2 restart {$service}", 'Verify status returns online'],
                'risk'        => 'medium',
                'auto_execute'=> true,
            ];
        }
    }

    $fix = $problem['auto_fix'] ?? null;
    if ($fix && isset($solutions[$fix])) {
        return $solutions[$fix];
    }

    return [
        'action'      => 'Escalate to human administrator',
        'steps'       => ['File autonomy report', 'Await human decision'],
        'risk'        => 'none',
        'auto_execute'=> false,
    ];
}

// ── Execute Auto-Fix ────────────────────────────────────────────
function executeAutoFix(string $fixType): array {
    switch ($fixType) {
        case 'restart_health_checks':
            $ch = curl_init('https://gositeme.com/api/monitoring-fleet.php?action=check');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['X-Internal-Secret: 3996f0ac32cdfb8c3159b653f512efdf0dacf0582d7a75e30af4ef650c6d060d'],
                CURLOPT_TIMEOUT => 30,
            ]);
            $result = curl_exec($ch);
            curl_close($ch);
            return ['executed' => true, 'result' => 'Health checks triggered', 'response' => substr($result, 0, 200)];

        case 'clear_temp_files':
            $cleared = 0;
            $cacheDir = dirname(__DIR__) . '/cache';
            if (is_dir($cacheDir)) {
                $files = glob($cacheDir . '/*');
                foreach ($files as $f) {
                    if (is_file($f) && (time() - filemtime($f)) > 86400) {
                        unlink($f);
                        $cleared++;
                    }
                }
            }
            return ['executed' => true, 'result' => "Cleared {$cleared} cache files"];

        case 'rotate_logs':
            $logFile = dirname(__DIR__) . '/logs/errors.log';
            if (file_exists($logFile) && filesize($logFile) > 5242880) {
                $archiveName = dirname(__DIR__) . '/logs/errors-' . date('Y-m-d-His') . '.log.gz';
                $content = file_get_contents($logFile);
                file_put_contents($archiveName, gzencode($content, 9));
                file_put_contents($logFile, '');
                return ['executed' => true, 'result' => 'Log rotated and archived'];
            }
            return ['executed' => false, 'result' => 'Log rotation not needed'];

        default:
            if (strpos($fixType, 'pm2_restart_') === 0) {
                $service = basename(str_replace('pm2_restart_', '', $fixType));
                $pm2Bin = '/home/gositeme/.local/node_modules/.bin/pm2';
                $output = @shell_exec($pm2Bin . ' restart ' . escapeshellarg($service) . ' 2>&1');
                return ['executed' => true, 'result' => "Restarted {$service}", 'output' => $output];
            }
            return ['executed' => false, 'result' => 'Unknown fix type'];
    }
}

// ── Advisory Panel Voting ───────────────────────────────────────
function advisoryVote(array $problem, array $solution): array {
    global $advisoryPanel;
    $votes = [];
    $totalWeight = 0;
    $approveWeight = 0;

    foreach ($advisoryPanel as $name => $member) {
        $vote = true; // Default approve
        $reason = '';

        // Each advisor evaluates based on their domain
        switch ($member['domain']) {
            case 'security':
                if (in_array($problem['type'], ['security_breach', 'data_deletion'])) {
                    $vote = false;
                    $reason = 'Security-sensitive — requires human oversight';
                } elseif ($solution['risk'] === 'high') {
                    $vote = false;
                    $reason = 'High-risk action needs manual verification';
                } else {
                    $reason = 'Security assessment: acceptable risk';
                }
                break;

            case 'privacy':
                if (in_array($problem['type'], ['legal', 'data_deletion'])) {
                    $vote = false;
                    $reason = 'Privacy-sensitive — requires DPO review';
                } else {
                    $reason = 'No privacy implications detected';
                }
                break;

            case 'infrastructure':
                if (in_array($problem['type'], ['performance', 'health_check', 'infrastructure'])) {
                    $vote = true;
                    $reason = 'Infrastructure issue — safe to auto-fix';
                } else {
                    $reason = 'Not infrastructure-related, deferring';
                }
                break;

            case 'strategy':
                $vote = ($problem['severity'] !== 'info'); // Don't auto-fix trivial issues
                $reason = $vote ? 'Strategic priority — address promptly' : 'Low priority, can wait';
                break;

            case 'innovation':
                $vote = true;
                $reason = 'Supports system resilience and self-improvement';
                break;
        }

        $votes[$name] = [
            'vote'   => $vote ? 'approve' : 'reject',
            'reason' => $reason,
            'weight' => $member['weight'],
        ];

        $totalWeight += $member['weight'];
        if ($vote) $approveWeight += $member['weight'];
    }

    $approvalRate = $totalWeight > 0 ? $approveWeight / $totalWeight : 0;

    return [
        'votes'         => $votes,
        'approval_rate' => round($approvalRate, 2),
        'decision'      => $approvalRate >= 0.8 ? 'approved' : ($approvalRate >= 0.5 ? 'review' : 'rejected'),
    ];
}

// ── Governance Cycle ────────────────────────────────────────────
function runGovernanceCycle(): array {
    global $governanceRules;

    $cycle = [
        'cycle_id'   => 'GOV-' . date('Ymd-His'),
        'started_at' => date('c'),
        'problems'   => [],
        'actions'    => [],
        'summary'    => [
            'problems_found' => 0,
            'auto_fixed'     => 0,
            'escalated'      => 0,
            'deferred'       => 0,
        ],
    ];

    $problems = detectProblems();
    $cycle['summary']['problems_found'] = count($problems);
    $autoActions = 0;

    foreach ($problems as $problem) {
        $solution = generateSolution($problem);
        $entry = [
            'problem'  => $problem,
            'solution' => $solution,
            'voting'   => null,
            'action_taken' => null,
        ];

        // Only auto-execute if: solution supports it AND not human-required category
        if ($solution['auto_execute'] && !in_array($problem['type'], $governanceRules['human_required_categories'])) {
            $voting = advisoryVote($problem, $solution);
            $entry['voting'] = $voting;

            if ($voting['decision'] === 'approved' && $autoActions < $governanceRules['max_auto_actions_per_hour']) {
                $result = executeAutoFix($problem['auto_fix']);
                $entry['action_taken'] = $result;
                $cycle['summary']['auto_fixed']++;
                $autoActions++;
            } elseif ($voting['decision'] === 'review') {
                $entry['action_taken'] = ['escalated' => true, 'reason' => 'Advisory panel split — needs review'];
                $cycle['summary']['escalated']++;
            } else {
                $entry['action_taken'] = ['deferred' => true, 'reason' => 'Advisory panel rejected auto-fix'];
                $cycle['summary']['deferred']++;
            }
        } else {
            $entry['action_taken'] = ['escalated' => true, 'reason' => $solution['auto_execute'] ? 'Category requires human approval' : 'No auto-fix available'];
            $cycle['summary']['escalated']++;
        }

        $cycle['problems'][] = $entry;
    }

    $cycle['completed_at'] = date('c');

    // Save cycle report
    $dir = dirname(__DIR__) . '/logs/governance';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($dir . '/cycle-' . date('Y-m-d-His') . '.json', json_encode($cycle, JSON_PRETTY_PRINT));

    // File autonomy report
    $payload = json_encode([
        'action'      => 'agent_report',
        'agent_id'    => 'SELF-GOVERNANCE',
        'report_type' => 'governance_cycle',
        'severity'    => $cycle['summary']['problems_found'] > 3 ? 'warning' : 'info',
        'title'       => "Governance Cycle: {$cycle['summary']['problems_found']} problems, {$cycle['summary']['auto_fixed']} auto-fixed",
        'details'     => json_encode($cycle['summary']),
    ]);

    $ch = curl_init('https://gositeme.com/api/agent-autonomy.php');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Internal-Secret: 3996f0ac32cdfb8c3159b653f512efdf0dacf0582d7a75e30af4ef650c6d060d',
        ],
        CURLOPT_TIMEOUT => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);

    return $cycle;
}

// ── API Handler ─────────────────────────────────────────────────
switch ($action) {
    case 'status':
        echo json_encode([
            'success'  => true,
            'system'   => 'Self-Governance Engine',
            'version'  => '1.0',
            'panel'    => array_map(fn($n, $m) => ['name' => $n, 'role' => $m['role']], array_keys($advisoryPanel), $advisoryPanel),
            'rules'    => [
                'auto_approve_threshold' => $governanceRules['auto_approve_threshold'],
                'max_auto_actions'       => $governanceRules['max_auto_actions_per_hour'],
                'auto_fixable'           => $governanceRules['auto_fixable_categories'],
                'human_required'         => $governanceRules['human_required_categories'],
            ],
            'status'   => 'ready',
        ]);
        break;

    case 'scan':
        if (!hash_equals($validSecret, $secret)) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        $problems = detectProblems();
        echo json_encode([
            'success'  => true,
            'problems' => $problems,
            'count'    => count($problems),
        ]);
        break;

    case 'cycle':
        if (!hash_equals($validSecret, $secret)) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        set_time_limit(120);
        $cycle = runGovernanceCycle();
        echo json_encode([
            'success' => true,
            'cycle'   => [
                'cycle_id'       => $cycle['cycle_id'],
                'problems_found' => $cycle['summary']['problems_found'],
                'auto_fixed'     => $cycle['summary']['auto_fixed'],
                'escalated'      => $cycle['summary']['escalated'],
                'deferred'       => $cycle['summary']['deferred'],
            ],
            'problems' => array_map(fn($p) => [
                'id'       => $p['problem']['id'],
                'title'    => $p['problem']['title'],
                'severity' => $p['problem']['severity'],
                'decision' => $p['voting']['decision'] ?? 'escalated',
                'fixed'    => isset($p['action_taken']['executed']) && $p['action_taken']['executed'],
            ], $cycle['problems']),
        ]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action. Use: status, scan, cycle']);
}
