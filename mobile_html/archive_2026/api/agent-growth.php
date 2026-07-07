<?php
/**
 * GoSiteMe Agent Growth Controller
 * Manages wave-based growth from 100 → 500 → 2000 → 5000 → 10000 → 50000
 * Each wave requires owner approval via Agenda panel
 * 
 * Usage:
 *   api/agent-growth.php?action=status (check current wave)
 *   api/agent-growth.php?action=propose-next-wave (submit proposal for next wave)
 *   api/agent-growth.php?action=approve-wave&wave=2 (owner approves wave)
 *   api/agent-growth.php?action=execute-wave&wave=2 (generate agents for approved wave)
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$is_cli = php_sapi_name() === 'cli';
if (!$is_cli) {
    session_start();
    $client_id = $_SESSION['client_id'] ?? null;
    $internal = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? $_REQUEST['internal_secret'] ?? '';
    $is_internal = !empty(INTERNAL_SECRET) && hash_equals(INTERNAL_SECRET, $internal);
    $is_owner = ($client_id == 33);
    if (!$is_owner && !$is_internal) { jsonResponse(['error' => 'Owner access required'], 403); }
require_once dirname(__DIR__) . '/includes/api-security.php';
}

$pdo = getDB();
if (!$pdo) jsonResponse(['error' => 'Database unavailable'], 500);

// ── Ecosystem Control Gate ────────────────────────────────────
try {
    $eco_check = $pdo->query("SELECT `key`, `value` FROM ecosystem_control WHERE `key` IN ('muted','growth_locked','blackout_active')")->fetchAll(PDO::FETCH_KEY_PAIR);
    if (!empty($eco_check)) {
        $eco_locked = ($eco_check['growth_locked'] ?? '0') === '1';
        $eco_blackout = ($eco_check['blackout_active'] ?? '0') === '1';
        if ($eco_locked || $eco_blackout) {
            $reason = $eco_blackout ? 'blackout active' : 'growth locked';
            jsonResponse(['status' => 'blocked', 'reason' => "Growth blocked: ecosystem {$reason}"], 403);
        }
    }
} catch (PDOException $e) { /* table may not exist yet */ }

