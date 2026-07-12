/* ===== Typing Effect ===== */
const phrases = [
    '"Draft my contract for Acme Corp..."',
    '"Analyze this quarterly data..."',
    '"Schedule my team for next week..."',
    '"Find me grants for my nonprofit..."',
    '"Review this pull request..."',
    '"Create a marketing campaign..."',
    '"Summarize these legal documents..."',
    '"Generate an SEO audit report..."'
];
let phraseIdx = 0, charIdx = 0, deleting = false;
const typingEl = document.getElementById('alTyping');

function typeLoop() {
    const current = phrases[phraseIdx];
    if (!deleting) {
        typingEl.textContent = current.substring(0, charIdx + 1);
        charIdx++;
        if (charIdx === current.length) {
            deleting = true;
            setTimeout(typeLoop, 2000);
            return;
        }
        setTimeout(typeLoop, 50);
    } else {
        typingEl.textContent = current.substring(0, charIdx - 1);
        charIdx--;
        if (charIdx === 0) {
            deleting = false;
            phraseIdx = (phraseIdx + 1) % phrases.length;
            setTimeout(typeLoop, 400);
            return;
        }
        setTimeout(typeLoop, 30);
    }
}
typeLoop();

/* ===== Pricing Toggle ===== */
let isAnnual = false;
function togglePricing() {
    isAnnual = !isAnnual;
    const toggle = document.getElementById('pricingToggle');
    const mLabel = document.getElementById('monthlyLabel');
    const aLabel = document.getElementById('annualLabel');
    toggle.classList.toggle('on', isAnnual);
    mLabel.classList.toggle('active', !isAnnual);
    aLabel.classList.toggle('active', isAnnual);

    document.querySelectorAll('.al-price-amount .amount').forEach(el => {
        const monthly = el.dataset.monthly;
        const annual = el.dataset.annual;
        el.textContent = isAnnual ? annual : monthly;
    });
    document.querySelectorAll('.al-price-original').forEach(el => {
        el.style.display = isAnnual ? 'block' : 'none';
    });
}

/* ===== FAQ Accordion ===== */
function toggleFaq(btn) {
    const answer = btn.nextElementSibling;
    const isOpen = btn.classList.contains('open');
    /* Close all */
    document.querySelectorAll('.al-faq-q').forEach(q => q.classList.remove('open'));
    document.querySelectorAll('.al-faq-a').forEach(a => a.classList.remove('show'));
    if (!isOpen) {
        btn.classList.add('open');
        answer.classList.add('show');
    }
}

/* ===== Scroll Reveal ===== */
const revealEls = document.querySelectorAll('.al-reveal');
const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            revealObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.12 });
revealEls.forEach(el => revealObserver.observe(el));

/* ===== Smooth Scroll ===== */
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

/* ===== Email Signup ===== */
function handleSignup(e) {
    e.preventDefault();
    const input = e.target.querySelector('input[type="email"]');
    const email = input.value.trim();
    if (!email) return;
    /* Redirect to Alfred with email pre-filled for registration */
    window.location.href = '/alfred.php?signup=' + encodeURIComponent(email);
}

/* ===== Stripe Checkout ===== */
async function startCheckout(plan) {
    /* Check if user is logged in */
    try {
        const authResp = await fetch('/api/auth.php?action=check', { credentials: 'same-origin' });
        const authData = await authResp.json();
        if (!authData.success || !authData.logged_in) {
            /* Not logged in — redirect to Alfred with the plan they want */
            window.location.href = '/alfred.php?plan=' + encodeURIComponent(plan) + '&billing=' + (isAnnual ? 'annual' : 'monthly');
            return;
        }

        /* User is logged in — create Stripe checkout session */
        const btn = event.target.closest('.al-price-btn') || event.target;
        const origHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        btn.disabled = true;

        const resp = await fetch('/api/stripe.php?action=create_checkout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                plan: plan,
                interval: isAnnual ? 'year' : 'month'
            })
        });
        const data = await resp.json();

        if (data.success && data.checkout_url) {
            window.location.href = data.checkout_url;
        } else {
            btn.innerHTML = origHTML;
            btn.disabled = false;
            if (data.error === 'Login required') {
                window.location.href = '/alfred.php?plan=' + encodeURIComponent(plan);
            } else {
                alert(data.error || 'Something went wrong. Please try again.');
            }
        }
    } catch (err) {
        console.error('Checkout error:', err);
        alert('Network error. Please check your connection and try again.');
    }
}
