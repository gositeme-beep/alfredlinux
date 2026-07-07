document.addEventListener('DOMContentLoaded', function() {
    if (typeof AOS !== 'undefined') AOS.init({ duration: 600, once: true, offset: 50 });
    if (window._affLoggedIn) loadDashboard();

    // Event listeners (migrated from inline onclick)
    const btnRegister = document.getElementById('btnRegisterAffiliate');
    if (btnRegister) btnRegister.addEventListener('click', registerAffiliate);

    const btnCopy = document.getElementById('copyBtn');
    if (btnCopy) btnCopy.addEventListener('click', copyLink);

    const btnBanners = document.getElementById('btnLoadBanners');
    if (btnBanners) btnBanners.addEventListener('click', function() { loadAssets('banners'); });

    document.querySelectorAll('[data-social]').forEach(function(btn) {
        btn.addEventListener('click', function() { copySocial(this.dataset.social); });
    });

    document.querySelectorAll('[data-email]').forEach(function(btn) {
        btn.addEventListener('click', function() { copyEmail(this.dataset.email); });
    });

    document.querySelectorAll('.aff-faq-q').forEach(function(el) {
        el.addEventListener('click', function() { toggleFaq(this); });
    });
});

// Social post templates
const socialPosts = {
    twitter: "🚀 I've been using Alfred AI and it's incredible — 1,220+ AI tools in one platform. Try it free for 14 days! {link} #AI #AlfredAI #GoSiteMe",
    linkedin: "Looking for an all-in-one AI platform? Alfred AI by GoSiteMe has 1,220+ tools across 29 categories — writing, coding, legal, marketing, and more. 14-day free trial, plans from $3.99/mo.\n\nCheck it out: {link}",
    facebook: "Just discovered Alfred AI — an incredible platform with 1,220+ AI tools! From writing to coding to legal help. Try it free for 14 days 🤖\n\n{link}"
};

const emailTemplates = {
    intro: {
        subject: "Try Alfred AI — 1,220+ AI Tools in One Platform",
        body: "Hi {name},\n\nI wanted to share something great with you — Alfred AI by GoSiteMe. It's an all-in-one AI platform with 1,220+ tools for writing, coding, legal, marketing, and more.\n\nWhat I love about it:\n• 1,220+ AI tools across 29 categories\n• Voice-first — talk to Alfred naturally\n• Plans starting at $3.99/mo\n• 14-day free trial, no credit card required\n\nTry it here: {link}\n\nBest regards"
    },
    quick: {
        subject: "Check out this AI platform",
        body: "Hey,\n\nHave you tried Alfred AI? 1,220+ AI tools, voice commands, and it starts at $3.99/mo. Free 14-day trial.\n\n{link}\n\nWorth checking out!"
    }
};

let affiliateLink = 'https://gositeme.com/?ref=YOUR_ID';

async function apiCall(action, method = 'GET', body = null) {
    const opts = { method, credentials: 'include', headers: {} };
    if (body) {
        opts.headers['Content-Type'] = 'application/x-www-form-urlencoded';
        opts.headers['X-CSRF-Token'] = window.AW_CSRF_TOKEN || '';
        opts.body = new URLSearchParams(body).toString();
    }
    const sep = action.includes('?') ? '&' : '?';
    const res = await fetch('/api/affiliates.php?action=' + action, opts);
    return res.json();
}

