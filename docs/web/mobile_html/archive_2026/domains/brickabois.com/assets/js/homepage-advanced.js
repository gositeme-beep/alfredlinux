/**
 * Free Village Network - Advanced Homepage Graphics
 * Next-level visual effects and interactions
 */

(function() {
    'use strict';

    let heroCanvas, heroCtx;
    let particles = [];
    let mouse = { x: 0, y: 0 };
    let animationId;
    let time = 0;

    // Initialize all advanced graphics
    document.addEventListener('DOMContentLoaded', function() {
        initAdvancedParticleSystem();
        init3DScene();
        initMorphingShapes();
        initGlowEffects();
        initParallaxLayers();
        initInteractiveCards();
        initFloatingIcons();
        initScrollReveal();
        initCursorTrail();
        initAnimatedGradients();
    });

    // ============================================
    // ADVANCED PARTICLE SYSTEM
    // ============================================
    function initAdvancedParticleSystem() {
        heroCanvas = document.getElementById('heroCanvas');
        if (!heroCanvas) return;

        heroCtx = heroCanvas.getContext('2d');
        const particleCount = 150;

        class AdvancedParticle {
            constructor() {
                this.reset();
                this.y = Math.random() * heroCanvas.height;
            }

            reset() {
                this.x = Math.random() * heroCanvas.width;
                this.y = Math.random() * heroCanvas.height;
                this.size = Math.random() * 3 + 1;
                this.speedX = (Math.random() - 0.5) * 0.5;
                this.speedY = (Math.random() - 0.5) * 0.5;
                this.life = Math.random();
                this.decay = Math.random() * 0.02 + 0.005;
                this.color = `hsl(${Math.random() * 60 + 30}, 70%, ${Math.random() * 30 + 50}%)`;
            }

            update() {
                // Mouse interaction
                const dx = mouse.x - this.x;
                const dy = mouse.y - this.y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < 100) {
                    const angle = Math.atan2(dy, dx);
                    const force = (100 - distance) / 100;
                    this.speedX -= Math.cos(angle) * force * 0.02;
                    this.speedY -= Math.sin(angle) * force * 0.02;
                }

                // Add some noise
                this.speedX += (Math.random() - 0.5) * 0.1;
                this.speedY += (Math.random() - 0.5) * 0.1;

                // Apply speed
                this.x += this.speedX;
                this.y += this.speedY;

                // Boundary wrap
                if (this.x < 0) this.x = heroCanvas.width;
                if (this.x > heroCanvas.width) this.x = 0;
                if (this.y < 0) this.y = heroCanvas.height;
                if (this.y > heroCanvas.height) this.y = 0;

                // Life decay
                this.life -= this.decay;
                if (this.life <= 0) this.reset();
            }

            draw() {
                heroCtx.save();
                heroCtx.globalAlpha = this.life;
                heroCtx.fillStyle = this.color;
                heroCtx.shadowBlur = 10;
                heroCtx.shadowColor = this.color;
                heroCtx.beginPath();
                heroCtx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                heroCtx.fill();
                heroCtx.restore();
            }
        }

        // Create particles
        for (let i = 0; i < particleCount; i++) {
            particles.push(new AdvancedParticle());
        }

        // Draw connections
        function drawConnections() {
            for (let i = 0; i < particles.length; i++) {
                for (let j = i + 1; j < particles.length; j++) {
                    const dx = particles[i].x - particles[j].x;
                    const dy = particles[i].y - particles[j].y;
                    const distance = Math.sqrt(dx * dx + dy * dy);

                    if (distance < 120) {
                        heroCtx.beginPath();
                        heroCtx.moveTo(particles[i].x, particles[i].y);
                        heroCtx.lineTo(particles[j].x, particles[j].y);
                        const opacity = (1 - distance / 120) * 0.3 * particles[i].life * particles[j].life;
                        heroCtx.strokeStyle = `rgba(212, 165, 116, ${opacity})`;
                        heroCtx.lineWidth = 1;
                        heroCtx.stroke();
                    }
                }
            }
        }

        // Resize handler
        function resizeCanvas() {
            heroCanvas.width = heroCanvas.offsetWidth;
            heroCanvas.height = heroCanvas.offsetHeight;
        }
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        // Mouse tracking
        heroCanvas.addEventListener('mousemove', (e) => {
            const rect = heroCanvas.getBoundingClientRect();
            mouse.x = e.clientX - rect.left;
            mouse.y = e.clientY - rect.top;
        });

        // Animation loop
        function animate() {
            time += 0.01;
            heroCtx.clearRect(0, 0, heroCanvas.width, heroCanvas.height);

            particles.forEach(particle => {
                particle.update();
                particle.draw();
            });

            drawConnections();

            animationId = requestAnimationFrame(animate);
        }
        animate();
    }

    // ============================================
    // 3D SCENE WITH THREE.JS STYLE EFFECTS
    // ============================================
    function init3DScene() {
        const scene3D = document.querySelector('.scene-3d');
        if (!scene3D) return;

        const canvas3D = document.createElement('canvas');
        canvas3D.className = 'canvas-3d';
        scene3D.appendChild(canvas3D);
        const ctx3D = canvas3D.getContext('2d');

        function resize3D() {
            canvas3D.width = scene3D.offsetWidth;
            canvas3D.height = scene3D.offsetHeight;
        }
        resize3D();
        window.addEventListener('resize', resize3D);

        const shapes = [];
        for (let i = 0; i < 20; i++) {
            shapes.push({
                x: Math.random() * canvas3D.width,
                y: Math.random() * canvas3D.height,
                z: Math.random() * 1000,
                size: Math.random() * 50 + 20,
                rotation: Math.random() * Math.PI * 2,
                rotationSpeed: (Math.random() - 0.5) * 0.02,
                color: `hsl(${Math.random() * 60 + 30}, 70%, 60%)`
            });
        }

        function animate3D() {
            ctx3D.clearRect(0, 0, canvas3D.width, canvas3D.height);
            const centerX = canvas3D.width / 2;
            const centerY = canvas3D.height / 2;

            shapes.forEach(shape => {
                shape.z -= 2;
                shape.rotation += shape.rotationSpeed;

                if (shape.z <= 0) {
                    shape.z = 1000;
                    shape.x = Math.random() * canvas3D.width;
                    shape.y = Math.random() * canvas3D.height;
                }

                const scale = 1000 / shape.z;
                const x = centerX + (shape.x - centerX) * scale;
                const y = centerY + (shape.y - centerY) * scale;
                const size = shape.size * scale;

                ctx3D.save();
                ctx3D.translate(x, y);
                ctx3D.rotate(shape.rotation);
                ctx3D.globalAlpha = Math.min(1, scale);
                ctx3D.strokeStyle = shape.color;
                ctx3D.lineWidth = 2;
                ctx3D.shadowBlur = 15;
                ctx3D.shadowColor = shape.color;
                ctx3D.beginPath();
                ctx3D.moveTo(-size/2, -size/2);
                ctx3D.lineTo(size/2, -size/2);
                ctx3D.lineTo(size/2, size/2);
                ctx3D.lineTo(-size/2, size/2);
                ctx3D.closePath();
                ctx3D.stroke();
                ctx3D.restore();
            });

            requestAnimationFrame(animate3D);
        }
        animate3D();
    }

    // ============================================
    // MORPHING SHAPES
    // ============================================
    function initMorphingShapes() {
        const morphContainer = document.querySelector('.morph-shapes');
        if (!morphContainer) return;

        for (let i = 0; i < 5; i++) {
            const shape = document.createElement('div');
            shape.className = 'morph-shape';
            shape.style.cssText = `
                position: absolute;
                width: ${Math.random() * 200 + 100}px;
                height: ${Math.random() * 200 + 100}px;
                border-radius: ${Math.random() * 50}%;
                background: linear-gradient(135deg, 
                    hsla(${Math.random() * 60 + 30}, 70%, 60%, 0.3),
                    hsla(${Math.random() * 60 + 30}, 70%, 50%, 0.2)
                );
                filter: blur(40px);
                left: ${Math.random() * 100}%;
                top: ${Math.random() * 100}%;
                animation: morph${i} ${Math.random() * 20 + 15}s ease-in-out infinite;
            `;
            morphContainer.appendChild(shape);

            // Create keyframes
            const style = document.createElement('style');
            style.textContent = `
                @keyframes morph${i} {
                    0%, 100% { 
                        transform: translate(0, 0) scale(1) rotate(0deg);
                        border-radius: ${Math.random() * 50}%;
                    }
                    25% { 
                        transform: translate(${Math.random() * 200 - 100}px, ${Math.random() * 200 - 100}px) scale(${Math.random() * 0.5 + 0.75}) rotate(90deg);
                        border-radius: ${Math.random() * 50}%;
                    }
                    50% { 
                        transform: translate(${Math.random() * 200 - 100}px, ${Math.random() * 200 - 100}px) scale(${Math.random() * 0.5 + 0.75}) rotate(180deg);
                        border-radius: ${Math.random() * 50}%;
                    }
                    75% { 
                        transform: translate(${Math.random() * 200 - 100}px, ${Math.random() * 200 - 100}px) scale(${Math.random() * 0.5 + 0.75}) rotate(270deg);
                        border-radius: ${Math.random() * 50}%;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }

    // ============================================
    // GLOW EFFECTS
    // ============================================
    function initGlowEffects() {
        const glowElements = document.querySelectorAll('.glow-effect');
        glowElements.forEach(el => {
            el.addEventListener('mousemove', (e) => {
                const rect = el.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                el.style.setProperty('--glow-x', x + 'px');
                el.style.setProperty('--glow-y', y + 'px');
            });
        });
    }

    // ============================================
    // PARALLAX LAYERS
    // ============================================
    function initParallaxLayers() {
        const parallaxElements = document.querySelectorAll('.parallax-layer:not(.dimensions-section-advanced)');
        
        // Only apply parallax if elements exist and it won't break scrolling
        if (parallaxElements.length === 0) return;
        
        let ticking = false;
        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    const scrolled = window.pageYOffset;
                    parallaxElements.forEach((el, index) => {
                        // Only apply subtle parallax, don't break layout
                        const speed = (index + 1) * 0.05; // Reduced speed
                        const yPos = -(scrolled * speed);
                        // Only apply if element is in viewport
                        const rect = el.getBoundingClientRect();
                        if (rect.top < window.innerHeight && rect.bottom > 0) {
                            el.style.transform = `translateY(${yPos}px)`;
                        }
                    });
                    ticking = false;
                });
                ticking = true;
            }
        });
    }

    // ============================================
    // INTERACTIVE CARDS
    // ============================================
    function initInteractiveCards() {
        const cards = document.querySelectorAll('.interactive-card-3d');
        cards.forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                const rotateX = (y - centerY) / 10;
                const rotateY = (centerX - x) / 10;

                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.05, 1.05, 1.05)`;
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale3d(1, 1, 1)';
            });
        });
    }

    // ============================================
    // FLOATING ICONS
    // ============================================
    function initFloatingIcons() {
        const icons = ['🌿', '🌱', '🏡', '🌍', '🤝', '💚', '🌳', '🦋'];
        const floatContainer = document.querySelector('.floating-icons');
        if (!floatContainer) return;

        for (let i = 0; i < 15; i++) {
            const icon = document.createElement('div');
            icon.className = 'floating-icon';
            icon.textContent = icons[Math.floor(Math.random() * icons.length)];
            icon.style.cssText = `
                position: absolute;
                font-size: ${Math.random() * 30 + 20}px;
                left: ${Math.random() * 100}%;
                top: ${Math.random() * 100}%;
                opacity: ${Math.random() * 0.5 + 0.3};
                animation: floatIcon${i} ${Math.random() * 10 + 10}s ease-in-out infinite;
                pointer-events: none;
            `;
            floatContainer.appendChild(icon);

            const style = document.createElement('style');
            style.textContent = `
                @keyframes floatIcon${i} {
                    0%, 100% { transform: translate(0, 0) rotate(0deg); }
                    25% { transform: translate(${Math.random() * 100 - 50}px, ${Math.random() * 100 - 50}px) rotate(90deg); }
                    50% { transform: translate(${Math.random() * 100 - 50}px, ${Math.random() * 100 - 50}px) rotate(180deg); }
                    75% { transform: translate(${Math.random() * 100 - 50}px, ${Math.random() * 100 - 50}px) rotate(270deg); }
                }
            `;
            document.head.appendChild(style);
        }
    }

    // ============================================
    // SCROLL REVEAL
    // ============================================
    function initScrollReveal() {
        const reveals = document.querySelectorAll('.reveal-on-scroll');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                }
            });
        }, { threshold: 0.1 });

        reveals.forEach(reveal => observer.observe(reveal));
    }

    // ============================================
    // CURSOR TRAIL
    // ============================================
    function initCursorTrail() {
        const trail = [];
        const trailLength = 20;

        for (let i = 0; i < trailLength; i++) {
            const dot = document.createElement('div');
            dot.className = 'cursor-trail-dot';
            dot.style.cssText = `
                position: fixed;
                width: ${10 - i * 0.4}px;
                height: ${10 - i * 0.4}px;
                border-radius: 50%;
                background: var(--color-accent);
                pointer-events: none;
                z-index: 9999;
                opacity: ${1 - i / trailLength};
                transition: transform 0.1s ease;
            `;
            document.body.appendChild(dot);
            trail.push({ element: dot, x: 0, y: 0 });
        }

        let mouseX = 0, mouseY = 0;
        document.addEventListener('mousemove', (e) => {
            mouseX = e.clientX;
            mouseY = e.clientY;
        });

        function animateTrail() {
            let x = mouseX;
            let y = mouseY;

            trail.forEach((dot, i) => {
                const nextX = x;
                const nextY = y;
                x += (dot.x - x) * 0.3;
                y += (dot.y - y) * 0.3;
                dot.x = nextX;
                dot.y = nextY;
                dot.element.style.transform = `translate(${dot.x}px, ${dot.y}px)`;
            });

            requestAnimationFrame(animateTrail);
        }
        animateTrail();
    }

    // ============================================
    // ANIMATED GRADIENTS
    // ============================================
    function initAnimatedGradients() {
        const gradientElements = document.querySelectorAll('.animated-gradient');
        gradientElements.forEach(el => {
            let hue = 0;
            setInterval(() => {
                hue = (hue + 1) % 360;
                el.style.background = `linear-gradient(135deg, hsl(${hue}, 70%, 60%), hsl(${(hue + 60) % 360}, 70%, 50%))`;
            }, 50);
        });
    }

})();

