/**
 * Advanced Animations and Interactive Features
 */

// 3D Card Tilt Effect
function init3DCards() {
    const cards = document.querySelectorAll('.dimension-card, .stat-card, .step-card, .value-card');
    
    cards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = (y - centerY) / 10;
            const rotateY = (centerX - x) / 10;
            
            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-5px)`;
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateY(0)';
        });
    });
}

// Scroll Progress Indicator
function initScrollProgress() {
    const progressBar = document.createElement('div');
    progressBar.id = 'scrollProgress';
    progressBar.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--color-primary), var(--color-accent));
        width: 0%;
        z-index: 10000;
        transition: width 0.1s ease;
    `;
    document.body.appendChild(progressBar);
    
    window.addEventListener('scroll', () => {
        const windowHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrolled = (window.scrollY / windowHeight) * 100;
        progressBar.style.width = scrolled + '%';
    });
}

// Morphing Background Shapes
function createMorphingShapes() {
    const shapesContainer = document.createElement('div');
    shapesContainer.id = 'morphingShapes';
    shapesContainer.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 0;
        overflow: hidden;
    `;
    document.querySelector('.hero').appendChild(shapesContainer);
    
    for (let i = 0; i < 3; i++) {
        const shape = document.createElement('div');
        shape.style.cssText = `
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(45, 80, 22, 0.1) 0%, transparent 70%);
            filter: blur(40px);
            animation: morph${i} 20s ease-in-out infinite;
        `;
        
        const size = 300 + Math.random() * 200;
        shape.style.width = size + 'px';
        shape.style.height = size + 'px';
        shape.style.left = Math.random() * 100 + '%';
        shape.style.top = Math.random() * 100 + '%';
        
        shapesContainer.appendChild(shape);
        
        // Add keyframes
        const style = document.createElement('style');
        style.textContent = `
            @keyframes morph${i} {
                0%, 100% {
                    transform: translate(0, 0) scale(1);
                    border-radius: 50%;
                }
                33% {
                    transform: translate(${Math.random() * 200 - 100}px, ${Math.random() * 200 - 100}px) scale(${0.8 + Math.random() * 0.4});
                    border-radius: ${30 + Math.random() * 40}% ${30 + Math.random() * 40}% ${30 + Math.random() * 40}% ${30 + Math.random() * 40}%;
                }
                66% {
                    transform: translate(${Math.random() * 200 - 100}px, ${Math.random() * 200 - 100}px) scale(${0.8 + Math.random() * 0.4});
                    border-radius: ${30 + Math.random() * 40}% ${30 + Math.random() * 40}% ${30 + Math.random() * 40}% ${30 + Math.random() * 40}%;
                }
            }
        `;
        document.head.appendChild(style);
    }
}

// Interactive Stats Visualization
function initStatsVisualization() {
    const statCards = document.querySelectorAll('.stat-card');
    
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            const number = this.querySelector('.stat-number');
            const target = parseInt(number.getAttribute('data-target')) || 0;
            
            // Create animated ring
            const ring = document.createElement('div');
            ring.style.cssText = `
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 100px;
                height: 100px;
                border: 3px solid var(--color-accent);
                border-radius: 50%;
                border-top-color: transparent;
                animation: spin 1s linear infinite;
                pointer-events: none;
            `;
            this.appendChild(ring);
            
            setTimeout(() => ring.remove(), 1000);
        });
    });
}

// Parallax Scrolling Enhancement (disabled to prevent layout issues)
function initAdvancedParallax() {
    // Disabled to prevent breaking layout
    // const parallaxElements = document.querySelectorAll('.hero, .dimension-card, .step-card');
    // 
    // window.addEventListener('scroll', () => {
    //     const scrolled = window.pageYOffset;
    //     
    //     parallaxElements.forEach((element, index) => {
    //         const speed = 0.5 + (index * 0.1);
    //         const yPos = -(scrolled * speed);
    //         element.style.transform = `translateY(${yPos}px)`;
    //     });
    // });
}

// Text Reveal Animation (disabled to prevent breaking text)
function initTextReveal() {
    // Disabled to prevent text from disappearing
    // const observer = new IntersectionObserver((entries) => {
    //     entries.forEach(entry => {
    //         if (entry.isIntersecting) {
    //             const text = entry.target;
    //             const words = text.textContent.split(' ');
    //             text.innerHTML = words.map((word, i) => 
    //                 `<span style="display: inline-block; animation: fadeInUp 0.5s ease forwards; animation-delay: ${i * 0.1}s; opacity: 0;">${word}</span>`
    //             ).join(' ');
    //             observer.unobserve(text);
    //         }
    //     });
    // }, { threshold: 0.5 });
    
    // document.querySelectorAll('.section-title, .hero-title').forEach(title => {
    //     observer.observe(title);
    // });
}

// Floating Elements
function createFloatingElements() {
    const floatingIcons = ['🌱', '💬', '📜', '🌍', '🤝', '⚖️', '🌿', '🔍'];
    
    floatingIcons.forEach((icon, index) => {
        const element = document.createElement('div');
        element.textContent = icon;
        element.style.cssText = `
            position: fixed;
            font-size: ${20 + Math.random() * 30}px;
            opacity: 0.1;
            pointer-events: none;
            z-index: 1;
            animation: float${index} ${10 + Math.random() * 10}s ease-in-out infinite;
            left: ${Math.random() * 100}%;
            top: ${Math.random() * 100}%;
        `;
        document.body.appendChild(element);
        
        const style = document.createElement('style');
        style.textContent = `
            @keyframes float${index} {
                0%, 100% {
                    transform: translate(0, 0) rotate(0deg);
                }
                25% {
                    transform: translate(${Math.random() * 100 - 50}px, ${Math.random() * 100 - 50}px) rotate(90deg);
                }
                50% {
                    transform: translate(${Math.random() * 100 - 50}px, ${Math.random() * 100 - 50}px) rotate(180deg);
                }
                75% {
                    transform: translate(${Math.random() * 100 - 50}px, ${Math.random() * 100 - 50}px) rotate(270deg);
                }
            }
        `;
        document.head.appendChild(style);
    });
}

// Cursor Trail Effect
function initCursorTrail() {
    const trail = [];
    const trailLength = 10;
    
    document.addEventListener('mousemove', (e) => {
        trail.push({ x: e.clientX, y: e.clientY, time: Date.now() });
        
        if (trail.length > trailLength) {
            trail.shift();
        }
        
        // Remove old trail elements
        document.querySelectorAll('.cursor-trail').forEach(el => el.remove());
        
        // Create new trail
        trail.forEach((point, index) => {
            const dot = document.createElement('div');
            const size = (trailLength - index) * 2;
            const opacity = (trailLength - index) / trailLength * 0.3;
            
            dot.className = 'cursor-trail';
            dot.style.cssText = `
                position: fixed;
                left: ${point.x}px;
                top: ${point.y}px;
                width: ${size}px;
                height: ${size}px;
                background: var(--color-accent);
                border-radius: 50%;
                pointer-events: none;
                z-index: 9999;
                opacity: ${opacity};
                transform: translate(-50%, -50%);
                transition: all 0.1s ease;
            `;
            document.body.appendChild(dot);
        });
    });
}

// Initialize all animations
document.addEventListener('DOMContentLoaded', () => {
    try {
        init3DCards();
        initScrollProgress();
        createMorphingShapes();
        initStatsVisualization();
        initTextReveal();
        createFloatingElements();
        // initCursorTrail(); // Uncomment if you want cursor trail
        
        console.log('✨ Advanced animations initialized');
    } catch (error) {
        console.error('Animation initialization error:', error);
    }
});

