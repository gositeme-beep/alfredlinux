<?php
/**
 * LEGACY SHIM — game-betting.php
 * All functionality consolidated into universal-betting.php.
 * Action aliases (tournament-entry, agent-bet, etc.) are preserved there.
 * This file forwards seamlessly so existing clients keep working.
 */
require_once __DIR__ . '/universal-betting.php';
