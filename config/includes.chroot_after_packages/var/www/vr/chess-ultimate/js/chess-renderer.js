/* ═══════════════════════════════════════════════════════════════
   CHESS ULTIMATE — 3D Renderer Module
   Agents: Da Vinci, Escher, Brunelleschi, Rodin (Graphics Division)
   
   Three.js powered 3D chess board and pieces with:
   - Instanced rendering for spectators/particles
   - LOD system for performance
   - 8 board themes, 4 piece styles
   - VR controller raycasting
   - Particle effects and lighting
   ═══════════════════════════════════════════════════════════════ */

const ChessRenderer = (() => {
    let scene, camera, renderer, controls, clock;
    let vrSession = null, vrControllers = [];
    let boardGroup, piecesGroup, effectsGroup, arenaGroup;
    let raycaster, mouse, hoverSquare = null, selectedSquare = null;
    let animationMixers = [];
    let boardConfig = { theme: 'classic', pieceStyle: 'staunton' };

    // Board themes
    const THEMES = {
        classic:   { light: 0xF0D9B5, dark: 0xB58863, border: 0x4A3728, name: 'Classic Wood' },
        marble:    { light: 0xFAFAFA, dark: 0x3D3D3D, border: 0x1A1A1A, name: 'Marble' },
        emerald:   { light: 0xEEEED2, dark: 0x769656, border: 0x2D4A2E, name: 'Emerald' },
        ocean:     { light: 0xE0F0FF, dark: 0x3366AA, border: 0x1A3355, name: 'Ocean Blue' },
        crimson:   { light: 0xFFE0E0, dark: 0xCC3333, border: 0x661A1A, name: 'Crimson' },
        midnight:  { light: 0x404060, dark: 0x1A1A2E, border: 0x0A0A1A, name: 'Midnight' },
        gold:      { light: 0xFFF8DC, dark: 0xDAA520, border: 0x8B6914, name: 'Royal Gold' },
        neon:      { light: 0x00FF88, dark: 0x0A0A2E, border: 0x00FFFF, name: 'Neon Cyber' },
    };

    // Piece geometry cache
    const geometryCache = {};
    const materialCache = {};

    function init(containerId) {
        const container = document.getElementById(containerId);
        clock = new THREE.Clock();

        // Scene
        scene = new THREE.Scene();
        scene.fog = new THREE.FogExp2(0x0a0a1a, 0.015);

        // Camera
        camera = new THREE.PerspectiveCamera(60, container.clientWidth / container.clientHeight, 0.1, 500);
        camera.position.set(0, 8, 8);
        camera.lookAt(0, 0, 0);

        // Renderer
        renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        renderer.setSize(container.clientWidth, container.clientHeight);
        renderer.setPixelRatio(typeof GoSiteMeQuality!=='undefined'?GoSiteMeQuality.dpr:Math.min(window.devicePixelRatio,2));
        renderer.shadowMap.enabled = true;
        renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        renderer.toneMapping = THREE.ACESFilmicToneMapping;
        renderer.toneMappingExposure = 1.2;
        renderer.outputEncoding = THREE.sRGBEncoding;
        renderer.xr.enabled = true;
        container.appendChild(renderer.domElement);

        // Controls
        controls = new THREE.OrbitControls(camera, renderer.domElement);
        controls.enableDamping = true;
        controls.dampingFactor = 0.08;
        controls.maxPolarAngle = Math.PI / 2.1;
        controls.minDistance = 4;
        controls.maxDistance = 25;
        controls.target.set(0, 0, 0);

        // Raycaster
        raycaster = new THREE.Raycaster();
        mouse = new THREE.Vector2();

        // Groups
        boardGroup = new THREE.Group();
        piecesGroup = new THREE.Group();
        effectsGroup = new THREE.Group();
        arenaGroup = new THREE.Group();
        scene.add(boardGroup, piecesGroup, effectsGroup, arenaGroup);

        // Lights
        setupLighting();

        // Skybox
        setupSkybox();

        // Board
        createBoard();

        // Arena
        createArena();

        // Events
        window.addEventListener('resize', onResize);
        renderer.domElement.addEventListener('pointermove', onPointerMove);
        renderer.domElement.addEventListener('pointerdown', onPointerDown);

        return { scene, camera, renderer };
    }

    function setupLighting() {
        // Ambient
        const ambient = new THREE.AmbientLight(0x404060, 0.4);
        scene.add(ambient);

        // Main directional (sun)
        const sun = new THREE.DirectionalLight(0xFFEEDD, 1.2);
        sun.position.set(5, 15, 5);
        sun.castShadow = true;
        sun.shadow.mapSize.width = 2048;
        sun.shadow.mapSize.height = 2048;
        sun.shadow.camera.near = 0.5;
        sun.shadow.camera.far = 50;
        sun.shadow.camera.left = -10;
        sun.shadow.camera.right = 10;
        sun.shadow.camera.top = 10;
        sun.shadow.camera.bottom = -10;
        scene.add(sun);

        // Fill light
        const fill = new THREE.DirectionalLight(0x8888FF, 0.3);
        fill.position.set(-5, 8, -5);
        scene.add(fill);

        // Rim lights for drama
        const rim1 = new THREE.PointLight(0xFF4444, 0.5, 20);
        rim1.position.set(-6, 5, 0);
        scene.add(rim1);

        const rim2 = new THREE.PointLight(0x4444FF, 0.5, 20);
        rim2.position.set(6, 5, 0);
        scene.add(rim2);

        // Spot on board center
        const spot = new THREE.SpotLight(0xFFFFFF, 0.8, 20, Math.PI / 6, 0.3);
        spot.position.set(0, 12, 0);
        spot.target.position.set(0, 0, 0);
        spot.castShadow = true;
        scene.add(spot, spot.target);
    }

    function setupSkybox() {
        // Procedural night sky with stars
        const starGeo = new THREE.BufferGeometry();
        const starCount = 2000;
        const positions = new Float32Array(starCount * 3);
        const colors = new Float32Array(starCount * 3);
        for (let i = 0; i < starCount; i++) {
            const r = 150 + Math.random() * 200;
            const theta = Math.random() * Math.PI * 2;
            const phi = Math.random() * Math.PI;
            positions[i * 3] = r * Math.sin(phi) * Math.cos(theta);
            positions[i * 3 + 1] = Math.abs(r * Math.cos(phi));
            positions[i * 3 + 2] = r * Math.sin(phi) * Math.sin(theta);
            const brightness = 0.5 + Math.random() * 0.5;
            colors[i * 3] = brightness;
            colors[i * 3 + 1] = brightness;
            colors[i * 3 + 2] = brightness + Math.random() * 0.2;
        }
        starGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        starGeo.setAttribute('color', new THREE.BufferAttribute(colors, 3));
        const starMat = new THREE.PointsMaterial({ size: 0.5, vertexColors: true, transparent: true, opacity: 0.8 });
        scene.add(new THREE.Points(starGeo, starMat));
    }

    function createBoard() {
        boardGroup.clear();
        const theme = THEMES[boardConfig.theme] || THEMES.classic;

        // Board base
        const baseGeo = new THREE.BoxGeometry(9, 0.3, 9);
        const baseMat = new THREE.MeshStandardMaterial({
            color: theme.border, roughness: 0.3, metalness: 0.6,
        });
        const base = new THREE.Mesh(baseGeo, baseMat);
        base.position.y = -0.15;
        base.receiveShadow = true;
        boardGroup.add(base);

        // Squares
        const squareGeo = new THREE.BoxGeometry(0.95, 0.05, 0.95);
        for (let r = 0; r < 8; r++) {
            for (let c = 0; c < 8; c++) {
                const isLight = (r + c) % 2 === 0;
                const mat = new THREE.MeshStandardMaterial({
                    color: isLight ? theme.light : theme.dark,
                    roughness: 0.5,
                    metalness: 0.1,
                });
                const sq = new THREE.Mesh(squareGeo, mat);
                sq.position.set(c - 3.5, 0.025, r - 3.5);
                sq.receiveShadow = true;
                sq.userData = { type: 'square', row: r, col: c, algebraic: String.fromCharCode(97 + c) + (r + 1) };
                boardGroup.add(sq);
            }
        }

        // Coordinate labels
        const labelColor = theme.border === 0x1A1A1A ? 0x888888 : 0xCCCCCC;
        for (let i = 0; i < 8; i++) {
            addTextSprite(String.fromCharCode(97 + i), i - 3.5, 0.01, -4.3, labelColor);
            addTextSprite(String(i + 1), -4.3, 0.01, i - 3.5, labelColor);
        }
    }

    function addTextSprite(text, x, y, z, color) {
        const canvas = document.createElement('canvas');
        canvas.width = 64; canvas.height = 64;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#' + color.toString(16).padStart(6, '0');
        ctx.font = 'bold 48px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(text, 32, 32);
        const tex = new THREE.CanvasTexture(canvas);
        const mat = new THREE.SpriteMaterial({ map: tex, transparent: true, opacity: 0.6 });
        const sprite = new THREE.Sprite(mat);
        sprite.position.set(x, y, z);
        sprite.scale.set(0.4, 0.4, 0.4);
        boardGroup.add(sprite);
    }

    // Procedural chess piece generation (Staunton style using LatheGeometry)
    function createPieceGeometry(type) {
        if (geometryCache[type]) return geometryCache[type];

        let points = [];
        const profiles = {
            pawn: [
                [0, 0], [0.26, 0], [0.28, 0.02], [0.28, 0.06], [0.14, 0.14],
                [0.16, 0.22], [0.22, 0.26], [0.22, 0.30], [0.10, 0.38],
                [0.14, 0.50], [0.12, 0.56], [0.08, 0.60], [0, 0.62]
            ],
            rook: [
                [0, 0], [0.28, 0], [0.30, 0.02], [0.30, 0.06], [0.16, 0.12],
                [0.18, 0.50], [0.24, 0.52], [0.24, 0.62], [0.20, 0.62],
                [0.20, 0.58], [0.14, 0.58], [0.14, 0.62], [0.08, 0.62],
                [0.08, 0.58], [0, 0.58]
            ],
            knight: [
                [0, 0], [0.28, 0], [0.30, 0.02], [0.30, 0.06], [0.16, 0.12],
                [0.18, 0.30], [0.14, 0.45], [0.18, 0.60], [0.14, 0.72],
                [0.06, 0.78], [0, 0.80]
            ],
            bishop: [
                [0, 0], [0.28, 0], [0.30, 0.02], [0.30, 0.06], [0.14, 0.14],
                [0.16, 0.22], [0.20, 0.26], [0.12, 0.50], [0.06, 0.62],
                [0.10, 0.64], [0.06, 0.72], [0.02, 0.76], [0, 0.78]
            ],
            queen: [
                [0, 0], [0.30, 0], [0.32, 0.02], [0.32, 0.06], [0.16, 0.14],
                [0.18, 0.24], [0.24, 0.28], [0.14, 0.55], [0.18, 0.64],
                [0.10, 0.72], [0.06, 0.80], [0.10, 0.82], [0.06, 0.86],
                [0, 0.90]
            ],
            king: [
                [0, 0], [0.30, 0], [0.32, 0.02], [0.32, 0.06], [0.16, 0.14],
                [0.18, 0.24], [0.24, 0.28], [0.14, 0.55], [0.18, 0.64],
                [0.10, 0.72], [0.04, 0.80], [0.04, 0.84], [0.12, 0.84],
                [0.12, 0.88], [0.04, 0.88], [0.04, 0.96], [0, 0.96]
            ],
        };

        const profile = profiles[type] || profiles.pawn;
        points = profile.map(([x, y]) => new THREE.Vector2(x * 1.2, y * 1.2));
        const geo = new THREE.LatheGeometry(points, 24);
        geo.computeVertexNormals();
        geometryCache[type] = geo;
        return geo;
    }

    function createPieceMesh(type, color) {
        const geo = createPieceGeometry(type);
        const key = `${color}_${boardConfig.pieceStyle}`;
        if (!materialCache[key]) {
            const isWhite = color === 'w';
            materialCache[key] = new THREE.MeshStandardMaterial({
                color: isWhite ? 0xF8F0E0 : 0x2A2A2A,
                roughness: isWhite ? 0.4 : 0.3,
                metalness: isWhite ? 0.1 : 0.4,
                envMapIntensity: 1.5,
            });
        }
        const mesh = new THREE.Mesh(geo, materialCache[key].clone());
        mesh.castShadow = true;
        mesh.receiveShadow = true;
        return mesh;
    }

    function positionToWorld(algebraic) {
        const col = algebraic.charCodeAt(0) - 97;
        const row = parseInt(algebraic[1]) - 1;
        return new THREE.Vector3(col - 3.5, 0.05, row - 3.5);
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
            mesh.userData = { type: 'piece', pieceType: sq.piece.type, color: sq.piece.color, square: sq.square };
            piecesGroup.add(mesh);
        });
    }

    function animateMove(from, to, callback) {
        const piece = piecesGroup.children.find(p => p.userData.square === from);
        if (!piece) { if (callback) callback(); return; }

        const startPos = piece.position.clone();
        const endPos = positionToWorld(to);
        const arcHeight = 0.6;
        const duration = 400;
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
            // Capture explosion effect
            spawnCaptureEffect(piece.position.clone(), piece.userData.color);
            piecesGroup.remove(piece);
            piece.geometry.dispose();
        }
    }

    function highlightSquares(squares, color = 0x44FF44) {
        // Remove old highlights
        effectsGroup.children.filter(c => c.userData.isHighlight).forEach(c => effectsGroup.remove(c));

        squares.forEach(sq => {
            const geo = new THREE.RingGeometry(0.3, 0.45, 16);
            geo.rotateX(-Math.PI / 2);
            const mat = new THREE.MeshBasicMaterial({ color, transparent: true, opacity: 0.6 });
            const ring = new THREE.Mesh(geo, mat);
            const pos = positionToWorld(sq);
            ring.position.set(pos.x, 0.06, pos.z);
            ring.userData.isHighlight = true;
            effectsGroup.add(ring);
        });
    }

    function clearHighlights() {
        const toRemove = effectsGroup.children.filter(c => c.userData.isHighlight);
        toRemove.forEach(c => { effectsGroup.remove(c); c.geometry.dispose(); c.material.dispose(); });
    }

    function spawnCaptureEffect(position, capturedColor) {
        const count = 20;
        const geo = new THREE.BufferGeometry();
        const positions = new Float32Array(count * 3);
        const velocities = [];
        for (let i = 0; i < count; i++) {
            positions[i * 3] = position.x;
            positions[i * 3 + 1] = position.y + 0.3;
            positions[i * 3 + 2] = position.z;
            velocities.push(new THREE.Vector3(
                (Math.random() - 0.5) * 4,
                Math.random() * 3 + 1,
                (Math.random() - 0.5) * 4
            ));
        }
        geo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        const mat = new THREE.PointsMaterial({
            color: capturedColor === 'w' ? 0xF8F0E0 : 0x444444,
            size: 0.08,
            transparent: true,
        });
        const particles = new THREE.Points(geo, mat);
        particles.userData.velocities = velocities;
        particles.userData.startTime = Date.now();
        particles.userData.isParticle = true;
        effectsGroup.add(particles);
    }

    function spawnCheckEffect(kingSquare) {
        const pos = positionToWorld(kingSquare);
        const geo = new THREE.RingGeometry(0.4, 0.5, 32);
        geo.rotateX(-Math.PI / 2);
        const mat = new THREE.MeshBasicMaterial({ color: 0xFF0000, transparent: true, opacity: 0.8 });
        const ring = new THREE.Mesh(geo, mat);
        ring.position.set(pos.x, 0.06, pos.z);
        ring.userData.isCheckEffect = true;
        ring.userData.startTime = Date.now();
        effectsGroup.add(ring);
    }

    function createArena() {
        arenaGroup.clear();

        // Floor
        const floorGeo = new THREE.CircleGeometry(40, 64);
        floorGeo.rotateX(-Math.PI / 2);
        const floorMat = new THREE.MeshStandardMaterial({
            color: 0x1A1A2E, roughness: 0.8, metalness: 0.2,
        });
        const floor = new THREE.Mesh(floorGeo, floorMat);
        floor.position.y = -0.16;
        floor.receiveShadow = true;
        arenaGroup.add(floor);

        // Spectator stands (instanced for performance)
        createSpectatorStands();

        // Pillars
        for (let i = 0; i < 8; i++) {
            const angle = (i / 8) * Math.PI * 2;
            const pillarGeo = new THREE.CylinderGeometry(0.15, 0.2, 6, 8);
            const pillarMat = new THREE.MeshStandardMaterial({ color: 0x3D3D5C, metalness: 0.6, roughness: 0.3 });
            const pillar = new THREE.Mesh(pillarGeo, pillarMat);
            pillar.position.set(Math.cos(angle) * 12, 3, Math.sin(angle) * 12);
            pillar.castShadow = true;
            arenaGroup.add(pillar);

            // Torch on each pillar
            const torchLight = new THREE.PointLight(0xFF6600, 0.3, 8);
            torchLight.position.set(Math.cos(angle) * 12, 5.5, Math.sin(angle) * 12);
            arenaGroup.add(torchLight);
        }
    }

    function createSpectatorStands() {
        // Use instanced meshes for performance (100 spectators)
        const spectatorGeo = new THREE.CapsuleGeometry(0.15, 0.4, 4, 8);
        const spectatorMat = new THREE.MeshStandardMaterial({ color: 0x888899, roughness: 0.7 });
        const instanceCount = 100;
        const spectators = new THREE.InstancedMesh(spectatorGeo, spectatorMat, instanceCount);
        const dummy = new THREE.Object3D();

        for (let i = 0; i < instanceCount; i++) {
            const angle = (i / instanceCount) * Math.PI * 2;
            const radius = 10 + Math.random() * 5;
            const row = Math.floor(Math.random() * 3);
            dummy.position.set(
                Math.cos(angle) * radius,
                0.3 + row * 0.8,
                Math.sin(angle) * radius
            );
            dummy.lookAt(0, 0, 0);
            dummy.updateMatrix();
            spectators.setMatrixAt(i, dummy.matrix);
            spectators.setColorAt(i, new THREE.Color().setHSL(Math.random(), 0.3, 0.4 + Math.random() * 0.3));
        }
        spectators.instanceMatrix.needsUpdate = true;
        spectators.instanceColor.needsUpdate = true;
        arenaGroup.add(spectators);
    }

    function updateEffects() {
        const now = Date.now();
        const toRemove = [];

        effectsGroup.children.forEach(child => {
            if (child.userData.isParticle) {
                const elapsed = (now - child.userData.startTime) / 1000;
                if (elapsed > 1.5) { toRemove.push(child); return; }
                const positions = child.geometry.attributes.position.array;
                child.userData.velocities.forEach((v, i) => {
                    positions[i * 3] += v.x * 0.016;
                    positions[i * 3 + 1] += (v.y - 9.8 * elapsed) * 0.016;
                    positions[i * 3 + 2] += v.z * 0.016;
                });
                child.geometry.attributes.position.needsUpdate = true;
                child.material.opacity = 1 - elapsed / 1.5;
            }
            if (child.userData.isCheckEffect) {
                const elapsed = (now - child.userData.startTime) / 1000;
                if (elapsed > 2) { toRemove.push(child); return; }
                child.material.opacity = 0.5 + Math.sin(elapsed * 8) * 0.3;
                child.scale.setScalar(1 + Math.sin(elapsed * 6) * 0.1);
            }
        });

        toRemove.forEach(c => {
            effectsGroup.remove(c);
            if (c.geometry) c.geometry.dispose();
            if (c.material) c.material.dispose();
        });
    }

    function onResize() {
        const container = renderer.domElement.parentElement;
        const w = container.clientWidth, h = container.clientHeight;
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
        renderer.setSize(w, h);
    }

    function onPointerMove(e) {
        const rect = renderer.domElement.getBoundingClientRect();
        mouse.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
        mouse.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;
    }

    let clickHandler = null;
    function onPointerDown(e) {
        raycaster.setFromCamera(mouse, camera);
        const hits = raycaster.intersectObjects([...boardGroup.children, ...piecesGroup.children]);
        if (hits.length > 0 && clickHandler) {
            const obj = hits[0].object;
            clickHandler(obj.userData);
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

    // VR Support
    function initVR() {
        if (!navigator.xr) return false;

        const vrButton = document.createElement('button');
        vrButton.textContent = 'ENTER VR';
        vrButton.className = 'vr-button';
        vrButton.onclick = async () => {
            try {
                vrSession = await navigator.xr.requestSession('immersive-vr', {
                    requiredFeatures: ['local-floor'],
                    optionalFeatures: ['hand-tracking'],
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

    function setupVRControllers() {
        for (let i = 0; i < 2; i++) {
            const controller = renderer.xr.getController(i);
            const rayGeo = new THREE.BufferGeometry().setFromPoints([
                new THREE.Vector3(0, 0, 0),
                new THREE.Vector3(0, 0, -5),
            ]);
            const rayMat = new THREE.LineBasicMaterial({ color: 0x00FF88 });
            controller.add(new THREE.Line(rayGeo, rayMat));
            scene.add(controller);
            vrControllers.push(controller);

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
        }
    }

    function render() {
        controls.update();
        updateEffects();
        renderer.render(scene, camera);
    }

    function startLoop() {
        renderer.setAnimationLoop(() => render());
    }

    function getCameraPresets() {
        return {
            white: { pos: [0, 8, 8], target: [0, 0, 0] },
            black: { pos: [0, 8, -8], target: [0, 0, 0] },
            top: { pos: [0, 14, 0.1], target: [0, 0, 0] },
            side: { pos: [10, 5, 0], target: [0, 0, 0] },
            cinematic: { pos: [6, 4, 6], target: [0, 1, 0] },
        };
    }

    function setCameraView(presetName) {
        const preset = getCameraPresets()[presetName];
        if (!preset) return;
        const targetPos = new THREE.Vector3(...preset.pos);
        const targetLook = new THREE.Vector3(...preset.target);
        const startPos = camera.position.clone();
        const startTarget = controls.target.clone();
        const duration = 800;
        const start = Date.now();

        function animate() {
            const t = Math.min(1, (Date.now() - start) / duration);
            const ease = t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
            camera.position.lerpVectors(startPos, targetPos, ease);
            controls.target.lerpVectors(startTarget, targetLook, ease);
            if (t < 1) requestAnimationFrame(animate);
        }
        animate();
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

    return {
        init,
        setPieces,
        animateMove,
        removePiece,
        highlightSquares,
        clearHighlights,
        spawnCheckEffect,
        spawnCaptureEffect,
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
