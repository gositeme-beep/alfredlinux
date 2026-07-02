<?php
/**
 * Veil Status API — Returns access log and system status
 * Used by /veil/command-center.php
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

session_start();
$clientId = $_SESSION['client_id'] ?? 0;

// Owner-only
if ((int)$clientId !== 33) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

header('Content-Type: application/json');

try {
    $db = getDB();
    
    // Create table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS veil_access_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        action VARCHAR(50) NOT NULL DEFAULT 'access',
        channel VARCHAR(100) DEFAULT '',
        details TEXT DEFAULT NULL,
        ip_address VARCHAR(45) DEFAULT '',
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_timestamp (timestamp)
    )");
    
    // Log this access
    $stmt = $db->prepare("INSERT INTO veil_access_log (client_id, action, channel, ip_address) VALUES (?, 'success', 'command-center', ?)");
    $stmt->execute([$clientId, $_SERVER['REMOTE_ADDR'] ?? '']);
    
    // Get recent log entries
    $stmt = $db->prepare("SELECT action, channel, timestamp FROM veil_access_log ORDER BY timestamp DESC LIMIT 20");
    $stmt->execute();
    $log = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format timestamps
    foreach ($log as &$entry) {
        $ts = strtotime($entry['timestamp']);
        $diff = time() - $ts;
        if ($diff < 60) $entry['timestamp'] = 'just now';
        elseif ($diff < 3600) $entry['timestamp'] = floor($diff / 60) . 'm ago';
        elseif ($diff < 86400) $entry['timestamp'] = floor($diff / 3600) . 'h ago';
        else $entry['timestamp'] = date('M j, g:ia', $ts);
    }
    
    echo json_encode(['success' => true, 'log' => $log]);
    
} catch (Exception $e) {
    error_log('Veil status error: ' . $e->getMessage());
    echo json_encode(['success' => true, 'log' => []]);
}
