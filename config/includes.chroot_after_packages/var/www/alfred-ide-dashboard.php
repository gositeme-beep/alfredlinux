<?php
/**
 * Alfred IDE Dashboard — "The Wow Page"
 *  Lands here after Alfred IDE sign-in. Deliberately extends `dashboard.php`
 *  style but is laser-focused on AI usage, value, progress.
 *
 *  Nine levels of depth (all enabled):
 *   L1  Hero + 4 top-line stat cards (today vs yesterday trend)
 *   L2  Per-tool / per-category breakdown (alfred_tool_usage)
 *   L3  14-day sparkline + month-end projection
 *   L4  XP / Level / Streak (alfred_user_xp_summary + alfred_xp)
 *   L5  Achievements gallery (earned vs locked, tier badges)
 *   L6  Alfred Learning Journal ("What Alfred learned about you")
 *   L7  Live activity stream (auto-refresh every 10 s via /api/alfred-ide-live)
 *   L8  Value estimator — time saved & $ saved from tool executions
 *   L9  Weekly AI-generated insights / smart recommendations
 */
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/db-config.inc.php';

// ─── Data collection ────────────────────────────────────────────────────────
$db       = null;
$stats    = [
    'usage'      => ['api_calls'=>0,'voice_minutes'=>0,'storage_mb'=>0,'agents'=>0],
    'today'      => ['api_calls'=>0,'voice_minutes'=>0,'tool_runs'=>0,'conversations'=>0],
    'yesterday'  => ['api_calls'=>0,'voice_minutes'=>0,'tool_runs'=>0,'conversations'=>0],
    'plan_name'  => 'Free',
    'plan_limits'=> ['api_calls'=>1000,'voice_minutes'=>60,'storage_mb'=>500,'agents'=>3],
    'tools'      => [],      // tool_name => [count, avg_ms, success%]
    'categories' => [],      // category  => count
    'spark'      => array_fill(0, 14, 0),
    'xp'         => ['total'=>0,'level'=>1,'title'=>'Newcomer','streak'=>0,'longest'=>0,'tools_used'=>0,'problems_solved'=>0],
    'xp_recent'  => [],
    'achievements_earned' => [],
    'achievements_all'    => [],
    'journal'    => [],
    'recent'     => [],
    'fleets'     => 0,
    'active_agents' => 0,
];

try { $db = getSharedDB(); } catch (Exception $e) { error_log('ide-dash db: '.$e->getMessage()); }

