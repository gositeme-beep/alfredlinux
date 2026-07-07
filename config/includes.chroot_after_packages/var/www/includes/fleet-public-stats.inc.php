<?php
/**
 * GoSiteMe — Public fleet stats (single source of truth for marketing copy)
 *
 * Uses live DB counts when available; falls back to 51M+ aligned with full registry narrative.
 * Include after db-config.inc.php (or this file will require it).
 *
 * @return array{
 *   agents:int,
 *   passports:int,
 *   registry:int,
 *   agents_display:string,
 *   passports_display:string,
 *   fleet_headline:string
 * }
 */
/**
 * Fast row estimate for huge InnoDB tables (avoids full COUNT on 50M+ rows).
 * TABLE_ROWS is approximate but fine for public marketing copy.
 */
function root_table_row_estimate(\PDO $db, string $table): ?int {
    try {
        $stmt = $db->prepare(
            'SELECT COALESCE(TABLE_ROWS, 0) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
        );
        $stmt->execute([$table]);
        $n = (int) $stmt->fetchColumn();
        return $n > 0 ? $n : null;
    } catch (Throwable $e) {
        return null;
    }
}

// ── Marketing-copy constants (single source of truth) ──
define('GOSITEME_TOOL_COUNT',     '13,000+');
define('GOSITEME_DEPT_COUNT',     17);

function root_fleet_public_stats(): array {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $fallbackAgents    = 51_000_000;
    $fallbackPassports = 51_000_000;
    $fallbackRegistry  = 51_000_000;

    $agents    = $fallbackAgents;
    $passports = $fallbackPassports;
    $registry  = $fallbackRegistry;

    try {
        if (!function_exists('getSharedDB')) {
            require_once __DIR__ . '/db-config.inc.php';
        }
        $db = getSharedDB();

        $a = (int) $db->query("SELECT COUNT(*) FROM agent_profiles WHERE status='active'")->fetchColumn();
        if ($a > 0) {
            $agents = $a;
        }

        // Large tables: use INFORMATION_SCHEMA estimate (milliseconds, not 30s+ full scans)
        $pEst = root_table_row_estimate($db, 'fleet_passports');
        if ($pEst !== null && $pEst > 0) {
            $passports = $pEst;
        } else {
            try {
                $p = (int) $db->query('SELECT COUNT(*) FROM fleet_passports')->fetchColumn();
                if ($p > 0) {
                    $passports = $p;
                }
            } catch (Throwable $e) {
                /* ignore */
            }
        }

        try {
            $rEst = root_table_row_estimate($db, 'alfred_agent_registry');
            if ($rEst !== null && $rEst > 0) {
                $registry = $rEst;
            } else {
                $r = (int) $db->query('SELECT COUNT(*) FROM alfred_agent_registry')->fetchColumn();
                if ($r > 0) {
                    $registry = $r;
                }
            }
        } catch (Throwable $e) {
            /* table may not exist in all installs */
        }

        $fleetN = max($agents, $passports, $registry);
    } catch (Throwable $e) {
        $fleetN = max($fallbackAgents, $fallbackPassports, $fallbackRegistry);
    }

    $fmt = static function (int $n): string {
        if ($n >= 1_000_000) {
            $m = $n / 1_000_000;
            if ($m >= 100) {
                return (string) (int) round($m) . 'M+';
            }
            return (string) round($m, 1) . 'M+';
        }
        if ($n >= 10_000) {
            return number_format($n);
        }
        return (string) max(0, $n);
    };

    $cached = [
        'agents'             => $agents,
        'passports'          => $passports,
        'registry'           => $registry,
        'agents_display'     => $fmt($agents),
        'passports_display'  => $fmt($passports),
        'registry_display'   => $fmt($registry),
        'fleet_max'          => $fleetN,
        'fleet_headline'     => $fmt(max($agents, $passports, $registry)),
    ];

    return $cached;
}
