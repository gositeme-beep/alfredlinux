<?php
/**
 * Sites-created counter for homepage floating card.
 * Value changes every hour, with natural-looking variation (up/down) e.g. 12, 14, 13, 17, 11...
 * Campaign starts on first page load (saved in cache). Then:
 * 3 months → trend to 100, next 6 months → trend to 1000, then email goal.
 */

define('SITES_COUNT_CACHE_DIR', __DIR__ . '/../cache');
define('SITES_COUNT_START_FILE', SITES_COUNT_CACHE_DIR . '/.sites_count_campaign_start');
define('SITES_COUNT_HOURS_PHASE1', 2160);   // 3 months = 90 * 24
define('SITES_COUNT_HOURS_PHASE2', 6480);   // 9 months total (3 + 6)
define('SITES_COUNT_GOAL_EMAIL_SENT_FILE', SITES_COUNT_CACHE_DIR . '/.sites_goal_email_sent');

/**
 * Campaign start timestamp. Set on first run so we always start at ~12 and grow over 9 months.
 */
function _sites_count_campaign_start_ts() {
    $file = SITES_COUNT_START_FILE;
    if (file_exists($file) && is_readable($file)) {
        $ts = (int) trim((string) file_get_contents($file));
        if ($ts > 0) {
            return $ts;
        }
    }
    $now = time();
    if (is_writable(SITES_COUNT_CACHE_DIR)) {
        @file_put_contents($file, (string) $now);
    }
    return $now;
}

/**
 * Returns the "sites created this hour" number for the current hour.
 * Deterministic per hour: same hour = same value. Grows with wobble over time.
 *
 * Phase 1 (0–3 months): trend 12 → 100
 * Phase 2 (3–9 months): trend 100 → 1000
 * Phase 3 (9+ months): 1000, and trigger goal email once
 */
function get_sites_created_this_hour() {
    $start = _sites_count_campaign_start_ts();
    $now   = time();
    if ($now < $start) {
        return 12;
    }
    $hourIndex = (int) floor(($now - $start) / 3600);

    // Phase 3: 9+ months → 1000
    if ($hourIndex >= SITES_COUNT_HOURS_PHASE2) {
        maybe_send_goal_email();
        return 1000;
    }

    // Deterministic wobble (same hour = same number)
    $wobble = (int) round(
        sin($hourIndex * 0.7) * 4 +
        sin($hourIndex * 1.3) * 3 +
        sin($hourIndex * 0.31) * 2
    );

    // Phase 1: 0–2160 hours, 12 → 100
    if ($hourIndex < SITES_COUNT_HOURS_PHASE1) {
        $progress = $hourIndex / SITES_COUNT_HOURS_PHASE1;
        $base     = 12 + (100 - 12) * $progress;
        $value    = (int) round($base + $wobble);
        return (int) max(8, min(100, $value));
    }

    // Phase 2: 2160–6480 hours, 100 → 1000
    $phase2Progress = ($hourIndex - SITES_COUNT_HOURS_PHASE1) / (SITES_COUNT_HOURS_PHASE2 - SITES_COUNT_HOURS_PHASE1);
    $base  = 100 + (1000 - 100) * $phase2Progress;
    $value = (int) round($base + $wobble * 2);
    return (int) max(90, min(1000, $value));
}

/**
 * Sends the "we've reached our goal" email once when we pass 9 months.
 */
function maybe_send_goal_email() {
    $sentFile = SITES_COUNT_GOAL_EMAIL_SENT_FILE;
    if (file_exists($sentFile)) {
        return;
    }
    $to      = 'admin@soundstudiopro.com';
    $subject = 'GoSiteMe – We\'ve reached our goal!';
    $body    = "Hi,\n\nWe've reached our goal: 1000 sites created in the last hour!\n\n"
             . "The homepage counter has been running for 9 months (3 months to 100, 6 months to 1000).\n\n"
             . "Congratulations!\n— GoSiteMe";
    $headers = 'From: noreply@root.com' . "\r\n" . 'Reply-To: noreply@root.com' . "\r\n";
    $ok      = @mail($to, $subject, $body, $headers);
    if ($ok || !is_writable(dirname($sentFile))) {
        @file_put_contents($sentFile, date('c') . "\n");
    }
}
