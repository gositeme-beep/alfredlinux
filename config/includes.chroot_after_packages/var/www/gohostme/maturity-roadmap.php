<?php
/**
 * GoHostMe — 100-Level Maturity Roadmap (public)
 * Narrative scale + bands + interactive explorer.
 */
$pageTitle = 'GoHostMe — 100-Level Maturity Roadmap';
$bands = [
    ['range' => '8–15', 'title' => 'Reliability engineering on display', 'items' => ['SLOs / error budgets per surface', 'Game days + redacted postmortems', 'Chaos drills on deploy / resize / restore']],
    ['range' => '16–25', 'title' => 'Security as a product', 'items' => ['Bug bounty + disclosure SLAs', 'SSO/SAML + SCIM story', 'Data residency + key custody options']],
    ['range' => '26–35', 'title' => 'Compliance & procurement', 'items' => ['SOC2 / ISO where real', 'Subprocessor register + DPIA templates', 'Enterprise MSA + order schedules']],
    ['range' => '36–45', 'title' => 'Network & edge', 'items' => ['Measured p95 by city', 'Private networking limits + pricing', 'LB + health checks + canaries']],
    ['range' => '46–55', 'title' => 'Data platform maturity', 'items' => ['Legal hold + WORM tiers', 'Cross-region failover stories', 'SIEM export of metrics/logs/traces']],
    ['range' => '56–65', 'title' => 'Developer experience at scale', 'items' => ['SDKs from OpenAPI', 'Prod-like sandboxes + ephemeral envs', 'Deprecation windows']],
    ['range' => '66–75', 'title' => 'FinOps & fairness', 'items' => ['Transparent egress math', 'Commitment + spend dashboards', 'Team chargeback + anomaly alerts']],
    ['range' => '76–85', 'title' => 'Ecosystem gravity', 'items' => ['Certified partners + rev share', 'Regulated reference architectures', 'RFC + public roadmap voting']],
    ['range' => '86–92', 'title' => 'Global operations', 'items' => ['Follow-the-sun + severity matrix', 'Language coverage where promised', 'Incident playbooks (summary public)']],
    ['range' => '93–97', 'title' => 'Research-grade', 'items' => ['Reproducible benchmarks', 'Co-design narrative where true', 'PQC roadmap only if serious']],
    ['range' => '98–100', 'title' => 'North star', 'items' => ['98 — Standards leadership tied to shipped code', '99 — Multi-continent active/active proofs', '100 — Critical-infrastructure posture (only if literal)']],
];

