<?php
if (isset($_GET['json']) && $_GET['json'] === '1') {
    define('GOSITEME_API', true);
    require_once __DIR__ . '/api/config.php';

    header('Content-Type: application/json');

    try {
        $cacheFile = __DIR__ . '/storage/fleet-live-cache.json';
        $cacheTtl = 15;
        if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
            readfile($cacheFile);
            exit;
        }

        $pdo = getDB();
        $metrics = $pdo->query("SELECT * FROM fleet_metrics_cache WHERE metric_key = 'fleet-50m' LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: null;

        $progress = [
            'last_wave' => 0,
            'total_spawned' => 0,
            'initial_fleet' => 0,
            'fleet_estimate' => 0,
            'peak_cpu' => 0,
            'peak_mem_pct' => 0,
            'cpu_pauses' => 0,
        ];

        $progressFile = __DIR__ . '/storage/fleet-50m-progress.json';
        if (is_file($progressFile)) {
            $decoded = json_decode((string)file_get_contents($progressFile), true);
            if (is_array($decoded)) {
                $progress = array_merge($progress, $decoded);
            }
        }

        $fleet = $metrics ? (int)$metrics['fleet'] : (int)($progress['fleet_estimate'] ?? 0);
        if ($fleet <= 0) {
            $fleet = (int)$pdo->query("SELECT COALESCE(TABLE_ROWS,0) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'alfred_agent_registry'")->fetchColumn();
        }
        $domains = $metrics ? (int)$metrics['domains'] : 126;

        $load = sys_getloadavg();
        $cores = 12;
        $cpuPct = $cores > 0 ? round(($load[0] / $cores) * 100, 1) : 0;

        $meminfo = @file_get_contents('/proc/meminfo');
        $memPct = 0;
        $memUsedMb = 0;
        $memTotalMb = 0;
        if ($meminfo !== false) {
            preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
            preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $avail);
            $memTotalMb = isset($total[1]) ? (int)round(((int)$total[1]) / 1024) : 0;
            $memAvailMb = isset($avail[1]) ? (int)round(((int)$avail[1]) / 1024) : 0;
            $memUsedMb = max(0, $memTotalMb - $memAvailMb);
            $memPct = $memTotalMb > 0 ? round(($memUsedMb / $memTotalMb) * 100, 1) : 0;
        }

        $payload = [
            'ok' => true,
            'fleet' => $fleet,
            'target' => 50010001,
            'domains' => $domains,
            'wave' => $metrics ? (int)$metrics['wave'] : (int)($progress['last_wave'] ?? 0),
            'waves_total' => 900,
            'spawned' => $metrics ? (int)$metrics['spawned'] : (int)($progress['total_spawned'] ?? 0),
            'peak_cpu' => $metrics ? (float)$metrics['peak_cpu'] : (float)($progress['peak_cpu'] ?? 0),
            'peak_ram' => $metrics ? (float)$metrics['peak_ram'] : (float)($progress['peak_mem_pct'] ?? 0),
            'cpu_pauses' => $metrics ? (int)$metrics['cpu_pauses'] : (int)($progress['cpu_pauses'] ?? 0),
            'status' => [
                'active' => 0,
                'idle' => 0,
                'busy' => 0,
                'offline' => 0,
                'error' => 0,
            ],
            'load_1' => $metrics ? (float)$metrics['load_1'] : round($load[0], 2),
            'load_5' => $metrics ? (float)$metrics['load_5'] : round($load[1], 2),
            'load_15' => $metrics ? (float)$metrics['load_15'] : round($load[2], 2),
            'cpu_pct' => $metrics ? (float)$metrics['cpu_pct'] : $cpuPct,
            'mem_used_mb' => $metrics ? (int)$metrics['mem_used_mb'] : $memUsedMb,
            'mem_total_mb' => $metrics ? (int)$metrics['mem_total_mb'] : $memTotalMb,
            'mem_pct' => $metrics ? (float)$metrics['mem_pct'] : $memPct,
            'updated_at' => $metrics && !empty($metrics['updated_at']) ? ($metrics['updated_at'] . ' UTC') : (gmdate('Y-m-d H:i:s') . ' UTC'),
        ];

        $json = json_encode($payload);
        file_put_contents($cacheFile, $json, LOCK_EX);
        echo $json;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'error' => 'Unable to fetch status',
            'details' => $e->getMessage(),
        ]);
    }
    exit;
}

