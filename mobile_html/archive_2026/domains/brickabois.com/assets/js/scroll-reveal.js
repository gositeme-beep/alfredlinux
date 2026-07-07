/**
 * Advanced Scroll Reveal Animations
 */

document.addEventListener('DOMContentLoaded', () => {
    try {
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                    
                    // Add stagger effect for children
                    const children = entry.target.querySelectorAll('.reveal-child');
                    children.forEach((child, index) => {
                        setTimeout(() => {
                            child.classList.add('active');
                        }, index * 100);
                    });
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        // Observe all reveal elements (only those that have the class)
        document.querySelectorAll('.reveal').forEach(el => {
            revealObserver.observe(el);
        });
    } catch (error) {
        console.error('Scroll reveal error:', error);
    }
});

// Parallax scroll effect
window.addEventListener('scroll', () => {
    const scrolled = window.pageYOffset;
    const parallaxElements = document.querySelectorAll('[data-parallax]');
    
    parallaxElements.forEach(element => {
        const speed = parseFloat(element.getAttribute('data-parallax')) || 0.5;
        const yPos = -(scrolled * speed);
        element.style.transform = `translateY(${yPos}px)`;
    });
});

