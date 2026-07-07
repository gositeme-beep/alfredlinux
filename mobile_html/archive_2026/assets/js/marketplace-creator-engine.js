/**
 * GoSiteMe Marketplace Creator Engine v2.0
 * Extracted from marketplace-creator.php inline JS
 */
const API = '/api/marketplace-creator.php';
let revenueChartInstance = null;
let analyticsChartInstance = null;
let currentProductsPage = 1;
let currentReviewsPage = 1;
let screenshotData = [null,null,null,null,null];
let iconData = null;

// ─── Navigation ───
function crNav(section, el) {
    document.querySelectorAll('.cr-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.cr-nav-item').forEach(n => n.classList.remove('active'));
    const sec = document.getElementById('sec-' + section);
    if (sec) sec.classList.add('active');
    if (el) el.classList.add('active');
    document.getElementById('crSidebar').classList.remove('open');

    // Load data for each section
    if (section === 'dashboard') loadDashboard();
    else if (section === 'products') loadProducts();
    else if (section === 'earnings') loadEarnings();
    else if (section === 'reviews') loadReviews();
    else if (section === 'analytics') { loadAnalyticsProductList(); loadAnalytics(''); }
}

// ─── Toast ───
function showToast(msg, type = 'success') {
    if (window.GDSToast) return GDSToast.show(msg, { type: type === 'error' ? 'danger' : type });
}

// ─── API Fetch ───
async function apiFetch(action, options = {}) {
    try {
        const url = API + '?action=' + action + (options.params || '');
        const fetchOpts = { credentials: 'same-origin' };
        if (options.method) fetchOpts.method = options.method;
        if (options.body) {
            fetchOpts.method = fetchOpts.method || 'POST';
            fetchOpts.headers = { 'Content-Type': 'application/json', 'X-CSRF-Token': window.AW_CSRF_TOKEN || '' };
            fetchOpts.body = JSON.stringify(options.body);
        }
        const resp = await fetch(url, fetchOpts);
        return await resp.json();
    } catch (e) {
        console.error('API Error:', e);
        showToast('Network error. Please retry.', 'error');
        return { success: false, error: 'Network error' };
    }
}

// ─── Format Helpers ───
function fmtMoney(v) { return '$' + parseFloat(v || 0).toFixed(2); }
function fmtNum(v) { return parseInt(v || 0).toLocaleString(); }
function fmtDate(d) { return d ? new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'; }
function starHtml(rating) {
    let html = '';
    for (let i = 1; i <= 5; i++) {
        if (rating >= i) html += '<i class="fas fa-star"></i>';
        else if (rating >= i - 0.5) html += '<i class="fas fa-star-half-alt"></i>';
        else html += '<i class="far fa-star"></i>';
    }
    return html;
}
function typeBadge(type) { return '<span class="cr-badge cr-badge-' + type + '">' + type + '</span>'; }
function statusBadge(status) { return '<span class="cr-badge cr-badge-' + status + '">' + status + '</span>'; }

// ─── DASHBOARD ───
async function loadDashboard() {
    const data = await apiFetch('dashboard');
    if (!data.success) return;

    document.getElementById('statProducts').textContent = fmtNum(data.stats.total_products);
    document.getElementById('statDownloads').textContent = fmtNum(data.stats.total_downloads);
    document.getElementById('statRevenue').textContent = fmtMoney(data.stats.total_revenue);
    document.getElementById('statRating').textContent = data.stats.avg_rating || '—';

    // Recent Downloads Table
    const tbody = document.getElementById('recentDownloadsBody');
    if (data.recent_downloads.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--cr-text-muted);padding:30px;">No sales yet. List your first product!</td></tr>';
    } else {
        tbody.innerHTML = data.recent_downloads.map(d =>
            '<tr><td>' + d.title + '</td><td>' + typeBadge(d.item_type) + '</td><td>' + fmtMoney(d.price) + '</td><td style="color:var(--cr-green);">' + fmtMoney(d.seller_earnings) + '</td><td>' + fmtDate(d.created_at) + '</td></tr>'
        ).join('');
    }

    // Recent Reviews
    const rl = document.getElementById('recentReviewsList');
    if (data.recent_reviews.length === 0) {
        rl.innerHTML = '<div class="cr-empty"><i class="fas fa-comments"></i><h3>No reviews yet</h3><p>Reviews will appear here once buyers rate your products.</p></div>';
    } else {
        rl.innerHTML = data.recent_reviews.map(r =>
            '<div class="cr-review-card"><div class="cr-review-header"><div class="cr-review-stars">' + starHtml(r.rating) + ' <span style="color:var(--cr-text-muted);font-size:0.85rem;">(' + r.rating + '/5)</span></div><div class="cr-review-product"><i class="fas fa-box"></i> ' + r.product_title + '</div></div>' +
            (r.title ? '<div style="font-weight:600;color:#fff;margin-bottom:6px;">' + r.title + '</div>' : '') +
            '<div class="cr-review-body">' + (r.review || '') + '</div><div class="cr-review-date">' + fmtDate(r.created_at) + '</div>' +
            (r.seller_response ? '<div class="cr-review-response"><strong>Your response:</strong> ' + r.seller_response + '</div>' : '') +
            '</div>'
        ).join('');
    }

    // Revenue Chart
    renderRevenueChart(data.revenue_chart || []);
}

