<?php
/**
 * Alfred Creative AI API
 * ──────────────────────
 * Generate images, video, music, and speech.
 * Integrates: FLUX/DALL-E (images), Kling/Runway (video),
 *             MusicGen (music), F5-TTS/ElevenLabs (speech).
 *
 * Endpoints:
 *   POST ?action=image         → Generate an image
 *   POST ?action=video         → Generate a video
 *   POST ?action=music         → Generate music
 *   POST ?action=tts           → Text-to-speech
 *   POST ?action=voice-clone   → Clone a voice
 *   GET  ?action=models        → List available models
 *   GET  ?action=history       → Generation history
 *   GET  ?action=status&id=... → Check generation status
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

requireCSRF();
apiRateLimit(15, 60, 'creative');

// API Keys
define('REPLICATE_API_TOKEN', getenv('REPLICATE_API_TOKEN') ?: '');
define('FAL_API_KEY', getenv('FAL_API_KEY') ?: '');
define('ELEVENLABS_API_KEY', getenv('ELEVENLABS_API_KEY') ?: '');
define('OPENAI_API_KEY_AI', getenv('OPENAI_API_KEY') ?: '');

// Output directory
define('CREATIVE_OUTPUT_DIR', dirname(__DIR__) . '/cache/creative/');

// ── Database Setup ──────────────────────────────────────────────
function ensureCreativeTable() {
    $db = getDB();
    if (!$db) return;
    $db->exec("CREATE TABLE IF NOT EXISTS alfred_creative (
        id VARCHAR(32) PRIMARY KEY,
        client_id INT NOT NULL,
        type ENUM('image','video','music','tts','voice_clone') NOT NULL,
        model VARCHAR(100) NOT NULL,
        prompt TEXT DEFAULT NULL,
        params JSON DEFAULT NULL,
        status ENUM('pending','processing','complete','failed') DEFAULT 'pending',
        output_url VARCHAR(500) DEFAULT NULL,
        output_format VARCHAR(10) DEFAULT NULL,
        cost_credits DECIMAL(10,4) DEFAULT 0,
        processing_time_ms INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        KEY idx_client (client_id),
        KEY idx_type (type),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function getAuthUser() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return $_SESSION['client_id'] ?? null;
}

function requireAuth() {
    $clientId = getAuthUser();
    if (!$clientId) jsonResponse(['error' => 'Authentication required'], 401);
    return $clientId;
}

// ── Rate Limiting ───────────────────────────────────────────────
function checkCreativeRateLimit($clientId, $type) {
    $db = getDB();
    if (!$db) return true;
    $limits = ['image' => 20, 'video' => 5, 'music' => 10, 'tts' => 30, 'voice_clone' => 3];
    $limit = $limits[$type] ?? 10;

    $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_creative
        WHERE client_id = ? AND type = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->execute([$clientId, $type]);
    return $stmt->fetchColumn() < $limit;
}

// ── Replicate API Client ────────────────────────────────────────
function replicatePredict($model, $input, $version = null) {
    if (!REPLICATE_API_TOKEN) {
        return ['error' => 'Replicate API token not configured'];
    }

    $body = ['input' => $input];
    if ($version) {
        $body['version'] = $version;
    }

    $url = $version
        ? 'https://api.replicate.com/v1/predictions'
        : "https://api.replicate.com/v1/models/{$model}/predictions";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . REPLICATE_API_TOKEN,
            'Prefer: wait',  // Wait for completion (up to 60s)
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
    ]);

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return json_decode($response, true) ?: ['error' => "HTTP $code"];
}

// ── fal.ai API Client ───────────────────────────────────────────
function falGenerate($model, $input) {
    if (!FAL_API_KEY) {
        return ['error' => 'fal.ai API key not configured'];
    }

    $ch = curl_init("https://fal.run/{$model}");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($input),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Key ' . FAL_API_KEY,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
    ]);

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return json_decode($response, true) ?: ['error' => "HTTP $code"];
}

// ── Model Definitions ───────────────────────────────────────────
$CREATIVE_MODELS = [
    'image' => [
        'flux-schnell' => [
            'name' => 'FLUX Schnell',
            'provider' => 'fal.ai',
            'model_id' => 'fal-ai/flux/schnell',
            'description' => 'Fast high-quality image generation',
            'max_size' => 1024,
            'supports' => ['prompt', 'size', 'seed'],
        ],
        'flux-pro' => [
            'name' => 'FLUX Pro',
            'provider' => 'fal.ai',
            'model_id' => 'fal-ai/flux-pro',
            'description' => 'Professional quality with finer control',
            'max_size' => 2048,
            'supports' => ['prompt', 'size', 'seed', 'guidance_scale'],
        ],
        'dall-e-3' => [
            'name' => 'DALL-E 3',
            'provider' => 'openai',
            'description' => 'OpenAI image generation',
            'max_size' => 1024,
            'supports' => ['prompt', 'size', 'quality', 'style'],
        ],
        'stable-diffusion-xl' => [
            'name' => 'Stable Diffusion XL',
            'provider' => 'replicate',
            'model_id' => 'stability-ai/sdxl',
            'description' => 'Open-source high-quality images',
            'max_size' => 1024,
            'supports' => ['prompt', 'negative_prompt', 'size', 'seed', 'guidance_scale', 'steps'],
        ],
    ],
    'video' => [
        'kling-v1' => [
            'name' => 'Kling v1',
            'provider' => 'fal.ai',
            'model_id' => 'fal-ai/kling-video/v1/standard/text-to-video',
            'description' => 'Text-to-video generation',
            'max_duration' => 10,
            'supports' => ['prompt', 'duration', 'aspect_ratio'],
        ],
        'minimax-video' => [
            'name' => 'MiniMax Video',
            'provider' => 'fal.ai',
            'model_id' => 'fal-ai/minimax/video-01/text-to-video',
            'description' => 'High-quality video synthesis',
            'max_duration' => 10,
            'supports' => ['prompt'],
        ],
    ],
    'music' => [
        'musicgen' => [
            'name' => 'MusicGen',
            'provider' => 'replicate',
            'model_id' => 'meta/musicgen',
            'description' => 'Meta AI music generation',
            'max_duration' => 30,
            'supports' => ['prompt', 'duration', 'temperature'],
        ],
    ],
    'tts' => [
        'f5-tts' => [
            'name' => 'F5-TTS',
            'provider' => 'replicate',
            'model_id' => 'jaze-ai/f5-tts',
            'description' => 'High-quality text-to-speech',
            'supports' => ['text', 'voice', 'speed'],
        ],
        'elevenlabs' => [
            'name' => 'ElevenLabs',
            'provider' => 'elevenlabs',
            'description' => 'Premium voice synthesis',
            'supports' => ['text', 'voice_id', 'model_id'],
        ],
        'openai-tts' => [
            'name' => 'OpenAI TTS',
            'provider' => 'openai',
            'description' => 'OpenAI text-to-speech',
            'supports' => ['text', 'voice', 'model', 'speed'],
        ],
    ],
];

// ── Generation Functions ────────────────────────────────────────

function generateImage($model, $params) {
    global $CREATIVE_MODELS;
    $config = $CREATIVE_MODELS['image'][$model] ?? null;
    if (!$config) return ['error' => "Unknown image model: $model"];

    $prompt = $params['prompt'] ?? '';
    if (strlen($prompt) < 3) return ['error' => 'Prompt too short'];

    switch ($config['provider']) {
        case 'fal.ai':
            $input = [
                'prompt' => $prompt,
                'image_size' => $params['size'] ?? 'landscape_16_9',
                'num_images' => 1,
            ];
            if (isset($params['seed'])) $input['seed'] = intval($params['seed']);
            if (isset($params['guidance_scale'])) $input['guidance_scale'] = floatval($params['guidance_scale']);

            $result = falGenerate($config['model_id'], $input);
            if (isset($result['images'][0]['url'])) {
                return ['url' => $result['images'][0]['url'], 'format' => 'png'];
            }
            return ['error' => $result['error'] ?? $result['detail'] ?? 'Generation failed'];

        case 'openai':
            if (!OPENAI_API_KEY_AI) return ['error' => 'OpenAI API key not configured'];
            $ch = curl_init('https://api.openai.com/v1/images/generations');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'model' => 'dall-e-3',
                    'prompt' => $prompt,
                    'size' => $params['size'] ?? '1024x1024',
                    'quality' => $params['quality'] ?? 'standard',
                    'style' => $params['style'] ?? 'vivid',
                    'n' => 1,
                ]),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . OPENAI_API_KEY_AI,
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
            ]);
            $response = json_decode(curl_exec($ch), true);
            curl_close($ch);

            if (isset($response['data'][0]['url'])) {
                return ['url' => $response['data'][0]['url'], 'format' => 'png', 'revised_prompt' => $response['data'][0]['revised_prompt'] ?? null];
            }
            return ['error' => $response['error']['message'] ?? 'Generation failed'];

        case 'replicate':
            $result = replicatePredict($config['model_id'], [
                'prompt' => $prompt,
                'negative_prompt' => $params['negative_prompt'] ?? '',
                'width' => intval($params['width'] ?? 1024),
                'height' => intval($params['height'] ?? 1024),
                'num_inference_steps' => min(intval($params['steps'] ?? 30), 50),
                'guidance_scale' => floatval($params['guidance_scale'] ?? 7.5),
            ]);

            $output = $result['output'] ?? null;
            if (is_array($output)) $output = $output[0] ?? null;
            if ($output) return ['url' => $output, 'format' => 'png'];
            return ['error' => $result['error'] ?? 'Generation failed'];
    }

    return ['error' => 'Unknown provider'];
}

function generateVideo($model, $params) {
    global $CREATIVE_MODELS;
    $config = $CREATIVE_MODELS['video'][$model] ?? null;
    if (!$config) return ['error' => "Unknown video model: $model"];

    $input = ['prompt' => $params['prompt'] ?? ''];
    if (isset($params['duration'])) $input['duration'] = min(intval($params['duration']), $config['max_duration']);
    if (isset($params['aspect_ratio'])) $input['aspect_ratio'] = $params['aspect_ratio'];

    if ($config['provider'] === 'fal.ai') {
        $result = falGenerate($config['model_id'], $input);
        $videoUrl = $result['video']['url'] ?? $result['video_url'] ?? null;
        if ($videoUrl) return ['url' => $videoUrl, 'format' => 'mp4'];
        return ['error' => $result['error'] ?? $result['detail'] ?? 'Video generation failed'];
    }

    return ['error' => 'Unknown provider'];
}

function generateMusic($model, $params) {
    global $CREATIVE_MODELS;
    $config = $CREATIVE_MODELS['music'][$model] ?? null;
    if (!$config) return ['error' => "Unknown music model: $model"];

    $result = replicatePredict($config['model_id'], [
        'prompt' => $params['prompt'] ?? '',
        'duration' => min(intval($params['duration'] ?? 15), $config['max_duration']),
        'temperature' => floatval($params['temperature'] ?? 1.0),
        'model_version' => 'large',
    ]);

    $output = $result['output'] ?? null;
    if ($output) return ['url' => $output, 'format' => 'wav'];
    return ['error' => $result['error'] ?? 'Music generation failed'];
}

function generateTTS($model, $params) {
    global $CREATIVE_MODELS;
    $config = $CREATIVE_MODELS['tts'][$model] ?? null;
    if (!$config) return ['error' => "Unknown TTS model: $model"];

    $text = $params['text'] ?? '';
    if (strlen($text) < 2) return ['error' => 'Text too short'];

    switch ($config['provider']) {
        case 'elevenlabs':
            if (!ELEVENLABS_API_KEY) return ['error' => 'ElevenLabs API key not configured'];
            $voiceId = $params['voice_id'] ?? 'EXAVITQu4vr4xnSDxMaL'; // default: Bella
            $ch = curl_init("https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}");
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'text' => substr($text, 0, 5000),
                    'model_id' => $params['model_id'] ?? 'eleven_turbo_v2_5',
                ]),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'xi-api-key: ' . ELEVENLABS_API_KEY,
                    'Accept: audio/mpeg',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
            ]);
            $audio = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200 && $audio) {
                $filename = bin2hex(random_bytes(16)) . '.mp3';
                if (!is_dir(CREATIVE_OUTPUT_DIR)) mkdir(CREATIVE_OUTPUT_DIR, 0750, true);
                file_put_contents(CREATIVE_OUTPUT_DIR . $filename, $audio);
                return ['url' => SITE_URL . '/cache/creative/' . $filename, 'format' => 'mp3'];
            }
            return ['error' => "ElevenLabs error: HTTP $code"];

        case 'openai':
            if (!OPENAI_API_KEY_AI) return ['error' => 'OpenAI API key not configured'];
            $ch = curl_init('https://api.openai.com/v1/audio/speech');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'model' => $params['model'] ?? 'tts-1',
                    'input' => substr($text, 0, 4096),
                    'voice' => $params['voice'] ?? 'alloy',
                    'speed' => max(0.25, min(4.0, floatval($params['speed'] ?? 1.0))),
                ]),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . OPENAI_API_KEY_AI,
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
            ]);
            $audio = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200 && $audio) {
                $filename = bin2hex(random_bytes(16)) . '.mp3';
                if (!is_dir(CREATIVE_OUTPUT_DIR)) mkdir(CREATIVE_OUTPUT_DIR, 0750, true);
                file_put_contents(CREATIVE_OUTPUT_DIR . $filename, $audio);
                return ['url' => SITE_URL . '/cache/creative/' . $filename, 'format' => 'mp3'];
            }
            return ['error' => "OpenAI TTS error: HTTP $code"];

        case 'replicate':
            $result = replicatePredict($config['model_id'], [
                'gen_text' => substr($text, 0, 5000),
            ]);
            $output = $result['output'] ?? null;
            if (is_string($output)) return ['url' => $output, 'format' => 'wav'];
            if (is_array($output) && isset($output['audio_url'])) return ['url' => $output['audio_url'], 'format' => 'wav'];
            return ['error' => $result['error'] ?? 'TTS generation failed'];
    }

    return ['error' => 'Unknown provider'];
}

// ── Router ──────────────────────────────────────────────────────
$action = sanitize($_GET['action'] ?? '', 30);

switch ($action) {

    // ── Generate Image ──────────────────────────────────────────
    case 'image':
        $clientId = requireAuth();
        if (!checkCreativeRateLimit($clientId, 'image')) {
            jsonResponse(['error' => 'Rate limit: max 20 images per hour'], 429);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $model = sanitize($input['model'] ?? 'flux-schnell', 50);
        $prompt = sanitize($input['prompt'] ?? '', 2000);

        $db = getDB();
        ensureCreativeTable();
        $genId = bin2hex(random_bytes(16));
        $startTime = microtime(true);

        $stmt = $db->prepare("INSERT INTO alfred_creative (id, client_id, type, model, prompt, params, status)
            VALUES (?, ?, 'image', ?, ?, ?, 'processing')");
        $stmt->execute([$genId, $clientId, $model, $prompt, json_encode($input)]);

        $result = generateImage($model, $input);
        $elapsed = intval((microtime(true) - $startTime) * 1000);

        if (isset($result['url'])) {
            $stmt = $db->prepare("UPDATE alfred_creative SET status='complete', output_url=?, output_format=?, processing_time_ms=?, completed_at=NOW() WHERE id=?");
            $stmt->execute([$result['url'], $result['format'] ?? 'png', $elapsed, $genId]);

            jsonResponse([
                'success' => true,
                'id' => $genId,
                'type' => 'image',
                'model' => $model,
                'url' => $result['url'],
                'format' => $result['format'] ?? 'png',
                'revised_prompt' => $result['revised_prompt'] ?? null,
                'processing_time_ms' => $elapsed,
            ]);
        } else {
            $stmt = $db->prepare("UPDATE alfred_creative SET status='failed', processing_time_ms=? WHERE id=?");
            $stmt->execute([$elapsed, $genId]);
            jsonResponse(['error' => $result['error'] ?? 'Image generation failed', 'id' => $genId], 500);
        }
        break;

    // ── Generate Video ──────────────────────────────────────────
    case 'video':
        $clientId = requireAuth();
        if (!checkCreativeRateLimit($clientId, 'video')) {
            jsonResponse(['error' => 'Rate limit: max 5 videos per hour'], 429);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $model = sanitize($input['model'] ?? 'kling-v1', 50);

        $db = getDB();
        ensureCreativeTable();
        $genId = bin2hex(random_bytes(16));
        $startTime = microtime(true);

        $stmt = $db->prepare("INSERT INTO alfred_creative (id, client_id, type, model, prompt, params, status)
            VALUES (?, ?, 'video', ?, ?, ?, 'processing')");
        $stmt->execute([$genId, $clientId, $model, $input['prompt'] ?? '', json_encode($input)]);

        $result = generateVideo($model, $input);
        $elapsed = intval((microtime(true) - $startTime) * 1000);

        if (isset($result['url'])) {
            $stmt = $db->prepare("UPDATE alfred_creative SET status='complete', output_url=?, output_format=?, processing_time_ms=?, completed_at=NOW() WHERE id=?");
            $stmt->execute([$result['url'], $result['format'] ?? 'mp4', $elapsed, $genId]);

            jsonResponse(['success' => true, 'id' => $genId, 'url' => $result['url'], 'format' => $result['format'] ?? 'mp4', 'processing_time_ms' => $elapsed]);
        } else {
            $stmt = $db->prepare("UPDATE alfred_creative SET status='failed', processing_time_ms=? WHERE id=?");
            $stmt->execute([$elapsed, $genId]);
            jsonResponse(['error' => $result['error'] ?? 'Video generation failed', 'id' => $genId], 500);
        }
        break;

    // ── Generate Music ──────────────────────────────────────────
    case 'music':
        $clientId = requireAuth();
        if (!checkCreativeRateLimit($clientId, 'music')) {
            jsonResponse(['error' => 'Rate limit: max 10 tracks per hour'], 429);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $model = sanitize($input['model'] ?? 'musicgen', 50);

        $db = getDB();
        ensureCreativeTable();
        $genId = bin2hex(random_bytes(16));
        $startTime = microtime(true);

        $stmt = $db->prepare("INSERT INTO alfred_creative (id, client_id, type, model, prompt, params, status)
            VALUES (?, ?, 'music', ?, ?, ?, 'processing')");
        $stmt->execute([$genId, $clientId, $model, $input['prompt'] ?? '', json_encode($input)]);

        $result = generateMusic($model, $input);
        $elapsed = intval((microtime(true) - $startTime) * 1000);

        if (isset($result['url'])) {
            $stmt = $db->prepare("UPDATE alfred_creative SET status='complete', output_url=?, output_format=?, processing_time_ms=?, completed_at=NOW() WHERE id=?");
            $stmt->execute([$result['url'], $result['format'] ?? 'wav', $elapsed, $genId]);

            jsonResponse(['success' => true, 'id' => $genId, 'url' => $result['url'], 'format' => $result['format'] ?? 'wav', 'processing_time_ms' => $elapsed]);
        } else {
            $stmt = $db->prepare("UPDATE alfred_creative SET status='failed', processing_time_ms=? WHERE id=?");
            $stmt->execute([$elapsed, $genId]);
            jsonResponse(['error' => $result['error'] ?? 'Music generation failed', 'id' => $genId], 500);
        }
        break;

    // ── Text-to-Speech ──────────────────────────────────────────
    case 'tts':
        $clientId = requireAuth();
        if (!checkCreativeRateLimit($clientId, 'tts')) {
            jsonResponse(['error' => 'Rate limit: max 30 TTS per hour'], 429);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $model = sanitize($input['model'] ?? 'openai-tts', 50);

        $db = getDB();
        ensureCreativeTable();
        $genId = bin2hex(random_bytes(16));
        $startTime = microtime(true);

        $stmt = $db->prepare("INSERT INTO alfred_creative (id, client_id, type, model, prompt, params, status)
            VALUES (?, ?, 'tts', ?, ?, ?, 'processing')");
        $stmt->execute([$genId, $clientId, $model, substr($input['text'] ?? '', 0, 200), json_encode($input)]);

        $result = generateTTS($model, $input);
        $elapsed = intval((microtime(true) - $startTime) * 1000);

        if (isset($result['url'])) {
            $stmt = $db->prepare("UPDATE alfred_creative SET status='complete', output_url=?, output_format=?, processing_time_ms=?, completed_at=NOW() WHERE id=?");
            $stmt->execute([$result['url'], $result['format'] ?? 'mp3', $elapsed, $genId]);

            jsonResponse(['success' => true, 'id' => $genId, 'url' => $result['url'], 'format' => $result['format'] ?? 'mp3', 'processing_time_ms' => $elapsed]);
        } else {
            $stmt = $db->prepare("UPDATE alfred_creative SET status='failed', processing_time_ms=? WHERE id=?");
            $stmt->execute([$elapsed, $genId]);
            jsonResponse(['error' => $result['error'] ?? 'TTS failed', 'id' => $genId], 500);
        }
        break;

    // ── List models ─────────────────────────────────────────────
    case 'models':
        $type = sanitize($_GET['type'] ?? '', 20);
        if ($type && isset($CREATIVE_MODELS[$type])) {
            jsonResponse(['success' => true, 'type' => $type, 'models' => $CREATIVE_MODELS[$type]]);
        }
        jsonResponse(['success' => true, 'models' => $CREATIVE_MODELS]);
        break;

    // ── Generation history ──────────────────────────────────────
    case 'history':
        $clientId = requireAuth();
        $type = sanitize($_GET['type'] ?? '', 20);
        $limit = min(intval($_GET['limit'] ?? 20), 50);

        $db = getDB();
        ensureCreativeTable();

        $sql = "SELECT id, type, model, prompt, status, output_url, output_format, processing_time_ms, created_at
            FROM alfred_creative WHERE client_id = ?";
        $params = [$clientId];

        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $db->prepare($sql);
        dbExecute($stmt, $params);

        jsonResponse(['success' => true, 'generations' => $stmt->fetchAll()]);
        break;

    // ── Check status ────────────────────────────────────────────
    case 'status':
        $id = sanitize($_GET['id'] ?? '', 32);
        if (!$id) jsonResponse(['error' => 'id required'], 400);

        $db = getDB();
        ensureCreativeTable();
        $stmt = $db->prepare("SELECT id, type, model, status, output_url, output_format, processing_time_ms, created_at, completed_at FROM alfred_creative WHERE id = ?");
        $stmt->execute([$id]);
        $gen = $stmt->fetch();

        if (!$gen) jsonResponse(['error' => 'Not found'], 404);
        jsonResponse(['success' => true, 'generation' => $gen]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action. Use: image, video, music, tts, models, history, status'], 400);
}
