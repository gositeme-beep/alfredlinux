/* ===== Billing Toggle ===== */
let isAnnual = false;

function toggleBilling() {
    isAnnual = !isAnnual;
    const toggle = document.getElementById('prToggle');
    const monthlyLabel = document.getElementById('prMonthlyLabel');
    const annualLabel = document.getElementById('prAnnualLabel');

    toggle.classList.toggle('annual', isAnnual);
    monthlyLabel.classList.toggle('active', !isAnnual);
    annualLabel.classList.toggle('active', isAnnual);

    document.querySelectorAll('.pr-price .amount').forEach(el => {
        const monthly = el.dataset.monthly;
        const annual = el.dataset.annual;
        if (monthly && annual) {
            el.textContent = isAnnual ? annual : monthly;
        }
    });

    document.querySelectorAll('.pr-original-price').forEach(el => {
        const original = el.dataset.original;
        if (original) {
            el.innerHTML = isAnnual ? '<s>' + original + '</s>' : '';
        }
    });

    document.querySelectorAll('.pr-annual-total').forEach(el => {
        const total = el.dataset.annualTotal;
        if (total) {
            el.textContent = isAnnual ? total : '';
        }
    });
}

/* ===== FAQ Accordion ===== */
function toggleFaq(el) {
    const item = el.closest('.pr-faq-item');
    const wasOpen = item.classList.contains('open');
    document.querySelectorAll('.pr-faq-item').forEach(i => i.classList.remove('open'));
    if (!wasOpen) item.classList.add('open');
}

/* ===== Stripe Checkout ===== */
async function startCheckout(plan) {
    try {
        const authResp = await fetch('/api/auth.php?action=check', { credentials: 'same-origin' });
        const authData = await authResp.json();
        if (!authData.success || !authData.logged_in) {
            window.location.href = '/alfred.php?plan=' + encodeURIComponent(plan) + '&billing=' + (isAnnual ? 'annual' : 'monthly');
            return;
        }

        const btn = event.target.closest('.pr-btn') || event.target;
        const origHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + (window.PR_LOADING_TEXT || 'Loading...') + '';
        btn.disabled = true;

        const resp = await fetch('/api/stripe.php?action=create_checkout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                plan: plan,
                billing_period: isAnnual ? 'annual' : 'monthly'
            })
        });
        const data = await resp.json();

        if (data.success && (data.checkout_url || data.url)) {
            window.location.href = data.checkout_url || data.url;
        } else if (data.redirect) {
            window.location.href = data.redirect;
        } else {
            btn.innerHTML = origHTML;
            btn.disabled = false;
            if (data.error === 'Login required' || data.error === 'Authentication required') {
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
