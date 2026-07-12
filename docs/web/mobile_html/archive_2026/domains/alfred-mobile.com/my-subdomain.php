<?php
$pageTitle = "Claim Your Subdomain — GoSiteMe";
$pageDescription = "Claim your personal subdomain on gositeme.com";
include 'includes/site-header.inc.php';

if (!isset($_SESSION['client_id']) && !isset($_SESSION['uid'])) {
    header('Location: /login.php?redirect=/my-subdomain.php');
    exit;
}
$clientId = (int) ($_SESSION['client_id'] ?? $_SESSION['uid']);
$isOwner = ($clientId === 33);
?>
<style>
.sd-page { max-width: 760px; margin: 2rem auto; padding: 0 1.5rem; }
.sd-hero { text-align: center; margin-bottom: 2.5rem; }
.sd-hero h1 { font-size: 2.2rem; background: linear-gradient(135deg, #f472b6, #a855f7); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: .5rem; }
.sd-hero p { color: rgba(255,255,255,.5); font-size: .95rem; }

.sd-claim-box { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 16px; padding: 2rem; margin-bottom: 2rem; }
.sd-claim-box h2 { font-size: 1.1rem; margin-bottom: 1rem; color: #f472b6; }

.sd-input-row { display: flex; gap: .75rem; align-items: center; margin-bottom: 1rem; }
.sd-input-row input { flex: 1; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1); border-radius: 10px; padding: .75rem 1rem; color: #fff; font-size: 1rem; outline: none; transition: border-color .2s; }
.sd-input-row input:focus { border-color: #a855f7; }
.sd-input-row .sd-suffix { color: rgba(255,255,255,.35); font-size: .9rem; flex-shrink: 0; font-family: monospace; }
.sd-input-row button { background: linear-gradient(135deg, #a855f7, #f472b6); border: none; color: #fff; padding: .75rem 1.5rem; border-radius: 10px; font-weight: 600; cursor: pointer; transition: opacity .2s; white-space: nowrap; }
.sd-input-row button:hover { opacity: .85; }
.sd-input-row button:disabled { opacity: .4; cursor: not-allowed; }

.sd-status { padding: .6rem 1rem; border-radius: 8px; font-size: .85rem; margin-bottom: 1rem; display: none; }
.sd-status.available { background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.2); color: #10b981; display: block; }
.sd-status.taken { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.2); color: #ef4444; display: block; }
.sd-status.error { background: rgba(251,191,36,.1); border: 1px solid rgba(251,191,36,.2); color: #fbbf24; display: block; }
.sd-status.info { background: rgba(96,165,250,.1); border: 1px solid rgba(96,165,250,.2); color: #60a5fa; display: block; }

.sd-preview { text-align: center; margin: 1.5rem 0; padding: 1rem; background: rgba(168,85,247,.05); border: 1px dashed rgba(168,85,247,.2); border-radius: 12px; }
.sd-preview-url { font-family: monospace; font-size: 1.3rem; color: #a855f7; word-break: break-all; }

/* My subdomains */
.sd-section { margin-bottom: 2rem; }
.sd-section h2 { font-size: 1.1rem; margin-bottom: 1rem; display: flex; align-items: center; gap: .5rem; }
.sd-list { list-style: none; padding: 0; }
.sd-list li { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 10px; padding: .8rem 1.2rem; margin-bottom: .5rem; display: flex; align-items: center; justify-content: space-between; transition: border-color .2s; }
.sd-list li:hover { border-color: rgba(168,85,247,.2); }
.sd-list .sd-name { font-weight: 600; color: #f472b6; font-family: monospace; }
.sd-list .sd-meta { font-size: .75rem; color: rgba(255,255,255,.35); }
.sd-list .sd-actions { display: flex; gap: .5rem; }
.sd-btn-sm { padding: .35rem .75rem; border-radius: 6px; border: none; font-size: .75rem; cursor: pointer; transition: .2s; }
.sd-btn-visit { background: rgba(16,185,129,.15); color: #10b981; }
.sd-btn-visit:hover { background: rgba(16,185,129,.25); }
.sd-btn-release { background: rgba(239,68,68,.1); color: #ef4444; }
.sd-btn-release:hover { background: rgba(239,68,68,.2); }

.sd-empty { text-align: center; padding: 2rem; color: rgba(255,255,255,.3); font-size: .9rem; }

/* Rules */
.sd-rules { background: rgba(255,255,255,.02); border-radius: 10px; padding: 1.2rem 1.5rem; margin-top: 1rem; }
.sd-rules h3 { font-size: .85rem; color: rgba(255,255,255,.5); margin-bottom: .5rem; }
.sd-rules ul { list-style: disc; padding-left: 1.5rem; }
.sd-rules li { font-size: .8rem; color: rgba(255,255,255,.35); margin-bottom: .25rem; }

@media (max-width: 600px) {
    .sd-input-row { flex-direction: column; }
    .sd-input-row .sd-suffix { text-align: center; }
    .sd-preview-url { font-size: 1rem; }
}
</style>

<div class="sd-page">
    <div class="sd-hero">
        <h1><i class="fa-solid fa-globe"></i> Your Subdomain</h1>
        <p>Claim your personal address on GoSiteMe — <strong>yourname.gositeme.com</strong></p>
    </div>

    <!-- Claim Box -->
    <div class="sd-claim-box">
        <h2><i class="fa-solid fa-wand-magic-sparkles"></i> Claim a Subdomain</h2>
        <div class="sd-input-row">
            <input type="text" id="sdNameInput" placeholder="yourname" maxlength="63" autocomplete="off" spellcheck="false">
            <span class="sd-suffix">.gositeme.com</span>
            <button id="sdCheckBtn" onclick="checkAvailability()"><i class="fa-solid fa-magnifying-glass"></i> Check</button>
        </div>
        <div id="sdStatus" class="sd-status"></div>
        <div id="sdPreview" class="sd-preview" style="display:none">
            <div>Your address will be:</div>
            <div class="sd-preview-url" id="sdPreviewUrl"></div>
        </div>
        <div style="text-align:center;margin-top:1rem;">
            <button id="sdClaimBtn" onclick="claimSubdomain()" style="display:none;background:linear-gradient(135deg,#10b981,#059669);border:none;color:#fff;padding:.8rem 2rem;border-radius:10px;font-weight:700;font-size:1rem;cursor:pointer;">
                <i class="fa-solid fa-check-circle"></i> Claim It!
            </button>
        </div>
    </div>

    <!-- My Subdomains -->
    <div class="sd-section">
        <h2><i class="fa-solid fa-server"></i> My Subdomains</h2>
        <ul class="sd-list" id="sdMyList">
            <li class="sd-empty">Loading...</li>
        </ul>
    </div>

    <!-- Rules -->
    <div class="sd-rules">
        <h3>Subdomain Rules</h3>
        <ul>
            <li>2–63 characters, letters, numbers, and hyphens only</li>
            <li>Cannot start or end with a hyphen</li>
            <li>Maximum <?php echo $isOwner ? 'unlimited' : '3'; ?> subdomains per account</li>
            <li>Some names are reserved (www, mail, api, admin, etc.)</li>
            <li>Subdomains point to your GoSiteMe hosting space</li>
        </ul>
    </div>
</div>

<script>
(function() {
    const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>';
    const API = '/api/subdomains.php';
    let lastCheckedName = '';
    let isAvailable = false;

    const nameInput  = document.getElementById('sdNameInput');
    const statusDiv  = document.getElementById('sdStatus');
    const previewDiv = document.getElementById('sdPreview');
    const previewUrl = document.getElementById('sdPreviewUrl');
    const claimBtn   = document.getElementById('sdClaimBtn');

    // Live preview
    nameInput.addEventListener('input', () => {
        const val = nameInput.value.toLowerCase().replace(/[^a-z0-9\-]/g, '');
        nameInput.value = val;
        if (val.length >= 2) {
            previewDiv.style.display = '';
            previewUrl.textContent = 'https://' + val + '.gositeme.com';
        } else {
            previewDiv.style.display = 'none';
        }
        claimBtn.style.display = 'none';
        statusDiv.className = 'sd-status';
        isAvailable = false;
    });

    // Enter key triggers check
    nameInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') checkAvailability();
    });

    window.checkAvailability = async function() {
        const name = nameInput.value.trim().toLowerCase();
        if (!name || name.length < 2) {
            showStatus('error', 'Enter at least 2 characters');
            return;
        }

        statusDiv.className = 'sd-status info';
        statusDiv.style.display = 'block';
        statusDiv.textContent = 'Checking...';

        try {
            const resp = await fetch(API + '?action=check&name=' + encodeURIComponent(name));
            const data = await resp.json();

            if (data.available) {
                showStatus('available', '✓ ' + data.full + ' is available!');
                claimBtn.style.display = '';
                lastCheckedName = name;
                isAvailable = true;
            } else {
                showStatus('taken', '✗ ' + (data.reason || 'Not available'));
                claimBtn.style.display = 'none';
                isAvailable = false;
            }
        } catch (err) {
            showStatus('error', 'Failed to check: ' + err.message);
        }
    };

    window.claimSubdomain = async function() {
        if (!isAvailable || !lastCheckedName) return;
        claimBtn.disabled = true;
        claimBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Claiming...';

        try {
            const resp = await fetch(API + '?action=claim', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify({ name: lastCheckedName }),
            });
            const data = await resp.json();

            if (data.ok) {
                showStatus('available', '🎉 Claimed! Your subdomain: ' + data.url);
                claimBtn.style.display = 'none';
                nameInput.value = '';
                previewDiv.style.display = 'none';
                isAvailable = false;
                loadMySubdomains();
            } else {
                showStatus('error', data.error || 'Claim failed');
            }
        } catch (err) {
            showStatus('error', 'Failed: ' + err.message);
        } finally {
            claimBtn.disabled = false;
            claimBtn.innerHTML = '<i class="fa-solid fa-check-circle"></i> Claim It!';
        }
    };

    window.releaseSubdomain = async function(name) {
        if (!confirm('Release ' + name + '.gositeme.com? This cannot be undone.')) return;

        try {
            const resp = await fetch(API + '?action=release', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify({ name }),
            });
            const data = await resp.json();

            if (data.ok) {
                loadMySubdomains();
            } else {
                alert(data.error || 'Release failed');
            }
        } catch (err) {
            alert('Failed: ' + err.message);
        }
    };

    async function loadMySubdomains() {
        const list = document.getElementById('sdMyList');
        try {
            const resp = await fetch(API + '?action=mine');
            const data = await resp.json();

            if (!data.subdomains || data.subdomains.length === 0) {
                list.innerHTML = '<li class="sd-empty">No subdomains yet — claim one above!</li>';
                return;
            }

            list.innerHTML = data.subdomains.map(s => `
                <li>
                    <div>
                        <div class="sd-name">${escapeHtml(s.full)}</div>
                        <div class="sd-meta">Created ${new Date(s.created).toLocaleDateString()} • ${s.status}</div>
                    </div>
                    <div class="sd-actions">
                        <a href="${escapeHtml(s.url)}" target="_blank" class="sd-btn-sm sd-btn-visit"><i class="fa-solid fa-external-link"></i> Visit</a>
                        ${s.status === 'active' ? `<button class="sd-btn-sm sd-btn-release" onclick="releaseSubdomain('${escapeHtml(s.name)}')"><i class="fa-solid fa-trash"></i></button>` : ''}
                    </div>
                </li>
            `).join('');
        } catch (err) {
            list.innerHTML = '<li class="sd-empty">Failed to load subdomains</li>';
        }
    }

    function showStatus(type, msg) {
        statusDiv.className = 'sd-status ' + type;
        statusDiv.textContent = msg;
        statusDiv.style.display = 'block';
    }

    function escapeHtml(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    // Initial load
    loadMySubdomains();
})();
</script>

<?php include 'includes/site-footer.inc.php'; ?>
