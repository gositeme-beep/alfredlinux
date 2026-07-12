<?php
/**
 * GoSiteMe AI Tools Directory API
 * Comprehensive directory of AI tools — both internal GoSiteMe tools and external
 * Supports categories, ratings, search, and affiliate integration
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

    $pdo->exec("CREATE TABLE IF NOT EXISTS `ai_tools_directory` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `tool_id` VARCHAR(50) UNIQUE NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `tagline` VARCHAR(200),
        `description` TEXT,
        `category` VARCHAR(50) NOT NULL,
        `subcategory` VARCHAR(50),
        `source` ENUM('internal','external') DEFAULT 'internal',
        `url` VARCHAR(500),
        `icon` VARCHAR(20) DEFAULT '🤖',
        `pricing` ENUM('free','freemium','paid','enterprise','open_source') DEFAULT 'free',
        `pricing_detail` VARCHAR(200),
        `features` JSON,
        `tags` JSON,
        `rating` DECIMAL(2,1) DEFAULT 0.0,
        `rating_count` INT DEFAULT 0,
        `views` INT DEFAULT 0,
        `is_featured` TINYINT DEFAULT 0,
        `is_gosm` TINYINT DEFAULT 0,
        `status` ENUM('active','coming_soon','deprecated') DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(`category`), INDEX(`source`), FULLTEXT(`name`, `description`, `tagline`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `ai_tool_ratings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `tool_id` VARCHAR(50) NOT NULL,
        `client_id` INT NOT NULL,
        `rating` INT NOT NULL,
        `review` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY(`tool_id`, `client_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error']); exit;
}

$action = $_REQUEST['action'] ?? 'directory';
$is_admin = ($client_id == 33) || $is_internal;

switch ($action) {

case 'directory':
    $cat = $_GET['category'] ?? null;
    $source = $_GET['source'] ?? null;
    $search = trim($_GET['search'] ?? '');

    $sql = "SELECT * FROM ai_tools_directory WHERE status = 'active'";
    $params = [];
    if ($cat) { $sql .= " AND category = ?"; $params[] = $cat; }
    if ($source) { $sql .= " AND source = ?"; $params[] = $source; }
    if (!empty($search)) { $sql .= " AND MATCH(name, description, tagline) AGAINST(? IN BOOLEAN MODE)"; $params[] = $search . '*'; }
    $sql .= " ORDER BY is_featured DESC, is_gosm DESC, rating DESC, views DESC LIMIT 100";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $categories = $pdo->query("SELECT category, COUNT(*) as c FROM ai_tools_directory WHERE status='active' GROUP BY category ORDER BY c DESC")->fetchAll();

    echo json_encode([
        'success' => true,
        'tools' => $stmt->fetchAll(),
        'categories' => $categories,
        'total' => $pdo->query("SELECT COUNT(*) FROM ai_tools_directory WHERE status='active'")->fetchColumn()
    ]);
    break;

case 'tool':
    $tid = $_GET['tool_id'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM ai_tools_directory WHERE tool_id = ?");
    $stmt->execute([$tid]);
    $tool = $stmt->fetch();
    if (!$tool) { echo json_encode(['error' => 'Not found']); exit; }
    $pdo->prepare("UPDATE ai_tools_directory SET views = views + 1 WHERE tool_id = ?")->execute([$tid]);
    $reviews = $pdo->prepare("SELECT r.*, c.username FROM ai_tool_ratings r LEFT JOIN clients c ON r.client_id=c.id WHERE r.tool_id = ? ORDER BY r.created_at DESC LIMIT 20");
    $reviews->execute([$tid]);
    echo json_encode(['success' => true, 'tool' => $tool, 'reviews' => $reviews->fetchAll()]);
    break;

case 'rate':
    $tid = $_POST['tool_id'] ?? '';
    $rating = intval($_POST['rating'] ?? 0);
    $review = trim($_POST['review'] ?? '');
    if ($rating < 1 || $rating > 5) { echo json_encode(['error' => 'Rating 1-5']); exit; }
    $pdo->prepare("INSERT INTO ai_tool_ratings (tool_id, client_id, rating, review) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE rating=VALUES(rating), review=VALUES(review)")
        ->execute([$tid, $client_id, $rating, $review]);
    // Update average
    $avg = $pdo->prepare("SELECT AVG(rating), COUNT(*) FROM ai_tool_ratings WHERE tool_id = ?");
    $avg->execute([$tid]);
    $r = $avg->fetch(PDO::FETCH_NUM);
    $pdo->prepare("UPDATE ai_tools_directory SET rating = ?, rating_count = ? WHERE tool_id = ?")->execute([round($r[0], 1), $r[1], $tid]);
    echo json_encode(['success' => true, 'new_rating' => round($r[0], 1)]);
    break;

case 'seed':
    if (!$is_admin) { echo json_encode(['error' => 'Admin only']); exit; }

    $tools = [
        // GoSiteMe Internal Tools
        ['gosm-alfred', 'Alfred AI', 'Your autonomous AI assistant', 'Full-spectrum AI assistant with 6-provider cascade, voice integration, and autonomous operations.', 'assistant', 'ai_chat', 'internal', '/alfred.php', '🧠', 'freemium', 'Free tier included', '["AI Chat","Voice","Autonomous Tasks","6 AI Providers","Tool Use"]', '["ai","assistant","chat","voice"]', 1, 1],
        ['gosm-voice', 'GoSiteMe Voice', 'Crystal-clear AI voice calls', 'Voice calling platform with AI receptionist, call recording, IVR builder, and voice cloning.', 'voice', 'calling', 'internal', '/voice.php', '📞', 'paid', 'From $9.99/mo', '["Voice Calls","AI Receptionist","Call Recording","IVR Builder","Voice Cloning"]', '["voice","phone","calling"]', 1, 1],
        ['gosm-gocode', 'GoCodeMe IDE', 'AI-powered code editor in the cloud', 'Full IDE with AI code completion, multi-language support, terminal, and deployment.', 'development', 'ide', 'internal', '/gocodeme.php', '💻', 'freemium', 'Free tier available', '["Code Editor","AI Completion","Terminal","Multi-Language","Git"]', '["ide","coding","development"]', 1, 1],
        ['gosm-circuit', 'Circuit Designer', 'Electronic circuit design & ZPE research', 'Interactive circuit designer with simulation engine, 27 components, and zero-point energy templates.', 'engineering', 'electronics', 'internal', '/tools/circuit-designer.php', '⚡', 'free', 'Free', '["Circuit Design","Simulation","ZPE Templates","Component Library"]', '["electronics","circuits","engineering"]', 1, 1],
        ['gosm-games', 'Game Creator Studio', 'AI-powered game creation for kids', 'Describe your dream game and AI builds it! Ages 8-15. HTML5 Canvas games.', 'creative', 'games', 'internal', '/tools/game-creator.php', '🎮', 'freemium', 'Free to create', '["Game Creation","AI Generation","HTML5","Canvas Games"]', '["games","kids","creative"]', 1, 1],
        ['gosm-marketplace', 'AI Marketplace', 'Buy and sell AI tools & templates', 'Marketplace for AI prompts, templates, tools, and services. 70/30 revenue split.', 'marketplace', 'commerce', 'internal', '/marketplace.php', '🏪', 'freemium', '70/30 revenue split', '["AI Tools","Templates","Prompts","Revenue Sharing"]', '["marketplace","sell","buy"]', 1, 1],
        ['gosm-discussions', 'Pro Discussions', 'AI expert panel debates', 'Watch AI professionals debate topics — lawyers, judges, doctors, and engineers in moot court, panels, and roundtables.', 'education', 'debate', 'internal', '/veil/pro-discussions.php', '⚖️', 'free', 'Free', '["Expert Debates","Moot Court","AI Panelists","Live Discussions"]', '["debate","legal","education"]', 1, 1],
        ['gosm-vr', 'Metaverse Hub', 'Three.js virtual reality experiences', 'Immersive VR experiences — Circuit Lab, Agent Presence, and more.', 'vr', 'metaverse', 'internal', '/vr/hub/', '🥽', 'free', 'Free', '["VR","Three.js","3D Visualization","Agent Avatars"]', '["vr","metaverse","3d"]', 1, 1],
        ['gosm-crypto', 'Crypto Transfer', 'Send crypto via QR, NFC, or link', 'Multi-chain crypto transfers — Solana Pay, Bitcoin, Ethereum. QR codes, NFC tap, and shareable links.', 'finance', 'crypto', 'internal', '/pay/transfer.php', '🪙', 'free', 'Free', '["Crypto Transfer","QR Code","NFC","Solana Pay","Multi-Chain"]', '["crypto","payment","transfer"]', 1, 1],
        ['gosm-depts', 'City Departments', '12 civic departments with 66 agents', '12 fully operational departments — Education, Legal, Transportation, Seniors Care, and more. 66 specialized AI agents.', 'government', 'civic', 'internal', '/veil/departments.php', '🏙️', 'free', 'Free', '["12 Departments","66 Agents","Intel Feed","Professional Discussions"]', '["government","civic","departments"]', 1, 1],

        // External AI Tools (curated)
        ['ext-chatgpt', 'ChatGPT', 'OpenAI conversational AI', 'Leading conversational AI by OpenAI. GPT-4o and GPT-4.1 models.', 'assistant', 'ai_chat', 'external', null, '💬', 'freemium', 'Free + $20/mo Pro', '["AI Chat","Code Generation","Analysis","Image Understanding"]', '["ai","chat","openai"]', 0, 0],
        ['ext-claude', 'Claude', 'Anthropic advanced AI assistant', 'Safety-focused AI by Anthropic. Excellent at analysis, writing, and coding.', 'assistant', 'ai_chat', 'external', null, '🟣', 'freemium', 'Free + $20/mo Pro', '["AI Chat","Long Context","Code","Analysis","Artifacts"]', '["ai","claude","anthropic"]', 0, 0],
        ['ext-midjourney', 'Midjourney', 'AI image generation', 'Premium AI image generation with stunning artistic quality.', 'creative', 'image_gen', 'external', null, '🎨', 'paid', 'From $10/mo', '["Image Generation","Art Styles","Upscaling","Variations"]', '["images","art","creative"]', 0, 0],
        ['ext-cursor', 'Cursor', 'AI-first code editor', 'VS Code fork with deep AI integration for code editing.', 'development', 'ide', 'external', null, '⌨️', 'freemium', 'Free + $20/mo', '["AI Coding","Auto-Complete","Chat","Multi-File Edit"]', '["ide","coding","ai"]', 0, 0],
        ['ext-notion', 'Notion AI', 'AI-powered workspace', 'All-in-one workspace with AI writing, summarization, and organization.', 'productivity', 'workspace', 'external', null, '📝', 'freemium', '$10/mo add-on', '["AI Writing","Summarize","Organize","Database"]', '["productivity","notes","workspace"]', 0, 0],
        ['ext-runway', 'Runway ML', 'AI video generation', 'AI-powered video editing and generation. Text-to-video, image-to-video.', 'creative', 'video', 'external', null, '🎬', 'paid', 'From $12/mo', '["Video Generation","Editing","Text-to-Video","Green Screen"]', '["video","creative","ai"]', 0, 0],
        ['ext-perplexity', 'Perplexity', 'AI-powered search engine', 'AI search that provides answers with citations from web sources.', 'search', 'research', 'external', null, '🔍', 'freemium', 'Free + $20/mo Pro', '["AI Search","Citations","Research","Academic"]', '["search","research","ai"]', 0, 0],
        ['ext-huggingface', 'Hugging Face', 'AI model hub', 'Largest repository of open-source AI models. Over 500K models.', 'development', 'models', 'external', null, '🤗', 'freemium', 'Free + paid inference', '["Model Hub","Open Source","Training","Inference"]', '["models","open-source","ml"]', 0, 0],
        ['ext-elevenlabs', 'ElevenLabs', 'AI voice synthesis', 'Ultra-realistic AI voice generation and cloning.', 'voice', 'synthesis', 'external', null, '🔊', 'freemium', 'Free + $5/mo', '["Voice Synthesis","Voice Cloning","Text-to-Speech","Dubbing"]', '["voice","tts","speech"]', 0, 0],
        ['ext-replicate', 'Replicate', 'Run AI models in the cloud', 'Cloud platform to run open-source AI models via API.', 'development', 'infrastructure', 'external', null, '☁️', 'paid', 'Pay per use', '["Cloud AI","API","Open Source Models","Fine-Tuning"]', '["cloud","api","models"]', 0, 0],
        ['ext-ollama', 'Ollama', 'Run LLMs locally', 'Run large language models locally on your machine. Open source.', 'development', 'local_ai', 'external', null, '🦙', 'open_source', 'Free', '["Local LLMs","Open Source","Privacy","Offline"]', '["local","llm","open-source"]', 0, 0],
    ];

    $count = 0;
    foreach ($tools as $t) {
        $stmt = $pdo->prepare("INSERT INTO ai_tools_directory (tool_id, name, tagline, description, category, subcategory, source, url, icon, pricing, pricing_detail, features, tags, is_featured, is_gosm) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), features=VALUES(features)");
        $stmt->execute($t);
        $count++;
    }

    echo json_encode(['success' => true, 'tools_seeded' => $count]);
    break;

default:
    echo json_encode(['error' => 'Unknown action', 'actions' => ['directory','tool','rate','seed']]);
}
