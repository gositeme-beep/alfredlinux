<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$loggedIn = !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VR Land Marketplace — Buy, Sell & Develop Virtual Land</title>
    <meta name="description" content="Browse, buy, and manage virtual land parcels in the GoSiteMe metaverse. Build, lease, and earn revenue from your VR plots.">
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="icon" href="/brand/logo.png" type="image/png">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{--bg:#030308;--surface:#0c0c1a;--surface2:#111126;--border:rgba(100,140,255,0.08);--glow:rgba(100,160,255,0.12);--text:#d8dce8;--dim:#5a6488;--accent:#6c5ce7;--accent2:#a29bfe;--green:#00b894;--red:#ff6b6b;--gold:#ffd700}
        body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
        a{color:var(--accent2);text-decoration:none}
        .container{max-width:1200px;margin:0 auto;padding:2rem 1.5rem}
        .page-header{text-align:center;margin-bottom:2rem}
        .page-header h1{font-size:2rem;font-weight:800;background:linear-gradient(135deg,#00b894,#00cec9);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
        .page-header p{color:var(--dim);margin-top:.5rem}
        .tabs{display:flex;gap:.5rem;justify-content:center;margin-bottom:2rem;flex-wrap:wrap}
        .tab{padding:.6rem 1.2rem;border-radius:10px;background:var(--surface);border:1px solid var(--border);color:var(--dim);cursor:pointer;font-size:.85rem;font-weight:600;transition:.2s}
        .tab.active,.tab:hover{background:var(--accent);color:#fff;border-color:var(--accent)}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:2rem}
        .stat-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1rem;text-align:center}
        .stat-val{font-size:1.5rem;font-weight:800;color:var(--green)}
        .stat-label{font-size:.75rem;color:var(--dim);margin-top:.25rem}
        .land-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem}
        .land-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;overflow:hidden;transition:.3s;cursor:pointer}
        .land-card:hover{border-color:var(--green);transform:translateY(-2px)}
        .land-preview{height:120px;background:linear-gradient(135deg,#0d2818,#1a4030);display:flex;align-items:center;justify-content:center;font-size:2rem;position:relative}
        .land-zone{position:absolute;top:.5rem;left:.5rem;font-size:.65rem;font-weight:700;padding:.2rem .5rem;border-radius:4px;background:rgba(0,0,0,0.5);color:var(--green)}
        .land-body{padding:1rem}
        .land-name{font-weight:700;margin-bottom:.3rem}
        .land-meta{font-size:.8rem;color:var(--dim);margin-bottom:.5rem}
        .land-price{font-size:1.1rem;font-weight:800;color:var(--gold)}
        .land-size{font-size:.75rem;color:var(--dim)}
        .btn{padding:.5rem 1rem;border-radius:8px;border:none;cursor:pointer;font-weight:700;font-size:.8rem}
        .btn-buy{background:var(--green);color:#000;margin-top:.5rem}
        .btn-buy:hover{opacity:.9}
        .map-container{width:100%;height:400px;background:var(--surface);border:1px solid var(--border);border-radius:16px;margin-bottom:2rem;position:relative;overflow:hidden}
        .map-grid{display:grid;width:100%;height:100%;padding:1rem}
        .map-cell{border:1px solid var(--border);border-radius:2px;cursor:pointer;transition:.2s;display:flex;align-items:center;justify-content:center;font-size:.5rem}
        .map-cell:hover{border-color:var(--green);background:rgba(0,184,148,0.1)}
        .map-cell.owned{background:rgba(108,92,231,0.2);border-color:var(--accent)}
        .map-cell.for-sale{background:rgba(0,184,148,0.15);border-color:var(--green)}
        .map-cell.mine{background:rgba(255,215,0,0.2);border-color:var(--gold)}
        .map-legend{display:flex;gap:1rem;justify-content:center;margin-top:.5rem;font-size:.75rem;color:var(--dim)}
        .legend-dot{width:10px;height:10px;border-radius:2px;display:inline-block;margin-right:.3rem}
        .empty{text-align:center;padding:3rem;color:var(--dim)}
        .nav-back{display:inline-flex;align-items:center;gap:.5rem;color:var(--dim);font-size:.85rem;margin-bottom:1rem}
        .nav-back:hover{color:var(--text)}
        .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:100;justify-content:center;align-items:center}
        .modal-overlay.active{display:flex}
        .modal{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:2rem;max-width:500px;width:90%;max-height:80vh;overflow-y:auto}
        @media(max-width:600px){.container{padding:1rem}.land-grid{grid-template-columns:1fr}.map-container{height:250px}}
    </style>
</head>
<body>
<div class="container">
    <a href="/wallet.php" class="nav-back">← Back to Wallet</a>
    <div class="page-header">
        <h1>🗺️ VR Land Marketplace</h1>
        <p>Buy, develop, and trade virtual land parcels across the metaverse.</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-val" id="statTotal">—</div><div class="stat-label">Total Parcels</div></div>
        <div class="stat-card"><div class="stat-val" id="statForSale">—</div><div class="stat-label">For Sale</div></div>
        <div class="stat-card"><div class="stat-val" id="statFloor">—</div><div class="stat-label">Floor Price</div></div>
        <div class="stat-card"><div class="stat-val" id="statVolume">—</div><div class="stat-label">Total Volume</div></div>
    </div>

    <div class="tabs">
        <div class="tab active" onclick="switchTab('browse')">Browse</div>
        <div class="tab" onclick="switchTab('map')">Map View</div>
        <div class="tab" onclick="switchTab('my_land')">My Land</div>
    </div>

    <div id="mapView" style="display:none">
        <div class="map-container" id="mapContainer"></div>
        <div class="map-legend">
            <span><span class="legend-dot" style="background:rgba(0,184,148,0.4)"></span> For Sale</span>
            <span><span class="legend-dot" style="background:rgba(108,92,231,0.4)"></span> Owned</span>
            <span><span class="legend-dot" style="background:rgba(255,215,0,0.4)"></span> Your Land</span>
            <span><span class="legend-dot" style="background:var(--surface2)"></span> Available</span>
        </div>
    </div>

    <div class="land-grid" id="landGrid"></div>
</div>

<div class="modal-overlay" id="parcelModal">
    <div class="modal" id="parcelContent"></div>
</div>

<script>
const API = '/api/vr-land-market.php';
let currentTab = 'browse';

function getCsrf() { const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/); return m ? decodeURIComponent(m[1]) : ''; }

async function api(action, body) {
    const opts = body ? { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':getCsrf()}, credentials:'same-origin', body:JSON.stringify(body) } : { credentials:'same-origin' };
    return (await fetch(API+'?action='+action, opts)).json();
}

function switchTab(tab) {
    currentTab = tab;
    document.querySelectorAll('.tab').forEach((t,i) => t.classList.toggle('active', ['browse','map','my_land'][i]===tab));
    document.getElementById('mapView').style.display = tab==='map' ? 'block' : 'none';
    document.getElementById('landGrid').style.display = tab==='map' ? 'none' : 'grid';
    loadTab();
}

async function loadTab() {
    const grid = document.getElementById('landGrid');
    if (currentTab === 'map') { loadMap(); return; }
    try {
        const action = currentTab === 'my_land' ? 'my_land' : 'list_parcels';
        const d = await api(action);
        const parcels = d.parcels || d.data || [];
        if (!parcels.length) { grid.innerHTML = `<div class="empty">${currentTab==='my_land'?'You don\'t own any land yet':'No parcels available'}</div>`; return; }
        grid.innerHTML = parcels.map(p => {
            const zoneColors = {prime:'#ffd700',commercial:'#00b894',residential:'#6c5ce7',recreational:'#00cec9',wilderness:'#5a6488'};
            const zoneColor = zoneColors[p.zone] || zoneColors.wilderness;
            return `<div class="land-card" onclick="showParcel(${p.id})">
                <div class="land-preview" style="background:linear-gradient(135deg,${zoneColor}22,${zoneColor}44)">
                    <span style="font-size:1.5rem">🗺️</span>
                    <div class="land-zone" style="color:${zoneColor}">${(p.zone||'unknown').toUpperCase()}</div>
                </div>
                <div class="land-body">
                    <div class="land-name">${esc(p.name||'Parcel #'+p.id)}</div>
                    <div class="land-meta">(${p.x||0}, ${p.y||0}) · ${p.size_x||1}×${p.size_y||1}</div>
                    ${p.price_gsm && parseFloat(p.price_gsm) > 0 ? `<div class="land-price">${parseFloat(p.price_gsm).toLocaleString()} GSM</div>` : '<div class="land-size" style="color:var(--dim)">Not for sale</div>'}
                    ${p.price_gsm && parseFloat(p.price_gsm) > 0 && currentTab !== 'my_land' ? '<button class="btn btn-buy" onclick="event.stopPropagation();buyParcel('+p.id+')">Buy Now</button>' : ''}
                </div>
            </div>`;
        }).join('');
    } catch(e) { grid.innerHTML = '<div class="empty">Error loading parcels</div>'; }
}

async function loadMap() {
    try {
        const d = await api('map_data');
        const parcels = d.parcels || d.data || [];
        const container = document.getElementById('mapContainer');
        const cols = 16, rows = 12;
        container.innerHTML = '';
        const mapGrid = document.createElement('div');
        mapGrid.className = 'map-grid';
        mapGrid.style.gridTemplateColumns = `repeat(${cols}, 1fr)`;
        mapGrid.style.gridTemplateRows = `repeat(${rows}, 1fr)`;
        const parcelMap = {};
        parcels.forEach(p => { parcelMap[`${p.x},${p.y}`] = p; });
        for (let y = 0; y < rows; y++) {
            for (let x = 0; x < cols; x++) {
                const cell = document.createElement('div');
                cell.className = 'map-cell';
                const p = parcelMap[`${x},${y}`];
                if (p) {
                    if (p.is_mine) cell.classList.add('mine');
                    else if (p.for_sale) cell.classList.add('for-sale');
                    else cell.classList.add('owned');
                    cell.title = `${p.name||'Parcel'} (${x},${y})`;
                    cell.onclick = () => showParcel(p.id);
                }
                mapGrid.appendChild(cell);
            }
        }
        container.appendChild(mapGrid);
    } catch(e) {}
}

async function showParcel(id) {
    const d = await api('parcel_detail&id=' + id);
    const p = d.parcel || d.data || {};
    document.getElementById('parcelContent').innerHTML = `
        <h2>${esc(p.name||'Parcel #'+p.id)}</h2>
        <div style="color:var(--dim);font-size:.85rem;margin-bottom:1rem">
            <p>Location: (${p.x||0}, ${p.y||0}) · Size: ${p.size_x||1}×${p.size_y||1}</p>
            <p>Zone: ${p.zone||'unknown'} · Type: ${p.terrain_type||'flat'}</p>
            ${p.owner_name ? `<p>Owner: ${esc(p.owner_name)}</p>` : '<p>Unowned</p>'}
            ${p.description ? `<p>${esc(p.description)}</p>` : ''}
        </div>
        ${p.price_gsm && parseFloat(p.price_gsm) > 0 ? `<div style="font-size:1.3rem;font-weight:800;color:var(--gold);margin:.5rem 0">${parseFloat(p.price_gsm).toLocaleString()} GSM</div><button class="btn btn-buy" onclick="buyParcel(${p.id})">Buy This Parcel</button>` : ''}
        <button class="btn" style="background:var(--surface2);color:var(--dim);margin-top:.5rem;margin-left:.5rem" onclick="closeParcel()">Close</button>`;
    document.getElementById('parcelModal').classList.add('active');
}

function closeParcel() { document.getElementById('parcelModal').classList.remove('active'); }
document.getElementById('parcelModal').addEventListener('click', e => { if (e.target===e.currentTarget) closeParcel(); });

async function buyParcel(id) {
    if (!confirm('Purchase this parcel?')) return;
    const d = await api('purchase', { parcel_id: id, currency: 'gsm' });
    if (d.success) { alert('Parcel purchased!'); closeParcel(); loadTab(); loadStats(); }
    else { alert(d.error || 'Purchase failed'); }
}

async function loadStats() {
    try {
        const d = await api('land_stats');
        const s = d.stats || d.data || {};
        document.getElementById('statTotal').textContent = s.total_parcels || 0;
        document.getElementById('statForSale').textContent = s.for_sale || 0;
        document.getElementById('statFloor').textContent = s.floor_price ? parseFloat(s.floor_price).toLocaleString() + ' GSM' : '—';
        document.getElementById('statVolume').textContent = s.total_volume ? parseFloat(s.total_volume).toLocaleString() + ' GSM' : '—';
    } catch(e){}
}

function esc(s) { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }

loadStats();
loadTab();
</script>
</body>
</html>
