<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
require_once __DIR__ . '/../../includes/path-guard.inc.php';
require_once __DIR__ . '/../../includes/db-config.inc.php';

$bearer = '';
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
if (preg_match('/Bearer\s+([\w\-\.]+)/i', $auth, $m)) $bearer = $m[1];
if ($bearer === '') { http_response_code(401); echo json_encode(['error' => 'unauthorized'], gositeme_json_public_encode_flags()); exit; }

$db = getSharedDB();
$h = hash('sha256', $bearer);
$st = $db->prepare(
    "SELECT t.user_id, c.firstname, c.lastname, c.email
     FROM alfred_oauth_tokens t JOIN clients c ON c.id = t.user_id
     WHERE t.access_token = ? AND t.revoked_at IS NULL
       AND (t.expires_at IS NULL OR t.expires_at > NOW())
     LIMIT 1"
);
$st->execute([$h]);
$r = $st->fetch(PDO::FETCH_ASSOC);
if (!$r) { http_response_code(401); echo json_encode(['error' => 'invalid_token'], gositeme_json_public_encode_flags()); exit; }

echo json_encode([
    'sub'                => (string)$r['user_id'],
    'name'               => trim(($r['firstname'] ?? '') . ' ' . ($r['lastname'] ?? '')),
    'given_name'         => $r['firstname'] ?? '',
    'family_name'        => $r['lastname']  ?? '',
    'email'              => $r['email'] ?? '',
    'email_verified'     => true,
    'preferred_username' => $r['email'] ?? ('user' . $r['user_id']),
], gositeme_json_public_encode_flags());