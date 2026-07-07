/* ═══════════════════════════════════════════════════════════════
   ALFRED COMMAND — Holographic Command Table
   Interactive 3D war table with territory overview
   ═══════════════════════════════════════════════════════════════ */

const CommandTable = {
    tableMesh: null,
    holoMap: null,
    zoneMarkers: [],

    build(scene) {
        // Physical table base
        const tableBase = BABYLON.MeshBuilder.CreateCylinder('tableBase', {
            diameter: 6, height: 1, tessellation: 8
        }, scene);
        tableBase.position = new BABYLON.Vector3(0, 0.5, 0);
        const baseMat = new BABYLON.StandardMaterial('tableBaseMat', scene);
        baseMat.diffuseColor = new BABYLON.Color3(0.08, 0.1, 0.18);
        baseMat.specularColor = new BABYLON.Color3(0.3, 0.3, 0.4);
        tableBase.material = baseMat;

        // Table top surface
        const tableTop = BABYLON.MeshBuilder.CreateCylinder('tableTop', {
            diameter: 6.5, height: 0.1, tessellation: 8
        }, scene);
        tableTop.position = new BABYLON.Vector3(0, 1.05, 0);
        const topMat = new BABYLON.StandardMaterial('tableTopMat', scene);
        topMat.diffuseColor = new BABYLON.Color3(0.05, 0.08, 0.15);
        topMat.emissiveColor = new BABYLON.Color3(0.02, 0.04, 0.08);
        topMat.specularColor = new BABYLON.Color3(0.4, 0.4, 0.5);
        tableTop.material = topMat;
        this.tableMesh = tableTop;

        // Holographic projection cone (lines going up)
        const holoBase = BABYLON.MeshBuilder.CreateCylinder('holoBase', {
            diameterTop: 5, diameterBottom: 6, height: 0.05, tessellation: 32
        }, scene);
        holoBase.position = new BABYLON.Vector3(0, 1.1, 0);
        const holoBaseMat = new BABYLON.StandardMaterial('holoBaseMat', scene);
        holoBaseMat.emissiveColor = new BABYLON.Color3(0.1, 0.3, 0.6);
        holoBaseMat.diffuseColor = new BABYLON.Color3(0, 0, 0);
        holoBaseMat.alpha = 0.6;
        holoBase.material = holoBaseMat;

        // Holographic map projection (3D terrain miniature)
        this.holoMap = BABYLON.MeshBuilder.CreateGround('holoMap', {
            width: 5, height: 5, subdivisions: 16
        }, scene);
        this.holoMap.position = new BABYLON.Vector3(0, 2.5, 0);

        const holoMat = new BABYLON.StandardMaterial('holoMapMat', scene);
        holoMat.emissiveColor = new BABYLON.Color3(0.1, 0.3, 0.6);
        holoMat.diffuseColor = new BABYLON.Color3(0, 0.1, 0.2);
        holoMat.alpha = 0.3;
        holoMat.wireframe = true;
        this.holoMap.material = holoMat;

        // Apply mini-terrain heightmap
        const positions = this.holoMap.getVerticesData(BABYLON.VertexBuffer.PositionKind);
        for (let i = 1; i < positions.length; i += 3) {
            const x = positions[i - 1];
            const z = positions[i + 1];
            positions[i] = Math.sin(x * 0.5) * Math.cos(z * 0.5) * 0.3 +
                           Math.sin(x * 1.2 + z * 0.8) * 0.15;
        }
        this.holoMap.updateVerticesData(BABYLON.VertexBuffer.PositionKind, positions);

        // Holographic scan line effect
        const scanLine = BABYLON.MeshBuilder.CreatePlane('scanLine', { width: 5, height: 0.02 }, scene);
        scanLine.position = new BABYLON.Vector3(0, 2.6, 0);
        scanLine.rotation.x = Math.PI / 2;
        const scanMat = new BABYLON.StandardMaterial('scanMat', scene);
        scanMat.emissiveColor = new BABYLON.Color3(0.2, 0.8, 0.4);
        scanMat.alpha = 0.4;
        scanMat.backFaceCulling = false;
        scanLine.material = scanMat;

        // Animate scan line
        let scanDir = 1;
        scene.registerBeforeRender(() => {
            scanLine.position.z += 0.02 * scanDir;
            if (scanLine.position.z > 2.5 || scanLine.position.z < -2.5) scanDir *= -1;
            this.holoMap.rotation.y += 0.002; // Slow rotation
        });

        // Edge glow
        const tableRing = BABYLON.MeshBuilder.CreateTorus('tableRing', {
            diameter: 6.5, thickness: 0.08, tessellation: 64
        }, scene);
        tableRing.position = new BABYLON.Vector3(0, 1.05, 0);
        const tableRingMat = new BABYLON.StandardMaterial('tableRingMat', scene);
        tableRingMat.emissiveColor = new BABYLON.Color3(0.15, 0.4, 0.8);
        tableRingMat.diffuseColor = new BABYLON.Color3(0, 0, 0);
        tableRing.material = tableRingMat;

        // Make table clickable
        tableTop.actionManager = new BABYLON.ActionManager(scene);
        tableTop.actionManager.registerAction(
            new BABYLON.ExecuteCodeAction(BABYLON.ActionManager.OnPickTrigger, () => {
                AlfredCommand.openTerritories();
            })
        );
    },

    // Update zone markers on the holographic map
    updateZones(zones) {
        const scene = GameEngine.scene;
        // Clear old markers
        this.zoneMarkers.forEach(m => m.dispose());
        this.zoneMarkers = [];

        const mapScale = 5 / 300; // Map size / world size ratio
        const zonePositions = {
            'ZONE-HQ': { x: 0, z: 0 },
            'ZONE-NORTH': { x: 0, z: -80 },
            'ZONE-SOUTH': { x: 0, z: 80 },
            'ZONE-EAST': { x: 80, z: 0 },
            'ZONE-WEST': { x: -80, z: 0 },
            'ZONE-CYBER': { x: 50, z: -50 },
            'ZONE-MESA': { x: -50, z: -50 },
            'ZONE-DOCK': { x: 50, z: 50 },
            'ZONE-VAULT': { x: -50, z: 50 },
            'ZONE-SKY': { x: 0, z: -120 },
        };

        zones.forEach(zone => {
            const worldPos = zonePositions[zone.zone_code] || { x: 0, z: 0 };
            const mapPos = new BABYLON.Vector3(
                worldPos.x * mapScale,
                2.7,
                worldPos.z * mapScale
            );

            const deployed = parseInt(zone.deployed_agents) || 0;
            const color = deployed > 0
                ? new BABYLON.Color3(0.2, 1, 0.5)  // Green = your agents
                : new BABYLON.Color3(0.5, 0.5, 0.6); // Gray = unclaimed

            const marker = BABYLON.MeshBuilder.CreateSphere(`holoZone-${zone.zone_code}`, {
                diameter: 0.15, segments: 8
            }, scene);
            marker.position = mapPos;
            const mat = new BABYLON.StandardMaterial(`holoZone-${zone.zone_code}-mat`, scene);
            mat.emissiveColor = color;
            mat.diffuseColor = new BABYLON.Color3(0, 0, 0);
            marker.material = mat;
            marker.metadata = { type: 'holoZone', data: zone };

            this.zoneMarkers.push(marker);
        });
    },
};
