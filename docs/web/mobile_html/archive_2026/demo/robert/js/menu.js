// Menu data
const menuData = {
    'appetizers': [
        {
            id: 1,
            name: 'Rouleaux de Printemps',
            description: 'Rouleaux de printemps croustillants aux légumes servis avec sauce chili douce',
            price: 6.99,
            image: 'images/menu/spring-rolls.jpg',
            rating: 4.5,
            reviewCount: 128,
            isVegetarian: true,
            isSpicy: false,
            isGlutenFree: false
        },
        {
            id: 2,
            name: 'Boulettes de Porc',
            description: 'Boulettes de porc et légumes cuites à la vapeur avec sauce soja',
            price: 7.99,
            image: 'images/menu/dumplings.jpg',
            rating: 4.7,
            reviewCount: 156,
            isVegetarian: false,
            isSpicy: false,
            isGlutenFree: false
        }
    ],
    'soups': [
        {
            id: 3,
            name: 'Soupe Aigre-Douce',
            description: 'Soupe traditionnelle chinoise au tofu, champignons et pousses de bambou',
            price: 5.99,
            image: 'images/menu/hot-sour-soup.jpg',
            rating: 4.6,
            reviewCount: 112,
            isVegetarian: true,
            isSpicy: true,
            isGlutenFree: true
        },
        {
            id: 4,
            name: 'Soupe Wonton',
            description: 'Bouillon clair avec wontons au porc et aux crevettes',
            price: 6.99,
            image: 'images/menu/wonton-soup.jpg',
            rating: 4.4,
            reviewCount: 89,
            isVegetarian: false,
            isSpicy: false,
            isGlutenFree: false
        }
    ],
    'main-courses': [
        {
            id: 5,
            name: 'Poulet Général Tso',
            description: 'Poulet croustillant dans une sauce sucrée et épicée',
            price: 14.99,
            image: 'images/menu/general-tsos.jpg',
            rating: 4.8,
            reviewCount: 245,
            isVegetarian: false,
            isSpicy: true,
            isGlutenFree: false
        },
        {
            id: 6,
            name: 'Bœuf au Brocoli',
            description: 'Lanières de bœuf tendres avec brocoli frais dans une sauce à l\'ail',
            price: 15.99,
            image: 'images/menu/beef-broccoli.jpg',
            rating: 4.5,
            reviewCount: 178,
            isVegetarian: false,
            isSpicy: false,
            isGlutenFree: true
        }
    ],
    'noodles': [
        {
            id: 7,
            name: 'Pad Thai',
            description: 'Nouilles de riz sautées avec œuf, légumes et cacahuètes',
            price: 12.99,
            image: 'images/menu/pad-thai.jpg',
            rating: 4.6,
            reviewCount: 189,
            isVegetarian: true,
            isSpicy: false,
            isGlutenFree: true
        },
        {
            id: 8,
            name: 'Lo Mein',
            description: 'Nouilles de blé sautées avec légumes et choix de protéines',
            price: 11.99,
            image: 'images/menu/lo-mein.jpg',
            rating: 4.4,
            reviewCount: 134,
            isVegetarian: false,
            isSpicy: false,
            isGlutenFree: false
        }
    ],
    'rice': [
        {
            id: 9,
            name: 'Riz Frit',
            description: 'Riz sauté avec œuf, légumes et choix de protéines',
            price: 10.99,
            image: 'images/menu/fried-rice.jpg',
            rating: 4.3,
            reviewCount: 167,
            isVegetarian: false,
            isSpicy: false,
            isGlutenFree: true
        },
        {
            id: 10,
            name: 'Riz Frit Yangzhou',
            description: 'Riz frit premium avec crevettes, jambon et légumes',
            price: 12.99,
            image: 'images/menu/yangzhou-rice.jpg',
            rating: 4.7,
            reviewCount: 98,
            isVegetarian: false,
            isSpicy: false,
            isGlutenFree: true
        }
    ],
    'desserts': [
        {
            id: 11,
            name: 'Riz Gluant à la Mangue',
            description: 'Riz gluant sucré avec mangue fraîche et lait de coco',
            price: 6.99,
            image: 'images/menu/mango-sticky-rice.jpg',
            rating: 4.8,
            reviewCount: 145,
            isVegetarian: true,
            isSpicy: false,
            isGlutenFree: true
        },
        {
            id: 12,
            name: 'Crêpe aux Haricots Rouges',
            description: 'Crêpe croustillante fourrée à la pâte de haricots rouges',
            price: 5.99,
            image: 'images/menu/red-bean-pancake.jpg',
            rating: 4.5,
            reviewCount: 112,
            isVegetarian: true,
            isSpicy: false,
            isGlutenFree: false
        }
    ],
    'beverages': [
        {
            id: 13,
            name: 'Thé à Bulles',
            description: 'Thé au lait sucré avec perles de tapioca',
            price: 4.99,
            image: 'images/menu/bubble-tea.jpg',
            rating: 4.7,
            reviewCount: 234,
            isVegetarian: true,
            isSpicy: false,
            isGlutenFree: true
        },
        {
            id: 14,
            name: 'Thé au Jasmin',
            description: 'Thé chinois traditionnel au jasmin',
            price: 3.99,
            image: 'images/menu/jasmine-tea.jpg',
            rating: 4.4,
            reviewCount: 156,
            isVegetarian: true,
            isSpicy: false,
            isGlutenFree: true
        }
    ]
};