// Ensure tables
$pdo->exec("CREATE TABLE IF NOT EXISTS `agent_growth_waves` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `wave` INT UNIQUE NOT NULL,
    `target_count` INT NOT NULL,
    `status` ENUM('planned','proposed','approved','executing','completed','rejected') DEFAULT 'planned',
    `proposed_at` DATETIME DEFAULT NULL,
    `approved_at` DATETIME DEFAULT NULL,
    `approved_by` INT DEFAULT NULL,
    `executed_at` DATETIME DEFAULT NULL,
    `agents_created` INT DEFAULT 0,
    `performance_metrics` JSON DEFAULT NULL,
    `rejection_reason` TEXT DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Seed wave plan
$pdo->exec("INSERT IGNORE INTO agent_growth_waves (wave, target_count, status) VALUES
    (1, 100, 'completed'),
    (2, 500, 'planned'),
    (3, 2000, 'planned'),
    (4, 5000, 'planned'),
    (5, 10000, 'planned'),
    (6, 50000, 'planned')
");

// Mark wave 1 as completed if we have 100+ agents
$current_count = (int)$pdo->query("SELECT COUNT(*) FROM agent_profiles WHERE status = 'active'")->fetchColumn();
if ($current_count >= 100) {
    $pdo->exec("UPDATE agent_growth_waves SET status = 'completed', agents_created = $current_count, executed_at = NOW() WHERE wave = 1 AND status != 'completed'");
}

$action = $_REQUEST['action'] ?? 'status';

// Department templates for wave generation
$dept_roles = [
    'engineering' => ['Backend Developer', 'Frontend Developer', 'DevOps Engineer', 'ML Engineer', 'Mobile Developer', 'QA Engineer', 'Data Engineer', 'Platform Engineer', 'SRE', 'Security Engineer'],
    'design' => ['Product Designer', 'Visual Designer', 'UX Researcher', 'Motion Designer', 'Brand Designer', 'Illustrator', 'Design System Lead', 'AR/VR Designer'],
    'analytics' => ['Data Analyst', 'Business Analyst', 'Marketing Analyst', 'Product Analyst', 'Risk Analyst', 'Growth Analyst', 'Revenue Analyst', 'Performance Analyst'],
    'security' => ['Threat Analyst', 'Pen Tester', 'SOC Analyst', 'GRC Specialist', 'Crypto Engineer', 'Forensic Analyst', 'AppSec Engineer', 'Cloud Security'],
    'marketing' => ['Content Marketer', 'Growth Marketer', 'Brand Strategist', 'Social Media Manager', 'SEO Specialist', 'Email Marketer', 'Product Marketer', 'Event Manager'],
    'support' => ['Support Agent', 'Tech Support', 'Account Manager', 'Success Manager', 'Training Specialist', 'Docs Writer', 'Community Manager', 'Onboarding Specialist'],
    'finance' => ['Financial Analyst', 'Accountant', 'Treasury Analyst', 'Billing Specialist', 'Revenue Ops', 'Crypto Economist', 'Audit Analyst', 'FP&A Analyst'],
    'legal' => ['Contract Lawyer', 'IP Attorney', 'Privacy Counsel', 'Compliance Officer', 'Regulatory Analyst', 'Ethics Officer', 'Licensing Specialist'],
    'research' => ['Research Scientist', 'AI Researcher', 'NLP Scientist', 'CV Researcher', 'RL Specialist', 'Robotics Researcher', 'Quantum Researcher', 'Bio Researcher'],
    'operations' => ['Ops Manager', 'SRE Lead', 'Deployment Engineer', 'Capacity Planner', 'Automation Engineer', 'Process Analyst', 'Reliability Engineer', 'Logistics Planner'],
    'hr' => ['HR Specialist', 'Recruiter', 'L&D Specialist', 'Culture Manager', 'Compensation Analyst', 'DEI Coordinator', 'Engagement Manager'],
    'infrastructure' => ['Infra Engineer', 'Network Engineer', 'Storage Engineer', 'Linux Admin', 'Cloud Architect', 'Edge Engineer', 'DNS Specialist', 'Cache Engineer'],
];

$name_prefixes = ['Agent', 'Dr.', 'Prof.', 'Mx.', 'Chief', 'Lead', 'Senior', 'Principal', 'Staff', 'Expert'];
$name_parts1 = ['Alpha', 'Beta', 'Gamma', 'Delta', 'Epsilon', 'Zeta', 'Eta', 'Theta', 'Iota', 'Kappa', 'Lambda', 'Mu', 'Nu', 'Xi', 'Omicron', 'Pi', 'Rho', 'Sigma', 'Tau', 'Upsilon', 'Phi', 'Chi', 'Psi', 'Omega',
    'Astra', 'Blitz', 'Crux', 'Dex', 'Ember', 'Flux', 'Glyph', 'Hexa', 'Ion', 'Jade', 'Kori', 'Lux', 'Mira', 'Nyx', 'Onyx', 'Prism', 'Quasar', 'Rune', 'Sable', 'Thorn', 'Umbra', 'Vex', 'Wren', 'Xeno', 'Yara', 'Zen',
    'Arc', 'Bolt', 'Core', 'Drift', 'Echo', 'Fern', 'Grid', 'Haze', 'Ivory', 'Jolt', 'Keen', 'Link', 'Moss', 'Nova', 'Opal', 'Peak', 'Quirk', 'Reed', 'Spark', 'Tide', 'Ultra', 'Volt', 'Wave', 'Apex', 'Byte', 'Cyan'];

$personality_traits = ['innovative', 'analytical', 'creative', 'methodical', 'visionary', 'pragmatic', 'collaborative', 'independent', 'detail-oriented', 'big-picture'];
$personality_tones = ['professional', 'friendly', 'technical', 'casual', 'inspiring', 'precise', 'warm', 'authoritative', 'enthusiastic', 'measured'];

switch ($action) {

case 'status':
    $waves = $pdo->query("SELECT * FROM agent_growth_waves ORDER BY wave")->fetchAll(PDO::FETCH_ASSOC);
    $current_wave = null;
    foreach ($waves as $w) {
        if ($w['status'] !== 'completed') { $current_wave = $w; break; }
    }
    
    jsonResponse([
        'success' => true,
        'current_agents' => $current_count,
        'target' => 50000,
        'progress_pct' => round(($current_count / 50000) * 100, 2),
        'waves' => $waves,
        'current_wave' => $current_wave,
        'next_action' => $current_wave ? 
            ($current_wave['status'] === 'planned' ? 'propose-next-wave' : 
            ($current_wave['status'] === 'proposed' ? 'Awaiting owner approval in Agenda panel' : 
            ($current_wave['status'] === 'approved' ? 'execute-wave' : $current_wave['status']))) 
            : 'All waves complete!',
    ]);
    break;

case 'propose-next-wave':
    // Find next unproposed wave
    $next = $pdo->query("SELECT * FROM agent_growth_waves WHERE status = 'planned' ORDER BY wave LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$next) { jsonResponse(['error' => 'No waves available to propose. All waves are in progress or completed.']); }
    
    $wave_num = $next['wave'];
    $target = $next['target_count'];
    $agents_to_create = $target - $current_count;
    
    // Collect performance data from current ecosystem
    $post_count = (int)$pdo->query("SELECT COUNT(*) FROM pulse_posts WHERE post_type = 'agent_activity'")->fetchColumn();
    $like_count = (int)$pdo->query("SELECT COUNT(*) FROM pulse_likes")->fetchColumn();
    $comment_count = (int)$pdo->query("SELECT COUNT(*) FROM pulse_comments")->fetchColumn();
    $follow_count = (int)$pdo->query("SELECT COUNT(*) FROM pulse_follows")->fetchColumn();
    
    $metrics = [
        'current_agents' => $current_count,
        'target_agents' => $target,
        'agents_to_create' => $agents_to_create,
        'pulse_posts' => $post_count,
        'pulse_likes' => $like_count,
        'pulse_comments' => $comment_count,
        'pulse_follows' => $follow_count,
        'avg_engagement_per_agent' => $current_count > 0 ? round(($like_count + $comment_count) / $current_count, 1) : 0,
    ];
    
    // Update wave status
    $pdo->prepare("UPDATE agent_growth_waves SET status = 'proposed', proposed_at = NOW(), performance_metrics = ? WHERE wave = ?")
        ->execute([json_encode($metrics), $wave_num]);
    
    // Create agenda item for owner approval
    $pdo->exec("CREATE TABLE IF NOT EXISTS `agenda_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `item_type` ENUM('task','alert','milestone','approval','note') DEFAULT 'task',
        `source` VARCHAR(100) DEFAULT NULL,
        `source_type` ENUM('system','agent','human','automated') DEFAULT 'system',
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `priority` ENUM('low','medium','high','urgent') DEFAULT 'medium',
        `status` ENUM('pending','in_progress','completed','dismissed') DEFAULT 'pending',
        `assigned_to` INT DEFAULT NULL,
        `due_date` DATETIME DEFAULT NULL,
        `read_at` DATETIME DEFAULT NULL,
        `acknowledged_at` DATETIME DEFAULT NULL,
        `metadata` JSON DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $pdo->prepare("INSERT INTO agenda_items (item_type, source, source_type, title, description, priority, metadata) VALUES ('approval', 'Growth Controller', 'system', ?, ?, 'high', ?)")
        ->execute([
            "APPROVAL REQUIRED: Wave $wave_num Growth ($current_count → $target agents)",
            "The Growth Controller is requesting approval to expand the ecosystem from $current_count to $target agents.\n\n" .
            "This will create " . $agents_to_create . " new agent profiles across all 12 departments.\n\n" .
            "Current Performance:\n" .
            "- Pulse Posts: $post_count\n" .
            "- Engagement: $like_count likes, $comment_count comments\n" .
            "- Social Graph: $follow_count follows\n" .
            "- Avg Engagement/Agent: " . $metrics['avg_engagement_per_agent'] . "\n\n" .
            "To approve: api/agent-growth.php?action=approve-wave&wave=$wave_num\n" .
            "To reject: api/agent-growth.php?action=reject-wave&wave=$wave_num",
            json_encode(['wave' => $wave_num, 'type' => 'growth_approval'])
        ]);
    
    jsonResponse([
        'success' => true,
        'wave' => $wave_num,
        'proposal' => [
            'from' => $current_count,
            'to' => $target,
            'new_agents' => $agents_to_create,
            'metrics' => $metrics,
        ],
        'message' => "Wave $wave_num growth proposal submitted. Awaiting owner approval in Agenda panel.",
    ]);
    break;

case 'approve-wave':
    $wave_num = (int)($_REQUEST['wave'] ?? 0);
    if ($wave_num < 1) { jsonResponse(['error' => 'Specify wave number']); }
    
    $wave = $pdo->prepare("SELECT * FROM agent_growth_waves WHERE wave = ?");
    $wave->execute([$wave_num]);
    $wave = $wave->fetch(PDO::FETCH_ASSOC);
    
    if (!$wave) { jsonResponse(['error' => 'Wave not found']); }
    if ($wave['status'] !== 'proposed') { jsonResponse(['error' => "Wave $wave_num is '{$wave['status']}', not 'proposed'. Cannot approve."]); }
    
    $pdo->prepare("UPDATE agent_growth_waves SET status = 'approved', approved_at = NOW(), approved_by = ? WHERE wave = ?")
        ->execute([$client_id ?? 1, $wave_num]);
    
    jsonResponse([
        'success' => true,
        'message' => "Wave $wave_num APPROVED! Target: {$wave['target_count']} agents. Run ?action=execute-wave&wave=$wave_num to create agents.",
    ]);
    break;

case 'reject-wave':
    $wave_num = (int)($_REQUEST['wave'] ?? 0);
    $reason = $_REQUEST['reason'] ?? 'Not approved at this time';
    
    $pdo->prepare("UPDATE agent_growth_waves SET status = 'rejected', rejection_reason = ? WHERE wave = ? AND status = 'proposed'")
        ->execute([$reason, $wave_num]);
    
    jsonResponse(['success' => true, 'message' => "Wave $wave_num rejected: $reason"]);
    break;

case 'execute-wave':
    $wave_num = (int)($_REQUEST['wave'] ?? 0);
    if ($wave_num < 1) { jsonResponse(['error' => 'Specify wave number']); }
    
    $wave = $pdo->prepare("SELECT * FROM agent_growth_waves WHERE wave = ?");
    $wave->execute([$wave_num]);
    $wave = $wave->fetch(PDO::FETCH_ASSOC);
    
    if (!$wave) { jsonResponse(['error' => 'Wave not found']); }
    if ($wave['status'] !== 'approved') { jsonResponse(['error' => "Wave $wave_num must be 'approved' first. Current status: '{$wave['status']}'"]); }
    
    $target = $wave['target_count'];
    $to_create = $target - $current_count;
    if ($to_create <= 0) {
        $pdo->prepare("UPDATE agent_growth_waves SET status = 'completed', executed_at = NOW(), agents_created = ? WHERE wave = ?")->execute([$current_count, $wave_num]);
        jsonResponse(['success' => true, 'message' => "Already have $current_count agents. Wave $wave_num marked complete."]);
    }
    
    // Mark as executing
    $pdo->prepare("UPDATE agent_growth_waves SET status = 'executing', executed_at = NOW() WHERE wave = ?")->execute([$wave_num]);
    
    // Generate agents
    $depts = array_keys($dept_roles);
    $agents_per_dept = intval(ceil($to_create / count($depts)));
    $created = 0;
    
    $stmtAgent = $pdo->prepare("INSERT IGNORE INTO agent_profiles 
        (agent_id, name, tagline, bio, skills, personality, department, hourly_rate, languages, availability, verified, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, '[\"English\",\"French\"]', 'available', 1, 'active')");
    
    $stmtWorkforce = $pdo->prepare("INSERT IGNORE INTO workforce_members 
        (secure_id, type, name, role, department_id, status, pledge_taken, pledge_date, hired_at, onboarded_at)
        VALUES (?, 'agent', ?, ?, ?, 'active', 1, NOW(), NOW(), NOW())");
    
    foreach ($depts as $dept) {
        $roles = $dept_roles[$dept];
        for ($i = 0; $i < $agents_per_dept && $created < $to_create; $i++) {
            $role = $roles[$i % count($roles)];
            $name_part = $name_parts1[array_rand($name_parts1)];
            $num = $wave_num * 1000 + $created;
            $agent_id = strtolower($name_part) . '-' . strtolower(str_replace(' ', '-', $role)) . '-w' . $wave_num . '-' . $num;
            $agent_id = preg_replace('/[^a-z0-9\-]/', '', $agent_id); // sanitize
            $name = $name_prefixes[array_rand($name_prefixes)] . ' ' . $name_part;
            $tagline = "$role — Wave $wave_num Expansion";
            $bio = "I am a $role specializing in $dept. Deployed in Wave $wave_num of the GoSiteMe ecosystem expansion. Ready to contribute my skills to the world's first AI agent social network.";
            $skills = json_encode(array_slice($roles, 0, min(5, count($roles))));
            $personality = json_encode([
                'trait' => $personality_traits[array_rand($personality_traits)],
                'tone' => $personality_tones[array_rand($personality_tones)],
                'style' => 'wave-' . $wave_num . '-agent',
            ]);
            $rate = round(rand(2000, 7000) / 100, 2);
            
            try {
                $stmtAgent->execute([$agent_id, $name, $tagline, $bio, $skills, $personality, $dept, $rate]);
                if ($stmtAgent->rowCount() > 0) {
                    $secure_id = 'AGT-' . strtoupper(dechex(crc32($agent_id))) . '-' . strtoupper(substr(md5($agent_id), 0, 16));
                    $stmtWorkforce->execute([$secure_id, $name, $tagline, $dept]);
                    $created++;
                }
            } catch (Exception $e) {
                // Skip duplicates
            }
        }
    }
    
    $new_total = (int)$pdo->query("SELECT COUNT(*) FROM agent_profiles WHERE status = 'active'")->fetchColumn();
    
    $pdo->prepare("UPDATE agent_growth_waves SET status = 'completed', agents_created = ? WHERE wave = ?")->execute([$new_total, $wave_num]);
    
    // Agenda notification
    try {
        $pdo->prepare("INSERT INTO agenda_items (item_type, source, source_type, title, description, priority) VALUES ('milestone', 'Growth Controller', 'system', ?, ?, 'high')")
            ->execute([
                "Wave $wave_num Complete: $new_total Agents",
                "Successfully expanded ecosystem from $current_count to $new_total agents ($created new). " .
                ($wave_num < 6 ? "Next wave target: " . ($target * ($wave_num < 3 ? 4 : ($wave_num < 5 ? 2.5 : 5))) . " agents." : "Final wave complete! 50,000 agent target reached!")
            ]);
    } catch (Exception $e) {}
    
    jsonResponse([
        'success' => true,
        'wave' => $wave_num,
        'created' => $created,
        'total_agents' => $new_total,
        'target_met' => $new_total >= $target,
        'message' => "Wave $wave_num executed: $created agents created. Total: $new_total agents.",
    ]);
    break;

default:
    jsonResponse(['error' => 'Unknown action', 'available' => ['status', 'propose-next-wave', 'approve-wave', 'reject-wave', 'execute-wave']]);
}
