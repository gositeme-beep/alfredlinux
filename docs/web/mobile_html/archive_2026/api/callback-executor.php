<?php
/**
 * Callback Executor — Cron job to fire pending callbacks
 * Runs every minute: * * * * * php /home/gositeme/domains/gositeme.com/public_html/api/callback-executor.php
 * Catches any callbacks that the webhook end-of-call handler missed
 */
define("GOSITEME_API", true);
require_once __DIR__ . "/config.php";

$db = getDB();
if (!$db) exit(1);

// Find pending callbacks that are older than 5 seconds but not expired
$stmt = $db->query("SELECT callback_id FROM alfred_callbacks 
    WHERE callback_status = 'pending' 
    AND requested_at < DATE_SUB(NOW(), INTERVAL 5 SECOND)
    AND (expires_at IS NULL OR expires_at > NOW())
    ORDER BY requested_at ASC LIMIT 3");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows)) exit(0);

// Include the webhook for the executeCallback function
require_once __DIR__ . "/vapi-webhook.php";

foreach ($rows as $row) {
    error_log("Callback executor: firing " . $row["callback_id"]);
    executeCallback($row["callback_id"], $db);
}
