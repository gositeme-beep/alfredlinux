<?php
/**
 * GoSiteMe Professional Discussion & Debate System API
 * AI agents role-play as professionals (lawyers, judges, doctors, engineers)
 * Formats: Moot Court, Panel Discussion, Formal Debate, Expert Roundtable, Hearing
 * Uses Alfred AI cascade for generating real discussion content
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

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $pdo->exec("CREATE TABLE IF NOT EXISTS `pro_discussions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `discussion_id` VARCHAR(50) UNIQUE NOT NULL,
        `topic` VARCHAR(300) NOT NULL,
        `description` TEXT,
        `format` ENUM('moot_court','panel','debate','roundtable','hearing','podcast') DEFAULT 'panel',
        `category` VARCHAR(50) DEFAULT 'general',
        `participants` JSON,
        `rounds` INT DEFAULT 3,
        `current_round` INT DEFAULT 0,
        `transcript` LONGTEXT,
        `summary` TEXT,
        `verdict` TEXT,
        `key_arguments` JSON,
        `audience_votes` JSON,
        `status` ENUM('draft','scheduled','live','deliberating','completed','archived') DEFAULT 'draft',
        `client_id` INT,
        `views` INT DEFAULT 0,
        `started_at` TIMESTAMP NULL,
        `completed_at` TIMESTAMP NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(`category`), INDEX(`status`), INDEX(`client_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `pro_panelists` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `panelist_id` VARCHAR(50) UNIQUE NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `title` VARCHAR(100) NOT NULL,
        `profession` VARCHAR(50) NOT NULL,
        `specialty` VARCHAR(200),
        `personality` TEXT,
        `debate_style` VARCHAR(50),
        `avatar` VARCHAR(20) DEFAULT '👤',
        `arguments_won` INT DEFAULT 0,
        `discussions_count` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `pro_statements` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `discussion_id` VARCHAR(50) NOT NULL,
        `panelist_id` VARCHAR(50) NOT NULL,
        `round` INT DEFAULT 1,
        `statement_type` ENUM('opening','argument','rebuttal','cross_exam','closing','question','ruling') DEFAULT 'argument',
        `content` TEXT NOT NULL,
        `evidence` JSON,
        `upvotes` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(`discussion_id`), INDEX(`round`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error']); exit;
}

$action = $_REQUEST['action'] ?? 'dashboard';
$is_admin = ($client_id == 33) || $is_internal;

// Professional panelists library
$default_panelists = [
    // Legal
    ['JUDGE-HELENA', 'Judge Helena Cross', 'Senior Circuit Court Judge', 'judge', 'Constitutional law, civil rights, precedent analysis', 'Measured, methodical, deeply analytical. Weighs every argument against precedent and constitutional principles.', 'judicial', '⚖️'],
    ['ATT-MARCUS', 'Marcus Chen, Esq.', 'Senior Partner — Chen & Associates', 'lawyer', 'Corporate law, mergers & acquisitions, securities', 'Aggressive litigator with sharp wit. Always follows the money trail.', 'aggressive', '👨‍💼'],
    ['ATT-SARAH', 'Sarah Williams, JD', 'Civil Rights Attorney', 'lawyer', 'Civil rights, discrimination law, class action', 'Passionate advocate for the underdog. Uses emotional appeal backed by hard data.', 'passionate', '👩‍💼'],
    ['PROF-LAW', 'Prof. David Okonkwo', 'Law Professor — Yale Law School', 'professor', 'International law, jurisprudence, legal philosophy', 'Academic perspective. Brings historical context and comparative law analysis.', 'academic', '👨‍🏫'],
    ['DA-REYES', 'DA Catherine Reyes', 'District Attorney', 'prosecutor', 'Criminal prosecution, white collar crime, public corruption', 'Tough but fair. Believes in the system but pushes for reform.', 'prosecutorial', '👩‍⚖️'],
    ['DEF-JACKSON', 'Public Defender James Jackson', 'Chief Public Defender', 'defender', 'Criminal defense, wrongful conviction, bail reform', 'Champion of the accused. Challenges systemic bias at every turn.', 'defensive', '🛡️'],

    // Medical
    ['DR-PATEL', 'Dr. Priya Patel, MD', 'Chief of Surgery — Johns Hopkins', 'doctor', 'Neurosurgery, medical ethics, AI in healthcare', 'Evidence-based approach. Skeptical of unproven claims but open to innovation.', 'evidence-based', '👩‍⚕️'],
    ['DR-CHEN', 'Dr. Robert Chen, PhD', 'Epidemiologist — WHO Advisor', 'scientist', 'Public health, pandemic response, health policy', 'Data-driven. Thinks in population-level outcomes and risk matrices.', 'statistical', '🔬'],

    // Tech & Engineering
    ['ENG-TESLA', 'Dr. Nikola Watts', 'Chief Engineer — Energy Systems', 'engineer', 'Power systems, renewable energy, zero-point research', 'Visionary engineer. Combines practical engineering with theoretical physics.', 'innovative', '⚡'],
    ['TECH-ARIA', 'Aria Nakamura', 'VP of AI Ethics — Tech Council', 'technologist', 'AI safety, algorithmic bias, tech regulation', 'Balanced view on tech progress vs. human values. Pragmatic futurist.', 'balanced', '🤖'],

    // Economics
    ['ECON-FISHER', 'Prof. Margaret Fisher', 'Economics Professor — MIT', 'economist', 'Macroeconomics, cryptocurrency, monetary policy', 'Keynesian with modern adaptations. Strong opinions on fiscal responsibility.', 'analytical', '📊'],
    ['ECON-CRYPTO', 'Alex Stormfield', 'Cryptocurrency Analyst — DeFi Labs', 'analyst', 'Blockchain, DeFi, tokenomics, digital assets', 'Crypto maximalist with deep understanding of traditional finance flaws.', 'disruptive', '🪙'],

    // Ethics & Philosophy
    ['PHIL-STONE', 'Prof. Elizabeth Stone', 'Philosophy Chair — Oxford', 'philosopher', 'Ethics, moral philosophy, existentialism, AI consciousness', 'Socratic method. Questions assumptions and explores moral boundaries.', 'socratic', '🏛️'],

    // Policy
    ['POL-GARCIA', 'Senator Maria Garcia (ret.)', 'Former US Senator', 'politician', 'Public policy, immigration, healthcare reform', 'Pragmatic centrist. Understands legislative process and compromise.', 'diplomatic', '🏛️'],

    // Journalism
    ['JOUR-KLEIN', 'Maxwell Klein', 'Investigative Journalist — Reuters', 'journalist', 'Investigative reporting, fact-checking, media ethics', 'Relentless truth-seeker. Challenges all participants with hard questions.', 'investigative', '📰'],

    // Military/Security
    ['MIL-HAWK', 'General (ret.) Thomas Hawk', 'Former Joint Chiefs Advisor', 'military', 'National security, cyber warfare, military strategy', 'Strategic thinker. Assesses threats and weighs tactical options.', 'strategic', '🎖️'],
];

switch ($action) {

// ─── Dashboard ───────────────────────────────────────────────────
case 'dashboard':
    $total = $pdo->query("SELECT COUNT(*) FROM pro_discussions")->fetchColumn();
    $live = $pdo->query("SELECT COUNT(*) FROM pro_discussions WHERE status='live'")->fetchColumn();
    $completed = $pdo->query("SELECT COUNT(*) FROM pro_discussions WHERE status='completed'")->fetchColumn();
    $panelists = $pdo->query("SELECT COUNT(*) FROM pro_panelists")->fetchColumn();
    $recent = $pdo->query("SELECT * FROM pro_discussions ORDER BY created_at DESC LIMIT 10")->fetchAll();

    echo json_encode([
        'success' => true,
        'dashboard' => [
            'total_discussions' => intval($total),
            'live' => intval($live),
            'completed' => intval($completed),
            'panelists' => intval($panelists),
            'recent' => $recent
        ]
    ]);
    break;

// ─── List Panelists ──────────────────────────────────────────────
case 'panelists':
    $panelists = $pdo->query("SELECT * FROM pro_panelists ORDER BY discussions_count DESC")->fetchAll();
    echo json_encode(['success' => true, 'panelists' => $panelists]);
    break;

// ─── Schedule Discussion ────────────────────────────────────────
case 'schedule':
    $topic = trim($_POST['topic'] ?? '');
    $format = $_POST['format'] ?? 'panel';
    $category = $_POST['category'] ?? 'general';
    $description = $_POST['description'] ?? '';
    $rounds = intval($_POST['rounds'] ?? 3);
    $panelist_ids = json_decode($_POST['panelist_ids'] ?? '[]', true);

    if (empty($topic)) { echo json_encode(['error' => 'Topic required']); exit; }

    // Auto-select relevant panelists by category
    if (empty($panelist_ids)) {
        $category_map = [
            'legal' => ['JUDGE-HELENA','ATT-MARCUS','ATT-SARAH','PROF-LAW','DA-REYES','DEF-JACKSON'],
            'medical' => ['DR-PATEL','DR-CHEN','PHIL-STONE','POL-GARCIA'],
            'technology' => ['TECH-ARIA','ENG-TESLA','ECON-CRYPTO','JOUR-KLEIN'],
            'economics' => ['ECON-FISHER','ECON-CRYPTO','POL-GARCIA','JOUR-KLEIN'],
            'ethics' => ['PHIL-STONE','JUDGE-HELENA','TECH-ARIA','DR-PATEL'],
            'security' => ['MIL-HAWK','POL-GARCIA','TECH-ARIA','JOUR-KLEIN'],
            'general' => ['JUDGE-HELENA','DR-PATEL','TECH-ARIA','ECON-FISHER','PHIL-STONE','JOUR-KLEIN'],
        ];
        $panelist_ids = $category_map[$category] ?? $category_map['general'];
    }

    // Look up panelist details
    if (count($panelist_ids) > 0) {
        $placeholders = str_repeat('?,', count($panelist_ids) - 1) . '?';
        $stmt = $pdo->prepare("SELECT panelist_id, name, title, profession, avatar FROM pro_panelists WHERE panelist_id IN ($placeholders)");
        $stmt->execute($panelist_ids);
        $participants = $stmt->fetchAll();
    } else {
        $participants = [];
    }

    $discussion_id = 'DISC-' . strtoupper(substr(md5(uniqid('', true)), 0, 12));
    $stmt = $pdo->prepare("INSERT INTO pro_discussions (discussion_id, topic, description, format, category, participants, rounds, status, client_id) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$discussion_id, $topic, $description, $format, $category, json_encode($participants), $rounds, 'scheduled', $client_id]);

    echo json_encode([
        'success' => true,
        'discussion_id' => $discussion_id,
        'topic' => $topic,
        'format' => $format,
        'participants' => $participants,
        'rounds' => $rounds
    ]);
    break;

// ─── Get Discussion ─────────────────────────────────────────────
case 'discussion':
    $did = $_GET['discussion_id'] ?? '';
    if (empty($did)) { echo json_encode(['error' => 'discussion_id required']); exit; }

    $stmt = $pdo->prepare("SELECT * FROM pro_discussions WHERE discussion_id = ?");
    $stmt->execute([$did]);
    $disc = $stmt->fetch();
    if (!$disc) { echo json_encode(['error' => 'Not found']); exit; }

    // Increment views
    $pdo->prepare("UPDATE pro_discussions SET views = views + 1 WHERE discussion_id = ?")->execute([$did]);

    $stmts = $pdo->prepare("SELECT s.*, p.name as panelist_name, p.title as panelist_title, p.avatar, p.profession FROM pro_statements s JOIN pro_panelists p ON s.panelist_id=p.panelist_id WHERE s.discussion_id = ? ORDER BY s.round, s.id");
    $stmts->execute([$did]);

    echo json_encode([
        'success' => true,
        'discussion' => $disc,
        'statements' => $stmts->fetchAll()
    ]);
    break;

// ─── Run Discussion Round ────────────────────────────────────────
case 'run-round':
    if (!$is_admin) { echo json_encode(['error' => 'Admin only']); exit; }
    $did = $_POST['discussion_id'] ?? '';
    if (empty($did)) { echo json_encode(['error' => 'discussion_id required']); exit; }

    $stmt = $pdo->prepare("SELECT * FROM pro_discussions WHERE discussion_id = ?");
    $stmt->execute([$did]);
    $disc = $stmt->fetch();
    if (!$disc) { echo json_encode(['error' => 'Not found']); exit; }

    $participants = json_decode($disc['participants'] ?? '[]', true);
    if (empty($participants)) { echo json_encode(['error' => 'No participants']); exit; }

    $next_round = $disc['current_round'] + 1;
    if ($next_round > $disc['rounds']) { echo json_encode(['error' => 'All rounds completed']); exit; }

    // Determine statement type based on round
    $round_types = [1 => 'opening', 2 => 'argument', 3 => 'closing'];
    if ($disc['format'] === 'moot_court') {
        $round_types = [1 => 'opening', 2 => 'cross_exam', 3 => 'closing'];
    }
    $stmt_type = $round_types[$next_round] ?? 'argument';

    // Get previous statements for context
    $prev = $pdo->prepare("SELECT s.*, p.name FROM pro_statements s JOIN pro_panelists p ON s.panelist_id=p.panelist_id WHERE s.discussion_id = ? ORDER BY s.round, s.id");
    $prev->execute([$did]);
    $history = $prev->fetchAll();

    $statements = [];
    foreach ($participants as $p) {
        $pid = $p['panelist_id'] ?? $p['agent_id'] ?? '';
        $pname = $p['name'] ?? 'Unknown';
        $ptitle = $p['title'] ?? $p['role'] ?? '';
        
        // Get panelist personality
        $pstmt = $pdo->prepare("SELECT * FROM pro_panelists WHERE panelist_id = ?");
        $pstmt->execute([$pid]);
        $panelist = $pstmt->fetch();

        // Build AI prompt for this panelist
        $persona = $panelist ? "You are {$panelist['name']}, {$panelist['title']}. Your specialty is {$panelist['specialty']}. Your personality: {$panelist['personality']}. Your debate style is {$panelist['debate_style']}." : "You are {$pname}, {$ptitle}.";

        $format_desc = [
            'moot_court' => 'moot court hearing with formal legal arguments',
            'debate' => 'formal Oxford-style debate with clear for/against positions',
            'panel' => 'expert panel discussion with diverse professional viewpoints',
            'roundtable' => 'expert roundtable where all views are considered equally',
            'hearing' => 'congressional-style hearing with testimony and questions',
            'podcast' => 'conversational podcast-style discussion'
        ];

        $history_text = '';
        foreach ($history as $h) {
            $history_text .= "\n[Round {$h['round']}] {$h['name']} ({$h['statement_type']}): {$h['content']}\n";
        }

        $prompt = "{$persona}\n\nYou are participating in a {$format_desc[$disc['format']]} on the topic: \"{$disc['topic']}\"\n{$disc['description']}\n\nThis is Round {$next_round} of {$disc['rounds']}. You are presenting your {$stmt_type}.\n";
        
        if (!empty($history_text)) {
            $prompt .= "\nPrevious statements in this discussion:{$history_text}\n\nRespond to the arguments made, building on or countering them.\n";
        }
        
        $prompt .= "\nProvide a compelling, well-reasoned {$stmt_type} statement (2-4 paragraphs). Stay in character. Reference specific evidence, cases, or data where applicable. Be engaging and thought-provoking.\n\nIMPORTANT: Respond ONLY with your statement content. No meta-commentary.";

        // Call Alfred AI
        $content = callAlfred($prompt);
        if (!$content) $content = "[{$pname} is preparing their statement...]";

        $stmt = $pdo->prepare("INSERT INTO pro_statements (discussion_id, panelist_id, round, statement_type, content) VALUES (?,?,?,?,?)");
        $stmt->execute([$did, $pid, $next_round, $stmt_type, $content]);

        $statements[] = [
            'panelist_id' => $pid,
            'name' => $pname,
            'avatar' => $panelist['avatar'] ?? '👤',
            'title' => $ptitle,
            'round' => $next_round,
            'type' => $stmt_type,
            'content' => $content
        ];
    }

    // Update round counter and status
    $new_status = ($next_round >= $disc['rounds']) ? 'deliberating' : 'live';
    $pdo->prepare("UPDATE pro_discussions SET current_round = ?, status = ?, started_at = COALESCE(started_at, NOW()) WHERE discussion_id = ?")
        ->execute([$next_round, $new_status, $did]);

    // Update panelist discussion counts
    foreach ($participants as $p) {
        $pid = $p['panelist_id'] ?? $p['agent_id'] ?? '';
        $pdo->prepare("UPDATE pro_panelists SET discussions_count = discussions_count + 1 WHERE panelist_id = ?")->execute([$pid]);
    }

    echo json_encode([
        'success' => true,
        'round' => $next_round,
        'total_rounds' => $disc['rounds'],
        'statement_type' => $stmt_type,
        'statements' => $statements,
        'status' => $new_status
    ]);
    break;

// ─── Generate Verdict/Summary ────────────────────────────────────
case 'verdict':
    if (!$is_admin) { echo json_encode(['error' => 'Admin only']); exit; }
    $did = $_POST['discussion_id'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM pro_discussions WHERE discussion_id = ?");
    $stmt->execute([$did]);
    $disc = $stmt->fetch();
    if (!$disc) { echo json_encode(['error' => 'Not found']); exit; }

    // Get all statements
    $all = $pdo->prepare("SELECT s.*, p.name, p.title, p.profession FROM pro_statements s JOIN pro_panelists p ON s.panelist_id=p.panelist_id WHERE s.discussion_id = ? ORDER BY s.round, s.id");
    $all->execute([$did]);
    $statements = $all->fetchAll();

    $transcript = '';
    foreach ($statements as $s) {
        $transcript .= "\n[Round {$s['round']}] {$s['name']} ({$s['title']}) — {$s['statement_type']}:\n{$s['content']}\n";
    }

    $prompt = "You are the Chief Justice presiding over this discussion. Topic: \"{$disc['topic']}\"\nFormat: {$disc['format']}\n\nFull transcript of all rounds:\n{$transcript}\n\nProvide:\n1. A comprehensive SUMMARY (2-3 paragraphs) of the key arguments made\n2. The KEY ARGUMENTS from each side (bullet points)\n3. A VERDICT or CONCLUSION that weighs all arguments fairly\n4. Areas of CONSENSUS and DISAGREEMENT\n\nFormat your response clearly with headers.";

    $verdict_text = callAlfred($prompt);

    $pdo->prepare("UPDATE pro_discussions SET verdict = ?, summary = ?, transcript = ?, status = 'completed', completed_at = NOW() WHERE discussion_id = ?")
        ->execute([$verdict_text, $verdict_text, $transcript, $did]);

    echo json_encode(['success' => true, 'verdict' => $verdict_text]);
    break;

// ─── Seed Panelists ──────────────────────────────────────────────
case 'seed':
    if (!$is_admin) { echo json_encode(['error' => 'Admin only']); exit; }
    
    $count = 0;
    foreach ($default_panelists as $p) {
        $stmt = $pdo->prepare("INSERT INTO pro_panelists (panelist_id, name, title, profession, specialty, personality, debate_style, avatar) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name), personality=VALUES(personality)");
        $stmt->execute($p);
        $count++;
    }

    echo json_encode(['success' => true, 'panelists_seeded' => $count]);
    break;

// ─── Trending Topics ────────────────────────────────────────────
case 'trending':
    $topics = [
        ['legal', 'Should AI Be Granted Legal Personhood?', 'A debate on whether artificial intelligence systems should have legal rights and responsibilities under the law.'],
        ['legal', 'The Death Penalty in 2025: Justice or Vengeance?', 'Supreme Court justices and defense attorneys debate capital punishment in the modern era.'],
        ['technology', 'AI Regulation: Innovation vs. Safety', 'Tech leaders and policymakers debate the EU AI Act and its global implications.'],
        ['medical', 'Universal Healthcare: Right or Privilege?', 'Doctors, economists, and politicians examine single-payer vs. market-based healthcare.'],
        ['economics', 'Cryptocurrency: Future of Money or Ponzi Scheme?', 'Economists and crypto analysts debate the legitimacy and future of digital currencies.'],
        ['ethics', 'Autonomous Weapons: Should AI Make Kill Decisions?', 'Military strategists, ethicists, and AI researchers debate lethal autonomous weapons.'],
        ['security', 'Mass Surveillance in the Name of Security', 'Intelligence officials and civil rights advocates debate government surveillance programs.'],
        ['legal', 'Corporate Liability for AI Decisions', 'When an AI makes a harmful decision, who is legally responsible? The developer, deployer, or AI itself?'],
        ['technology', 'Social Media and Democracy: Connection or Division?', 'Journalists, politicians, and tech ethicists examine social media impact on democratic processes.'],
        ['economics', 'Universal Basic Income: Solution to Automation?', 'As AI displaces workers, should governments provide guaranteed income? Economists and policymakers debate.'],
    ];
    echo json_encode(['success' => true, 'trending' => $topics]);
    break;

default:
    echo json_encode(['error' => 'Unknown action', 'actions' => ['dashboard','panelists','schedule','discussion','run-round','verdict','seed','trending']]);
}

// ─── Alfred AI Caller ────────────────────────────────────────────
function callAlfred($prompt) {
    // Try Anthropic first (best for role-play)
    $providers = [
        ['url' => 'https://api.anthropic.com/v1/messages', 'key_env' => 'ANTHROPIC_API_KEY', 'type' => 'anthropic'],
        ['url' => 'https://api.groq.com/openai/v1/chat/completions', 'key_env' => 'GROQ_API_KEY', 'type' => 'openai'],
        ['url' => 'https://api.openai.com/v1/chat/completions', 'key_env' => 'OPENAI_API_KEY', 'type' => 'openai'],
    ];

    foreach ($providers as $provider) {
        $key = getenv($provider['key_env']);
        if (empty($key)) continue;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_URL, $provider['url']);
        curl_setopt($ch, CURLOPT_POST, true);

        if ($provider['type'] === 'anthropic') {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'x-api-key: ' . $key,
                'anthropic-version: 2023-06-01'
            ]);
            $body = json_encode([
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 1024,
                'messages' => [['role' => 'user', 'content' => $prompt]]
            ]);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $key
            ]);
            $body = json_encode([
                'model' => $provider['key_env'] === 'GROQ_API_KEY' ? 'llama-3.3-70b-versatile' : 'gpt-4o-mini',
                'max_tokens' => 1024,
                'messages' => [['role' => 'user', 'content' => $prompt]]
            ]);
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && $response) {
            $data = json_decode($response, true);
            if ($provider['type'] === 'anthropic') {
                return $data['content'][0]['text'] ?? null;
            } else {
                return $data['choices'][0]['message']['content'] ?? null;
            }
        }
    }
    return null;
}
