<?php
/**
 * GoSiteMe Kids Game Creator API
 * Ages 8-15: Describe your dream game in your own words, AI builds it
 * Supports: HTML5 Canvas, Phaser-style, Text Adventures, Quiz Games, Platformers
 * Revenue: $0.99/game publish, free to create/play
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
$internal = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
$is_internal = !empty(INTERNAL_SECRET) && hash_equals(INTERNAL_SECRET, $internal);
if (!$client_id && !$is_internal) { echo json_encode(['error' => 'Auth required']); exit; }
require_once dirname(__DIR__) . '/includes/api-security.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $pdo->exec("CREATE TABLE IF NOT EXISTS `kids_games` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `game_id` VARCHAR(50) UNIQUE NOT NULL,
        `client_id` INT NOT NULL,
        `creator_name` VARCHAR(50) DEFAULT 'Anonymous Creator',
        `title` VARCHAR(100) NOT NULL,
        `description` TEXT NOT NULL,
        `game_type` ENUM('platformer','adventure','quiz','puzzle','racing','rpg','shooter','sandbox','story','custom') DEFAULT 'custom',
        `age_rating` ENUM('E','E10','T') DEFAULT 'E',
        `game_code` LONGTEXT,
        `thumbnail` TEXT,
        `tags` JSON,
        `plays` INT DEFAULT 0,
        `likes` INT DEFAULT 0,
        `status` ENUM('drafting','generating','ready','published','featured','flagged') DEFAULT 'drafting',
        `is_published` TINYINT DEFAULT 0,
        `publish_price` DECIMAL(4,2) DEFAULT 0.00,
        `revenue_earned` DECIMAL(10,2) DEFAULT 0.00,
        `ai_model_used` VARCHAR(50),
        `generation_prompt` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `published_at` TIMESTAMP NULL,
        INDEX(`client_id`), INDEX(`status`), INDEX(`game_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `kids_game_plays` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `game_id` VARCHAR(50) NOT NULL,
        `client_id` INT,
        `score` INT DEFAULT 0,
        `played_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(`game_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error']); exit;
}

$action = $_REQUEST['action'] ?? 'gallery';
$is_admin = ($client_id == 33) || $is_internal;

switch ($action) {

// ─── Gallery (public games) ─────────────────────────────────────
case 'gallery':
    $type = $_GET['type'] ?? null;
    $sql = "SELECT game_id, client_id, creator_name, title, description, game_type, age_rating, plays, likes, status, created_at FROM kids_games WHERE status IN ('published','featured')";
    $params = [];
    if ($type) { $sql .= " AND game_type = ?"; $params[] = $type; }
    $sql .= " ORDER BY FIELD(status,'featured','published'), plays DESC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true, 'games' => $stmt->fetchAll()]);
    break;

// ─── My Games ───────────────────────────────────────────────────
case 'my-games':
    $stmt = $pdo->prepare("SELECT * FROM kids_games WHERE client_id = ? ORDER BY created_at DESC");
    $stmt->execute([$client_id]);
    echo json_encode(['success' => true, 'games' => $stmt->fetchAll()]);
    break;

// ─── Create Game (describe it!) ─────────────────────────────────
case 'create':
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $game_type = $_POST['game_type'] ?? 'custom';
    $creator_name = trim($_POST['creator_name'] ?? 'Anonymous Creator');

    if (empty($title) || empty($description)) {
        echo json_encode(['error' => 'Tell us the name of your game and describe it!']);
        exit;
    }
    if (strlen($title) > 100) { echo json_encode(['error' => 'Title too long (max 100 chars)']); exit; }

    // Content safety check — basic profanity/violence filter for kids
    $unsafe_patterns = '/\b(kill|murder|blood|gore|sex|drug|gun|bomb|terror|suicide|naked)\b/i';
    if (preg_match($unsafe_patterns, $description) || preg_match($unsafe_patterns, $title)) {
        echo json_encode(['error' => 'Hmm, let\'s keep it fun and safe! Try describing your game differently.']);
        exit;
    }

    $game_id = 'GAME-' . strtoupper(substr(md5(uniqid('', true)), 0, 10));
    $stmt = $pdo->prepare("INSERT INTO kids_games (game_id, client_id, creator_name, title, description, game_type, generation_prompt, status) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$game_id, $client_id, $creator_name, $title, $description, $game_type, $description, 'drafting']);

    echo json_encode([
        'success' => true,
        'game_id' => $game_id,
        'message' => "Awesome! Your game idea '{$title}' is saved! Now click 'Generate' to bring it to life!"
    ]);
    break;

// ─── Generate Game Code (THE MAGIC) ─────────────────────────────
case 'generate':
    $game_id = $_POST['game_id'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM kids_games WHERE game_id = ? AND client_id = ?");
    $stmt->execute([$game_id, $client_id]);
    $game = $stmt->fetch();
    if (!$game) { echo json_encode(['error' => 'Game not found']); exit; }

    $type_hints = [
        'platformer' => 'a side-scrolling platformer game with a character that can run and jump. Include platforms, collectibles, and a goal.',
        'adventure' => 'a point-and-click adventure game with different scenes, items to collect, and puzzles to solve.',
        'quiz' => 'an interactive quiz game with multiple choice questions, score tracking, and fun animations.',
        'puzzle' => 'a puzzle game with a grid or board where the player arranges or matches pieces.',
        'racing' => 'a top-down racing game with a track, obstacles, and a finish line.',
        'rpg' => 'a simple RPG with a character that explores, talks to NPCs, and battles enemies turn-by-turn.',
        'shooter' => 'a space invaders style game where the player shoots at targets (non-violent, like catching stars or popping bubbles).',
        'sandbox' => 'a creative sandbox where the player can build and place objects freely.',
        'story' => 'an interactive story game where choices lead to different paths and endings.',
        'custom' => 'a fun game based exactly on what the creator described.',
    ];

    $type_desc = $type_hints[$game['game_type']] ?? $type_hints['custom'];

    $prompt = "You are a game developer creating a game for a child (age 8-15). The child described their dream game:

GAME TITLE: {$game['title']}
GAME DESCRIPTION: {$game['description']}
GAME TYPE: {$game['game_type']} — {$type_desc}

Create a COMPLETE, WORKING HTML5 game using JavaScript and Canvas. Requirements:
1. The game MUST be self-contained in a single HTML file
2. Use HTML5 Canvas for graphics (draw shapes, no external images needed)
3. Include colorful graphics using canvas drawing (rectangles, circles, text)
4. Game must have: start screen, gameplay, score display, game over screen
5. Keyboard controls (arrows + space) and touch/click support for mobile
6. Fun colors and simple animations
7. A score or progress system
8. Make it genuinely FUN and replayable
9. Appropriate for children — NO violence, NO scary content
10. Canvas should be responsive (fill parent container)

Return ONLY the complete HTML code. No explanation, no markdown, just the HTML starting with <!DOCTYPE html>.";

    // Update status
    $pdo->prepare("UPDATE kids_games SET status = 'generating' WHERE game_id = ?")->execute([$game_id]);

    // Call AI
    $game_code = callAI($prompt);
    $model_used = 'unknown';

    if (empty($game_code)) {
        $pdo->prepare("UPDATE kids_games SET status = 'drafting' WHERE game_id = ?")->execute([$game_id]);
        echo json_encode(['error' => 'AI is taking a break — try again in a moment!']);
        exit;
    }

    // Clean up response — extract just the HTML
    if (preg_match('/<!DOCTYPE html>.*$/is', $game_code, $matches)) {
        $game_code = $matches[0];
    }
    // Remove any markdown code fences
    $game_code = preg_replace('/^```html?\s*/i', '', $game_code);
    $game_code = preg_replace('/\s*```\s*$/', '', $game_code);

    $pdo->prepare("UPDATE kids_games SET game_code = ?, status = 'ready', ai_model_used = ? WHERE game_id = ?")
        ->execute([$game_code, $model_used, $game_id]);

    echo json_encode([
        'success' => true,
        'game_id' => $game_id,
        'message' => "Your game is ready! Click Play to try it out!"
    ]);
    break;

