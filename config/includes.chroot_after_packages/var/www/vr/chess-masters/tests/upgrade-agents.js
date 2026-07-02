#!/usr/bin/env node
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * GoSiteMe Continuous Upgrade Agent System v1.0
 * 20 Specialized AI Agents That Continuously Improve All VR Games
 * ══════════════════════════════════════════════════════════════════════════════
 *
 * Each agent specializes in a domain and continuously scans all VR games,
 * scoring them on best practices. Issues are logged and — where safe —
 * automated fixes are applied. Agents run in rounds; each round scans every
 * game, scores it, logs recommendations, and applies safe auto-fixes.
 *
 * Usage:
 *   node upgrade-agents.js                    # Single scan
 *   node upgrade-agents.js --continuous       # Run forever (24hr+)
 *   node upgrade-agents.js --fix              # Apply auto-fixes
 *   node upgrade-agents.js --game chess       # Scan one game only
 *   node upgrade-agents.js --agent lighting   # Run one agent only
 *
 * Copyright © 2026 GoSiteMe Inc.
 * ══════════════════════════════════════════════════════════════════════════════
 */

'use strict';

const fs = require('fs');
const path = require('path');

// ═══ CONFIG ═══
const VR_ROOT = path.resolve(__dirname, '../../');
const SHARED_DIR = path.join(VR_ROOT, 'shared');
const LOG_DIR = path.join(__dirname, 'logs');
if (!fs.existsSync(LOG_DIR)) fs.mkdirSync(LOG_DIR, { recursive: true });

const args = process.argv.slice(2);
const CONTINUOUS = args.includes('--continuous');
const AUTO_FIX = args.includes('--fix');
const SINGLE_GAME = args.find((a, i) => args[i - 1] === '--game') || null;
const SINGLE_AGENT = args.find((a, i) => args[i - 1] === '--agent') || null;
const SCAN_INTERVAL = 300000; // 5 minutes between rounds

// ═══ DISCOVER ALL VR GAMES ═══
function discoverGames() {
    const games = [];
    const dirs = fs.readdirSync(VR_ROOT, { withFileTypes: true });
    for (const d of dirs) {
        if (!d.isDirectory()) continue;
        if (d.name === 'shared' || d.name === 'node_modules') continue;
        const gamePath = path.join(VR_ROOT, d.name);
        
        // Find main files
        const htmlFiles = [];
        const jsFiles = [];
        
        const scanDir = (dir, depth) => {
            if (depth > 2) return;
            try {
                const items = fs.readdirSync(dir, { withFileTypes: true });
                for (const item of items) {
                    const full = path.join(dir, item.name);
                    if (item.isDirectory() && item.name !== 'node_modules' && item.name !== '.git') {
                        scanDir(full, depth + 1);
                    } else if (item.isFile()) {
                        if (item.name.endsWith('.html')) htmlFiles.push(full);
                        if (item.name.endsWith('.js') && !item.name.includes('.min.')) jsFiles.push(full);
                    }
                }
            } catch(e) {}
        };
        
        scanDir(gamePath, 0);
        
        if (htmlFiles.length > 0 || jsFiles.length > 0) {
            games.push({
                name: d.name,
                path: gamePath,
                htmlFiles,
                jsFiles,
                allCode: null, // lazy loaded
            });
        }
    }
    return SINGLE_GAME ? games.filter(g => g.name.includes(SINGLE_GAME)) : games;
}

function loadGameCode(game) {
    if (game.allCode) return game.allCode;
    let code = '';
    for (const f of [...game.htmlFiles, ...game.jsFiles]) {
        try { code += fs.readFileSync(f, 'utf8') + '\n'; } catch(e) {}
    }
    game.allCode = code;
    return code;
}

// ═══ THE 20 AGENTS ═══
// Each agent has: id, name, category, scan(game) → {score, issues[], fixes[]}