async function loadDashboard() {
    const loading = document.getElementById('dashLoading');
    const register = document.getElementById('dashRegister');
    const active = document.getElementById('dashActive');

    try {
        const stats = await apiCall('get_stats');
        loading.style.display = 'none';

        if (stats.error && stats.register) {
            register.style.display = 'block';
            return;
        }

        if (stats.success) {
            active.style.display = 'block';
            document.getElementById('statTotalRefs').textContent = stats.total_referrals || 0;
            document.getElementById('statActiveRefs').textContent = stats.active_referrals || 0;
            document.getElementById('statTotalEarnings').textContent = '$' + (stats.total_commission || 0).toFixed(2);
            document.getElementById('statPending').textContent = '$' + (stats.pending_payout || 0).toFixed(2);
            document.getElementById('referralLink').value = stats.referral_link || affiliateLink;
            affiliateLink = stats.referral_link || affiliateLink;

            // Tier badge
            const tier = (stats.tier || 'bronze').charAt(0).toUpperCase() + (stats.tier || 'bronze').slice(1);
            document.getElementById('tierBadge').textContent = tier;

            // Load referrals
            loadReferrals();
        }
    } catch (e) {
        loading.innerHTML = '<p style="color:var(--al-danger);">Failed to load dashboard. Please try again.</p>';
        console.error('Dashboard load error:', e);
    }
}

async function loadReferrals() {
    try {
        const data = await apiCall('get_referrals');
        const tbody = document.getElementById('referralsBody');

        if (data.success && data.referrals && data.referrals.length > 0) {
            tbody.innerHTML = data.referrals.slice(0, 20).map(r => {
                const date = new Date(r.created_at).toLocaleDateString();
                const status = r.status || 'clicked';
                const revenue = r.revenue ? '$' + parseFloat(r.revenue).toFixed(2) : '—';
                const commission = r.commission ? '$' + parseFloat(r.commission).toFixed(2) : '—';
                return `<tr>
                    <td>${date}</td>
                    <td style="font-family:monospace;font-size:0.82rem;">${r.referral_id || '—'}</td>
                    <td><span class="aff-status ${status}">${status.replace('_',' ')}</span></td>
                    <td>${revenue}</td>
                    <td>${commission}</td>
                </tr>`;
            }).join('');
        }
    } catch (e) {
        console.error('Referrals load error:', e);
    }
}

async function registerAffiliate() {
    try {
        const data = await apiCall('register', 'POST');
        if (data.success) {
            // Reload dashboard
            document.getElementById('dashRegister').style.display = 'none';
            document.getElementById('dashLoading').style.display = 'block';
            loadDashboard();
        } else {
            alert(data.error || 'Registration failed. Please try again.');
        }
    } catch (e) {
        alert('Registration failed. Please try again.');
        console.error('Register error:', e);
    }
}

function copyLink() {
    const input = document.getElementById('referralLink');
    input.select();
    document.execCommand('copy');
    try { navigator.clipboard.writeText(input.value); } catch(e) {}

    const btn = document.getElementById('copyBtn');
    btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
    btn.classList.add('copied');
    setTimeout(() => {
        btn.innerHTML = '<i class="fas fa-copy"></i> Copy';
        btn.classList.remove('copied');
    }, 2000);
}

function copySocial(platform) {
    const text = socialPosts[platform].replace('{link}', affiliateLink);
    navigator.clipboard.writeText(text).then(() => {
        showToast('Social post copied to clipboard!');
    }).catch(() => {
        fallbackCopy(text);
    });
}

function copyEmail(type) {
    const tmpl = emailTemplates[type];
    const text = 'Subject: ' + tmpl.subject + '\n\n' + tmpl.body.replace(/{link}/g, affiliateLink);
    navigator.clipboard.writeText(text).then(() => {
        showToast('Email template copied to clipboard!');
    }).catch(() => {
        fallbackCopy(text);
    });
}

function fallbackCopy(text) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    showToast('Copied to clipboard!');
}

function showToast(msg) {
    if (window.GDSToast) return GDSToast.success(msg);
}

function loadAssets(type) {
    if (type === 'banners') {
        showToast('Banner pack download starting...');
        // In production, this would trigger a download from the server
        window.open('/api/affiliates.php?action=get_assets', '_blank');
    }
}

function toggleFaq(el) {
    const item = el.parentElement;
    const wasActive = item.classList.contains('active');
    // Close all
    document.querySelectorAll('.aff-faq-item').forEach(i => i.classList.remove('active'));
    // Toggle clicked
    if (!wasActive) item.classList.add('active');
}