// ─── Play Game ──────────────────────────────────────────────────
case 'play':
    $game_id = $_GET['game_id'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM kids_games WHERE game_id = ? AND status IN ('ready','published','featured')");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch();
    if (!$game) { echo json_encode(['error' => 'Game not found or not ready yet']); exit; }

    // Increment plays
    $pdo->prepare("UPDATE kids_games SET plays = plays + 1 WHERE game_id = ?")->execute([$game_id]);
    $pdo->prepare("INSERT INTO kids_game_plays (game_id, client_id) VALUES (?, ?)")->execute([$game_id, $client_id]);

    echo json_encode([
        'success' => true,
        'game' => [
            'game_id' => $game['game_id'],
            'title' => $game['title'],
            'description' => $game['description'],
            'game_type' => $game['game_type'],
            'creator_name' => $game['creator_name'],
            'plays' => $game['plays'] + 1,
            'likes' => $game['likes']
        ],
        'code' => $game['game_code']
    ]);
    break;

// ─── Publish Game ───────────────────────────────────────────────
case 'publish':
    $game_id = $_POST['game_id'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM kids_games WHERE game_id = ? AND client_id = ? AND status = 'ready'");
    $stmt->execute([$game_id, $client_id]);
    $game = $stmt->fetch();
    if (!$game) { echo json_encode(['error' => 'Game not found or not ready']); exit; }

    $pdo->prepare("UPDATE kids_games SET status = 'published', is_published = 1, published_at = NOW() WHERE game_id = ?")
        ->execute([$game_id]);

    echo json_encode(['success' => true, 'message' => "'{$game['title']}' is now published! Other creators can play it!"]);
    break;

// ─── Like Game ──────────────────────────────────────────────────
case 'like':
    $game_id = $_POST['game_id'] ?? '';
    $pdo->prepare("UPDATE kids_games SET likes = likes + 1 WHERE game_id = ? AND status IN ('published','featured')")
        ->execute([$game_id]);
    echo json_encode(['success' => true]);
    break;

// ─── Stats (admin) ──────────────────────────────────────────────
case 'stats':
    $total = $pdo->query("SELECT COUNT(*) FROM kids_games")->fetchColumn();
    $published = $pdo->query("SELECT COUNT(*) FROM kids_games WHERE is_published = 1")->fetchColumn();
    $plays = $pdo->query("SELECT SUM(plays) FROM kids_games")->fetchColumn();
    $creators = $pdo->query("SELECT COUNT(DISTINCT client_id) FROM kids_games")->fetchColumn();
    $top = $pdo->query("SELECT title, creator_name, plays, likes, game_type FROM kids_games WHERE is_published = 1 ORDER BY plays DESC LIMIT 10")->fetchAll();

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_games' => intval($total),
            'published' => intval($published),
            'total_plays' => intval($plays ?: 0),
            'unique_creators' => intval($creators),
            'top_games' => $top
        ]
    ]);
    break;

