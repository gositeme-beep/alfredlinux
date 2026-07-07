class Particles {
    constructor() {
        this.canvas = document.getElementById('canvas');
        this.ctx = this.canvas.getContext('2d');
        this.particles = [];
        this.mouse = { x: 0, y: 0 };
        this.isMobile = window.innerWidth <= 768;
        this.count = this.isMobile ? 80 : 150;
        
        this.init();
        this.animate();
        this.resize();
        this.mouseMove();
    }
    
    init() {
        this.create();
    }
    
    resize() {
        window.addEventListener('resize', () => {
            this.isMobile = window.innerWidth <= 768;
            this.count = this.isMobile ? 80 : 150;
            this.canvas.width = window.innerWidth;
            this.canvas.height = window.innerHeight;
            this.particles = [];
            this.create();
        });
    }
    
    create() {
        this.canvas.width = window.innerWidth;
        this.canvas.height = window.innerHeight;
        
        for (let i = 0; i < this.count; i++) {
            this.particles.push({
                x: Math.random() * this.canvas.width,
                y: Math.random() * this.canvas.height,
                r: Math.random() * 2 + 1,
                vx: (Math.random() - 0.5) * 0.8,
                vy: (Math.random() - 0.5) * 0.8,
                color: this.getColor(),
                opacity: Math.random() * 0.6 + 0.4,
                life: Math.random() * 200 + 100,
                pulse: Math.random() * Math.PI * 2
            });
        }
    }
    
    getColor() {
        const colors = [
            'rgba(201, 169, 97, 0.8)',
            'rgba(212, 175, 106, 0.7)',
            'rgba(255, 255, 255, 0.5)',
            'rgba(201, 169, 97, 0.6)',
            'rgba(212, 175, 106, 0.5)'
        ];
        return colors[Math.floor(Math.random() * colors.length)];
    }
    
    mouseMove() {
        window.addEventListener('mousemove', (e) => {
            this.mouse.x = e.clientX;
            this.mouse.y = e.clientY;
        });
    }
    
    update() {
        for (let i = 0; i < this.particles.length; i++) {
            const p = this.particles[i];
            
            p.x += p.vx;
            p.y += p.vy;
            
            const dx = this.mouse.x - p.x;
            const dy = this.mouse.y - p.y;
            const dist = Math.sqrt(dx * dx + dy * dy);
            
            if (dist < 200) {
                const force = (200 - dist) / 200;
                p.vx -= (dx / dist) * force * 0.05;
                p.vy -= (dy / dist) * force * 0.05;
            }
            
            if (p.x < 0 || p.x > this.canvas.width) {
                p.vx *= -1;
                p.x = Math.max(0, Math.min(this.canvas.width, p.x));
            }
            if (p.y < 0 || p.y > this.canvas.height) {
                p.vy *= -1;
                p.y = Math.max(0, Math.min(this.canvas.height, p.y));
            }
            
            p.life -= 0.3;
            if (p.life <= 0) {
                p.x = Math.random() * this.canvas.width;
                p.y = Math.random() * this.canvas.height;
                p.life = Math.random() * 200 + 100;
                p.opacity = Math.random() * 0.6 + 0.4;
            }
            
            p.pulse += 0.05;
            p.currentOpacity = p.opacity * (Math.sin(p.pulse) * 0.2 + 0.8);
        }
    }
    
    draw() {
        this.ctx.fillStyle = 'rgba(0, 0, 0, 0.1)';
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
        
        for (let i = 0; i < this.particles.length; i++) {
            for (let j = i + 1; j < this.particles.length; j++) {
                const dx = this.particles[i].x - this.particles[j].x;
                const dy = this.particles[i].y - this.particles[j].y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                
                if (dist < 150) {
                    const opacity = (1 - dist / 150) * 0.3;
                    const grad = this.ctx.createLinearGradient(
                        this.particles[i].x, this.particles[i].y,
                        this.particles[j].x, this.particles[j].y
                    );
                    grad.addColorStop(0, `rgba(201, 169, 97, ${opacity})`);
                    grad.addColorStop(1, `rgba(212, 175, 106, ${opacity})`);
                    
                    this.ctx.strokeStyle = grad;
                    this.ctx.lineWidth = 1.5;
                    this.ctx.beginPath();
                    this.ctx.moveTo(this.particles[i].x, this.particles[i].y);
                    this.ctx.lineTo(this.particles[j].x, this.particles[j].y);
                    this.ctx.stroke();
                }
            }
        }
        
        for (let i = 0; i < this.particles.length; i++) {
            const p = this.particles[i];
            
            const grad = this.ctx.createRadialGradient(
                p.x, p.y, 0,
                p.x, p.y, p.r * 4
            );
            grad.addColorStop(0, p.color.replace('0.8', p.currentOpacity));
            grad.addColorStop(0.5, p.color.replace('0.8', p.currentOpacity * 0.5));
            grad.addColorStop(1, p.color.replace('0.8', '0'));
            
            this.ctx.fillStyle = grad;
            this.ctx.beginPath();
            this.ctx.arc(p.x, p.y, p.r * 4, 0, Math.PI * 2);
            this.ctx.fill();
            
            this.ctx.fillStyle = p.color.replace('0.8', p.currentOpacity);
            this.ctx.beginPath();
            this.ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            this.ctx.fill();
            
            this.ctx.fillStyle = `rgba(255, 255, 255, ${p.currentOpacity})`;
            this.ctx.beginPath();
            this.ctx.arc(p.x, p.y, p.r * 0.5, 0, Math.PI * 2);
            this.ctx.fill();
        }
    }
    
    animate() {
        this.update();
        this.draw();
        requestAnimationFrame(() => this.animate());
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new Particles();
});

