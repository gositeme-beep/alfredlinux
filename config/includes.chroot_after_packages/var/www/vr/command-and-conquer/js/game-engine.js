/* ═══════════════════════════════════════════════════════════════
   ALFRED COMMAND — Game Engine Core
   Babylon.js WebXR game engine with VR support
   ═══════════════════════════════════════════════════════════════ */

const GameEngine = {
    canvas: null,
    engine: null,
    scene: null,
    camera: null,
    xrHelper: null,
    shadowGenerator: null,
    glowLayer: null,

    // Game state
    state: {
        player: null,
        session: null,
        territories: [],
        zones: [],
        deployments: [],
        activeMissions: [],
        resources: {},
        structures: [],
    },

    async init() {
        this.canvas = document.getElementById('renderCanvas');
        this.engine = new BABYLON.Engine(this.canvas, true, {
            preserveDrawingBuffer: true,
            stencil: true,
            antialias: true,
        });

        await this.createScene();
        this.engine.runRenderLoop(() => this.scene.render());
        window.addEventListener('resize', () => this.engine.resize());
    },

    async createScene() {
        this.scene = new BABYLON.Scene(this.engine);
        this.scene.clearColor = new BABYLON.Color4(0.02, 0.03, 0.06, 1);
        this.scene.ambientColor = new BABYLON.Color3(0.1, 0.1, 0.15);
        this.scene.fogMode = BABYLON.Scene.FOGMODE_EXP2;
        this.scene.fogDensity = 0.001;
        this.scene.fogColor = new BABYLON.Color3(0.02, 0.03, 0.06);

        // Camera
        this.camera = new BABYLON.ArcRotateCamera('mainCam',
            -Math.PI / 2, Math.PI / 3, 50,
            new BABYLON.Vector3(0, 0, 0), this.scene
        );
        this.camera.attachControl(this.canvas, true);
        this.camera.lowerRadiusLimit = 5;
        this.camera.upperRadiusLimit = 200;
        this.camera.wheelPrecision = 10;
        this.camera.panningSensibility = 100;

        // Lighting
        const hemiLight = new BABYLON.HemisphericLight('hemi',
            new BABYLON.Vector3(0, 1, 0), this.scene
        );
        hemiLight.intensity = 0.4;
        hemiLight.diffuse = new BABYLON.Color3(0.6, 0.65, 0.8);

        const dirLight = new BABYLON.DirectionalLight('dir',
            new BABYLON.Vector3(-1, -2, -1), this.scene
        );
        dirLight.position = new BABYLON.Vector3(50, 100, 50);
        dirLight.intensity = 0.8;

        // Shadows
        this.shadowGenerator = new BABYLON.ShadowGenerator(2048, dirLight);
        this.shadowGenerator.useBlurExponentialShadowMap = true;

        // Glow
        this.glowLayer = new BABYLON.GlowLayer('glow', this.scene, {
            mainTextureFixedSize: 512,
            blurKernelSize: 32,
        });
        this.glowLayer.intensity = 0.6;

        // Skybox
        this.createSkybox();

        // Ground
        this.createGround();

        // Try WebXR
        await this.initXR();
    },

    createSkybox() {
        // Procedural starfield skybox
        const skyMat = new BABYLON.StandardMaterial('skyMat', this.scene);
        skyMat.backFaceCulling = false;
        skyMat.disableLighting = true;

        // Dark space color
        skyMat.emissiveColor = new BABYLON.Color3(0.01, 0.015, 0.03);
        skyMat.diffuseColor = new BABYLON.Color3(0, 0, 0);

        const skybox = BABYLON.MeshBuilder.CreateBox('skybox', { size: 1000 }, this.scene);
        skybox.material = skyMat;
        skybox.infiniteDistance = true;
        skybox.renderingGroupId = 0;

        // Particle stars
        const starCount = 2000;
        const starSPS = new BABYLON.SolidParticleSystem('stars', this.scene, { isPickable: false });
        const starModel = BABYLON.MeshBuilder.CreatePlane('starModel', { size: 0.3 }, this.scene);
        starSPS.addShape(starModel, starCount);
        starModel.dispose();

        const starMesh = starSPS.buildMesh();
        starMesh.material = new BABYLON.StandardMaterial('starMat', this.scene);
        starMesh.material.emissiveColor = new BABYLON.Color3(1, 1, 1);
        starMesh.material.disableLighting = true;
        starMesh.material.alpha = 0.8;

        starSPS.initParticles = () => {
            for (let i = 0; i < starSPS.nbParticles; i++) {
                const p = starSPS.particles[i];
                const r = 400 + Math.random() * 100;
                const theta = Math.random() * Math.PI * 2;
                const phi = Math.random() * Math.PI;
                p.position.x = r * Math.sin(phi) * Math.cos(theta);
                p.position.y = r * Math.cos(phi);
                p.position.z = r * Math.sin(phi) * Math.sin(theta);
                const s = 0.1 + Math.random() * 0.5;
                p.scaling = new BABYLON.Vector3(s, s, s);
                const brightness = 0.3 + Math.random() * 0.7;
                p.color = new BABYLON.Color4(brightness, brightness, brightness * 1.1, brightness);
            }
        };
        starSPS.initParticles();
        starSPS.setParticles();
        starSPS.isAlwaysVisible = true;
    },

    createGround() {
        // Main terrain — heightmap-style procedural ground
        const ground = BABYLON.MeshBuilder.CreateGround('terrain', {
            width: 500, height: 500,
            subdivisions: 64,
            updatable: true,
        }, this.scene);

        // Apply height displacement
        const positions = ground.getVerticesData(BABYLON.VertexBuffer.PositionKind);
        for (let i = 1; i < positions.length; i += 3) {
            const x = positions[i - 1];
            const z = positions[i + 1];
            // Gentle rolling terrain
            positions[i] = Math.sin(x * 0.02) * Math.cos(z * 0.02) * 3 +
                           Math.sin(x * 0.05 + z * 0.03) * 1.5 +
                           Math.cos(z * 0.01) * 2;
        }
        ground.updateVerticesData(BABYLON.VertexBuffer.PositionKind, positions);
        ground.computeWorldMatrix(true);

        // Create normals for lighting
        const normals = [];
        BABYLON.VertexData.ComputeNormals(positions, ground.getIndices(), normals);
        ground.updateVerticesData(BABYLON.VertexBuffer.NormalKind, normals);

        // Ground material
        const groundMat = new BABYLON.StandardMaterial('groundMat', this.scene);
        groundMat.diffuseColor = new BABYLON.Color3(0.08, 0.12, 0.08);
        groundMat.specularColor = new BABYLON.Color3(0.02, 0.02, 0.02);
        groundMat.emissiveColor = new BABYLON.Color3(0.01, 0.02, 0.01);
        ground.material = groundMat;
        ground.receiveShadows = true;
        ground.checkCollisions = true;

        // Grid overlay
        const gridMat = new BABYLON.GridMaterial('gridMat', this.scene);
        gridMat.majorUnitFrequency = 10;
        gridMat.minorUnitVisibility = 0.3;
        gridMat.gridRatio = 2;
        gridMat.opacity = 0.15;
        gridMat.mainColor = new BABYLON.Color3(0.1, 0.3, 0.6);
        gridMat.lineColor = new BABYLON.Color3(0.1, 0.3, 0.6);

        const gridPlane = BABYLON.MeshBuilder.CreateGround('grid', {
            width: 500, height: 500, subdivisions: 1,
        }, this.scene);
        gridPlane.material = gridMat;
        gridPlane.position.y = 0.1;

        this.ground = ground;
    },

    async initXR() {
        try {
            this.xrHelper = await this.scene.createDefaultXRExperienceAsync({
                floorMeshes: [this.ground],
                uiOptions: {
                    sessionMode: 'immersive-vr',
                    referenceSpaceType: 'local-floor',
                },
                optionalFeatures: ['hand-tracking'],
            });

            if (this.xrHelper.baseExperience) {
                document.getElementById('vrStatus').textContent = '🥽 VR Ready — Meta Quest 3 Detected';
                document.getElementById('vrButton').style.display = 'flex';

                this.xrHelper.baseExperience.onStateChangedObservable.add((state) => {
                    if (state === BABYLON.WebXRState.IN_XR) {
                        AlfredCommand.toast('Entering VR — Welcome, Commander');
                    }
                });
            }
        } catch (e) {
            document.getElementById('vrStatus').textContent = 'Desktop Mode — VR not available';
            document.getElementById('vrButton').style.display = 'none';
        }
    },

    toggleVR() {
        if (!this.xrHelper || !this.xrHelper.baseExperience) return;
        if (this.xrHelper.baseExperience.state === BABYLON.WebXRState.IN_XR) {
            this.xrHelper.baseExperience.exitXRAsync();
        } else {
            this.xrHelper.baseExperience.enterXRAsync('immersive-vr', 'local-floor');
        }
    },

    // Utility: create glowing marker at position
    createMarker(name, position, color, size = 1) {
        const marker = BABYLON.MeshBuilder.CreateSphere(name, { diameter: size, segments: 16 }, this.scene);
        marker.position = position;
        const mat = new BABYLON.StandardMaterial(name + 'Mat', this.scene);
        mat.emissiveColor = color;
        mat.diffuseColor = color;
        mat.alpha = 0.9;
        marker.material = mat;
        this.shadowGenerator.addShadowCaster(marker);
        return marker;
    },

    // Create a text billboard
    createLabel(name, text, position, color = '#3b82f6') {
        const plane = BABYLON.MeshBuilder.CreatePlane(name + 'Label', { width: 4, height: 1 }, this.scene);
        plane.position = position.add(new BABYLON.Vector3(0, 3, 0));
        plane.billboardMode = BABYLON.Mesh.BILLBOARDMODE_ALL;

        const texture = new BABYLON.GUI.AdvancedDynamicTexture.CreateForMesh(plane, 512, 128);
        const textBlock = new BABYLON.GUI.TextBlock();
        textBlock.text = text;
        textBlock.color = color;
        textBlock.fontSize = 36;
        textBlock.fontWeight = 'bold';
        textBlock.outlineWidth = 2;
        textBlock.outlineColor = '#000';
        texture.addControl(textBlock);

        return plane;
    },

    // Dispose scene for cleanup
    dispose() {
        if (this.scene) this.scene.dispose();
        if (this.engine) this.engine.dispose();
    }
};
