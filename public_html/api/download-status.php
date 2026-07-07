<?php
/**
 * Hard status for operators, scripts, and uptime checks — no secrets.
 * GET only. JSON.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once dirname(__DIR__) . '/includes/ga-release-state.php';

$gaLive = !empty($finalGaIsoPublished) && !empty($gaP2pDownloadsEnabled);

echo json_encode([
    'ga_iso_marked_published' => (bool) $finalGaIsoPublished,
    'p2p_magnet_torrent_live' => (bool) $gaLive,
    'download_page_countdown_enabled' => (bool) ($downloadPageShowLaunchCountdown ?? false),
    'message' => $gaLive
        ? 'GA P2P offer is live on /download (verify swarm + hashes yourself).'
        : 'GA P2P offer is OFF. Flip flags in includes/ga-release-state.php only after Omahon bar — no release date implied by this endpoint.',
], JSON_UNESCAPED_SLASHES);
