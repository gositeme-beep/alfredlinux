// Language detection and translation system
const translations = {
    en: {
        // Main content
        domainUnderConstruction: "Domain Under Construction",
        subtitle: "This domain is currently under construction and being held by GoSiteMe.com. Access your account or learn about our enterprise-grade domain and hosting services.",
        enterpriseGrade: "Enterprise-Grade",
        digitalInfrastructure: "Digital Infrastructure",
        premiumPlatform: "Premium Platform Launching Soon",
        clientLogin: "Client Login",
        createAccount: "Create Account",
        accessAccount: "Access Your GoSiteMe Account",
        accessAccountDesc: "Manage your domains, hosting, and services through our secure client portal",
        instantDomainRegistration: "Instant Domain Registration",
        instantDomainRegistrationDesc: "Check availability and register domains instantly through our automated system",
        enterDomainName: "Enter domain name",
        checkAvailability: "Check Availability",
        checking: "Checking...",
        popularExtensions: "Popular Extensions",
        platformDevelopment: "Platform Development",
        getStarted: "Get Started",
        getStartedDesc: "Ready to build your online presence? Start with professional domain and hosting services.",
        enterpriseSolutions: "Enterprise Solutions",
        enterpriseSolutionsDesc: "Trusted by businesses worldwide for their digital infrastructure needs",
        enterpriseSales: "Enterprise Sales",
        stayUpdated: "Stay Updated",
        stayUpdatedDesc: "Get notified about new services, pricing updates, and exclusive enterprise offers.",
        emailPlaceholder: "Enter your email address",
        subscribe: "Subscribe",
        services: "Services",
        contact: "Contact",
        support: "Support",
        copyright: "© 2025 GoSiteMe.com. Professional domain services and hosting solutions.",
        visitGoSiteMe: "Visit GoSiteMe.com",
        
        // Feature cards
        domainManagement: "Domain Management",
        domainManagementDesc: "Advanced domain registration, DNS management, and bulk operations for enterprise clients.",
        webHosting: "Web Hosting",
        webHostingDesc: "High-performance hosting with global CDN, 99.99% uptime, and auto-scaling infrastructure.",
        securitySSL: "Security & SSL",
        securitySSLDesc: "Enterprise-grade security with DDoS protection, SSL certificates, and compliance standards.",
        
        // Domain status messages
        available: "Available",
        taken: "Taken",
        domainAlreadyRegistered: "Domain Already Registered",
        invalidDomain: "Invalid Domain",
        
        // Buttons
        registerDomainNow: "Register Domain Now",
        checkAlternatives: "Check Alternatives",
        viewAllServices: "View All Services",
        
        // Messages
        redirectingToRegistration: "Redirecting to domain registration for",
        domainTakenMessage: "Domain is taken. Check our backorder services or contact sales for alternatives.",
        subscribedMessage: "You're subscribed! We'll keep you updated on domain services.",
        pleaseEnterDomain: "Please enter a domain name",
        domainTooShort: "Domain name must be at least 2 characters long",
        domainInvalidChars: "Domain name can only contain letters, numbers, and hyphens"
    },
    fr: {
        // Main content
        domainUnderConstruction: "Domaine en Construction",
        subtitle: "Ce domaine est actuellement en construction et géré par GoSiteMe.com. Accédez à votre compte ou découvrez nos services d'hébergement et de domaines de niveau entreprise.",
        enterpriseGrade: "Niveau Entreprise",
        digitalInfrastructure: "Infrastructure Numérique",
        premiumPlatform: "Plateforme Premium Bientôt Disponible",
        clientLogin: "Connexion Client",
        createAccount: "Créer un Compte",
        accessAccount: "Accédez à Votre Compte GoSiteMe",
        accessAccountDesc: "Gérez vos domaines, hébergement et services via notre portail client sécurisé",
        instantDomainRegistration: "Enregistrement Instantané de Domaine",
        instantDomainRegistrationDesc: "Vérifiez la disponibilité et enregistrez des domaines instantanément via notre système automatisé",
        enterDomainName: "Entrez le nom de domaine",
        checkAvailability: "Vérifier la Disponibilité",
        checking: "Vérification...",
        popularExtensions: "Extensions Populaires",
        platformDevelopment: "Développement de Plateforme",
        getStarted: "Commencer",
        getStartedDesc: "Prêt à construire votre présence en ligne ? Commencez par des services professionnels de domaine et d'hébergement.",
        enterpriseSolutions: "Solutions Entreprise",
        enterpriseSolutionsDesc: "Approuvé par les entreprises du monde entier pour leurs besoins d'infrastructure numérique",
        enterpriseSales: "Ventes Entreprise",
        stayUpdated: "Restez Informé",
        stayUpdatedDesc: "Soyez notifié des nouveaux services, mises à jour de prix et offres exclusives entreprise.",
        emailPlaceholder: "Entrez votre adresse e-mail",
        subscribe: "S'abonner",
        services: "Services",
        contact: "Contact",
        support: "Support",
        copyright: "© 2025 GoSiteMe.com. Services de domaine professionnels et solutions d'hébergement.",
        visitGoSiteMe: "Visiter GoSiteMe.com",
        
        // Feature cards
        domainManagement: "Gestion de Domaines",
        domainManagementDesc: "Enregistrement de domaines avancé, gestion DNS et opérations en masse pour clients entreprise.",
        webHosting: "Hébergement Web",
        webHostingDesc: "Hébergement haute performance avec CDN global, 99,99% de disponibilité et infrastructure d'auto-mise à l'échelle.",
        securitySSL: "Sécurité et SSL",
        securitySSLDesc: "Sécurité de niveau entreprise avec protection DDoS, certificats SSL et normes de conformité.",
        
        // Domain status messages
        available: "Disponible",
        taken: "Pris",
        domainAlreadyRegistered: "Domaine Déjà Enregistré",
        invalidDomain: "Domaine Invalide",
        
        // Buttons
        registerDomainNow: "Enregistrer le Domaine Maintenant",
        checkAlternatives: "Vérifier les Alternatives",
        viewAllServices: "Voir Tous les Services",
        
        // Messages
        redirectingToRegistration: "Redirection vers l'enregistrement de domaine pour",
        domainTakenMessage: "Le domaine est pris. Vérifiez nos services de réservation ou contactez les ventes pour des alternatives.",
        subscribedMessage: "Vous êtes abonné ! Nous vous tiendrons informé des services de domaine.",
        pleaseEnterDomain: "Veuillez entrer un nom de domaine",
        domainTooShort: "Le nom de domaine doit contenir au moins 2 caractères",
        domainInvalidChars: "Le nom de domaine ne peut contenir que des lettres, chiffres et tirets"
    }
};

