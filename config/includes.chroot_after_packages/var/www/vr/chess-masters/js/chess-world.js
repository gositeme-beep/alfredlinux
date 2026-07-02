/* ═══════════════════════════════════════════════════════════════
   CHESS MASTERS — World Event Module
   GoSiteMe · Project Grandmaster II

   Renders the 200-player outdoor chess event world:
   - 50 chess tables spread across the tropical pavilion grounds
   - Player avatars (colored capsule figures with nameplates)
   - Smooth interpolation of remote player positions
   - Table status indicators (available/in-game/full)
   - Proximity-based name visibility
   - Mini-map showing player/table positions
   ═══════════════════════════════════════════════════════════════ */

const ChessWorld = (() => {
    'use strict';

    let scene, camera;
    const playerMeshes = new Map();   // playerId → THREE.Group
    const tableMeshes = new Map();    // tableId → THREE.Group
    const nameSprites = new Map();    // playerId → THREE.Sprite
    let worldGroup = null;
    let initialized = false;

    // ── TABLE LAYOUT — 50 tables in organic outdoor clusters ──
    const TABLE_POSITIONS = [];
    (function generateTableLayout() {
        // Inner ring — 8 premium tables near center pavilion
        for (let i = 0; i < 8; i++) {
            const angle = (i / 8) * Math.PI * 2;
            const r = 6;
            TABLE_POSITIONS.push({
                id: `table_${i + 1}`,
                x: Math.cos(angle) * r,
                z: Math.sin(angle) * r,
                tier: 'premium',
            });
        }
        // Middle ring — 16 tables on the terrace
        for (let i = 0; i < 16; i++) {
            const angle = (i / 16) * Math.PI * 2 + 0.15;
            const r = 12 + Math.sin(i * 1.3) * 1.5;
            TABLE_POSITIONS.push({
                id: `table_${i + 9}`,
                x: Math.cos(angle) * r,
                z: Math.sin(angle) * r,
                tier: 'standard',
            });
        }
        // Outer ring — 16 tables on the beach/garden area
        for (let i = 0; i < 16; i++) {
            const angle = (i / 16) * Math.PI * 2 + 0.3;
            const r = 20 + Math.sin(i * 2.1) * 2;
            TABLE_POSITIONS.push({
                id: `table_${i + 25}`,
                x: Math.cos(angle) * r,
                z: Math.sin(angle) * r,
                tier: 'garden',
            });
        }
        // Scattered — 10 more in scenic spots
        const scenic = [
            { x: -25, z: -8 }, { x: 26, z: -6 }, { x: -22, z: 12 }, { x: 24, z: 10 },
            { x: 0, z: -22 }, { x: -15, z: -18 }, { x: 16, z: -20 },
            { x: -28, z: 4 }, { x: 30, z: -2 }, { x: 0, z: 25 },
        ];
        scenic.forEach((s, i) => {
            TABLE_POSITIONS.push({ id: `table_${i + 41}`, x: s.x, z: s.z, tier: 'scenic' });
        });
    })();

    // ── Shared materials (reused across instances) ──
    const sharedMats = {};
    let instancedLegs = null;     // InstancedMesh for all table legs
    let instancedChairLegs = null; // InstancedMesh for all chair legs

    // ── INIT ──
    function init(threeScene, threeCamera) {
        scene = threeScene;
        camera = threeCamera;
        worldGroup = new THREE.Group();
        worldGroup.name = 'chess-world';
        scene.add(worldGroup);

        // Shared materials
        sharedMats.tablePremium = new THREE.MeshStandardMaterial({ color: 0x3A1808, roughness: 0.3, metalness: 0.1 });
        sharedMats.tableStandard = new THREE.MeshStandardMaterial({ color: 0x5A3A1A, roughness: 0.3, metalness: 0.1 });
        sharedMats.leg = new THREE.MeshStandardMaterial({ color: 0x2A1205, roughness: 0.4 });
        sharedMats.chair = new THREE.MeshStandardMaterial({ color: 0x4A3018, roughness: 0.5 });
        sharedMats.statusGreen = new THREE.MeshBasicMaterial({ color: 0x00FF44 });

        // Create shared board texture once
        const boardCanvas = document.createElement('canvas');
        boardCanvas.width = 64;
        boardCanvas.height = 64;
        const bCtx = boardCanvas.getContext('2d');
        for (let r = 0; r < 8; r++) {
            for (let c = 0; c < 8; c++) {
                bCtx.fillStyle = (r + c) % 2 === 0 ? '#F5DEB3' : '#5C3317';
                bCtx.fillRect(c * 8, r * 8, 8, 8);
            }
        }
        const boardTex = new THREE.CanvasTexture(boardCanvas);
        boardTex.magFilter = THREE.NearestFilter;
        sharedMats.board = new THREE.MeshStandardMaterial({ map: boardTex, roughness: 0.4 });

        // ── INSTANCED TABLE LEGS (50 tables × 4 legs = 200 instances) ──
        const legGeo = new THREE.CylinderGeometry(0.03, 0.04, 0.82, 6);
        instancedLegs = new THREE.InstancedMesh(legGeo, sharedMats.leg, 200);
        instancedLegs.castShadow = false;
        instancedLegs.receiveShadow = false;
        let legIdx = 0;
        const legMatrix = new THREE.Matrix4();
        const legOffsets = [[-0.5, 0.41, -0.35], [0.5, 0.41, -0.35], [-0.5, 0.41, 0.35], [0.5, 0.41, 0.35]];

        // ── INSTANCED CHAIR LEGS (50 tables × 2 chairs × 4 legs = 400 instances) ──
        const clGeo = new THREE.CylinderGeometry(0.02, 0.02, 0.5, 4);
        instancedChairLegs = new THREE.InstancedMesh(clGeo, sharedMats.chair, 400);
        instancedChairLegs.castShadow = false;
        instancedChairLegs.receiveShadow = false;
        let chairLegIdx = 0;
        const clMatrix = new THREE.Matrix4();
        const clOffsets = [[-0.16, 0.25, -0.16], [0.16, 0.25, -0.16], [-0.16, 0.25, 0.16], [0.16, 0.25, 0.16]];

        // Place tables with LOD
        TABLE_POSITIONS.forEach(tp => {
            const lod = createWorldTableLOD(tp);
            tableMeshes.set(tp.id, lod);
            worldGroup.add(lod);

            // Set instanced leg transforms for this table
            legOffsets.forEach(([lx, ly, lz]) => {
                legMatrix.makeTranslation(tp.x + lx, ly, tp.z + lz);
                instancedLegs.setMatrixAt(legIdx++, legMatrix);
            });

            // Set instanced chair leg transforms
            [-0.65, 0.65].forEach(zOff => {
                clOffsets.forEach(([cx, cy, cz]) => {
                    clMatrix.makeTranslation(tp.x + cx, cy, tp.z + zOff + cz);
                    instancedChairLegs.setMatrixAt(chairLegIdx++, clMatrix);
                });
            });
        });

        instancedLegs.instanceMatrix.needsUpdate = true;
        instancedChairLegs.instanceMatrix.needsUpdate = true;
        worldGroup.add(instancedLegs);
        worldGroup.add(instancedChairLegs);

        // Listen for multiplayer events
        if (typeof ChessMultiplayer !== 'undefined') {
            ChessMultiplayer.on('world-sync', handleWorldSync);
            ChessMultiplayer.on('player-join', handlePlayerJoin);
            ChessMultiplayer.on('player-leave', handlePlayerLeave);
            ChessMultiplayer.on('player-move', handlePlayerMove);
            ChessMultiplayer.on('table-update', handleTableUpdate);
        }

        initialized = true;
        console.log(`[Chess World] Initialized — ${TABLE_POSITIONS.length} tables placed`);
    }

    // ── CREATE WORLD TABLE with LOD (3 detail levels) ──
    function createWorldTableLOD(tp) {
        const lod = new THREE.LOD();
        lod.position.set(tp.x, 0, tp.z);
        lod.userData.tableId = tp.id;
        lod.userData.tier = tp.tier;

        // HIGH DETAIL (< 15 units) — tabletop + board + chairs + status light (legs are instanced)
        const highGroup = new THREE.Group();
        const tableMat = tp.tier === 'premium' ? sharedMats.tablePremium : sharedMats.tableStandard;

        const tableGeo = new THREE.BoxGeometry(1.2, 0.06, 0.9);
        const tableTop = new THREE.Mesh(tableGeo, tableMat);
        tableTop.position.y = 0.82;
        tableTop.castShadow = true;
        tableTop.receiveShadow = true;
        highGroup.add(tableTop);

        // Board on table
        const boardGeo = new THREE.PlaneGeometry(0.7, 0.7);
        boardGeo.rotateX(-Math.PI / 2);
        const board = new THREE.Mesh(boardGeo, sharedMats.board);
        board.position.y = 0.86;
        highGroup.add(board);

        // Chairs (seats + backs only, legs are instanced)
        [-0.65, 0.65].forEach(zOff => {
            const seat = new THREE.Mesh(new THREE.BoxGeometry(0.4, 0.04, 0.4), sharedMats.chair);
            seat.position.set(0, 0.5, zOff);
            highGroup.add(seat);
            const back = new THREE.Mesh(new THREE.BoxGeometry(0.4, 0.45, 0.04), sharedMats.chair);
            back.position.set(0, 0.74, zOff + (zOff > 0 ? 0.18 : -0.18));
            highGroup.add(back);
        });

        // Status light
        const lightGeo = new THREE.SphereGeometry(0.04, 6, 6);
        const lightMat = sharedMats.statusGreen.clone(); // clone so each can change color
        const statusLight = new THREE.Mesh(lightGeo, lightMat);
        statusLight.position.y = 1.1;
        statusLight.userData.isStatusLight = true;
        highGroup.add(statusLight);

        // MEDIUM DETAIL (15-35 units) — simple table slab + status light
        const medGroup = new THREE.Group();
        const medTable = new THREE.Mesh(new THREE.BoxGeometry(1.2, 0.82, 0.9), tableMat);
        medTable.position.y = 0.41;
        medGroup.add(medTable);
        const medLight = new THREE.Mesh(lightGeo, lightMat);
        medLight.position.y = 1.1;
        medLight.userData.isStatusLight = true;
        medGroup.add(medLight);

        // LOW DETAIL (> 35 units) — just a colored dot
        const lowGroup = new THREE.Group();
        const dot = new THREE.Mesh(new THREE.SphereGeometry(0.2, 4, 4), lightMat);
        dot.position.y = 0.85;
        lowGroup.add(dot);

        lod.addLevel(highGroup, 0);
        lod.addLevel(medGroup, 15);
        lod.addLevel(lowGroup, 35);

        return lod;
    }

    // ── PLAYER AVATAR ──
    function createPlayerAvatar(playerData) {
        const group = new THREE.Group();
        group.userData.playerId = playerData.id;

        const colors = playerData.avatar || { body: 0x4A90D9, accent: 0xFFD700 };

        // Body — capsule shape (cylinder + 2 hemispheres)
        const bodyMat = new THREE.MeshStandardMaterial({
            color: colors.body,
            roughness: 0.6,
            metalness: 0.1,
        });
        const bodyGeo = new THREE.CylinderGeometry(0.18, 0.2, 0.7, 10);
        const body = new THREE.Mesh(bodyGeo, bodyMat);
        body.position.y = 0.55;
        body.castShadow = true;
        group.add(body);

        // Head
        const headGeo = new THREE.SphereGeometry(0.14, 10, 8);
        const head = new THREE.Mesh(headGeo, bodyMat);
        head.position.y = 1.05;
        head.castShadow = true;
        group.add(head);

        // Accent band (belt/collar)
        const accentMat = new THREE.MeshStandardMaterial({
            color: colors.accent,
            roughness: 0.3,
            metalness: 0.5,
        });
        const band = new THREE.Mesh(new THREE.TorusGeometry(0.19, 0.025, 6, 12), accentMat);
        band.position.y = 0.75;
        band.rotation.x = Math.PI / 2;
        group.add(band);

        // Name tag — canvas sprite
        const nameSprite = createNameSprite(playerData.name, playerData.elo);
        nameSprite.position.y = 1.35;
        group.add(nameSprite);
        nameSprites.set(playerData.id, nameSprite);

        // Set initial position
        if (playerData.pos) {
            group.position.set(playerData.pos.x, playerData.pos.y || 0, playerData.pos.z);
        }
        if (playerData.rot) {
            group.rotation.y = playerData.rot.y || 0;
        }

        // Interpolation targets
        group.userData.targetPos = group.position.clone();
        group.userData.targetRotY = group.rotation.y;

        return group;
    }

    function createNameSprite(name, elo) {
        const canvas = document.createElement('canvas');
        canvas.width = 256;
        canvas.height = 64;
        const ctx = canvas.getContext('2d');

        // Background
        ctx.fillStyle = 'rgba(0,0,0,0.6)';
        ctx.roundRect(4, 4, 248, 56, 8);
        ctx.fill();

        // Name
        ctx.fillStyle = '#FFFFFF';
        ctx.font = 'bold 22px Arial';
        ctx.textAlign = 'center';
        ctx.fillText(name || 'Guest', 128, 30);

        // ELO
        ctx.fillStyle = '#FFD700';
        ctx.font = '16px Arial';
        ctx.fillText(`ELO ${elo || 1200}`, 128, 52);

        const tex = new THREE.CanvasTexture(canvas);
        const mat = new THREE.SpriteMaterial({ map: tex, transparent: true, depthTest: false });
        const sprite = new THREE.Sprite(mat);
        sprite.scale.set(1.2, 0.3, 1);
        return sprite;
    }

    // ── EVENT HANDLERS ──
    function handleWorldSync(data) {
        // Remove stale players
        for (const [id] of playerMeshes) {
            if (!data.players.find(p => p.id === id)) {
                removePlayerMesh(id);
            }
        }
        // Add/update players
        data.players.forEach(p => {
            if (!playerMeshes.has(p.id)) {
                addPlayerMesh(p);
            } else {
                updatePlayerTarget(p);
            }
        });
    }

    function handlePlayerJoin(player) {
        if (!playerMeshes.has(player.id)) {
            addPlayerMesh(player);
        }
    }

    function handlePlayerLeave(data) {
        removePlayerMesh(data.playerId);
    }

    function handlePlayerMove(data) {
        const mesh = playerMeshes.get(data.playerId);
        if (mesh) {
            mesh.userData.targetPos.set(data.pos.x, data.pos.y || 0, data.pos.z);
            mesh.userData.targetRotY = data.rot ? data.rot.y : mesh.rotation.y;
        }
    }

    function handleTableUpdate(table) {
        const lod = tableMeshes.get(table.id);
        if (!lod) return;

        // Find status light in any LOD level
        let color = 0x00FF44;
        if (table.status === 'playing') color = 0xFF4444;
        else if (table.white || table.black) color = 0xFFAA00;

        lod.traverse(child => {
            if (child.userData && child.userData.isStatusLight) {
                child.material.color.setHex(color);
            }
        });
    }

    function addPlayerMesh(playerData) {
        const avatar = createPlayerAvatar(playerData);
        playerMeshes.set(playerData.id, avatar);
        worldGroup.add(avatar);
    }

    function removePlayerMesh(id) {
        const mesh = playerMeshes.get(id);
        if (mesh) {
            worldGroup.remove(mesh);
            mesh.traverse(c => {
                if (c.geometry) c.geometry.dispose();
                if (c.material) {
                    if (c.material.map) c.material.map.dispose();
                    c.material.dispose();
                }
            });
            playerMeshes.delete(id);
            nameSprites.delete(id);
        }
    }

    function updatePlayerTarget(p) {
        const mesh = playerMeshes.get(p.id);
        if (mesh && p.pos) {
            mesh.userData.targetPos.set(p.pos.x, p.pos.y || 0, p.pos.z);
            if (p.rot) mesh.userData.targetRotY = p.rot.y;
        }
    }

    // ── UPDATE LOOP (called from renderer) ──
    function update(delta) {
        if (!initialized) return;

        // Update LOD levels based on camera distance
        for (const [, lod] of tableMeshes) {
            if (lod.isLOD) lod.update(camera);
        }

        // Smooth interpolation for all remote players
        const lerpSpeed = 8 * delta; // ~8x per second catch-up
        for (const [id, mesh] of playerMeshes) {
            const target = mesh.userData.targetPos;
            mesh.position.lerp(target, Math.min(lerpSpeed, 1));

            // Smooth rotation
            const targetY = mesh.userData.targetRotY;
            let diff = targetY - mesh.rotation.y;
            while (diff > Math.PI) diff -= Math.PI * 2;
            while (diff < -Math.PI) diff += Math.PI * 2;
            mesh.rotation.y += diff * Math.min(lerpSpeed, 1);

            // Name tags always face camera
            const sprite = nameSprites.get(id);
            if (sprite && camera) {
                // Sprites auto-face camera, but hide if too far
                const dist = mesh.position.distanceTo(camera.position);
                sprite.visible = dist < 30;
                sprite.material.opacity = dist < 15 ? 1 : Math.max(0, 1 - (dist - 15) / 15);
            }
        }
    }

    // ── PUBLIC API ──
    return {
        init,
        update,
        TABLE_POSITIONS,
        get playerCount() { return playerMeshes.size; },
        get tableCount() { return tableMeshes.size; },
        getTableMesh: (id) => tableMeshes.get(id),
        getPlayerMesh: (id) => playerMeshes.get(id),
    };
})();
