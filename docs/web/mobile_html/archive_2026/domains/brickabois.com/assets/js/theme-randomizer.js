/**
 * Theme Randomizer - Cycle through beautiful color themes
 */

(function() {
    'use strict';

    // Available color themes
    const colorThemes = [
        'earth',
        'ocean',
        'forest',
        'sunset',
        'purple',
        'teal',
        'amber',
        'indigo',
        'emerald',
        'rose',
        'cyan',
        'brown',
        'bluegrey',
        'deeporange',
        'lime',
        'pink'
    ];

    // Theme names for display
    const themeNames = {
        'earth': '🌍 Earth',
        'ocean': '🌊 Ocean',
        'forest': '🌲 Forest',
        'sunset': '🌅 Sunset',
        'purple': '💜 Purple',
        'teal': '💎 Teal',
        'amber': '🔥 Amber',
        'indigo': '💙 Indigo',
        'emerald': '💚 Emerald',
        'rose': '🌹 Rose',
        'cyan': '💠 Cyan',
        'brown': '🍫 Brown',
        'bluegrey': '🌫️ Blue Grey',
        'deeporange': '🧡 Deep Orange',
        'lime': '💚 Lime',
        'pink': '🌸 Pink'
    };

    // Get current color theme
    function getCurrentColorTheme() {
        return document.documentElement.getAttribute('data-color-theme') || 'forest';
    }

    // Set color theme
    function setColorTheme(theme) {
        if (!colorThemes.includes(theme)) {
            theme = 'forest';
        }
        document.documentElement.setAttribute('data-color-theme', theme);
        localStorage.setItem('colorTheme', theme);
        updateThemeButton(theme);
    }

    // Get random theme (excluding current)
    function getRandomTheme() {
        const current = getCurrentColorTheme();
        let available = colorThemes.filter(t => t !== current);
        if (available.length === 0) {
            available = colorThemes;
        }
        const randomIndex = Math.floor(Math.random() * available.length);
        return available[randomIndex];
    }

    // Randomize theme
    function randomizeTheme() {
        const newTheme = getRandomTheme();
        setColorTheme(newTheme);
        
        // Add animation effect
        document.body.style.transition = 'background-color 0.5s ease, color 0.5s ease';
        setTimeout(() => {
            document.body.style.transition = '';
        }, 500);
    }

    // Cycle through themes sequentially
    function cycleTheme() {
        const current = getCurrentColorTheme();
        const currentIndex = colorThemes.indexOf(current);
        const nextIndex = (currentIndex + 1) % colorThemes.length;
        setColorTheme(colorThemes[nextIndex]);
    }

    // Update theme button text
    function updateThemeButton(theme) {
        const button = document.getElementById('colorThemeRandomizer');
        if (button) {
            const themeName = themeNames[theme] || theme;
            button.setAttribute('title', `Current: ${themeName}. Click to randomize!`);
            button.innerHTML = `<span class="theme-icon">🎨</span><span class="theme-text">${themeName}</span>`;
        }
    }

    // Initialize theme on load
    function initTheme() {
        const savedTheme = localStorage.getItem('colorTheme');
        if (savedTheme && colorThemes.includes(savedTheme)) {
            setColorTheme(savedTheme);
        } else {
            setColorTheme('forest');
        }
    }

    // Add theme randomizer button to navbar
    function addThemeButton() {
        const navbar = document.querySelector('.navbar .nav-links');
        if (!navbar) return;

        // Check if button already exists
        if (document.getElementById('colorThemeRandomizer')) return;

        const button = document.createElement('button');
        button.id = 'colorThemeRandomizer';
        button.className = 'color-theme-randomizer';
        button.setAttribute('title', 'Click to randomize color theme!');
        button.innerHTML = '<span class="theme-icon">🎨</span><span class="theme-text">Theme</span>';
        
        // Add click handler
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            randomizeTheme();
        });

        // Add double-click to cycle
        let clickTimer = null;
        button.addEventListener('dblclick', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (clickTimer) {
                clearTimeout(clickTimer);
                clickTimer = null;
            }
            cycleTheme();
        });

        // Insert before light theme switcher if it exists, otherwise append
        const lightSwitcher = document.getElementById('lightThemeSwitcher');
        if (lightSwitcher && lightSwitcher.parentNode) {
            navbar.insertBefore(button, lightSwitcher);
        } else {
            navbar.appendChild(button);
        }
    }

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initTheme();
        addThemeButton();
    });

    // Expose functions globally for manual control
    window.randomizeColorTheme = randomizeTheme;
    window.cycleColorTheme = cycleTheme;
    window.setColorTheme = setColorTheme;
    window.getColorTheme = getCurrentColorTheme;

})();

