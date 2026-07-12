/**
 * ═══════════════════════════════════════════════════════════════
 * GRAVITY FIELD ENGINE — Quadratic Gravity Spacetime Simulator
 * ═══════════════════════════════════════════════════════════════
 *
 * Visualizes the action:
 *   S[φ] = ∫ d⁴x √(-g) [ R/(16πG) + L_matter(φ,∇φ) + αR² + βR_μνR^μν ]
 *
 * Physics:
 *   - Modified Schwarzschild with Yukawa corrections from higher-order gravity
 *   - Massive spin-0 mode (from αR²): m₀² = 1/(6α + 2β)
 *   - Massive spin-2 mode (from βR_μν R^μν): m₂² = -1/(2β)
 *   - Modified Newtonian potential: Φ(r) = -GM/r [1 + (1/3)e^{-m₀r} - (4/3)e^{-m₂r}]
 *   - Geodesic integration via RK4
 *   - Scalar field φ as radial standing wave
 *
 * By GoSiteMe — Danny William Perez
 */

const GravityFieldEngine = (() => {
    'use strict';

    // ── Physics Constants ──
    const PHYS = {
        G: 6.67430e-11,
        c: 299792458,
        hbar: 1.054571817e-34,
        k_B: 1.380649e-23,
        pi: Math.PI,
        twoPi: 2 * Math.PI
    };

    // ── Simulation units (natural: G=c=1, distances in Schwarzschild radii) ──
    const SIM = {
        // We work in units where r_s = 2GM/c² = 1 for M=1
        // All distances in units of r_s, all times in units of r_s/c
    };

    // ══════════════════════════════════════
    //  METRIC & CURVATURE
    // ══════════════════════════════════════

    /**
     * Modified gravitational potential including quadratic gravity corrections.
     * Φ(r) = -M/r [1 + (1/3)e^{-m₀r} - (4/3)e^{-m₂r}]
     */
    function modifiedPotential(r, M, alpha, beta) {
        if (r < 0.01) r = 0.01; // prevent singularity
        const newtonian = -M / r;

        // Mass scales from quadratic terms
        const denom0 = Math.abs(6 * alpha + 2 * beta);
        const denom2 = Math.abs(2 * beta);
        const m0 = denom0 > 1e-10 ? 1 / Math.sqrt(denom0) : 0;
        const m2 = denom2 > 1e-10 ? 1 / Math.sqrt(denom2) : 0;

        // Yukawa corrections
        let correction = 1;
        if (m0 > 0) correction += (1 / 3) * Math.exp(-m0 * r);
        if (m2 > 0) correction -= (4 / 3) * Math.exp(-m2 * r);

        return newtonian * correction;
    }

    /**
     * Effective radial force (negative gradient of potential).
     */
    function radialForce(r, M, alpha, beta) {
        const dr = 0.001;
        const phiPlus = modifiedPotential(r + dr, M, alpha, beta);
        const phiMinus = modifiedPotential(r - dr, M, alpha, beta);
        return -(phiPlus - phiMinus) / (2 * dr);
    }

    /**
     * Ricci scalar for Schwarzschild + corrections.
     * In standard GR, R=0 in vacuum. With quadratic gravity + scalar field:
     * R ≈ 8πG T (trace of stress-energy) + higher-order corrections
     */
    function ricciScalar(r, M, alpha, beta, phi0) {
        if (r < 0.01) r = 0.01;
        const R_grav = 2 * M / (r * r * r); // Kretschner-inspired curvature measure
        const R_alpha = alpha * R_grav * R_grav * 4; // R² enhancement
        const R_beta = beta * R_grav * R_grav * 2;   // R_μν R^μν contribution
        const R_matter = phi0 * phi0 * Math.exp(-r) / (r * r); // scalar field
        return R_grav + R_alpha + R_beta + R_matter;
    }

    /**
     * Energy density of the scalar field φ.
     * φ(r,t) = φ₀ sin(ωt) e^{-r/λ} / r  (spherical standing wave)
     */
    function scalarFieldDensity(r, t, phi0, omega) {
        if (r < 0.05) r = 0.05;
        const phi = phi0 * Math.sin(omega * t) * Math.exp(-r * 0.3) / r;
        const dphi_dr = -phi0 * Math.sin(omega * t) * Math.exp(-r * 0.3) * (0.3 * r + 1) / (r * r);
        const dphi_dt = phi0 * omega * Math.cos(omega * t) * Math.exp(-r * 0.3) / r;
        // T₀₀ = ½(∂φ/∂t)² + ½(∂φ/∂r)² + ½m²φ²
        return 0.5 * dphi_dt * dphi_dt + 0.5 * dphi_dr * dphi_dr + 0.5 * phi * phi;
    }

    // ══════════════════════════════════════
    //  GEODESIC INTEGRATOR (RK4)
    // ══════════════════════════════════════

    /**
     * Integrate geodesic equation in polar coords (r, θ) with effective potential.
     * d²r/dt² = -∂Φ_eff/∂r + L²/r³
     */
    function geodesicStep(state, dt, M, alpha, beta) {
        // state = { r, theta, vr, vtheta }
        const L = state.r * state.r * state.vtheta; // conserved angular momentum

        function derivatives(r, vr, theta, vtheta) {
            const rSafe = Math.max(r, 0.5); // stay outside "horizon"
            const fr = radialForce(rSafe, M, alpha, beta);
            const centrifugal = L * L / (rSafe * rSafe * rSafe);
            const ar = fr + centrifugal;
            const atheta = -2 * vr * vtheta / rSafe;
            return { dr: vr, dvr: ar, dtheta: vtheta, dvtheta: atheta };
        }

        // RK4 integration
        const k1 = derivatives(state.r, state.vr, state.theta, state.vtheta);
        const k2 = derivatives(
            state.r + 0.5 * dt * k1.dr,
            state.vr + 0.5 * dt * k1.dvr,
            state.theta + 0.5 * dt * k1.dtheta,
            state.vtheta + 0.5 * dt * k1.dvtheta
        );
        const k3 = derivatives(
            state.r + 0.5 * dt * k2.dr,
            state.vr + 0.5 * dt * k2.dvr,
            state.theta + 0.5 * dt * k2.dtheta,
            state.vtheta + 0.5 * dt * k2.dvtheta
        );
        const k4 = derivatives(
            state.r + dt * k3.dr,
            state.vr + dt * k3.dvr,
            state.theta + dt * k3.dtheta,
            state.vtheta + dt * k3.dvtheta
        );

        return {
            r: state.r + (dt / 6) * (k1.dr + 2 * k2.dr + 2 * k3.dr + k4.dr),
            vr: state.vr + (dt / 6) * (k1.dvr + 2 * k2.dvr + 2 * k3.dvr + k4.dvr),
            theta: state.theta + (dt / 6) * (k1.dtheta + 2 * k2.dtheta + 2 * k3.dtheta + k4.dtheta),
            vtheta: state.vtheta + (dt / 6) * (k1.dvtheta + 2 * k2.dvtheta + 2 * k3.dvtheta + k4.dvtheta)
        };
    }

    // ══════════════════════════════════════
    //  PARTICLE SYSTEM
    // ══════════════════════════════════════

    class Particle {
        constructor(r, theta, vr, vtheta, color, size, type) {
            this.r = r;
            this.theta = theta;
            this.vr = vr;
            this.vtheta = vtheta;
            this.color = color;
            this.size = size || 3;
            this.type = type || 'matter'; // 'matter', 'photon', 'energy'
            this.trail = [];
            this.maxTrail = 120;
            this.age = 0;
            this.alive = true;
            this.sparkTimer = 0;
            this.energy = 1;
        }

        update(dt, M, alpha, beta) {
            const state = geodesicStep(
                { r: this.r, theta: this.theta, vr: this.vr, vtheta: this.vtheta },
                dt, M, alpha, beta
            );
            this.r = state.r;
            this.theta = state.theta;
            this.vr = state.vr;
            this.vtheta = state.vtheta;
            this.age += dt;

            // Store trail
            this.trail.push({ r: this.r, theta: this.theta });
            if (this.trail.length > this.maxTrail) this.trail.shift();

            // Spark effect in high curvature
            const R = ricciScalar(this.r, M, alpha, beta, 0);
            if (R > 5) {
                this.sparkTimer = 8;
                this.energy = Math.min(3, this.energy + 0.1);
            }
            if (this.sparkTimer > 0) this.sparkTimer--;

            // Absorbed if too close
            if (this.r < 0.4) {
                this.alive = false;
            }
            // Escaped if too far
            if (this.r > 50) {
                this.alive = false;
            }
        }

        getXY(scale, cx, cy) {
            const x = cx + this.r * scale * Math.cos(this.theta);
            const y = cy + this.r * scale * Math.sin(this.theta);
            return { x, y };
        }
    }

    // ══════════════════════════════════════
    //  SPARK EFFECT SYSTEM
    // ══════════════════════════════════════

    class Spark {
        constructor(x, y, color) {
            this.x = x;
            this.y = y;
            this.vx = (Math.random() - 0.5) * 8;
            this.vy = (Math.random() - 0.5) * 8;
            this.life = 1.0;
            this.decay = 0.02 + Math.random() * 0.04;
            this.color = color || '#00D4FF';
            this.size = 1 + Math.random() * 3;
        }

        update() {
            this.x += this.vx;
            this.y += this.vy;
            this.vx *= 0.96;
            this.vy *= 0.96;
            this.life -= this.decay;
            return this.life > 0;
        }
    }

    // ══════════════════════════════════════
    //  GRAVITATIONAL WAVE RIPPLE
    // ══════════════════════════════════════

    class Ripple {
        constructor(cx, cy) {
            this.cx = cx;
            this.cy = cy;
            this.radius = 0;
            this.speed = 3;
            this.life = 1.0;
            this.decay = 0.008;
        }

        update() {
            this.radius += this.speed;
            this.life -= this.decay;
            return this.life > 0;
        }
    }

    // ══════════════════════════════════════
    //  MAIN SIMULATOR
    // ══════════════════════════════════════

    class SpacetimeSimulator {
        constructor(canvasId) {
            this.canvas = document.getElementById(canvasId);
            if (!this.canvas) return;
            this.ctx = this.canvas.getContext('2d');
            this.running = false;
            this.time = 0;
            this.dt = 0.03;
            this.speed = 1;
            this.frameId = null;

            // Physics parameters
            this.M = 3;          // central mass
            this.alpha = 0.5;    // R² coupling
            this.beta = 0.2;     // R_μν R^μν coupling
            this.phi0 = 1.0;     // scalar field amplitude
            this.omega = 2.0;    // scalar field frequency

            // Visual
            this.particles = [];
            this.sparks = [];
            this.ripples = [];
            this.scale = 30;     // pixels per unit radius
            this.showGrid = true;
            this.showField = true;
            this.showCurvature = true;
            this.showFormula = true;
            this.gridPulse = 0;
            this.inflationPulse = 0;
            this.inflationPhase = 0;

            // Stats
            this.stats = {
                R_center: 0,
                energy_total: 0,
                particles_alive: 0,
                potential_min: 0,
                m0: 0,
                m2: 0
            };

            this.resize();
            this._initParticles();

            window.addEventListener('resize', () => this.resize());
        }

        resize() {
            const rect = this.canvas.parentElement.getBoundingClientRect();
            this.canvas.width = rect.width * (window.devicePixelRatio || 1);
            this.canvas.height = rect.height * (window.devicePixelRatio || 1);
            this.canvas.style.width = rect.width + 'px';
            this.canvas.style.height = rect.height + 'px';
            this.ctx.setTransform(window.devicePixelRatio || 1, 0, 0, window.devicePixelRatio || 1, 0, 0);
            this.w = rect.width;
            this.h = rect.height;
            this.cx = this.w / 2;
            this.cy = this.h / 2;
            this.scale = Math.min(this.w, this.h) / 30;
        }

        _initParticles() {
            this.particles = [];
            const colors = ['#00D4FF', '#7D00FF', '#FF3366', '#00FF88', '#FFB800', '#ec4899'];

            // Orbiting particles at various radii
            for (let i = 0; i < 18; i++) {
                const r = 3 + Math.random() * 8;
                const theta = Math.random() * PHYS.twoPi;
                const vOrbit = Math.sqrt(this.M / r) * (0.8 + Math.random() * 0.4);
                const vr = (Math.random() - 0.5) * 0.15;
                const color = colors[i % colors.length];
                const size = 2 + Math.random() * 3;
                this.particles.push(new Particle(r, theta, vr, vOrbit / r, color, size, 'matter'));
            }

            // Photons (high speed, no mass)
            for (let i = 0; i < 6; i++) {
                const r = 1.5 + Math.random() * 3;
                const theta = Math.random() * PHYS.twoPi;
                const speed = 1.5;
                const angle = theta + Math.PI / 2 + (Math.random() - 0.5) * 0.3;
                this.particles.push(new Particle(
                    r, theta,
                    speed * Math.cos(angle - theta) * 0.1,
                    speed / r,
                    '#FFFFFF', 2, 'photon'
                ));
            }

            // Energy sparks near the mass
            for (let i = 0; i < 8; i++) {
                const r = 1 + Math.random() * 2;
                const theta = Math.random() * PHYS.twoPi;
                const vOrbit = Math.sqrt(this.M / r) * (0.6 + Math.random() * 0.8);
                this.particles.push(new Particle(r, theta, (Math.random() - 0.5) * 0.5, vOrbit / r, '#FFD700', 1.5, 'energy'));
            }
        }

        // ── RENDERING ──

        _drawBackground() {
            const ctx = this.ctx;
            // Deep space gradient
            const grad = ctx.createRadialGradient(this.cx, this.cy, 0, this.cx, this.cy, this.w * 0.7);
            grad.addColorStop(0, '#0a0816');
            grad.addColorStop(0.5, '#060610');
            grad.addColorStop(1, '#020208');
            ctx.fillStyle = grad;
            ctx.fillRect(0, 0, this.w, this.h);
        }

        _drawCurvatureField() {
            if (!this.showCurvature) return;
            const ctx = this.ctx;
            const maxR = 14;
            const step = 3;

            for (let px = 0; px < this.w; px += step) {
                for (let py = 0; py < this.h; py += step) {
                    const dx = px - this.cx;
                    const dy = py - this.cy;
                    const dist = Math.sqrt(dx * dx + dy * dy) / this.scale;
                    if (dist > maxR || dist < 0.3) continue;

                    const R = ricciScalar(dist, this.M, this.alpha, this.beta, this.phi0);
                    const intensity = Math.min(1, R * 0.15);

                    if (intensity > 0.01) {
                        // Blend between cyan (Einstein) and purple (quadratic corrections)
                        const correctionStrength = (this.alpha + this.beta) * 2;
                        const r = Math.floor(lerp(0, 125, Math.min(1, correctionStrength)) * intensity);
                        const g = Math.floor(lerp(212, 0, Math.min(1, correctionStrength)) * intensity * 0.5);
                        const b = Math.floor(lerp(255, 255, Math.min(1, correctionStrength)) * intensity);

                        ctx.fillStyle = `rgba(${r},${g},${b},${intensity * 0.25})`;
                        ctx.fillRect(px, py, step, step);
                    }
                }
            }
        }

        _drawScalarField() {
            if (!this.showField) return;
            const ctx = this.ctx;
            const maxR = 12;
            const step = 4;

            for (let px = 0; px < this.w; px += step) {
                for (let py = 0; py < this.h; py += step) {
                    const dx = px - this.cx;
                    const dy = py - this.cy;
                    const dist = Math.sqrt(dx * dx + dy * dy) / this.scale;
                    if (dist > maxR || dist < 0.3) continue;

                    const rho = scalarFieldDensity(dist, this.time, this.phi0, this.omega);
                    const intensity = Math.min(1, rho * 8);

                    if (intensity > 0.02) {
                        ctx.fillStyle = `rgba(125,0,255,${intensity * 0.15})`;
                        ctx.fillRect(px, py, step, step);
                    }
                }
            }
        }

        _drawSpacetimeGrid() {
            if (!this.showGrid) return;
            const ctx = this.ctx;

            // Pulsing grid alpha from inflation term
            const gridAlpha = 0.15 + 0.05 * Math.sin(this.inflationPhase);

            // Radial circles (constant r)
            for (let rr = 1; rr <= 14; rr += 1) {
                ctx.beginPath();

                // Warp the circles based on curvature
                const segments = 120;
                for (let i = 0; i <= segments; i++) {
                    const angle = (i / segments) * PHYS.twoPi;
                    // The actual plotted radius warps due to gravity
                    const Phi = modifiedPotential(rr, this.M, this.alpha, this.beta);
                    const warp = 1 + Phi * 0.3; // spacetime compression
                    const plotR = rr * warp * this.scale;
                    const x = this.cx + plotR * Math.cos(angle);
                    const y = this.cy + plotR * Math.sin(angle);
                    if (i === 0) ctx.moveTo(x, y);
                    else ctx.lineTo(x, y);
                }
                ctx.closePath();

                const proxGlow = Math.max(0, 1 - rr / 5);
                ctx.strokeStyle = `rgba(0,212,255,${gridAlpha + proxGlow * 0.2})`;
                ctx.lineWidth = rr <= 2 ? 1.5 : 0.7;
                ctx.stroke();
            }

            // Angular lines (constant θ)
            const angLines = 24;
            for (let i = 0; i < angLines; i++) {
                const angle = (i / angLines) * PHYS.twoPi;
                ctx.beginPath();

                for (let rr = 0.5; rr <= 14; rr += 0.3) {
                    const Phi = modifiedPotential(rr, this.M, this.alpha, this.beta);
                    const warp = 1 + Phi * 0.3;
                    const plotR = rr * warp * this.scale;
                    const x = this.cx + plotR * Math.cos(angle);
                    const y = this.cy + plotR * Math.sin(angle);
                    if (rr <= 0.5) ctx.moveTo(x, y);
                    else ctx.lineTo(x, y);
                }

                ctx.strokeStyle = `rgba(0,212,255,${gridAlpha * 0.5})`;
                ctx.lineWidth = 0.5;
                ctx.stroke();
            }
        }

        _drawCentralMass() {
            const ctx = this.ctx;

            // Event horizon glow
            const horizonR = Math.max(8, this.M * 3 * this.scale * 0.15);

            // Outer glow
            const outerGlow = ctx.createRadialGradient(this.cx, this.cy, 0, this.cx, this.cy, horizonR * 4);
            outerGlow.addColorStop(0, 'rgba(125,0,255,0.3)');
            outerGlow.addColorStop(0.3, 'rgba(0,212,255,0.15)');
            outerGlow.addColorStop(0.6, 'rgba(0,212,255,0.05)');
            outerGlow.addColorStop(1, 'rgba(0,0,0,0)');
            ctx.fillStyle = outerGlow;
            ctx.beginPath();
            ctx.arc(this.cx, this.cy, horizonR * 4, 0, PHYS.twoPi);
            ctx.fill();

            // Accretion disk glow (rotating)
            ctx.save();
            ctx.translate(this.cx, this.cy);
            ctx.rotate(this.time * 0.5);
            const diskGrad = ctx.createRadialGradient(0, 0, horizonR * 0.5, 0, 0, horizonR * 2.5);
            diskGrad.addColorStop(0, 'rgba(255,180,0,0.0)');
            diskGrad.addColorStop(0.3, 'rgba(255,120,0,0.15)');
            diskGrad.addColorStop(0.5, 'rgba(255,60,40,0.1)');
            diskGrad.addColorStop(1, 'rgba(255,0,100,0.0)');

            ctx.fillStyle = diskGrad;
            ctx.beginPath();
            ctx.ellipse(0, 0, horizonR * 2.5, horizonR * 1.2, 0, 0, PHYS.twoPi);
            ctx.fill();
            ctx.restore();

            // Inner core — pulsing
            const pulse = 1 + 0.15 * Math.sin(this.time * 3);
            const coreGrad = ctx.createRadialGradient(this.cx, this.cy, 0, this.cx, this.cy, horizonR * pulse);
            coreGrad.addColorStop(0, '#FFFFFF');
            coreGrad.addColorStop(0.2, '#00D4FF');
            coreGrad.addColorStop(0.5, '#7D00FF');
            coreGrad.addColorStop(0.8, 'rgba(125,0,255,0.3)');
            coreGrad.addColorStop(1, 'rgba(0,0,0,0)');
            ctx.fillStyle = coreGrad;
            ctx.beginPath();
            ctx.arc(this.cx, this.cy, horizonR * pulse, 0, PHYS.twoPi);
            ctx.fill();

            // Bright center point
            ctx.fillStyle = '#fff';
            ctx.shadowColor = '#00D4FF';
            ctx.shadowBlur = 20;
            ctx.beginPath();
            ctx.arc(this.cx, this.cy, 3, 0, PHYS.twoPi);
            ctx.fill();
            ctx.shadowBlur = 0;
        }

        _drawParticles() {
            const ctx = this.ctx;

            for (const p of this.particles) {
                if (!p.alive) continue;
                const pos = p.getXY(this.scale, this.cx, this.cy);

                // Draw trail
                if (p.trail.length > 2) {
                    ctx.beginPath();
                    for (let i = 0; i < p.trail.length; i++) {
                        const t = p.trail[i];
                        const tx = this.cx + t.r * this.scale * Math.cos(t.theta);
                        const ty = this.cy + t.r * this.scale * Math.sin(t.theta);
                        if (i === 0) ctx.moveTo(tx, ty);
                        else ctx.lineTo(tx, ty);
                    }
                    const trailAlpha = p.type === 'photon' ? 0.4 : 0.25;
                    ctx.strokeStyle = p.color.replace(')', `,${trailAlpha})`).replace('rgb', 'rgba').replace('##', '#');
                    // Use hex-to-rgba for trail
                    ctx.globalAlpha = trailAlpha;
                    ctx.strokeStyle = p.color;
                    ctx.lineWidth = p.type === 'photon' ? 1 : 0.8;
                    ctx.stroke();
                    ctx.globalAlpha = 1;
                }

                // Particle glow
                const glowSize = p.size * (2 + p.energy);
                const glow = ctx.createRadialGradient(pos.x, pos.y, 0, pos.x, pos.y, glowSize);
                glow.addColorStop(0, p.color);
                glow.addColorStop(0.5, p.color + '80');
                glow.addColorStop(1, p.color + '00');
                ctx.fillStyle = glow;
                ctx.beginPath();
                ctx.arc(pos.x, pos.y, glowSize, 0, PHYS.twoPi);
                ctx.fill();

                // Core dot
                ctx.fillStyle = '#FFFFFF';
                ctx.beginPath();
                ctx.arc(pos.x, pos.y, p.size * 0.5, 0, PHYS.twoPi);
                ctx.fill();

                // Spark burst in high curvature
                if (p.sparkTimer > 0) {
                    for (let s = 0; s < 3; s++) {
                        this.sparks.push(new Spark(pos.x, pos.y, p.color));
                    }
                }
            }
        }

        _drawSparks() {
            const ctx = this.ctx;
            for (const spark of this.sparks) {
                const alpha = spark.life;
                ctx.fillStyle = spark.color;
                ctx.globalAlpha = alpha;
                ctx.shadowColor = spark.color;
                ctx.shadowBlur = 6;
                ctx.beginPath();
                ctx.arc(spark.x, spark.y, spark.size * spark.life, 0, PHYS.twoPi);
                ctx.fill();
            }
            ctx.globalAlpha = 1;
            ctx.shadowBlur = 0;
        }

        _drawRipples() {
            const ctx = this.ctx;
            for (const ripple of this.ripples) {
                ctx.beginPath();
                ctx.arc(ripple.cx, ripple.cy, ripple.radius, 0, PHYS.twoPi);
                ctx.strokeStyle = `rgba(0,212,255,${ripple.life * 0.3})`;
                ctx.lineWidth = 2 * ripple.life;
                ctx.stroke();

                // Secondary purple ripple slightly behind
                if (ripple.radius > 10) {
                    ctx.beginPath();
                    ctx.arc(ripple.cx, ripple.cy, ripple.radius - 8, 0, PHYS.twoPi);
                    ctx.strokeStyle = `rgba(125,0,255,${ripple.life * 0.2})`;
                    ctx.lineWidth = 1.5 * ripple.life;
                    ctx.stroke();
                }
            }
        }

        _drawInflationWave() {
            if (this.alpha < 0.01) return;
            const ctx = this.ctx;
            const waveR = (this.inflationPulse % 400);
            const life = 1 - waveR / 400;

            if (life > 0) {
                ctx.beginPath();
                ctx.arc(this.cx, this.cy, waveR, 0, PHYS.twoPi);
                ctx.strokeStyle = `rgba(255,215,0,${life * 0.2 * this.alpha})`;
                ctx.lineWidth = 3 * life;
                ctx.stroke();

                // Inner ring
                ctx.beginPath();
                ctx.arc(this.cx, this.cy, waveR * 0.9, 0, PHYS.twoPi);
                ctx.strokeStyle = `rgba(255,140,0,${life * 0.15 * this.alpha})`;
                ctx.lineWidth = 2 * life;
                ctx.stroke();
            }
        }

        _drawActionFormula() {
            if (!this.showFormula) return;
            const ctx = this.ctx;
            const y = 30;
            ctx.textAlign = 'center';
            ctx.font = '600 14px "SF Mono", "Fira Code", monospace';

            // The action formula with color-coded terms
            const terms = [
                { text: 'S[φ] = ∫ d⁴x √(-g) [', color: '#a8b2d1' },
                { text: ' R/(16πG)', color: '#00D4FF' },
                { text: ' + ', color: '#a8b2d1' },
                { text: 'ℒ(φ,∇φ)', color: '#ec4899' },
                { text: ' + ', color: '#a8b2d1' },
                { text: 'αR²', color: '#FFB800' },
                { text: ' + ', color: '#a8b2d1' },
                { text: 'βR', color: '#00FF88' },
                { text: 'μν', color: '#00FF88', sub: true },
                { text: 'R', color: '#00FF88' },
                { text: 'μν', color: '#00FF88', sub: true },
                { text: ' ]', color: '#a8b2d1' }
            ];

            // Background bar
            ctx.fillStyle = 'rgba(6,6,18,0.85)';
            ctx.fillRect(0, 0, this.w, 50);
            ctx.fillStyle = 'rgba(0,212,255,0.05)';
            ctx.fillRect(0, 48, this.w, 2);

            // Compute total width
            ctx.font = '600 14px "SF Mono", "Fira Code", monospace';
            let totalW = 0;
            for (const t of terms) {
                if (t.sub) {
                    ctx.font = '600 10px "SF Mono", "Fira Code", monospace';
                } else {
                    ctx.font = '600 14px "SF Mono", "Fira Code", monospace';
                }
                totalW += ctx.measureText(t.text).width;
            }

            let xPos = (this.w - totalW) / 2;
            for (const t of terms) {
                if (t.sub) {
                    ctx.font = '600 10px "SF Mono", "Fira Code", monospace';
                    ctx.fillStyle = t.color;
                    ctx.textAlign = 'left';
                    ctx.fillText(t.text, xPos, y + 5);
                    xPos += ctx.measureText(t.text).width;
                } else {
                    ctx.font = '600 14px "SF Mono", "Fira Code", monospace';
                    ctx.fillStyle = t.color;
                    ctx.textAlign = 'left';

                    // Glow effect on active terms
                    ctx.shadowColor = t.color;
                    ctx.shadowBlur = 8;
                    ctx.fillText(t.text, xPos, y);
                    ctx.shadowBlur = 0;
                    xPos += ctx.measureText(t.text).width;
                }
            }
        }

        _drawLegend() {
            const ctx = this.ctx;
            const x = 15;
            let y = 65;
            const lineH = 18;

            ctx.font = '500 11px Inter, sans-serif';
            ctx.textAlign = 'left';

            const items = [
                { color: '#00D4FF', label: 'R/(16πG)  Einstein-Hilbert (gravity)' },
                { color: '#ec4899', label: 'ℒ(φ,∇φ)   Scalar field (matter)' },
                { color: '#FFB800', label: 'αR²        Starobinsky inflation' },
                { color: '#00FF88', label: 'βRμνRμν    Quantum corrections' }
            ];

            ctx.fillStyle = 'rgba(6,6,18,0.7)';
            ctx.fillRect(5, y - 12, 270, items.length * lineH + 10);

            for (const item of items) {
                // Color dot
                ctx.fillStyle = item.color;
                ctx.shadowColor = item.color;
                ctx.shadowBlur = 6;
                ctx.beginPath();
                ctx.arc(x + 4, y - 3, 4, 0, PHYS.twoPi);
                ctx.fill();
                ctx.shadowBlur = 0;

                // Label
                ctx.fillStyle = '#a8b2d1';
                ctx.fillText(item.label, x + 16, y);
                y += lineH;
            }
        }

        _drawStats() {
            const ctx = this.ctx;
            const x = this.w - 240;
            let y = 65;
            const lineH = 17;

            ctx.font = '500 11px "SF Mono", monospace';
            ctx.textAlign = 'left';

            const s = this.stats;
            const items = [
                { label: 'R(center)', value: s.R_center.toFixed(4), color: '#00D4FF' },
                { label: 'α (R²)',    value: this.alpha.toFixed(3), color: '#FFB800' },
                { label: 'β (RμνRμν)', value: this.beta.toFixed(3), color: '#00FF88' },
                { label: 'M (mass)',   value: this.M.toFixed(2), color: '#e8e8f0' },
                { label: 'φ₀ (field)', value: this.phi0.toFixed(2), color: '#ec4899' },
                { label: 'particles',  value: String(s.particles_alive), color: '#e8e8f0' },
                { label: 'm₀ (spin-0)', value: s.m0 > 0 ? s.m0.toFixed(3) : '∞', color: '#FFB800' },
                { label: 'm₂ (spin-2)', value: s.m2 > 0 ? s.m2.toFixed(3) : '∞', color: '#00FF88' }
            ];

            ctx.fillStyle = 'rgba(6,6,18,0.7)';
            ctx.fillRect(x - 10, y - 12, 245, items.length * lineH + 10);

            for (const item of items) {
                ctx.fillStyle = '#6a7a8a';
                ctx.fillText(item.label, x, y);
                ctx.fillStyle = item.color;
                ctx.fillText(item.value, x + 130, y);
                y += lineH;
            }
        }

        // ── SIMULATION LOOP ──

        _update() {
            const dt = this.dt * this.speed;
            this.time += dt;
            this.inflationPhase += dt * 0.8;
            this.inflationPulse += 2 * this.alpha;

            // Update particles
            for (const p of this.particles) {
                if (p.alive) p.update(dt, this.M, this.alpha, this.beta);
            }

            // Remove dead, respawn occasionally
            this.particles = this.particles.filter(p => p.alive);
            if (this.particles.length < 20 && Math.random() < 0.08) {
                this._spawnParticle();
            }

            // Update sparks
            this.sparks = this.sparks.filter(s => s.update());

            // Update ripples
            this.ripples = this.ripples.filter(r => r.update());

            // Spawn ripples from β term (gravitational wave-like)
            if (this.beta > 0.01 && Math.random() < 0.02 * this.beta) {
                this.ripples.push(new Ripple(this.cx, this.cy));
            }

            // Random energy sparks near center
            if (Math.random() < 0.1) {
                const angle = Math.random() * PHYS.twoPi;
                const dist = (1 + Math.random() * 2) * this.scale;
                this.sparks.push(new Spark(
                    this.cx + dist * Math.cos(angle),
                    this.cy + dist * Math.sin(angle),
                    Math.random() < 0.5 ? '#00D4FF' : '#7D00FF'
                ));
            }

            // Update stats
            this.stats.R_center = ricciScalar(1, this.M, this.alpha, this.beta, this.phi0);
            this.stats.particles_alive = this.particles.length;
            this.stats.potential_min = modifiedPotential(1, this.M, this.alpha, this.beta);

            const d0 = Math.abs(6 * this.alpha + 2 * this.beta);
            const d2 = Math.abs(2 * this.beta);
            this.stats.m0 = d0 > 1e-10 ? 1 / Math.sqrt(d0) : 0;
            this.stats.m2 = d2 > 1e-10 ? 1 / Math.sqrt(d2) : 0;
        }

        _spawnParticle() {
            const colors = ['#00D4FF', '#7D00FF', '#FF3366', '#00FF88', '#FFB800', '#ec4899', '#FFFFFF'];
            const r = 5 + Math.random() * 7;
            const theta = Math.random() * PHYS.twoPi;
            const vOrbit = Math.sqrt(this.M / r) * (0.7 + Math.random() * 0.6);
            const vr = (Math.random() - 0.5) * 0.2;
            const color = colors[Math.floor(Math.random() * colors.length)];
            const types = ['matter', 'matter', 'matter', 'photon', 'energy'];
            const type = types[Math.floor(Math.random() * types.length)];
            const size = type === 'photon' ? 2 : (2 + Math.random() * 3);
            this.particles.push(new Particle(r, theta, vr, vOrbit / r, color, size, type));
        }

        _render() {
            this._drawBackground();
            this._drawCurvatureField();
            this._drawScalarField();
            this._drawSpacetimeGrid();
            this._drawInflationWave();
            this._drawRipples();
            this._drawCentralMass();
            this._drawParticles();
            this._drawSparks();
            this._drawActionFormula();
            this._drawLegend();
            this._drawStats();
        }

        _loop() {
            if (!this.running) return;
            this._update();
            this._render();
            this.frameId = requestAnimationFrame(() => this._loop());
        }

        start() {
            if (this.running) return;
            this.running = true;
            this._loop();
        }

        stop() {
            this.running = false;
            if (this.frameId) cancelAnimationFrame(this.frameId);
        }

        reset() {
            this.time = 0;
            this.sparks = [];
            this.ripples = [];
            this.inflationPulse = 0;
            this._initParticles();
        }

        setMass(v) { this.M = v; }
        setAlpha(v) { this.alpha = v; this.inflationPulse = 0; }
        setBeta(v) { this.beta = v; }
        setPhi0(v) { this.phi0 = v; }
        setSpeed(v) { this.speed = v; }
        setOmega(v) { this.omega = v; }

        toggleGrid() { this.showGrid = !this.showGrid; }
        toggleField() { this.showField = !this.showField; }
        toggleCurvature() { this.showCurvature = !this.showCurvature; }
        toggleFormula() { this.showFormula = !this.showFormula; }

        // Preset scenarios
        loadPreset(name) {
            switch (name) {
                case 'pure-einstein':
                    this.M = 3; this.alpha = 0; this.beta = 0; this.phi0 = 0;
                    break;
                case 'starobinsky-inflation':
                    this.M = 2; this.alpha = 2.0; this.beta = 0; this.phi0 = 0.5;
                    break;
                case 'stelle-gravity':
                    this.M = 3; this.alpha = 0.5; this.beta = 0.8; this.phi0 = 0;
                    break;
                case 'full-action':
                    this.M = 3; this.alpha = 0.5; this.beta = 0.2; this.phi0 = 1.0;
                    break;
                case 'strong-quantum':
                    this.M = 4; this.alpha = 1.5; this.beta = 1.5; this.phi0 = 2.0;
                    break;
                case 'black-hole':
                    this.M = 8; this.alpha = 0.1; this.beta = 0.1; this.phi0 = 0.3;
                    break;
                default:
                    break;
            }
            this.reset();
        }
    }

    // ── UTILITY ──
    function lerp(a, b, t) { return a + (b - a) * t; }

    // ── FORMULAS (for display / educational panel) ──
    const GRAVITY_FORMULAS = {
        einsteinHilbert: {
            name: 'Einstein-Hilbert Action',
            formula: 'S_EH = (1/16πG) ∫ R √(-g) d⁴x',
            description: 'Standard GR — curvature R from mass-energy.',
            calc: (M, r) => -(2 * M) / (r * r * r)
        },
        ricciSquared: {
            name: 'R² (Starobinsky)',
            formula: 'S_R² = α ∫ R² √(-g) d⁴x',
            description: 'Drives cosmic inflation. Best-fit to Planck CMB data.',
            calc: (R, alpha) => alpha * R * R
        },
        ricciTensorSquared: {
            name: 'Rμν Rμν (Stelle)',
            formula: 'S_Ric = β ∫ RμνRμν √(-g) d⁴x',
            description: 'Makes gravity renormalizable. Introduces massive spin-2 mode.',
            calc: (R, beta) => beta * R * R * 0.5
        },
        modifiedPotential: {
            name: 'Modified Newtonian Potential',
            formula: 'Φ(r) = -GM/r [1 + ⅓e^(-m₀r) - ⁴⁄₃e^(-m₂r)]',
            description: 'Yukawa corrections from R² + RμνRμν terms.',
            calc: (r, M, alpha, beta) => modifiedPotential(r, M, alpha, beta)
        },
        massiveSpin0: {
            name: 'Massive Scalar (spin-0)',
            formula: 'm₀² = 1 / (6α + 2β)',
            description: 'From R² term. Mediates attractive Yukawa force.',
            calc: (alpha, beta) => {
                const d = Math.abs(6 * alpha + 2 * beta);
                return d > 1e-10 ? 1 / Math.sqrt(d) : Infinity;
            }
        },
        massiveSpin2: {
            name: 'Massive Graviton (spin-2)',
            formula: 'm₂² = -1 / (2β)',
            description: 'From RμνRμν. Ghost mode — makes theory renormalizable.',
            calc: (beta) => {
                const d = Math.abs(2 * beta);
                return d > 1e-10 ? 1 / Math.sqrt(d) : Infinity;
            }
        },
        scalarFieldLagrangian: {
            name: 'Scalar Field Lagrangian',
            formula: 'ℒ = ½(∂μφ)(∂μφ) - ½m²φ²',
            description: 'Klein-Gordon scalar field coupling to spacetime.',
            calc: (phi, dphi, m) => 0.5 * dphi * dphi - 0.5 * m * m * phi * phi
        },
        geodesicEquation: {
            name: 'Geodesic Equation',
            formula: 'd²xμ/dτ² + Γμαβ (dxα/dτ)(dxβ/dτ) = 0',
            description: 'Free-fall through curved spacetime. Particles follow these paths.',
            calc: null
        }
    };

    // ── EXPORT ──
    return {
        PHYS,
        SpacetimeSimulator,
        Particle,
        Spark,
        Ripple,
        modifiedPotential,
        radialForce,
        ricciScalar,
        scalarFieldDensity,
        geodesicStep,
        GRAVITY_FORMULAS
    };
})();

window.GravityFieldEngine = GravityFieldEngine;