default:
    echo json_encode(['error' => 'Unknown action', 'actions' => ['gallery','my-games','create','generate','play','publish','like','stats']]);
}

function callAI($prompt) {
    $providers = [
        ['url' => 'https://api.anthropic.com/v1/messages', 'key_env' => 'ANTHROPIC_API_KEY', 'type' => 'anthropic'],
        ['url' => 'https://api.groq.com/openai/v1/chat/completions', 'key_env' => 'GROQ_API_KEY', 'type' => 'openai'],
        ['url' => 'https://api.openai.com/v1/chat/completions', 'key_env' => 'OPENAI_API_KEY', 'type' => 'openai'],
    ];
    foreach ($providers as $p) {
        $key = getenv($p['key_env']);
        if (empty($key)) continue;
        $ch = curl_init($p['url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);
        curl_setopt($ch, CURLOPT_POST, true);
        if ($p['type'] === 'anthropic') {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'x-api-key: ' . $key, 'anthropic-version: 2023-06-01']);
            $body = json_encode(['model' => 'claude-sonnet-4-20250514', 'max_tokens' => 4096, 'messages' => [['role' => 'user', 'content' => $prompt]]]);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $key]);
            $model = $p['key_env'] === 'GROQ_API_KEY' ? 'llama-3.3-70b-versatile' : 'gpt-4o-mini';
            $body = json_encode(['model' => $model, 'max_tokens' => 4096, 'messages' => [['role' => 'user', 'content' => $prompt]]]);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 200 && $resp) {
            $data = json_decode($resp, true);
            return $p['type'] === 'anthropic' ? ($data['content'][0]['text'] ?? null) : ($data['choices'][0]['message']['content'] ?? null);
        }
    }
    return null;
}
