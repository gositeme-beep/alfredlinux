// Testimonial Carousel
document.addEventListener('DOMContentLoaded', function() {
    const testimonials = document.querySelectorAll('.testimonial');
    const dots = document.querySelectorAll('.dot');
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    let currentIndex = 0;
    let slideInterval;

    function showTestimonial(index) {
        testimonials.forEach(testimonial => {
            testimonial.style.opacity = '0';
            testimonial.style.transform = 'translateX(100%)';
            testimonial.classList.remove('active');
        });
        dots.forEach(dot => dot.classList.remove('active'));
        
        testimonials[index].style.opacity = '1';
        testimonials[index].style.transform = 'translateX(0)';
        testimonials[index].classList.add('active');
        dots[index].classList.add('active');
        currentIndex = index;
    }

    function nextTestimonial() {
        currentIndex = (currentIndex + 1) % testimonials.length;
        showTestimonial(currentIndex);
    }

    function prevTestimonial() {
        currentIndex = (currentIndex - 1 + testimonials.length) % testimonials.length;
        showTestimonial(currentIndex);
    }

    function startAutoSlide() {
        slideInterval = setInterval(nextTestimonial, 5000);
    }

    function stopAutoSlide() {
        clearInterval(slideInterval);
    }

    // Initialize first testimonial
    testimonials.forEach(testimonial => {
        testimonial.style.transition = 'all 0.5s ease';
    });
    showTestimonial(0);
    startAutoSlide();

    // Event listeners
    prevBtn.addEventListener('click', () => {
        stopAutoSlide();
        prevTestimonial();
        startAutoSlide();
    });

    nextBtn.addEventListener('click', () => {
        stopAutoSlide();
        nextTestimonial();
        startAutoSlide();
    });

    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            stopAutoSlide();
            showTestimonial(index);
            startAutoSlide();
        });
    });

    // Pause auto-slide on hover
    const slider = document.querySelector('.testimonial-slider');
    slider.addEventListener('mouseenter', stopAutoSlide);
    slider.addEventListener('mouseleave', startAutoSlide);
});

