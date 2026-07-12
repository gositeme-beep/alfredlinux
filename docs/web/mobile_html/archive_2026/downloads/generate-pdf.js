const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

// Check what's available
const checks = ['wkhtmltopdf', 'chromium-browser', 'chromium', 'google-chrome', 'puppeteer'];
checks.forEach(tool => {
    try {
        const result = execSync(`which ${tool} 2>/dev/null || echo "not found"`).toString().trim();
        console.log(`${tool}: ${result}`);
    } catch(e) {
        console.log(`${tool}: not found`);
    }
});

// Check npm global packages
try {
    const npm = execSync('npm list -g --depth=0 2>/dev/null').toString();
    console.log('\nNPM globals:\n', npm);
} catch(e) {}

// Check php extensions
try {
    const php = execSync('php -m 2>/dev/null | grep -i pdf').toString();
    console.log('\nPHP PDF modules:', php);
} catch(e) { console.log('No PHP PDF modules'); }

// Check composer packages
try {
    const comp = execSync('find /home -name "composer.json" 2>/dev/null | head -5').toString();
    console.log('\nComposer files:', comp);
} catch(e) {}
