document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabs = document.querySelectorAll('.wl-config-tab');
    const panels = document.querySelectorAll('.wl-tab-panel');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            const target = document.getElementById('tab-' + this.dataset.tab);
            if (target) target.classList.add('active');
        });
    });

    // Color sync
    const primaryColor = document.getElementById('wlPrimaryColor');
    const primaryText = document.getElementById('wlPrimaryColorText');
    const secondaryColor = document.getElementById('wlSecondaryColor');
    const secondaryText = document.getElementById('wlSecondaryColorText');

    if (primaryColor && primaryText) {
        primaryColor.addEventListener('input', function() {
            primaryText.value = this.value;
            updatePreview();
        });
        primaryText.addEventListener('input', function() {
            if (/^#[0-9a-fA-F]{6}$/.test(this.value)) {
                primaryColor.value = this.value;
                updatePreview();
            }
        });
    }
    if (secondaryColor && secondaryText) {
        secondaryColor.addEventListener('input', function() {
            secondaryText.value = this.value;
            updatePreview();
        });
        secondaryText.addEventListener('input', function() {
            if (/^#[0-9a-fA-F]{6}$/.test(this.value)) {
                secondaryColor.value = this.value;
                updatePreview();
            }
        });
    }

    // Live preview updates
    const companyName = document.getElementById('wlCompanyName');
    const fontFamily = document.getElementById('wlFontFamily');
    if (companyName) companyName.addEventListener('input', updatePreview);
    if (fontFamily) fontFamily.addEventListener('change', updatePreview);

    // Load config on page load (logged-in users)
    if (window._wlLoggedIn) loadConfig();
});

function updatePreview() {
    const logo = document.getElementById('wlPreviewLogo');
    const brand = document.getElementById('wlPreviewBrand');
    const nav = document.getElementById('wlPreviewNav');
    const btn = document.getElementById('wlPreviewBtn');
    const box = document.getElementById('wlPreviewBody');

    const name = document.getElementById('wlCompanyName')?.value || 'Your Brand';
    const primary = document.getElementById('wlPrimaryColor')?.value || '#6c5ce7';
    const secondary = document.getElementById('wlSecondaryColor')?.value || '#a29bfe';
    const font = document.getElementById('wlFontFamily')?.value || 'Inter';

    if (logo) {
        logo.style.background = primary;
        logo.textContent = name.charAt(0).toUpperCase();
    }
    if (brand) brand.textContent = name;
    if (btn) btn.style.background = primary;
    if (box) box.style.fontFamily = font + ', sans-serif';
    if (nav) nav.style.borderLeft = '3px solid ' + primary;
}

function loadConfig() {
    fetch('/api/white-label.php?action=config')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const c = data.config;
            setVal('wlCompanyName', c.company_name);
            setVal('wlLogoData', c.logo_data);
            setVal('wlPrimaryColorText', c.primary_color);
            setVal('wlPrimaryColor', c.primary_color);
            setVal('wlSecondaryColorText', c.secondary_color);
            setVal('wlSecondaryColor', c.secondary_color);
            setVal('wlFontFamily', c.font_family);
            setVal('wlCustomCSS', c.custom_css);
            setVal('wlCustomDomain', c.custom_domain);
            setVal('wlEmailSender', c.email_sender_name);
            setVal('wlEmailReplyTo', c.email_reply_to);
            setVal('wlWelcomeTemplate', c.welcome_template);
            setVal('wlNotificationTemplate', c.notification_template);
            setVal('wlVoiceGreeting', c.voice_greeting);
            setVal('wlVoiceCompanyName', c.voice_company_name);
            setVal('wlHoldMusicUrl', c.hold_music_url);

            // Domain status
            if (c.domain_verified) {
                const ds = document.getElementById('wlDomainStatus');
                if (ds) {
                    ds.className = 'wl-status wl-status-verified';
                    ds.innerHTML = '<i class="fas fa-check-circle"></i> Verified';
                }
                const ss = document.getElementById('wlSslStatus');
                if (ss) {
                    ss.className = 'wl-status wl-status-verified';
                    ss.innerHTML = '<i class="fas fa-check-circle"></i> Active';
                }
            }

            // Feature toggles
            if (c.feature_toggles && typeof c.feature_toggles === 'object') {
                document.querySelectorAll('[data-feature]').forEach(input => {
                    const feat = input.dataset.feature;
                    if (feat in c.feature_toggles) {
                        input.checked = !!c.feature_toggles[feat];
                    }
                });
            }

            updatePreview();
        })
        .catch(err => console.error('Failed to load config:', err));
}

function saveConfig() {
    const toggles = {};
    document.querySelectorAll('[data-feature]').forEach(input => {
        toggles[input.dataset.feature] = input.checked;
    });

    const payload = {
        company_name: getVal('wlCompanyName'),
        logo_data: getVal('wlLogoData'),
        primary_color: getVal('wlPrimaryColorText') || getVal('wlPrimaryColor'),
        secondary_color: getVal('wlSecondaryColorText') || getVal('wlSecondaryColor'),
        font_family: getVal('wlFontFamily'),
        custom_css: getVal('wlCustomCSS'),
        custom_domain: getVal('wlCustomDomain'),
        email_sender_name: getVal('wlEmailSender'),
        email_reply_to: getVal('wlEmailReplyTo'),
        welcome_template: getVal('wlWelcomeTemplate'),
        notification_template: getVal('wlNotificationTemplate'),
        voice_greeting: getVal('wlVoiceGreeting'),
        voice_company_name: getVal('wlVoiceCompanyName'),
        hold_music_url: getVal('wlHoldMusicUrl'),
        feature_toggles: toggles
    };

    const msg = document.getElementById('wlSaveMsg');

    fetch('/api/white-label.php?action=config', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            msg.className = 'wl-save-msg success';
            msg.textContent = 'Configuration saved successfully!';
        } else {
            msg.className = 'wl-save-msg error';
            msg.textContent = data.error || 'Failed to save.';
        }
        setTimeout(() => { msg.textContent = ''; }, 5000);
    })
    .catch(err => {
        msg.className = 'wl-save-msg error';
        msg.textContent = 'Network error. Please try again.';
        setTimeout(() => { msg.textContent = ''; }, 5000);
    });
}

function verifyDomain() {
    const msg = document.getElementById('wlSaveMsg');

    fetch('/api/white-label.php?action=verify-domain', { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            const ds = document.getElementById('wlDomainStatus');
            const ss = document.getElementById('wlSslStatus');
            if (data.verified) {
                if (ds) { ds.className = 'wl-status wl-status-verified'; ds.innerHTML = '<i class="fas fa-check-circle"></i> Verified'; }
                if (ss) { ss.className = 'wl-status wl-status-verified'; ss.innerHTML = '<i class="fas fa-check-circle"></i> Active'; }
                msg.className = 'wl-save-msg success';
                msg.textContent = 'Domain verified!';
            } else {
                if (ds) { ds.className = 'wl-status wl-status-unverified'; ds.innerHTML = '<i class="fas fa-times-circle"></i> Not Verified'; }
                msg.className = 'wl-save-msg error';
                msg.textContent = data.message || 'Domain verification failed.';
            }
            setTimeout(() => { msg.textContent = ''; }, 6000);
        })
        .catch(err => {
            msg.className = 'wl-save-msg error';
            msg.textContent = 'Verification request failed.';
            setTimeout(() => { msg.textContent = ''; }, 5000);
        });
}

function getVal(id) {
    const el = document.getElementById(id);
    return el ? el.value : '';
}
function setVal(id, val) {
    const el = document.getElementById(id);
    if (el && val) el.value = val;
}
