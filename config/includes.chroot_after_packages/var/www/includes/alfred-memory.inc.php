<?php
/**
 * Alfred Unified Memory — PHP Helper
 *
 * Cross-instance context layer for Widget Alfred.
 * Reads/writes the same Redis keys as the Node.js alfredMemory.js module.
 *
 * Redis Key Schema:
 *   alfred:ctx:{userId}:latest     — latest interaction summary
 *   alfred:ctx:{userId}:history    — recent cross-instance summaries (LIST, max 20)
 *   alfred:ctx:{userId}:tasks      — active tasks/projects
 *   alfred:ctx:{userId}:prefs      — user preferences
 */

defined('ALFRED_MEMORY_LOADED') || define('ALFRED_MEMORY_LOADED', true);

class AlfredMemory {
    private $redis;
    private const MAX_HISTORY = 20;
    private const LATEST_TTL = 604800;   // 7 days
    private const HISTORY_TTL = 2592000; // 30 days

    public function __construct() {
        try {
            $this->redis = new Redis();
            $this->redis->connect('127.0.0.1', 6379);
        } catch (Exception $e) {
            error_log('AlfredMemory Redis connect failed: ' . $e->getMessage());
            $this->redis = null;
        }
    }

    /**
     * Record an interaction from the widget Alfred.
     */
    public function recordInteraction(int $userId, array $opts = []): void {
        if (!$this->redis || !$userId) return;

        try {
            $prefix = "alfred:ctx:{$userId}";
            $now = gmdate('Y-m-d\TH:i:s\Z');

            $userMsg = mb_substr($opts['userMessage'] ?? '', 0, 500);
            $alfredMsg = mb_substr($opts['alfredResponse'] ?? '', 0, 500);

            // Update latest
            $latest = json_encode([
                'source'        => $opts['source'] ?? 'widget',
                'agent'         => $opts['agent'] ?? 'alfred',
                'model'         => $opts['model'] ?? '',
                'userMessage'   => $userMsg,
                'alfredResponse'=> $alfredMsg,
                'pageUrl'       => $opts['pageUrl'] ?? '',
                'convId'        => $opts['convId'] ?? '',
                'timestamp'     => $now,
            ], JSON_UNESCAPED_UNICODE);

            $this->redis->setex("{$prefix}:latest", self::LATEST_TTL, $latest);

            // Push to history
            $summary = json_encode([
                'source' => $opts['source'] ?? 'widget',
                'agent'  => $opts['agent'] ?? 'alfred',
                'user'   => mb_substr($userMsg, 0, 200),
                'alfred' => mb_substr($alfredMsg, 0, 200),
                'ts'     => $now,
            ], JSON_UNESCAPED_UNICODE);

            $this->redis->lPush("{$prefix}:history", $summary);
            $this->redis->lTrim("{$prefix}:history", 0, self::MAX_HISTORY - 1);
            $this->redis->expire("{$prefix}:history", self::HISTORY_TTL);

        } catch (Exception $e) {
            error_log('AlfredMemory.recordInteraction error: ' . $e->getMessage());
        }
    }

    /**
     * Get cross-instance context for injecting into the system prompt.
     */
    public function getCrossContext(int $userId, string $currentSource = 'widget'): string {
        if (!$this->redis || !$userId) return '';

        try {
            $prefix = "alfred:ctx:{$userId}";

            $historyRaw = $this->redis->lRange("{$prefix}:history", 0, 9);
            $tasksRaw   = $this->redis->get("{$prefix}:tasks");
            $prefsRaw   = $this->redis->get("{$prefix}:prefs");

            $parts = [];

            // Cross-instance recent activity
            if ($historyRaw && count($historyRaw) > 0) {
                $entries = [];
                foreach ($historyRaw as $raw) {
                    $e = json_decode($raw, true);
                    if ($e && ($e['source'] ?? '') !== $currentSource) {
                        $entries[] = $e;
                    }
                }

                if (count($entries) > 0) {
                    $parts[] = '## Recent Activity Across Other Channels';
                    $sourceLabels = [
                        'widget' => '🌐 Website Chat',
                        'ide'    => '💻 Alfred IDE',
                        'voice'  => '🎙️ Voice',
                        'phone'  => '📞 Phone',
                    ];
                    foreach (array_slice($entries, 0, 5) as $e) {
                        $src = $sourceLabels[$e['source']] ?? $e['source'];
                        $ago = $this->timeAgo($e['ts'] ?? '');
                        $parts[] = "- [{$src}] {$ago}: User said: \"{$e['user']}\" → Alfred said: \"{$e['alfred']}\"";
                    }
                }
            }

            // Active tasks
            if ($tasksRaw) {
                $tasks = json_decode($tasksRaw, true);
                if (isset($tasks['items']) && count($tasks['items']) > 0) {
                    $parts[] = '## Active Tasks';
                    foreach ($tasks['items'] as $t) {
                        $icon = ($t['status'] ?? '') === 'done' ? '✅' : '🔄';
                        $parts[] = "- {$icon} {$t['title']}";
                    }
                }
            }

            // User preferences
            if ($prefsRaw) {
                $prefs = json_decode($prefsRaw, true);
                if ($prefs && count($prefs) > 0) {
                    $parts[] = '## User Preferences';
                    foreach ($prefs as $k => $v) {
                        $parts[] = "- {$k}: {$v}";
                    }
                }
            }

            if (empty($parts)) return '';

            return "\n\n<cross_instance_context>\n" . implode("\n", $parts) . "\n</cross_instance_context>\n";

        } catch (Exception $e) {
            error_log('AlfredMemory.getCrossContext error: ' . $e->getMessage());
            return '';
        }
    }

    private function timeAgo(string $isoStr): string {
        if (!$isoStr) return '';
        $diff = time() - strtotime($isoStr);
        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        return floor($diff / 86400) . 'd ago';
    }
}