function renderRevenueChart(chartData) {
    const ctx = document.getElementById('revenueChart');
    if (revenueChartInstance) revenueChartInstance.destroy();

    // Fill 30 days
    const days = [];
    const earnings = [];
    const sales = [];
    const dataMap = {};
    chartData.forEach(d => { dataMap[d.day] = d; });

    for (let i = 29; i >= 0; i--) {
        const dt = new Date();
        dt.setDate(dt.getDate() - i);
        const key = dt.toISOString().slice(0,10);
        days.push(dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
        earnings.push(parseFloat(dataMap[key]?.earnings || 0));
        sales.push(parseInt(dataMap[key]?.sales || 0));
    }

    revenueChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: days,
            datasets: [{
                label: 'Earnings ($)',
                data: earnings,
                borderColor: '#00b894',
                backgroundColor: 'rgba(0,184,148,0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 2
            },{
                label: 'Sales',
                data: sales,
                borderColor: '#6c5ce7',
                backgroundColor: 'rgba(108,92,231,0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 2,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: { legend: { labels: { color: '#8a8a9a' } } },
            scales: {
                x: { ticks: { color: '#8a8a9a', maxTicksLimit: 10 }, grid: { color: 'rgba(255,255,255,0.04)' } },
                y: { ticks: { color: '#8a8a9a', callback: v => '$' + v }, grid: { color: 'rgba(255,255,255,0.04)' }, beginAtZero: true },
                y1: { position: 'right', ticks: { color: '#8a8a9a' }, grid: { display: false }, beginAtZero: true }
            }
        }
    });
}