// Sample product data
const products = [
    {
        id: 1,
        name: "Grilled Salmon",
        description: "Fresh Atlantic salmon with lemon butter sauce",
        price: 24.99,
        image: "/images/salmon.jpg",
        category: "main-courses",
        rating: 4.8,
        reviewCount: 120
    },
    {
        id: 2,
        name: "Beef Tenderloin",
        description: "Premium cut with red wine reduction",
        price: 32.99,
        image: "/images/beef.jpg",
        category: "main-courses",
        rating: 4.9,
        reviewCount: 95
    },
    {
        id: 3,
        name: "Chocolate Lava Cake",
        description: "Warm chocolate cake with vanilla ice cream",
        price: 12.99,
        image: "/images/lava-cake.jpg",
        category: "desserts",
        rating: 4.7,
        reviewCount: 150
    },
    {
        id: 4,
        name: "Poutine Classique",
        description: "Hand-cut fries, authentic cheese curds, rich gravy",
        price: 14.99,
        image: "/images/poutine.jpg",
        category: "appetizers",
        rating: 4.9,
        reviewCount: 280
    },
    {
        id: 5,
        name: "Souvlaki Platter",
        description: "Greek-style marinated chicken skewers with tzatziki",
        price: 22.99,
        image: "/images/souvlaki.jpg",
        category: "main-courses",
        rating: 4.7,
        reviewCount: 165
    },
    {
        id: 6,
        name: "Coq au Vin",
        description: "Classic French braised chicken in red wine sauce",
        price: 26.99,
        image: "/images/coq-au-vin.jpg",
        category: "main-courses",
        rating: 4.8,
        reviewCount: 142
    },
    {
        id: 7,
        name: "Paella Valenciana",
        description: "Spanish saffron rice with seafood and chorizo",
        price: 28.99,
        image: "/images/paella.jpg",
        category: "main-courses",
        rating: 4.6,
        reviewCount: 98
    },
    {
        id: 8,
        name: "Tourtière",
        description: "Traditional Québécois meat pie with flaky crust",
        price: 19.99,
        image: "/images/tourtiere.jpg",
        category: "main-courses",
        rating: 4.7,
        reviewCount: 175
    },
    {
        id: 9,
        name: "Moussaka",
        description: "Layered Greek eggplant casserole with béchamel",
        price: 23.99,
        image: "/images/moussaka.jpg",
        category: "main-courses",
        rating: 4.8,
        reviewCount: 132
    },
    {
        id: 10,
        name: "Crème Brûlée",
        description: "Classic French vanilla custard with caramelized sugar",
        price: 11.99,
        image: "/images/creme-brulee.jpg",
        category: "desserts",
        rating: 4.9,
        reviewCount: 190
    },
    {
        id: 11,
        name: "General Tao Chicken",
        description: "Crispy chicken in sweet and spicy sauce",
        price: 21.99,
        image: "/images/general-tao.jpg",
        category: "main-courses",
        rating: 4.7,
        reviewCount: 145
    },
    {
        id: 12,
        name: "Baklava",
        description: "Honey-sweetened phyllo pastry with nuts",
        price: 9.99,
        image: "/images/baklava.jpg",
        category: "desserts",
        rating: 4.8,
        reviewCount: 110
    },
    {
        id: 13,
        name: "Gazpacho",
        description: "Chilled Spanish tomato soup with fresh vegetables",
        price: 12.99,
        image: "/images/gazpacho.jpg",
        category: "appetizers",
        rating: 4.5,
        reviewCount: 85
    },
    {
        id: 14,
        name: "Boeuf Bourguignon",
        description: "French-style braised beef with red wine and mushrooms",
        price: 27.99,
        image: "/images/boeuf-bourguignon.jpg",
        category: "main-courses",
        rating: 4.8,
        reviewCount: 156
    },
    {
        id: 15,
        name: "Spanakopita",
        description: "Greek spinach and feta cheese pie in phyllo",
        price: 16.99,
        image: "/images/spanakopita.jpg",
        category: "appetizers",
        rating: 4.6,
        reviewCount: 95
    },
    {
        id: 16,
        name: "Bagel & Lox Platter",
        description: "Montreal-style bagel with smoked salmon and cream cheese",
        price: 18.99,
        image: "/images/bagel-lox.jpg",
        category: "appetizers",
        rating: 4.9,
        reviewCount: 220
    },
    {
        id: 17,
        name: "Pad Thai",
        description: "Thai rice noodles with shrimp, tofu, and peanuts",
        price: 20.99,
        image: "/images/pad-thai.jpg",
        category: "main-courses",
        rating: 4.7,
        reviewCount: 168
    },
    {
        id: 18,
        name: "Osso Buco",
        description: "Italian braised veal shanks with gremolata",
        price: 34.99,
        image: "/images/osso-buco.jpg",
        category: "main-courses",
        rating: 4.8,
        reviewCount: 112
    },
    {
        id: 19,
        name: "Shakshuka",
        description: "Middle Eastern eggs poached in spiced tomato sauce",
        price: 17.99,
        image: "/images/shakshuka.jpg",
        category: "main-courses",
        rating: 4.6,
        reviewCount: 95
    },
    {
        id: 20,
        name: "Maple Crêpes",
        description: "Traditional French crêpes with Quebec maple syrup",
        price: 15.99,
        image: "/images/crepes.jpg",
        category: "desserts",
        rating: 4.8,
        reviewCount: 185
    },
    {
        id: 21,
        name: "Vietnamese Pho",
        description: "Rice noodle soup with beef and fresh herbs",
        price: 19.99,
        image: "/images/pho.jpg",
        category: "main-courses",
        rating: 4.7,
        reviewCount: 145
    },
    {
        id: 22,
        name: "Portuguese Grilled Sardines",
        description: "Fresh sardines with olive oil and herbs",
        price: 21.99,
        image: "/images/sardines.jpg",
        category: "main-courses",
        rating: 4.6,
        reviewCount: 88
    },
    {
        id: 23,
        name: "Lebanese Mezze Platter",
        description: "Hummus, tabbouleh, falafel, and pita bread",
        price: 24.99,
        image: "/images/mezze.jpg",
        category: "appetizers",
        rating: 4.8,
        reviewCount: 175
    },
    {
        id: 24,
        name: "Pouding Chômeur",
        description: "Traditional Quebec maple pudding cake",
        price: 10.99,
        image: "/images/pouding-chomeur.jpg",
        category: "desserts",
        rating: 4.9,
        reviewCount: 160
    },
    {
        id: 25,
        name: "Polish Pierogi",
        description: "Dumplings filled with potato and cheese",
        price: 16.99,
        image: "/images/pierogi.jpg",
        category: "appetizers",
        rating: 4.7,
        reviewCount: 130
    }
];

