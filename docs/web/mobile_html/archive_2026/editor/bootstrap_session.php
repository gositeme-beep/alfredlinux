<?php
/**
 * GoCodeMe Editor - Session bootstrap
 * Share login session with the billing portal.
 * This must be included BEFORE any session_start() or output.
 */
if (!defined('GOCODEME_EDITOR')) {
    define('GOCODEME_EDITOR', true);
}

// Start session if not already started (shares cookies with billing portal)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
define('EDITOR_SESSION_LOADED', true);