// ─── MY PRODUCTS ───
async function loadProducts() {
    const status = document.getElementById('filterStatus').value;
    const type = document.getElementById('filterType').value;
    let params = '&page=' + currentProductsPage;
    if (status) params += '&status=' + status;
    if (type) params += '&type=' + type;

    const data = await apiFetch('products', { params });
    const grid = document.getElementById('productsGrid');

    if (!data.success || data.products.length === 0) {
        grid.innerHTML = '<div class="cr-empty" style="grid-column:1/-1;"><i class="fas fa-box-open"></i><h3>No products found</h3><p>Create your first product to start earning!</p><button class="cr-btn cr-btn-primary cr-btn-lg" style="margin-top:16px;" onclick="crNav(\'add-product\',document.querySelector(\'[data-section=add-product]\'))"><i class="fas fa-plus"></i> Add New Product</button></div>';
        document.getElementById('productsPagination').innerHTML = '';
        return;
    }

    grid.innerHTML = data.products.map(p => {
        const icon = {agent:'fa-robot',tool:'fa-wrench',fleet:'fa-users-cog',template:'fa-file-alt',integration:'fa-plug'}[p.item_type] || 'fa-box';
        return '<div class="cr-product-card">' +
            '<div class="cr-product-top">' +
                '<div class="cr-product-icon">' + (p.icon_url ? '<img src="' + p.icon_url + '" alt="' + (p.title || 'Product icon').replace(/"/g, '&quot;') + '" style="width:100%;height:100%;object-fit:cover;border-radius:12px;">' : '<i class="fas ' + icon + '"></i>') + '</div>' +
                '<div class="cr-product-info"><h4>' + p.title + '</h4><div class="cr-product-badges">' + typeBadge(p.item_type) + ' ' + statusBadge(p.status) + '</div></div>' +
            '</div>' +
            '<div class="cr-product-meta">' +
                '<span><i class="fas fa-dollar-sign"></i>' + (parseFloat(p.price) > 0 ? fmtMoney(p.price) : 'Free') + '</span>' +
                '<span><i class="fas fa-download"></i>' + fmtNum(p.downloads) + '</span>' +
                '<span><i class="fas fa-star" style="color:var(--cr-orange);"></i> ' + (parseFloat(p.rating) || '—') + '</span>' +
            '</div>' +
            '<div class="cr-product-actions">' +
                '<button class="cr-btn cr-btn-outline" onclick="editProduct(' + p.id + ')"><i class="fas fa-edit"></i> Edit</button>' +
                (p.status === 'draft' ? '<button class="cr-btn cr-btn-success" onclick="submitForReview(' + p.id + ')"><i class="fas fa-paper-plane"></i> Submit</button>' : '') +
                (p.status === 'published' ? '<button class="cr-btn cr-btn-outline" onclick="unpublishProduct(' + p.id + ')"><i class="fas fa-eye-slash"></i> Unpublish</button>' : '') +
                '<button class="cr-btn cr-btn-danger" onclick="deleteProduct(' + p.id + ',\'' + p.title.replace(/'/g, "\\'") + '\')"><i class="fas fa-trash"></i></button>' +
            '</div>' +
        '</div>';
    }).join('');

    // Pagination
    const pag = document.getElementById('productsPagination');
    if (data.pages > 1) {
        let html = '';
        for (let i = 1; i <= data.pages; i++) {
            html += '<button class="cr-btn ' + (i === data.page ? 'cr-btn-primary' : 'cr-btn-outline') + '" style="margin:0 4px;" onclick="currentProductsPage=' + i + ';loadProducts()">' + i + '</button>';
        }
        pag.innerHTML = html;
    } else {
        pag.innerHTML = '';
    }
}

function setView(mode, btn) {
    document.querySelectorAll('.cr-view-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const grid = document.getElementById('productsGrid');
    if (mode === 'list') grid.classList.add('list-view');
    else grid.classList.remove('list-view');
}

async function submitForReview(id) {
    const data = await apiFetch('product/submit', { body: { id } });
    if (data.success) { showToast('Product submitted for review!'); loadProducts(); }
    else showToast(data.error || 'Failed to submit', 'error');
}

async function unpublishProduct(id) {
    if (!confirm('Unpublish this product?')) return;
    const data = await apiFetch('product/update', { body: { id, status: 'draft' } });
    if (data.success) { showToast('Product unpublished'); loadProducts(); }
    else showToast(data.error || 'Failed', 'error');
}

async function deleteProduct(id, title) {
    if (!confirm('Delete "' + title + '"? This will remove it from the marketplace.')) return;
    const data = await apiFetch('product/delete', { body: { id } });
    if (data.success) { showToast('Product removed'); loadProducts(); }
    else showToast(data.error || 'Failed', 'error');
}

async function editProduct(id) {
    const data = await apiFetch('product', { params: '&id=' + id });
    if (!data.success) { showToast('Could not load product', 'error'); return; }

    crNav('add-product', document.querySelector('[data-section="add-product"]'));
    const p = data.product;

    // Populate form
    document.getElementById('productTitle').value = p.title;
    document.getElementById('productDesc').value = p.description;
    document.getElementById('productCategory').value = p.category;
    document.getElementById('productTags').value = (p.tags || []).join(', ');

    // Type
    document.querySelectorAll('.cr-type-option').forEach(o => {
        o.classList.toggle('selected', o.dataset.type === p.item_type);
    });
    document.getElementById('productType').value = p.item_type;

    // Price
    if (parseFloat(p.price) > 0) {
        setPricing('paid', document.querySelectorAll('.cr-price-option')[1]);
        document.getElementById('productPrice').value = p.price;
    } else {
        setPricing('free', document.querySelectorAll('.cr-price-option')[0]);
    }

    // Store edit ID
    document.getElementById('addProductForm').dataset.editId = p.id;
    document.querySelector('.cr-section-title i.fa-plus-circle')?.closest('.cr-section-title')?.querySelector('i')?.classList?.replace('fa-plus-circle', 'fa-edit');
}

// ─── ADD PRODUCT ───
function selectType(el) {
    document.querySelectorAll('.cr-type-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('productType').value = el.dataset.type;
}

function setPricing(mode, el) {
    document.querySelectorAll('.cr-price-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('priceInputWrap').style.display = mode === 'paid' ? 'block' : 'none';
    if (mode === 'free') document.getElementById('productPrice').value = '';
}

function toggleMdPreview(mode, btn) {
    document.querySelectorAll('.cr-md-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    const textarea = document.getElementById('productDesc');
    const preview = document.getElementById('mdPreview');
    if (mode === 'preview') {
        textarea.style.display = 'none';
        preview.classList.add('active');
        preview.innerHTML = simpleMarkdown(textarea.value);
    } else {
        textarea.style.display = '';
        preview.classList.remove('active');
    }
}

function simpleMarkdown(text) {
    if (!text) return '<em style="color:var(--cr-text-muted);">Nothing to preview</em>';
    return text
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/^### (.+)$/gm, '<h3>$1</h3>')
        .replace(/^## (.+)$/gm, '<h2>$1</h2>')
        .replace(/^# (.+)$/gm, '<h1>$1</h1>')
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.+?)\*/g, '<em>$1</em>')
        .replace(/`(.+?)`/g, '<code style="background:rgba(108,92,231,0.15);padding:2px 6px;border-radius:4px;">$1</code>')
        .replace(/\n/g, '<br>');
}

function previewIcon(input) {
    if (!input.files[0]) return;
    const reader = new FileReader();
    reader.onload = (e) => {
        iconData = e.target.result;
        document.getElementById('iconUpload').innerHTML = '<img src="' + iconData + '" alt="Product icon preview">';
    };
    reader.readAsDataURL(input.files[0]);
}

function addScreenshot(idx) {
    const input = document.getElementById('ssFile');
    input.onchange = () => {
        if (!input.files[0]) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            screenshotData[idx] = e.target.result;
            const slots = document.querySelectorAll('.cr-screenshot-slot');
            slots[idx].innerHTML = '<img src="' + e.target.result + '" alt="Screenshot ' + (idx + 1) + ' preview"><button class="remove-ss" onclick="event.stopPropagation();removeScreenshot(' + idx + ')"><i class="fas fa-times"></i></button>';
        };
        reader.readAsDataURL(input.files[0]);
    };
    input.click();
}

function removeScreenshot(idx) {
    screenshotData[idx] = null;
    const slots = document.querySelectorAll('.cr-screenshot-slot');
    slots[idx].innerHTML = '<span class="placeholder"><i class="fas fa-plus"></i><br>Add</span>';
}

async function submitProduct(e) {
    e.preventDefault();
    const form = document.getElementById('addProductForm');
    const editId = form.dataset.editId;
    const submitAction = e.submitter?.value || 'draft';

    const type = document.getElementById('productType').value;
    if (!type) { showToast('Please select an item type', 'error'); return false; }

    const title = document.getElementById('productTitle').value.trim();
    const desc = document.getElementById('productDesc').value.trim();
    const category = document.getElementById('productCategory').value;
    const tagsRaw = document.getElementById('productTags').value;
    const priceVal = document.getElementById('productPrice').value;
    const configJson = document.getElementById('productConfig').value.trim();
    const tags = tagsRaw ? tagsRaw.split(',').map(t => t.trim()).filter(Boolean).slice(0, 5) : [];
    const price = priceVal ? parseFloat(priceVal) : 0;
    const screenshots = screenshotData.filter(Boolean);

    // Validate config JSON if provided
    if (configJson) {
        try { JSON.parse(configJson); } catch(err) {
            showToast('Configuration JSON is invalid', 'error');
            return false;
        }
    }

    const body = { item_type: type, title, description: desc, category, tags, price, icon_url: iconData || '', screenshots, config_json: configJson };

    let data;
    if (editId) {
        body.id = parseInt(editId);
        data = await apiFetch('product/update', { body });
    } else {
        data = await apiFetch('product/create', { body });
    }

    if (!data.success) {
        showToast((data.errors || [data.error]).join(', '), 'error');
        return false;
    }

    const productId = data.product_id || editId;

    // If submitting for review
    if (submitAction === 'review' && !editId) {
        await apiFetch('product/submit', { body: { id: parseInt(productId) } });
        showToast('Product submitted for review!');
    } else {
        showToast(editId ? 'Product updated!' : 'Product saved as draft!');
    }

    // Reset form
    form.reset();
    form.dataset.editId = '';
    document.querySelectorAll('.cr-type-option').forEach(o => o.classList.remove('selected'));
    iconData = null;
    screenshotData = [null,null,null,null,null];
    document.getElementById('iconUpload').innerHTML = '<i class="fas fa-image"></i>';
    document.querySelectorAll('.cr-screenshot-slot').forEach(s => { s.innerHTML = '<span class="placeholder"><i class="fas fa-plus"></i><br>Add</span>'; });
    setPricing('free', document.querySelectorAll('.cr-price-option')[0]);

    // Switch to products
    crNav('products', document.querySelector('[data-section="products"]'));
    return false;
}

// ─── EARNINGS ───
async function loadEarnings() {
    const data = await apiFetch('earnings');
    if (!data.success) return;

    const lt = data.lifetime;
    document.getElementById('earnLifetime').textContent = fmtMoney(lt.total_earnings);
    document.getElementById('earnBalance').textContent = fmtMoney(lt.current_balance);
    document.getElementById('earnPaidOut').textContent = fmtMoney(lt.paid_out);
    document.getElementById('earnSales').textContent = fmtNum(lt.total_sales);

    const btn = document.getElementById('payoutBtn');
    if (parseFloat(lt.current_balance) >= 25) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-wallet"></i> Request Payout (' + fmtMoney(lt.current_balance) + ')';
    } else {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-wallet"></i> Min $25 Required';
    }

    // By product
    const tbody = document.getElementById('earningsByProduct');
    if (data.by_product.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--cr-text-muted);padding:30px;">No earnings data yet</td></tr>';
    } else {
        tbody.innerHTML = data.by_product.map(p =>
            '<tr><td>' + p.title + '</td><td>' + typeBadge(p.item_type) + '</td><td>' + fmtNum(p.sales) + '</td><td>' + fmtMoney(p.gross_revenue) + '</td><td style="color:var(--cr-fire);">' + fmtMoney(p.platform_commission) + '</td><td style="color:var(--cr-green);font-weight:600;">' + fmtMoney(p.net_earnings) + '</td></tr>'
        ).join('');
    }

    // Payout history
    const ph = document.getElementById('payoutHistory');
    if (data.payout_history.length === 0) {
        ph.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--cr-text-muted);padding:30px;">No payouts yet</td></tr>';
    } else {
        ph.innerHTML = data.payout_history.map(p =>
            '<tr><td>#' + p.id + '</td><td style="font-weight:600;">' + fmtMoney(p.amount) + '</td><td>' + (p.method || 'stripe') + '</td><td>' + statusBadge(p.status) + '</td><td>' + fmtDate(p.requested_at) + '</td><td>' + fmtDate(p.processed_at) + '</td></tr>'
        ).join('');
    }
}

async function requestPayout() {
    if (!confirm('Request a payout of your available balance?')) return;
    const data = await apiFetch('payout/request', { method: 'POST', body: {} });
    if (data.success) { showToast(data.message); loadEarnings(); }
    else showToast(data.error || 'Payout request failed', 'error');
}

// ─── REVIEWS ───
async function loadReviews() {
    const rating = document.getElementById('reviewRatingFilter').value;
    let params = '&page=' + currentReviewsPage;
    if (rating) params += '&rating=' + rating;

    const data = await apiFetch('reviews', { params });
    const container = document.getElementById('reviewsList');

    if (!data.success || data.reviews.length === 0) {
        container.innerHTML = '<div class="cr-empty"><i class="fas fa-star"></i><h3>No reviews found</h3><p>Reviews from buyers will appear here.</p></div>';
        document.getElementById('reviewsPagination').innerHTML = '';
        return;
    }

    container.innerHTML = data.reviews.map(r =>
        '<div class="cr-review-card">' +
            '<div class="cr-review-header">' +
                '<div><span class="cr-review-stars">' + starHtml(r.rating) + '</span> <span style="color:var(--cr-text-muted);font-size:0.85rem;">(' + r.rating + '/5)</span></div>' +
                '<div class="cr-review-product"><i class="fas fa-box"></i> ' + r.product_title + '</div>' +
            '</div>' +
            (r.title ? '<div style="font-weight:600;color:#fff;margin-bottom:6px;">' + r.title + '</div>' : '') +
            '<div class="cr-review-body">' + (r.review || '') + '</div>' +
            '<div class="cr-review-date">' + fmtDate(r.created_at) + (r.is_verified_purchase ? ' <span class="cr-badge cr-badge-published"><i class="fas fa-check"></i> Verified</span>' : '') + '</div>' +
            (r.seller_response ?
                '<div class="cr-review-response"><strong>Your response:</strong> ' + r.seller_response + '</div>' :
                '<div class="cr-review-respond-form" id="respond-' + r.id + '">' +
                    '<textarea class="cr-textarea" style="min-height:80px;" id="resp-text-' + r.id + '" placeholder="Write your response..."></textarea>' +
                    '<button class="cr-btn cr-btn-primary" style="margin-top:8px;" onclick="respondToReview(' + r.id + ')"><i class="fas fa-reply"></i> Respond</button>' +
                '</div>' +
                '<button class="cr-btn cr-btn-outline" style="margin-top:8px;" onclick="document.getElementById(\'respond-' + r.id + '\').classList.toggle(\'show\')"><i class="fas fa-reply"></i> Reply</button>'
            ) +
        '</div>'
    ).join('');

    // Pagination
    const pag = document.getElementById('reviewsPagination');
    if (data.pages > 1) {
        let html = '';
        for (let i = 1; i <= data.pages; i++) {
            html += '<button class="cr-btn ' + (i === data.page ? 'cr-btn-primary' : 'cr-btn-outline') + '" style="margin:0 4px;" onclick="currentReviewsPage=' + i + ';loadReviews()">' + i + '</button>';
        }
        pag.innerHTML = html;
    } else {
        pag.innerHTML = '';
    }
}

async function respondToReview(id) {
    const text = document.getElementById('resp-text-' + id).value.trim();
    if (text.length < 5) { showToast('Response must be at least 5 characters', 'error'); return; }
    const data = await apiFetch('reviews/respond', { body: { review_id: id, response: text } });
    if (data.success) { showToast('Response posted!'); loadReviews(); }
    else showToast(data.error || 'Failed to respond', 'error');
}

// ─── ANALYTICS ───
async function loadAnalyticsProductList() {
    const data = await apiFetch('products', { params: '&limit=50' });
    const select = document.getElementById('analyticsProductSelect');
    select.innerHTML = '<option value="">All Products (Overview)</option>';
    if (data.success && data.products) {
        data.products.forEach(p => {
            select.innerHTML += '<option value="' + p.id + '">' + p.title + '</option>';
        });
    }
}

async function loadAnalytics(productId) {
    let params = '';
    if (productId) params = '&product_id=' + productId;
    const data = await apiFetch('analytics', { params });
    if (!data.success) return;

    renderAnalyticsChart(data.daily || []);

    const topDiv = document.getElementById('analyticsTopProducts');
    const ratingDiv = document.getElementById('analyticsRatingBreakdown');

    if (productId && data.rating_breakdown) {
        topDiv.style.display = 'none';
        ratingDiv.style.display = 'block';
        const rbc = document.getElementById('ratingBreakdownContent');
        if (data.rating_breakdown.length === 0) {
            rbc.innerHTML = '<p style="color:var(--cr-text-muted);">No ratings yet.</p>';
        } else {
            rbc.innerHTML = data.rating_breakdown.map(r => {
                const pct = data.totals && data.totals.total_sales > 0 ? Math.round(r.count / data.totals.total_sales * 100) : 0;
                return '<div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">' +
                    '<span style="width:60px;color:var(--cr-orange);">' + starHtml(r.rating) + '</span>' +
                    '<div style="flex:1;height:8px;background:var(--cr-surface-2);border-radius:4px;overflow:hidden;">' +
                        '<div style="width:' + pct + '%;height:100%;background:var(--cr-orange);border-radius:4px;"></div>' +
                    '</div>' +
                    '<span style="color:var(--cr-text-muted);font-size:0.85rem;width:50px;">' + r.count + '</span>' +
                '</div>';
            }).join('');
        }
    } else if (data.top_products) {
        topDiv.style.display = 'block';
        ratingDiv.style.display = 'none';
        const tbody = document.getElementById('topProductsBody');
        if (data.top_products.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:var(--cr-text-muted);padding:30px;">No data yet</td></tr>';
        } else {
            tbody.innerHTML = data.top_products.map(p =>
                '<tr><td>' + p.title + '</td><td>' + typeBadge(p.item_type) + '</td><td>' + fmtNum(p.sales) + '</td><td style="color:var(--cr-green);">' + fmtMoney(p.earnings) + '</td></tr>'
            ).join('');
        }
    }
}

function renderAnalyticsChart(chartData) {
    const ctx = document.getElementById('analyticsChart');
    if (analyticsChartInstance) analyticsChartInstance.destroy();

    const days = [];
    const salesArr = [];
    const earningsArr = [];
    const dataMap = {};
    chartData.forEach(d => { dataMap[d.day] = d; });

    for (let i = 29; i >= 0; i--) {
        const dt = new Date();
        dt.setDate(dt.getDate() - i);
        const key = dt.toISOString().slice(0,10);
        days.push(dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
        salesArr.push(parseInt(dataMap[key]?.downloads || dataMap[key]?.sales || 0));
        earningsArr.push(parseFloat(dataMap[key]?.earnings || 0));
    }

    analyticsChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: days,
            datasets: [{
                label: 'Sales',
                data: salesArr,
                backgroundColor: 'rgba(108,92,231,0.6)',
                borderRadius: 4,
                yAxisID: 'y'
            },{
                label: 'Earnings ($)',
                data: earningsArr,
                type: 'line',
                borderColor: '#00b894',
                backgroundColor: 'rgba(0,184,148,0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 2,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: { legend: { labels: { color: '#8a8a9a' } } },
            scales: {
                x: { ticks: { color: '#8a8a9a', maxTicksLimit: 10 }, grid: { color: 'rgba(255,255,255,0.04)' } },
                y: { ticks: { color: '#8a8a9a' }, grid: { color: 'rgba(255,255,255,0.04)' }, beginAtZero: true },
                y1: { position: 'right', ticks: { color: '#8a8a9a', callback: v => '$' + v }, grid: { display: false }, beginAtZero: true }
            }
        }
    });
}

// ─── Initial Load ───
document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
});