$levels = [
    1 => ['t' => 'Presence & story', 'b' => 'Clear brand, hero, differentiation, CTAs, ecosystem hooks.'],
    2 => ['t' => 'Prove the basics', 'b' => 'Legal/trust, status, DPA/PCI plain language; guided first deploy with real screenshots.'],
    3 => ['t' => 'Product depth', 'b' => 'Panel preview, honest comparisons, docs written against your real panel.'],
    4 => ['t' => 'Automation truth', 'b' => 'Public API = shipped API; OpenAPI/Terraform starter; calculator without “contact sales” for common SKUs.'],
    5 => ['t' => 'Scale proof', 'b' => 'Named stories + metrics; SLA appendix tied to monitoring; reseller runbooks.'],
    6 => ['t' => 'AI contracts', 'b' => 'Data flows, retention, recording/law, provider boundaries, human handoff, limits.'],
    7 => ['t' => 'Platform OS', 'b' => 'Unified identity + billing graph; webhooks + audit export + RBAC; partner marketplace.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?></title>
<meta name="description" content="GoHostMe public roadmap: a 1–100 maturity ladder from story to civilization-grade platform. Bands, detail levels, and how we ship.">
<link rel="icon" href="/favicon.ico">
<style>
:root {
  --bg:#0a0e1a; --surface:#111827; --surface2:#1a2236; --border:rgba(255,255,255,.1);
  --text:#e4e8f0; --dim:#8892a6; --accent:#6366f1; --accent2:#818cf8; --cyan:#22d3ee;
  --green:#10b981; --gold:#fbbf24;
}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:var(--bg);color:var(--text);line-height:1.65}
a{color:var(--cyan);text-decoration:none}a:hover{text-decoration:underline}
.wrap{max-width:1100px;margin:0 auto;padding:28px 20px 80px}
.nav-top{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:28px;flex-wrap:wrap}
.brand{display:flex;align-items:center;gap:10px;font-weight:800;letter-spacing:.02em}
.brand svg{flex-shrink:0}
.hero{padding:36px 28px;border-radius:20px;background:linear-gradient(135deg,rgba(99,102,241,.18),transparent 55%),var(--surface);border:1px solid var(--border);margin-bottom:32px}
.hero h1{font-size:clamp(1.55rem,3vw,2.35rem);line-height:1.2;margin-bottom:12px}
.hero p{color:var(--dim);max-width:72ch}
.pill{display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border-radius:999px;background:rgba(34,211,238,.12);color:var(--cyan);font-size:.82rem;font-weight:600;margin-bottom:14px}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:18px}
@media(max-width:900px){.grid2{grid-template-columns:1fr}}
.card{background:var(--surface2);border:1px solid var(--border);border-radius:16px;padding:20px 22px}
.card h3{font-size:1.05rem;margin-bottom:8px;color:#fff}
.card p,.card li{color:var(--dim);font-size:.95rem}
.card ul{margin:10px 0 0 18px}
.levels{display:grid;gap:12px}
.lrow{display:grid;grid-template-columns:52px 1fr;gap:14px;align-items:start;padding:14px 16px;border-radius:12px;background:rgba(255,255,255,.03);border:1px solid var(--border)}
.ln{font-weight:800;color:var(--gold);font-variant-numeric:tabular-nums}
.band-grid{display:grid;gap:14px}
.band{display:grid;grid-template-columns:88px 1fr;gap:14px;padding:16px 18px;border-radius:14px;background:var(--surface);border:1px solid var(--border)}
.br{font-weight:800;color:var(--accent2);font-size:.9rem;white-space:nowrap}
.explorer{margin-top:36px;padding:24px;border-radius:18px;background:linear-gradient(160deg,rgba(99,102,241,.12),transparent),var(--surface2);border:1px solid var(--border)}
.explorer h2{margin-bottom:12px;font-size:1.25rem}
.slider-row{display:flex;align-items:center;gap:18px;flex-wrap:wrap;margin:16px 0}
input[type=range]{width:min(520px,100%);accent-color:var(--accent)}
#levelReadout{font-size:2rem;font-weight:800;font-variant-numeric:tabular-nums;color:var(--gold)}
#bandReadout{color:var(--dim);max-width:70ch}
.footer{margin-top:48px;padding-top:24px;border-top:1px solid var(--border);color:var(--dim);font-size:.88rem}
.anchor{padding-top:72px;margin-top:-48px}
h2.section{font-size:1.35rem;margin:36px 0 16px}
</style>
</head>
<body>
<div class="wrap">
  <div class="nav-top">
    <a href="/gohostme/" class="brand" style="color:#fff">
      <svg width="32" height="32" viewBox="0 0 32 32" fill="none" aria-hidden="true"><rect width="32" height="32" rx="8" fill="#6366f1"/><path d="M8 16h4v8H8zM14 10h4v14h-4zM20 13h4v11h-4z" fill="#fff"/></svg>
      GoHostMe
    </a>
    <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:.92rem">
      <a href="/gohostme/">Home</a>
      <a href="/gohostme/products">Store</a>
      <a href="/gohostme/dashboard">Dashboard</a>
      <a href="/gohostme/GOHOSTME-MATURITY-LADDER.md">Markdown</a>
    </div>
  </div>

  <div class="hero">
    <div class="pill">Public roadmap · 1 → 100</div>
    <h1>The Ascension Ladder</h1>
    <p>One scale from first impression to civilization-grade infrastructure. Levels <strong>1–7</strong> are spelled out; <strong>8–100</strong> are shipped as <em>bands</em> so we can assign real releases inside each band without maintaining ninety duplicate bullet lists.</p>
  </div>

  <h2 class="section" id="explorer">Interactive explorer</h2>
  <div class="explorer card" style="background:var(--surface2)">
    <p style="color:var(--dim);margin-bottom:8px">Drag the slider — see which band you are exploring and what to tighten next.</p>
    <div class="slider-row">
      <span id="levelReadout">1</span>
      <input type="range" id="lvl" min="1" max="100" value="1" aria-label="Maturity level 1 to 100">
    </div>
    <div id="bandReadout"></div>
  </div>

  <h2 class="section anchor" id="detail">Levels 1–7 (detail)</h2>
  <div class="levels">
<?php foreach ($levels as $n => $L): ?>
    <div class="lrow">
      <div class="ln"><?= (int)$n ?></div>
      <div>
        <strong style="color:#fff"><?= htmlspecialchars($L['t']) ?></strong>
        <p style="margin-top:6px;color:var(--dim)"><?= htmlspecialchars($L['b']) ?></p>
      </div>
    </div>
<?php endforeach; ?>
  </div>

  <h2 class="section anchor" id="bands">Bands 8–100</h2>
  <p style="color:var(--dim);margin:-8px 0 16px">Each band is many micro-releases. Pick one user journey and climb it vertically before spreading wide.</p>
  <div class="band-grid">
<?php foreach ($bands as $b): ?>
    <div class="band">
      <div class="br"><?= htmlspecialchars($b['range']) ?></div>
      <div>
        <h3 style="font-size:1.05rem;margin-bottom:8px;color:#fff"><?= htmlspecialchars($b['title']) ?></h3>
        <ul>
<?php foreach ($b['items'] as $it): ?>
          <li><?= htmlspecialchars($it) ?></li>
<?php endforeach; ?>
        </ul>
      </div>
    </div>
<?php endforeach; ?>
  </div>

  <h2 class="section anchor" id="ship">How we ship against 1–100</h2>
  <div class="grid2">
    <div class="card">
      <h3>Vertical slices</h3>
      <p>Choose a journey (DNS change, backup restore, first VPS). Move it from level <strong>2</strong> (provable) through <strong>4</strong> (API truth) before painting new surfaces.</p>
    </div>
    <div class="card">
      <h3>Never skip truth</h3>
      <p>Levels <strong>2–3</strong> beat louder adjectives. Anything customer-touching gets status, docs, and screenshots before we claim it at scale.</p>
    </div>
  </div>

  <div class="footer">
    GoHostMe is a GoSiteMe product. This page is a living compass — not a certification. For the canonical markdown source, see <a href="/gohostme/GOHOSTME-MATURITY-LADDER.md">GOHOSTME-MATURITY-LADDER.md</a>.
  </div>
</div>
<script>
(function(){
  const bands = [
    {min:1,max:7,label:'Levels 1–7 · Detail track',hint:'Story → proof → depth → API truth → scale → AI contracts → platform OS.'},
    {min:8,max:15,label:'Band 8–15 · Reliability',hint:'SLOs, game days, chaos on deploy/resize/restore.'},
    {min:16,max:25,label:'Band 16–25 · Security product',hint:'Bounty, SAML/SCIM, residency & keys.'},
    {min:26,max:35,label:'Band 26–35 · Compliance',hint:'SOC2 truth, subprocessors, enterprise ordering.'},
    {min:36,max:45,label:'Band 36–45 · Network & edge',hint:'Measured latency, private networking, LB+canaries.'},
    {min:46,max:55,label:'Band 46–55 · Data platform',hint:'Legal hold, replication drills, SIEM export.'},
    {min:56,max:65,label:'Band 56–65 · Developer scale',hint:'SDKs, sandboxes, deprecation policy.'},
    {min:66,max:75,label:'Band 66–75 · FinOps',hint:'Egress math, commitments, chargeback.'},
    {min:76,max:85,label:'Band 76–85 · Ecosystem',hint:'Partners, regulated refs, community RFCs.'},
    {min:86,max:92,label:'Band 86–92 · Global ops',hint:'24/7 matrix, languages, playbooks.'},
    {min:93,max:97,label:'Band 93–97 · Research-grade',hint:'Repro benchmarks, co-design, PQC if real.'},
    {min:98,max:100,label:'Band 98–100 · North star',hint:'Standards leadership → continuity proofs → critical infrastructure (only if literal).'}
  ];
  const el = document.getElementById('lvl');
  const out = document.getElementById('levelReadout');
  const band = document.getElementById('bandReadout');
  function render(){
    const v = parseInt(el.value,10);
    out.textContent = v;
    const b = bands.find(x => v>=x.min && v<=x.max) || bands[0];
    band.innerHTML = '<strong style="color:#e4e8f0">' + b.label + '</strong><br><span style="display:block;margin-top:8px">' + b.hint + '</span>';
  }
  el.addEventListener('input', render);
  render();
})();
</script>
</body>
</html>
