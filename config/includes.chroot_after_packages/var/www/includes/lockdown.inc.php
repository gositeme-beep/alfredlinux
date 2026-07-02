<?php
/**
 * EMERGENCY LOCKDOWN — commander (client_id=33) and approved Brian accounts may sign in / be signed in.
 * Toggle by deleting this file or setting LOCKDOWN_ENABLED to false.
 */
const LOCKDOWN_ENABLED = true;
const LOCKDOWN_ALLOW   = [33, 37, 30];          // client_ids that may pass
const LOCKDOWN_MESSAGE = 'GoSiteMe is temporarily restricted while we screen recent sign-in activity. Please check back shortly.';

require_once __DIR__ . '/path-guard.inc.php';

function lockdown_block_response(): void {
    if (!headers_sent()) {
        http_response_code(503);
        header('Retry-After: 3600');
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!doctype html><meta charset=utf-8><title>Maintenance — GoSiteMe</title>';
    echo '<style>body{background:#0a0a12;color:#e8ecf4;font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:2rem;text-align:center}.b{max-width:480px}.h{font-size:1.4rem;margin-bottom:12px;color:#00d4ff}.m{color:#9aa3c7;line-height:1.6;font-size:.95rem}</style>';
    echo '<div class=b><div class=h>Sign-in temporarily restricted</div><div class=m>' . htmlspecialchars(LOCKDOWN_MESSAGE) . '</div></div>';
    exit;
}

function lockdown_block_api(string $msg = null): void {
    if (!headers_sent()) {
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, private');
        header('Pragma: no-cache');
    }
    echo json_encode(['success' => false, 'error' => $msg ?: LOCKDOWN_MESSAGE, 'lockdown' => true], root_json_public_encode_flags());
    exit;
}
