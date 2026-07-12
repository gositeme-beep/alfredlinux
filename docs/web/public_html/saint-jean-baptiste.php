<?php
/**
 * Alfred Linux — Saint-Jean-Baptiste Day Release
 *
 * GoSiteMe Inc. — June 2026
 */
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Divine Timing: Saint-Jean-Baptiste Day Release — Alfred Linux</title>
    <meta name="description" content="Alfred Linux 7.77 Kingdom of God Edition is physically releasing on Saint-Jean-Baptiste Day. The perfect divine timing of a 125GB sovereign payload.">
    <meta property="og:title" content="Divine Timing: A Release on Saint-Jean-Baptiste Day">
    <meta property="og:description" content="Alfred Linux 7.77 Kingdom of God Edition is officially compiling on Saint-Jean-Baptiste Day. The timing is undeniable.">
    <meta property="og:type" content="article">
    <meta property="og:url" content="https://alfredlinux.com/saint-jean-baptiste">
    <meta property="og:image" content="https://alfredlinux.com/assets/hologram_john_baptist.png">
    <link rel="canonical" href="https://alfredlinux.com/saint-jean-baptiste">
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <style>
        :root {
            --bg: #030712; --surface: rgba(15, 23, 42, 0.6); --surface-hover: rgba(30, 41, 59, 0.8);
            --border: rgba(56, 189, 248, 0.3); --border-hover: rgba(56, 189, 248, 0.6);
            --text: #e0e0e0; --text-muted: #94a3b8; --text-dim: #64748b;
            --accent: #0ea5e9; --accent-light: #7dd3fc;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter',-apple-system,BlinkMacSystemFont,sans-serif; 
            background: radial-gradient(circle at center, #0f172a 0%, #030712 100%);
            color: var(--text); min-height: 100vh; -webkit-font-smoothing: antialiased; line-height: 1.8; 
            position: relative;
        }
        a { color: var(--accent-light); text-decoration: none; }
        a:hover { text-decoration: underline; color: #38bdf8; text-shadow: 0 0 10px rgba(56, 189, 248, 0.5); }

        .container { position: relative; z-index: 10; max-width: 900px; margin: 0 auto; padding: 60px 20px; }
        
        .hologram-wrapper {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 50px 0;
            padding: 20px;
            perspective: 1200px;
            z-index: 10;
        }
        

        .story-content {
            position: relative;
            z-index: 10;
            background: rgba(15, 23, 42, 0.7);
            padding: 50px;
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 0 0 20px rgba(56, 189, 248, 0.1);
            font-size: 1.25rem;
            color: #cbd5e1;
            backdrop-filter: blur(10px);
        }
        .story-content p { margin-bottom: 30px; }
        .story-content strong { color: #fff; text-shadow: 0 0 8px rgba(255,255,255,0.4); }
        
        .highlight {
            color: #facc15;
            font-style: italic;
            border-left: 4px solid #facc15;
            padding-left: 20px;
            margin: 40px 0;
            display: block;
            background: linear-gradient(90deg, rgba(250, 204, 21, 0.1) 0%, transparent 100%);
            padding: 20px;
            border-radius: 0 12px 12px 0;
            font-size: 1.4rem;
            text-shadow: 0 0 10px rgba(250, 204, 21, 0.4);
        }

        .omahon {
            position: relative;
            z-index: 10;
            text-align: center;
            margin: 80px 0 40px;
            padding: 50px 20px;
            background: radial-gradient(circle at center, rgba(56, 189, 248, 0.1) 0%, transparent 100%);
            border-radius: 16px;
            border: 1px solid rgba(56, 189, 248, 0.3);
            box-shadow: inset 0 0 30px rgba(56, 189, 248, 0.1);
        }
        .omahon h3 { 
            color: #fff; 
            font-size: 2.5rem; 
            margin-bottom: 15px; 
            letter-spacing: 4px; 
            text-shadow: 0 0 15px #38bdf8, 0 0 30px #0284c7;
        }

        footer {
            position: relative;
            z-index: 10;
            margin-top: 80px;
            text-align: center;
            border-top: 1px solid rgba(255,255,255,0.05);
            padding: 40px 20px;
            color: var(--text-muted);
        }
        footer p { margin-bottom: 10px; }

        @keyframes pulse {
            0% { opacity: 0.8; box-shadow: 0 0 8px rgba(250, 204, 21, 0.6); }
            50% { opacity: 1; box-shadow: 0 0 15px rgba(250, 204, 21, 1); }
            100% { opacity: 0.8; box-shadow: 0 0 8px rgba(250, 204, 21, 0.6); }
        }
    
        .reveal-element {
            transition: opacity 1.5s cubic-bezier(0.16, 1, 0.3, 1), transform 1.5s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .hidden-start {
            opacity: 0;
            transform: translateY(40px);
        }

    </style>
</head>
<body>

<canvas id="spirit-canvas" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 0; pointer-events: none; opacity: 0.4;"></canvas>

<div style="position: relative; z-index: 100;">
<?php $currentPage = 'saint-jean-baptiste'; include __DIR__ . '/includes/nav.php'; ?>
</div>

<div class="container">
    <div class="hero" style="padding-top: 40px; margin-bottom: 60px;">
        <div style="display: flex; justify-content: center; margin-bottom: 25px;">
            <div style="background: linear-gradient(135deg, rgba(250,204,21,0.15), rgba(0,0,0,0.8)); border: 1px solid rgba(250,204,21,0.4); color: #facc15; box-shadow: 0 0 25px rgba(250,204,21,0.15); border-radius: 50px; padding: 8px 24px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; font-size: 0.85rem; display: flex; align-items: center; gap: 10px;">
                <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #facc15; box-shadow: 0 0 10px #facc15, 0 0 20px #facc15; animation: pulse 2s infinite;"></span> OFFICIAL RELEASE DATE REVEAL
            </div>
        </div>
        
        <h1 style="font-size: clamp(2.5rem, 6vw, 5rem); font-weight: 900; background: linear-gradient(135deg, #fef08a 0%, #facc15 50%, #ca8a04 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 5px; line-height: 1.1; letter-spacing: -0.03em; text-shadow: none;">
            ALFRED LINUX 7.77
        </h1>
        
        <h2 style="font-size: clamp(1.4rem, 3vw, 2.2rem); font-weight: 800; color: #fff; margin-bottom: 2rem; background: linear-gradient(135deg, #ffffff, #7dd3fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; letter-spacing: 1px;">
            Kingdom of God Edition
        </h2>
        
        <div style="margin: 0 auto; max-width: 500px; padding: 25px 20px; border-radius: 20px; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(56, 189, 248, 0.4); box-shadow: inset 0 0 30px rgba(56, 189, 248, 0.1), 0 10px 30px rgba(0,0,0,0.5); backdrop-filter: blur(10px);">
            <div style="font-size: 2.2rem; color: #fff; font-weight: 900; letter-spacing: 3px; margin-bottom: 2px; text-shadow: 0 0 20px rgba(255,255,255,0.3);">JUNE 24, 2026</div>
            <div style="color: #38bdf8; text-transform: uppercase; letter-spacing: 5px; font-size: 0.95rem; font-weight: 600;">Saint-Jean-Baptiste Day</div>
        </div>
    </div>

    <div class="hologram-wrapper">
        <div class="hologram-physics" style="width: 100%; max-width: 700px; transform-style: preserve-3d; transition: transform 0.1s ease-out, box-shadow 0.1s ease-out; will-change: transform, box-shadow; border-radius: 24px; box-shadow: 0 0 50px rgba(0, 229, 255, 0.4), 0 0 100px rgba(0, 102, 255, 0.2), inset 0 0 20px rgba(0, 229, 255, 0.5); border: 2px solid rgba(56, 189, 248, 0.5);">
            <img src="/assets/hologram_john_baptist.png" alt="3D Holographic Render of Saint John the Baptist" class="hologram" style="width: 100%; border-radius: 22px; display: block; filter: brightness(1) drop-shadow(0 0 0px transparent); transform: rotateY(0deg) scale(1);">
        </div>
    </div>

    <div class="story-content">
        <p class="reveal-element">He was the voice crying out in the absolute desolation of the wilderness. Clothed in camel's hair and sustained by the harsh desert, Saint John the Baptist endured profound physical and spiritual suffering, fueled by an unshakeable faith that transcended all earthly pain. He was the Forerunner—the prophet destined to prepare the way for the Word of God incarnate.</p>
        
        <div class="highlight reveal-element">
            "The voice of one crying in the wilderness, Prepare ye the way of the Lord, make his paths straight." &mdash; Mark 1:3
        </div>
        
        <p class="reveal-element">Saint John the Baptist was Jesus's best friend on Earth, His cousin, and the man who held the most absolute faith in the Messiah before the world ever truly knew Him. When John saw Jesus approaching the waters of the Jordan, he did not hesitate. He declared with infinite love and truth, <strong>"Behold the Lamb of God, which taketh away the sin of the world"</strong> (John 1:29). In that singular moment, the divine virtues of Jesus Christ—His boundless mercy, His perfect truth, His sacrificial love, and His unwavering grace—were proclaimed to humanity.</p>
        
        <p class="reveal-element">John's life was a testament to humility and devotion. Recognizing the immense glory of the Savior, John famously declared, <strong>"He must increase, but I must decrease"</strong> (John 3:30). He endured isolation, mockery, imprisonment, and ultimately martyrdom, not for his own glory, but for the supreme glory of truth and the perfect mercy of his best friend, Jesus.</p>
        
        <div class="highlight reveal-element" style="border-left-color: #38bdf8; background: linear-gradient(90deg, rgba(56, 189, 248, 0.1) 0%, transparent 100%); text-shadow: 0 0 10px rgba(56, 189, 248, 0.4);">
            "Greater love hath no man than this, that a man lay down his life for his friends." &mdash; John 15:13
        </div>

        <p class="reveal-element">It is no coincidence that the monumental release of <strong>Alfred Linux 7.77 (Kingdom of God Edition)</strong> is physically compiling on this exact day. After months of intense cyber-warfare, spiritual pain, architectural restructuring, and overcoming catastrophic failures, the final 125GB sovereign payload is being fused together right now.</p>

        <p class="reveal-element">God's timing is undeniably perfect. Just as John the Baptist prepared the way for the Kingdom of Heaven to manifest on Earth, Alfred Linux is being unleashed on his feast day to pave the way for a new dawn in cyber-defense and sovereign computing. This is a spiritual arsenal, carrying over 3,000 tools, the massive Unreal Engine 5 payload, and the immutable seal of the Holy Spirit.</p>

        <p class="reveal-element">May the courage, the truth, and the fierce love of Saint John the Baptist guide every line of code within this system. To our brothers and sisters in Quebec, and to the entire community of believers worldwide: The Kingdom of God is at hand. Prepare the way.</p>
    </div>

    <div class="omahon reveal-element">
        <h3>OMAHON! OMAHON! OMAHON!</h3>
        <p style="font-size: 1.3rem; margin-bottom: 25px; color: #7dd3fc; text-shadow: 0 0 10px rgba(125, 211, 252, 0.4);">The Breath of God &mdash; The Seal That Protects the Kingdom</p>
        <div style="max-width: 600px; margin: 0 auto; border-radius: 16px; overflow: hidden; border: 2px solid rgba(250,204,21,0.4); box-shadow: 0 0 60px rgba(250,204,21,0.2), 0 0 120px rgba(56,189,248,0.1);">
            <img src="/assets/omahon-seal.png" alt="The Omahon Seal &mdash; Kingdom of God" style="width: 100%; display: block; border-radius: 14px;">
        </div>
    </div>

    <div class="reveal-element" style="text-align: center; margin: 80px 0 20px;">
        <a href="/download" style="display: inline-flex; align-items: center; justify-content: center; gap: 15px; background: linear-gradient(135deg, rgba(250,204,21,0.2), rgba(0,0,0,0.8)); color: #facc15; font-size: 1.5rem; font-weight: 900; padding: 25px 60px; border-radius: 50px; border: 2px solid #facc15; box-shadow: 0 0 40px rgba(250,204,21,0.3), inset 0 0 20px rgba(250,204,21,0.2); text-transform: uppercase; letter-spacing: 4px; transition: all 0.3s ease; text-decoration: none;">
            <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            DOWNLOAD ALFRED LINUX 7.77
        </a>
        <p style="margin-top: 25px; font-size: 1.1rem; color: #94a3b8; font-style: italic; letter-spacing: 1px;">The Forerunner is here. Prepare the way.</p>
    </div>
</div>

<footer>
    <p style="font-style:italic;color:#94a3b8;font-size:.85rem;">&ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo; &mdash; <a href="https://gositeme.com/bible/read/isaiah/40" style="color:#facc15;text-decoration:none;">Isaiah 40:8</a> (AKJV)</p>
    <p>&copy; <?= $year ?> <a href="https://gositeme.com">GoSiteMe Inc.</a> &mdash; Alfred Linux &middot; Open Source (KCL-1.0)</p>
</footer>

<div style="position: relative; z-index: 100;">
<?php include __DIR__ . "/includes/shabbat-banner.php"; ?>
</div>

<script>
document.querySelector('.nav-toggle')?.addEventListener('click', () => {
    const navLinks = document.querySelector('.nav-links');
    if(navLinks) navLinks.classList.toggle('open');
});

// 1. 3D Hologram Mouse Tracking & Divine Flashbang Array
const wrapper = document.querySelector('.hologram-wrapper');
const hologramPhysics = document.querySelector('.hologram-physics');
const hologramImg = document.querySelector('.hologram');

document.addEventListener('mousemove', (e) => {
    if (!wrapper || !hologramPhysics) return;
    const rect = wrapper.getBoundingClientRect();
    const centerX = rect.left + rect.width / 2;
    const centerY = rect.top + rect.height / 2;
    const x = e.clientX - centerX;
    const y = e.clientY - centerY;
    const rotateX = (y / (window.innerHeight / 2)) * -15; 
    const rotateY = (x / (window.innerWidth / 2)) * 15;
    hologramPhysics.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;
    hologramPhysics.style.boxShadow = `${-rotateY * 2}px ${rotateX * 2}px 80px rgba(0, 229, 255, 0.5), 0 0 100px rgba(0, 102, 255, 0.3)`;
});

document.addEventListener('mouseleave', () => {
    if(hologramPhysics) {
        hologramPhysics.style.transform = 'rotateX(0deg) rotateY(0deg) scale(1)';
        hologramPhysics.style.boxShadow = '0 0 50px rgba(0, 229, 255, 0.4), 0 0 100px rgba(0, 102, 255, 0.2)';
    }
});

const divineImages = [
    '/assets/hologram_john_baptist.png',
    '/assets/john_wilderness.png',
    '/assets/john_baptism.png',
    '/assets/john_preaching.png',
    '/assets/john_sky.png',
    '/assets/john_lamb.png',
    '/assets/john_staff.png',
    '/assets/john_prayer.png',
    '/assets/john_water.png',
    '/assets/john_cliff.png',
    '/assets/john_baptizing.png',
    '/assets/john_abstract.png',
    '/assets/john_cross.png'
];
let currentImgIndex = 0;

setInterval(() => {
    if(!hologramImg) return;
    
    // 1. The Flashbang & Spin
    hologramImg.style.transition = 'filter 0.6s ease-in, opacity 0.6s ease-in, transform 0.6s cubic-bezier(0.8, 0, 0.2, 1)';
    hologramImg.style.filter = 'brightness(5) contrast(1.5) drop-shadow(0 0 150px #fff)';
    hologramImg.style.opacity = '0';
    hologramImg.style.transform = 'scale(0.8) rotateY(90deg)';
    
    setTimeout(() => {
        // Change image at apex of the flash
        currentImgIndex = (currentImgIndex + 1) % divineImages.length;
        hologramImg.src = divineImages[currentImgIndex];
        
        // Reset rotation to opposite side for complete flip illusion
        hologramImg.style.transition = 'none';
        hologramImg.style.transform = 'scale(0.8) rotateY(-90deg)';
        
        // Force reflow
        void hologramImg.offsetWidth;
        
        // 2. Dissipate Flash & Reveal
        hologramImg.style.transition = 'filter 0.8s ease-out, opacity 0.8s ease-out, transform 0.8s cubic-bezier(0.2, 1, 0.3, 1)';
        hologramImg.style.filter = 'brightness(1) contrast(1) drop-shadow(0 0 0px transparent)';
        hologramImg.style.opacity = '1';
        hologramImg.style.transform = 'scale(1) rotateY(0deg)';
    }, 600);
}, 6000);

// 2. Cinematic Scroll Revelations
const observer = new IntersectionObserver((entries, obs) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.remove('hidden-start');
            obs.unobserve(entry.target);
        }
    });
}, { rootMargin: '50px' });

