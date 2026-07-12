<?php
/**
 * GoSiteMe Conference Room API — LiveKit Integration
 * Manages rooms, participants, and access tokens for LiveKit
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

// LiveKit Configuration
define('LIVEKIT_HOST', 'http://127.0.0.1:7880');
define('LIVEKIT_API_KEY', 'APIGCw6xqbGY8K3');
define('LIVEKIT_API_SECRET', 'XgDGv5eW7vRkSD4EgTKoplVgyBmeT3p0QHQIaZmHrzi');
define('LIVEKIT_WS_URL', 'wss://gositeme.com/livekit-ws');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

session_start();
$isLoggedIn = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
$clientId   = $isLoggedIn ? (int)$_SESSION['client_id'] : 0;
$clientName = $_SESSION['client_name'] ?? 'Guest';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'create_room':
        requireAuth();
        createRoom();
        break;
    case 'join_room':
        requireAuth();
        joinRoom();
        break;
    case 'list_rooms':
        requireAuth();
        listRooms();
        break;
    case 'delete_room':
        requireAuth();
        deleteRoom();
        break;
    case 'participants':
        requireAuth();
        listParticipants();
        break;
    case 'token':
        requireAuth();
        generateToken();
        break;
    default:
        jsonResponse(['error' => 'Unknown action', 'actions' => [
            'create_room', 'join_room', 'list_rooms', 'delete_room', 'participants', 'token'
        ]], 400);
}

function requireAuth() {
    global $isLoggedIn;
    if (!$isLoggedIn) {
        jsonResponse(['error' => 'Authentication required'], 401);
        exit;
    }
}

/**
 * Create a LiveKit room
 */
function createRoom() {
    global $clientId, $clientName;
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    
    $roomName = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['name'] ?? '');
    if (empty($roomName)) {
        jsonResponse(['error' => 'Room name required (alphanumeric, hyphens, underscores)'], 400);
        return;
    }
    
    $maxParticipants = min((int)($input['max_participants'] ?? 20), 50);
    $emptyTimeout    = min((int)($input['empty_timeout'] ?? 300), 3600);
    
    $body = json_encode([
        'name' => $roomName,
        'empty_timeout' => $emptyTimeout,
        'max_participants' => $maxParticipants,
        'metadata' => json_encode([
            'created_by' => $clientId,
            'created_by_name' => $clientName,
            'created_at' => date('c'),
            'description' => substr($input['description'] ?? '', 0, 500)
        ])
    ]);
    
    $result = livekitApiCall('/twirp/livekit.RoomService/CreateRoom', $body);
    
    // Generate a join token for the creator
    $token = generateAccessToken($roomName, $clientName, $clientId, true);
    
    jsonResponse([
        'success' => true,
        'room' => $result,
        'token' => $token,
        'ws_url' => LIVEKIT_WS_URL
    ]);
}

/**
 * Join an existing room
 */
function joinRoom() {
    global $clientId, $clientName;
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    
    $roomName = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['room'] ?? '');
    if (empty($roomName)) {
        jsonResponse(['error' => 'Room name required'], 400);
        return;
    }
    
    $displayName = substr($input['display_name'] ?? $clientName, 0, 100);
    
    $token = generateAccessToken($roomName, $displayName, $clientId, false);
    
    jsonResponse([
        'success' => true,
        'token' => $token,
        'ws_url' => LIVEKIT_WS_URL,
        'room' => $roomName
    ]);
}

/**
 * List active rooms
 */
function listRooms() {
    $result = livekitApiCall('/twirp/livekit.RoomService/ListRooms', '{}');
    jsonResponse(['success' => true, 'rooms' => $result['rooms'] ?? []]);
}

/**
 * Delete a room
 */
function deleteRoom() {
    global $clientId;
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    
    $roomName = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['room'] ?? '');
    if (empty($roomName)) {
        jsonResponse(['error' => 'Room name required'], 400);
        return;
    }
    
    $result = livekitApiCall('/twirp/livekit.RoomService/DeleteRoom', json_encode([
        'room' => $roomName
    ]));
    
    jsonResponse(['success' => true, 'deleted' => $roomName]);
}

/**
 * List participants in a room
 */
function listParticipants() {
    $roomName = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['room'] ?? '');
    if (empty($roomName)) {
        jsonResponse(['error' => 'Room name required'], 400);
        return;
    }
    
    $result = livekitApiCall('/twirp/livekit.RoomService/ListParticipants', json_encode([
        'room' => $roomName
    ]));
    
    jsonResponse(['success' => true, 'participants' => $result['participants'] ?? []]);
}

/**
 * Generate a join token (standalone endpoint)
 */
function generateToken() {
    global $clientId, $clientName;
    $input = json_decode(file_get_contents('php://input'), true) ?: $_GET;
    
    $roomName = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['room'] ?? '');
    if (empty($roomName)) {
        jsonResponse(['error' => 'Room name required'], 400);
        return;
    }
    
    $token = generateAccessToken($roomName, $clientName, $clientId, false);
    
    jsonResponse([
        'success' => true,
        'token' => $token,
        'ws_url' => LIVEKIT_WS_URL
    ]);
}

/**
 * Generate a LiveKit access token (JWT)
 * LiveKit tokens are JWTs signed with the API secret
 */
function generateAccessToken(string $room, string $identity, int $clientId, bool $isAdmin = false): string {
    $header = base64UrlEncode(json_encode([
        'alg' => 'HS256',
        'typ' => 'JWT'
    ]));
    
    $now = time();
    $grants = [
        'roomJoin' => true,
        'room' => $room,
        'canPublish' => true,
        'canSubscribe' => true,
        'canPublishData' => true,
    ];
    
    if ($isAdmin) {
        $grants['roomAdmin'] = true;
        $grants['roomCreate'] = true;
    }
    
    $payload = base64UrlEncode(json_encode([
        'iss' => LIVEKIT_API_KEY,
        'sub' => 'client_' . $clientId,
        'name' => $identity,
        'iat' => $now,
        'nbf' => $now,
        'exp' => $now + 86400, // 24 hours
        'jti' => 'client_' . $clientId . '_' . bin2hex(random_bytes(8)),
        'video' => $grants,
        'metadata' => json_encode(['client_id' => $clientId])
    ]));
    
    $signature = base64UrlEncode(
        hash_hmac('sha256', "$header.$payload", LIVEKIT_API_SECRET, true)
    );
    
    return "$header.$payload.$signature";
}

/**
 * Make an authenticated API call to LiveKit
 */
function livekitApiCall(string $path, string $body): array {
    $header = base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $now = time();
    $payload = base64UrlEncode(json_encode([
        'iss' => LIVEKIT_API_KEY,
        'sub' => LIVEKIT_API_KEY,
        'iat' => $now,
        'nbf' => $now,
        'exp' => $now + 60,
        'jti' => bin2hex(random_bytes(8)),
        'video' => [
            'roomCreate' => true,
            'roomList' => true,
            'roomAdmin' => true,
        ]
    ]));
    $sig = base64UrlEncode(hash_hmac('sha256', "$header.$payload", LIVEKIT_API_SECRET, true));
    $jwt = "$header.$payload.$sig";
    
    $ch = curl_init(LIVEKIT_HOST . $path);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $jwt
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 400) {
        error_log("LiveKit API error ($httpCode): $response");
        jsonResponse(['error' => 'LiveKit API error', 'code' => $httpCode], 502);
        exit;
    }
    
    return json_decode($response, true) ?: [];
}

/**
 * Base64 URL-safe encode
 */
function base64UrlEncode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
