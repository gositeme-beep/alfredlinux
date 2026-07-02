/* ═══════════════════════════════════════════════════════════════
   CHESS MASTERS — Photorealistic Renderer Module
   GSM Alfred OS · Project Grandmaster II
   
   Photorealistic VR chess room with:
   - PBR materials (wood, marble, leather, metal, glass)
   - Realistic room environment (private chess club)
   - Area lights, candles, fireplace glow
   - High-detail LatheGeometry pieces with PBR
   - Reflection probes / environment mapping
   - Post-processing (bloom, vignette, tone mapping)
   - WebXR hand tracking for natural piece interaction
   - Spatial audio integration hooks
   - Animated piece capture / check effects
   ═══════════════════════════════════════════════════════════════ */

const ChessRenderer = (() => {
    'use strict';

    // ── Scene Objects ──
    let scene, camera, renderer, controls;
    let raycaster, mouse;
    let boardGroup, piecesGroup, effectsGroup, roomGroup;
    let envMap = null;
    let vrSession = null;
    let vrControllers = [];
    let selectedPiece = null;
    let hoverIndicator = null;
    let clock;
    let composer = null; // Post-processing composer

    const materialCache = {};
    const geometryCache = {};
    const textureCache = {};

    // ═══ ADAPTIVE QUALITY SYSTEM — scales from 1080p to 8K+ ═══
    const quality = {
        tier: 'ultra',     // auto-detected: 'high', 'ultra', '8k'
        dpr: 1,            // resolved device pixel ratio
        texSize: 1024,     // procedural texture resolution
        shadowSize: 4096,  // sun shadow map resolution
        spotShadow: 1024,  // spot shadow map resolution
    };

    function detectQualityTier() {
        const dpr = window.devicePixelRatio || 1;
        const screenW = window.screen.width * dpr;
        const screenH = window.screen.height * dpr;
        const maxDim = Math.max(screenW, screenH);
        const gl = document.createElement('canvas').getContext('webgl2') ||
                   document.createElement('canvas').getContext('webgl');
        const maxTexSize = gl ? gl.getParameter(gl.MAX_TEXTURE_SIZE) : 4096;

        if (maxDim >= 7680) {
            // 8K display (7680x4320+)
            quality.tier = '8k';
            quality.dpr = Math.min(dpr, maxTexSize >= 8192 ? dpr : 4);
            quality.texSize = Math.min(4096, maxTexSize);
            quality.shadowSize = 8192;
            quality.spotShadow = 4096;
        } else if (maxDim >= 3840) {
            // 4K display (3840x2160+)
            quality.tier = 'ultra';
            quality.dpr = Math.min(dpr, 4);
            quality.texSize = 2048;
            quality.shadowSize = 8192;
            quality.spotShadow = 2048;
        } else {
            // 1080p–1440p
            quality.tier = 'high';
            quality.dpr = Math.min(dpr, 2);
            quality.texSize = 1024;
            quality.shadowSize = 4096;
            quality.spotShadow = 1024;
        }

        // VR headset override — cap pixel ratio for performance
        if (navigator.xr) {
            navigator.xr.isSessionSupported('immersive-vr').then(supported => {
                if (supported && quality.dpr > 2) quality.dpr = 2;
            }).catch(() => {});
        }

        console.log(`[Chess Masters] Quality tier: ${quality.tier} | DPR: ${quality.dpr} | Tex: ${quality.texSize}px | Shadow: ${quality.shadowSize}px | Screen: ${maxDim}px`);
    }

    // ═══ PROCEDURAL PBR TEXTURE GENERATORS — resolution adaptive ═══
    // Generate textures at runtime — no external files needed for photorealism

    function generateWoodGrainTexture(width, height, baseColor, grainColor) {
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');

        // Base color fill
        ctx.fillStyle = '#' + new THREE.Color(baseColor).getHexString();
        ctx.fillRect(0, 0, width, height);

        // Wood grain lines
        ctx.globalAlpha = 0.15;
        const gc = new THREE.Color(grainColor);
        for (let i = 0; i < 120; i++) {
            const y = Math.random() * height;
            const thickness = 0.5 + Math.random() * 2.5;
            const waveAmp = 2 + Math.random() * 8;
            const waveFreq = 0.005 + Math.random() * 0.015;
            ctx.beginPath();
            ctx.strokeStyle = '#' + gc.getHexString();
            ctx.lineWidth = thickness;
            ctx.moveTo(0, y);
            for (let x = 0; x < width; x += 3) {
                const dy = Math.sin(x * waveFreq + i * 0.5) * waveAmp;
                ctx.lineTo(x, y + dy);
            }
            ctx.stroke();
        }

        // Subtle knots
        ctx.globalAlpha = 0.08;
        for (let k = 0; k < 5; k++) {
            const kx = Math.random() * width;
            const ky = Math.random() * height;
            const kr = 8 + Math.random() * 20;
            const grad = ctx.createRadialGradient(kx, ky, 0, kx, ky, kr);
            grad.addColorStop(0, '#' + new THREE.Color(grainColor).getHexString());
            grad.addColorStop(1, 'transparent');
            ctx.fillStyle = grad;
            ctx.fillRect(kx - kr, ky - kr, kr * 2, kr * 2);
        }

        ctx.globalAlpha = 1;
        const tex = new THREE.CanvasTexture(canvas);
        tex.wrapS = THREE.RepeatWrapping;
        tex.wrapT = THREE.RepeatWrapping;
        tex.encoding = THREE.sRGBEncoding;
        return tex;
    }

    function generateWoodNormalMap(width, height) {
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');

        // Neutral normal (128,128,255)
        ctx.fillStyle = 'rgb(128,128,255)';
        ctx.fillRect(0, 0, width, height);

        // Grain groove normals
        for (let i = 0; i < 100; i++) {
            const y = Math.random() * height;
            const waveAmp = 2 + Math.random() * 6;
            const waveFreq = 0.005 + Math.random() * 0.015;
            ctx.beginPath();
            ctx.strokeStyle = 'rgb(128,100,255)'; // tilted normal
            ctx.lineWidth = 0.5 + Math.random() * 1.5;
            ctx.moveTo(0, y);
            for (let x = 0; x < width; x += 3) {
                const dy = Math.sin(x * waveFreq + i * 0.5) * waveAmp;
                ctx.lineTo(x, y + dy);
            }
            ctx.stroke();
            // Opposite side of groove
            ctx.beginPath();
            ctx.strokeStyle = 'rgb(128,156,255)';
            ctx.lineWidth = 0.5;
            ctx.moveTo(0, y + 1);
            for (let x = 0; x < width; x += 3) {
                const dy = Math.sin(x * waveFreq + i * 0.5) * waveAmp;
                ctx.lineTo(x, y + dy + 1);
            }
            ctx.stroke();
        }

        const tex = new THREE.CanvasTexture(canvas);
        tex.wrapS = THREE.RepeatWrapping;
        tex.wrapT = THREE.RepeatWrapping;
        return tex;
    }

    function generateStoneTileTexture(width, height) {
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');

        // Base warm sandstone
        ctx.fillStyle = '#9B8565';
        ctx.fillRect(0, 0, width, height);

        // Random stone color variation
        for (let i = 0; i < 3000; i++) {
            const x = Math.random() * width;
            const y = Math.random() * height;
            const s = 1 + Math.random() * 4;
            const brightness = 0.85 + Math.random() * 0.30;
            ctx.fillStyle = `rgba(${Math.floor(155*brightness)},${Math.floor(133*brightness)},${Math.floor(101*brightness)},0.3)`;
            ctx.fillRect(x, y, s, s);
        }

        // Tile grid lines
        const tileSize = width / 8;
        ctx.strokeStyle = 'rgba(60,40,20,0.25)';
        ctx.lineWidth = 2;
        for (let gx = 0; gx <= width; gx += tileSize) {
            ctx.beginPath();
            ctx.moveTo(gx, 0);
            ctx.lineTo(gx, height);
            ctx.stroke();
        }
        for (let gy = 0; gy <= height; gy += tileSize) {
            ctx.beginPath();
            ctx.moveTo(0, gy);
            ctx.lineTo(width, gy);
            ctx.stroke();
        }

        const tex = new THREE.CanvasTexture(canvas);
        tex.wrapS = THREE.RepeatWrapping;
        tex.wrapT = THREE.RepeatWrapping;
        tex.encoding = THREE.sRGBEncoding;
        return tex;
    }

    function generateStoneNormalMap(width, height) {
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');

        ctx.fillStyle = 'rgb(128,128,255)';
        ctx.fillRect(0, 0, width, height);

        // Random surface bumps
        for (let i = 0; i < 2000; i++) {
            const x = Math.random() * width;
            const y = Math.random() * height;
            const s = 1 + Math.random() * 4;
            const nx = 120 + Math.floor(Math.random() * 16);
            const ny = 120 + Math.floor(Math.random() * 16);
            ctx.fillStyle = `rgb(${nx},${ny},255)`;
            ctx.fillRect(x, y, s, s);
        }

        // Tile groove normals
        const tileSize = width / 8;
        ctx.strokeStyle = 'rgb(128,80,240)';
        ctx.lineWidth = 3;
        for (let gx = 0; gx <= width; gx += tileSize) {
            ctx.beginPath(); ctx.moveTo(gx, 0); ctx.lineTo(gx, height); ctx.stroke();
        }
        for (let gy = 0; gy <= height; gy += tileSize) {
            ctx.beginPath(); ctx.moveTo(0, gy); ctx.lineTo(width, gy); ctx.stroke();
        }

        const tex = new THREE.CanvasTexture(canvas);
        tex.wrapS = THREE.RepeatWrapping;
        tex.wrapT = THREE.RepeatWrapping;
        return tex;
    }

    function generateOceanNormalMap(width, height) {
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');

        ctx.fillStyle = 'rgb(128,128,255)';
        ctx.fillRect(0, 0, width, height);

        // Overlapping wave normal patterns at different scales
        for (let layer = 0; layer < 3; layer++) {
            const freq = [0.02, 0.04, 0.08][layer];
            const amp = [30, 15, 8][layer];
            ctx.globalAlpha = [0.5, 0.3, 0.2][layer];
            for (let y = 0; y < height; y += 2) {
                for (let x = 0; x < width; x += 2) {
                    const wave = Math.sin(x * freq + y * freq * 0.5) * amp;
                    const nx = 128 + Math.floor(wave);
                    const ny = 128 + Math.floor(Math.cos(y * freq * 0.7 + x * freq * 0.3) * amp * 0.6);
                    ctx.fillStyle = `rgb(${Math.max(0,Math.min(255,nx))},${Math.max(0,Math.min(255,ny))},255)`;
                    ctx.fillRect(x, y, 2, 2);
                }
            }
        }
        ctx.globalAlpha = 1;

        const tex = new THREE.CanvasTexture(canvas);
        tex.wrapS = THREE.RepeatWrapping;
        tex.wrapT = THREE.RepeatWrapping;
        return tex;
    }

    function generateRoughnessMap(width, height, baseRoughness) {
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');

        const base = Math.floor(baseRoughness * 255);
        ctx.fillStyle = `rgb(${base},${base},${base})`;
        ctx.fillRect(0, 0, width, height);

        // Random variation
        for (let i = 0; i < 1500; i++) {
            const x = Math.random() * width;
            const y = Math.random() * height;
            const s = 1 + Math.random() * 6;
            const v = Math.floor(base + (Math.random() - 0.5) * 40);
            ctx.fillStyle = `rgb(${v},${v},${v})`;
            ctx.fillRect(x, y, s, s);
        }

        const tex = new THREE.CanvasTexture(canvas);
        tex.wrapS = THREE.RepeatWrapping;
        tex.wrapT = THREE.RepeatWrapping;
        return tex;
    }

    function initTextures() {
        // Generate all procedural textures at adaptive resolution
        const ts = quality.texSize;     // main textures (1024–4096)
        const rs = ts >> 1;             // roughness maps at half-res (512–2048)
        textureCache.woodColor = generateWoodGrainTexture(ts, ts, 0x3A1808, 0x1A0A02);
        textureCache.woodNormal = generateWoodNormalMap(ts, ts);
        textureCache.woodRoughness = generateRoughnessMap(rs, rs, 0.25);
        textureCache.stoneColor = generateStoneTileTexture(ts, ts);
        textureCache.stoneNormal = generateStoneNormalMap(ts, ts);
        textureCache.stoneRoughness = generateRoughnessMap(rs, rs, 0.55);
        textureCache.oceanNormal = generateOceanNormalMap(rs, rs);
        textureCache.darkWoodColor = generateWoodGrainTexture(ts, ts, 0x3A1E08, 0x150A02);
        textureCache.darkWoodNormal = generateWoodNormalMap(ts, ts);

        // Apply max anisotropy to all textures for sharp oblique viewing angles
        const maxAniso = renderer.capabilities.getMaxAnisotropy();
        Object.values(textureCache).forEach(tex => {
            tex.anisotropy = maxAniso;
            tex.minFilter = THREE.LinearMipmapLinearFilter;
            tex.magFilter = THREE.LinearFilter;
            tex.needsUpdate = true;
        });

        console.log(`[Chess Masters] Generated ${Object.keys(textureCache).length} procedural textures at ${ts}px | Anisotropy: ${maxAniso}x`);
    }

    // ── Board Themes ──
    const THEMES = {
        walnut: {
            name: 'Walnut & Maple',
            light: 0xF5DEB3, dark: 0x5C3317, border: 0x3B1E08,
            borderAccent: 0x8B5E3C, accentGlow: 0xD4A843,
            lightRough: 0.45, darkRough: 0.35,
            lightMetal: 0.0,  darkMetal: 0.0,
            whitePiece: 0xFAF0E0, blackPiece: 0x1A1A1A,
            whiteAccent: 0xD4C8A0, blackAccent: 0x1A1008,
            feltColor: 0x2D5A1A,
        },
        marble: {
            name: 'Carrara & Verde',
            light: 0xF0EDE6, dark: 0x2F4F3F, border: 0x1A1A1A,
            borderAccent: 0xD4A843, accentGlow: 0xFFD700,
            lightRough: 0.15, darkRough: 0.2,
            lightMetal: 0.05, darkMetal: 0.05,
            whitePiece: 0xFAFAFA, blackPiece: 0x1A1A1A,
            whiteAccent: 0xD4A843, blackAccent: 0x2A2A2A,
            feltColor: 0x1A3A1A,
        },
        obsidian: {
            name: 'Obsidian & Gold',
            light: 0xF0E68C, dark: 0x1C1C1C, border: 0x0D0D0D,
            borderAccent: 0xDAA520, accentGlow: 0xFFD700,
            lightRough: 0.1, darkRough: 0.05,
            lightMetal: 0.8, darkMetal: 0.1,
            whitePiece: 0xE2E8F0, blackPiece: 0x1A1028,
            whiteAccent: 0xB8C4D0, blackAccent: 0x2D1B4E,
            feltColor: 0x1A5C2A,
        },
        rosewood: {
            name: 'Rosewood & Ivory',
            light: 0xFFFFF0, dark: 0x65000B, border: 0x3C0008,
            borderAccent: 0x8B4513, accentGlow: 0xFF6F00,
            lightRough: 0.3, darkRough: 0.25,
            lightMetal: 0.0, darkMetal: 0.0,
            whitePiece: 0xFAF5E8, blackPiece: 0x3D1E00,
            whiteAccent: 0xE0C8A0, blackAccent: 0x2A1400,
            feltColor: 0x2D4A1A,
        },
        tournament: {
            name: 'Tournament Green',
            light: 0xF5F5DC, dark: 0x2E6B35, border: 0x1A3D1F,
            borderAccent: 0x3D6A2C, accentGlow: 0x4CAF50,
            lightRough: 0.5, darkRough: 0.45,
            lightMetal: 0.0, darkMetal: 0.0,
            whitePiece: 0xFAF8F0, blackPiece: 0x1A1A1A,
            whiteAccent: 0xE0D8C0, blackAccent: 0x0A0A0A,
            feltColor: 0x2D5A1A,
        },
        midnight: {
            name: 'Midnight Blue',
            light: 0xC0C8D8, dark: 0x1B2838, border: 0x0D1520,
            borderAccent: 0x4488CC, accentGlow: 0x4FC3F7,
            lightRough: 0.2, darkRough: 0.15,
            lightMetal: 0.1, darkMetal: 0.15,
            whitePiece: 0xE8F0F5, blackPiece: 0x1A2A35,
            whiteAccent: 0xA0D0E0, blackAccent: 0x0A1A25,
            feltColor: 0x1A3A3A,
        },
    };

    let boardConfig = {
        theme: 'walnut',
        pieceStyle: 'staunton',
        showCoords: true,
    };

    // ═══ INIT ═══
    function init(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        // Scene — cinematic golden-hour beach
        scene = new THREE.Scene();
        scene.background = new THREE.Color(0x2B4A6E);
        scene.fog = new THREE.FogExp2(0x6A8BA8, 0.004);

        // Camera — closer, lower angle for intimate luxury chess view
        const w = container.clientWidth || window.innerWidth;
        const h = container.clientHeight || window.innerHeight;
        camera = new THREE.PerspectiveCamera(45, w / h, 0.05, 300);
        camera.position.set(0, 2.8, 3.2);

        // Renderer — max quality
        renderer = new THREE.WebGLRenderer({
            antialias: true,
            alpha: false,
            powerPreference: 'high-performance',
        });
        renderer.setSize(w, h);
        // DPR set after detectQualityTier() below — placeholder 1 until then

        renderer.outputEncoding = THREE.sRGBEncoding;
        renderer.toneMapping = THREE.ACESFilmicToneMapping;
        renderer.toneMappingExposure = 1.05;
        renderer.shadowMap.enabled = true;
        renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        renderer.physicallyCorrectLights = true;
        renderer.xr.enabled = true;
        container.appendChild(renderer.domElement);

        // Controls
        controls = new THREE.OrbitControls(camera, renderer.domElement);
        controls.target.set(0, 1.8, 0);
        controls.enableDamping = true;
        controls.dampingFactor = 0.06;
        controls.minDistance = 1.5;
        controls.maxDistance = 60;
        controls.minPolarAngle = Math.PI * 0.05;
        controls.maxPolarAngle = Math.PI * 0.55;
        controls.update();

        // Raycaster
        raycaster = new THREE.Raycaster();
        mouse = new THREE.Vector2();

        // Groups
        roomGroup = new THREE.Group();
        boardGroup = new THREE.Group();
        piecesGroup = new THREE.Group();
        effectsGroup = new THREE.Group();
        scene.add(roomGroup);
        scene.add(boardGroup);
        scene.add(piecesGroup);
        scene.add(effectsGroup);

        // Clock for animations
        clock = new THREE.Clock();

        // Detect display capability and set quality tier
        detectQualityTier();

        // NOW apply the detected DPR — must happen after detectQualityTier()
        renderer.setPixelRatio(quality.dpr);

        // Generate procedural PBR textures at adaptive resolution
        initTextures();

        // Build environment
        createEnvironmentMap();
        setupLighting();
        createRoom();
        createTable();
        createBoard();
        createHoverIndicator();
        createAmbientParticles();

        // Events
        window.addEventListener('resize', onResize);
        renderer.domElement.addEventListener('pointermove', onPointerMove);
        renderer.domElement.addEventListener('pointerdown', onPointerDown);

        // Post-processing pipeline (Unreal Engine quality)
        setupPostProcessing();

        // Initialize 200-player world (50 tables, avatars)
        try {
            if (typeof ChessWorld !== 'undefined') {
                ChessWorld.init(scene, camera);
            }
        } catch (e) { console.warn('[Chess Masters] World init:', e); }

        return renderer;
    }

    // ═══ ENVIRONMENT MAP — Cinematic tropical sunset reflections ═══
    function createEnvironmentMap() {
        const pmremGenerator = new THREE.PMREMGenerator(renderer);
        pmremGenerator.compileEquirectangularShader();

        const envScene = new THREE.Scene();
        envScene.background = new THREE.Color(0x1A3050);

        // Warm golden fill
        const ambientEnv = new THREE.AmbientLight(0xDD9955, 0.5);
        envScene.add(ambientEnv);

        // Sky dome — warm sunset gradient via vertex colors
        const envSkyGeo = new THREE.SphereGeometry(30, 16, 16);
        const envSkyColors = new Float32Array(envSkyGeo.attributes.position.count * 3);
        for (let i = 0; i < envSkyGeo.attributes.position.count; i++) {
            const y = envSkyGeo.attributes.position.getY(i);
            const t = Math.max(0, Math.min(1, (y + 10) / 40));
            envSkyColors[i * 3] = 0.95 - t * 0.70;
            envSkyColors[i * 3 + 1] = 0.55 - t * 0.30;
            envSkyColors[i * 3 + 2] = 0.25 + t * 0.25;
        }
        envSkyGeo.setAttribute('color', new THREE.BufferAttribute(envSkyColors, 3));
        const envSkyMat = new THREE.MeshBasicMaterial({ side: THREE.BackSide, vertexColors: true });
        envScene.add(new THREE.Mesh(envSkyGeo, envSkyMat));

        // Turquoise ocean plane for reflections
        const oceanMat = new THREE.MeshBasicMaterial({ color: 0x1A7B8C });
        const oceanPlane = new THREE.Mesh(new THREE.PlaneGeometry(60, 60), oceanMat);
        oceanPlane.rotation.x = -Math.PI / 2;
        oceanPlane.position.y = -2;
        envScene.add(oceanPlane);

        // Warm golden sun for specular highlights
        const sunSphere = new THREE.Mesh(
            new THREE.SphereGeometry(4, 8, 8),
            new THREE.MeshBasicMaterial({ color: 0xFFCC66 })
        );
        sunSphere.position.set(15, 8, -20);
        envScene.add(sunSphere);

        // Secondary warm bounce from sand
        const bounceSphere = new THREE.Mesh(
            new THREE.SphereGeometry(3, 8, 8),
            new THREE.MeshBasicMaterial({ color: 0xE8A050 })
        );
        bounceSphere.position.set(-10, 0, 10);
        envScene.add(bounceSphere);

        // Cool sky fill above
        const skyFill = new THREE.Mesh(
            new THREE.SphereGeometry(2, 8, 8),
            new THREE.MeshBasicMaterial({ color: 0x4488BB })
        );
        skyFill.position.set(0, 20, 0);
        envScene.add(skyFill);

        const cubeRT = pmremGenerator.fromScene(envScene, 0.04);
        envMap = cubeRT.texture;
        scene.environment = envMap;

        pmremGenerator.dispose();
        envScene.traverse(obj => {
            if (obj.geometry) obj.geometry.dispose();
            if (obj.material) obj.material.dispose();
        });
    }

    // ═══ POST-PROCESSING — Unreal Engine quality cinematic pipeline ═══
    function setupPostProcessing() {
        // Guard: only init if Three.js post-processing addons loaded
        if (typeof THREE.EffectComposer === 'undefined' ||
            typeof THREE.RenderPass === 'undefined') {
            console.warn('Post-processing libraries not loaded, using direct render');
            return;
        }

        try {

        const w = renderer.domElement.width;
        const h = renderer.domElement.height;

        composer = new THREE.EffectComposer(renderer);

        // 1. Base scene render
        const renderPass = new THREE.RenderPass(scene, camera);
        composer.addPass(renderPass);

        // 2. SSAO — Screen Space Ambient Occlusion (contact shadows in crevices)
        if (typeof THREE.SSAOPass !== 'undefined') {
            const ssaoPass = new THREE.SSAOPass(scene, camera, w, h);
            ssaoPass.kernelRadius = 0.6;
            ssaoPass.minDistance = 0.001;
            ssaoPass.maxDistance = 0.12;
            ssaoPass.output = THREE.SSAOPass.OUTPUT.Default;
            composer.addPass(ssaoPass);
        }

        // 3. Unreal Bloom — soft glow on highlights (sun, gold, reflections)
        if (typeof THREE.UnrealBloomPass !== 'undefined') {
            const bloomPass = new THREE.UnrealBloomPass(
                new THREE.Vector2(w, h),
                0.35,   // strength — subtle, not overwhelming
                0.6,    // radius — wide soft glow
                0.85    // threshold — only bright areas bloom
            );
            composer.addPass(bloomPass);
        }

        // 4. Depth of Field — cinematic bokeh focus on the board
        if (typeof THREE.ShaderPass !== 'undefined') {
            const dofShader = {
                uniforms: {
                    tDiffuse: { value: null },
                    tDepth: { value: null },
                    resolution: { value: new THREE.Vector2(1 / w, 1 / h) },
                    focalDepth: { value: 0.5 },
                    focalLength: { value: 0.04 },
                    fStop: { value: 2.8 },
                    maxBlur: { value: 0.008 },
                },
                vertexShader: [
                    'varying vec2 vUv;',
                    'void main() { vUv = uv; gl_Position = projectionMatrix * modelViewMatrix * vec4(position,1.0); }',
                ].join('\n'),
                fragmentShader: [
                    'uniform sampler2D tDiffuse;',
                    'uniform vec2 resolution;',
                    'uniform float maxBlur;',
                    'varying vec2 vUv;',
                    'void main() {',
                    '  vec4 center = texture2D(tDiffuse, vUv);',
                    '  // Gentle radial blur on edges — simulates shallow depth of field',
                    '  vec2 fromCenter = vUv - vec2(0.5);',
                    '  float dist = length(fromCenter);',
                    '  float blur = smoothstep(0.25, 0.7, dist) * maxBlur;',
                    '  if (blur < 0.0001) { gl_FragColor = center; return; }',
                    '  vec4 sum = center;',
                    '  float total = 1.0;',
                    '  for (int i = 0; i < 8; i++) {',
                    '    float angle = float(i) * 0.785398;',
                    '    vec2 offset = vec2(cos(angle), sin(angle)) * blur;',
                    '    sum += texture2D(tDiffuse, vUv + offset);',
                    '    total += 1.0;',
                    '  }',
                    '  gl_FragColor = sum / total;',
                    '}',
                ].join('\n'),
            };
            const dofPass = new THREE.ShaderPass(dofShader);
            composer.addPass(dofPass);
        }

        // 5. Color Grading + Vignette + Film Grain + Chromatic Aberration — cinematic post
        if (typeof THREE.ShaderPass !== 'undefined') {
            const colorGradingShader = {
                uniforms: {
                    tDiffuse: { value: null },
                    brightness: { value: 0.02 },
                    contrast: { value: 1.08 },
                    saturation: { value: 1.12 },
                    vignetteOffset: { value: 0.95 },
                    vignetteDarkness: { value: 1.2 },
                    liftShadows: { value: new THREE.Vector3(0.98, 0.95, 1.05) },
                    gainHighlights: { value: new THREE.Vector3(1.05, 1.02, 0.95) },
                    time: { value: 0.0 },
                    grainIntensity: { value: 0.035 },
                    chromaticAberration: { value: 0.0015 },
                },
                vertexShader: [
                    'varying vec2 vUv;',
                    'void main() {',
                    '  vUv = uv;',
                    '  gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);',
                    '}',
                ].join('\n'),
                fragmentShader: [
                    'uniform sampler2D tDiffuse;',
                    'uniform float brightness;',
                    'uniform float contrast;',
                    'uniform float saturation;',
                    'uniform float vignetteOffset;',
                    'uniform float vignetteDarkness;',
                    'uniform vec3 liftShadows;',
                    'uniform vec3 gainHighlights;',
                    'uniform float time;',
                    'uniform float grainIntensity;',
                    'uniform float chromaticAberration;',
                    'varying vec2 vUv;',
                    '',
                    'float rand(vec2 co) {',
                    '  return fract(sin(dot(co, vec2(12.9898, 78.233))) * 43758.5453);',
                    '}',
                    '',
                    'void main() {',
                    '  // Chromatic aberration — subtle RGB channel separation at edges',
                    '  vec2 dir = (vUv - 0.5) * chromaticAberration;',
                    '  float r = texture2D(tDiffuse, vUv + dir).r;',
                    '  float g = texture2D(tDiffuse, vUv).g;',
                    '  float b = texture2D(tDiffuse, vUv - dir).b;',
                    '  float a = texture2D(tDiffuse, vUv).a;',
                    '  vec3 color = vec3(r, g, b);',
                    '',
                    '  // Brightness + Contrast',
                    '  color = (color + brightness - 0.5) * contrast + 0.5;',
                    '',
                    '  // Saturation',
                    '  float luma = dot(color, vec3(0.2126, 0.7152, 0.0722));',
                    '  color = mix(vec3(luma), color, saturation);',
                    '',
                    '  // Lift/Gain split toning',
                    '  float luminance = dot(color, vec3(0.2126, 0.7152, 0.0722));',
                    '  color = mix(color * liftShadows, color * gainHighlights, luminance);',
                    '',
                    '  // Film grain — organic photographic noise',
                    '  float grain = rand(vUv * 1000.0 + time) * 2.0 - 1.0;',
                    '  color += grain * grainIntensity * (1.0 - luminance);',
                    '',
                    '  // Vignette',
                    '  vec2 uv = (vUv - vec2(0.5)) * vec2(vignetteOffset);',
                    '  float vignette = clamp(1.0 - dot(uv, uv), 0.0, 1.0);',
                    '  color *= mix(1.0 - vignetteDarkness, 1.0, vignette);',
                    '',
                    '  gl_FragColor = vec4(clamp(color, 0.0, 1.0), a);',
                    '}',
                ].join('\n'),
            };
            const colorPass = new THREE.ShaderPass(colorGradingShader);
            colorPass.userData.timeUniform = colorGradingShader.uniforms.time;
            composer.addPass(colorPass);
        }

        // 6. SCREEN SPACE REFLECTIONS — subtle floor reflections
        if (typeof THREE.ShaderPass !== 'undefined') {
            const ssrShader = {
                uniforms: {
                    tDiffuse: { value: null },
                    resolution: { value: new THREE.Vector2(w, h) },
                    ssrIntensity: { value: 0.15 },
                },
                vertexShader: 'varying vec2 vUv; void main(){ vUv = uv; gl_Position = projectionMatrix * modelViewMatrix * vec4(position,1.0); }',
                fragmentShader: [
                    'uniform sampler2D tDiffuse;',
                    'uniform vec2 resolution;',
                    'uniform float ssrIntensity;',
                    'varying vec2 vUv;',
                    'void main() {',
                    '  vec4 color = texture2D(tDiffuse, vUv);',
                    '  // Reflect lower portion of screen onto the floor area',
                    '  float floorZone = smoothstep(0.0, 0.35, vUv.y) * (1.0 - smoothstep(0.35, 0.55, vUv.y));',
                    '  vec2 reflectUv = vec2(vUv.x, 1.0 - vUv.y);',
                    '  vec4 reflected = texture2D(tDiffuse, reflectUv);',
                    '  // Fade reflection at edges and apply intensity',
                    '  float edgeFade = smoothstep(0.0, 0.1, vUv.x) * smoothstep(1.0, 0.9, vUv.x);',
                    '  color.rgb = mix(color.rgb, reflected.rgb, floorZone * ssrIntensity * edgeFade);',
                    '  gl_FragColor = color;',
                    '}',
                ].join('\n'),
            };
            const ssrPass = new THREE.ShaderPass(ssrShader);
            composer.addPass(ssrPass);
        }

        // 7. MOTION BLUR — camera-velocity based directional blur
        if (typeof THREE.ShaderPass !== 'undefined') {
            const motionBlurShader = {
                uniforms: {
                    tDiffuse: { value: null },
                    velocityFactor: { value: 0.0 },
                    delta: { value: new THREE.Vector2(0, 0) },
                },
                vertexShader: 'varying vec2 vUv; void main(){ vUv = uv; gl_Position = projectionMatrix * modelViewMatrix * vec4(position,1.0); }',
                fragmentShader: [
                    'uniform sampler2D tDiffuse;',
                    'uniform float velocityFactor;',
                    'uniform vec2 delta;',
                    'varying vec2 vUv;',
                    'void main() {',
                    '  vec2 texelSize = delta * velocityFactor;',
                    '  vec4 color = texture2D(tDiffuse, vUv);',
                    '  color += texture2D(tDiffuse, vUv + texelSize * 1.0);',
                    '  color += texture2D(tDiffuse, vUv - texelSize * 1.0);',
                    '  color += texture2D(tDiffuse, vUv + texelSize * 2.0);',
                    '  color += texture2D(tDiffuse, vUv - texelSize * 2.0);',
                    '  gl_FragColor = color / 5.0;',
                    '}',
                ].join('\n'),
            };
            const motionPass = new THREE.ShaderPass(motionBlurShader);
            motionPass.userData.isMotionBlur = true;
            motionPass.userData.prevCamPos = new THREE.Vector3();
            motionPass.userData.prevCamRot = new THREE.Euler();
            composer.addPass(motionPass);
        }

        // 8. FXAA anti-aliasing as final pass
        if (typeof THREE.ShaderPass !== 'undefined' && typeof THREE.FXAAShader !== 'undefined') {
            const fxaaPass = new THREE.ShaderPass(THREE.FXAAShader);
            const pixelRatio = renderer.getPixelRatio();
            fxaaPass.material.uniforms['resolution'].value.x = 1 / (w * pixelRatio);
            fxaaPass.material.uniforms['resolution'].value.y = 1 / (h * pixelRatio);
            fxaaPass.renderToScreen = true;
            composer.addPass(fxaaPass);
        }

        } catch (err) {
            console.error('Post-processing setup failed, falling back to direct render:', err);
            composer = null;
        }
    }

    // ═══ LIGHTING — Cinematic golden hour with warm/cool contrast ═══
    function setupLighting() {
        // Very low ambient — dramatic contrast
        const ambient = new THREE.AmbientLight(0x201510, 0.35);
        scene.add(ambient);

        // Main sun — rich warm golden, low angle for long dramatic shadows
        const sunLight = new THREE.DirectionalLight(0xFFBB66, 3.0);
        sunLight.position.set(12, 6, -15);
        sunLight.target.position.set(0, 1.8, 0);
        sunLight.castShadow = true;
        sunLight.shadow.mapSize.set(quality.shadowSize, quality.shadowSize);
        sunLight.shadow.camera.near = 0.5;
        sunLight.shadow.camera.far = 40;
        sunLight.shadow.camera.left = -10;
        sunLight.shadow.camera.right = 10;
        sunLight.shadow.camera.top = 10;
        sunLight.shadow.camera.bottom = -5;
        sunLight.shadow.bias = -0.0003;
        sunLight.shadow.radius = 3;
        sunLight.shadow.normalBias = 0.02;
        scene.add(sunLight);
        scene.add(sunLight.target);

        // Sky hemisphere — cool blue sky above, warm sandy ground below
        const skyFill = new THREE.HemisphereLight(0x4488BB, 0xAA8855, 0.35);
        scene.add(skyFill);

        // Rim/back light — cool blue-purple for depth separation
        const rimLight = new THREE.DirectionalLight(0x5577AA, 0.5);
        rimLight.position.set(-8, 5, 10);
        scene.add(rimLight);

        // Warm fill light from the opposite side — sandy bounce
        const fillLight = new THREE.DirectionalLight(0xDDA866, 0.4);
        fillLight.position.set(-5, 2, -6);
        scene.add(fillLight);

        // Ocean bounce — subtle cool aqua from below
        const oceanBounce = new THREE.DirectionalLight(0x448899, 0.2);
        oceanBounce.position.set(0, -1, -5);
        scene.add(oceanBounce);

        // Warm candle accent on table
        const candleLight = new THREE.PointLight(0xFF8822, 6, 4, 2);
        candleLight.position.set(0, 2.4, -1.0);
        candleLight.userData.isCandle = true;
        candleLight.userData.baseIntensity = 6;
        scene.add(candleLight);

        // Focused warm spotlight on the board for drama
        const boardSpot = new THREE.SpotLight(0xFFF5E0, 1.5, 5, Math.PI / 6, 0.5, 1.5);
        boardSpot.position.set(0, 3.8, 0);
        boardSpot.target.position.set(0, 1.8, 0);
        boardSpot.castShadow = true;
        boardSpot.shadow.mapSize.set(quality.spotShadow, quality.spotShadow);
        scene.add(boardSpot);
        scene.add(boardSpot.target);

        // ── LIGHT PROBES — Spherical Harmonics for indirect illumination ──
        if (typeof THREE.LightProbe !== 'undefined') {
            // Warm golden probe for table area (captures warm sun bounce)
            const tableProbe = new THREE.LightProbe();
            tableProbe.position.set(0, 2.0, 0);
            // Set SH coefficients for warm indirect light
            const sh = tableProbe.sh.coefficients;
            sh[0].set(0.35, 0.28, 0.18);  // L00 — overall warm ambient
            sh[1].set(0.0, 0.05, 0.08);   // L1-1 — slight cool from sky above
            sh[2].set(0.15, 0.10, 0.05);  // L10 — warm from ground bounce
            sh[3].set(0.08, 0.06, 0.02);  // L11 — warm from sun direction
            tableProbe.intensity = 0.6;
            scene.add(tableProbe);

            // Cool probe for distant areas (sky-dominant)
            const skyProbe = new THREE.LightProbe();
            skyProbe.position.set(0, 8, -20);
            const skySH = skyProbe.sh.coefficients;
            skySH[0].set(0.20, 0.25, 0.35); // L00 — cool blue ambient
            skySH[1].set(0.0, 0.03, 0.08);  // L1-1 — sky gradient
            skySH[2].set(0.05, 0.08, 0.12); // L10 — upper sky
            skySH[3].set(0.02, 0.04, 0.06); // L11 — horizon
            skyProbe.intensity = 0.4;
            scene.add(skyProbe);
        }
    }

    // ═══ TROPICAL LUXURY PAVILION ═══
    function createRoom() {
        roomGroup.clear();

        // === STONE TILE FLOOR — warm polished sandstone terrace with procedural PBR ===
        const floorGeo = new THREE.PlaneGeometry(80, 80, 1, 1);
        floorGeo.rotateX(-Math.PI / 2);
        const stoneColorTex = textureCache.stoneColor;
        stoneColorTex.repeat.set(12, 12);
        const stoneNormalTex = textureCache.stoneNormal;
        stoneNormalTex.repeat.set(12, 12);
        const stoneRoughTex = textureCache.stoneRoughness;
        stoneRoughTex.repeat.set(12, 12);
        const floorMat = new THREE.MeshStandardMaterial({
            map: stoneColorTex,
            normalMap: stoneNormalTex,
            normalScale: new THREE.Vector2(0.4, 0.4),
            roughnessMap: stoneRoughTex,
            roughness: 0.55,
            metalness: 0.05,
            envMap,
            envMapIntensity: 0.4,
        });
        const floor = new THREE.Mesh(floorGeo, floorMat);
        floor.receiveShadow = true;
        roomGroup.add(floor);

        // Decorative floor tile border (concentric rectangle accent)
        const tileBorderGeo = new THREE.RingGeometry(3.0, 3.3, 4);
        tileBorderGeo.rotateX(-Math.PI / 2);
        tileBorderGeo.rotateY(Math.PI / 4);
        const tileBorderMat = new THREE.MeshStandardMaterial({
            color: 0x8B6914,
            roughness: 0.45,
            metalness: 0.18,
            envMap,
            envMapIntensity: 0.4,
        });
        const tileBorder = new THREE.Mesh(tileBorderGeo, tileBorderMat);
        tileBorder.position.y = 0.003;
        roomGroup.add(tileBorder);

        // Secondary tile geometric accent
        const tileInnerGeo = new THREE.RingGeometry(1.6, 1.8, 4);
        tileInnerGeo.rotateX(-Math.PI / 2);
        tileInnerGeo.rotateY(Math.PI / 4);
        const tileInner = new THREE.Mesh(tileInnerGeo, tileBorderMat);
        tileInner.position.y = 0.004;
        roomGroup.add(tileInner);

        // === ORNATE COLUMNS — carved pillars ===
        createPavilionColumns();

        // === FLOWING CURTAINS ===
        createCurtains();

        // === OCEAN, SKY & SCENERY ===
        createOceanAndSky();

        // === TROPICAL VEGETATION ===
        createPalmTrees();
        createFlowers();

        // === ORNATE PERSIAN RUG under table ===
        const rugGeo = new THREE.CircleGeometry(3.5, 48);
        rugGeo.rotateX(-Math.PI / 2);
        const rugMat = new THREE.MeshStandardMaterial({
            color: 0x7B2A1A,
            roughness: 0.72,
            metalness: 0.02,
        });
        const rug = new THREE.Mesh(rugGeo, rugMat);
        rug.position.y = 0.005;
        rug.receiveShadow = true;
        roomGroup.add(rug);

        // Rug ornate outer border (gold edge)
        const rugBorderGeo = new THREE.RingGeometry(3.2, 3.5, 48);
        rugBorderGeo.rotateX(-Math.PI / 2);
        const rugBorderMat = new THREE.MeshStandardMaterial({
            color: 0xAA7720,
            roughness: 0.55,
            metalness: 0.20,
        });
        const rugBorder = new THREE.Mesh(rugBorderGeo, rugBorderMat);
        rugBorder.position.y = 0.006;
        roomGroup.add(rugBorder);

        // Rug middle accent ring
        const rugInnerGeo = new THREE.RingGeometry(2.0, 2.2, 48);
        rugInnerGeo.rotateX(-Math.PI / 2);
        const rugInnerMat = new THREE.MeshStandardMaterial({
            color: 0xC9950C,
            roughness: 0.55,
            metalness: 0.22,
        });
        const rugInner = new THREE.Mesh(rugInnerGeo, rugInnerMat);
        rugInner.position.y = 0.007;
        roomGroup.add(rugInner);

        // Rug center medallion
        const rugCenterGeo = new THREE.CircleGeometry(1.0, 48);
        rugCenterGeo.rotateX(-Math.PI / 2);
        const rugCenterMat = new THREE.MeshStandardMaterial({
            color: 0x5A1A10,
            roughness: 0.70,
            metalness: 0.03,
        });
        const rugCenter = new THREE.Mesh(rugCenterGeo, rugCenterMat);
        rugCenter.position.y = 0.008;
        roomGroup.add(rugCenter);
    }

    function createPavilionColumns() {
        // Six ornate columns — carved mahogany with gold trim, Corinthian-style
        const columnPositions = [
            [-4, 0, -4],
            [4, 0, -4],
            [-4, 0, 4],
            [4, 0, 4],
            [-4, 0, 0],   // Mid-left
            [4, 0, 0],    // Mid-right
        ];

        const goldTrimMat = new THREE.MeshStandardMaterial({
            color: 0xC9950C,
            roughness: 0.20,
            metalness: 0.80,
            envMap,
            envMapIntensity: 1.0,
        });
        const darkWoodMat = new THREE.MeshStandardMaterial({
            map: textureCache.darkWoodColor,
            normalMap: textureCache.darkWoodNormal,
            normalScale: new THREE.Vector2(0.3, 0.3),
            roughness: 0.32,
            metalness: 0.08,
            envMap,
            envMapIntensity: 0.6,
        });

        columnPositions.forEach(pos => {
            // Column shaft — richly carved lathe profile with multiple rings
            const shaftProfile = [
                new THREE.Vector2(0, 0),
                new THREE.Vector2(0.20, 0),
                new THREE.Vector2(0.22, 0.03),
                new THREE.Vector2(0.22, 0.08),         // Plinth
                new THREE.Vector2(0.17, 0.12),
                new THREE.Vector2(0.14, 0.20),         // Base taper
                new THREE.Vector2(0.12, 0.40),
                new THREE.Vector2(0.14, 0.44),         // Ring 1
                new THREE.Vector2(0.14, 0.48),
                new THREE.Vector2(0.11, 0.55),
                new THREE.Vector2(0.11, 1.30),
                new THREE.Vector2(0.13, 1.33),         // Ring 2
                new THREE.Vector2(0.13, 1.37),
                new THREE.Vector2(0.10, 1.45),
                new THREE.Vector2(0.10, 2.20),
                new THREE.Vector2(0.12, 2.23),         // Ring 3
                new THREE.Vector2(0.12, 2.27),
                new THREE.Vector2(0.10, 2.35),
                new THREE.Vector2(0.10, 3.40),
                new THREE.Vector2(0.12, 3.43),         // Ring 4
                new THREE.Vector2(0.12, 3.47),
                new THREE.Vector2(0.10, 3.55),
                new THREE.Vector2(0.10, 3.70),
                new THREE.Vector2(0.14, 3.72),         // Capital base
                new THREE.Vector2(0.18, 3.78),
                new THREE.Vector2(0.22, 3.85),         // Capital swell
                new THREE.Vector2(0.24, 3.92),
                new THREE.Vector2(0.24, 4.00),
                new THREE.Vector2(0.22, 4.05),
                new THREE.Vector2(0.18, 4.08),
                new THREE.Vector2(0.14, 4.10),
                new THREE.Vector2(0, 4.10),
            ];
            const shaftGeo = new THREE.LatheGeometry(shaftProfile, 20);
            const shaft = new THREE.Mesh(shaftGeo, darkWoodMat);
            shaft.position.set(...pos);
            shaft.castShadow = true;
            shaft.receiveShadow = true;
            roomGroup.add(shaft);

            // Gold rings at each carved band
            [0.46, 1.35, 2.25, 3.45, 3.72].forEach((ringY, idx) => {
                const r = idx === 4 ? 0.15 : 0.125;
                const ringGeo = new THREE.TorusGeometry(r, 0.012, 6, 20);
                const ring = new THREE.Mesh(ringGeo, goldTrimMat);
                ring.position.set(pos[0], ringY, pos[2]);
                ring.rotation.x = Math.PI / 2;
                roomGroup.add(ring);
            });

            // Gold capital wreath ring
            const capitalGeo = new THREE.TorusGeometry(0.20, 0.018, 8, 20);
            const capital = new THREE.Mesh(capitalGeo, goldTrimMat);
            capital.position.set(pos[0], 3.90, pos[2]);
            capital.rotation.x = Math.PI / 2;
            roomGroup.add(capital);

            // Gold base ring
            const baseRingGeo = new THREE.TorusGeometry(0.19, 0.015, 8, 20);
            const baseRing = new THREE.Mesh(baseRingGeo, goldTrimMat);
            baseRing.position.set(pos[0], 0.08, pos[2]);
            baseRing.rotation.x = Math.PI / 2;
            roomGroup.add(baseRing);

            // Small decorative ball finial on top of each column
            const finialGeo = new THREE.SphereGeometry(0.04, 8, 8);
            const finial = new THREE.Mesh(finialGeo, goldTrimMat);
            finial.position.set(pos[0], 4.12, pos[2]);
            roomGroup.add(finial);
        });

        // Canopy beams connecting columns — carved wood
        const beamMat = new THREE.MeshStandardMaterial({
            color: 0x3A1E08,
            roughness: 0.32,
            metalness: 0.06,
            envMap,
            envMapIntensity: 0.4,
        });
        const beamPairs = [
            [[-4, 4.1, -4], [4, 4.1, -4]],   // Back
            [[-4, 4.1, 4], [4, 4.1, 4]],      // Front
            [[-4, 4.1, -4], [-4, 4.1, 4]],    // Left
            [[4, 4.1, -4], [4, 4.1, 4]],      // Right
        ];
        beamPairs.forEach(([start, end]) => {
            const dx = end[0] - start[0], dz = end[2] - start[2];
            const len = Math.sqrt(dx * dx + dz * dz);
            const beam = new THREE.Mesh(new THREE.BoxGeometry(0.14, 0.10, len + 0.3), beamMat);
            beam.position.set((start[0] + end[0]) / 2, 4.1, (start[2] + end[2]) / 2);
            if (Math.abs(dx) > Math.abs(dz)) beam.rotation.y = Math.PI / 2;
            beam.castShadow = true;
            roomGroup.add(beam);
        });

        // Cross beams (X pattern)
        const crossBeam1 = new THREE.Mesh(new THREE.BoxGeometry(0.07, 0.07, 11.4), beamMat);
        crossBeam1.position.set(0, 4.15, 0);
        crossBeam1.rotation.y = Math.PI / 4;
        roomGroup.add(crossBeam1);

        const crossBeam2 = new THREE.Mesh(new THREE.BoxGeometry(0.07, 0.07, 11.4), beamMat);
        crossBeam2.position.set(0, 4.15, 0);
        crossBeam2.rotation.y = -Math.PI / 4;
        roomGroup.add(crossBeam2);

        // Canopy roof — warm woven thatch/bamboo canopy with slatted light
        const roofGeo = new THREE.PlaneGeometry(8.6, 8.6, 8, 8);
        roofGeo.rotateX(-Math.PI / 2);
        // Add slight sag in center for natural canopy look
        const roofPos = roofGeo.attributes.position;
        for (let i = 0; i < roofPos.count; i++) {
            const x = roofPos.getX(i);
            const z = roofPos.getZ(i);
            const dist = Math.sqrt(x * x + z * z) / 6;
            roofPos.setY(i, roofPos.getY(i) - dist * dist * 0.15);
        }
        roofGeo.computeVertexNormals();
        const roofMat = new THREE.MeshStandardMaterial({
            color: 0x8B6B3A,
            roughness: 0.92,
            metalness: 0.0,
            transparent: true,
            opacity: 0.6,
            side: THREE.DoubleSide,
        });
        const roof = new THREE.Mesh(roofGeo, roofMat);
        roof.position.y = 4.18;
        roof.receiveShadow = true;
        roomGroup.add(roof);
    }

    function createCurtains() {
        // Sheer flowing curtains — light ivory silk, very transparent
        const curtainMat = new THREE.MeshStandardMaterial({
            color: 0xFFF5E8,
            roughness: 0.80,
            metalness: 0.0,
            transparent: true,
            opacity: 0.30,
            side: THREE.DoubleSide,
        });

        // Curtain panels at each opening (draped to the sides)
        const curtainPositions = [
            { pos: [-4.3, 2.1, -4], rot: [0, 0, 0], size: [0.8, 3.8] },        // Back-left
            { pos: [4.3, 2.1, -4], rot: [0, 0, 0], size: [0.8, 3.8] },         // Back-right
            { pos: [-4.3, 2.1, 4], rot: [0, 0, 0], size: [0.8, 3.8] },         // Front-left
            { pos: [4.3, 2.1, 4], rot: [0, 0, 0], size: [0.8, 3.8] },          // Front-right
            { pos: [-4, 2.1, -4.3], rot: [0, Math.PI / 2, 0], size: [0.8, 3.8] }, // Left-back
            { pos: [-4, 2.1, 4.3], rot: [0, Math.PI / 2, 0], size: [0.8, 3.8] },  // Left-front
            { pos: [4, 2.1, -4.3], rot: [0, Math.PI / 2, 0], size: [0.8, 3.8] },  // Right-back
            { pos: [4, 2.1, 4.3], rot: [0, Math.PI / 2, 0], size: [0.8, 3.8] },   // Right-front
        ];

        curtainPositions.forEach(c => {
            // Wavy curtain shape using sine-deformed plane
            const curtGeo = new THREE.PlaneGeometry(c.size[0], c.size[1], 8, 16);
            const posAttr = curtGeo.attributes.position;
            for (let i = 0; i < posAttr.count; i++) {
                const y = posAttr.getY(i);
                const wave = Math.sin(y * 3) * 0.06 + Math.sin(y * 7) * 0.02;
                posAttr.setZ(i, posAttr.getZ(i) + wave);
            }
            curtGeo.computeVertexNormals();
            const curtain = new THREE.Mesh(curtGeo, curtainMat);
            curtain.position.set(...c.pos);
            curtain.rotation.set(...c.rot);
            curtain.userData.isCurtain = true;
            curtain.userData.baseX = c.pos[0];
            curtain.userData.baseZ = c.pos[2];
            roomGroup.add(curtain);
        });

        // Curtain tie-backs (golden rope)
        const tieGeo = new THREE.TorusGeometry(0.08, 0.015, 6, 12);
        const tieMat = new THREE.MeshStandardMaterial({
            color: 0xDAA520,
            roughness: 0.3,
            metalness: 0.7,
            envMap,
            envMapIntensity: 1.0,
        });
        curtainPositions.forEach(c => {
            const tie = new THREE.Mesh(tieGeo, tieMat);
            tie.position.set(c.pos[0], 1.5, c.pos[2]);
            tie.rotation.set(c.rot[0], c.rot[1], c.rot[2]);
            roomGroup.add(tie);
        });
    }

    function createOceanAndSky() {
        // === OCEAN — deep turquoise animated water with normal-mapped waves ===
        const oceanGeo = new THREE.PlaneGeometry(160, 160, 80, 80);
        oceanGeo.rotateX(-Math.PI / 2);
        const oceanNormalTex = textureCache.oceanNormal;
        oceanNormalTex.repeat.set(8, 8);
        const oceanMat = new THREE.MeshStandardMaterial({
            color: 0x1A7B8C,
            normalMap: oceanNormalTex,
            normalScale: new THREE.Vector2(0.6, 0.6),
            roughness: 0.05,
            metalness: 0.3,
            transparent: true,
            opacity: 0.92,
            envMap,
            envMapIntensity: 2.5,
        });
        const ocean = new THREE.Mesh(oceanGeo, oceanMat);
        ocean.position.y = -0.18;
        ocean.userData.isOcean = true;
        roomGroup.add(ocean);

        // Sun reflection path on water — bright strip toward sun
        const sunPathGeo = new THREE.PlaneGeometry(4, 80);
        sunPathGeo.rotateX(-Math.PI / 2);
        const sunPathMat = new THREE.MeshBasicMaterial({
            color: 0xFFDD88,
            transparent: true,
            opacity: 0.12,
        });
        const sunPath = new THREE.Mesh(sunPathGeo, sunPathMat);
        sunPath.position.set(8, -0.10, -30);
        sunPath.rotation.y = 0.35;
        roomGroup.add(sunPath);

        // Beach sand — wider, warmer with subtle grain
        const sandGeo = new THREE.PlaneGeometry(40, 8);
        sandGeo.rotateX(-Math.PI / 2);
        const sandRoughTex = generateRoughnessMap(512, 512, 0.80);
        sandRoughTex.repeat.set(4, 1);
        const sandMat = new THREE.MeshStandardMaterial({
            color: 0xD4B88C,
            roughnessMap: sandRoughTex,
            roughness: 0.80,
            metalness: 0.0,
        });
        const sand = new THREE.Mesh(sandGeo, sandMat);
        sand.position.set(0, -0.06, 14);
        sand.receiveShadow = true;
        roomGroup.add(sand);

        // Wet sand strip at shoreline
        const wetSandGeo = new THREE.PlaneGeometry(40, 2);
        wetSandGeo.rotateX(-Math.PI / 2);
        const wetSandMat = new THREE.MeshStandardMaterial({
            color: 0x8C7A5C,
            roughness: 0.30,
            metalness: 0.15,
            envMap,
            envMapIntensity: 0.6,
        });
        const wetSand = new THREE.Mesh(wetSandGeo, wetSandMat);
        wetSand.position.set(0, -0.08, 10);
        roomGroup.add(wetSand);

        // === DISTANT ISLANDS ===
        const hillMat = new THREE.MeshStandardMaterial({
            color: 0x2D6B45,
            roughness: 0.75,
            metalness: 0.0,
        });
        const hill1 = new THREE.Mesh(new THREE.SphereGeometry(8, 16, 8, 0, Math.PI * 2, 0, Math.PI / 2), hillMat);
        hill1.position.set(-30, -1.5, -45);
        hill1.scale.set(1.2, 0.25, 1);
        roomGroup.add(hill1);

        const hill2 = new THREE.Mesh(new THREE.SphereGeometry(5, 16, 8, 0, Math.PI * 2, 0, Math.PI / 2), hillMat);
        hill2.position.set(25, -1, -50);
        hill2.scale.set(1.8, 0.20, 1);
        roomGroup.add(hill2);

        // Island 3 — closer, smaller
        const hill3 = new THREE.Mesh(new THREE.SphereGeometry(3, 12, 6, 0, Math.PI * 2, 0, Math.PI / 2), hillMat);
        hill3.position.set(15, -0.5, -30);
        hill3.scale.set(1, 0.3, 0.8);
        roomGroup.add(hill3);

        // === SKY DOME — rich golden-hour gradient ===
        const skyGeo = new THREE.SphereGeometry(70, 32, 24);
        const skyMat = new THREE.MeshBasicMaterial({
            side: THREE.BackSide,
        });
        // Multi-band sunset gradient
        const skyColors = new Float32Array(skyGeo.attributes.position.count * 3);
        for (let i = 0; i < skyGeo.attributes.position.count; i++) {
            const y = skyGeo.attributes.position.getY(i);
            const t = Math.max(0, Math.min(1, (y + 15) / 85)); // 0=horizon, 1=zenith
            let r, g, b;
            if (t < 0.15) {
                // Horizon: warm peach-gold
                const ht = t / 0.15;
                r = 0.95 - ht * 0.10;
                g = 0.55 - ht * 0.05;
                b = 0.25 + ht * 0.05;
            } else if (t < 0.35) {
                // Lower sky: salmon → soft orange
                const ht = (t - 0.15) / 0.20;
                r = 0.85 - ht * 0.25;
                g = 0.50 - ht * 0.10;
                b = 0.30 + ht * 0.05;
            } else {
                // Upper sky: soft blue deepening
                const ht = (t - 0.35) / 0.65;
                r = 0.60 - ht * 0.48;
                g = 0.40 - ht * 0.15;
                b = 0.35 + ht * 0.20;
            }
            skyColors[i * 3] = r;
            skyColors[i * 3 + 1] = g;
            skyColors[i * 3 + 2] = b;
        }
        skyGeo.setAttribute('color', new THREE.BufferAttribute(skyColors, 3));
        skyMat.vertexColors = true;
        const sky = new THREE.Mesh(skyGeo, skyMat);
        roomGroup.add(sky);

        // Sun disc — larger, warmer
        const sunGeo = new THREE.SphereGeometry(4, 16, 16);
        const sunMat = new THREE.MeshBasicMaterial({
            color: 0xFFCC55,
            transparent: true,
            opacity: 0.98,
        });
        const sun = new THREE.Mesh(sunGeo, sunMat);
        sun.position.set(22, 7, -50);
        roomGroup.add(sun);

        // Inner warm halo
        const haloGeo = new THREE.SphereGeometry(7, 16, 16);
        const haloMat = new THREE.MeshBasicMaterial({
            color: 0xFFBB55,
            transparent: true,
            opacity: 0.30,
        });
        const halo = new THREE.Mesh(haloGeo, haloMat);
        halo.position.set(22, 7, -50);
        roomGroup.add(halo);

        // Outer atmospheric glow
        const outerHaloGeo = new THREE.SphereGeometry(12, 16, 16);
        const outerHaloMat = new THREE.MeshBasicMaterial({
            color: 0xFFAA55,
            transparent: true,
            opacity: 0.12,
        });
        const outerHalo = new THREE.Mesh(outerHaloGeo, outerHaloMat);
        outerHalo.position.set(22, 7, -50);
        roomGroup.add(outerHalo);

        // Clouds — simple soft shapes near horizon
        const cloudMat = new THREE.MeshBasicMaterial({
            color: 0xFFDDAA,
            transparent: true,
            opacity: 0.25,
        });
        [[-20, 12, -55], [35, 10, -48], [-35, 8, -52], [0, 14, -60]].forEach(cPos => {
            const cloud = new THREE.Mesh(
                new THREE.SphereGeometry(3 + Math.random() * 3, 8, 6),
                cloudMat
            );
            cloud.position.set(...cPos);
            cloud.scale.set(2.5 + Math.random(), 0.4, 1.2);
            roomGroup.add(cloud);
        });

        // === PROCEDURAL LENS FLARE — sun produces cinematic light artifacts ===
        createSunLensFlare(22, 7, -50);
    }

    function createSunLensFlare(sx, sy, sz) {
        // Procedural flare textures (no external images required)
        const flareTexture = generateFlareTexture(256, 0xFFDD88, 1.0);
        const ringTexture = generateFlareTexture(256, 0xFFAA44, 0.5);
        const hexTexture = generateFlareTexture(64, 0xFF9933, 0.3);

        if (typeof THREE.Lensflare !== 'undefined') {
            // Use built-in Lensflare addon if loaded
            const lensflare = new THREE.Lensflare();
            lensflare.addElement(new THREE.LensflareElement(flareTexture, 700, 0));
            lensflare.addElement(new THREE.LensflareElement(ringTexture, 200, 0.2));
            lensflare.addElement(new THREE.LensflareElement(hexTexture, 60, 0.6));
            lensflare.addElement(new THREE.LensflareElement(hexTexture, 40, 0.8));
            lensflare.position.set(sx, sy, sz);
            roomGroup.add(lensflare);
        } else {
            // Fallback: simple sprite-based flare
            const flareMat = new THREE.SpriteMaterial({
                map: flareTexture,
                transparent: true,
                opacity: 0.6,
                blending: THREE.AdditiveBlending,
                depthTest: false,
            });
            const flareSprite = new THREE.Sprite(flareMat);
            flareSprite.position.set(sx, sy, sz);
            flareSprite.scale.set(15, 15, 1);
            roomGroup.add(flareSprite);
        }
    }

    function generateFlareTexture(size, color, intensity) {
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        const ctx = canvas.getContext('2d');
        const c = new THREE.Color(color);
        const half = size / 2;

        // Radial gradient
        const grad = ctx.createRadialGradient(half, half, 0, half, half, half);
        grad.addColorStop(0, `rgba(${Math.round(c.r*255)},${Math.round(c.g*255)},${Math.round(c.b*255)},${intensity})`);
        grad.addColorStop(0.2, `rgba(${Math.round(c.r*200)},${Math.round(c.g*200)},${Math.round(c.b*180)},${intensity*0.5})`);
        grad.addColorStop(1, 'rgba(0,0,0,0)');
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, size, size);

        const tex = new THREE.CanvasTexture(canvas);
        tex.needsUpdate = true;
        return tex;
    }

    function createPalmTrees() {
        // Procedural palm tree — segmented brown trunk + realistic drooping fronds
        const trunkMat = new THREE.MeshStandardMaterial({
            color: 0x6B4820,
            roughness: 0.75,
            metalness: 0.0,
        });
        const frondMat = new THREE.MeshStandardMaterial({
            color: 0x2D6B28,
            roughness: 0.65,
            metalness: 0.0,
            side: THREE.DoubleSide,
            transparent: true,
            alphaTest: 0.1,
        });

        // Build a reusable leaf-shaped geometry (tapered + serrated edges)
        function createFrondGeometry() {
            const segs = 12;
            const verts = [];
            const indices = [];
            const uvs = [];
            // Create tapered leaf shape with slight serration
            for (let i = 0; i <= segs; i++) {
                const t = i / segs;
                // Leaf width: starts thin, widens in middle, tapers to point
                const w = Math.sin(t * Math.PI) * 0.35 * (1 - t * 0.3);
                const y = t * 3.5; // length
                const droop = t * t * 0.9; // increasing droop toward tip
                const sway = Math.sin(t * 4) * 0.04; // subtle wave

                // Left edge with small serrations
                const serL = (i % 2 === 0) ? 0.02 : 0;
                verts.push(-w - serL + sway, y, -droop);
                uvs.push(0, t);

                // Center spine (slightly raised)
                verts.push(sway, y, -droop + 0.02);
                uvs.push(0.5, t);

                // Right edge with small serrations
                const serR = (i % 2 === 1) ? 0.02 : 0;
                verts.push(w + serR + sway, y, -droop);
                uvs.push(1, t);
            }

            for (let i = 0; i < segs; i++) {
                const row = i * 3;
                const next = (i + 1) * 3;
                // Left triangle pair
                indices.push(row, next, row + 1);
                indices.push(row + 1, next, next + 1);
                // Right triangle pair
                indices.push(row + 1, next + 1, row + 2);
                indices.push(row + 2, next + 1, next + 2);
            }

            const geo = new THREE.BufferGeometry();
            geo.setAttribute('position', new THREE.Float32BufferAttribute(verts, 3));
            geo.setAttribute('uv', new THREE.Float32BufferAttribute(uvs, 2));
            geo.setIndex(indices);
            geo.computeVertexNormals();
            return geo;
        }

        const frondGeo = createFrondGeometry();

        const palmPositions = [
            { pos: [-9, 0, -7], height: 8, lean: 0.12 },
            { pos: [10, 0, -9], height: 9, lean: -0.08 },
            { pos: [-8, 0, 9], height: 7, lean: 0.18 },
            { pos: [11, 0, 7], height: 8.5, lean: -0.12 },
            { pos: [-14, 0, 1], height: 10, lean: 0.08 },
            { pos: [7, 0, -14], height: 7.5, lean: -0.06 },
            { pos: [-6, 0, -13], height: 6.5, lean: 0.15 },
            { pos: [14, 0, -2], height: 9.5, lean: -0.10 },
        ];

        palmPositions.forEach(palm => {
            const treeGroup = new THREE.Group();
            treeGroup.position.set(...palm.pos);

            // Trunk — tapered cylinder with slight curve
            const segments = 12;
            for (let i = 0; i < segments; i++) {
                const t = i / segments;
                const segH = palm.height / segments;
                const radius = 0.12 * (1 - t * 0.6); // Taper
                const segGeo = new THREE.CylinderGeometry(
                    radius * 0.9, radius, segH, 8
                );
                const seg = new THREE.Mesh(segGeo, trunkMat);
                seg.position.y = t * palm.height + segH / 2;
                seg.position.x = Math.sin(t * Math.PI * 0.5) * palm.lean * palm.height;
                seg.castShadow = true;
                treeGroup.add(seg);
            }

            // Fronds — leaf-shaped geometry radiating from crown
            const frondCount = 9;
            for (let f = 0; f < frondCount; f++) {
                const angle = (f / frondCount) * Math.PI * 2 + Math.random() * 0.3;
                const frond = new THREE.Mesh(frondGeo, frondMat);
                frond.position.y = palm.height;
                frond.position.x = palm.lean * 0.3;
                frond.rotation.y = angle;
                frond.rotation.x = -0.3 - Math.random() * 0.35;
                // Slight random scale for variety
                const s = 0.8 + Math.random() * 0.4;
                frond.scale.set(s, s * (0.7 + Math.random() * 0.4), s);
                frond.userData.isFrond = true;
                frond.userData.baseRotX = frond.rotation.x;
                treeGroup.add(frond);
            }

            roomGroup.add(treeGroup);
        });
    }

    function createFlowers() {
        // Small flower arrangements / tropical plants near columns
        const potMat = new THREE.MeshStandardMaterial({
            color: 0x8B4513,
            roughness: 0.6,
            metalness: 0.05,
        });
        const flowerColors = [0xFF69B4, 0xFFD700, 0xFF4500, 0xFF1493, 0xFF6347];

        const flowerPositions = [
            [-3, 0, -3.5],
            [3, 0, -3.5],
            [-3, 0, 3.5],
            [3, 0, 3.5],
        ];

        flowerPositions.forEach(pos => {
            // Pot
            const potGeo = new THREE.CylinderGeometry(0.15, 0.12, 0.25, 8);
            const pot = new THREE.Mesh(potGeo, potMat);
            pot.position.set(pos[0], 0.125, pos[2]);
            pot.castShadow = true;
            roomGroup.add(pot);

            // Flowers (small colored spheres clustered)
            for (let f = 0; f < 5; f++) {
                const flowerGeo = new THREE.SphereGeometry(0.05 + Math.random() * 0.03, 6, 6);
                const flowerMat = new THREE.MeshStandardMaterial({
                    color: flowerColors[Math.floor(Math.random() * flowerColors.length)],
                    roughness: 0.5,
                    metalness: 0.0,
                });
                const flower = new THREE.Mesh(flowerGeo, flowerMat);
                flower.position.set(
                    pos[0] + (Math.random() - 0.5) * 0.2,
                    0.3 + Math.random() * 0.15,
                    pos[2] + (Math.random() - 0.5) * 0.2
                );
                roomGroup.add(flower);
            }

            // Leaves
            const leafMat = new THREE.MeshStandardMaterial({
                color: 0x228B22,
                roughness: 0.6,
                side: THREE.DoubleSide,
            });
            for (let l = 0; l < 4; l++) {
                const leafGeo = new THREE.PlaneGeometry(0.12, 0.2);
                const leaf = new THREE.Mesh(leafGeo, leafMat);
                leaf.position.set(
                    pos[0] + (Math.random() - 0.5) * 0.2,
                    0.28,
                    pos[2] + (Math.random() - 0.5) * 0.2
                );
                leaf.rotation.set(
                    -0.3 + Math.random() * 0.6,
                    Math.random() * Math.PI * 2,
                    0
                );
                roomGroup.add(leaf);
            }
        });
    }

    // ═══ ORNATE CHESS TABLE — Carved wood with gold inlay ═══
    function createTable() {
        const TABLE_Y = 1.8;

        // Rich dark carved mahogany — deeply polished with wood grain texture
        const woodColorTex = textureCache.woodColor;
        woodColorTex.repeat.set(2, 1);
        const woodNormalTex = textureCache.woodNormal;
        woodNormalTex.repeat.set(2, 1);
        const woodRoughTex = textureCache.woodRoughness;
        woodRoughTex.repeat.set(2, 1);
        const tableMat = new THREE.MeshStandardMaterial({
            map: woodColorTex,
            normalMap: woodNormalTex,
            normalScale: new THREE.Vector2(0.35, 0.35),
            roughnessMap: woodRoughTex,
            roughness: 0.22,
            metalness: 0.12,
            envMap,
            envMapIntensity: 0.7,
        });
        // Gold inlay trim
        const goldMat = new THREE.MeshStandardMaterial({
            color: 0xDAA520,
            roughness: 0.2,
            metalness: 0.8,
            envMap,
            envMapIntensity: 1.5,
        });

        // Table top — thick slab
        const tableTop = new THREE.Mesh(new THREE.BoxGeometry(3.2, 0.10, 2.8), tableMat);
        tableTop.position.set(0, TABLE_Y, 0);
        tableTop.castShadow = true;
        tableTop.receiveShadow = true;
        roomGroup.add(tableTop);

        // Gold edge trim — outer border
        const edgeGeo = new THREE.BoxGeometry(3.32, 0.03, 2.92);
        const edge = new THREE.Mesh(edgeGeo, goldMat);
        edge.position.set(0, TABLE_Y + 0.055, 0);
        roomGroup.add(edge);

        // Gold inner border (board frame)
        const innerBorderGeo = new THREE.BoxGeometry(2.1, 0.025, 2.1);
        const innerBorder = new THREE.Mesh(innerBorderGeo, goldMat);
        innerBorder.position.set(0, TABLE_Y + 0.056, 0);
        roomGroup.add(innerBorder);

        // Decorative apron (carved border under table top)
        const apronMat = new THREE.MeshStandardMaterial({
            color: 0x1E0D02,
            roughness: 0.30,
            metalness: 0.12,
            envMap,
            envMapIntensity: 0.7,
        });
        // Front apron
        const apronF = new THREE.Mesh(new THREE.BoxGeometry(3.1, 0.12, 0.06), apronMat);
        apronF.position.set(0, TABLE_Y - 0.11, 1.35);
        apronF.castShadow = true;
        roomGroup.add(apronF);
        // Back apron
        const apronB = new THREE.Mesh(new THREE.BoxGeometry(3.1, 0.12, 0.06), apronMat);
        apronB.position.set(0, TABLE_Y - 0.11, -1.35);
        roomGroup.add(apronB);
        // Left apron
        const apronL = new THREE.Mesh(new THREE.BoxGeometry(0.06, 0.12, 2.6), apronMat);
        apronL.position.set(-1.55, TABLE_Y - 0.11, 0);
        roomGroup.add(apronL);
        // Right apron
        const apronR = new THREE.Mesh(new THREE.BoxGeometry(0.06, 0.12, 2.6), apronMat);
        apronR.position.set(1.55, TABLE_Y - 0.11, 0);
        roomGroup.add(apronR);

        // Gold rosettes at apron corners
        const rosetteGeo = new THREE.SphereGeometry(0.06, 8, 8);
        [[-1.52, TABLE_Y - 0.11, 1.32], [1.52, TABLE_Y - 0.11, 1.32],
         [-1.52, TABLE_Y - 0.11, -1.32], [1.52, TABLE_Y - 0.11, -1.32]].forEach(pos => {
            const rosette = new THREE.Mesh(rosetteGeo, goldMat);
            rosette.position.set(...pos);
            roomGroup.add(rosette);
        });

        // Ornate cabriole legs — carved Queen Anne with claw-and-ball
        const legProfile = [
            new THREE.Vector2(0, 0),
            new THREE.Vector2(0.09, 0),        // Ball foot
            new THREE.Vector2(0.11, 0.02),
            new THREE.Vector2(0.12, 0.05),
            new THREE.Vector2(0.10, 0.08),     // Claw grip
            new THREE.Vector2(0.06, 0.15),     // Ankle taper
            new THREE.Vector2(0.05, 0.25),
            new THREE.Vector2(0.07, 0.40),     // Knee swell begins
            new THREE.Vector2(0.09, 0.50),     // Knee peak
            new THREE.Vector2(0.10, 0.55),
            new THREE.Vector2(0.10, 0.58),
            new THREE.Vector2(0.08, 0.65),     // Above knee taper
            new THREE.Vector2(0.06, 0.80),
            new THREE.Vector2(0.06, 1.10),     // Long upper shaft
            new THREE.Vector2(0.07, 1.25),     // Capital swell
            new THREE.Vector2(0.09, 1.38),
            new THREE.Vector2(0.10, 1.44),
            new THREE.Vector2(0.10, 1.52),
            new THREE.Vector2(0.08, 1.58),
            new THREE.Vector2(0.06, 1.62),
            new THREE.Vector2(0.05, 1.65),
            new THREE.Vector2(0, 1.65),
        ];
        const legGeo = new THREE.LatheGeometry(legProfile, 12);
        const legPositions = [
            [-1.4, 0, -1.15],
            [1.4, 0, -1.15],
            [-1.4, 0, 1.15],
            [1.4, 0, 1.15],
        ];
        legPositions.forEach(pos => {
            const leg = new THREE.Mesh(legGeo, tableMat);
            leg.position.set(...pos);
            leg.castShadow = true;
            roomGroup.add(leg);

            // Gold ring at knee
            const ringGeo = new THREE.TorusGeometry(0.09, 0.015, 6, 12);
            const ring = new THREE.Mesh(ringGeo, goldMat);
            ring.position.set(pos[0], 0.55, pos[2]);
            ring.rotation.x = Math.PI / 2;
            roomGroup.add(ring);
        });

        // === CANDLE in champagne glass on table ===
        const glassMat = new THREE.MeshStandardMaterial({
            color: 0xFFFFFF,
            roughness: 0.05,
            metalness: 0.1,
            transparent: true,
            opacity: 0.3,
            envMap,
            envMapIntensity: 2.0,
        });
        const glassProfile = [
            new THREE.Vector2(0, 0),
            new THREE.Vector2(0.04, 0),
            new THREE.Vector2(0.05, 0.02),
            new THREE.Vector2(0.04, 0.03),
            new THREE.Vector2(0.015, 0.06),
            new THREE.Vector2(0.012, 0.15),
            new THREE.Vector2(0.04, 0.20),
            new THREE.Vector2(0.05, 0.25),
            new THREE.Vector2(0.05, 0.30),
            new THREE.Vector2(0.045, 0.31),
        ];
        const glassGeo = new THREE.LatheGeometry(glassProfile, 12);

        [-1.4, 1.4].forEach(x => {
            const glass = new THREE.Mesh(glassGeo, glassMat);
            glass.position.set(x, TABLE_Y + 0.05, -1.0);
            roomGroup.add(glass);
        });

        // Flower vase centerpiece (near edge)
        const vaseProfile = [
            new THREE.Vector2(0, 0),
            new THREE.Vector2(0.06, 0),
            new THREE.Vector2(0.07, 0.02),
            new THREE.Vector2(0.05, 0.06),
            new THREE.Vector2(0.04, 0.12),
            new THREE.Vector2(0.05, 0.18),
            new THREE.Vector2(0.06, 0.22),
            new THREE.Vector2(0.055, 0.25),
        ];
        const vaseGeo = new THREE.LatheGeometry(vaseProfile, 10);
        const vaseMat = new THREE.MeshStandardMaterial({
            color: 0xF5F5DC,
            roughness: 0.3,
            metalness: 0.05,
            envMap,
            envMapIntensity: 0.6,
        });
        const vase = new THREE.Mesh(vaseGeo, vaseMat);
        vase.position.set(0, TABLE_Y + 0.05, -1.2);
        roomGroup.add(vase);

        // Small flowers in vase
        [0xFF69B4, 0xFFD700, 0xFF4500].forEach((c, i) => {
            const fGeo = new THREE.SphereGeometry(0.03, 6, 6);
            const fMat = new THREE.MeshStandardMaterial({ color: c, roughness: 0.5 });
            const f = new THREE.Mesh(fGeo, fMat);
            f.position.set(
                (i - 1) * 0.04,
                TABLE_Y + 0.32,
                -1.2 + (Math.random() - 0.5) * 0.04
            );
            roomGroup.add(f);
        });

        // === ELEGANT CHAIRS ===
        createChair(0, 0, 2.2, 0);          // Player chair
        createChair(0, 0, -2.2, Math.PI);   // Opponent chair
    }

    function createChair(x, y, z, rotY) {
        const chairGroup = new THREE.Group();
        chairGroup.position.set(x, y, z);
        chairGroup.rotation.y = rotY;

        // Rich burgundy velvet upholstery
        const upholsteryMat = new THREE.MeshStandardMaterial({
            color: 0x8B3A3A,
            roughness: 0.65,
            metalness: 0.02,
            envMap,
            envMapIntensity: 0.2,
        });
        // Dark carved mahogany frame
        const frameMat = new THREE.MeshStandardMaterial({
            color: 0x2A1205,
            roughness: 0.30,
            metalness: 0.10,
            envMap,
            envMapIntensity: 0.7,
        });
        // Gold accents
        const goldAccentMat = new THREE.MeshStandardMaterial({
            color: 0xDAA520,
            roughness: 0.25,
            metalness: 0.75,
            envMap,
            envMapIntensity: 1.2,
        });

        // Seat cushion (thick, padded)
        const seatGeo = new THREE.BoxGeometry(0.65, 0.12, 0.58);
        const seat = new THREE.Mesh(seatGeo, upholsteryMat);
        seat.position.y = 0.92;
        seat.castShadow = true;
        chairGroup.add(seat);

        // Seat frame
        const seatFrameGeo = new THREE.BoxGeometry(0.70, 0.04, 0.62);
        const seatFrame = new THREE.Mesh(seatFrameGeo, frameMat);
        seatFrame.position.y = 0.84;
        chairGroup.add(seatFrame);

        // Back rest — tall, curved, tufted
        const backGeo = new THREE.BoxGeometry(0.58, 0.85, 0.08);
        const backrest = new THREE.Mesh(backGeo, upholsteryMat);
        backrest.position.set(0, 1.45, -0.26);
        backrest.rotation.x = -0.08;
        backrest.castShadow = true;
        chairGroup.add(backrest);

        // Backrest crown (carved ornamental top)
        const crownGeo = new THREE.TorusGeometry(0.22, 0.03, 6, 12, Math.PI);
        const crown = new THREE.Mesh(crownGeo, frameMat);
        crown.position.set(0, 1.9, -0.30);
        crown.rotation.z = Math.PI;
        chairGroup.add(crown);

        // Gold finial on top
        const finialGeo = new THREE.SphereGeometry(0.03, 6, 6);
        const finial = new THREE.Mesh(finialGeo, goldAccentMat);
        finial.position.set(0, 1.95, -0.30);
        chairGroup.add(finial);

        // Tufting buttons (small gold dots on back)
        for (let r = 0; r < 3; r++) {
            for (let c = 0; c < 2; c++) {
                const btn = new THREE.Mesh(
                    new THREE.SphereGeometry(0.012, 4, 4),
                    goldAccentMat
                );
                btn.position.set(
                    (c - 0.5) * 0.2,
                    1.2 + r * 0.2,
                    -0.21
                );
                chairGroup.add(btn);
            }
        }

        // Cabriole chair legs
        const chairLegProfile = [
            new THREE.Vector2(0, 0),
            new THREE.Vector2(0.035, 0),
            new THREE.Vector2(0.04, 0.03),
            new THREE.Vector2(0.025, 0.12),
            new THREE.Vector2(0.03, 0.35),
            new THREE.Vector2(0.035, 0.40),
            new THREE.Vector2(0.03, 0.48),
            new THREE.Vector2(0.025, 0.65),
            new THREE.Vector2(0.03, 0.78),
            new THREE.Vector2(0.025, 0.82),
            new THREE.Vector2(0, 0.82),
        ];
        const chairLegGeo = new THREE.LatheGeometry(chairLegProfile, 8);
        [[-0.28, 0, -0.24], [0.28, 0, -0.24], [-0.28, 0, 0.24], [0.28, 0, 0.24]].forEach(pos => {
            const leg = new THREE.Mesh(chairLegGeo, frameMat);
            leg.position.set(...pos);
            chairGroup.add(leg);
        });

        // Armrests — curved wood with padded top
        [-0.35, 0.35].forEach(ax => {
            // Arm support post
            const armPost = new THREE.Mesh(
                new THREE.CylinderGeometry(0.02, 0.025, 0.55, 6),
                frameMat
            );
            armPost.position.set(ax, 1.1, 0.05);
            chairGroup.add(armPost);

            // Arm rest pad
            const armPad = new THREE.Mesh(
                new THREE.BoxGeometry(0.06, 0.04, 0.38),
                upholsteryMat
            );
            armPad.position.set(ax, 1.15, -0.05);
            chairGroup.add(armPad);

            // Gold arm tip
            const armTip = new THREE.Mesh(
                new THREE.SphereGeometry(0.025, 6, 6),
                goldAccentMat
            );
            armTip.position.set(ax, 1.16, 0.15);
            chairGroup.add(armTip);
        });

        roomGroup.add(chairGroup);
    }

    // ═══ CHESS BOARD ═══
    function createBoard() {
        boardGroup.clear();
        const theme = THEMES[boardConfig.theme] || THEMES.walnut;
        const TABLE_Y = 1.8;
        const SQUARE_SIZE = 0.22;
        const BOARD_SIZE = SQUARE_SIZE * 8;
        const FRAME_INNER = BOARD_SIZE + 0.04;
        const FRAME_OUTER = BOARD_SIZE + 0.18;

        // Board base — thick platform (V1: high metalness for luxurious feel)
        const baseGeo = new THREE.BoxGeometry(FRAME_OUTER + 0.04, 0.10, FRAME_OUTER + 0.04);
        const baseMat = new THREE.MeshStandardMaterial({
            color: theme.border,
            roughness: 0.25,
            metalness: 0.6,
            envMap,
            envMapIntensity: 0.8,
        });
        const base = new THREE.Mesh(baseGeo, baseMat);
        base.position.set(0, TABLE_Y + 0.05, 0);
        base.castShadow = true;
        base.receiveShadow = true;
        boardGroup.add(base);

        // Ornamental border frame — V1-style accent with emissive glow
        const frameMat = new THREE.MeshStandardMaterial({
            color: theme.borderAccent || 0x8B5E3C,
            roughness: 0.15,
            metalness: 0.8,
            emissive: theme.borderAccent || 0x8B5E3C,
            emissiveIntensity: 0.05,
            envMap,
            envMapIntensity: 1.2,
        });
        const frameH = 0.025;
        const frameBand = (FRAME_OUTER - FRAME_INNER) / 2;
        // Four frame edges
        [
            { w: FRAME_OUTER, d: frameBand, x: 0, z: (FRAME_INNER + FRAME_OUTER) / 4 },
            { w: FRAME_OUTER, d: frameBand, x: 0, z: -(FRAME_INNER + FRAME_OUTER) / 4 },
            { w: frameBand, d: FRAME_INNER, x: (FRAME_INNER + FRAME_OUTER) / 4, z: 0 },
            { w: frameBand, d: FRAME_INNER, x: -(FRAME_INNER + FRAME_OUTER) / 4, z: 0 },
        ].forEach(f => {
            const edge = new THREE.Mesh(new THREE.BoxGeometry(f.w, frameH, f.d), frameMat);
            edge.position.set(f.x, TABLE_Y + 0.11, f.z);
            edge.receiveShadow = true;
            edge.castShadow = true;
            boardGroup.add(edge);
        });

        // Corner ornaments — polished cylinders at frame corners
        for (let cx = -1; cx <= 1; cx += 2) {
            for (let cz = -1; cz <= 1; cz += 2) {
                const corner = new THREE.Mesh(
                    new THREE.CylinderGeometry(0.02, 0.02, frameH + 0.01, 12),
                    frameMat
                );
                corner.position.set(cx * FRAME_OUTER / 2, TABLE_Y + 0.11, cz * FRAME_OUTER / 2);
                corner.castShadow = true;
                boardGroup.add(corner);
            }
        }

        // Edge glow — subtle transparent halo around board (V1 feature)
        const edgeGlowGeo = new THREE.BoxGeometry(FRAME_OUTER + 0.06, 0.08, FRAME_OUTER + 0.06);
        const edgeGlowMat = new THREE.MeshBasicMaterial({
            color: theme.accentGlow || 0xD4A843,
            transparent: true,
            opacity: 0.06,
        });
        const edgeGlow = new THREE.Mesh(edgeGlowGeo, edgeGlowMat);
        edgeGlow.position.set(0, TABLE_Y + 0.08, 0);
        boardGroup.add(edgeGlow);

        // Individual squares
        const squareGeo = new THREE.BoxGeometry(SQUARE_SIZE - 0.002, 0.012, SQUARE_SIZE - 0.002);
        for (let row = 0; row < 8; row++) {
            for (let col = 0; col < 8; col++) {
                const isLight = (row + col) % 2 === 0;
                const mat = new THREE.MeshStandardMaterial({
                    color: isLight ? theme.light : theme.dark,
                    roughness: isLight ? (theme.lightRough || 0.4) : (theme.darkRough || 0.3),
                    metalness: isLight ? (theme.lightMetal || 0.0) : (theme.darkMetal || 0.0),
                    envMap,
                    envMapIntensity: 0.6,
                });
                const square = new THREE.Mesh(squareGeo, mat);
                const x = (col - 3.5) * SQUARE_SIZE;
                const z = (row - 3.5) * SQUARE_SIZE;
                square.position.set(x, TABLE_Y + 0.126, z);
                square.receiveShadow = true;
                square.userData = {
                    type: 'square',
                    algebraic: String.fromCharCode(97 + col) + (row + 1),
                    col, row,
                };
                boardGroup.add(square);
            }
        }

        // Coordinate labels
        if (boardConfig.showCoords) {
            const labelColor = 0xAA9977;
            for (let i = 0; i < 8; i++) {
                addTextSprite(String.fromCharCode(97 + i), labelColor,
                    (i - 3.5) * SQUARE_SIZE, TABLE_Y + 0.08, 4.2 * SQUARE_SIZE, 0.08);
                addTextSprite(String(i + 1), labelColor,
                    -4.2 * SQUARE_SIZE, TABLE_Y + 0.08, (i - 3.5) * SQUARE_SIZE, 0.08);
            }
        }
    }

    function addTextSprite(text, color, x, y, z, size) {
        const canvas = document.createElement('canvas');
        canvas.width = 64;
        canvas.height = 64;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#' + new THREE.Color(color).getHexString();
        ctx.font = 'bold 48px serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(text, 32, 32);
        const tex = new THREE.CanvasTexture(canvas);
        const mat = new THREE.SpriteMaterial({ map: tex, transparent: true, opacity: 0.6 });
        const sprite = new THREE.Sprite(mat);
        sprite.position.set(x, y, z);
        sprite.scale.set(size, size, 1);
        boardGroup.add(sprite);
    }

    // ═══ PIECE GEOMETRY — Championship Staunton proportions ═══
    // Inspired by Jaques original 1849, FIDE tournament standard, Fischer-era DGT sets
    // Height ratios: King(1.0) Queen(0.88) Bishop(0.75) Knight(0.62) Rook(0.56) Pawn(0.45)
    // Base width ~60% of SQUARE_SIZE for visual stability

    function createPieceGeometry(type) {
        if (geometryCache[type]) return geometryCache[type];

        // All [radius, height] — profiles designed at 1:1 scale
        // Pieces will be scaled by SQUARE_SIZE ratio in the map step below
        const profiles = {
            // ─── PAWN: Wide base → tapered stem → collar → ball top ───
            pawn: [
                [0, 0],
                [0.13, 0], [0.14, 0.005], [0.14, 0.025], [0.13, 0.035],  // Flat base lip
                [0.11, 0.04], [0.08, 0.05],                                // Base curve in
                [0.065, 0.06], [0.055, 0.10], [0.055, 0.15],              // Stem
                [0.065, 0.16], [0.07, 0.165], [0.07, 0.175], [0.06, 0.185], // Collar disk
                [0.045, 0.20], [0.035, 0.22],                              // Neck
                [0.05, 0.24], [0.06, 0.27], [0.06, 0.29],                 // Ball
                [0.05, 0.31], [0.03, 0.33], [0, 0.34],                    // Ball top
            ],

            // ─── ROOK: Wide base → column → collar → flared rampart with battlements ───
            rook: [
                [0, 0],
                [0.14, 0], [0.15, 0.005], [0.15, 0.03], [0.14, 0.04],    // Base lip
                [0.115, 0.05], [0.085, 0.06],                              // Base curve
                [0.07, 0.07], [0.065, 0.12], [0.065, 0.22],               // Column
                [0.075, 0.23], [0.08, 0.235], [0.08, 0.245], [0.07, 0.255], // Collar
                [0.065, 0.26], [0.07, 0.28], [0.08, 0.30],                // Flare outward
                [0.10, 0.32], [0.11, 0.34], [0.11, 0.38],                 // Rampart wall
                [0.12, 0.38], [0.12, 0.42],                                // Battlement merlon
                [0.085, 0.42], [0.085, 0.39],                              // Crenel gap
                [0.065, 0.39], [0.065, 0.42],                              // Inner merlon
                [0, 0.42],                                                  // Open top
            ],

            // ─── KNIGHT: Base pedestal only (horse head added via ExtrudeGeometry in createPieceMesh) ───
            knight: [
                [0, 0],
                [0.14, 0], [0.15, 0.005], [0.15, 0.03], [0.14, 0.04],    // Base lip
                [0.115, 0.05], [0.085, 0.06],                              // Base curve
                [0.07, 0.07], [0.065, 0.12], [0.065, 0.20],               // Column
                [0.075, 0.21], [0.08, 0.215], [0.08, 0.225], [0.07, 0.235], // Collar
                [0, 0.235],                                                 // Close off at collar top
            ],

            // ─── BISHOP: Base → column → collar → mitre dome → finial knob ───
            bishop: [
                [0, 0],
                [0.14, 0], [0.15, 0.005], [0.15, 0.03], [0.14, 0.04],    // Base lip
                [0.115, 0.05], [0.085, 0.06],                              // Base curve
                [0.07, 0.07], [0.065, 0.12], [0.065, 0.24],               // Longer column
                [0.075, 0.25], [0.08, 0.255], [0.08, 0.265], [0.07, 0.275], // Collar
                [0.055, 0.29], [0.04, 0.32],                               // Neck
                [0.06, 0.35], [0.075, 0.39], [0.08, 0.42],                // Mitre swell
                [0.07, 0.46], [0.05, 0.50], [0.03, 0.53],                 // Mitre taper
                [0.015, 0.545], [0.02, 0.55], [0.015, 0.565],             // Finial knob
                [0, 0.57],                                                  // Point
            ],

            // ─── QUEEN: Tall base → long column → collar → coronet → monde ball ───
            queen: [
                [0, 0],
                [0.15, 0], [0.16, 0.005], [0.16, 0.035], [0.15, 0.045],   // Wide base lip
                [0.12, 0.055], [0.09, 0.065],                              // Base curve
                [0.075, 0.075], [0.07, 0.14], [0.07, 0.28],               // Long column
                [0.08, 0.29], [0.085, 0.295], [0.085, 0.305], [0.075, 0.315], // Collar
                [0.06, 0.33], [0.045, 0.36],                               // Neck
                [0.065, 0.39], [0.08, 0.43], [0.085, 0.46],               // Crown swell
                [0.075, 0.49], [0.055, 0.52],                              // Crown taper
                [0.065, 0.525], [0.045, 0.545], [0.055, 0.55],            // Coronet points
                [0.035, 0.57], [0.02, 0.58],                               // Stem to monde
                [0.035, 0.59], [0.04, 0.60], [0.04, 0.62],                // Monde ball
                [0.03, 0.635], [0.015, 0.645], [0, 0.65],                 // Ball top
            ],

            // ─── KING: Widest base → tallest column → crown → cross pattée ───
            king: [
                [0, 0],
                [0.16, 0], [0.17, 0.005], [0.17, 0.035], [0.16, 0.045],   // Widest base lip
                [0.13, 0.055], [0.095, 0.065],                             // Base curve
                [0.08, 0.075], [0.075, 0.14], [0.075, 0.32],              // Longest column
                [0.085, 0.33], [0.09, 0.335], [0.09, 0.345], [0.08, 0.355], // Collar
                [0.065, 0.37], [0.05, 0.40],                               // Neck
                [0.07, 0.43], [0.085, 0.47], [0.09, 0.50],                // Crown swell
                [0.08, 0.53], [0.06, 0.56],                                // Crown taper
                [0.07, 0.565], [0.05, 0.585], [0.06, 0.59],               // Crown points
                [0.04, 0.61], [0.025, 0.63],                               // Cross base stem
                [0.025, 0.66], [0.05, 0.66], [0.05, 0.69],                // Cross horizontal arm
                [0.025, 0.69], [0.025, 0.74], [0.012, 0.75], [0, 0.755], // Cross vertical top
            ],
        };

        const profile = profiles[type] || profiles.pawn;
        // Scale pieces relative to board square size. Profiles authored at ~0.22 scale.
        const scaleFactor = SQUARE_SIZE / 0.22;
        const points = profile.map(([x, y]) => new THREE.Vector2(x * scaleFactor, y * scaleFactor));
        const geo = new THREE.LatheGeometry(points, 32);
        geo.computeVertexNormals();
        geometryCache[type] = geo;
        return geo;
    }

    function createPieceMesh(type, color) {
        const geo = createPieceGeometry(type);
        const isWhite = color === 'w';
        const theme = THEMES[boardConfig.theme] || THEMES.walnut;

        const mainMat = new THREE.MeshPhysicalMaterial({
            color: isWhite ? (theme.whitePiece || 0xFAF0E0) : (theme.blackPiece || 0x1A1A1A),
            roughness: isWhite ? 0.28 : 0.22,
            metalness: isWhite ? 0.06 : 0.2,
            emissive: isWhite ? 0x080804 : 0x080404,
            emissiveIntensity: 0.05,
            envMap,
            envMapIntensity: isWhite ? 0.6 : 0.7,
            clearcoat: isWhite ? 0.6 : 0.8,
            clearcoatRoughness: isWhite ? 0.15 : 0.1,
            reflectivity: isWhite ? 0.5 : 0.7,
        });

        const accentMat = new THREE.MeshPhysicalMaterial({
            color: isWhite ? (theme.whiteAccent || 0xD4C8A0) : (theme.blackAccent || 0x1A1008),
            roughness: 0.15,
            metalness: 0.75,
            emissive: isWhite ? 0x181400 : 0x0A0010,
            emissiveIntensity: 0.06,
            envMap,
            envMapIntensity: 0.9,
            clearcoat: 0.4,
            clearcoatRoughness: 0.1,
        });

        const feltMat = new THREE.MeshStandardMaterial({
            color: theme.feltColor || 0x2D5A1A,
            roughness: 0.95,
            metalness: 0,
        });

        const group = new THREE.Group();
        const mainMesh = new THREE.Mesh(geo, mainMat);
        mainMesh.castShadow = true;
        mainMesh.receiveShadow = true;
        group.add(mainMesh);

        // Felt bottom (all pieces)
        const feltGeo = new THREE.CircleGeometry(SQUARE_SIZE * 0.35, 16);
        feltGeo.rotateX(-Math.PI / 2);
        const felt = new THREE.Mesh(feltGeo, feltMat);
        felt.position.y = 0.002;
        group.add(felt);

        // Piece-specific accent decorations — proportioned to match piece body radii
        switch (type) {
            case 'pawn': {
                // Collar ring — sits on the collar disk at height 0.175, body radius 0.07
                const ring = new THREE.Mesh(new THREE.TorusGeometry(0.068, 0.004, 6, 20), accentMat);
                ring.position.y = 0.18;
                ring.rotation.x = Math.PI / 2;
                group.add(ring);
                break;
            }
            case 'rook': {
                // Accent band at collar — height 0.235, body radius 0.08
                const band = new THREE.Mesh(new THREE.TorusGeometry(0.078, 0.004, 6, 20), accentMat);
                band.position.y = 0.24;
                band.rotation.x = Math.PI / 2;
                group.add(band);
                // Battlement columns on top (4 merlons) — ported from V1
                for (let i = 0; i < 4; i++) {
                    const merlon = new THREE.Mesh(
                        new THREE.BoxGeometry(0.040, 0.035, 0.030),
                        mainMat
                    );
                    const angle = (i / 4) * Math.PI * 2 + Math.PI / 4;
                    merlon.position.set(
                        Math.sin(angle) * 0.082,
                        0.435,
                        Math.cos(angle) * 0.082
                    );
                    merlon.rotation.y = angle;
                    merlon.castShadow = true;
                    group.add(merlon);
                }
                break;
            }
            case 'knight': {
                // ── Horse head — sculpted 2D bezier silhouette extruded to 3D ──
                // Shape coordinates: refined Staunton horse profile (from V1)
                const headShape = new THREE.Shape();
                headShape.moveTo(-0.12, 0);
                // Throat curve
                headShape.bezierCurveTo(-0.15, 0.04, -0.17, 0.12, -0.16, 0.20);
                // Throat to jaw
                headShape.bezierCurveTo(-0.15, 0.28, -0.12, 0.34, -0.10, 0.38);
                // Lower jaw / mouth
                headShape.bezierCurveTo(-0.07, 0.42, -0.02, 0.46, 0.02, 0.50);
                // Nose / muzzle
                headShape.bezierCurveTo(0.06, 0.54, 0.10, 0.56, 0.12, 0.58);
                // Nostril bump
                headShape.bezierCurveTo(0.13, 0.60, 0.13, 0.62, 0.12, 0.64);
                // Bridge of nose to forehead
                headShape.bezierCurveTo(0.10, 0.66, 0.08, 0.70, 0.06, 0.74);
                // Forehead curve
                headShape.bezierCurveTo(0.04, 0.78, 0.02, 0.82, 0.01, 0.85);
                // Top of head / ear
                headShape.bezierCurveTo(-0.01, 0.88, -0.02, 0.92, -0.01, 0.96);
                // Ear tip
                headShape.bezierCurveTo(0.00, 0.99, 0.01, 1.01, 0.00, 1.02);
                // Back of ear to poll/neck
                headShape.bezierCurveTo(-0.02, 1.01, -0.04, 0.98, -0.06, 0.94);
                // Back of head / mane line
                headShape.bezierCurveTo(-0.08, 0.88, -0.10, 0.78, -0.12, 0.68);
                // Neck sweeping down
                headShape.bezierCurveTo(-0.14, 0.56, -0.16, 0.42, -0.17, 0.30);
                // Back to throat
                headShape.bezierCurveTo(-0.17, 0.20, -0.16, 0.10, -0.14, 0.04);
                headShape.lineTo(-0.12, 0);

                const hs = 0.25; // head scale — maps raw 1.02-unit shape to ~0.255 world height
                const headGeo = new THREE.ExtrudeGeometry(headShape, {
                    depth: 0.055,
                    bevelEnabled: true,
                    bevelThickness: 0.006,
                    bevelSize: 0.005,
                    bevelSegments: 4
                });
                headGeo.scale(hs, hs, 1);
                const headMesh = new THREE.Mesh(headGeo, mainMat);
                headMesh.position.set(0, 0.22, -0.028);
                headMesh.castShadow = true;
                group.add(headMesh);

                // Eyes — small accent spheres on both sides of the head
                const eyeGeo = new THREE.SphereGeometry(0.006, 8, 8);
                const eye1 = new THREE.Mesh(eyeGeo, accentMat);
                eye1.position.set(0.012, 0.435, 0.008);
                group.add(eye1);
                const eye2 = eye1.clone();
                eye2.position.z = -0.063;
                group.add(eye2);

                // Mane ridge — small bumps along the back of the neck
                for (let mi = 0; mi < 5; mi++) {
                    const maneGeo = new THREE.SphereGeometry(0.008 - mi * 0.001, 5, 5);
                    const maneSeg = new THREE.Mesh(maneGeo, accentMat);
                    maneSeg.position.set(
                        -0.028 - mi * 0.003,
                        0.45 - mi * 0.025,
                        -0.028
                    );
                    maneSeg.scale.set(0.7, 1.1, 1.6);
                    group.add(maneSeg);
                }

                // Nostrils — tiny dark dots
                const nostrilMat = new THREE.MeshStandardMaterial({ color: 0x111111, roughness: 0.9 });
                const nostrilGeo = new THREE.SphereGeometry(0.003, 5, 5);
                const n1 = new THREE.Mesh(nostrilGeo, nostrilMat);
                n1.position.set(0.038, 0.365, 0.004);
                group.add(n1);
                const n2 = n1.clone();
                n2.position.z = -0.059;
                group.add(n2);

                // Collar ring at base-head join — body radius 0.075 at collar
                const collar = new THREE.Mesh(new THREE.TorusGeometry(0.073, 0.004, 6, 20), accentMat);
                collar.position.y = 0.225;
                collar.rotation.x = Math.PI / 2;
                group.add(collar);
                break;
            }
            case 'bishop': {
                // Collar ring — height 0.265, body radius 0.075
                const bCollar = new THREE.Mesh(new THREE.TorusGeometry(0.073, 0.004, 6, 20), accentMat);
                bCollar.position.y = 0.27;
                bCollar.rotation.x = Math.PI / 2;
                group.add(bCollar);
                // Mitre slot (diagonal cut) — proportional to bishop body
                const slot = new THREE.Mesh(new THREE.BoxGeometry(0.005, 0.045, 0.06), accentMat);
                slot.position.set(0, 0.48, 0);
                slot.rotation.z = 0.5;
                group.add(slot);
                // Finial ball on tip
                const tip = new THREE.Mesh(new THREE.SphereGeometry(0.012, 8, 8), accentMat);
                tip.position.y = 0.565;
                group.add(tip);
                break;
            }
            case 'queen': {
                // Collar ring — height 0.30, body radius 0.085
                const qCollar = new THREE.Mesh(new THREE.TorusGeometry(0.083, 0.004, 6, 20), accentMat);
                qCollar.position.y = 0.305;
                qCollar.rotation.x = Math.PI / 2;
                group.add(qCollar);
                // Crown rim torus — height 0.525, body radius ~0.06
                const crown = new THREE.Mesh(new THREE.TorusGeometry(0.058, 0.003, 6, 20), accentMat);
                crown.position.y = 0.525;
                crown.rotation.x = Math.PI / 2;
                group.add(crown);
                // Crown points — 8 small cones around the coronet
                for (let i = 0; i < 8; i++) {
                    const point = new THREE.Mesh(new THREE.ConeGeometry(0.007, 0.022, 5), accentMat);
                    const angle = (i / 8) * Math.PI * 2;
                    point.position.set(
                        Math.sin(angle) * 0.050,
                        0.545,
                        Math.cos(angle) * 0.050
                    );
                    group.add(point);
                }
                break;
            }
            case 'king': {
                // Collar ring — height 0.345, body radius 0.085
                const kCollar = new THREE.Mesh(new THREE.TorusGeometry(0.083, 0.004, 6, 20), accentMat);
                kCollar.position.y = 0.345;
                kCollar.rotation.x = Math.PI / 2;
                group.add(kCollar);
                // Crown band — height 0.57, body radius ~0.065
                const kBand = new THREE.Mesh(new THREE.TorusGeometry(0.063, 0.003, 6, 20), accentMat);
                kBand.position.y = 0.57;
                kBand.rotation.x = Math.PI / 2;
                group.add(kBand);
                // Cross pattée on top — vertical + horizontal bars (proportional to crown)
                const crossV = new THREE.Mesh(new THREE.BoxGeometry(0.014, 0.065, 0.014), accentMat);
                crossV.position.y = 0.72;
                crossV.castShadow = true;
                group.add(crossV);
                const crossH = new THREE.Mesh(new THREE.BoxGeometry(0.050, 0.012, 0.012), accentMat);
                crossH.position.y = 0.725;
                crossH.castShadow = true;
                group.add(crossH);
                break;
            }
        }

        return group;
    }

    // ═══ PIECE POSITIONING ═══
    const TABLE_Y = 1.8;
    const SQUARE_SIZE = 0.22;

    function positionToWorld(algebraic) {
        const col = algebraic.charCodeAt(0) - 97;
        const row = parseInt(algebraic[1]) - 1;
        return new THREE.Vector3(
            (col - 3.5) * SQUARE_SIZE,
            TABLE_Y + 0.13,
            (row - 3.5) * SQUARE_SIZE
        );
    }

    function setPieces(boardState) {
        piecesGroup.clear();
        const typeMap = { p: 'pawn', r: 'rook', n: 'knight', b: 'bishop', q: 'queen', k: 'king' };

        boardState.forEach(sq => {
            if (!sq.piece) return;
            const mesh = createPieceMesh(typeMap[sq.piece.type], sq.piece.color);
            const pos = positionToWorld(sq.square);
            mesh.position.copy(pos);
            if (sq.piece.color === 'b') mesh.rotation.y = Math.PI;
            mesh.userData = {
                type: 'piece',
                pieceType: sq.piece.type,
                color: sq.piece.color,
                square: sq.square,
            };
            piecesGroup.add(mesh);
        });
    }

    // ═══ ANIMATION ═══
    function animateMove(from, to, callback) {
        const piece = piecesGroup.children.find(p => p.userData.square === from);
        if (!piece) { if (callback) callback(); return; }

        const startPos = piece.position.clone();
        const endPos = positionToWorld(to);
        const arcHeight = 0.15; // Subtle arc — realistic lift
        const duration = 500;
        const startTime = Date.now();

        function step() {
            const t = Math.min(1, (Date.now() - startTime) / duration);
            const ease = t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
            piece.position.lerpVectors(startPos, endPos, ease);
            piece.position.y = startPos.y + Math.sin(ease * Math.PI) * arcHeight;

            if (t < 1) {
                requestAnimationFrame(step);
            } else {
                piece.position.copy(endPos);
                piece.userData.square = to;
                if (callback) callback();
            }
        }
        step();
    }

    function removePiece(square) {
        const piece = piecesGroup.children.find(p => p.userData.square === square);
        if (piece) {
            spawnCaptureEffect(piece.position.clone(), piece.userData.color);
            piecesGroup.remove(piece);
            // Handle Group (multi-mesh) or single Mesh disposal
            if (piece.traverse) {
                piece.traverse(child => {
                    if (child.geometry) child.geometry.dispose();
                    if (child.material) child.material.dispose();
                });
            } else if (piece.geometry) {
                piece.geometry.dispose();
            }
        }
    }

    // ═══ HOVER INDICATOR ═══
    function createHoverIndicator() {
        const geo = new THREE.RingGeometry(SQUARE_SIZE * 0.35, SQUARE_SIZE * 0.42, 24);
        geo.rotateX(-Math.PI / 2);
        const mat = new THREE.MeshBasicMaterial({
            color: 0xFFCC44,
            transparent: true,
            opacity: 0,
        });
        hoverIndicator = new THREE.Mesh(geo, mat);
        hoverIndicator.position.y = TABLE_Y + 0.135;
        effectsGroup.add(hoverIndicator);
    }

    // ═══ HIGHLIGHTS ═══
    function highlightSquares(squares, color = 0x44FF44) {
        effectsGroup.children.filter(c => c.userData.isHighlight).forEach(c => {
            effectsGroup.remove(c);
            c.geometry.dispose();
            c.material.dispose();
        });

        squares.forEach(sq => {
            const geo = new THREE.RingGeometry(SQUARE_SIZE * 0.25, SQUARE_SIZE * 0.38, 20);
            geo.rotateX(-Math.PI / 2);
            const mat = new THREE.MeshBasicMaterial({ color, transparent: true, opacity: 0.5 });
            const ring = new THREE.Mesh(geo, mat);
            const pos = positionToWorld(sq);
            ring.position.set(pos.x, TABLE_Y + 0.135, pos.z);
            ring.userData.isHighlight = true;
            effectsGroup.add(ring);
        });
    }

    function clearHighlights() {
        const toRemove = effectsGroup.children.filter(c => c.userData.isHighlight);
        toRemove.forEach(c => { effectsGroup.remove(c); c.geometry.dispose(); c.material.dispose(); });
    }

    // ═══ EFFECTS ═══
    function spawnCaptureEffect(position, capturedColor) {
        // Dramatic burst — V1-style with warm colors & additive blending
        const count = 30;
        const geo = new THREE.BufferGeometry();
        const positions = new Float32Array(count * 3);
        const colors = new Float32Array(count * 3);
        const velocities = [];
        const burstColors = [0xFF4444, 0xFF8800, 0xFFCC00, 0xFFDD88, 0xFFAA44];
        for (let i = 0; i < count; i++) {
            positions[i * 3] = position.x;
            positions[i * 3 + 1] = position.y + 0.05;
            positions[i * 3 + 2] = position.z;
            velocities.push(new THREE.Vector3(
                (Math.random() - 0.5) * 1.2,
                Math.random() * 0.8 + 0.3,
                (Math.random() - 0.5) * 1.2
            ));
            const c = new THREE.Color(burstColors[Math.floor(Math.random() * burstColors.length)]);
            colors[i * 3] = c.r;
            colors[i * 3 + 1] = c.g;
            colors[i * 3 + 2] = c.b;
        }
        geo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        geo.setAttribute('color', new THREE.BufferAttribute(colors, 3));
        const mat = new THREE.PointsMaterial({
            size: 0.04,
            vertexColors: true,
            transparent: true,
            opacity: 1,
            blending: THREE.AdditiveBlending,
            depthWrite: false,
        });
        const particles = new THREE.Points(geo, mat);
        particles.userData.velocities = velocities;
        particles.userData.startTime = Date.now();
        particles.userData.isParticle = true;
        effectsGroup.add(particles);
    }

    // Victory confetti — V1-style fireworks
    function spawnVictoryParticles() {
        const count = 150;
        const geo = new THREE.BufferGeometry();
        const positions = new Float32Array(count * 3);
        const colors = new Float32Array(count * 3);
        const velocities = [];
        const TABLE_Y = 1.8;
        const cheerColors = [0xFFD700, 0xFF4444, 0x44FF44, 0x4488FF, 0xFF44FF, 0xFF8800];
        for (let i = 0; i < count; i++) {
            positions[i * 3] = (Math.random() - 0.5) * 0.5;
            positions[i * 3 + 1] = TABLE_Y + 0.5;
            positions[i * 3 + 2] = (Math.random() - 0.5) * 0.5;
            velocities.push(new THREE.Vector3(
                (Math.random() - 0.5) * 2.0,
                Math.random() * 2.5 + 1.0,
                (Math.random() - 0.5) * 2.0
            ));
            const c = new THREE.Color(cheerColors[Math.floor(Math.random() * cheerColors.length)]);
            colors[i * 3] = c.r;
            colors[i * 3 + 1] = c.g;
            colors[i * 3 + 2] = c.b;
        }
        geo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        geo.setAttribute('color', new THREE.BufferAttribute(colors, 3));
        const mat = new THREE.PointsMaterial({
            size: 0.05,
            vertexColors: true,
            transparent: true,
            opacity: 1,
            blending: THREE.AdditiveBlending,
            depthWrite: false,
        });
        const particles = new THREE.Points(geo, mat);
        particles.userData.velocities = velocities;
        particles.userData.startTime = Date.now();
        particles.userData.isVictoryParticle = true;
        effectsGroup.add(particles);
    }

    // Ambient floating particles — tropical fireflies/dust motes
    let ambientParticles = null;
    function createAmbientParticles() {
        const count = 300;
        const geo = new THREE.BufferGeometry();
        const positions = new Float32Array(count * 3);
        const colors = new Float32Array(count * 3);
        for (let i = 0; i < count; i++) {
            positions[i * 3] = (Math.random() - 0.5) * 20;
            positions[i * 3 + 1] = Math.random() * 5 + 0.5;
            positions[i * 3 + 2] = (Math.random() - 0.5) * 20;
            const warmth = Math.random();
            const c = warmth > 0.5
                ? new THREE.Color(0xFFDD88) // golden dust
                : new THREE.Color(0x88CCFF); // cool sparkle
            colors[i * 3] = c.r * (0.4 + Math.random() * 0.6);
            colors[i * 3 + 1] = c.g * (0.4 + Math.random() * 0.6);
            colors[i * 3 + 2] = c.b * (0.4 + Math.random() * 0.6);
        }
        geo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        geo.setAttribute('color', new THREE.BufferAttribute(colors, 3));
        const mat = new THREE.PointsMaterial({
            size: 0.03,
            vertexColors: true,
            transparent: true,
            opacity: 0.5,
            blending: THREE.AdditiveBlending,
            depthWrite: false,
        });
        ambientParticles = new THREE.Points(geo, mat);
        scene.add(ambientParticles);
    }

    function spawnCheckEffect(kingSquare) {
        const pos = positionToWorld(kingSquare);
        const geo = new THREE.RingGeometry(SQUARE_SIZE * 0.3, SQUARE_SIZE * 0.42, 24);
        geo.rotateX(-Math.PI / 2);
        const mat = new THREE.MeshBasicMaterial({ color: 0xFF2200, transparent: true, opacity: 0.7 });
        const ring = new THREE.Mesh(geo, mat);
        ring.position.set(pos.x, TABLE_Y + 0.14, pos.z);
        ring.userData.isCheckEffect = true;
        ring.userData.startTime = Date.now();
        effectsGroup.add(ring);
    }

    // ═══ EFFECTS UPDATE ═══
    function updateEffects(delta) {
        const now = Date.now();
        const toRemove = [];

        effectsGroup.children.forEach(child => {
            // Capture burst particles
            if (child.userData.isParticle) {
                const elapsed = (now - child.userData.startTime) / 1000;
                if (elapsed > 1.5) { toRemove.push(child); return; }
                const positions = child.geometry.attributes.position.array;
                child.userData.velocities.forEach((v, i) => {
                    positions[i * 3] += v.x * delta;
                    positions[i * 3 + 1] += (v.y - 3.0 * elapsed) * delta;
                    positions[i * 3 + 2] += v.z * delta;
                });
                child.geometry.attributes.position.needsUpdate = true;
                child.material.opacity = Math.max(0, 1 - elapsed / 1.5);
            }
            // Victory firework particles
            if (child.userData.isVictoryParticle) {
                const elapsed = (now - child.userData.startTime) / 1000;
                if (elapsed > 3) { toRemove.push(child); return; }
                const positions = child.geometry.attributes.position.array;
                child.userData.velocities.forEach((v, i) => {
                    positions[i * 3] += v.x * delta;
                    positions[i * 3 + 1] += (v.y - 4.0 * elapsed) * delta;
                    positions[i * 3 + 2] += v.z * delta;
                });
                child.geometry.attributes.position.needsUpdate = true;
                child.material.opacity = Math.max(0, 1 - elapsed / 3);
            }
            // Check effect (pulsing red ring)
            if (child.userData.isCheckEffect) {
                const elapsed = (now - child.userData.startTime) / 1000;
                if (elapsed > 3) { toRemove.push(child); return; }
                child.material.opacity = 0.4 + Math.sin(elapsed * 6) * 0.3;
                child.scale.setScalar(1 + Math.sin(elapsed * 4) * 0.05);
            }
        });

        toRemove.forEach(c => {
            effectsGroup.remove(c);
            if (c.geometry) c.geometry.dispose();
            if (c.material) c.material.dispose();
        });

        // Ambient floating particles — gentle drift
        if (ambientParticles) {
            ambientParticles.rotation.y += 0.0002;
            const positions = ambientParticles.geometry.attributes.position.array;
            const time = now * 0.001;
            for (let i = 0; i < positions.length; i += 3) {
                positions[i + 1] += Math.sin(time + i) * 0.0008;
            }
            ambientParticles.geometry.attributes.position.needsUpdate = true;
        }
    }

    // ═══ AMBIENT ANIMATIONS ═══
    function updateAmbientAnimations(time) {
        // Ocean waves — gentle vertex displacement + scrolling normal map
        roomGroup.traverse(obj => {
            if (obj.userData.isOcean && obj.geometry) {
                const posAttr = obj.geometry.attributes.position;
                for (let i = 0; i < posAttr.count; i++) {
                    const x = posAttr.getX(i);
                    const z = posAttr.getZ(i);
                    const wave = Math.sin(x * 0.5 + time * 0.8) * 0.15
                               + Math.sin(z * 0.3 + time * 0.6) * 0.1
                               + Math.sin((x + z) * 0.2 + time * 1.2) * 0.05;
                    posAttr.setY(i, wave);
                }
                posAttr.needsUpdate = true;
                // Scroll ocean normal map for realistic water movement
                if (obj.material && obj.material.normalMap) {
                    obj.material.normalMap.offset.x = time * 0.008;
                    obj.material.normalMap.offset.y = time * 0.005;
                }
            }
            // Curtain gentle sway
            if (obj.userData.isCurtain) {
                const posAttr = obj.geometry.attributes.position;
                for (let i = 0; i < posAttr.count; i++) {
                    const y = posAttr.getY(i);
                    const sway = Math.sin(time * 1.5 + y * 2) * 0.02;
                    posAttr.setZ(i, sway + Math.sin(y * 3) * 0.06 + Math.sin(y * 7) * 0.02);
                }
                posAttr.needsUpdate = true;
                obj.geometry.computeVertexNormals();
            }
            // Palm frond gentle oscillation
            if (obj.userData.isFrond) {
                obj.rotation.x = obj.userData.baseRotX + Math.sin(time * 0.8 + obj.rotation.y * 3) * 0.05;
            }
        });

        // Candle flicker on table
        scene.traverse(obj => {
            if (obj.isLight && obj.userData.isCandle) {
                obj.intensity = obj.userData.baseIntensity * (0.9 + Math.sin(time * 8) * 0.1 + Math.random() * 0.05);
            }
        });

        // Hover indicator pulse
        if (hoverIndicator && hoverIndicator.material.opacity > 0) {
            hoverIndicator.material.opacity = 0.3 + Math.sin(time * 4) * 0.15;
        }
    }

    // ═══ EVENTS ═══
    function onResize() {
        const container = renderer.domElement.parentElement;
        const w = container.clientWidth, h = container.clientHeight;
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
        renderer.setSize(w, h);
        renderer.setPixelRatio(quality.dpr);
        if (composer) {
            composer.setSize(w, h);
            // Update FXAA resolution uniforms for crisp edges at any resolution
            const fxaaPass = composer.passes.find(p => p.material && p.material.uniforms && p.material.uniforms['resolution']);
            if (fxaaPass) {
                const pr = renderer.getPixelRatio();
                fxaaPass.material.uniforms['resolution'].value.set(1 / (w * pr), 1 / (h * pr));
            }
        }
    }

    function onPointerMove(e) {
        const rect = renderer.domElement.getBoundingClientRect();
        mouse.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
        mouse.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;

        // Hover effect
        raycaster.setFromCamera(mouse, camera);
        const hits = raycaster.intersectObjects([...boardGroup.children, ...piecesGroup.children]);
        if (hits.length > 0) {
            const obj = hits[0].object;
            if (obj.userData.algebraic || obj.userData.square) {
                const sq = obj.userData.algebraic || obj.userData.square;
                const pos = positionToWorld(sq);
                hoverIndicator.position.x = pos.x;
                hoverIndicator.position.z = pos.z;
                hoverIndicator.material.opacity = 0.4;
                renderer.domElement.style.cursor = 'pointer';
            }
        } else {
            hoverIndicator.material.opacity = 0;
            renderer.domElement.style.cursor = 'default';
        }
    }

    let clickHandler = null;
    function onPointerDown(e) {
        raycaster.setFromCamera(mouse, camera);
        const hits = raycaster.intersectObjects([...boardGroup.children, ...piecesGroup.children]);
        if (hits.length > 0 && clickHandler) {
            clickHandler(hits[0].object.userData);
        }
    }

    function onSquareClick(handler) {
        clickHandler = handler;
    }

    function setTheme(themeName) {
        if (THEMES[themeName]) {
            boardConfig.theme = themeName;
            createBoard();
        }
    }

    // ═══ VR SUPPORT ═══
    function updateTeleportArc(controller) {
        const tempMatrix = new THREE.Matrix4();
        tempMatrix.identity().extractRotation(controller.matrixWorld);
        const dir = new THREE.Vector3(0, 0, -1).applyMatrix4(tempMatrix);
        const origin = new THREE.Vector3();
        origin.setFromMatrixPosition(controller.matrixWorld);

        // Parabolic arc simulation (gravity)
        const velocity = dir.multiplyScalar(5);
        const gravity = -9.8;
        const segments = 30;
        const dt = 0.05;
        const positions = teleportArc.geometry.attributes.position.array;
        let hitGround = false;
        const pos = origin.clone();
        const vel = velocity.clone();

        for (let i = 0; i < segments; i++) {
            positions[i * 3] = pos.x;
            positions[i * 3 + 1] = pos.y;
            positions[i * 3 + 2] = pos.z;

            vel.y += gravity * dt;
            pos.add(vel.clone().multiplyScalar(dt));

            // Check if hit ground plane (y = 0)
            if (pos.y <= 0 && !hitGround) {
                pos.y = 0;
                hitGround = true;
                teleportMarker.position.copy(pos);
                teleportMarker.visible = true;
                // Fill remaining segments at ground point
                for (let j = i + 1; j < segments; j++) {
                    positions[j * 3] = pos.x;
                    positions[j * 3 + 1] = 0;
                    positions[j * 3 + 2] = pos.z;
                }
                break;
            }
        }

        if (!hitGround) teleportMarker.visible = false;
        teleportArc.geometry.attributes.position.needsUpdate = true;
    }

    function initVR() {
        if (!navigator.xr) return false;

        const vrButton = document.createElement('button');
        vrButton.textContent = 'ENTER VR';
        vrButton.className = 'vr-button';
        vrButton.onclick = async () => {
            try {
                vrSession = await navigator.xr.requestSession('immersive-vr', {
                    requiredFeatures: ['local-floor'],
                    optionalFeatures: ['hand-tracking', 'bounded-floor'],
                });
                renderer.xr.setSession(vrSession);
                setupVRControllers();
            } catch (e) {
                console.warn('VR not available:', e);
            }
        };
        renderer.domElement.parentElement.appendChild(vrButton);
        return true;
    }

    let teleportMarker = null;
    let teleportArc = null;
    let activeTeleportController = null;

    function setupVRControllers() {
        // Teleportation marker — flat ring on ground
        const markerGeo = new THREE.RingGeometry(0.15, 0.25, 32);
        markerGeo.rotateX(-Math.PI / 2);
        const markerMat = new THREE.MeshBasicMaterial({
            color: 0x00FFAA,
            transparent: true,
            opacity: 0.7,
            side: THREE.DoubleSide,
        });
        teleportMarker = new THREE.Mesh(markerGeo, markerMat);
        teleportMarker.visible = false;
        scene.add(teleportMarker);

        // Teleportation arc
        const arcGeo = new THREE.BufferGeometry();
        const arcPositions = new Float32Array(30 * 3); // 30 segments
        arcGeo.setAttribute('position', new THREE.BufferAttribute(arcPositions, 3));
        const arcMat = new THREE.LineBasicMaterial({ color: 0x00FFAA, transparent: true, opacity: 0.5 });
        teleportArc = new THREE.Line(arcGeo, arcMat);
        teleportArc.visible = false;
        scene.add(teleportArc);

        for (let i = 0; i < 2; i++) {
            const controller = renderer.xr.getController(i);

            // Elegant golden ray pointer
            const rayGeo = new THREE.BufferGeometry().setFromPoints([
                new THREE.Vector3(0, 0, 0),
                new THREE.Vector3(0, 0, -3),
            ]);
            const rayMat = new THREE.LineBasicMaterial({
                color: 0xFFCC44,
                transparent: true,
                opacity: 0.6,
            });
            controller.add(new THREE.Line(rayGeo, rayMat));
            scene.add(controller);
            vrControllers.push(controller);

            // Select = interact with chess pieces
            controller.addEventListener('selectstart', () => {
                const tempMatrix = new THREE.Matrix4();
                tempMatrix.identity().extractRotation(controller.matrixWorld);
                raycaster.ray.origin.setFromMatrixPosition(controller.matrixWorld);
                raycaster.ray.direction.set(0, 0, -1).applyMatrix4(tempMatrix);

                const hits = raycaster.intersectObjects([...boardGroup.children, ...piecesGroup.children]);
                if (hits.length > 0 && clickHandler) {
                    clickHandler(hits[0].object.userData);
                }
            });

            // Squeeze = teleportation (hold to aim, release to teleport)
            controller.addEventListener('squeezestart', () => {
                activeTeleportController = controller;
                teleportArc.visible = true;
                teleportMarker.visible = true;
            });

            controller.addEventListener('squeezeend', () => {
                if (activeTeleportController === controller && teleportMarker.visible) {
                    // Teleport — move XR camera rig to marker position
                    const cameraRig = renderer.xr.getCamera();
                    const offsetX = teleportMarker.position.x - cameraRig.position.x;
                    const offsetZ = teleportMarker.position.z - cameraRig.position.z;
                    // Move the reference space
                    const refSpace = renderer.xr.getReferenceSpace();
                    if (refSpace) {
                        const teleportOffset = new XRRigidTransform({ x: -offsetX, y: 0, z: -offsetZ });
                        const newRefSpace = refSpace.getOffsetReferenceSpace(teleportOffset);
                        renderer.xr.setReferenceSpace(newRefSpace);
                    }
                }
                activeTeleportController = null;
                teleportArc.visible = false;
                teleportMarker.visible = false;
            });

            // Hand tracking support
            const hand = renderer.xr.getHand(i);
            scene.add(hand);

            hand.addEventListener('pinchstart', () => {
                // Natural piece pickup with pinch gesture
                const indexTip = hand.joints['index-finger-tip'];
                if (indexTip) {
                    const tipPos = new THREE.Vector3();
                    indexTip.getWorldPosition(tipPos);

                    let closest = null;
                    let closestDist = 0.1; // 10cm pickup range
                    piecesGroup.children.forEach(piece => {
                        const dist = tipPos.distanceTo(piece.position);
                        if (dist < closestDist) {
                            closestDist = dist;
                            closest = piece;
                        }
                    });

                    if (closest && clickHandler) {
                        clickHandler(closest.userData);
                    }
                }
            });
        }
    }

    // ═══ CAMERA ═══
    function getCameraPresets() {
        return {
            white: { pos: [0, 3.5, 4.5], target: [0, 1.8, 0] },
            black: { pos: [0, 3.5, -4.5], target: [0, 1.8, 0] },
            top: { pos: [0, 6.5, 0.1], target: [0, 1.8, 0] },
            side: { pos: [5, 3.0, 0], target: [0, 1.8, 0] },
            cinematic: { pos: [3, 3.2, 3.5], target: [0, 2.0, 0] },
            fireplace: { pos: [-3.5, 2.8, 2], target: [0, 1.8, 0] },
        };
    }

    function setCameraView(presetName) {
        const preset = getCameraPresets()[presetName];
        if (!preset) return;
        const targetPos = new THREE.Vector3(...preset.pos);
        const targetLook = new THREE.Vector3(...preset.target);
        const startPos = camera.position.clone();
        const startTarget = controls.target.clone();
        const duration = 1000;
        const start = Date.now();

        function animate() {
            const t = Math.min(1, (Date.now() - start) / duration);
            const ease = t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
            camera.position.lerpVectors(startPos, targetPos, ease);
            controls.target.lerpVectors(startTarget, targetLook, ease);
            if (t < 1) requestAnimationFrame(animate);
        }
        animate();
    }

    // ═══ RENDER LOOP ═══
    function render() {
        const delta = clock.getDelta();
        const time = clock.getElapsedTime();

        controls.update();
        updateEffects(delta);
        updateAmbientAnimations(time);

        // Animate film grain time uniform + motion blur velocity
        if (composer && composer.passes) {
            for (const pass of composer.passes) {
                if (pass.userData && pass.userData.timeUniform) {
                    pass.userData.timeUniform.value = time;
                }
                if (pass.userData && pass.userData.isMotionBlur) {
                    const prev = pass.userData.prevCamPos;
                    const dx = camera.position.x - prev.x;
                    const dy = camera.position.y - prev.y;
                    const dz = camera.position.z - prev.z;
                    const speed = Math.sqrt(dx * dx + dy * dy + dz * dz);
                    // Smooth velocity factor (clamped 0–1)
                    const target = Math.min(speed * 0.5, 1.0);
                    const uniforms = pass.material.uniforms;
                    uniforms.velocityFactor.value += (target - uniforms.velocityFactor.value) * 0.1;
                    // Compute blur direction from camera rotation delta
                    const rotDx = camera.rotation.y - pass.userData.prevCamRot.y;
                    const rotDy = camera.rotation.x - pass.userData.prevCamRot.x;
                    uniforms.delta.value.set(rotDx * 2.0, rotDy * 2.0);
                    // Store current for next frame
                    prev.copy(camera.position);
                    pass.userData.prevCamRot.copy(camera.rotation);
                }
            }
        }

        // Update multiplayer world (avatar interpolation, name tags)
        if (typeof ChessWorld !== 'undefined') {
            try { ChessWorld.update(delta); } catch(e) {}
        }

        // Update VR teleportation arc when aiming
        if (activeTeleportController && teleportArc) {
            updateTeleportArc(activeTeleportController);
        }

        // Use post-processing composer if available, else direct render
        if (composer) {
            composer.render(delta);
        } else {
            renderer.render(scene, camera);
        }
    }

    function startLoop() {
        renderer.setAnimationLoop(() => render());
    }

    function destroy() {
        window.removeEventListener('resize', onResize);
        renderer.dispose();
        scene.traverse(obj => {
            if (obj.geometry) obj.geometry.dispose();
            if (obj.material) {
                if (Array.isArray(obj.material)) obj.material.forEach(m => m.dispose());
                else obj.material.dispose();
            }
        });
    }

    // ═══ PUBLIC API ═══
    return {
        init,
        setPieces,
        animateMove,
        removePiece,
        highlightSquares,
        clearHighlights,
        spawnCheckEffect,
        spawnCaptureEffect,
        spawnVictoryParticles,
        setTheme,
        setCameraView,
        onSquareClick,
        initVR,
        startLoop,
        render,
        createBoard,
        destroy,
        get themes() { return THEMES; },
        get scene() { return scene; },
        get camera() { return camera; },
        get renderer() { return renderer; },
    };
})();
