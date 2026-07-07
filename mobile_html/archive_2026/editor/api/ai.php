<?php
/**
 * GoCodeMe Editor - AI Code Generation API
 * Handles AI-powered website generation
 */

require_once dirname(__DIR__) . '/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth.php';

// CORS headers
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Check authentication and AI limits
$user = checkPermission('use_ai');
if (!$user) {
    jsonResponse([
        'error' => 'AI limit reached or login required',
        'login_url' => BILLING_URL . '/clientarea.php'
    ], 403);
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$prompt = trim($input['prompt'] ?? '');
$projectId = $input['project_id'] ?? null;
$action = $input['action'] ?? 'generate'; // generate, modify, explain

if (empty($prompt)) {
    jsonResponse(['error' => 'Prompt is required'], 400);
}

if (strlen($prompt) > 5000) {
    jsonResponse(['error' => 'Prompt too long (max 5000 characters)'], 400);
}

/**
 * Call OpenAI API
 */
function callOpenAI($systemPrompt, $userPrompt) {
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    
    $data = [
        'model' => OPENAI_MODEL,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ],
        'max_tokens' => OPENAI_MAX_TOKENS,
        'temperature' => 0.7
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY
        ],
        CURLOPT_TIMEOUT => 120
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("API request failed: $error");
    }
    
    $result = json_decode($response, true);
    
    if ($httpCode !== 200) {
        $errorMsg = $result['error']['message'] ?? 'Unknown API error';
        throw new Exception("API error: $errorMsg");
    }
    
    return [
        'content' => $result['choices'][0]['message']['content'] ?? '',
        'tokens' => $result['usage']['total_tokens'] ?? 0
    ];
}

/**
 * Call Anthropic Claude API
 */
function callAnthropic($systemPrompt, $userPrompt) {
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    
    $data = [
        'model' => ANTHROPIC_MODEL,
        'max_tokens' => OPENAI_MAX_TOKENS,
        'system' => $systemPrompt,
        'messages' => [
            ['role' => 'user', 'content' => $userPrompt]
        ]
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . ANTHROPIC_API_KEY,
            'anthropic-version: 2023-06-01'
        ],
        CURLOPT_TIMEOUT => 120
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("API request failed: $error");
    }
    
    $result = json_decode($response, true);
    
    if ($httpCode !== 200) {
        $errorMsg = $result['error']['message'] ?? 'Unknown API error';
        throw new Exception("API error: $errorMsg");
    }
    
    return [
        'content' => $result['content'][0]['text'] ?? '',
        'tokens' => ($result['usage']['input_tokens'] ?? 0) + ($result['usage']['output_tokens'] ?? 0)
    ];
}

/**
 * Parse AI response to extract code blocks
 */
function parseCodeResponse($content) {
    $result = [
        'html' => '',
        'css' => '',
        'js' => '',
        'message' => ''
    ];
    
    // Extract HTML
    if (preg_match('/```html\s*([\s\S]*?)```/i', $content, $matches)) {
        $result['html'] = trim($matches[1]);
    } elseif (preg_match('/```(?:markup)?\s*(<!DOCTYPE[\s\S]*?<\/html>)\s*```/i', $content, $matches)) {
        $result['html'] = trim($matches[1]);
    }
    
    // Extract CSS
    if (preg_match('/```css\s*([\s\S]*?)```/i', $content, $matches)) {
        $result['css'] = trim($matches[1]);
    }
    
    // Extract JavaScript
    if (preg_match('/```(?:javascript|js)\s*([\s\S]*?)```/i', $content, $matches)) {
        $result['js'] = trim($matches[1]);
    }
    
    // Extract message (non-code text)
    $message = preg_replace('/```[\s\S]*?```/', '', $content);
    $result['message'] = trim($message);
    
    // If no separate CSS/JS found, check if they're embedded in HTML
    if (empty($result['css']) && !empty($result['html'])) {
        if (preg_match('/<style[^>]*>([\s\S]*?)<\/style>/i', $result['html'], $matches)) {
            $result['css'] = trim($matches[1]);
        }
    }
    
    if (empty($result['js']) && !empty($result['html'])) {
        if (preg_match('/<script[^>]*>([\s\S]*?)<\/script>/i', $result['html'], $matches)) {
            $result['js'] = trim($matches[1]);
        }
    }
    
    return $result;
}