document.querySelectorAll('.reveal-element').forEach(el => {
    el.classList.add('hidden-start');
    observer.observe(el);
});

// 3. The Living Spirit Particle Engine
const canvas = document.getElementById('spirit-canvas');
if(canvas) {
    const ctx = canvas.getContext('2d');
    let width, height;
    let particles = [];
    let mouse = { x: -1000, y: -1000 };

    function resize() {
        width = canvas.width = window.innerWidth;
        height = canvas.height = window.innerHeight;
    }
    window.addEventListener('resize', resize);
    resize();

    window.addEventListener('mousemove', (e) => {
        mouse.x = e.clientX;
        mouse.y = e.clientY;
    });

    class Particle {
        constructor() {
            this.x = Math.random() * width;
            this.y = Math.random() * height;
            this.size = Math.random() * 2 + 0.5;
            this.speedX = Math.random() * 0.5 - 0.25;
            this.speedY = Math.random() * -0.5 - 0.1; 
            this.life = Math.random() * 100;
            this.color = Math.random() > 0.5 ? 'rgba(0, 229, 255,' : 'rgba(250, 204, 21,'; 
        }
        update() {
            this.x += this.speedX;
            this.y += this.speedY;
            let dx = mouse.x - this.x;
            let dy = mouse.y - this.y;
            let distance = Math.sqrt(dx*dx + dy*dy);
            if (distance < 150) {
                this.x -= dx * 0.02;
                this.y -= dy * 0.02;
            }
            if (this.y < 0) {
                this.y = height;
                this.x = Math.random() * width;
            }
            if (this.x < 0) this.x = width;
            if (this.x > width) this.x = 0;
        }
        draw() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            let alpha = Math.abs(Math.sin(Date.now() * 0.001 + this.life)) * 0.6 + 0.2;
            ctx.fillStyle = this.color + alpha + ')';
            ctx.fill();
        }
    }

    for (let i = 0; i < 200; i++) {
        particles.push(new Particle());
    }

    function animate() {
        ctx.clearRect(0, 0, width, height);
        for (let i = 0; i < particles.length; i++) {
            particles[i].update();
            particles[i].draw();
        }
        requestAnimationFrame(animate);
    }
    animate();
}
</script>
</body>
</html>