// Detect user's preferred language (default to English)
function detectLanguage() {
    const browserLang = navigator.language || navigator.userLanguage;
    const langCode = browserLang.split('-')[0];
    // Only use French if explicitly French, otherwise default to English
    return (langCode === 'fr') ? 'fr' : 'en';
}

// Set language and update content
function setLanguage(lang) {
    document.documentElement.lang = lang;
    document.documentElement.setAttribute('data-lang', lang);
    
    const t = translations[lang];
    if (!t) return;
    
    // Update all translatable elements
    const elements = document.querySelectorAll('[data-translate]');
    elements.forEach(el => {
        const key = el.getAttribute('data-translate');
        if (t[key]) {
            el.textContent = t[key];
        }
    });
    
    // Update placeholders
    const inputs = document.querySelectorAll('[data-translate-placeholder]');
    inputs.forEach(input => {
        const key = input.getAttribute('data-translate-placeholder');
        if (t[key]) {
            input.placeholder = t[key];
        }
    });
    
    // Update button text
    const buttons = document.querySelectorAll('[data-translate-button]');
    buttons.forEach(button => {
        const key = button.getAttribute('data-translate-button');
        if (t[key]) {
            button.innerHTML = t[key];
        }
    });
    
    // Update language switcher active state
    const langButtons = document.querySelectorAll('.lang-btn');
    langButtons.forEach(btn => {
        btn.classList.remove('active');
        if (btn.textContent === lang.toUpperCase()) {
            btn.classList.add('active');
        }
    });
}

// Initialize language on page load
document.addEventListener('DOMContentLoaded', function() {
    const userLang = detectLanguage();
    setLanguage(userLang);
    
    // Add language switcher (only once)
    addLanguageSwitcher();
});

// Add language switcher to the page (prevents duplicates)
function addLanguageSwitcher() {
    // Check if language switcher already exists
    if (document.querySelector('.language-switcher')) {
        return;
    }
    
    // Place language switcher in the top-right corner, separate from logo
    const header = document.querySelector('.main-content');
    if (header) {
        const langSwitcher = document.createElement('div');
        langSwitcher.className = 'language-switcher';
        langSwitcher.innerHTML = `
            <button class="lang-btn" onclick="setLanguage('en')">EN</button>
            <button class="lang-btn" onclick="setLanguage('fr')">FR</button>
        `;
        header.insertBefore(langSwitcher, header.firstChild);
        
        // Set initial active state
        const userLang = document.documentElement.lang || 'en';
        const activeBtn = langSwitcher.querySelector(`[onclick="setLanguage('${userLang}')"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }
        
        // Ensure English is active by default
        if (userLang === 'en') {
            const enBtn = langSwitcher.querySelector('[onclick="setLanguage(\'en\')"]');
            if (enBtn) enBtn.classList.add('active');
        }
    }
}
