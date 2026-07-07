// Mobile Menu Toggle
const mobileMenuToggle = document.createElement('button');
mobileMenuToggle.className = 'mobile-menu-toggle';
mobileMenuToggle.innerHTML = '<i class="fas fa-bars"></i>';
document.querySelector('.navbar').appendChild(mobileMenuToggle);

const navLinks = document.querySelector('.nav-links');
const authButtons = document.querySelector('.auth-buttons');

mobileMenuToggle.addEventListener('click', () => {
    mobileMenuToggle.classList.toggle('active');
    navLinks.classList.toggle('active');
    authButtons.classList.toggle('active');
});

// Close menu when clicking outside
document.addEventListener('click', (e) => {
    if (!e.target.closest('.navbar')) {
        mobileMenuToggle.classList.remove('active');
        navLinks.classList.remove('active');
        authButtons.classList.remove('active');
    }
});

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Plan selection animation
document.querySelectorAll('.select-plan').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        this.classList.add('clicked');
        setTimeout(() => {
            this.classList.remove('clicked');
            window.location.href = this.getAttribute('href');
        }, 300);
    });
});

// Domain search functionality
const domainSearch = document.querySelector('.search-container');
if (domainSearch) {
    const searchInput = domainSearch.querySelector('input');
    const searchSelect = domainSearch.querySelector('select');
    const searchButton = domainSearch.querySelector('.search-btn');

    searchButton.addEventListener('click', () => {
        const domain = searchInput.value.trim();
        const extension = searchSelect.value;
        if (domain) {
            alert(`Checking availability for ${domain}${extension}...`);
            // Here you would typically make an API call to check domain availability
        }
    });
}

// Intersection Observer for animations
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate');
        }
    });
}, {
    threshold: 0.1
});

// Observe elements for animation
document.querySelectorAll('.pricing-card, .feature-card, .security-card').forEach(el => {
    observer.observe(el);
});

// Pricing hover effects
document.querySelectorAll('.pricing-card').forEach(card => {
    card.addEventListener('mouseenter', () => {
        card.style.transform = 'translateY(-10px)';
    });

    card.addEventListener('mouseleave', () => {
        card.style.transform = 'translateY(0)';
    });
});

// Navbar scroll effect
let lastScroll = 0;
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    const currentScroll = window.pageYOffset;

    if (currentScroll > lastScroll) {
        navbar.style.transform = 'translateY(-100%)';
    } else {
        navbar.style.transform = 'translateY(0)';
    }

    if (currentScroll === 0) {
        navbar.classList.remove('scrolled');
    } else {
        navbar.classList.add('scrolled');
    }

    lastScroll = currentScroll;
});

document.addEventListener('DOMContentLoaded', () => {
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Intersection Observer for animations
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe elements for animation
    document.querySelectorAll('.hero-text, .hero-preview, .trust-badges, .pricing-card, .feature-card').forEach(el => {
        el.classList.add('animate-ready');
        observer.observe(el);
    });

    // Browser preview hover effect
    const browserPreview = document.querySelector('.browser-mockup');
    if (browserPreview) {
        browserPreview.addEventListener('mouseenter', () => {
            browserPreview.style.transform = 'scale(1.02) translateY(-5px)';
            browserPreview.style.boxShadow = '0 15px 40px rgba(0, 0, 0, 0.15)';
        });

        browserPreview.addEventListener('mouseleave', () => {
            browserPreview.style.transform = 'scale(1) translateY(0)';
            browserPreview.style.boxShadow = '0 10px 30px rgba(0, 0, 0, 0.1)';
        });
    }

    // Floating elements animation
    const floatingElements = document.querySelectorAll('.chat-icon, .notification, .stats-circle');
    floatingElements.forEach((el, index) => {
        el.style.animation = `float ${2 + index * 0.2}s ease-in-out infinite`;
    });

    // Add floating animation keyframes
    const style = document.createElement('style');
    style.textContent = `
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
    `;
    document.head.appendChild(style);

    // Mobile menu toggle
    const navbar = document.querySelector('.navbar');
    let lastScroll = window.pageYOffset;

    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        if (currentScroll > lastScroll && currentScroll > 100) {
            navbar.style.transform = 'translateY(-100%)';
        } else {
            navbar.style.transform = 'translateY(0)';
        }
        lastScroll = currentScroll;
    });

    // Stats circle animation
    const statsCircle = document.querySelector('.stats-circle path');
    if (statsCircle) {
        setTimeout(() => {
            statsCircle.style.strokeDasharray = '75, 100';
        }, 1000);
    }

    // Add mobile menu functionality
    const menuButton = document.createElement('button');
    menuButton.className = 'mobile-menu-btn';
    menuButton.innerHTML = '<i class="fas fa-bars"></i>';
    navbar.appendChild(menuButton);

    const navLinks = document.querySelector('.nav-links');
    const authButtons = document.querySelector('.auth-buttons');

    menuButton.addEventListener('click', () => {
        navLinks.classList.toggle('active');
        authButtons.classList.toggle('active');
        menuButton.classList.toggle('active');
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', (e) => {
        if (!navbar.contains(e.target) && navLinks.classList.contains('active')) {
            navLinks.classList.remove('active');
            authButtons.classList.remove('active');
            menuButton.classList.remove('active');
        }
    });
}); 