// Expose menuData to global scope
window.menuData = menuData;

// DOM Elements
const menuItemsGrid = document.getElementById('menu-items');
const searchInput = document.getElementById('menu-search');
const filterButtons = document.querySelectorAll('.filter-btn');
const dietaryButtons = document.querySelectorAll('.dietary-btn');

// Initialize menu
document.addEventListener('DOMContentLoaded', () => {
    loadMenuItems();
    
    // Add event listeners
    filterButtons.forEach(button => {
        button.addEventListener('click', handleCategoryFilter);
    });

    dietaryButtons.forEach(button => {
        button.addEventListener('click', handleDietaryFilter);
    });

    searchInput.addEventListener('input', filterMenuItems);

    // Menu page hero slideshow
    const slides = document.querySelectorAll('.hero-slide');
    let currentSlide = 0;
    const slideCount = slides.length;

    function showSlide(index) {
        // Remove active class from all slides
        slides.forEach(slide => slide.classList.remove('active'));
        
        // Add active class to current slide
        slides[index].classList.add('active');
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % slideCount;
        showSlide(currentSlide);
    }

    // Show first slide initially
    showSlide(currentSlide);

    // Change slide every 4 seconds
    setInterval(nextSlide, 4000);

    // Add language change listener
    const langToggle = document.getElementById('langToggle');
    if (langToggle) {
        langToggle.addEventListener('click', () => {
            const currentLang = document.documentElement.lang;
            const newLang = currentLang === 'fr' ? 'en' : 'fr';
            document.documentElement.lang = newLang;
            loadMenuItems(getActiveCategory(), getActiveDietaryFilters());
        });
    }
});

function loadMenuItems(category = 'all', dietaryFilters = []) {
    menuItemsGrid.innerHTML = '';
    
    let items = [];
    if (category === 'all') {
        // Get all items from all categories
        Object.values(menuData).forEach(categoryItems => {
            items = items.concat(categoryItems);
        });
    } else {
        // Get items from specific category
        items = menuData[category] || [];
    }

    // Apply dietary filters
    if (dietaryFilters.length > 0) {
        items = items.filter(item => {
            if (dietaryFilters.includes('vegetarian') && !item.isVegetarian) return false;
            if (dietaryFilters.includes('vegan') && !item.isVegan) return false;
            if (dietaryFilters.includes('spicy') && !item.isSpicy) return false;
            return true;
        });
    }

    // Display items
    items.forEach(item => {
        const card = createMenuItemCard(item);
        menuItemsGrid.appendChild(card);
    });
}

