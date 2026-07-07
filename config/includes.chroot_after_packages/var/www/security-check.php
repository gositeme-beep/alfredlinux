<?php
/**
 * Legacy alias — some clients/bookmarks use security-check.php.
 * Canonical vault UI is security-unlock.php (same query string preserved).
 */
$qs = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
    ? ('?' . $_SERVER['QUERY_STRING'])
    : '';
header('Location: /security-unlock.php' . $qs, true, 302);
exit;