if ($db) {
    $ex = function(string $sql, array $a=[], $fetch='col') use ($db) {
        try {
            $s = $db->prepare($sql); $s->execute($a);
            if ($fetch === 'col')  return $s->fetchColumn();
            if ($fetch === 'row')  return $s->fetch(PDO::FETCH_ASSOC) ?: [];
            if ($fetch === 'all')  return $s->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) { error_log('ide-dash q: '.$e->getMessage()); }
        return $fetch === 'col' ? 0 : [];
    };

    // ── L1: today / yesterday counters ─────────────────────────────────────
    foreach (['api_call'=>'api_calls','voice_minute'=>'voice_minutes'] as $rt => $k) {
        $stats['today'][$k]     = (int)$ex("SELECT COALESCE(SUM(quantity),COUNT(*)) FROM alfred_usage WHERE user_id=? AND resource_type=? AND created_at>=CURDATE() AND created_at<CURDATE()+INTERVAL 1 DAY", [$clientId,$rt]);
        $stats['yesterday'][$k] = (int)$ex("SELECT COALESCE(SUM(quantity),COUNT(*)) FROM alfred_usage WHERE user_id=? AND resource_type=? AND created_at>=CURDATE()-INTERVAL 1 DAY AND created_at<CURDATE()", [$clientId,$rt]);
    }
    $stats['today']['tool_runs']     = (int)$ex("SELECT COUNT(*) FROM alfred_tool_usage WHERE user_id=? AND used_at>=CURDATE()", [$clientId]);
    $stats['yesterday']['tool_runs'] = (int)$ex("SELECT COUNT(*) FROM alfred_tool_usage WHERE user_id=? AND used_at>=CURDATE()-INTERVAL 1 DAY AND used_at<CURDATE()", [$clientId]);
    $stats['today']['conversations']     = (int)$ex("SELECT COUNT(*) FROM alfred_conversations WHERE user_id=? AND created_at>=CURDATE()", [$clientId]);
    $stats['yesterday']['conversations'] = (int)$ex("SELECT COUNT(*) FROM alfred_conversations WHERE user_id=? AND created_at>=CURDATE()-INTERVAL 1 DAY AND created_at<CURDATE()", [$clientId]);

    // ── month-to-date usage + plan ─────────────────────────────────────────
    $rows = $ex("SELECT resource_type, COALESCE(SUM(quantity),COUNT(*)) total FROM alfred_usage WHERE user_id=? AND created_at>=DATE_FORMAT(CURDATE(),'%Y-%m-01') GROUP BY resource_type", [$clientId], 'all');
    foreach ($rows as $r) {
        $t=(int)$r['total'];
        switch ($r['resource_type']) {
            case 'api_call':      $stats['usage']['api_calls']=$t; break;
            case 'voice_minute':  $stats['usage']['voice_minutes']=$t; break;
            case 'storage':       $stats['usage']['storage_mb']=$t; break;
        }
    }
    $stats['active_agents'] = (int)$ex("SELECT COUNT(*) FROM alfred_fleet_agents fa JOIN alfred_fleets f ON fa.fleet_id=f.id WHERE f.user_id=? AND fa.status IN ('queued','running')", [$clientId]);
    $stats['usage']['agents'] = (int)$ex("SELECT COUNT(*) FROM alfred_fleet_agents fa JOIN alfred_fleets f ON fa.fleet_id=f.id WHERE f.user_id=?", [$clientId]);
    $stats['fleets'] = (int)$ex("SELECT COUNT(*) FROM alfred_fleets WHERE user_id=?", [$clientId]);

    $plan = $ex("SELECT plan_name, plan_limits FROM alfred_subscriptions WHERE user_id=? AND status='active' ORDER BY created_at DESC LIMIT 1", [$clientId], 'row');
    if (!empty($plan['plan_name'])) {
        $stats['plan_name']   = $plan['plan_name'];
        $lim = json_decode($plan['plan_limits'] ?? '{}', true);
        if ($lim) $stats['plan_limits'] = array_merge($stats['plan_limits'], $lim);
    }

    // ── L2: per-tool / per-category ────────────────────────────────────────
    $stats['tools'] = $ex("SELECT tool_name, COUNT(*) runs, AVG(execution_time_ms) avg_ms, 100*SUM(success)/COUNT(*) success_pct FROM alfred_tool_usage WHERE user_id=? AND used_at>=CURDATE()-INTERVAL 30 DAY GROUP BY tool_name ORDER BY runs DESC LIMIT 8", [$clientId], 'all');
    $stats['categories'] = $ex("SELECT COALESCE(category,'uncategorised') category, COUNT(*) runs FROM alfred_tool_usage WHERE user_id=? AND used_at>=CURDATE()-INTERVAL 30 DAY GROUP BY category ORDER BY runs DESC", [$clientId], 'all');

    // ── L3: 14-day sparkline (api_calls + tool_runs combined) ──────────────
    $sp = $ex("SELECT DATE(d) dt, COALESCE(SUM(c),0) c FROM (
                    SELECT DATE(created_at) d, COUNT(*) c FROM alfred_usage WHERE user_id=? AND resource_type='api_call' AND created_at>=CURDATE()-INTERVAL 13 DAY GROUP BY DATE(created_at)
                    UNION ALL
                    SELECT DATE(used_at) d, COUNT(*) c FROM alfred_tool_usage WHERE user_id=? AND used_at>=CURDATE()-INTERVAL 13 DAY GROUP BY DATE(used_at)
                ) x GROUP BY DATE(d)", [$clientId,$clientId], 'all');
    $map = [];
    foreach ($sp as $r) $map[$r['dt']] = (int)$r['c'];
    for ($i=13;$i>=0;$i--) {
        $d = date('Y-m-d', strtotime("-$i day"));
        $stats['spark'][13-$i] = $map[$d] ?? 0;
    }

    // ── L4: XP / level / streak ────────────────────────────────────────────
    $xp = $ex("SELECT total_xp,level,title,streak_days,longest_streak,tools_used,problems_solved FROM alfred_user_xp_summary WHERE client_id=?", [$clientId], 'row');
    if ($xp) {
        $stats['xp'] = [
            'total'=>(int)$xp['total_xp'],'level'=>(int)$xp['level'],
            'title'=>$xp['title'] ?: 'Newcomer',
            'streak'=>(int)$xp['streak_days'],'longest'=>(int)$xp['longest_streak'],
            'tools_used'=>(int)$xp['tools_used'],'problems_solved'=>(int)$xp['problems_solved'],
        ];
    }
    $stats['xp_recent'] = $ex("SELECT xp_amount, action_type, source_tool, notes, earned_at FROM alfred_xp WHERE user_id=? ORDER BY earned_at DESC LIMIT 5", [$clientId], 'all');

    // ── L5: achievements ───────────────────────────────────────────────────
    $stats['achievements_earned'] = $ex("SELECT achievement_name, achievement_type, badge_tier, xp_awarded, unlocked_at FROM alfred_achievements WHERE user_id=? ORDER BY unlocked_at DESC LIMIT 12", [$clientId], 'all');
    $stats['achievements_all']    = $ex("SELECT achievement_name, achievement_type, badge_tier, xp_awarded FROM alfred_achievements WHERE user_id=0 ORDER BY FIELD(badge_tier,'bronze','silver','gold','platinum','diamond'), xp_awarded ASC LIMIT 25", [], 'all');
    $earnedKeys = [];
    foreach ($stats['achievements_earned'] as $a) $earnedKeys[$a['achievement_name']] = true;
    foreach ($stats['achievements_all']    as &$a) $a['is_earned'] = isset($earnedKeys[$a['achievement_name']]);
    unset($a);

    // ── L6: learning journal ───────────────────────────────────────────────
    $stats['journal'] = $ex("SELECT entry_type, content, confidence, created_at FROM alfred_learning_journal WHERE user_id=? ORDER BY created_at DESC LIMIT 6", [$clientId], 'all');

    // ── L7: recent activity (initial snapshot; JS refreshes) ───────────────
    $stats['recent'] = $ex("SELECT resource_type type, IFNULL(description,'') description, COALESCE(quantity,1) quantity, created_at, IFNULL(status,'completed') status FROM alfred_usage WHERE user_id=? ORDER BY created_at DESC LIMIT 15", [$clientId], 'all');
}

