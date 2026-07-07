/* ═══════════════════════════════════════════════════════════════
   ALFRED COMMAND — VR Environments
   3D environments for the Command Center, bases, and zones
   ═══════════════════════════════════════════════════════════════ */

const Environments = {

    // Build the command center environment
    buildCommandCenter(scene) {
        const sg = GameEngine.shadowGenerator;

        // Central platform (elevated hexagonal command deck)
        const platform = BABYLON.MeshBuilder.CreateCylinder('platform', {
            diameter: 20, height: 0.5, tessellation: 6
        }, scene);
        platform.position.y = 0.25;
        const platMat = new BABYLON.StandardMaterial('platMat', scene);
        platMat.diffuseColor = new BABYLON.Color3(0.1, 0.13, 0.2);
        platMat.specularColor = new BABYLON.Color3(0.2, 0.25, 0.4);
        platMat.emissiveColor = new BABYLON.Color3(0.02, 0.03, 0.05);
        platform.material = platMat;
        platform.receiveShadows = true;

        // Edge glow ring
        const ring = BABYLON.MeshBuilder.CreateTorus('ringGlow', {
            diameter: 20, thickness: 0.15, tessellation: 64
        }, scene);
        ring.position.y = 0.5;
        const ringMat = new BABYLON.StandardMaterial('ringMat', scene);
        ringMat.emissiveColor = new BABYLON.Color3(0.2, 0.5, 1.0);
        ringMat.diffuseColor = new BABYLON.Color3(0, 0, 0);
        ring.material = ringMat;

        // Command pillars (6 around the hex)
        for (let i = 0; i < 6; i++) {
            const angle = (i / 6) * Math.PI * 2;
            const x = Math.cos(angle) * 9;
            const z = Math.sin(angle) * 9;

            const pillar = BABYLON.MeshBuilder.CreateCylinder(`pillar${i}`, {
                diameter: 0.6, height: 5, tessellation: 8
            }, scene);
            pillar.position = new BABYLON.Vector3(x, 2.5, z);
            const pillarMat = new BABYLON.StandardMaterial(`pillarMat${i}`, scene);
            pillarMat.diffuseColor = new BABYLON.Color3(0.12, 0.15, 0.25);
            pillarMat.emissiveColor = new BABYLON.Color3(0.03, 0.05, 0.1);
            pillar.material = pillarMat;
            sg.addShadowCaster(pillar);

            // Pillar top light
            const topLight = BABYLON.MeshBuilder.CreateSphere(`pillarLight${i}`, {
                diameter: 0.4, segments: 8
            }, scene);
            topLight.position = new BABYLON.Vector3(x, 5.2, z);
            const lightMat = new BABYLON.StandardMaterial(`lightMat${i}`, scene);
            const hue = i / 6;
            lightMat.emissiveColor = BABYLON.Color3.FromHSV(hue * 360, 0.7, 1.0);
            lightMat.diffuseColor = new BABYLON.Color3(0, 0, 0);
            topLight.material = lightMat;

            // Point light from pillar
            const pLight = new BABYLON.PointLight(`pLight${i}`,
                new BABYLON.Vector3(x, 5, z), scene
            );
            pLight.intensity = 0.3;
            pLight.diffuse = BABYLON.Color3.FromHSV(hue * 360, 0.5, 1.0);
            pLight.range = 15;
        }

        // Holographic dome (semi-transparent shell)
        const dome = BABYLON.MeshBuilder.CreateSphere('dome', {
            diameter: 22, segments: 32, slice: 0.5
        }, scene);
        dome.position.y = 0;
        const domeMat = new BABYLON.StandardMaterial('domeMat', scene);
        domeMat.diffuseColor = new BABYLON.Color3(0.1, 0.2, 0.4);
        domeMat.emissiveColor = new BABYLON.Color3(0.02, 0.05, 0.1);
        domeMat.alpha = 0.08;
        domeMat.backFaceCulling = false;
        domeMat.wireframe = true;
        dome.material = domeMat;

        // Surrounding structures (barracks, hangars)
        this._buildStructureCluster(scene, new BABYLON.Vector3(30, 0, 0), 'barracks');
        this._buildStructureCluster(scene, new BABYLON.Vector3(-30, 0, 0), 'hangar');
        this._buildStructureCluster(scene, new BABYLON.Vector3(0, 0, 30), 'comms');
        this._buildStructureCluster(scene, new BABYLON.Vector3(0, 0, -30), 'depot');

        // Animated particles — command center ambient
        const particleSystem = new BABYLON.ParticleSystem('ccParticles', 200, scene);
        particleSystem.createPointEmitter(
            new BABYLON.Vector3(-0.5, 1, -0.5),
            new BABYLON.Vector3(0.5, 1, 0.5)
        );
        particleSystem.emitter = new BABYLON.Vector3(0, 1, 0);
        particleSystem.minLifeTime = 2;
        particleSystem.maxLifeTime = 5;
        particleSystem.minSize = 0.02;
        particleSystem.maxSize = 0.08;
        particleSystem.emitRate = 30;
        particleSystem.color1 = new BABYLON.Color4(0.2, 0.5, 1, 0.3);
        particleSystem.color2 = new BABYLON.Color4(0.1, 1, 0.5, 0.2);
        particleSystem.colorDead = new BABYLON.Color4(0, 0, 0.2, 0);
        particleSystem.gravity = new BABYLON.Vector3(0, 0.05, 0);
        particleSystem.start();
    },

    _buildStructureCluster(scene, center, type) {
        const colors = {
            barracks: new BABYLON.Color3(0.15, 0.12, 0.08),
            hangar: new BABYLON.Color3(0.1, 0.1, 0.15),
            comms: new BABYLON.Color3(0.08, 0.12, 0.15),
            depot: new BABYLON.Color3(0.12, 0.1, 0.08),
        };
        const color = colors[type] || colors.barracks;

        // Main building
        const building = BABYLON.MeshBuilder.CreateBox(`${type}Main`, {
            width: 8, height: 4, depth: 6
        }, scene);
        building.position = center.add(new BABYLON.Vector3(0, 2, 0));
        const mat = new BABYLON.StandardMaterial(`${type}Mat`, scene);
        mat.diffuseColor = color;
        mat.specularColor = new BABYLON.Color3(0.1, 0.1, 0.1);
        building.material = mat;
        building.receiveShadows = true;
        GameEngine.shadowGenerator.addShadowCaster(building);

        // Roof
        const roof = BABYLON.MeshBuilder.CreateBox(`${type}Roof`, {
            width: 9, height: 0.3, depth: 7
        }, scene);
        roof.position = center.add(new BABYLON.Vector3(0, 4.15, 0));
        const roofMat = new BABYLON.StandardMaterial(`${type}RoofMat`, scene);
        roofMat.diffuseColor = color.scale(0.7);
        roof.material = roofMat;

        // Label
        GameEngine.createLabel(type, type.toUpperCase(), center, '#94a3b8');

        // Antenna for comms
        if (type === 'comms') {
            const antenna = BABYLON.MeshBuilder.CreateCylinder(`${type}Antenna`, {
                diameter: 0.15, height: 8
            }, scene);
            antenna.position = center.add(new BABYLON.Vector3(0, 8, 0));
            const antMat = new BABYLON.StandardMaterial(`${type}AntMat`, scene);
            antMat.emissiveColor = new BABYLON.Color3(0.3, 0.6, 1);
            antenna.material = antMat;

            // Blinking light
            const blink = BABYLON.MeshBuilder.CreateSphere(`${type}Blink`, { diameter: 0.3 }, scene);
            blink.position = center.add(new BABYLON.Vector3(0, 12, 0));
            const blinkMat = new BABYLON.StandardMaterial(`${type}BlinkMat`, scene);
            blinkMat.emissiveColor = new BABYLON.Color3(1, 0.2, 0.2);
            blink.material = blinkMat;

            // Animate blink
            let blinkOn = true;
            scene.registerBeforeRender(() => {
                const t = Math.floor(performance.now() / 1000);
                blinkMat.emissiveColor = t % 2 === 0
                    ? new BABYLON.Color3(1, 0.2, 0.2)
                    : new BABYLON.Color3(0.1, 0.02, 0.02);
            });
        }
    },

    // Build zone markers across the map based on real territory data
    buildTerritoryMarkers(scene, zones) {
        // Clear existing markers
        scene.meshes
            .filter(m => m.name.startsWith('zone-'))
            .forEach(m => m.dispose());

        const zonePositions = {
            'ZONE-HQ':    { x: 0,    z: 0 },
            'ZONE-NORTH': { x: 0,    z: -80 },
            'ZONE-SOUTH': { x: 0,    z: 80 },
            'ZONE-EAST':  { x: 80,   z: 0 },
            'ZONE-WEST':  { x: -80,  z: 0 },
            'ZONE-CYBER': { x: 50,   z: -50 },
            'ZONE-MESA':  { x: -50,  z: -50 },
            'ZONE-DOCK':  { x: 50,   z: 50 },
            'ZONE-VAULT': { x: -50,  z: 50 },
            'ZONE-SKY':   { x: 0,    z: -120 },
        };

        zones.forEach(zone => {
            const pos = zonePositions[zone.zone_code] || { x: Math.random() * 100 - 50, z: Math.random() * 100 - 50 };
            const position = new BABYLON.Vector3(pos.x, 1, pos.z);

            // Zone type determines color
            const typeColors = {
                outpost:  new BABYLON.Color3(0.3, 0.6, 0.3),
                base:     new BABYLON.Color3(0.3, 0.5, 0.8),
                fortress: new BABYLON.Color3(0.6, 0.3, 0.8),
                citadel:  new BABYLON.Color3(0.8, 0.6, 0.2),
                capital:  new BABYLON.Color3(0.9, 0.2, 0.2),
            };
            const color = typeColors[zone.zone_type] || typeColors.outpost;

            // Zone base
            const base = BABYLON.MeshBuilder.CreateCylinder(`zone-${zone.zone_code}`, {
                diameter: 8, height: 0.5, tessellation: 6
            }, scene);
            base.position = position;
            const baseMat = new BABYLON.StandardMaterial(`zone-${zone.zone_code}-mat`, scene);
            baseMat.diffuseColor = color.scale(0.5);
            baseMat.emissiveColor = color.scale(0.2);
            base.material = baseMat;
            base.receiveShadows = true;

            // Zone beacon
            const beacon = BABYLON.MeshBuilder.CreateCylinder(`zone-${zone.zone_code}-beacon`, {
                diameterTop: 0, diameterBottom: 1.5, height: 6, tessellation: 4
            }, scene);
            beacon.position = position.add(new BABYLON.Vector3(0, 3, 0));
            const beaconMat = new BABYLON.StandardMaterial(`zone-${zone.zone_code}-beaconMat`, scene);
            beaconMat.emissiveColor = color;
            beaconMat.diffuseColor = new BABYLON.Color3(0, 0, 0);
            beaconMat.alpha = 0.7;
            beacon.material = beaconMat;
            GameEngine.shadowGenerator.addShadowCaster(beacon);

            // Glow ring around zone
            const zoneRing = BABYLON.MeshBuilder.CreateTorus(`zone-${zone.zone_code}-ring`, {
                diameter: 10, thickness: 0.1, tessellation: 32
            }, scene);
            zoneRing.position = position.add(new BABYLON.Vector3(0, 0.3, 0));
            const ringMat = new BABYLON.StandardMaterial(`zone-${zone.zone_code}-ringMat`, scene);
            ringMat.emissiveColor = color;
            ringMat.diffuseColor = new BABYLON.Color3(0, 0, 0);
            ringMat.alpha = 0.5;
            zoneRing.material = ringMat;

            // Agent count indicator (if deployed)
            const deployed = parseInt(zone.deployed_agents) || 0;
            if (deployed > 0) {
                // Show agent dots around zone
                const dotCount = Math.min(20, Math.ceil(deployed / 100));
                for (let i = 0; i < dotCount; i++) {
                    const angle = (i / dotCount) * Math.PI * 2;
                    const dot = BABYLON.MeshBuilder.CreateSphere(`zone-${zone.zone_code}-agent${i}`, {
                        diameter: 0.3
                    }, scene);
                    dot.position = position.add(new BABYLON.Vector3(
                        Math.cos(angle) * 5, 0.5, Math.sin(angle) * 5
                    ));
                    const dotMat = new BABYLON.StandardMaterial(`zone-${zone.zone_code}-agentMat${i}`, scene);
                    dotMat.emissiveColor = new BABYLON.Color3(0.2, 1, 0.5);
                    dotMat.diffuseColor = new BABYLON.Color3(0, 0, 0);
                    dot.material = dotMat;
                }
            }

            // Label
            GameEngine.createLabel(`zone-${zone.zone_code}`, zone.zone_name, position,
                zone.deployed_agents > 0 ? '#10b981' : '#94a3b8');

            // Zone metadata
            base.metadata = { type: 'zone', data: zone };
            beacon.metadata = { type: 'zone', data: zone };

            // Animate beacon rotation
            scene.registerBeforeRender(() => {
                beacon.rotation.y += 0.005;
            });
        });
    },

    // Build player structures in the scene
    buildPlayerStructures(scene, structures) {
        structures.forEach((s, i) => {
            const offset = new BABYLON.Vector3(20 + i * 8, 0, 20);
            const structColors = {
                fob: new BABYLON.Color3(0.4, 0.3, 0.2),
                greenhouse: new BABYLON.Color3(0.1, 0.5, 0.1),
                medical: new BABYLON.Color3(0.8, 0.2, 0.2),
                comms_tower: new BABYLON.Color3(0.2, 0.4, 0.8),
                supply_depot: new BABYLON.Color3(0.5, 0.4, 0.2),
                safe_zone: new BABYLON.Color3(0.2, 0.8, 0.3),
                watchtower: new BABYLON.Color3(0.4, 0.4, 0.5),
                training: new BABYLON.Color3(0.5, 0.3, 0.5),
                vault: new BABYLON.Color3(0.3, 0.3, 0.4),
                sanctuary: new BABYLON.Color3(0.8, 0.7, 0.3),
            };
            const color = structColors[s.structure_type] || structColors.fob;

            const box = BABYLON.MeshBuilder.CreateBox(`struct-${s.id}`, {
                width: 4, height: 3, depth: 4
            }, scene);
            box.position = offset.add(new BABYLON.Vector3(0, 1.5, 0));
            const mat = new BABYLON.StandardMaterial(`struct-${s.id}-mat`, scene);
            mat.diffuseColor = color;
            mat.emissiveColor = color.scale(0.15);
            box.material = mat;
            GameEngine.shadowGenerator.addShadowCaster(box);

            GameEngine.createLabel(`struct-${s.id}`, s.structure_name || s.structure_type, offset, '#f59e0b');
        });
    },
};
