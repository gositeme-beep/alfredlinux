<?php
/**
 * Emergency Kit — Apocalypse-ready survival knowledge hub
 * Offline-capable, cached via Service Worker, zero-dependency
 */
$page_title = 'Emergency Kit — Sovereign Survival Systems | GoSiteMe';
$page_description = 'Apocalypse-ready emergency systems: offline medical guides, mesh communications, cached maps, survival knowledge, and decentralized coordination. Works when the internet doesn\'t.';
$page_canonical = 'https://root.com/emergency-kit';
require_once __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<link rel="stylesheet" href="/assets/css/fonts.css">

<style>
:root {
    --ek-bg: #0a0808;
    --ek-surface: #141010;
    --ek-card: rgba(255,60,60,0.03);
    --ek-border: rgba(255,80,80,0.1);
    --ek-red: #ef4444;
    --ek-amber: #fbbf24;
    --ek-green: #34d399;
    --ek-blue: #60a5fa;
    --ek-text: rgba(255,255,255,0.88);
    --ek-muted: rgba(255,255,255,0.5);
    --ek-radius: 16px;
}
.ek-page { background: var(--ek-bg); color: var(--ek-text); font-family: 'Inter','DM Sans',system-ui,sans-serif; min-height: 100vh; }
.ek-page a { color: var(--ek-blue); text-decoration: none; }
.ek-page a:hover { text-decoration: underline; }

/* Hero */
.ek-hero {
    position: relative;
    text-align: center;
    padding: clamp(60px,12vw,120px) 20px 60px;
    overflow: hidden;
}
.ek-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    left: 50%;
    width: 800px;
    height: 800px;
    transform: translateX(-50%);
    background: radial-gradient(circle, rgba(239,68,68,0.08) 0%, transparent 70%);
    pointer-events: none;
}
.ek-alert-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    border-radius: 50px;
    background: rgba(239,68,68,0.1);
    border: 1px solid rgba(239,68,68,0.2);
    color: var(--ek-red);
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 24px;
    animation: ek-pulse 2s ease-in-out infinite;
}
@keyframes ek-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.2); }
    50% { box-shadow: 0 0 0 8px rgba(239,68,68,0); }
}
.ek-hero h1 {
    font-size: clamp(36px, 6vw, 64px);
    font-weight: 900;
    letter-spacing: -2px;
    line-height: 1.1;
    margin-bottom: 16px;
    background: linear-gradient(135deg, #ef4444, #fbbf24);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.ek-hero p {
    font-size: clamp(15px, 2vw, 18px);
    color: var(--ek-muted);
    max-width: 640px;
    margin: 0 auto 32px;
    line-height: 1.7;
}
.ek-cta-row {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}
.ek-cta {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    font-family: inherit;
}
.ek-cta-primary {
    background: linear-gradient(135deg, var(--ek-red), #dc2626);
    color: #fff;
}
.ek-cta-primary:hover { filter: brightness(1.1); transform: translateY(-1px); text-decoration: none; }
.ek-cta-secondary {
    background: rgba(255,255,255,0.06);
    color: var(--ek-text);
    border: 1px solid rgba(255,255,255,0.1);
}
.ek-cta-secondary:hover { border-color: rgba(255,255,255,0.2); text-decoration: none; }

/* Section */
.ek-container { max-width: 1100px; margin: 0 auto; padding: 0 20px; }
.ek-section { padding: 60px 0; }
.ek-section-title {
    font-size: clamp(24px, 4vw, 36px);
    font-weight: 800;
    letter-spacing: -1px;
    margin-bottom: 12px;
}
.ek-section-sub {
    color: var(--ek-muted);
    font-size: 15px;
    margin-bottom: 32px;
    max-width: 600px;
}

/* Card Grid */
.ek-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
.ek-card {
    background: var(--ek-card);
    border: 1px solid var(--ek-border);
    border-radius: var(--ek-radius);
    padding: 28px;
    transition: all 0.25s;
    position: relative;
    overflow: hidden;
}
.ek-card:hover {
    transform: translateY(-2px);
    border-color: rgba(255,80,80,0.2);
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
}
.ek-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    margin-bottom: 16px;
}
.ek-card h3 { font-size: 18px; font-weight: 700; margin-bottom: 8px; color: #fff; }
.ek-card p { font-size: 14px; color: var(--ek-muted); line-height: 1.7; }
.ek-card-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 50px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-top: 12px;
}

/* Offline indicator */
.ek-offline-bar {
    background: rgba(52,211,153,0.06);
    border-bottom: 1px solid rgba(52,211,153,0.1);
    padding: 8px 20px;
    text-align: center;
    font-size: 12px;
    color: var(--ek-green);
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.ek-offline-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--ek-green);
    animation: ek-blink 2s infinite;
}
@keyframes ek-blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