// ─── Derived metrics (all levels) ────────────────────────────────────────────
function pct(int $used, int $limit): int { return $limit>0 ? min(100,(int)round($used/$limit*100)) : 0; }
function trend(int $t, int $y): array {
    if ($y === 0) return [$t>0 ? 'up' : 'flat', $t>0 ? 100 : 0];
    $d = (int)round((($t-$y)/$y)*100);
    return [$d>0?'up':($d<0?'down':'flat'), abs($d)];
}
function fmt(int $n): string { return $n>=1000000?number_format($n/1000000,1).'M':($n>=1000?number_format($n/1000,1).'k':(string)$n); }

// L3 projection: monthly run-rate based on MTD
$dayOfMonth   = (int)date('j');
$daysInMonth  = (int)date('t');
$proj_api     = $dayOfMonth>0 ? (int)round($stats['usage']['api_calls']/$dayOfMonth*$daysInMonth) : 0;
$proj_voice   = $dayOfMonth>0 ? (int)round($stats['usage']['voice_minutes']/$dayOfMonth*$daysInMonth) : 0;

// L8: value estimator — rough heuristics
$total_tool_runs   = array_sum(array_column($stats['tools'], 'runs'));
$minutes_saved     = (int)round($total_tool_runs * 4 + $stats['today']['api_calls']*0.2 + $stats['usage']['voice_minutes']*1.5);
$hours_saved       = round($minutes_saved / 60, 1);
$dollars_saved     = (int)round($hours_saved * 45); // $45/h developer-time proxy

// L4 XP thresholds — simple curve
$xpCurve = function(int $lvl): int { return (int)(100 * pow($lvl, 1.7)); };
$curLvl  = max(1, (int)$stats['xp']['level']);
$nextLvl = $curLvl + 1;
$xpFloor = $xpCurve($curLvl);
$xpCeil  = $xpCurve($nextLvl);
$xpInto  = max(0, $stats['xp']['total'] - $xpFloor);
$xpSpan  = max(1, $xpCeil - $xpFloor);
$xpPct   = min(100, (int)round($xpInto / $xpSpan * 100));

// Trend arrows
[$tr_api_dir,$tr_api_pct]     = trend($stats['today']['api_calls'],$stats['yesterday']['api_calls']);
[$tr_voice_dir,$tr_voice_pct] = trend($stats['today']['voice_minutes'],$stats['yesterday']['voice_minutes']);
[$tr_tool_dir,$tr_tool_pct]   = trend($stats['today']['tool_runs'],$stats['yesterday']['tool_runs']);
[$tr_conv_dir,$tr_conv_pct]   = trend($stats['today']['conversations'],$stats['yesterday']['conversations']);

// L9: smart insight — simple rule-based (upgradable to LLM)
$insights = [];
if ($stats['xp']['streak'] >= 3) $insights[] = ['🔥', "You're on a ".$stats['xp']['streak']."-day streak. Longest ever: ".$stats['xp']['longest']." days."];
if ($tr_api_pct >= 50 && $tr_api_dir === 'up') $insights[] = ['📈', "API usage is up {$tr_api_pct}% vs yesterday — a big day."];
if (!empty($stats['tools'])) {
    $top = $stats['tools'][0];
    $insights[] = ['🛠️', "Your go-to tool is <b>".htmlspecialchars($top['tool_name'])."</b> — {$top['runs']} runs this month (".round($top['success_pct'])."% success)."];
}
if ($proj_api > $stats['plan_limits']['api_calls']*0.9) $insights[] = ['⚠️', "Projected API calls ({$proj_api}) will exceed plan limit (".$stats['plan_limits']['api_calls'].") — consider upgrading."];
if ($hours_saved >= 1) $insights[] = ['⏱️', "Alfred has saved you an estimated <b>{$hours_saved} hours</b> ≈ \${$dollars_saved} of developer time."];
if (empty($insights)) $insights[] = ['👋', "Welcome to Alfred — start using tools to earn XP and unlock achievements."];