$pageTitle = 'Fleet Live Panel';
$pageDescription = 'Real-time live panel for Alfred 50M fleet expansion';
include 'includes/site-header.inc.php';
?>
<style>
  .fl-wrap { max-width: 1100px; margin: 24px auto; padding: 16px; }
  .fl-title { font-size: 2rem; margin: 0 0 12px; }
  .fl-sub { opacity: .85; margin: 0 0 20px; }
  .fl-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px,1fr)); gap: 12px; }
  .fl-card { border: 1px solid rgba(255,255,255,.12); border-radius: 12px; padding: 14px; background: rgba(255,255,255,.03); }
  .fl-k { font-size: .85rem; opacity: .75; margin-bottom: 6px; }
  .fl-v { font-size: 1.5rem; font-weight: 700; }
  .fl-progress { height: 16px; background: rgba(255,255,255,.1); border-radius: 999px; overflow: hidden; margin-top: 10px; }
  .fl-progress > div { height: 100%; width: 0%; background: linear-gradient(90deg, #00e5ff, #00ffa3); transition: width .6s ease; }
  .fl-foot { margin-top: 16px; font-size: .9rem; opacity: .8; }
</style>

<div class="fl-wrap">
  <h1 class="fl-title">Alfred Fleet Live Panel</h1>
  <p class="fl-sub">Live view of the 50M expansion. Auto-refresh every 10 seconds.</p>

  <div class="fl-grid">
    <div class="fl-card"><div class="fl-k">Fleet Total</div><div class="fl-v" id="fleet">-</div></div>
    <div class="fl-card"><div class="fl-k">Target</div><div class="fl-v" id="target">50,010,001</div></div>
    <div class="fl-card"><div class="fl-k">Wave</div><div class="fl-v" id="wave">-</div></div>
    <div class="fl-card"><div class="fl-k">Spawned This Run</div><div class="fl-v" id="spawned">-</div></div>
    <div class="fl-card"><div class="fl-k">CPU Load (1/5/15)</div><div class="fl-v" id="load">-</div></div>
    <div class="fl-card"><div class="fl-k">CPU %</div><div class="fl-v" id="cpu">-</div></div>
    <div class="fl-card"><div class="fl-k">RAM %</div><div class="fl-v" id="ram">-</div></div>
    <div class="fl-card"><div class="fl-k">Peak CPU</div><div class="fl-v" id="peakcpu">-</div></div>
    <div class="fl-card"><div class="fl-k">Peak RAM</div><div class="fl-v" id="peakram">-</div></div>
    <div class="fl-card"><div class="fl-k">CPU Pauses</div><div class="fl-v" id="pauses">-</div></div>
    <div class="fl-card"><div class="fl-k">Active / Idle</div><div class="fl-v" id="status">-</div></div>
    <div class="fl-card"><div class="fl-k">Domains</div><div class="fl-v" id="domains">-</div></div>
  </div>

  <div class="fl-progress"><div id="bar"></div></div>
  <div class="fl-foot">Updated: <span id="updated">-</span></div>
</div>

<script>
(function () {
  function fmt(n) {
    return Number(n || 0).toLocaleString();
  }

  async function tick() {
    try {
      const res = await fetch('fleet-live-panel.php?json=1', { cache: 'no-store' });
      const d = await res.json();
      if (!d.ok) return;

      document.getElementById('fleet').textContent = fmt(d.fleet);
      document.getElementById('target').textContent = fmt(d.target);
      document.getElementById('wave').textContent = `${fmt(d.wave)} / ${fmt(d.waves_total)}`;
      document.getElementById('spawned').textContent = fmt(d.spawned);
      document.getElementById('load').textContent = `${d.load_1} / ${d.load_5} / ${d.load_15}`;
      document.getElementById('cpu').textContent = `${d.cpu_pct}%`;
      document.getElementById('ram').textContent = (d.mem_total_mb && d.mem_total_mb > 0) ? `${d.mem_pct}%` : 'N/A';
      document.getElementById('peakcpu').textContent = d.peak_cpu;
      document.getElementById('peakram').textContent = `${d.peak_ram}%`;
      document.getElementById('pauses').textContent = fmt(d.cpu_pauses);
      document.getElementById('status').textContent = `${fmt(d.status.active)} / ${fmt(d.status.idle)}`;
      document.getElementById('domains').textContent = fmt(d.domains);
      document.getElementById('updated').textContent = d.updated_at;

      const pct = Math.max(0, Math.min(100, (d.fleet / d.target) * 100));
      document.getElementById('bar').style.width = pct.toFixed(2) + '%';
    } catch (e) {
      // Keep panel alive even if one poll fails.
    }
  }

  tick();
  setInterval(tick, 10000);
})();
</script>

<?php include 'includes/site-footer.inc.php'; ?>