function createMenuItemCard(item) {
    const card = document.createElement('div');
    card.className = 'menu-item';
    
    // Get current language
    const currentLang = document.documentElement.lang || 'fr';
    
    // Get translations for dietary tags
    const vegetarianText = translations[currentLang]['tag-vegetarian'] || 'Vegetarian';
    const spicyText = translations[currentLang]['tag-spicy'] || 'Spicy';
    const glutenFreeText = translations[currentLang]['tag-gluten-free'] || 'Gluten Free';
    
    card.innerHTML = `
        <img src="${item.image}" alt="${translations[currentLang][item.name] || item.name}" class="menu-item-image">
        <div class="menu-item-content">
            <div class="menu-item-header">
                <h3>${translations[currentLang][item.name] || item.name}</h3>
                <span class="price">$${item.price.toFixed(2)}</span>
            </div>
            <p>${translations[currentLang][item.name + '-desc'] || item.description}</p>
            <div class="menu-item-footer">
                <div class="rating">
                    ${generateStarRating(item.rating)}
                    <span>(${item.reviewCount})</span>
                </div>
                <div class="tags">
                    ${item.isVegetarian ? `<span class="tag vegetarian">${vegetarianText}</span>` : ''}
                    ${item.isSpicy ? `<span class="tag spicy">${spicyText}</span>` : ''}
                    ${item.isGlutenFree ? `<span class="tag gluten-free">${glutenFreeText}</span>` : ''}
                </div>
            </div>
        </div>
    `;
    return card;
}

function generateStarRating(rating) {
    const fullStars = Math.floor(rating);
    const halfStar = rating % 1 >= 0.5;
    const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);
    
    let stars = '';
    for (let i = 0; i < fullStars; i++) {
        stars += '<i class="fas fa-star"></i>';
    }
    if (halfStar) {
        stars += '<i class="fas fa-star-half-alt"></i>';
    }
    for (let i = 0; i < emptyStars; i++) {
        stars += '<i class="far fa-star"></i>';
    }
    return stars;
}

function filterMenuItems() {
    const searchTerm = searchInput.value.toLowerCase();
    const activeCategory = getActiveCategory();
    const dietaryFilters = getActiveDietaryFilters();
    
    loadMenuItems(activeCategory, dietaryFilters);
    
    // Filter by search term
    const items = menuItemsGrid.querySelectorAll('.menu-item');
    items.forEach(item => {
        const name = item.querySelector('h3').textContent.toLowerCase();
        const description = item.querySelector('p').textContent.toLowerCase();
        const matchesSearch = name.includes(searchTerm) || description.includes(searchTerm);
        item.style.display = matchesSearch ? 'block' : 'none';
    });
}

function handleCategoryFilter(e) {
    // Remove active class from all buttons
    filterButtons.forEach(btn => btn.classList.remove('active'));
    // Add active class to clicked button
    e.target.classList.add('active');
    // Load items for selected category
    const category = e.target.dataset.category;
    const dietaryFilters = getActiveDietaryFilters();
    loadMenuItems(category, dietaryFilters);
}

function handleDietaryFilter(e) {
    e.target.classList.toggle('active');
    const activeCategory = getActiveCategory();
    const dietaryFilters = getActiveDietaryFilters();
    loadMenuItems(activeCategory, dietaryFilters);
}

function getActiveCategory() {
    const activeButton = document.querySelector('.filter-btn.active');
    return activeButton ? activeButton.dataset.category : 'all';
}

function getActiveDietaryFilters() {
    const activeButtons = document.querySelectorAll('.dietary-btn.active');
    return Array.from(activeButtons).map(btn => btn.dataset.filter);
} 