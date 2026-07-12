
// Interactive Phone Mockup Logic
const layerBoot = document.getElementById('layerBoot');
const layerLock = document.getElementById('layerLock');
const layerHome = document.getElementById('layerHome');
const swipeArea = document.getElementById('swipeArea');

if (layerBoot && layerLock && layerHome && swipeArea) {
    // 1. Boot sequence to Lock Screen transition
    setTimeout(() => {
        layerBoot.classList.remove('active');
        layerLock.classList.add('active');
        updateClock();
    }, 5000); // 5 seconds of boot terminal

    // 2. Swipe to unlock logic
    let isDragging = false;
    let startY = 0;

    swipeArea.addEventListener('mousedown', (e) => {
        isDragging = true;
        startY = e.clientY;
    });

    window.addEventListener('mouseup', () => {
        if (isDragging) {
            isDragging = false;
            swipeArea.style.transform = 'translateY(0)';
            swipeArea.style.opacity = '1';
        }
    });

    window.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        
        const deltaY = e.clientY - startY;
        if (deltaY < 0) { // Swiping up
            swipeArea.style.transform = `translateY(${deltaY}px)`;
            swipeArea.style.opacity = Math.max(0, 1 - Math.abs(deltaY) / 100);
            
            if (deltaY < -80) { // Threshold reached, unlock!
                isDragging = false;
                layerLock.classList.remove('active');
                layerHome.classList.add('active');
            }
        }
    });

    // Touch support for mobile
    swipeArea.addEventListener('touchstart', (e) => {
        isDragging = true;
        startY = e.touches[0].clientY;
    });

    window.addEventListener('touchend', () => {
        if (isDragging) {
            isDragging = false;
            swipeArea.style.transform = 'translateY(0)';
            swipeArea.style.opacity = '1';
        }
    });

    window.addEventListener('touchmove', (e) => {
        if (!isDragging) return;
        
        const deltaY = e.touches[0].clientY - startY;
        if (deltaY < 0) {
            swipeArea.style.transform = `translateY(${deltaY}px)`;
            swipeArea.style.opacity = Math.max(0, 1 - Math.abs(deltaY) / 100);
            
            if (deltaY < -80) {
                isDragging = false;
                layerLock.classList.remove('active');
                layerHome.classList.add('active');
            }
        }
    });

    // Live Clock for Lock Screen
    function updateClock() {
        const now = new Date();
        let h = now.getHours();
        let m = now.getMinutes();
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12;
        h = h ? h : 12; 
        m = m < 10 ? '0'+m : m;
        document.getElementById('lockTime').textContent = `${h}:${m}`;
        
        const options = { weekday: 'short', month: 'short', day: 'numeric' };
        document.getElementById('lockDate').textContent = now.toLocaleDateString('en-US', options);
        
        setTimeout(updateClock, 1000);
    }
}

// 3D Phone Manipulation Logic
const phone = document.getElementById('interactivePhone');
if (phone) {
    let isPhoneDragging = false;
    let startX, startY, startRotX = 0, startRotY = 0;
    let currentRotX = 0, currentRotY = 0;

    phone.addEventListener('mousedown', (e) => {
        // Prevent rotation if clicking inside the screen to swipe or click apps
        if(e.target.closest('.screen')) return;
        
        isPhoneDragging = true;
        startX = e.clientX;
        startY = e.clientY;
        phone.style.cursor = 'grabbing';
        phone.style.transition = 'none'; // Remove transition for smooth dragging
    });

    window.addEventListener('mouseup', () => {
        isPhoneDragging = false;
        phone.style.cursor = 'grab';
        phone.style.transition = 'transform 0.1s';
    });

    window.addEventListener('mousemove', (e) => {
        if (!isPhoneDragging) return;
        
        const deltaX = e.clientX - startX;
        const deltaY = e.clientY - startY;
        
        currentRotY = startRotY + (deltaX * 0.5);
        currentRotX = startRotX - (deltaY * 0.5);
        
        // Clamp X rotation so it doesn't flip upside down completely, but allow full Y rotation
        currentRotX = Math.max(-60, Math.min(60, currentRotX));

        phone.style.setProperty('--rx', `${currentRotX}deg`);
        phone.style.setProperty('--ry', `${currentRotY}deg`);
    });
    
    // Save rotation state when drag stops
    window.addEventListener('mouseup', () => {
        if(isPhoneDragging) {
            startRotX = currentRotX;
            startRotY = currentRotY;
        }
    });
}