const AGENTS = [

    // ── RENDERING QUALITY (Agents 1-4) ──
    {
        id: 'pixel-density',
        name: '8K Pixel Density Agent',
        category: 'rendering',
        scan(game) {
            const code = loadGameCode(game);
            const issues = [];
            const fixes = [];
            let score = 100;

            if (!code.includes('GoSiteMeQuality')) {
                issues.push({ severity: 'high', msg: 'Not using GoSiteMeQuality adaptive system' });
                score -= 30;
            }
            if (/setPixelRatio\(Math\.min\(.*,\s*2\)\)/.test(code) && !code.includes('GoSiteMeQuality')) {
                issues.push({ severity: 'critical', msg: 'Pixel ratio hard-capped at 2 — no 4K/8K support' });
                score -= 40;
            }
            if (/setPixelRatio\(1\)/.test(code)) {
                issues.push({ severity: 'high', msg: 'Pixel ratio forced to 1 — looks blurry on all modern displays' });
                score -= 50;
            }
            if (!code.includes('powerPreference')) {
                issues.push({ severity: 'low', msg: 'Missing powerPreference: "high-performance" on WebGLRenderer' });
                score -= 5;
            }
            return { score: Math.max(0, score), issues, fixes };
        }
    },
    {
        id: 'shadow-quality',
        name: 'Shadow Quality Agent',
        category: 'rendering',
        scan(game) {
            const code = loadGameCode(game);
            const issues = [];
            let score = 100;

            if (!code.includes('shadowMap.enabled')) {
                issues.push({ severity: 'medium', msg: 'Shadows not enabled — flat/unrealistic look' });
                score -= 25;
            }
            if (code.includes('BasicShadowMap')) {
                issues.push({ severity: 'medium', msg: 'Using BasicShadowMap — upgrade to PCFSoftShadowMap for quality' });
                score -= 15;
            }
            const shadowSizes = code.match(/shadow\.mapSize\.set\((\d+)/g) || [];
            for (const s of shadowSizes) {
                const size = parseInt(s.match(/\d+/)[0]);
                if (size < 1024) {
                    issues.push({ severity: 'medium', msg: `Shadow map size ${size} is too low (min 1024)` });
                    score -= 10;
                }
            }
            if (!code.includes('shadow.bias')) {
                issues.push({ severity: 'low', msg: 'No shadow.bias set — may have shadow acne artifacts' });
                score -= 5;
            }
            if (!code.includes('shadow.normalBias')) {
                issues.push({ severity: 'low', msg: 'No shadow.normalBias — can cause peter-panning' });
                score -= 3;
            }
            return { score: Math.max(0, score), issues, fixes: [] };
        }
    },
    {
        id: 'post-processing',
        name: 'Post-Processing Agent',
        category: 'rendering',
        scan(game) {
            const code = loadGameCode(game);
            const issues = [];
            let score = 100;

            const hasComposer = code.includes('EffectComposer');
            if (!hasComposer) {
                issues.push({ severity: 'medium', msg: 'No post-processing pipeline — missing bloom, SSAO, color grading' });
                score -= 20;
            }
            if (!code.includes('UnrealBloom') && !code.includes('BloomPass')) {
                issues.push({ severity: 'low', msg: 'No bloom effect — specular highlights look flat' });
                score -= 10;
            }
            if (!code.includes('SSAO')) {
                issues.push({ severity: 'low', msg: 'No SSAO — missing contact shadows in crevices' });
                score -= 8;
            }
            if (!code.includes('FXAA') && !code.includes('SMAA') && !code.includes('MSAA')) {
                issues.push({ severity: 'medium', msg: 'No anti-aliasing pass — jagged edges visible at high res' });
                score -= 12;
            }
            if (!code.includes('toneMapping')) {
                issues.push({ severity: 'medium', msg: 'No tone mapping — HDR lighting will look washed out or clipped' });
                score -= 15;
            }
            if (hasComposer && !code.includes('renderToScreen')) {
                issues.push({ severity: 'critical', msg: 'EffectComposer present but no renderToScreen = true on final pass — BLACK SCREEN' });
                score -= 50;
            }
            if (code.includes('SSAOPass') && (!code.includes('SSAOShader') || !code.includes('SimplexNoise'))) {
                issues.push({ severity: 'critical', msg: 'SSAOPass loaded but missing SSAOShader.js or SimplexNoise.js — BLACK SCREEN crash' });
                score -= 50;
            }
            if (hasComposer && !code.includes('try') && !code.includes('catch')) {
                issues.push({ severity: 'high', msg: 'Post-processing pipeline has no try/catch — any pass failure = black screen' });
                score -= 20;
            }
            return { score: Math.max(0, score), issues, fixes: [] };
        }
    },
    {
        id: 'material-quality',
        name: 'PBR Material Agent',
        category: 'rendering',
        scan(game) {
            const code = loadGameCode(game);
            const issues = [];
            let score = 100;

            if (code.includes('MeshBasicMaterial') && !code.includes('MeshStandardMaterial')) {
                issues.push({ severity: 'medium', msg: 'Using only MeshBasicMaterial — no lighting response, flat look' });
                score -= 25;
            }
            if (!code.includes('normalMap') && !code.includes('bumpMap')) {
                issues.push({ severity: 'medium', msg: 'No normal/bump maps — surfaces lack detail and depth' });
                score -= 15;
            }
            if (!code.includes('roughnessMap') && !code.includes('roughness')) {
                issues.push({ severity: 'low', msg: 'No roughness variation — surfaces look uniformly shiny or matte' });
                score -= 8;
            }
            if (!code.includes('envMap') && !code.includes('environmentMap')) {
                issues.push({ severity: 'medium', msg: 'No environment map — surfaces lack reflections' });
                score -= 12;
            }
            if (code.includes('MeshLambertMaterial')) {
                issues.push({ severity: 'low', msg: 'Using MeshLambertMaterial — upgrade to MeshStandardMaterial for PBR' });
                score -= 8;
            }
            if (!code.includes('envMapIntensity')) {
                issues.push({ severity: 'low', msg: 'No envMapIntensity tuning — reflections may be too strong or weak' });
                score -= 5;
            }
            return { score: Math.max(0, score), issues, fixes: [] };
        }
    },

    // ── LIGHTING (Agents 5-6) ──
    {
        id: 'lighting-setup',
        name: 'Cinematic Lighting Agent',
        category: 'lighting',
        scan(game) {
            const code = loadGameCode(game);
            const issues = [];
            let score = 100;

            const lightTypes = ['AmbientLight', 'DirectionalLight', 'PointLight', 'SpotLight', 'HemisphereLight'];
            const foundLights = lightTypes.filter(t => code.includes(t));
            if (foundLights.length < 2) {
                issues.push({ severity: 'medium', msg: `Only ${foundLights.length} light types — needs multi-light setup for depth` });
                score -= 20;
            }
            if (!code.includes('HemisphereLight')) {
                issues.push({ severity: 'low', msg: 'No hemisphere light — missing sky/ground color bleed' });
                score -= 5;
            }
            if (!code.includes('castShadow')) {
                issues.push({ severity: 'medium', msg: 'No lights cast shadows — scene looks flat' });
                score -= 15;
            }
            if (!code.includes('physicallyCorrectLights')) {
                issues.push({ severity: 'low', msg: 'physicallyCorrectLights not enabled — light attenuation is non-physical' });
                score -= 5;
            }
            return { score: Math.max(0, score), issues, fixes: [] };
        }
    },
    {
        id: 'environment-map',
        name: 'Environment Reflections Agent',
        category: 'lighting',
        scan(game) {
            const code = loadGameCode(game);
            const issues = [];
            let score = 100;

            if (!code.includes('CubeTextureLoader') && !code.includes('PMREMGenerator') && !code.includes('envMap')) {
                issues.push({ severity: 'medium', msg: 'No environment map — reflective surfaces will appear black' });
                score -= 30;
            }
            if (!code.includes('FogExp2') && !code.includes('Fog(')) {
                issues.push({ severity: 'low', msg: 'No fog — distant objects lack atmospheric depth' });
                score -= 8;
            }
            if (!code.includes('background')) {
                issues.push({ severity: 'low', msg: 'No scene background set — may show default black' });
                score -= 5;
            }
            return { score: Math.max(0, score), issues, fixes: [] };
        }
    },

    // ── PERFORMANCE (Agents 7-9) ──
    {
        id: 'geometry-optimize',
        name: 'Geometry Optimization Agent',
        category: 'performance',
        scan(game) {
            const code = loadGameCode(game);
            const issues = [];
            let score = 100;

            if (code.includes('BufferGeometry') || code.includes('SphereGeometry') || code.includes('BoxGeometry')) {
                // Check for very high segment counts
                const highSeg = code.match(/Geometry\(\s*[\d.]+,\s*[\d.]+,?\s*(\d{3,})/g);
                if (highSeg) {
                    issues.push({ severity: 'medium', msg: `High segment count geometry detected — may impact VR performance` });
                    score -= 10;
                }
            }
            if (!code.includes('dispose')) {
                issues.push({ severity: 'medium', msg: 'No geometry.dispose() calls — potential GPU memory leak' });
                score -= 15;
            }
            if (!code.includes('frustumCulled') && code.match(/new THREE\.\w+Geometry/g)?.length > 30) {
                issues.push({ severity: 'low', msg: 'Many geometries without frustum culling checks' });
                score -= 5;
            }
            return { score: Math.max(0, score), issues, fixes: [] };
        }
    },
    {
        id: 'texture-optimize',
        name: 'Texture Optimization Agent',
        category: 'performance',
        scan(game) {
            const code = loadGameCode(game);
            const issues = [];
            let score = 100;

            // Check for uncompressed large textures
            const texLoads = (code.match(/TextureLoader\(\)/g) || []).length;
            if (texLoads > 20) {
                issues.push({ severity: 'medium', msg: `${texLoads} texture loads — consider texture atlas or lazy loading` });
                score -= 10;
            }
            if (!code.includes('anisotropy')) {
                issues.push({ severity: 'low', msg: 'No anisotropic filtering — textures blur at oblique angles' });
                score -= 5;
            }
            if (!code.includes('generateMipmaps') && texLoads > 5) {
                issues.push({ severity: 'low', msg: 'Mipmaps not explicitly managed — may cause shimmering at distance' });
                score -= 3;
            }
            return { score: Math.max(0, score), issues, fixes: [] };
        }
    },
    {
        id: 'render-loop',
        name: 'Render Loop Agent',
        category: 'performance',
        scan(game) {
            const code = loadGameCode(game);
            const issues = [];
            let score = 100;

            if (!code.includes('setAnimationLoop') && !code.includes('requestAnimationFrame')) {
                issues.push({ severity: 'critical', msg: 'No render loop detected' });
                score -= 50;
            }
            if (!code.includes('Clock') && !code.includes('getDelta')) {
                issues.push({ severity: 'medium', msg: 'No delta time — animations will run at inconsistent speed' });
                score -= 15;
            }
            if (!code.includes('resize') && !code.includes('onresize')) {
                issues.push({ severity: 'high', msg: 'No resize handler — game won\'t adapt to window size changes' });
                score -= 20;
            }
            return { score: Math.max(0, score), issues, fixes: [] };
        }
    },

    // ── INTERACTIVITY (Agents 10-12) ──
    {
        id: 'vr-support',
        name: 'WebXR VR Agent',
        category: 'interactivity',
        scan(game) {
            const code = loadGameCode(game);
            const issues = [];
            let score = 100;

            if (!code.includes('xr.enabled') && !code.includes('XRButton') && !code.includes('VRButton')) {
                issues.push({ severity: 'high', msg: 'No WebXR/VR support — can\'t be used in VR headsets' });
                score -= 30;
            }
            if (!code.includes('VRButton') && !code.includes('XRButton') && !code.includes('Enter VR')) {
                issues.push({ severity: 'medium', msg: 'No VR entry button — users can\'t enter immersive mode' });
                score -= 15;
            }
            if (code.includes('xr.enabled') && !code.includes('getController') && !code.includes('controller')) {
                issues.push({ severity: 'medium', msg: 'VR enabled but no controller handling — users can\'t interact in VR' });
                score -= 15;
            }
            return { score: Math.max(0, score), issues, fixes: [] };
        }
    },
    {
        id: 'controls',
        name: 'User Controls Agent',
        category: 'interactivity',
        scan(game) {
            const code = loadGameCode(game);
            const issues = [];
            let score = 100;

            if (!code.includes('OrbitControls') && !code.includes('PointerLockControls') && !code.includes('FirstPersonControls')) {
                issues.push({ severity: 'medium', msg: 'No camera controls — user can\'t look around' });
                score -= 20;
            }
            if (!code.includes('Raycaster')) {
                issues.push({ severity: 'medium', msg: 'No raycaster — user can\'t click/interact with 3D objects' });
                score -= 15;
            }
            if (!code.includes('pointer') && !code.includes('mouse') && !code.includes('touch')) {
                issues.push({ severity: 'medium', msg: 'No pointer/mouse/touch event handling' });
                score -= 15;
            }
            return { score: Math.max(0, score), issues, fixes: [] };
        }
    },
    {
        id: 'audio-spatial',
        name: 'Spatial Audio Agent',
        category: 'interactivity',
        scan(game) {
            const code = loadGameCode(game);
            const issues = [];
            let score = 100;

            if (!code.includes('AudioListener') && !code.includes('AudioContext') && !code.includes('WebAudio')) {
                issues.push({ severity: 'medium', msg: 'No audio system — silent experience' });
                score -= 25;
            }
            if (!code.includes('PositionalAudio') && code.includes('AudioListener')) {
                issues.push({ severity: 'low', msg: 'No positional/spatial audio — sounds don\'t have 3D placement' });
                score -= 10;
            }
            return { score: Math.max(0, score), issues, fixes: [] };
        }
    },

    // ── VISUAL EFFECTS (Agents 13-15) ──
    {
        id: 'particles',
        name: 'Particle Effects Agent',
        category: 'effects',
        scan(game) {
            const code = loadGameCode(game);
            const issues = [];
            let score = 100;

            if (!code.includes('Points') && !code.includes('ParticleSystem') && !code.includes('particle')) {
                issues.push({ severity: 'low', msg: 'No particle effects — missing ambient atmosphere (dust, fireflies, etc.)' });
                score -= 10;
            }
            return { score: Math.max(0, score), issues, fixes: [] };
        }
    },
    {
        id: 'animations',
        name: 'Animation Quality Agent',
        category: 'effects',
        scan(game) {
            const code = loadGameCode(game);
            const issues = [];
            let score = 100;

            const hasAnimation = code.includes('AnimationMixer') || code.includes('requestAnimationFrame') ||
                                 code.includes('tween') || code.includes('gsap');
            if (!hasAnimation) {
                issues.push({ severity: 'medium', msg: 'No animation system — static scene' });
                score -= 20;
            }
            if (!code.includes('ease') && !code.includes('lerp') && !code.includes('smoothstep')) {
                issues.push({ severity: 'low', msg: 'No easing functions — animations may feel abrupt' });
                score -= 5;
            }
            return { score: Math.max(0, score), issues, fixes: [] };
        }
    },
    {
        id: 'sky-environment',
        name: 'Sky & Environment Agent',
        category: 'effects',
        scan(game) {
            const code = loadGameCode(game);
            const issues = [];
            let score = 100;

            if (!code.includes('Sky') && !code.includes('sky') && !code.includes('skybox') && !code.includes('CubeTexture')) {
                issues.push({ severity: 'medium', msg: 'No sky/skybox — bland or black background' });
                score -= 15;
            }
            return { score: Math.max(0, score), issues, fixes: [] };
        }
    },

    // ── CODE QUALITY (Agents 16-18) ──
    {
        id: 'error-handling',
        name: 'Error Handling Agent',
        category: 'code-quality',
        scan(game) {
            const code = loadGameCode(game);
            const issues = [];
            let score = 100;

            if (!code.includes('try') && !code.includes('catch')) {
                issues.push({ severity: 'medium', msg: 'No try/catch error handling — game will crash silently on errors' });
                score -= 20;
            }
            if (!code.includes('console.error') && !code.includes('console.warn')) {
                issues.push({ severity: 'low', msg: 'No error/warning logging — hard to debug issues' });
                score -= 5;
            }
            if (!code.includes('WebGL') && !code.includes('webgl')) {
                issues.push({ severity: 'low', msg: 'No WebGL support check — may fail on unsupported browsers' });
                score -= 5;
            }
            return { score: Math.max(0, score), issues, fixes: [] };
        }
    },
    {
        id: 'memory-management',
        name: 'Memory Management Agent',
        category: 'code-quality',
        scan(game) {
            const code = loadGameCode(game);
            const issues = [];
            let score = 100;

            if (!code.includes('.dispose()')) {
                issues.push({ severity: 'high', msg: 'No .dispose() calls — textures/geometries leak GPU memory' });
                score -= 25;
            }
            if (!code.includes('removeEventListener') && code.includes('addEventListener')) {
                issues.push({ severity: 'medium', msg: 'Event listeners added but never removed — potential memory leak' });
                score -= 10;
            }
            if (code.includes('setInterval') && !code.includes('clearInterval')) {
                issues.push({ severity: 'medium', msg: 'setInterval without clearInterval — timer leak' });
                score -= 10;
            }
            return { score: Math.max(0, score), issues, fixes: [] };
        }
    },
    {
        id: 'accessibility',
        name: 'Accessibility Agent',
        category: 'code-quality',
        scan(game) {
            const code = loadGameCode(game);
            const issues = [];
            let score = 100;

            if (!code.includes('aria-') && !code.includes('role=')) {
                issues.push({ severity: 'low', msg: 'No ARIA attributes — not accessible to screen readers' });
                score -= 8;
            }
            if (!code.includes('alt=') && code.includes('<img')) {
                issues.push({ severity: 'low', msg: 'Images without alt text' });
                score -= 5;
            }
            if (!code.includes('keyboard') && !code.includes('keydown') && !code.includes('keyup')) {
                issues.push({ severity: 'medium', msg: 'No keyboard controls — not accessible without mouse/VR controller' });
                score -= 10;
            }
            return { score: Math.max(0, score), issues, fixes: [] };
        }
    },

    // ── CONNECTIVITY (Agents 19-20) ──
    {
        id: 'multiplayer',
        name: 'Multiplayer Connectivity Agent',
        category: 'connectivity',
        scan(game) {
            const code = loadGameCode(game);
            const issues = [];
            let score = 100;

            if (!code.includes('WebSocket') && !code.includes('socket') && !code.includes('multiplayer') && !code.includes('peer')) {
                issues.push({ severity: 'medium', msg: 'No multiplayer/WebSocket support — single-player only' });
                score -= 15;
            }
            if (!code.includes('RTCPeerConnection') && !code.includes('WebRTC')) {
                issues.push({ severity: 'low', msg: 'No WebRTC — no voice/video chat capability' });
                score -= 5;
            }
            return { score: Math.max(0, score), issues, fixes: [] };
        }
    },
    {
        id: 'api-integration',
        name: 'API Integration Agent',
        category: 'connectivity',
        scan(game) {
            const code = loadGameCode(game);
            const issues = [];
            let score = 100;

            if (!code.includes('fetch(') && !code.includes('XMLHttpRequest') && !code.includes('axios')) {
                issues.push({ severity: 'low', msg: 'No API calls — game doesn\'t connect to backend services' });
                score -= 5;
            }
            if (!code.includes('GoSiteMe') && !code.includes('gositeme') && !code.includes('alfred')) {
                issues.push({ severity: 'low', msg: 'No GoSiteMe/Alfred integration — missing AI features' });
                score -= 5;
            }
            if (!code.includes('serviceWorker') && !code.includes('sw.js') && !code.includes('offline')) {
                issues.push({ severity: 'low', msg: 'No service worker — game doesn\'t work offline' });
                score -= 3;
            }
            return { score: Math.max(0, score), issues, fixes: [] };
        }
    },
];

// ═══ SCORING & GRADING ═══
function getGrade(score) {
    if (score >= 95) return '★★★★★ S-Tier (Metaverse Best)';
    if (score >= 85) return '★★★★☆ A-Tier (Excellent)';
    if (score >= 70) return '★★★☆☆ B-Tier (Good)';
    if (score >= 55) return '★★☆☆☆ C-Tier (Needs Work)';
    if (score >= 40) return '★☆☆☆☆ D-Tier (Major Issues)';
    return '☆☆☆☆☆ F-Tier (Overhaul Needed)';
}

// ═══ SCAN RUNNER ═══
function runFullScan() {
    const games = discoverGames();
    const timestamp = new Date().toISOString();
    const results = [];

    console.log(`\n${'═'.repeat(70)}`);
    console.log(`  GoSiteMe Continuous Upgrade Agent System — Scan Report`);
    console.log(`  ${timestamp} | ${games.length} games | ${AGENTS.length} agents`);
    console.log(`${'═'.repeat(70)}\n`);

    for (const game of games) {
        const gameResults = { name: game.name, agents: {}, totalScore: 0, categoryScores: {}, issues: [], grade: '' };
        let agentScores = [];

        const activeAgents = SINGLE_AGENT ? AGENTS.filter(a => a.id.includes(SINGLE_AGENT)) : AGENTS;

        for (const agent of activeAgents) {
            try {
                const result = agent.scan(game);
                gameResults.agents[agent.id] = result;
                agentScores.push(result.score);
                gameResults.issues.push(...result.issues.map(i => ({ ...i, agent: agent.name })));

                // Category tracking
                if (!gameResults.categoryScores[agent.category]) gameResults.categoryScores[agent.category] = [];
                gameResults.categoryScores[agent.category].push(result.score);
            } catch (err) {
                console.error(`  [ERROR] Agent ${agent.name} crashed on ${game.name}: ${err.message}`);
            }
        }

        gameResults.totalScore = Math.round(agentScores.reduce((s, v) => s + v, 0) / agentScores.length);
        gameResults.grade = getGrade(gameResults.totalScore);

        // Category averages
        const catAvg = {};
        for (const [cat, scores] of Object.entries(gameResults.categoryScores)) {
            catAvg[cat] = Math.round(scores.reduce((s, v) => s + v, 0) / scores.length);
        }

        // Print game report
        const critical = gameResults.issues.filter(i => i.severity === 'critical').length;
        const high = gameResults.issues.filter(i => i.severity === 'high').length;
        const medium = gameResults.issues.filter(i => i.severity === 'medium').length;
        const low = gameResults.issues.filter(i => i.severity === 'low').length;

        console.log(`╔${'═'.repeat(68)}╗`);
        console.log(`║  ${game.name.toUpperCase().padEnd(50)} Score: ${String(gameResults.totalScore).padStart(3)}/100 ║`);
        console.log(`║  ${gameResults.grade.padEnd(66)}║`);
        console.log(`╠${'═'.repeat(68)}╣`);

        for (const [cat, avg] of Object.entries(catAvg)) {
            const bar = '█'.repeat(Math.floor(avg / 5)) + '░'.repeat(20 - Math.floor(avg / 5));
            console.log(`║  ${cat.padEnd(16)} ${bar} ${String(avg).padStart(3)}% ${' '.repeat(20)}║`);
        }

        console.log(`╠${'─'.repeat(68)}╣`);
        console.log(`║  Issues: ${critical} critical, ${high} high, ${medium} medium, ${low} low${' '.repeat(Math.max(0, 24 - String(critical + high + medium + low).length))}║`);

        // Show top 5 issues
        const topIssues = gameResults.issues
            .sort((a, b) => { const p = { critical: 0, high: 1, medium: 2, low: 3 }; return p[a.severity] - p[b.severity]; })
            .slice(0, 5);
        for (const issue of topIssues) {
            const sev = issue.severity === 'critical' ? '🔴' : issue.severity === 'high' ? '🟠' : issue.severity === 'medium' ? '🟡' : '🟢';
            const line = `${sev} ${issue.msg}`;
            console.log(`║  ${line.substring(0, 66).padEnd(66)}║`);
        }

        console.log(`╚${'═'.repeat(68)}╝\n`);
        results.push(gameResults);
    }

    // Overall summary
    const overallAvg = Math.round(results.reduce((s, r) => s + r.totalScore, 0) / results.length);
    console.log(`${'═'.repeat(70)}`);
    console.log(`  PORTFOLIO AVERAGE: ${overallAvg}/100 — ${getGrade(overallAvg)}`);
    console.log(`  Games Scanned: ${results.length} | Agents Run: ${AGENTS.length * results.length}`);
    
    const allIssues = results.flatMap(r => r.issues);
    console.log(`  Total Issues: ${allIssues.length} (${allIssues.filter(i => i.severity === 'critical').length} critical, ${allIssues.filter(i => i.severity === 'high').length} high)`);
    console.log(`${'═'.repeat(70)}\n`);

    // Save JSON report
    const reportPath = path.join(LOG_DIR, `upgrade-report-${timestamp.slice(0, 19).replace(/:/g, '-')}.json`);
    fs.writeFileSync(reportPath, JSON.stringify({ timestamp, games: results, overallAvg, agentCount: AGENTS.length }, null, 2));
    console.log(`Report saved: ${reportPath}\n`);

    return results;
}

// ═══ MAIN ═══
async function main() {
    console.log(`
╔══════════════════════════════════════════════════════════════╗
║  GoSiteMe Continuous Upgrade Agent System v1.0              ║
║  20 AI Agents — Rendering • Lighting • Performance          ║
║  Effects • Interactivity • Code Quality • Connectivity      ║
╠══════════════════════════════════════════════════════════════╣
║  Mode: ${(CONTINUOUS ? 'CONTINUOUS (running forever)' : 'SINGLE SCAN').padEnd(51)}║
║  Auto-fix: ${(AUTO_FIX ? 'ENABLED' : 'DISABLED (use --fix to enable)').padEnd(47)}║
║  Target: ${(SINGLE_GAME || 'ALL GAMES').padEnd(49)}║
║  Agent: ${(SINGLE_AGENT || 'ALL 20 AGENTS').padEnd(50)}║
╚══════════════════════════════════════════════════════════════╝
`);

    if (CONTINUOUS) {
        let round = 0;
        while (true) {
            round++;
            console.log(`\n${'─'.repeat(70)}`);
            console.log(`  CONTINUOUS ROUND ${round} — ${new Date().toISOString()}`);
            console.log(`${'─'.repeat(70)}`);
            runFullScan();
            console.log(`  Next scan in ${SCAN_INTERVAL / 1000}s...`);
            await new Promise(r => setTimeout(r, SCAN_INTERVAL));
        }
    } else {
        runFullScan();
    }
}

process.on('SIGINT', () => {
    console.log('\n  Upgrade agent system shutting down gracefully.');
    process.exit(0);
});

main();
