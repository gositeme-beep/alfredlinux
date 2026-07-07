<?php
/**
 * Fleet Scanner API — Bug Detection Fleet Command Center
 * ══════════════════════════════════════════════════════════
 * Manages 25,000 agent scan tasks, collects bug reports,
 * and provides real-time aggregated results.
 *
 * Actions:
 *   create_scan      — Initialize a new fleet scan session
 *   generate_tasks   — Generate task manifest (file × scan_type matrix)
 *   claim_task       — Agent claims next available task
 *   report_bug       — Agent files a bug report
 *   complete_task    — Agent marks task done
 *   scan_status      — Dashboard: scan progress & stats
 *   bug_summary      — Aggregated bug report (by severity, type, file)
 *   list_bugs        — Paginated bug list with filters
 *   bulk_report      — Agent submits multiple bugs at once
 *   scout_report     — 33 scout agents submit their analysis
 */

session_start();

// Auth: require logged-in user with owner privileges
if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Owner-only: clientId must be 33
$clientId = (int)$_SESSION['client_id'];
if ($clientId !== 33) {
    http_response_code(403);
    echo json_encode(['error' => 'Owner access required']);
    exit;
}

require_once dirname(__DIR__) . '/includes/db-config.inc.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$db = getSharedDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ══════════════════════════════════════════════════════════════
    // CREATE SCAN — Initialize a new fleet scan session
    // ══════════════════════════════════════════════════════════════
    case 'create_scan':
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $scanId = 'SCAN-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4));
        $totalTasks = (int)($input['total_tasks'] ?? 0);
        $metadata = json_encode($input['metadata'] ?? []);

        $stmt = $db->prepare("INSERT INTO agent_fleet_scans (scan_id, total_tasks, status, metadata) VALUES (?, ?, 'preparing', ?)");
        $stmt->execute([$scanId, $totalTasks, $metadata]);

        echo json_encode(['success' => true, 'scan_id' => $scanId]);
        break;

    // ══════════════════════════════════════════════════════════════
    // GENERATE TASKS — Build the full task manifest
    // ══════════════════════════════════════════════════════════════
    case 'generate_tasks':
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $scanId = $input['scan_id'] ?? '';
        if (!$scanId) { echo json_encode(['error' => 'scan_id required']); break; }

        $tasks = $input['tasks'] ?? [];
        if (empty($tasks)) { echo json_encode(['error' => 'tasks array required']); break; }

        $inserted = 0;
        $stmt = $db->prepare("INSERT IGNORE INTO agent_fleet_tasks (scan_id, task_id, file_path, scan_type, batch_number) VALUES (?, ?, ?, ?, ?)");

        $db->beginTransaction();
        try {
            foreach ($tasks as $task) {
                $stmt->execute([
                    $scanId,
                    $task['task_id'],
                    $task['file_path'],
                    $task['scan_type'],
                    (int)($task['batch'] ?? 0)
                ]);
                $inserted++;
            }

            // Update scan total
            $db->prepare("UPDATE agent_fleet_scans SET total_tasks = ?, status = 'running' WHERE scan_id = ?")->execute([$inserted, $scanId]);
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => 'Failed: ' . $e->getMessage()]);
            break;
        }

        echo json_encode(['success' => true, 'inserted' => $inserted]);
        break;

    // ══════════════════════════════════════════════════════════════
    // CLAIM TASK — Agent picks up next available task
    // ══════════════════════════════════════════════════════════════
    case 'claim_task':
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $scanId = $input['scan_id'] ?? '';
        $agentId = $input['agent_id'] ?? 'agent-' . bin2hex(random_bytes(4));

        $stmt = $db->prepare("SELECT id, task_id, file_path, scan_type FROM agent_fleet_tasks WHERE scan_id = ? AND status = 'pending' ORDER BY batch_number ASC, id ASC LIMIT 1 FOR UPDATE");
        
        $db->beginTransaction();
        $stmt->execute([$scanId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            $db->commit();
            echo json_encode(['success' => false, 'message' => 'No tasks available']);
            break;
        }

        $db->prepare("UPDATE agent_fleet_tasks SET status = 'running', agent_session_id = ?, started_at = NOW() WHERE id = ?")->execute([$agentId, $task['id']]);
        $db->commit();

        echo json_encode(['success' => true, 'task' => $task]);
        break;

    // ══════════════════════════════════════════════════════════════
    // REPORT BUG — Single bug report from an agent
    // ══════════════════════════════════════════════════════════════
    case 'report_bug':
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $required = ['scan_id', 'task_id', 'file_path', 'scan_type', 'severity', 'bug_category', 'title'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                echo json_encode(['error' => "$field is required"]);
                exit;
            }
        }

        $validSeverities = ['critical', 'high', 'medium', 'low', 'info'];
        $severity = in_array($input['severity'], $validSeverities) ? $input['severity'] : 'medium';

        $validTypes = ['security', 'logic', 'performance', 'frontend', 'accessibility', 'deprecated', 'error_handling', 'data_validation'];
        $scanType = in_array($input['scan_type'], $validTypes) ? $input['scan_type'] : 'logic';

        $stmt = $db->prepare("INSERT INTO agent_bug_reports (scan_id, task_id, file_path, scan_type, severity, bug_category, title, description, line_number, code_snippet, suggested_fix, agent_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['scan_id'],
            $input['task_id'],
            $input['file_path'],
            $scanType,
            $severity,
            $input['bug_category'],
            $input['title'],
            $input['description'] ?? null,
            $input['line_number'] ?? null,
            $input['code_snippet'] ?? null,
            $input['suggested_fix'] ?? null,
            $input['agent_id'] ?? null
        ]);

        echo json_encode(['success' => true, 'bug_id' => $db->lastInsertId()]);
        break;

    // ══════════════════════════════════════════════════════════════
    // BULK REPORT — Agent submits multiple bugs at once
    // ══════════════════════════════════════════════════════════════
    case 'bulk_report':
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $bugs = $input['bugs'] ?? [];
        if (empty($bugs)) { echo json_encode(['error' => 'bugs array required']); break; }

        $stmt = $db->prepare("INSERT INTO agent_bug_reports (scan_id, task_id, file_path, scan_type, severity, bug_category, title, description, line_number, code_snippet, suggested_fix, agent_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $inserted = 0;
        $db->beginTransaction();
        try {
            foreach ($bugs as $bug) {
                $validSeverities = ['critical', 'high', 'medium', 'low', 'info'];
                $severity = in_array($bug['severity'] ?? '', $validSeverities) ? $bug['severity'] : 'medium';
                $validTypes = ['security', 'logic', 'performance', 'frontend', 'accessibility', 'deprecated', 'error_handling', 'data_validation'];
                $scanType = in_array($bug['scan_type'] ?? '', $validTypes) ? $bug['scan_type'] : 'logic';

                $stmt->execute([
                    $bug['scan_id'] ?? $input['scan_id'] ?? '',
                    $bug['task_id'] ?? '',
                    $bug['file_path'] ?? '',
                    $scanType,
                    $severity,
                    $bug['bug_category'] ?? 'unknown',
                    $bug['title'] ?? 'Untitled bug',
                    $bug['description'] ?? null,
                    $bug['line_number'] ?? null,
                    $bug['code_snippet'] ?? null,
                    $bug['suggested_fix'] ?? null,
                    $bug['agent_id'] ?? null
                ]);
                $inserted++;
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => 'Bulk insert failed: ' . $e->getMessage()]);
            break;
        }

        echo json_encode(['success' => true, 'inserted' => $inserted]);
        break;

    // ══════════════════════════════════════════════════════════════
    // COMPLETE TASK — Agent marks its task done
    // ══════════════════════════════════════════════════════════════
    case 'complete_task':
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $taskId = $input['task_id'] ?? '';
        $bugsFound = (int)($input['bugs_found'] ?? 0);
        $summary = $input['summary'] ?? '';

        $db->prepare("UPDATE agent_fleet_tasks SET status = 'completed', bugs_found = ?, result_summary = ?, completed_at = NOW() WHERE task_id = ?")->execute([$bugsFound, $summary, $taskId]);

        // Update scan counters
        $scanId = $input['scan_id'] ?? '';
        if ($scanId) {
            $db->prepare("UPDATE agent_fleet_scans SET completed_tasks = completed_tasks + 1, total_bugs_found = total_bugs_found + ? WHERE scan_id = ?")->execute([$bugsFound, $scanId]);

            // Update severity counters
            if ($bugsFound > 0) {
                $severityCounts = $input['severity_counts'] ?? [];
                if (!empty($severityCounts)) {
                    $db->prepare("UPDATE agent_fleet_scans SET critical_bugs = critical_bugs + ?, high_bugs = high_bugs + ?, medium_bugs = medium_bugs + ?, low_bugs = low_bugs + ? WHERE scan_id = ?")
                        ->execute([
                            (int)($severityCounts['critical'] ?? 0),
                            (int)($severityCounts['high'] ?? 0),
                            (int)($severityCounts['medium'] ?? 0),
                            (int)($severityCounts['low'] ?? 0),
                            $scanId
                        ]);
                }
            }

            // Check if all tasks complete
            $row = $db->prepare("SELECT total_tasks, completed_tasks FROM agent_fleet_scans WHERE scan_id = ?")->execute([$scanId]);
            $scan = $db->prepare("SELECT total_tasks, completed_tasks FROM agent_fleet_scans WHERE scan_id = ?");
            $scan->execute([$scanId]);
            $scanData = $scan->fetch(PDO::FETCH_ASSOC);
            if ($scanData && $scanData['completed_tasks'] >= $scanData['total_tasks']) {
                $db->prepare("UPDATE agent_fleet_scans SET status = 'completed', completed_at = NOW() WHERE scan_id = ?")->execute([$scanId]);
            }
        }

        echo json_encode(['success' => true]);
        break;

    // ══════════════════════════════════════════════════════════════
    // SCAN STATUS — Real-time dashboard data
    // ══════════════════════════════════════════════════════════════
    case 'scan_status':
        $scanId = $_GET['scan_id'] ?? '';

        if ($scanId) {
            $stmt = $db->prepare("SELECT * FROM agent_fleet_scans WHERE scan_id = ?");
            $stmt->execute([$scanId]);
            $scan = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get task status breakdown
            $taskStats = $db->prepare("SELECT status, COUNT(*) as cnt FROM agent_fleet_tasks WHERE scan_id = ? GROUP BY status");
            $taskStats->execute([$scanId]);
            $taskBreakdown = $taskStats->fetchAll(PDO::FETCH_KEY_PAIR);

            // Get bug type breakdown
            $bugStats = $db->prepare("SELECT scan_type, severity, COUNT(*) as cnt FROM agent_bug_reports WHERE scan_id = ? GROUP BY scan_type, severity ORDER BY FIELD(severity, 'critical','high','medium','low','info')");
            $bugStats->execute([$scanId]);
            $bugBreakdown = $bugStats->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'scan' => $scan,
                'task_breakdown' => $taskBreakdown,
                'bug_breakdown' => $bugBreakdown,
                'progress_pct' => $scan ? round(($scan['completed_tasks'] / max($scan['total_tasks'], 1)) * 100, 1) : 0
            ]);
        } else {
            // List all scans
            $scans = $db->query("SELECT * FROM agent_fleet_scans ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'scans' => $scans]);
        }
        break;

    // ══════════════════════════════════════════════════════════════
    // BUG SUMMARY — Aggregated bug report
    // ══════════════════════════════════════════════════════════════
    case 'bug_summary':
        $scanId = $_GET['scan_id'] ?? '';
        $where = $scanId ? "WHERE scan_id = ?" : "";
        $params = $scanId ? [$scanId] : [];

        // By severity
        $stmt = $db->prepare("SELECT severity, COUNT(*) as cnt FROM agent_bug_reports $where GROUP BY severity ORDER BY FIELD(severity, 'critical','high','medium','low','info')");
        dbExecute($stmt, $params);
        $bySeverity = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // By scan type
        $stmt = $db->prepare("SELECT scan_type, COUNT(*) as cnt FROM agent_bug_reports $where GROUP BY scan_type ORDER BY cnt DESC");
        dbExecute($stmt, $params);
        $byType = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // By category
        $stmt = $db->prepare("SELECT bug_category, COUNT(*) as cnt FROM agent_bug_reports $where GROUP BY bug_category ORDER BY cnt DESC LIMIT 20");
        dbExecute($stmt, $params);
        $byCategory = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Most buggy files
        $stmt = $db->prepare("SELECT file_path, COUNT(*) as cnt, SUM(severity = 'critical') as crits, SUM(severity = 'high') as highs FROM agent_bug_reports $where GROUP BY file_path ORDER BY crits DESC, highs DESC, cnt DESC LIMIT 30");
        dbExecute($stmt, $params);
        $hotFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Total
        $stmt = $db->prepare("SELECT COUNT(*) FROM agent_bug_reports $where");
        dbExecute($stmt, $params);
        $total = $stmt->fetchColumn();

        echo json_encode([
            'success' => true,
            'total_bugs' => (int)$total,
            'by_severity' => $bySeverity,
            'by_type' => $byType,
            'by_category' => $byCategory,
            'hot_files' => $hotFiles
        ]);
        break;

    // ══════════════════════════════════════════════════════════════
    // LIST BUGS — Paginated bug list with filters
    // ══════════════════════════════════════════════════════════════
    case 'list_bugs':
        $scanId = $_GET['scan_id'] ?? '';
        $severity = $_GET['severity'] ?? '';
        $scanType = $_GET['scan_type'] ?? '';
        $status = $_GET['status'] ?? '';
        $filePath = $_GET['file'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(10, (int)($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if ($scanId) { $where[] = "scan_id = ?"; $params[] = $scanId; }
        if ($severity) { $where[] = "severity = ?"; $params[] = $severity; }
        if ($scanType) { $where[] = "scan_type = ?"; $params[] = $scanType; }
        if ($status) { $where[] = "status = ?"; $params[] = $status; }
        if ($filePath) { $where[] = "file_path LIKE ?"; $params[] = "%$filePath%"; }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $countStmt = $db->prepare("SELECT COUNT(*) FROM agent_bug_reports $whereSQL");
        dbExecute($countStmt, $params);
        $total = $countStmt->fetchColumn();

        $params[] = $limit;
        $params[] = $offset;
        $stmt = $db->prepare("SELECT * FROM agent_bug_reports $whereSQL ORDER BY FIELD(severity, 'critical','high','medium','low','info'), id DESC LIMIT ? OFFSET ?");
        dbExecute($stmt, $params);
        $bugs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'bugs' => $bugs,
            'total' => (int)$total,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ]);
        break;

    // ══════════════════════════════════════════════════════════════
    // SCOUT REPORT — The 33 scout agents submit their findings
    // ══════════════════════════════════════════════════════════════
    case 'scout_report':
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $scanId = $input['scan_id'] ?? '';
        $scoutId = (int)($input['scout_id'] ?? 0);
        $findings = $input['findings'] ?? [];
        $filesCovered = $input['files_covered'] ?? [];

        // Insert all scout findings as bug reports
        $stmt = $db->prepare("INSERT INTO agent_bug_reports (scan_id, task_id, file_path, scan_type, severity, bug_category, title, description, line_number, code_snippet, suggested_fix, agent_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $inserted = 0;
        $db->beginTransaction();
        try {
            foreach ($findings as $bug) {
                $validSeverities = ['critical', 'high', 'medium', 'low', 'info'];
                $severity = in_array($bug['severity'] ?? '', $validSeverities) ? $bug['severity'] : 'medium';
                $validTypes = ['security', 'logic', 'performance', 'frontend', 'accessibility', 'deprecated', 'error_handling', 'data_validation'];
                $scanType = in_array($bug['scan_type'] ?? '', $validTypes) ? $bug['scan_type'] : 'logic';

                $stmt->execute([
                    $scanId,
                    'SCOUT-' . str_pad($scoutId, 3, '0', STR_PAD_LEFT),
                    $bug['file_path'] ?? '',
                    $scanType,
                    $severity,
                    $bug['bug_category'] ?? 'scout_finding',
                    $bug['title'] ?? 'Scout finding',
                    $bug['description'] ?? null,
                    $bug['line_number'] ?? null,
                    $bug['code_snippet'] ?? null,
                    $bug['suggested_fix'] ?? null,
                    $scoutId
                ]);
                $inserted++;
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => 'Scout report failed: ' . $e->getMessage()]);
            break;
        }

        echo json_encode(['success' => true, 'inserted' => $inserted, 'scout_id' => $scoutId]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action. Available: create_scan, generate_tasks, claim_task, report_bug, bulk_report, complete_task, scan_status, bug_summary, list_bugs, scout_report']);
        break;
}