$displayName = trim($clientName ?? '') ?: explode('@', $clientEmail ?? 'friend')[0];
$sparkJson   = json_encode(array_values($stats['spark']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Alfred IDE Dashboard — <?= htmlspecialchars($displayName) ?></title>
<link rel="stylesheet" href="/assets/css/fonts.css">
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<link rel="stylesheet" href="/assets/css/design-tokens.css?v=20260310">
<link rel="stylesheet" href="/assets/css/components.css?v=20260310">
<style>
:root{
  --gold:#ffcf4a; --gold-dim:#b78e1e;
  --dark:#0a0a1a; --dark-card:#161636;
  --border:rgba(255,255,255,0.06); --border-hover:rgba(255,207,74,0.35);
  --text:#e7e9ef; --muted:#9aa0b4;
  --good:#10b981; --warn:#f59e0b; --bad:#ef4444; --info:#60a5fa;
}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;background:var(--dark);color:var(--text);min-height:100vh;-webkit-font-smoothing:antialiased;
  background-image:
    radial-gradient(ellipse at top left, rgba(255,207,74,0.05), transparent 60%),
    radial-gradient(ellipse at bottom right, rgba(0,168,255,0.04), transparent 60%);
}
.shell{max-width:1320px;margin:0 auto;padding:28px 24px 80px}
.topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:12px}
.topbar .brand{display:flex;align-items:center;gap:12px;font-weight:700;letter-spacing:.3px}
.topbar .brand .logo{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--gold),var(--gold-dim));display:grid;place-items:center;color:#111;font-weight:900}
.topbar .brand .sub{color:var(--muted);font-weight:400;font-size:.85rem}
.topbar .actions{display:flex;gap:8px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:8px;padding:9px 14px;border-radius:10px;border:1px solid var(--border);background:var(--dark-card);color:var(--text);font-size:.88rem;text-decoration:none;cursor:pointer;transition:.15s}
.btn:hover{border-color:var(--border-hover);transform:translateY(-1px)}
.btn.primary{background:linear-gradient(135deg,var(--gold),var(--gold-dim));color:#111;border-color:transparent;font-weight:700}
.btn.ghost{background:transparent}

.hero{background:linear-gradient(135deg, rgba(255,207,74,0.10), rgba(0,168,255,0.05));border:1px solid var(--border);border-radius:18px;padding:26px;margin-bottom:22px;display:grid;grid-template-columns:1fr auto;gap:22px;align-items:center}
.hero h1{font-size:1.85rem;font-weight:800;margin-bottom:6px}
.hero h1 .wave{display:inline-block;animation:wave 2.5s infinite;transform-origin:70% 70%}
@keyframes wave{0%,60%,100%{transform:rotate(0)}10%{transform:rotate(18deg)}20%{transform:rotate(-12deg)}30%{transform:rotate(16deg)}40%{transform:rotate(-6deg)}}
.hero .tag{display:inline-block;padding:3px 10px;border-radius:999px;background:rgba(255,207,74,0.15);color:var(--gold);font-size:.75rem;font-weight:600;margin-right:6px;border:1px solid rgba(255,207,74,0.25)}
.hero .sub{color:var(--muted);margin-top:4px}
.hero .hero-right{display:flex;flex-direction:column;align-items:flex-end;gap:6px}
.hero .level-chip{display:flex;align-items:center;gap:10px;padding:8px 14px;border-radius:14px;background:rgba(0,0,0,0.35);border:1px solid var(--border)}
.hero .level-chip .lvl{font-size:1.9rem;font-weight:900;color:var(--gold);line-height:1}
.hero .level-chip .title{font-size:.8rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px}
.hero .xp-bar{width:260px;height:8px;background:rgba(255,255,255,0.06);border-radius:99px;overflow:hidden}
.hero .xp-bar>span{display:block;height:100%;background:linear-gradient(90deg,var(--gold),var(--info));}
.hero .xp-meta{font-size:.75rem;color:var(--muted)}

.grid{display:grid;gap:16px}
.grid.cols-4{grid-template-columns:repeat(4,1fr)}
.grid.cols-3{grid-template-columns:repeat(3,1fr)}
.grid.cols-2{grid-template-columns:1fr 1fr}
@media (max-width:980px){.grid.cols-4,.grid.cols-3,.grid.cols-2{grid-template-columns:1fr 1fr}.hero{grid-template-columns:1fr}.hero .hero-right{align-items:flex-start}}
@media (max-width:640px){.grid.cols-4,.grid.cols-3,.grid.cols-2{grid-template-columns:1fr}}

.card{background:var(--dark-card);border:1px solid var(--border);border-radius:14px;padding:18px;position:relative;transition:.15s}
.card:hover{border-color:var(--border-hover);transform:translateY(-2px)}
.card h3{font-size:.75rem;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);margin-bottom:10px;display:flex;align-items:center;justify-content:space-between}
.card h3 .lvl-badge{background:rgba(255,207,74,0.12);color:var(--gold);padding:2px 7px;border-radius:6px;font-size:.65rem;letter-spacing:.5px}
.stat{display:flex;align-items:flex-end;gap:12px}
.stat .n{font-size:2rem;font-weight:800;line-height:1}
.stat .unit{color:var(--muted);font-size:.85rem;padding-bottom:3px}
.trend{display:inline-flex;align-items:center;gap:4px;font-size:.78rem;padding:3px 8px;border-radius:99px;margin-top:8px}
.trend.up{background:rgba(16,185,129,.12);color:var(--good)}
.trend.down{background:rgba(239,68,68,.12);color:var(--bad)}
.trend.flat{background:rgba(255,255,255,.05);color:var(--muted)}
.meter{margin-top:10px;height:6px;background:rgba(255,255,255,0.05);border-radius:99px;overflow:hidden}
.meter>span{display:block;height:100%}
.meter-label{display:flex;justify-content:space-between;font-size:.72rem;color:var(--muted);margin-top:6px}

