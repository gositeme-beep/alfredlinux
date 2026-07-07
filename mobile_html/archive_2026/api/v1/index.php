<?php
/**
 * Alfred API v1 — Entry Point
 * 
 * This file exists for direct access compatibility (e.g., /api/v1/index.php?route=tools).
 * The .htaccess routes all clean URLs to router.php, which contains the full API framework.
 * 
 * @version 1.0.0
 * @since   2026-03-04
 */

require_once __DIR__ . '/router.php';
