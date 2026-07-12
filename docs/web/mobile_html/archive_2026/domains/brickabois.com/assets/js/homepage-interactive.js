/**
 * Free Village Network - Ultra Advanced Interactive Homepage
 * Next-level interactivity and features
 */

(function() {
    'use strict';

    // Global state
    const state = {
        mouse: { x: 0, y: 0 },
        scroll: 0,
        particles: [],
        villages: [],
        stats: {},
        activeDimension: null,
        interactiveMode: true
    };

    // Initialize all advanced features
    document.addEventListener('DOMContentLoaded', function() {
        initAdvancedInteractions();
        init3DWorld();
        initInteractiveVillageMap();
        initLiveStatsDashboard();
        initInteractiveTimeline();
        initDimensionExplorer();
        initInteractiveStorytelling();
        initAdvancedScrollEffects();
        initMicroInteractions();
        initInteractiveFilters();
        initRealTimeUpdates();
        initAdvancedParticleSystem();
        initInteractiveCards();
        initGestureControls();
    });

    // ============================================
    // ADVANCED 3D WORLD
    // ============================================
    function init3DWorld() {
        const world3D = document.getElementById('world3D');
        if (!world3D) return;

        const canvas = document.createElement('canvas');
        canvas.id = 'world3DCanvas';
        canvas.style.cssText = 'width: 100%; height: 100%; position: absolute; top: 0; left: 0;';
        world3D.appendChild(canvas);

        const ctx = canvas.getContext('2d');
        let animationFrame;

        function resize() {
            canvas.width = world3D.offsetWidth;
            canvas.height = world3D.offsetHeight;
        }
        resize();
        window.addEventListener('resize', resize);

        const nodes = [];
        const connections = [];

        // Create network nodes
        for (let i = 0; i < 30; i++) {
            nodes.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                z: Math.random() * 500,
                vx: (Math.random() - 0.5) * 0.5,
                vy: (Math.random() - 0.5) * 0.5,
                radius: Math.random() * 3 + 2,
                color: `hsl(${Math.random() * 60 + 30}, 70%, ${Math.random() * 30 + 50}%)`,
                pulse: Math.random() * Math.PI * 2
            });
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2;

            // Update nodes
            nodes.forEach(node => {
                node.x += node.vx;
                node.y += node.vy;
                node.pulse += 0.05;

                // Boundary wrap
                if (node.x < 0) node.x = canvas.width;
                if (node.x > canvas.width) node.x = 0;
                if (node.y < 0) node.y = canvas.height;
                if (node.y > canvas.height) node.y = 0;

                // Mouse interaction
                const dx = state.mouse.x - node.x;
                const dy = state.mouse.y - node.y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                if (distance < 150) {
                    const force = (150 - distance) / 150;
                    node.vx -= (dx / distance) * force * 0.1;
                    node.vy -= (dy / distance) * force * 0.1;
                }

                // Draw connections
                nodes.forEach(other => {
                    if (node !== other) {
                        const dx = node.x - other.x;
                        const dy = node.y - other.y;
                        const distance = Math.sqrt(dx * dx + dy * dy);
                        if (distance < 200) {
                            ctx.beginPath();
                            ctx.moveTo(node.x, node.y);
                            ctx.lineTo(other.x, other.y);
                            const opacity = (1 - distance / 200) * 0.3;
                            ctx.strokeStyle = `rgba(212, 165, 116, ${opacity})`;
                            ctx.lineWidth = 1;
                            ctx.stroke();
                        }
                    }
                });

                // Draw node
                const scale = 1000 / (1000 + node.z);
                const x = centerX + (node.x - centerX) * scale;
                const y = centerY + (node.y - centerY) * scale;
                const radius = node.radius * scale * (1 + Math.sin(node.pulse) * 0.3);

                ctx.beginPath();
                ctx.arc(x, y, radius, 0, Math.PI * 2);
                ctx.fillStyle = node.color;
                ctx.shadowBlur = 15;
                ctx.shadowColor = node.color;
                ctx.fill();
            });

            animationFrame = requestAnimationFrame(animate);
        }
        animate();
    }

    // ============================================
    // INTERACTIVE VILLAGE MAP
    // ============================================
    // Map is now handled by interactive-map.js
    function initInteractiveVillageMap() {
        // Map initialization moved to dedicated file
        return;
    }

    // ============================================
    // LIVE STATS DASHBOARD
    // ============================================
    function initLiveStatsDashboard() {
        const dashboard = document.getElementById('liveStatsDashboard');
        if (!dashboard) return;

        const stats = {
            citizens: { current: 0, target: 1250, speed: 2 },
            posts: { current: 0, target: 3420, speed: 5 },
            villages: { current: 0, target: 18, speed: 0.5 },
            events: { current: 0, target: 45, speed: 1 }
        };

        function updateStats() {
            Object.keys(stats).forEach(key => {
                const stat = stats[key];
                if (stat.current < stat.target) {
                    stat.current = Math.min(stat.current + stat.speed, stat.target);
                    const element = dashboard.querySelector(`[data-stat="${key}"]`);
                    if (element) {
                        element.textContent = Math.floor(stat.current).toLocaleString();
                        element.style.transform = 'scale(1.1)';
                        setTimeout(() => element.style.transform = 'scale(1)', 200);
                    }
                }
            });
            requestAnimationFrame(updateStats);
        }
        updateStats();
    }

    // ============================================
    // INTERACTIVE TIMELINE
    // ============================================
    function initInteractiveTimeline() {
        const timeline = document.getElementById('interactiveTimeline');
        if (!timeline) return;

        const events = [
            { year: 2020, title: 'Network Founded', desc: 'The vision begins' },
            { year: 2021, title: 'First Village', desc: 'First physical node established' },
            { year: 2022, title: 'Governance Launch', desc: 'The Ledger goes live' },
            { year: 2023, title: 'Expansion', desc: 'Multiple villages join' },
            { year: 2024, title: 'Today', desc: 'Growing network' }
        ];

        events.forEach((event, i) => {
            const node = document.createElement('div');
            node.className = 'timeline-node';
            node.innerHTML = `
                <div class="timeline-year">${event.year}</div>
                <div class="timeline-content">
                    <h4>${event.title}</h4>
                    <p>${event.desc}</p>
                </div>
            `;
            node.style.left = `${(i / (events.length - 1)) * 100}%`;
            node.addEventListener('click', () => {
                timeline.querySelectorAll('.timeline-node').forEach(n => n.classList.remove('active'));
                node.classList.add('active');
            });
            timeline.appendChild(node);
        });
    }

    // ============================================
    // DIMENSION EXPLORER
    // ============================================
    function initDimensionExplorer() {
        const dimensions = document.querySelectorAll('.dimension-explorer-card');
        dimensions.forEach(card => {
            let detailsVisible = false;
            
            // Click to toggle details
            card.addEventListener('click', function(e) {
                // Don't trigger if clicking on a link
                if (e.target.closest('a')) return;
                
                e.preventDefault();
                e.stopPropagation();
                
                const dimension = this.dataset.dimension || this.dataset.category;
                const details = document.getElementById(`${dimension}Details`);
                
                if (details) {
                    detailsVisible = !detailsVisible;
                    if (detailsVisible) {
                        details.style.display = 'block';
                        details.style.opacity = '0';
                        details.style.transform = 'translateY(20px)';
                        setTimeout(() => {
                            details.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
                            details.style.opacity = '1';
                            details.style.transform = 'translateY(0)';
                        }, 10);
                    } else {
                        details.style.transition = 'all 0.3s ease';
                        details.style.opacity = '0';
                        details.style.transform = 'translateY(20px)';
                        setTimeout(() => {
                            details.style.display = 'none';
                        }, 300);
                    }
                }
            });

            card.addEventListener('mouseenter', function() {
                state.activeDimension = this.dataset.dimension || this.dataset.category;
                this.style.cursor = 'pointer';
                this.style.zIndex = '10';
            });

            card.addEventListener('mouseleave', function() {
                this.style.zIndex = '1';
                state.activeDimension = null;
            });

            // 3D tilt effect (only if not filtering)
            card.addEventListener('mousemove', function(e) {
                if (this.style.display === 'none') return;
                
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                const rotateX = (y - centerY) / 15;
                const rotateY = (centerX - x) / 15;
                
                // Only apply tilt if card is visible
                if (this.style.opacity !== '0' && this.style.display !== 'none') {
                    this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.05)`;
                    this.style.transition = 'none';
                }
            });
            
            card.addEventListener('mouseleave', function() {
                if (this.style.display !== 'none') {
                    this.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1)';
                    this.style.transition = 'transform 0.5s ease';
                }
            });
        });
    }

    // ============================================
    // INTERACTIVE STORYTELLING
    // ============================================
    function initInteractiveStorytelling() {
        const story = document.getElementById('interactiveStory');
        if (!story) return;

        const chapters = story.querySelectorAll('.story-chapter');
        let currentChapter = 0;

        function showChapter(index) {
            chapters.forEach((ch, i) => {
                ch.classList.toggle('active', i === index);
                ch.style.opacity = i === index ? '1' : '0';
                ch.style.transform = i === index ? 'translateY(0)' : 'translateY(50px)';
            });
        }

        story.querySelectorAll('.story-nav').forEach(btn => {
            btn.addEventListener('click', () => {
                if (btn.dataset.direction === 'next') {
                    currentChapter = Math.min(currentChapter + 1, chapters.length - 1);
                } else {
                    currentChapter = Math.max(currentChapter - 1, 0);
                }
                showChapter(currentChapter);
            });
        });

        // Auto-advance on scroll
        let lastScroll = 0;
        window.addEventListener('scroll', () => {
            const storyRect = story.getBoundingClientRect();
            if (storyRect.top < window.innerHeight / 2 && storyRect.bottom > window.innerHeight / 2) {
                const scrollDelta = window.scrollY - lastScroll;
                if (Math.abs(scrollDelta) > 100) {
                    if (scrollDelta > 0 && currentChapter < chapters.length - 1) {
                        currentChapter++;
                        showChapter(currentChapter);
                    } else if (scrollDelta < 0 && currentChapter > 0) {
                        currentChapter--;
                        showChapter(currentChapter);
                    }
                    lastScroll = window.scrollY;
                }
            }
        });
    }

    // ============================================
    // ADVANCED SCROLL EFFECTS
    // ============================================
    function initAdvancedScrollEffects() {
        const sections = document.querySelectorAll('.scroll-effect-section');
        
        sections.forEach(section => {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const progress = entry.intersectionRatio;
                        section.style.opacity = progress;
                        section.style.transform = `translateY(${(1 - progress) * 50}px) scale(${0.9 + progress * 0.1})`;
                    }
                });
            }, { threshold: Array.from({ length: 100 }, (_, i) => i / 100) });

            observer.observe(section);
        });

        // Parallax scrolling
        window.addEventListener('scroll', () => {
            state.scroll = window.scrollY;
            document.querySelectorAll('.parallax-element').forEach((el, i) => {
                const speed = (i + 1) * 0.1;
                el.style.transform = `translateY(${state.scroll * speed}px)`;
            });
        });
    }

    // ============================================
    // MICRO INTERACTIONS
    // ============================================
    function initMicroInteractions() {
        // Button ripple effects
        document.querySelectorAll('.btn-interactive').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;

                ripple.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.5);
                    left: ${x}px;
                    top: ${y}px;
                    transform: scale(0);
                    animation: ripple 0.6s ease-out;
                    pointer-events: none;
                `;
                this.appendChild(ripple);
                setTimeout(() => ripple.remove(), 600);
            });
        });

        // Card hover effects
        document.querySelectorAll('.interactive-card-advanced').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.boxShadow = '0 20px 60px rgba(212, 165, 116, 0.4)';
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.boxShadow = '';
                this.style.transform = '';
            });
        });
    }

    // ============================================
    // INTERACTIVE FILTERS
    // ============================================
    function initInteractiveFilters() {
        const filters = document.getElementById('interactiveFilters');
        if (!filters) return;

        filters.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Update active state
                filters.querySelectorAll('.filter-btn').forEach(b => {
                    b.classList.remove('active');
                    b.style.background = 'transparent';
                    b.style.border = '2px solid var(--color-border)';
                    b.style.color = 'var(--color-text)';
                });
                
                this.classList.add('active');
                this.style.background = 'var(--color-primary)';
                this.style.border = 'none';
                this.style.color = 'white';
                
                const filter = this.dataset.filter;
                
                // Filter dimension cards
                document.querySelectorAll('.filterable-item').forEach(item => {
                    const category = item.dataset.category || item.dataset.dimension;
                    if (filter === 'all' || category === filter) {
                        item.style.display = 'block';
                        item.style.opacity = '0';
                        item.style.transform = 'scale(0.9)';
                        setTimeout(() => {
                            item.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
                            item.style.opacity = '1';
                            item.style.transform = 'scale(1)';
                        }, 10);
                    } else {
                        item.style.transition = 'all 0.3s ease';
                        item.style.opacity = '0';
                        item.style.transform = 'scale(0.8)';
                        setTimeout(() => {
                            item.style.display = 'none';
                        }, 300);
                    }
                });
            });
        });
    }

    // ============================================
    // REAL-TIME UPDATES
    // ============================================
    function initRealTimeUpdates() {
        // Simulate real-time updates
        setInterval(() => {
            const liveElements = document.querySelectorAll('.live-update');
            liveElements.forEach(el => {
                const current = parseInt(el.textContent) || 0;
                const change = Math.floor(Math.random() * 3);
                el.textContent = current + change;
                el.style.animation = 'pulse 0.3s ease';
                setTimeout(() => el.style.animation = '', 300);
            });
        }, 5000);
    }

    // ============================================
    // ADVANCED PARTICLE SYSTEM
    // ============================================
    function initAdvancedParticleSystem() {
        const containers = document.querySelectorAll('.particle-container');
        containers.forEach(container => {
            const canvas = document.createElement('canvas');
            canvas.style.cssText = 'width: 100%; height: 100%; position: absolute; top: 0; left: 0; pointer-events: none;';
            container.appendChild(canvas);
            const ctx = canvas.getContext('2d');

            function resize() {
                canvas.width = container.offsetWidth;
                canvas.height = container.offsetHeight;
            }
            resize();
            window.addEventListener('resize', resize);

            const particles = [];
            for (let i = 0; i < 50; i++) {
                particles.push({
                    x: Math.random() * canvas.width,
                    y: Math.random() * canvas.height,
                    vx: (Math.random() - 0.5) * 0.5,
                    vy: (Math.random() - 0.5) * 0.5,
                    radius: Math.random() * 2 + 1,
                    life: Math.random()
                });
            }

            function animate() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                particles.forEach(p => {
                    p.x += p.vx;
                    p.y += p.vy;
                    p.life += 0.01;

                    if (p.x < 0 || p.x > canvas.width) p.vx *= -1;
                    if (p.y < 0 || p.y > canvas.height) p.vy *= -1;

                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
                    ctx.fillStyle = `rgba(212, 165, 116, ${Math.sin(p.life) * 0.5 + 0.5})`;
                    ctx.fill();
                });
                requestAnimationFrame(animate);
            }
            animate();
        });
    }

    // ============================================
    // INTERACTIVE CARDS
    // ============================================
    function initInteractiveCards() {
        document.querySelectorAll('.card-3d-interactive').forEach(card => {
            // Skip if already handled by dimension explorer
            if (card.classList.contains('dimension-explorer-card')) return;
            
            card.addEventListener('mousemove', function(e) {
                if (this.style.display === 'none') return;
                
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                const rotateX = (y - centerY) / 20;
                const rotateY = (centerX - x) / 20;

                this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.05)`;
                this.style.transition = 'none';
            });

            card.addEventListener('mouseleave', function() {
                if (this.style.display !== 'none') {
                    this.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1)';
                    this.style.transition = 'transform 0.5s ease';
                }
            });
        });
    }

    // ============================================
    // GESTURE CONTROLS
    // ============================================
    function initGestureControls() {
        let touchStart = null;
        document.addEventListener('touchstart', (e) => {
            touchStart = { x: e.touches[0].clientX, y: e.touches[0].clientY };
        });

        document.addEventListener('touchmove', (e) => {
            if (!touchStart) return;
            const touchEnd = { x: e.touches[0].clientX, y: e.touches[0].clientY };
            const dx = touchEnd.x - touchStart.x;
            const dy = touchEnd.y - touchStart.y;

            if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 50) {
                // Horizontal swipe
                if (dx > 0) {
                    // Swipe right
                    document.dispatchEvent(new CustomEvent('swiperight'));
                } else {
                    // Swipe left
                    document.dispatchEvent(new CustomEvent('swipeleft'));
                }
            }
        });
    }

    // ============================================
    // ADVANCED INTERACTIONS
    // ============================================
    function initAdvancedInteractions() {
        // Mouse tracking
        document.addEventListener('mousemove', (e) => {
            state.mouse.x = e.clientX;
            state.mouse.y = e.clientY;
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(m => m.remove());
            }
        });
    }

    // Add CSS animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple {
            to { transform: scale(4); opacity: 0; }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .village-info-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            animation: fadeInUp 0.3s ease;
        }
        .modal-content {
            background: var(--color-bg-card);
            padding: 2rem;
            border-radius: 20px;
            max-width: 400px;
            position: relative;
        }
        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 2rem;
            cursor: pointer;
            color: var(--color-text);
        }
    `;
    document.head.appendChild(style);

})();

