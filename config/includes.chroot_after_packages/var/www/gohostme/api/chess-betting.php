<?php
/**
 * LEGACY SHIM — chess-betting.php
 * All functionality consolidated into universal-betting.php.
 * Action aliases: create-wager → place-wager, get-wagers → wager-history,
 * get-active → active-wager, get-balance → get-balance (all in universal-betting).
 * Chess-Masters JS clients that call ?action=create-wager etc. will work unchanged.
 */
require_once __DIR__ . '/universal-betting.php';
