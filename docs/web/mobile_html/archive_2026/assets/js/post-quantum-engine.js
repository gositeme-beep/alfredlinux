// Accordion
document.querySelectorAll('.v-acc-header').forEach(h => {
    h.addEventListener('click', () => {
        const item = h.parentElement;
        const body = item.querySelector('.v-acc-body');
        const inner = body.querySelector('.v-acc-body-inner');
        const isOpen = item.classList.contains('open');
        document.querySelectorAll('.v-acc-item.open').forEach(o => {
            o.classList.remove('open');
            o.querySelector('.v-acc-body').style.maxHeight = '0';
        });
        if (!isOpen) {
            item.classList.add('open');
            body.style.maxHeight = inner.scrollHeight + 24 + 'px';
        }
    });
});

// Android download — show install instructions if not on Android
document.getElementById('android-dl-btn')?.addEventListener('click', function(e) {
    if (!/Android/i.test(navigator.userAgent)) {
        e.preventDefault();
        const msg = 'The Android APK is designed for Android devices.\n\n' +
                    'To install:\n' +
                    '1. Open this page on your Android device\n' +
                    '2. Tap "Download APK"\n' +
                    '3. Allow install from unknown sources when prompted\n' +
                    '4. Open the downloaded file to install';
        alert(msg);
    }
});