// Build the system prompt
$systemPrompt = <<<'PROMPT'
You are GoCodeMe, an expert AI web developer that creates beautiful, modern websites. 

When generating code:
1. Create complete, working HTML5 documents
2. Use modern CSS with flexbox/grid, gradients, shadows, and smooth animations
3. Make designs responsive (mobile-first)
4. Use professional color schemes and typography
5. Include smooth hover effects and transitions
6. Write clean, well-commented JavaScript when needed

Output format - ALWAYS provide code in THREE separate code blocks:
1. ```html - Complete HTML document
2. ```css - All styles (will be linked as styles.css)
3. ```javascript - Any scripts (will be linked as script.js)

Style guidelines:
- Modern, clean aesthetic
- Good contrast and readability
- Professional spacing and layout
- Subtle animations (not overwhelming)
- Mobile-responsive design

If the user asks to modify existing code, make only the requested changes while preserving the rest.
PROMPT;

// Modify system prompt based on action
if ($action === 'modify' && !empty($input['current_code'])) {
    $systemPrompt .= "\n\nCurrent code to modify:\n";
    $systemPrompt .= "HTML:\n" . ($input['current_code']['html'] ?? '') . "\n";
    $systemPrompt .= "CSS:\n" . ($input['current_code']['css'] ?? '') . "\n";
    $systemPrompt .= "JS:\n" . ($input['current_code']['js'] ?? '') . "\n";
}

try {
    // Call AI API
    if (AI_PROVIDER === 'anthropic') {
        $response = callAnthropic($systemPrompt, $prompt);
    } else {
        $response = callOpenAI($systemPrompt, $prompt);
    }
    
    // Parse the response
    $parsed = parseCodeResponse($response['content']);
    
    // Log AI usage
    $pdo = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO editor_ai_history 
        (user_id, project_id, prompt, response, model, tokens_used, status)
        VALUES (?, ?, ?, ?, ?, ?, 'success')
    ");
    $stmt->execute([
        $user['id'],
        $projectId,
        $prompt,
        json_encode($parsed),
        AI_PROVIDER === 'anthropic' ? ANTHROPIC_MODEL : OPENAI_MODEL,
        $response['tokens']
    ]);
    
    // Update usage counter
    $stmt = $pdo->prepare("
        INSERT INTO editor_user_settings (user_id, ai_used_this_month)
        VALUES (?, 1)
        ON DUPLICATE KEY UPDATE ai_used_this_month = ai_used_this_month + 1
    ");
    $stmt->execute([$user['id']]);
    
    // Calculate remaining AI uses
    $aiRemaining = $user['ai_limit'] - $user['ai_used'] - 1;
    
    jsonResponse([
        'success' => true,
        'code' => [
            'html' => $parsed['html'],
            'css' => $parsed['css'],
            'js' => $parsed['js']
        ],
        'message' => $parsed['message'] ?: "I've generated your website! Check the preview and let me know if you'd like any changes.",
        'tokens_used' => $response['tokens'],
        'ai_remaining' => max(0, $aiRemaining)
    ]);
    
} catch (Exception $e) {
    // Log error
    $pdo = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO editor_ai_history 
        (user_id, project_id, prompt, model, status, error_message)
        VALUES (?, ?, ?, ?, 'error', ?)
    ");
    $stmt->execute([
        $user['id'],
        $projectId,
        $prompt,
        AI_PROVIDER === 'anthropic' ? ANTHROPIC_MODEL : OPENAI_MODEL,
        $e->getMessage()
    ]);
    
    jsonResponse([
        'error' => DEBUG_MODE ? $e->getMessage() : 'AI generation failed. Please try again.',
        'success' => false
    ], 500);
}