/* Sparkline svg */
.spark{width:100%;height:44px;margin-top:10px}
.spark polyline{fill:none;stroke:var(--gold);stroke-width:2;filter:drop-shadow(0 0 4px rgba(255,207,74,.4))}
.spark .fill{fill:url(#sparkFill);stroke:none}

/* Tool list */
.tool-row{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px dashed rgba(255,255,255,.04);font-size:.9rem}
.tool-row:last-child{border-bottom:0}
.tool-row .name{flex:1;font-weight:600}
.tool-row .runs{color:var(--muted);font-size:.8rem;min-width:70px;text-align:right}
.tool-row .succ{font-size:.7rem;padding:2px 6px;border-radius:6px;background:rgba(16,185,129,.1);color:var(--good)}
.tool-row .succ.low{background:rgba(239,68,68,.1);color:var(--bad)}
.tool-row .succ.mid{background:rgba(245,158,11,.1);color:var(--warn)}

/* Achievements */
.ach-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(96px,1fr));gap:10px;margin-top:6px}
.ach{aspect-ratio:1;border-radius:14px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:6px;border:1px solid var(--border);background:rgba(255,255,255,.02);opacity:.35;transition:.15s;cursor:default}
.ach.earned{opacity:1;background:linear-gradient(135deg,rgba(255,207,74,.15),rgba(0,168,255,.08));border-color:rgba(255,207,74,.3);box-shadow:0 0 16px rgba(255,207,74,.08)}
.ach .tier{font-size:1.25rem;margin-bottom:2px}
.ach .nm{font-size:.65rem;color:var(--muted);line-height:1.1}
.ach.earned .nm{color:var(--text);font-weight:600}
.ach .xp{font-size:.55rem;color:var(--gold);margin-top:2px}
.tier-bronze{filter:hue-rotate(10deg)}
.tier-silver{filter:hue-rotate(180deg) saturate(.5)}
.tier-gold{}
.tier-platinum{filter:hue-rotate(200deg) brightness(1.1)}
.tier-diamond{filter:hue-rotate(280deg) brightness(1.2)}

