<?php
/**
 * Alfred Mobile — OTA Update API
 * Serves firmware updates to Alfred Mobile devices
 * 
 * Endpoints:
 *   GET /ota/?action=check&device=DEVICE&version=CURRENT_VER
 *   GET /ota/?action=changelog&device=DEVICE
 *   GET /ota/?action=download&device=DEVICE&version=TARGET_VER
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-cache');

$action  = $_GET['action'] ?? 'check';
$device  = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device'] ?? '');
$version = preg_replace('/[^a-zA-Z0-9._-]/', '', $_GET['version'] ?? '');

// OTA builds directory
$otaDir = __DIR__ . '/builds/';

switch ($action) {
    case 'check':
        // Check for available updates
        if (!$device) {
            http_response_code(400);
            echo json_encode(['error' => 'Device codename required']);
            break;
        }
        
        $manifest = $otaDir . $device . '/latest.json';
        if (!file_exists($manifest)) {
            echo json_encode([
                'update_available' => false,
                'device' => $device,
                'current_version' => $version,
                'message' => 'No builds available for this device yet. Alfred Mobile is in development.'
            ]);
            break;
        }
        
        $latest = json_decode(file_get_contents($manifest), true);
        $updateAvailable = version_compare($latest['version'] ?? '0', $version, '>');
        
        echo json_encode([
            'update_available' => $updateAvailable,
            'device' => $device,
            'current_version' => $version,
            'latest_version' => $latest['version'] ?? null,
            'download_size' => $latest['size'] ?? null,
            'checksum_sha256' => $latest['sha256'] ?? null,
            'changelog' => $latest['changelog'] ?? '',
            'download_url' => $updateAvailable ? "https://alfred-mobile.com/ota/builds/{$device}/" . ($latest['filename'] ?? '') : null
        ]);
        break;
    
    case 'changelog':
        if (!$device) {
            http_response_code(400);
            echo json_encode(['error' => 'Device codename required']);
            break;
        }
        
        $changelogFile = $otaDir . $device . '/changelog.json';
        if (file_exists($changelogFile)) {
            echo file_get_contents($changelogFile);
        } else {
            echo json_encode([
                'device' => $device,
                'entries' => [],
                'message' => 'No changelog entries yet.'
            ]);
        }
        break;
    
    case 'devices':
        // List supported devices
        echo json_encode([
            'supported_devices' => [
                ['codename' => 'lynx', 'name' => 'Google Pixel 7a', 'status' => 'planned'],
                ['codename' => 'husky', 'name' => 'Google Pixel 8 Pro', 'status' => 'planned'],
                ['codename' => 'caiman', 'name' => 'Google Pixel 9', 'status' => 'planned'],
                ['codename' => 'salami', 'name' => 'OnePlus 11', 'status' => 'planned'],
                ['codename' => 'a55x', 'name' => 'Samsung Galaxy A55', 'status' => 'planned'],
            ],
            'base' => 'LineageOS 21 (Android 14)',
            'project' => 'Alfred Mobile',
            'version' => '1.0.0-dev'
        ]);
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action', 'available' => ['check', 'changelog', 'devices']]);
}
