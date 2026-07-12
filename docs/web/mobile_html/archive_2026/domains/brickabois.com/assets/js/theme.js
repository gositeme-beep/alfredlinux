/**
 * Theme Switcher - Light/Dark Mode with Color Theme Randomization
 */

// Light color themes (brighter, more vibrant)
const lightColorThemes = [
    'cyan',
    'lime',
    'teal',
    'amber',
    'rose',
    'pink',
    'emerald'
];

// Dark color themes (darker, more muted)
const darkColorThemes = [
    'earth',
    'ocean',
    'forest',
    'sunset',
    'purple',
    'indigo',
    'brown',
    'bluegrey',
    'deeporange',
    'teal'
];

// Get current theme
function getTheme() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        return savedTheme;
    }
    return 'dark'; // Default to dark
}

// Get current color theme
function getColorTheme() {
    return document.documentElement.getAttribute('data-color-theme') || 'forest';
}

// Set theme (light or dark)
function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
}

// Set color theme
function setColorTheme(colorTheme) {
    document.documentElement.setAttribute('data-color-theme', colorTheme);
    localStorage.setItem('colorTheme', colorTheme);
}

// Get random light color theme
function getRandomLightTheme() {
    const current = getColorTheme();
    let available = lightColorThemes.filter(t => t !== current);
    if (available.length === 0) {
        available = lightColorThemes;
    }
    const randomIndex = Math.floor(Math.random() * available.length);
    return available[randomIndex];
}

// Get random dark color theme
function getRandomDarkTheme() {
    const current = getColorTheme();
    let available = darkColorThemes.filter(t => t !== current);
    if (available.length === 0) {
        available = darkColorThemes;
    }
    const randomIndex = Math.floor(Math.random() * available.length);
    return available[randomIndex];
}

// Randomize light color theme
function randomizeLightTheme() {
    setTheme('light');
    const newColorTheme = getRandomLightTheme();
    setColorTheme(newColorTheme);
    
    // Add animation effect
    document.body.style.transition = 'background-color 0.5s ease, color 0.5s ease';
    setTimeout(() => {
        document.body.style.transition = '';
    }, 500);
}

// Randomize dark color theme
function randomizeDarkTheme() {
    setTheme('dark');
    const newColorTheme = getRandomDarkTheme();
    setColorTheme(newColorTheme);
    
    // Add animation effect
    document.body.style.transition = 'background-color 0.5s ease, color 0.5s ease';
    setTimeout(() => {
        document.body.style.transition = '';
    }, 500);
}

// Initialize theme immediately
(function() {
    const theme = getTheme();
    setTheme(theme);
    const colorTheme = localStorage.getItem('colorTheme') || (theme === 'light' ? 'cyan' : 'forest');
    setColorTheme(colorTheme);
})();

// Add event listeners after DOM loads
document.addEventListener('DOMContentLoaded', () => {
    const lightSwitcher = document.getElementById('lightThemeSwitcher');
    const darkSwitcher = document.getElementById('darkThemeSwitcher');
    
    if (lightSwitcher) {
        lightSwitcher.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            randomizeLightTheme();
        });
    }
    
    if (darkSwitcher) {
        darkSwitcher.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            randomizeDarkTheme();
        });
    }
});

// Expose functions globally
window.randomizeLightTheme = randomizeLightTheme;
window.randomizeDarkTheme = randomizeDarkTheme;
window.setTheme = setTheme;
window.setColorTheme = setColorTheme;