/* Activity stream */
.feed{max-height:360px;overflow-y:auto}
.feed::-webkit-scrollbar{width:6px}
.feed::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:99px}
.feed-item{display:flex;gap:10px;padding:10px 0;border-bottom:1px dashed rgba(255,255,255,.04);animation:fadeIn .3s ease}
.feed-item:last-child{border-bottom:0}
.feed-item i{width:28px;height:28px;display:grid;place-items:center;border-radius:8px;background:rgba(255,207,74,.1);color:var(--gold);flex-shrink:0}
.feed-item .meta{flex:1;min-width:0}
.feed-item .t1{font-size:.88rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.feed-item .t2{font-size:.72rem;color:var(--muted)}
@keyframes fadeIn{from{opacity:0;transform:translateY(-3px)}to{opacity:1;transform:none}}

.journal-item{padding:10px 0;border-bottom:1px dashed rgba(255,255,255,.04)}
.journal-item:last-child{border-bottom:0}
.journal-item .type{display:inline-block;font-size:.65rem;letter-spacing:1px;text-transform:uppercase;padding:2px 7px;border-radius:5px;background:rgba(96,165,250,.12);color:var(--info);margin-right:6px}
.journal-item .conf{font-size:.7rem;color:var(--muted);margin-left:4px}
.journal-item .content{font-size:.88rem;margin-top:4px;color:#d5d9e4}

/* Insight card */
.insight{display:flex;gap:12px;align-items:flex-start;padding:12px;border-radius:12px;background:linear-gradient(135deg,rgba(0,168,255,.08),rgba(255,207,74,.04));border:1px solid rgba(0,168,255,.18);margin-bottom:8px}
.insight .emo{font-size:1.4rem;flex-shrink:0}
.insight .msg{font-size:.9rem;line-height:1.4}

/* Value card — big */
.value{background:linear-gradient(135deg,rgba(16,185,129,.12),rgba(255,207,74,.08));border-color:rgba(16,185,129,.3)}
.value .huge{font-size:2.8rem;font-weight:900;background:linear-gradient(135deg,var(--good),var(--gold));-webkit-background-clip:text;-webkit-text-fill-color:transparent;line-height:1}
.value .saved{font-size:.9rem;color:var(--muted);margin-top:4px}

/* Streak flame */
.streak-big{text-align:center;padding:12px 0}
.streak-big .flame{font-size:3rem;filter:drop-shadow(0 0 14px rgba(239,68,68,.4));animation:flick 1.4s ease-in-out infinite}
@keyframes flick{0%,100%{transform:scale(1)}50%{transform:scale(1.07)}}
.streak-big .days{font-size:2rem;font-weight:900;color:var(--gold)}

/* Category donut rows */
.cat-row{display:flex;align-items:center;gap:8px;padding:5px 0;font-size:.85rem}
.cat-row .swatch{width:10px;height:10px;border-radius:3px}
.cat-row .label{flex:1;text-transform:capitalize}
.cat-row .pct{color:var(--muted);font-size:.78rem}

.footer-cta{margin-top:30px;text-align:center;color:var(--muted);font-size:.82rem}
.footer-cta a{color:var(--gold);text-decoration:none;margin:0 6px}
</style>
</head>
<body>
<div class="shell">

  <!-- TOP BAR -->
  <div class="topbar">
    <div class="brand">
      <div class="logo">A</div>
      <div>
        Alfred IDE Dashboard
        <div class="sub"><?= htmlspecialchars($clientEmail ?? '') ?></div>
      </div>
    </div>
    <div class="actions">
      <a class="btn ghost" href="/dashboard.php"><i class="fa-solid fa-house"></i> Main dashboard</a>
      <a class="btn ghost" href="/alfred-ide/?folder=/home/<?= htmlspecialchars(strtolower(explode('@',$clientEmail??'user')[0])) ?>"><i class="fa-solid fa-code"></i> Open IDE</a>
      <a class="btn primary" href="/billing.php"><i class="fa-solid fa-bolt"></i> Upgrade plan</a>
    </div>
  </div>

  <!-- L1 HERO -->
  <div class="hero">
    <div>
      <h1>Welcome back, <?= htmlspecialchars($displayName) ?> <span class="wave">👋</span></h1>
      <div>
        <span class="tag"><?= htmlspecialchars($stats['plan_name']) ?> plan</span>
        <span class="tag" style="background:rgba(16,185,129,.15);color:var(--good);border-color:rgba(16,185,129,.25)"><?= $stats['active_agents'] ?> agents live</span>
        <span class="tag" style="background:rgba(96,165,250,.15);color:var(--info);border-color:rgba(96,165,250,.25)"><?= $stats['fleets'] ?> fleets</span>
      </div>
      <div class="sub">Here's everything Alfred's been up to on your behalf.</div>
    </div>
    <div class="hero-right">
      <div class="level-chip">
        <div>
          <div class="lvl">L<?= $stats['xp']['level'] ?></div>
          <div class="title"><?= htmlspecialchars($stats['xp']['title']) ?></div>
        </div>
        <div>
          <div class="xp-bar"><span style="width:<?= $xpPct ?>%"></span></div>
          <div class="xp-meta"><?= fmt($stats['xp']['total']) ?> XP · <?= fmt(max(0,$xpCeil-$stats['xp']['total'])) ?> to L<?= $nextLvl ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- L1: FOUR TOP CARDS -->
  <div class="grid cols-4" style="margin-bottom:16px">
    <div class="card">
      <h3><span>API Calls Today</span><span class="lvl-badge">L1</span></h3>
      <div class="stat"><div class="n"><?= fmt($stats['today']['api_calls']) ?></div><div class="unit">/ <?= fmt($stats['yesterday']['api_calls']) ?> yest.</div></div>
      <div class="trend <?= $tr_api_dir ?>"><i class="fa-solid fa-arrow-<?= $tr_api_dir==='flat'?'right':$tr_api_dir ?>"></i> <?= $tr_api_pct ?>%</div>
      <div class="meter"><span style="width:<?= pct($stats['usage']['api_calls'],$stats['plan_limits']['api_calls']) ?>%;background:linear-gradient(90deg,var(--gold),var(--info))"></span></div>
      <div class="meter-label"><span><?= fmt($stats['usage']['api_calls']) ?> this month</span><span><?= fmt($stats['plan_limits']['api_calls']) ?> limit</span></div>
    </div>
    <div class="card">
      <h3><span>Voice Minutes Today</span><span class="lvl-badge">L1</span></h3>
      <div class="stat"><div class="n"><?= fmt($stats['today']['voice_minutes']) ?></div><div class="unit">/ <?= fmt($stats['yesterday']['voice_minutes']) ?> yest.</div></div>
      <div class="trend <?= $tr_voice_dir ?>"><i class="fa-solid fa-arrow-<?= $tr_voice_dir==='flat'?'right':$tr_voice_dir ?>"></i> <?= $tr_voice_pct ?>%</div>
      <div class="meter"><span style="width:<?= pct($stats['usage']['voice_minutes'],$stats['plan_limits']['voice_minutes']) ?>%;background:linear-gradient(90deg,#a78bfa,var(--gold))"></span></div>
      <div class="meter-label"><span><?= fmt($stats['usage']['voice_minutes']) ?> min this month</span><span><?= fmt($stats['plan_limits']['voice_minutes']) ?> limit</span></div>
    </div>
    <div class="card">
      <h3><span>Tool Runs Today</span><span class="lvl-badge">L1</span></h3>
      <div class="stat"><div class="n"><?= fmt($stats['today']['tool_runs']) ?></div><div class="unit">/ <?= fmt($stats['yesterday']['tool_runs']) ?> yest.</div></div>
      <div class="trend <?= $tr_tool_dir ?>"><i class="fa-solid fa-arrow-<?= $tr_tool_dir==='flat'?'right':$tr_tool_dir ?>"></i> <?= $tr_tool_pct ?>%</div>
      <div class="meter-label" style="margin-top:12px"><span><?= fmt($stats['xp']['tools_used']) ?> unique tools lifetime</span></div>
    </div>
    <div class="card">
      <h3><span>Conversations</span><span class="lvl-badge">L1</span></h3>
      <div class="stat"><div class="n"><?= fmt($stats['today']['conversations']) ?></div><div class="unit">/ <?= fmt($stats['yesterday']['conversations']) ?> yest.</div></div>
      <div class="trend <?= $tr_conv_dir ?>"><i class="fa-solid fa-arrow-<?= $tr_conv_dir==='flat'?'right':$tr_conv_dir ?>"></i> <?= $tr_conv_pct ?>%</div>
      <div class="meter-label" style="margin-top:12px"><span><?= fmt($stats['xp']['problems_solved']) ?> problems solved</span></div>
    </div>
  </div>

  <!-- L3 sparkline + L8 value + L4 streak -->
  <div class="grid cols-3" style="margin-bottom:16px">
    <div class="card" style="grid-column:span 1">
      <h3><span>14-Day Activity</span><span class="lvl-badge">L3</span></h3>
      <svg class="spark" viewBox="0 0 200 44" preserveAspectRatio="none" id="spark">
        <defs><linearGradient id="sparkFill" x1="0" x2="0" y1="0" y2="1">
          <stop offset="0%" stop-color="rgba(255,207,74,.35)"/><stop offset="100%" stop-color="rgba(255,207,74,0)"/>
        </linearGradient></defs>
      </svg>
      <div class="meter-label"><span>14 days ago</span><span>today</span></div>
      <div style="margin-top:12px;padding-top:10px;border-top:1px dashed rgba(255,255,255,.05);font-size:.85rem">
        <div style="display:flex;justify-content:space-between;color:var(--muted)"><span>Projected month-end</span><span style="color:var(--text);font-weight:700"><?= fmt($proj_api) ?> API</span></div>
        <div style="display:flex;justify-content:space-between;color:var(--muted);margin-top:2px"><span>Voice month-end</span><span style="color:var(--text);font-weight:700"><?= fmt($proj_voice) ?> min</span></div>
      </div>
    </div>

    <div class="card value">
      <h3><span>Value Delivered</span><span class="lvl-badge">L8</span></h3>
      <div class="huge">$<?= number_format($dollars_saved) ?></div>
      <div class="saved">≈ <?= $hours_saved ?> hours saved · based on <?= fmt($total_tool_runs) ?> tool runs</div>
      <div style="margin-top:10px;font-size:.78rem;color:var(--muted)">Estimated at $45/hr developer-time. Alfred handled what would've taken you this long to type, search, and debug.</div>
    </div>

    <div class="card">
      <h3><span>Current Streak</span><span class="lvl-badge">L4</span></h3>
      <div class="streak-big">
        <div class="flame">🔥</div>
        <div class="days"><?= $stats['xp']['streak'] ?> <span style="font-size:1rem;color:var(--muted);font-weight:400">days</span></div>
        <div style="font-size:.8rem;color:var(--muted);margin-top:4px">Longest: <?= $stats['xp']['longest'] ?> days</div>
      </div>
    </div>
  </div>

  <!-- L2 tools + L2 categories + L9 insights -->
  <div class="grid cols-3" style="margin-bottom:16px">
    <div class="card" style="grid-column:span 1">
      <h3><span>Top Tools (30 d)</span><span class="lvl-badge">L2</span></h3>
      <?php if (empty($stats['tools'])): ?>
        <div style="color:var(--muted);font-size:.85rem;padding:20px 0;text-align:center">No tool usage yet. Start running tools in the IDE to see insights here.</div>
      <?php else: foreach ($stats['tools'] as $t): $sp = (float)$t['success_pct']; $cls = $sp>=90?'':($sp>=70?'mid':'low'); ?>
        <div class="tool-row">
          <div class="name"><?= htmlspecialchars($t['tool_name']) ?></div>
          <div class="runs"><?= fmt((int)$t['runs']) ?> runs<?= $t['avg_ms']?' · '.round($t['avg_ms']).'ms':'' ?></div>
          <div class="succ <?= $cls ?>"><?= round($sp) ?>%</div>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <div class="card">
      <h3><span>Categories</span><span class="lvl-badge">L2</span></h3>
      <?php
      $totCat = array_sum(array_column($stats['categories'],'runs')) ?: 1;
      $palette = ['#ffcf4a','#60a5fa','#10b981','#a78bfa','#f59e0b','#ef4444','#ec4899','#22d3ee'];
      ?>
      <?php if (empty($stats['categories'])): ?>
        <div style="color:var(--muted);font-size:.85rem;padding:20px 0;text-align:center">No category data yet.</div>
      <?php else: foreach ($stats['categories'] as $i=>$c): $p = round(((int)$c['runs']/$totCat)*100); ?>
        <div class="cat-row">
          <span class="swatch" style="background:<?= $palette[$i % count($palette)] ?>"></span>
          <span class="label"><?= htmlspecialchars($c['category']) ?></span>
          <span class="pct"><?= fmt((int)$c['runs']) ?> · <?= $p ?>%</span>
        </div>
        <div class="meter" style="margin-top:0"><span style="width:<?= $p ?>%;background:<?= $palette[$i % count($palette)] ?>"></span></div>
      <?php endforeach; endif; ?>
    </div>

    <div class="card">
      <h3><span>Smart Insights</span><span class="lvl-badge">L9</span></h3>
      <?php foreach ($insights as $i): ?>
        <div class="insight"><div class="emo"><?= $i[0] ?></div><div class="msg"><?= $i[1] ?></div></div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- L5 achievements -->
  <div class="card" style="margin-bottom:16px">
    <h3><span>Achievements (<?= count($stats['achievements_earned']) ?> / <?= count($stats['achievements_all']) ?>)</span><span class="lvl-badge">L5</span></h3>
    <div class="ach-grid">
      <?php
      $tierIcon = ['bronze'=>'🥉','silver'=>'🥈','gold'=>'🥇','platinum'=>'💎','diamond'=>'💠'];
      foreach ($stats['achievements_all'] as $a):
        $earned = !empty($a['is_earned']); $ti = $tierIcon[$a['badge_tier']] ?? '🏅';
      ?>
        <div class="ach <?= $earned?'earned':'' ?> tier-<?= htmlspecialchars($a['badge_tier']) ?>" title="<?= htmlspecialchars($a['achievement_name']) ?>  (+<?= (int)$a['xp_awarded'] ?> XP)">
          <div class="tier"><?= $ti ?></div>
          <div class="nm"><?= htmlspecialchars($a['achievement_name']) ?></div>
          <div class="xp">+<?= (int)$a['xp_awarded'] ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- L6 journal + L7 feed -->
  <div class="grid cols-2" style="margin-bottom:16px">
    <div class="card">
      <h3><span>What Alfred Has Learned About You</span><span class="lvl-badge">L6</span></h3>
      <?php if (empty($stats['journal'])): ?>
        <div style="color:var(--muted);font-size:.85rem;padding:20px 0;text-align:center">Alfred is still getting to know you. Observations will appear here as you work together.</div>
      <?php else: foreach ($stats['journal'] as $j): ?>
        <div class="journal-item">
          <span class="type"><?= htmlspecialchars($j['entry_type']) ?></span>
          <span class="conf"><?= round((float)$j['confidence']*100) ?>% confidence</span>
          <div class="content"><?= htmlspecialchars($j['content']) ?></div>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <div class="card">
      <h3><span>Live Activity <span id="liveDot" style="display:inline-block;width:7px;height:7px;border-radius:99px;background:var(--good);margin-left:4px;animation:flick 1.5s infinite"></span></span><span class="lvl-badge">L7</span></h3>
      <div class="feed" id="feed">
        <?php
        $aIcon = ['api_call'=>'fa-code','voice_minute'=>'fa-microphone','tool_execution'=>'fa-wrench','conversation'=>'fa-comments','agent_deploy'=>'fa-robot','storage'=>'fa-database'];
        foreach ($stats['recent'] as $r):
          $ic = $aIcon[$r['type']] ?? 'fa-circle-dot';
          $desc = $r['description'] !== '' ? $r['description'] : ucfirst(str_replace('_',' ',$r['type']));
          $ts = strtotime($r['created_at']); $diff = time()-$ts;
          $ago = $diff<60?'just now':($diff<3600?floor($diff/60).'m':($diff<86400?floor($diff/3600).'h':floor($diff/86400).'d'));
        ?>
          <div class="feed-item">
            <i class="fa-solid <?= $ic ?>"></i>
            <div class="meta">
              <div class="t1"><?= htmlspecialchars($desc) ?></div>
              <div class="t2"><?= $ago ?> ago · qty <?= (int)$r['quantity'] ?> · <?= htmlspecialchars($r['status']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="footer-cta">
    Everything here comes from your real Alfred data.
    <a href="/api/alfred-ide-billing.php">Billing API</a>·
    <a href="/dashboard.php">Full dashboard</a>·
    <a href="/alfred-ide/?folder=/home/<?= htmlspecialchars(strtolower(explode('@',$clientEmail??'user')[0])) ?>">Return to IDE</a>
  </div>
</div>

<script>
// L3 sparkline render
(() => {
  const data = <?= $sparkJson ?>;
  const max = Math.max(1, ...data);
  const w = 200, h = 44, step = data.length>1 ? w/(data.length-1) : w;
  const pts = data.map((v,i)=>`${(i*step).toFixed(1)},${(h - (v/max)*(h-4) - 2).toFixed(1)}`).join(' ');
  const area = `0,${h} ${pts} ${w},${h}`;
  const svg = document.getElementById('spark');
  svg.insertAdjacentHTML('beforeend',
    `<polyline class="fill" points="${area}"/>
     <polyline points="${pts}"/>`);
})();

// L7 live refresh every 10s
async function refreshFeed() {
  try {
    const r = await fetch('/api/alfred-ide-live.php', {credentials:'same-origin'});
    if (!r.ok) return;
    const j = await r.json();
    if (!j.ok || !Array.isArray(j.recent)) return;
    const icons = {api_call:'fa-code',voice_minute:'fa-microphone',tool_execution:'fa-wrench',conversation:'fa-comments',agent_deploy:'fa-robot',storage:'fa-database'};
    const feed = document.getElementById('feed');
    feed.innerHTML = j.recent.map(r => {
      const ic = icons[r.type] || 'fa-circle-dot';
      const ts = new Date(r.created_at).getTime()/1000;
      const diff = Math.max(0, Math.floor(Date.now()/1000 - ts));
      const ago = diff<60?'just now':diff<3600?Math.floor(diff/60)+'m':diff<86400?Math.floor(diff/3600)+'h':Math.floor(diff/86400)+'d';
      const desc = (r.description||r.type.replace(/_/g,' ')).replace(/</g,'&lt;');
      return `<div class="feed-item"><i class="fa-solid ${ic}"></i><div class="meta"><div class="t1">${desc}</div><div class="t2">${ago} ago · qty ${r.quantity|0} · ${r.status||'ok'}</div></div></div>`;
    }).join('');
    // Optional: top-card counters
    if (j.today) {
      const m = {api_calls:0, voice_minutes:1, tool_runs:2, conversations:3};
      const ns = document.querySelectorAll('.grid.cols-4 .card .n');
      for (const [k,i] of Object.entries(m)) if (ns[i] && j.today[k]!=null) ns[i].textContent = j.today[k] >= 1000 ? (j.today[k]/1000).toFixed(1)+'k' : j.today[k];
    }
  } catch(e) {}
}
setInterval(refreshFeed, 10000);
</script>
</body>
</html>
