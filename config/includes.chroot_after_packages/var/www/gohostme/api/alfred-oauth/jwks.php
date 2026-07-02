<?php

require_once __DIR__ . '/../../includes/path-guard.inc.php';

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, private');
    header('Pragma: no-cache');
}
echo json_encode(['keys' => []], gositeme_json_public_encode_flags());