// Initialize cart from localStorage
let cart = JSON.parse(localStorage.getItem('cart')) || [];
updateCartCount();

// Cache DOM elements
const DOM = {
    heroSection: document.querySelector('.hero'),
    heroSlides: document.querySelectorAll('.hero-slide'),
    menuToggle: document.querySelector('.menu-toggle'),
    mainNav: document.querySelector('.main-nav'),
    newsletterForm: document.querySelector('.newsletter-form')
};

// Constants
const NOTIFICATION_DURATION = 3000;

// Mobile menu toggle
function initMobileMenu() {
    DOM.menuToggle.addEventListener('click', () => {
        DOM.mainNav.classList.toggle('active');
        DOM.menuToggle.setAttribute('aria-expanded', 
            DOM.mainNav.classList.contains('active'));
    });

    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
        if (!DOM.mainNav.contains(e.target) && 
            !DOM.menuToggle.contains(e.target) && 
            DOM.mainNav.classList.contains('active')) {
            DOM.mainNav.classList.remove('active');
            DOM.menuToggle.setAttribute('aria-expanded', 'false');
        }
    });
}

// Newsletter form handling
function initNewsletter() {
    if (!DOM.newsletterForm) return;

    DOM.newsletterForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const emailInput = DOM.newsletterForm.querySelector('input[type="email"]');
        const email = emailInput.value.trim();

        if (!isValidEmail(email)) {
            showNotification('Please enter a valid email address', 'error');
            return;
        }

        try {
            // Here you would typically send the email to your server
            // For now, we'll just show a success message
            showNotification('Thank you for subscribing!', 'success');
            DOM.newsletterForm.reset();
        } catch (error) {
            showNotification('An error occurred. Please try again later.', 'error');
        }
    });
}

// Email validation
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Notification system
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.setAttribute('role', 'alert');
    notification.setAttribute('aria-live', 'polite');
    
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    // Trigger reflow
    notification.offsetHeight;
    
    notification.classList.add('show');
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, NOTIFICATION_DURATION);
}

// Load featured products
function loadFeaturedProducts() {
    const productsGrid = document.getElementById('featured-products');
    products.forEach(product => {
        const productCard = createProductCard(product);
        productsGrid.appendChild(productCard);
    });
}

// Create product card
function createProductCard(product) {
    const card = document.createElement('div');
    card.className = 'product-card';
    card.innerHTML = `
        <img src="${product.image}" alt="${product.name}" class="product-image">
        <div class="product-info">
            <h3 class="product-title">${product.name}</h3>
            <p class="product-description">${product.description}</p>
            <div class="product-meta">
                <span class="product-price">$${product.price.toFixed(2)}</span>
                <span class="product-rating">
                    ${generateStarRating(product.rating)}
                    <span class="review-count">(${product.reviewCount})</span>
                </span>
            </div>
            <button class="add-to-cart-btn" data-id="${product.id}">Add to Cart</button>
        </div>
    `;

    const addToCartBtn = card.querySelector('.add-to-cart-btn');
    addToCartBtn.addEventListener('click', () => {
        addToCart(product);
    });

    return card;
}

// Add product to cart
function addToCart(product) {
    const existingItem = cart.find(item => item.id === product.id);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: product.price,
            image: product.image,
            quantity: 1
        });
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    showNotification(`${product.name} added to cart!`, 'success');
}

// Update cart count in header
function updateCartCount() {
    const cartCount = document.querySelector('.cart-count');
    const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
    cartCount.textContent = totalItems;
}

// Generate star rating HTML
function generateStarRating(rating) {
    const fullStars = Math.floor(rating);
    const halfStar = rating % 1 >= 0.5;
    const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);
    
    let starsHTML = '';
    
    // Add full stars
    for (let i = 0; i < fullStars; i++) {
        starsHTML += '<i class="fas fa-star"></i>';
    }
    
    // Add half star if needed
    if (halfStar) {
        starsHTML += '<i class="fas fa-star-half-alt"></i>';
    }
    
    // Add empty stars
    for (let i = 0; i < emptyStars; i++) {
        starsHTML += '<i class="far fa-star"></i>';
    }
    
    return starsHTML;
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing...');
    initMobileMenu();
    initNewsletter();
    loadFeaturedProducts();
}); 