/* Knowledge modules */
.ek-module {
    background: var(--ek-surface);
    border: 1px solid var(--ek-border);
    border-radius: var(--ek-radius);
    margin-bottom: 16px;
    overflow: hidden;
}
.ek-module-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px 24px;
    cursor: pointer;
    transition: background 0.2s;
}
.ek-module-header:hover { background: rgba(255,255,255,0.02); }
.ek-module-header i { font-size: 18px; width: 24px; text-align: center; }
.ek-module-header h3 { flex: 1; font-size: 16px; font-weight: 600; }
.ek-module-header .ek-expand { color: var(--ek-muted); transition: transform 0.3s; }
.ek-module.open .ek-expand { transform: rotate(180deg); }
.ek-module-body {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s ease;
}
.ek-module.open .ek-module-body { max-height: 2000px; }
.ek-module-content {
    padding: 0 24px 24px;
    font-size: 14px;
    line-height: 1.8;
    color: var(--ek-muted);
}
.ek-module-content h4 { color: #fff; font-size: 15px; margin: 16px 0 8px; }
.ek-module-content ul { padding-left: 20px; }
.ek-module-content li { margin-bottom: 6px; }
.ek-module-content strong { color: var(--ek-text); }

/* Status dashboard */
.ek-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 12px;
    margin-top: 24px;
}
.ek-status-item {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 12px;
    padding: 16px;
    text-align: center;
}
.ek-status-val {
    font-size: 24px;
    font-weight: 800;
    margin-bottom: 4px;
}
.ek-status-lbl {
    font-size: 11px;
    color: var(--ek-muted);
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Responsive */
@media (max-width: 768px) {
    .ek-hero h1 { letter-spacing: -1px; }
    .ek-grid { grid-template-columns: 1fr; }
    .ek-status-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<div class="ek-page">
    <!-- Offline Status -->
    <div class="ek-offline-bar">
        <div class="ek-offline-dot"></div>
        <span id="ekStatus">Online — All systems operational</span>
        <span style="margin-left:12px;opacity:0.5;">|</span>
        <span style="margin-left:12px;opacity:0.7;">This page works offline via Service Worker cache</span>
    </div>

    <!-- Hero -->
    <div class="ek-hero">
        <div class="ek-alert-badge"><i class="fas fa-broadcast-tower"></i> Sovereign Emergency Systems</div>
        <h1>When Everything Else Fails</h1>
        <p>Critical survival knowledge, mesh communications, offline maps, and medical guides — all cached locally, encrypted, and accessible even when the internet is gone.</p>
        <div class="ek-cta-row">
            <button class="ek-cta ek-cta-primary" onclick="cacheAll()"><i class="fas fa-download"></i> Cache Everything Offline</button>
            <a href="/search?mode=emergency" class="ek-cta ek-cta-secondary"><i class="fas fa-search"></i> Emergency Search</a>
        </div>
    </div>

    <div class="ek-container">
        <!-- Readiness Status -->
        <div class="ek-section">
            <h2 class="ek-section-title">Your Readiness Status</h2>
            <p class="ek-section-sub">How prepared is your local cache for offline survival scenarios.</p>
            <div class="ek-status-grid">
                <div class="ek-status-item">
                    <div class="ek-status-val" style="color:var(--ek-green);" id="ekCachePages">-</div>
                    <div class="ek-status-lbl">Pages Cached</div>
                </div>
                <div class="ek-status-item">
                    <div class="ek-status-val" style="color:var(--ek-blue);" id="ekCacheSize">-</div>
                    <div class="ek-status-lbl">Cache Size</div>
                </div>
                <div class="ek-status-item">
                    <div class="ek-status-val" style="color:var(--ek-amber);" id="ekSwStatus">-</div>
                    <div class="ek-status-lbl">Service Worker</div>
                </div>
                <div class="ek-status-item">
                    <div class="ek-status-val" style="color:var(--ek-red);" id="ekLastSync">-</div>
                    <div class="ek-status-lbl">Last Sync</div>
                </div>
            </div>
        </div>

        <!-- Emergency Systems -->
        <div class="ek-section">
            <h2 class="ek-section-title" id="systems">Emergency Systems</h2>
            <p class="ek-section-sub">Critical systems designed to operate in grid-down, internet-dark scenarios.</p>
            <div class="ek-grid">
                <div class="ek-card">
                    <div class="ek-card-icon" style="background:rgba(239,68,68,0.1);color:var(--ek-red);">
                        <i class="fas fa-first-aid"></i>
                    </div>
                    <h3>Medical Triage System</h3>
                    <p>Step-by-step medical emergency procedures, wound care, fracture management, CPR protocols, and medication guides. All cached locally.</p>
                    <div class="ek-card-tag" style="background:rgba(52,211,153,0.1);color:var(--ek-green);"><i class="fas fa-check"></i> Offline Ready</div>
                </div>
                <div class="ek-card" id="comms">
                    <div class="ek-card-icon" style="background:rgba(251,191,36,0.1);color:var(--ek-amber);">
                        <i class="fas fa-satellite"></i>
                    </div>
                    <h3>Mesh Communications</h3>
                    <p>Peer-to-peer messaging via WebRTC, Bluetooth LE, and local WiFi hotspot mesh. No internet required. Encrypted end-to-end via Veil Protocol.</p>
                    <div class="ek-card-tag" style="background:rgba(251,191,36,0.1);color:var(--ek-amber);"><i class="fas fa-flask"></i> Experimental</div>
                </div>
                <div class="ek-card" id="maps">
                    <div class="ek-card-icon" style="background:rgba(52,211,153,0.1);color:var(--ek-green);">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <h3>Offline Maps &amp; Navigation</h3>
                    <p>Cache map tiles for your region. GPS works without internet — satellite triangulation is independent of cell networks. Compass, waypoints, rally points.</p>
                    <div class="ek-card-tag" style="background:rgba(96,165,250,0.1);color:var(--ek-blue);"><i class="fas fa-download"></i> Downloadable</div>
                </div>
                <div class="ek-card">
                    <div class="ek-card-icon" style="background:rgba(96,165,250,0.1);color:var(--ek-blue);">
                        <i class="fas fa-water"></i>
                    </div>
                    <h3>Water &amp; Food Safety</h3>
                    <p>Water purification methods, food preservation, wild edible identification, caloric needs calculator, and rationing plans for extended scenarios.</p>
                    <div class="ek-card-tag" style="background:rgba(52,211,153,0.1);color:var(--ek-green);"><i class="fas fa-check"></i> Offline Ready</div>
                </div>
                <div class="ek-card">
                    <div class="ek-card-icon" style="background:rgba(168,85,247,0.1);color:#a855f7;">
                        <i class="fas fa-broadcast-tower"></i>
                    </div>
                    <h3>Emergency Broadcasts</h3>
                    <p>Receive and publish emergency alerts through the Alfred infrastructure. Sovereign broadcast system independent of government EAS.</p>
                    <div class="ek-card-tag" style="background:rgba(251,191,36,0.1);color:var(--ek-amber);"><i class="fas fa-flask"></i> Coming Soon</div>
                </div>
                <div class="ek-card">
                    <div class="ek-card-icon" style="background:rgba(219,39,119,0.1);color:#db2777;">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Shelter &amp; Defense</h3>
                    <p>Emergency shelter construction, fire-starting techniques, weather protection, and security perimeter setup guides. Print-ready documents.</p>
                    <div class="ek-card-tag" style="background:rgba(52,211,153,0.1);color:var(--ek-green);"><i class="fas fa-check"></i> Offline Ready</div>
                </div>
            </div>
        </div>

        <!-- Knowledge Modules (expandable) -->
        <div class="ek-section" id="medical">
            <h2 class="ek-section-title">Survival Knowledge Base</h2>
            <p class="ek-section-sub">Expandable reference guides — all stored locally for offline access.</p>

            <div class="ek-module" data-module="medical">
                <div class="ek-module-header" onclick="toggleModule(this)">
                    <i class="fas fa-heartbeat" style="color:var(--ek-red);"></i>
                    <h3>Medical Emergency Protocols</h3>
                    <i class="fas fa-chevron-down ek-expand"></i>
                </div>
                <div class="ek-module-body">
                    <div class="ek-module-content">
                        <h4>Cardiac Emergency (CPR)</h4>
                        <ul>
                            <li><strong>Check responsiveness</strong> — tap shoulders, shout. Call for help.</li>
                            <li><strong>Open airway</strong> — head tilt, chin lift. Look, listen, feel for breathing.</li>
                            <li><strong>30 compressions</strong> — center of chest, 2 inches deep, 100-120/min.</li>
                            <li><strong>2 rescue breaths</strong> — pinch nose, seal mouth, 1 second each.</li>
                            <li><strong>Continue 30:2</strong> until AED arrives or emergency services respond.</li>
                        </ul>
                        <h4>Severe Bleeding</h4>
                        <ul>
                            <li><strong>Direct pressure</strong> — apply firm pressure with clean cloth. Do not remove.</li>
                            <li><strong>Tourniquet</strong> — 2-3 inches above wound, tighten until bleeding stops. Note time.</li>
                            <li><strong>Elevation</strong> — raise injured limb above heart level if possible.</li>
                            <li><strong>Shock prevention</strong> — lay flat, elevate legs, keep warm, monitor breathing.</li>
                        </ul>
                        <h4>Fractures &amp; Splinting</h4>
                        <ul>
                            <li><strong>Immobilize</strong> — splint the joint above and below the break.</li>
                            <li><strong>Materials</strong> — sticks, rolled magazines, boards. Pad with cloth.</li>
                            <li><strong>Check circulation</strong> — feel for pulse below splint, check skin color.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="ek-module" data-module="water">
                <div class="ek-module-header" onclick="toggleModule(this)">
                    <i class="fas fa-tint" style="color:var(--ek-blue);"></i>
                    <h3>Water Purification Methods</h3>
                    <i class="fas fa-chevron-down ek-expand"></i>
                </div>
                <div class="ek-module-body">
                    <div class="ek-module-content">
                        <h4>Boiling</h4>
                        <ul>
                            <li>Bring to rolling boil for <strong>1 minute</strong> (3 minutes above 6,500 ft elevation).</li>
                            <li>Kills bacteria, viruses, and parasites. Does not remove chemicals.</li>
                        </ul>
                        <h4>Chemical Treatment</h4>
                        <ul>
                            <li><strong>Bleach</strong> — 2 drops per quart of clear water. 4 drops if cloudy. Wait 30 min.</li>
                            <li><strong>Iodine</strong> — 5 drops per quart. Wait 30 min. Not for pregnant/thyroid issues.</li>
                        </ul>
                        <h4>Solar Disinfection (SODIS)</h4>
                        <ul>
                            <li>Fill clear PET bottles. Expose to direct sunlight for <strong>6+ hours</strong>.</li>
                            <li>UV radiation kills pathogens. Only works with clear water.</li>
                        </ul>
                        <h4>Filtration</h4>
                        <ul>
                            <li><strong>DIY filter</strong> — layers of gravel, sand, charcoal in a container.</li>
                            <li>Removes sediment and some bacteria. Follow with boiling for full safety.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="ek-module" data-module="comms">
                <div class="ek-module-header" onclick="toggleModule(this)">
                    <i class="fas fa-broadcast-tower" style="color:var(--ek-amber);"></i>
                    <h3>Communication Without Internet</h3>
                    <i class="fas fa-chevron-down ek-expand"></i>
                </div>
                <div class="ek-module-body">
                    <div class="ek-module-content">
                        <h4>Alfred Mesh Network</h4>
                        <ul>
                            <li><strong>WiFi Direct</strong> — create local hotspot, other Alfred devices auto-connect.</li>
                            <li><strong>Bluetooth LE</strong> — short-range, low-power message relay, hop-by-hop.</li>
                            <li><strong>WebRTC P2P</strong> — if any path to another device exists, Veil-encrypted comms.</li>
                        </ul>
                        <h4>Radio Backup</h4>
                        <ul>
                            <li><strong>FRS/GMRS</strong> — Family Radio Service: channels 1-22, 0.5-2W, no license needed (FRS).</li>
                            <li><strong>HAM</strong> — requires license. 2m/70cm bands for regional comms.</li>
                            <li><strong>CB Radio</strong> — channel 9 = emergency, channel 19 = highway. 4W limit.</li>
                        </ul>
                        <h4>Signal Codes</h4>
                        <ul>
                            <li><strong>SOS</strong> — 3 short, 3 long, 3 short (mirror, flashlight, horn).</li>
                            <li><strong>Ground signals</strong> — V = need assistance, X = need medical, → = traveling this direction.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="ek-module" data-module="shelter">
                <div class="ek-module-header" onclick="toggleModule(this)">
                    <i class="fas fa-home" style="color:var(--ek-green);"></i>
                    <h3>Shelter &amp; Fire</h3>
                    <i class="fas fa-chevron-down ek-expand"></i>
                </div>
                <div class="ek-module-body">
                    <div class="ek-module-content">
                        <h4>Emergency Shelter Types</h4>
                        <ul>
                            <li><strong>Debris hut</strong> — ridgepole + ribs + leaves/debris. Insulates to -20&deg;F.</li>
                            <li><strong>Snow cave</strong> — dig into snowbank, create shelf above entrance. Block wind.</li>
                            <li><strong>Tarp shelter</strong> — A-frame with paracord. 10 min setup. Rain protection.</li>
                        </ul>
                        <h4>Fire Starting</h4>
                        <ul>
                            <li><strong>Ferro rod</strong> — scrape with knife spine at 45&deg; angle toward tinder.</li>
                            <li><strong>Bow drill</strong> — socket + spindle + fireboard + bow. Friction fire.</li>
                            <li><strong>Battery + steel wool</strong> — touch 9V battery to fine steel wool. Instant ignition.</li>
                        </ul>
                        <h4>Rule of 3s</h4>
                        <ul>
                            <li><strong>3 minutes</strong> without air</li>
                            <li><strong>3 hours</strong> without shelter (extreme weather)</li>
                            <li><strong>3 days</strong> without water</li>
                            <li><strong>3 weeks</strong> without food</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="ek-module" data-module="navigation">
                <div class="ek-module-header" onclick="toggleModule(this)">
                    <i class="fas fa-compass" style="color:#a855f7;"></i>
                    <h3>Navigation Without GPS</h3>
                    <i class="fas fa-chevron-down ek-expand"></i>
                </div>
                <div class="ek-module-body">
                    <div class="ek-module-content">
                        <h4>Natural Navigation</h4>
                        <ul>
                            <li><strong>Sun</strong> — rises East, sets West. Shadow tip method: mark shadow tip, wait 15 min, line between marks = E-W.</li>
                            <li><strong>Stars (Northern)</strong> — find Big Dipper, extend pointer stars 5x = Polaris (North).</li>
                            <li><strong>Stars (Southern)</strong> — Southern Cross, extend long axis 4.5x toward horizon = South.</li>
                            <li><strong>Moon</strong> — crescent: line through tips meets horizon at roughly South (N. hemisphere).</li>
                        </ul>
                        <h4>Map Reading</h4>
                        <ul>
                            <li><strong>Contour lines</strong> — close together = steep. Far apart = gentle slope.</li>
                            <li><strong>Declination</strong> — difference between true north and magnetic north. Adjust compass.</li>
                            <li><strong>Pace count</strong> — count double-steps per 100m. Know your count for distance estimation.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Technology Stack -->
        <div class="ek-section">
            <h2 class="ek-section-title">Built for the Worst Case</h2>
            <p class="ek-section-sub">The technology behind sovereign emergency systems.</p>
            <div class="ek-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
                <div class="ek-card">
                    <h3 style="font-size:15px;"><i class="fas fa-wifi-slash" style="color:var(--ek-amber);margin-right:8px;"></i>Offline-First Architecture</h3>
                    <p>All critical pages cached via Service Worker. IndexedDB for structured data. Works with zero connectivity.</p>
                </div>
                <div class="ek-card">
                    <h3 style="font-size:15px;"><i class="fas fa-lock" style="color:var(--ek-green);margin-right:8px;"></i>Veil-Encrypted Mesh</h3>
                    <p>AES-256-GCM + Kyber-1024 post-quantum encryption on all mesh communications. Even relay nodes can't read your messages.</p>
                </div>
                <div class="ek-card">
                    <h3 style="font-size:15px;"><i class="fas fa-battery-full" style="color:var(--ek-blue);margin-right:8px;"></i>Low-Power Design</h3>
                    <p>Minimal JS, no frameworks, tiny assets. Designed for devices running on battery power or solar chargers.</p>
                </div>
                <div class="ek-card">
                    <h3 style="font-size:15px;"><i class="fas fa-print" style="color:var(--ek-red);margin-right:8px;"></i>Print-Ready Guides</h3>
                    <p>Every module has a print-optimized stylesheet. Print critical guides before an emergency — paper doesn't need batteries.</p>
                </div>
            </div>
        </div>

        <!-- Integration with ecosystem -->
        <div class="ek-section" style="text-align:center;padding-bottom:80px;">
            <h2 class="ek-section-title">Part of the Alfred Ecosystem</h2>
            <p class="ek-section-sub" style="margin:0 auto 32px;">Emergency Kit is integrated with every other sovereign system.</p>
            <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
                <a href="/search?mode=emergency" class="ek-cta ek-cta-secondary"><i class="fas fa-search"></i> Emergency Search</a>
                <a href="/alfred-browser" class="ek-cta ek-cta-secondary"><i class="fas fa-globe-americas"></i> Alfred Browser</a>
                <a href="/veil/" class="ek-cta ek-cta-secondary"><i class="fas fa-shield-alt"></i> Veil Protocol</a>
                <a href="/security" class="ek-cta ek-cta-secondary"><i class="fas fa-lock"></i> Security</a>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/emergency-kit-engine.js"></script>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
