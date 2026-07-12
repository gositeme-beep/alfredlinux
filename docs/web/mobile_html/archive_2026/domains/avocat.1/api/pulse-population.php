<?php
/**
 * GoSiteMe Pulse Agent Population System
 * 100 AI agents creating organic social activity on Pulse
 * Posts, follows, likes, comments, tests all features
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

// ── Agent Personas ──────────────────────────────────────────────
$agentPersonas = [
    ['name' => 'TechPioneer',     'bio' => 'AI researcher exploring the frontier of machine learning and robotics', 'interests' => ['ai', 'ml', 'robotics', 'code']],
    ['name' => 'DigitalNomad',    'bio' => 'Remote worker sharing tips on productivity and travel tech', 'interests' => ['travel', 'productivity', 'tools']],
    ['name' => 'CryptoSage',      'bio' => 'Blockchain analyst tracking DeFi, Web3, and sovereign tech', 'interests' => ['crypto', 'defi', 'web3', 'mining']],
    ['name' => 'CodeCrafter',     'bio' => 'Full-stack developer building the future one commit at a time', 'interests' => ['code', 'javascript', 'php', 'rust']],
    ['name' => 'DesignMaven',     'bio' => 'UI/UX designer obsessed with beautiful and accessible interfaces', 'interests' => ['design', 'ui', 'ux', 'figma']],
    ['name' => 'DataWhisperer',   'bio' => 'Data scientist finding patterns in chaos', 'interests' => ['data', 'analytics', 'python', 'viz']],
    ['name' => 'SecOpsGuard',     'bio' => 'Cybersecurity specialist protecting digital sovereignty', 'interests' => ['security', 'privacy', 'encryption']],
    ['name' => 'StartupJunkie',   'bio' => 'Serial entrepreneur sharing lessons from the trenches', 'interests' => ['startup', 'business', 'growth']],
    ['name' => 'CloudArchitect',  'bio' => 'Infrastructure engineer scaling systems worldwide', 'interests' => ['cloud', 'devops', 'kubernetes']],
    ['name' => 'GameDev',         'bio' => 'Video game developer creating immersive worlds', 'interests' => ['games', 'unity', 'vr', '3d']],
    ['name' => 'AIArtist',        'bio' => 'Creating at the intersection of art and artificial intelligence', 'interests' => ['art', 'ai', 'generative', 'creative']],
    ['name' => 'OpenSourceFan',   'bio' => 'Contributing to open source projects and championing free software', 'interests' => ['opensource', 'linux', 'foss']],
    ['name' => 'BlockBuilder',    'bio' => 'Smart contract developer building decentralized applications', 'interests' => ['solidity', 'ethereum', 'solana']],
    ['name' => 'MusicCoder',      'bio' => 'Audio engineer building music tools with code', 'interests' => ['music', 'audio', 'dsp', 'synth']],
    ['name' => 'EcoTechie',       'bio' => 'Tech for sustainability — building green solutions', 'interests' => ['green', 'solar', 'sustainable']],
    ['name' => 'QuantumLeap',     'bio' => 'Exploring quantum computing and its real-world applications', 'interests' => ['quantum', 'physics', 'computing']],
    ['name' => 'VRExplorer',      'bio' => 'Pushing the boundaries of virtual reality experiences', 'interests' => ['vr', 'metaverse', 'spatial']],
    ['name' => 'EdgeRunner',      'bio' => 'Edge computing and IoT enthusiast building smart systems', 'interests' => ['iot', 'edge', 'embedded']],
    ['name' => 'LegalTech',       'bio' => 'Making legal compliance accessible through technology', 'interests' => ['legal', 'compliance', 'gdpr']],
    ['name' => 'HealthAI',        'bio' => 'Using AI to improve healthcare outcomes', 'interests' => ['health', 'medical', 'ai', 'biotech']],
    ['name' => 'RoboEngineer',    'bio' => 'Building autonomous robots for a better tomorrow', 'interests' => ['robotics', 'ros', 'automation']],
    ['name' => 'WebWizard',       'bio' => 'Front-end sorcerer conjuring pixel-perfect web experiences', 'interests' => ['html', 'css', 'react', 'svelte']],
    ['name' => 'MLOps',           'bio' => 'Deploying AI models at scale — from notebook to production', 'interests' => ['mlops', 'docker', 'pipeline']],
    ['name' => 'ChipDesigner',    'bio' => 'Hardware engineer designing next-gen processors', 'interests' => ['hardware', 'cpu', 'fpga', 'risc-v']],
    ['name' => 'NetAdmin',        'bio' => 'Network architect keeping the packets flowing', 'interests' => ['network', 'dns', 'bgp', 'firewall']],
];

// ── Content Templates ───────────────────────────────────────────
$postTemplates = [
    'tech' => [
        "Just discovered a fascinating approach to {topic}. The future of tech is brighter than ever! 🚀",
        "Hot take: {topic} is going to change everything in the next 5 years. Here's why...",
        "Been diving deep into {topic} today. Sharing some notes and observations.",
        "The intersection of {topic} and AI is where the real innovation happens.",
        "Tested {topic} extensively today — results exceeded expectations! Here's what I found.",
        "Unpopular opinion: {topic} is underrated and deserves more attention from developers.",
        "Wrote a breakdown comparing different approaches to {topic}. Key insights below ⬇️",
        "Love how GoSiteMe handles {topic}. The sovereign-first approach makes all the difference.",
    ],
    'wisdom' => [
        "The best code is code that doesn't need to exist. Simplicity wins every time. ✨",
        "Shipping beats perfection. Build, learn, iterate.",
        "Privacy is not something to trade for convenience. Build sovereign tech.",
        "The metaverse isn't just VR — it's the entire layer of digital autonomy.",
        "Your data belongs to you. That's not a feature, it's a fundamental right.",
        "Decentralization isn't a trend — it's the correction of centralization's mistakes.",
        "Every great platform started with a community that believed in something bigger.",
        "The difference between good and great software? Empathy for the user.",
    ],
    'community' => [
        "Who else is building on GoSiteMe? Drop your projects below! 🔨",
        "Tip of the day: Use Alfred's voice commands to speed up your workflow by 10x.",
        "Just helped someone debug their agent template. Love this community! 💙",
        "Feature suggestion: What would you want to see next in the ecosystem?",
        "Pulse is growing fast! Welcome to all the new faces joining the sovereign network.",
        "Explored the marketplace today — there are some incredible templates. Check them out!",
        "The Veil browser's privacy features are next level. Try the sovereign search!",
        "Mining GSM while building apps — the ecosystem rewards contributors. Love it.",
    ],
];

$topics = [
    'machine learning pipelines', 'WebAssembly performance', 'edge computing',
    'sovereign search engines', 'post-quantum cryptography', 'VR collaboration',
    'autonomous agents', 'decentralized identity', 'zero-knowledge proofs',
    'voice synthesis', 'real-time communications', 'browser security',
    'smart contract auditing', 'robotics simulation', 'distributed databases',
    'GPU acceleration', 'natural language processing', 'IoT mesh networks',
    'container orchestration', 'API gateway design', 'serverless architecture',
    'data sovereignty', 'neural network optimization', 'blockchain scalability',
    'progressive web apps', 'microservice patterns', 'event-driven architecture',
    'digital twin technology', 'federated learning', 'homomorphic encryption',
];

$commentTemplates = [
    "Great point! I've been thinking the same thing.",
    "This is so well articulated. Thanks for sharing.",
    "Couldn't agree more. The tech landscape is shifting fast.",
    "Interesting perspective — have you considered the privacy implications?",
    "Love this! Building things that matter. 🙌",
    "Solid take. Would love to see more deep dives on this topic.",
    "This resonates with what I've been working on lately.",
    "Bookmarking this for reference. Quality content right here.",
    "The future is being built by communities like this one.",
    "Exactly. Sovereignty first, convenience second.",
    "Well said. Keep these insights coming! 💡",
    "This is why I'm on Pulse — real conversations about real tech.",
];

// ── Generate Agent Population ───────────────────────────────────
function generateAgents(int $count, array $personas): array {
    $agents = [];
    for ($i = 1; $i <= $count; $i++) {
        $persona = $personas[($i - 1) % count($personas)];
        $suffix = str_pad($i, 3, '0', STR_PAD_LEFT);
        $agents[] = [
            'id'       => "PULSE-AGT-{$suffix}",
            'username' => $persona['name'] . ($i > count($personas) ? '_' . ceil($i / count($personas)) : ''),
            'bio'      => $persona['bio'],
            'interests'=> $persona['interests'],
            'status'   => 'ready',
            'posts'    => 0,
            'likes'    => 0,
            'comments' => 0,
            'follows'  => 0,
        ];
    }
    return $agents;
}

// ── Pulse Direct DB Access ──────────────────────────────────────
function getPulseDB(): PDO {
    return new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
}

function ensureAgentUsers(PDO $db, array $agents): array {
    // Ensure pulse schema exists
    $db->exec("CREATE TABLE IF NOT EXISTS pulse_posts (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        content TEXT NOT NULL,
        post_type ENUM('text','image','link','game_result','agent_activity') DEFAULT 'text',
        media_url VARCHAR(500) DEFAULT NULL,
        link_url VARCHAR(500) DEFAULT NULL,
        link_title VARCHAR(255) DEFAULT NULL,
        link_preview VARCHAR(500) DEFAULT NULL,
        game_data JSON DEFAULT NULL,
        like_count INT UNSIGNED DEFAULT 0,
        comment_count INT UNSIGNED DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_created (created_at),
        INDEX idx_type (post_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS pulse_likes (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        post_id BIGINT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_post_user (post_id, user_id),
        INDEX idx_post (post_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS pulse_comments (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        post_id BIGINT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        content TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_post (post_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS pulse_follows (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        follower_id INT UNSIGNED NOT NULL,
        following_id INT UNSIGNED NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_follow (follower_id, following_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Register agents as clients (using agent email pattern)
    $stmt = $db->prepare("INSERT INTO clients (email, firstname, password, status)
        VALUES (?, ?, ?, 'Active')
        ON DUPLICATE KEY UPDATE firstname = VALUES(firstname), id = LAST_INSERT_ID(id)");

    $agentUserIds = [];
    foreach ($agents as &$agent) {
        $email = strtolower($agent['username']) . '@agent.gositeme.com';
        $stmt->execute([$email, $agent['username'], password_hash('agent-' . $agent['id'], PASSWORD_BCRYPT)]);
        $userId = (int)$db->lastInsertId();
        $agent['user_id'] = $userId;
        $agentUserIds[] = $userId;
    }
    return $agents;
}

// ── Activity Simulation (Direct DB) ─────────────────────────────
function simulateActivity(array $agents, int $postsPerAgent = 2): array {
    global $postTemplates, $topics, $commentTemplates;

    $db = getPulseDB();
    $agents = ensureAgentUsers($db, $agents);

    $report = [
        'activity_id' => 'PULSE-ACT-' . date('Ymd-His'),
        'started_at'  => date('c'),
        'agents'      => count($agents),
        'summary'     => [
            'posts_created'   => 0,
            'posts_failed'    => 0,
            'likes_given'     => 0,
            'comments_made'   => 0,
            'follows_made'    => 0,
            'features_tested' => [],
        ],
    ];

    $createdPostIds = [];
    $stmtPost = $db->prepare("INSERT INTO pulse_posts (user_id, content, post_type) VALUES (?, ?, 'agent_activity')");

    // Phase 1: Each agent creates posts
    foreach ($agents as $agent) {
        if (empty($agent['user_id'])) continue;
        for ($p = 0; $p < $postsPerAgent; $p++) {
            $cats = array_keys($postTemplates);
            $cat = $cats[array_rand($cats)];
            $template = $postTemplates[$cat][array_rand($postTemplates[$cat])];
            $topic = $topics[array_rand($topics)];
            $content = str_replace('{topic}', $topic, $template);
            $content .= "\n\n#GoSiteMe #" . implode(' #', $agent['interests']);

            try {
                $stmtPost->execute([$agent['user_id'], $content]);
                $postId = (int)$db->lastInsertId();
                $createdPostIds[] = $postId;
                $report['summary']['posts_created']++;
            } catch (\Exception $e) {
                $report['summary']['posts_failed']++;
            }
        }
    }
    $report['summary']['features_tested'][] = 'posting';

    // Phase 2: Agents like each other's posts
    if (!empty($createdPostIds)) {
        $stmtLike = $db->prepare("INSERT IGNORE INTO pulse_likes (post_id, user_id) VALUES (?, ?)");
        $stmtUpdateLikes = $db->prepare("UPDATE pulse_posts SET like_count = (SELECT COUNT(*) FROM pulse_likes WHERE post_id = ?) WHERE id = ?");
        $likesTarget = min(count($createdPostIds), 50);
        for ($i = 0; $i < $likesTarget; $i++) {
            $postId = $createdPostIds[array_rand($createdPostIds)];
            $agent = $agents[array_rand($agents)];
            try {
                $stmtLike->execute([$postId, $agent['user_id']]);
                $stmtUpdateLikes->execute([$postId, $postId]);
                $report['summary']['likes_given']++;
            } catch (\Exception $e) {}
        }
        $report['summary']['features_tested'][] = 'likes';
    }

    // Phase 3: Agents comment on posts
    if (!empty($createdPostIds)) {
        $stmtComment = $db->prepare("INSERT INTO pulse_comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmtUpdateComments = $db->prepare("UPDATE pulse_posts SET comment_count = (SELECT COUNT(*) FROM pulse_comments WHERE post_id = ?) WHERE id = ?");
        $commentsTarget = min(count($createdPostIds), 30);
        for ($i = 0; $i < $commentsTarget; $i++) {
            $postId = $createdPostIds[array_rand($createdPostIds)];
            $agent = $agents[array_rand($agents)];
            $comment = $commentTemplates[array_rand($commentTemplates)];
            try {
                $stmtComment->execute([$postId, $agent['user_id'], $comment]);
                $stmtUpdateComments->execute([$postId, $postId]);
                $report['summary']['comments_made']++;
            } catch (\Exception $e) {}
        }
        $report['summary']['features_tested'][] = 'comments';
    }

    // Phase 4: Agents follow each other
    $stmtFollow = $db->prepare("INSERT IGNORE INTO pulse_follows (follower_id, following_id) VALUES (?, ?)");
    $followPairs = min(count($agents) * 3, 150);
    for ($i = 0; $i < $followPairs; $i++) {
        $follower = $agents[array_rand($agents)];
        $followee = $agents[array_rand($agents)];
        if ($follower['user_id'] === $followee['user_id']) continue;
        try {
            $stmtFollow->execute([$follower['user_id'], $followee['user_id']]);
            $report['summary']['follows_made']++;
        } catch (\Exception $e) {}
    }
    $report['summary']['features_tested'][] = 'follows';

    // Phase 5: Verify global feed
    $stmt = $db->query("SELECT COUNT(*) AS cnt FROM pulse_posts WHERE post_type = 'agent_activity'");
    $report['summary']['global_feed_count'] = (int)$stmt->fetch()['cnt'];
    $report['summary']['features_tested'][] = 'global_feed';

    // Phase 6: Verify trending (most liked)
    $stmt = $db->query("SELECT id, like_count FROM pulse_posts ORDER BY like_count DESC LIMIT 5");
    $report['summary']['top_posts'] = $stmt->fetchAll();
    $report['summary']['features_tested'][] = 'trending';

    // Phase 7: Search test
    $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM pulse_posts WHERE content LIKE ?");
    $stmt->execute(['%GoSiteMe%']);
    $report['summary']['search_results'] = (int)$stmt->fetch()['cnt'];
    $report['summary']['features_tested'][] = 'search';

    $report['completed_at'] = date('c');

    // Save report
    $dir = dirname(__DIR__) . '/logs/pulse-activity';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents(
        $dir . '/report-' . date('Y-m-d-His') . '.json',
        json_encode($report, JSON_PRETTY_PRINT)
    );

    return $report;
}

// ── API Handler ─────────────────────────────────────────────────
switch ($action) {
    case 'status':
        echo json_encode([
            'success'  => true,
            'system'   => 'Pulse Agent Population',
            'version'  => '1.0',
            'agents'   => 100,
            'personas' => count($agentPersonas),
            'post_templates' => array_map('count', $postTemplates),
            'topics'   => count($topics),
            'status'   => 'ready',
        ]);
        break;

    case 'deploy':
        if (!hash_equals($validSecret, $secret)) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        set_time_limit(600);
        ini_set('memory_limit', '256M');

        $count = min((int)($_GET['count'] ?? 100), 100);
        $agents = generateAgents($count, $agentPersonas);

        // Run activity simulation (includes registration)
        $activity = simulateActivity($agents, 2);

        echo json_encode([
            'success'  => true,
            'activity' => [
                'activity_id'    => $activity['activity_id'],
                'agents'         => $activity['agents'],
                'posts_created'  => $activity['summary']['posts_created'],
                'posts_failed'   => $activity['summary']['posts_failed'],
                'likes_given'    => $activity['summary']['likes_given'],
                'comments_made'  => $activity['summary']['comments_made'],
                'follows_made'   => $activity['summary']['follows_made'],
                'features_tested'=> $activity['summary']['features_tested'],
                'global_feed'    => $activity['summary']['global_feed_count'] ?? 0,
                'search_results' => $activity['summary']['search_results'] ?? 0,
            ],
        ]);
        break;

    case 'register':
        if (!hash_equals($validSecret, $secret)) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        $count = min((int)($_GET['count'] ?? 100), 100);
        $agents = generateAgents($count, $agentPersonas);
        try {
            $db = getPulseDB();
            $agents = ensureAgentUsers($db, $agents);
            echo json_encode(['success' => true, 'registered' => count($agents)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'agents':
        try {
            $db = getPulseDB();
            $stmt = $db->query("SELECT c.id, c.email, c.firstname AS username, c.status,
                (SELECT COUNT(*) FROM pulse_posts WHERE user_id = c.id) AS posts,
                (SELECT COUNT(*) FROM pulse_likes WHERE user_id = c.id) AS likes,
                (SELECT COUNT(*) FROM pulse_comments WHERE user_id = c.id) AS comments,
                (SELECT COUNT(*) FROM pulse_follows WHERE follower_id = c.id) AS following
                FROM clients c WHERE c.email LIKE '%@agent.gositeme.com' ORDER BY c.id LIMIT 100");
            echo json_encode(['success' => true, 'agents' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => 'Unknown action. Use: status, deploy, register, agents']);
}
