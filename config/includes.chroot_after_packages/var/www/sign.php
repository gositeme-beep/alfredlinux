<?php
/**
 * DIGITAL DOCUMENT SIGNING — GoSiteMe
 * ═══════════════════════════════════════════════════════════
 * Upload PDF → Draw signature on phone → SHA-3 verification → Notify lawyer
 *
 * Routes:
 *   /sign              — Upload + sign interface
 *   /sign/verify/TOKEN — Public verification page
 */
$page_title = 'Sign Documents — GoSiteMe Digital Signing';
$page_description = 'Sign legal documents digitally with cryptographic SHA-3 verification. Upload a PDF, draw your signature specimen, and notify your lawyer instantly.';
$page_canonical = 'https://root.com/sign';

// Check for verification route
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$verifyMatch = [];
if (preg_match('#/sign/verify/([a-f0-9]{16,64})#i', $requestUri, $verifyMatch)) {
    $verifyToken = $verifyMatch[1];
}

require_once __DIR__ . '/includes/site-header.inc.php';
require_once __DIR__ . '/includes/db-config.inc.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$db = getSharedDB();
$userId = $_SESSION['ide_user_id'] ?? null;
$clientId = $_SESSION['client_id'] ?? $_SESSION['ide_client_id'] ?? null;
$isCommander = $clientId && (int)$clientId === 33;
$isLoggedIn = $userId || $isCommander;

// If verification page
if (isset($verifyToken)) {
    $stmt = $db->prepare("SELECT id, doc_hash_sha3, signed_hash_sha3, signer_name, original_filename, status, signed_at, created_at FROM signed_documents WHERE verification_token = ?");
    $stmt->execute([$verifyToken]);
    $verifyDoc = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<style>
:root {
    --sign-bg: #0a0a0f;
    --sign-surface: rgba(255,255,255,.03);
    --sign-border: rgba(255,215,0,.1);
    --sign-gold: #ffd700;
    --sign-gold2: #f59e0b;
    --sign-red: #dc2626;
    --sign-green: #22c55e;
    --sign-blue: #3b82f6;
    --sign-white: #f0f0f5;
    --sign-muted: rgba(240,240,245,.5);
    --sign-dim: rgba(240,240,245,.3);
}
.sign-page { max-width: 800px; margin: 0 auto; padding: 0 1.2rem 4rem; color: var(--sign-white); }
.sign-hero { text-align: center; padding: 50px 0 30px; }
.sign-hero h1 { font-size: clamp(1.6rem, 4vw, 2.4rem); font-weight: 800; margin-bottom: .5rem; }
.sign-hero h1 .gold { color: var(--sign-gold); }
.sign-hero .sub { color: var(--sign-muted); font-size: .9rem; }
.sign-badge { display:inline-flex; align-items:center; gap:8px; padding:5px 16px; border-radius:999px; background:rgba(255,215,0,.08); border:1px solid rgba(255,215,0,.25); color:var(--sign-gold); font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.15em; margin-bottom:1.2rem; }

/* Cards */
.sign-card { background: var(--sign-surface); border: 1px solid var(--sign-border); border-radius: 16px; padding: 1.8rem; margin-bottom: 1.5rem; }
.sign-card h2 { color: var(--sign-gold); font-size: 1.1rem; margin: 0 0 .3rem; }
.sign-card .desc { color: var(--sign-muted); font-size: .82rem; line-height: 1.6; margin-bottom: 1.2rem; }

/* Form elements */
.form-g { margin-bottom: 1rem; }
.form-g label { display: block; font-size: .72rem; color: var(--sign-gold); font-weight: 700; text-transform: uppercase; letter-spacing: .1em; margin-bottom: .3rem; }
.form-g input, .form-g select { width: 100%; padding: .7rem .9rem; border-radius: 8px; background: rgba(255,255,255,.04); border: 1px solid rgba(255,215,0,.12); color: var(--sign-white); font-size: .9rem; }
.form-g input:focus { outline: none; border-color: var(--sign-gold); box-shadow: 0 0 0 3px rgba(255,215,0,.08); }

/* Upload zone */
.upload-zone { border: 2px dashed rgba(255,215,0,.2); border-radius: 14px; padding: 2.5rem 1.5rem; text-align: center; cursor: pointer; transition: .2s; }
.upload-zone:hover, .upload-zone.dragover { border-color: var(--sign-gold); background: rgba(255,215,0,.04); }
.upload-zone .uz-icon { font-size: 2.5rem; margin-bottom: .5rem; }
.upload-zone .uz-text { color: var(--sign-muted); font-size: .85rem; }
.upload-zone .uz-hint { color: var(--sign-dim); font-size: .72rem; margin-top: .3rem; }
.upload-zone input[type="file"] { display: none; }

/* Signature pad */
.sig-container { position: relative; }
.sig-canvas { width: 100%; height: 200px; border: 2px solid rgba(255,215,0,.2); border-radius: 12px; background: #fff; cursor: crosshair; touch-action: none; }
.sig-actions { display: flex; gap: .5rem; margin-top: .5rem; }

/* Doc info */
.doc-info { display: grid; grid-template-columns: 1fr 1fr; gap: .8rem; margin: 1rem 0; }
.doc-stat { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 8px; padding: .6rem .8rem; }
.doc-stat .ds-label { font-size: .65rem; color: var(--sign-dim); text-transform: uppercase; letter-spacing: .08em; }
.doc-stat .ds-val { font-size: .85rem; color: #fff; font-weight: 600; word-break: break-all; }
.doc-stat.full { grid-column: 1 / -1; }

/* Steps */
.sign-step { display: none; }
.sign-step.active { display: block; animation: fadeUp .3s; }
@keyframes fadeUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }

/* Buttons */
.btn { display: inline-flex; align-items: center; gap: .4rem; padding: .7rem 1.5rem; border-radius: 8px; font-size: .85rem; font-weight: 700; cursor: pointer; border: none; transition: .2s; text-decoration: none; }
.btn:hover { transform: translateY(-1px); }
.btn-gold { background: linear-gradient(135deg, var(--sign-gold), var(--sign-gold2)); color: #000; }
.btn-red { background: linear-gradient(135deg, var(--sign-red), #991b1b); color: #fff; }
.btn-outline { background: transparent; border: 2px solid rgba(255,215,0,.25); color: var(--sign-gold); }
.btn-dim { background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); color: var(--sign-dim); }
.btn-green { background: linear-gradient(135deg, var(--sign-green), #16a34a); color: #fff; }
.btn-row { display: flex; gap: .8rem; justify-content: flex-end; margin-top: 1rem; flex-wrap: wrap; }

/* Verify page */
.verify-card { text-align: center; }
.verify-check { font-size: 4rem; margin: 1rem 0; }
.verify-check.ok { color: var(--sign-green); }
.verify-check.fail { color: var(--sign-red); }

/* Resp */
@media (max-width: 600px) {
    .doc-info { grid-template-columns: 1fr; }
    .btn-row { flex-direction: column; }
    .btn { width: 100%; justify-content: center; }
}

/* Progress */
.sign-progress { display: flex; justify-content: center; gap: 0; margin: 1.5rem 0; }
.sp-item { display: flex; align-items: center; gap: .3rem; }
.sp-dot { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .7rem; font-weight: 800; background: rgba(255,255,255,.05); color: var(--sign-dim); border: 2px solid rgba(255,255,255,.1); }
.sp-item.active .sp-dot { border-color: var(--sign-gold); color: var(--sign-gold); background: rgba(255,215,0,.1); }
.sp-item.done .sp-dot { border-color: var(--sign-green); color: var(--sign-green); background: rgba(34,197,94,.1); }
.sp-line { width: 40px; height: 2px; background: rgba(255,255,255,.08); margin: 0 .2rem; }
.sp-label { font-size: .6rem; color: var(--sign-dim); margin-left: .1rem; }
.sp-item.active .sp-label { color: var(--sign-gold); }
</style>

<div class="sign-page">

<?php if (isset($verifyDoc)): ?>
    <!-- ══ VERIFICATION PAGE ══ -->
    <div class="sign-hero">
        <div class="sign-badge">&#128274; Document Verification</div>
        <h1><span class="gold">Signature</span> Verification</h1>
    </div>

    <?php if ($verifyDoc): ?>
    <div class="sign-card verify-card">
        <div class="verify-check ok">&#10003;</div>
        <h2 style="color:var(--sign-green); font-size:1.3rem;">Signature Verified</h2>
        <p class="desc" style="max-width:500px; margin:.5rem auto 1.5rem;">
            This document has been cryptographically signed and verified.
        </p>

        <div class="doc-info" style="max-width:500px; margin:0 auto;">
            <div class="doc-stat"><div class="ds-label">Document</div><div class="ds-val"><?= htmlspecialchars($verifyDoc['original_filename']) ?></div></div>
            <div class="doc-stat"><div class="ds-label">Signed By</div><div class="ds-val"><?= htmlspecialchars($verifyDoc['signer_name']) ?></div></div>
            <div class="doc-stat"><div class="ds-label">Signed At</div><div class="ds-val"><?= htmlspecialchars($verifyDoc['signed_at'] ?? 'Pending') ?></div></div>
            <div class="doc-stat"><div class="ds-label">Status</div><div class="ds-val" style="color:var(--sign-green); text-transform:uppercase;"><?= htmlspecialchars($verifyDoc['status']) ?></div></div>
            <div class="doc-stat full"><div class="ds-label">Original SHA3-256</div><div class="ds-val" style="font-family:monospace; font-size:.7rem;"><?= htmlspecialchars($verifyDoc['doc_hash_sha3']) ?></div></div>
            <?php if ($verifyDoc['signed_hash_sha3']): ?>
            <div class="doc-stat full"><div class="ds-label">Signed SHA3-256</div><div class="ds-val" style="font-family:monospace; font-size:.7rem;"><?= htmlspecialchars($verifyDoc['signed_hash_sha3']) ?></div></div>
            <?php endif; ?>
        </div>

        <?php if ($verifyDoc['status'] !== 'pending'): ?>
        <div class="btn-row" style="justify-content:center; margin-top:1.5rem;">
            <a href="/api/sign.php?action=download&token=<?= htmlspecialchars($verifyToken) ?>" class="btn btn-gold">&#128196; Download Signed PDF</a>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="sign-card verify-card">
        <div class="verify-check fail">&#10007;</div>
        <h2 style="color:var(--sign-red); font-size:1.3rem;">Document Not Found</h2>
        <p class="desc">This verification link is invalid or the document has been removed.</p>
    </div>
    <?php endif; ?>

<?php elseif (!$isLoggedIn): ?>
    <!-- ══ NOT LOGGED IN ══ -->
    <div class="sign-hero">
        <div class="sign-badge">&#9998; Digital Signing</div>
        <h1><span class="gold">Sign</span> Documents</h1>
        <p class="sub">Log in to upload and sign documents.</p>
    </div>
    <div class="sign-card" style="text-align:center;">
        <p style="color:var(--sign-muted); margin-bottom:1rem;">You need to be logged in to sign documents.</p>
        <a href="/alfred-ide-auth.php" class="btn btn-gold">Log In</a>
    </div>

<?php else: ?>
    <!-- ══ SIGNING INTERFACE ══ -->
    <div class="sign-hero">
        <div class="sign-badge">&#9998; Digital Signing</div>
        <h1><span class="gold">Sign</span> Documents</h1>
        <p class="sub">Upload a PDF, draw your signature, notify your lawyer. SHA-3 verified.</p>
    </div>

    <!-- Progress -->
    <div class="sign-progress" id="signProgress">
        <div class="sp-item active" data-s="1"><div class="sp-dot">1</div><div class="sp-label">Upload</div></div>
        <div class="sp-line"></div>
        <div class="sp-item" data-s="2"><div class="sp-dot">2</div><div class="sp-label">Details</div></div>
        <div class="sp-line"></div>
        <div class="sp-item" data-s="3"><div class="sp-dot">3</div><div class="sp-label">Sign</div></div>
        <div class="sp-line"></div>
        <div class="sp-item" data-s="4"><div class="sp-dot">4</div><div class="sp-label">Done</div></div>
    </div>

    <!-- STEP 1: Upload -->
    <div class="sign-step active" id="signStep1">
        <div class="sign-card">
            <h2>&#128196; Upload Document</h2>
            <p class="desc">Upload the PDF you need to sign. It will be hashed with SHA-3 for tamper verification.</p>

            <div class="upload-zone" id="uploadZone" onclick="document.getElementById('fileInput').click()">
                <div class="uz-icon">&#128196;</div>
                <div class="uz-text">Tap to select PDF or drag &amp; drop</div>
                <div class="uz-hint">PDF only &middot; Max 20MB</div>
                <input type="file" id="fileInput" accept="application/pdf">
            </div>

            <div id="uploadStatus" style="display:none; margin-top:1rem;"></div>
        </div>
    </div>

    <!-- STEP 2: Recipient Details -->
    <div class="sign-step" id="signStep2">
        <div class="sign-card">
            <h2>&#128231; Recipient Details</h2>
            <p class="desc">Who should be notified when you sign? They'll receive the signed PDF and verification link.</p>

            <div id="docMeta" class="doc-info"></div>

            <div class="form-g">
                <label>Recipient Name (Lawyer / Firm)</label>
                <input type="text" id="recipientName" placeholder="e.g., Justin Wee" maxlength="200">
            </div>
            <div class="form-g">
                <label>Recipient Email</label>
                <input type="email" id="recipientEmail" placeholder="e.g., justin@lawfirm.ca" maxlength="255">
            </div>
            <div class="form-g">
                <label>Your Name (as signer)</label>
                <input type="text" id="signerName" value="<?= htmlspecialchars($isCommander ? 'Danny William Perez' : '') ?>" placeholder="Your full legal name" maxlength="200">
            </div>

            <div class="btn-row">
                <button class="btn btn-dim" onclick="goSignStep(1)">&#8592; Back</button>
                <button class="btn btn-gold" onclick="goSignStep(3)">Continue to Sign &#8594;</button>
            </div>
        </div>
    </div>

    <!-- STEP 3: Signature -->
    <div class="sign-step" id="signStep3">
        <div class="sign-card">
            <h2>&#9998; Draw Your Signature</h2>
            <p class="desc">
                Use your finger (phone) or mouse to draw your signature specimen below.
                This will be stamped onto the last page of the PDF.
            </p>

            <div class="sig-container">
                <canvas id="sigCanvas" class="sig-canvas"></canvas>
                <div class="sig-actions">
                    <button class="btn btn-dim" onclick="clearSig()" style="font-size:.75rem; padding:.4rem .8rem;">&#128465; Clear</button>
                    <button class="btn btn-dim" onclick="undoSig()" style="font-size:.75rem; padding:.4rem .8rem;">&#8630; Undo</button>
                </div>
            </div>

            <div style="margin-top:1rem; padding:.8rem; background:rgba(255,215,0,.04); border:1px solid rgba(255,215,0,.12); border-radius:8px; font-size:.78rem; color:var(--sign-muted);">
                <strong style="color:var(--sign-gold);">&#128274; SHA-3 Verification:</strong>
                The original document hash is locked before signing. Any tampering with the PDF
                will invalidate the cryptographic proof. Your lawyer receives both hashes for verification.
            </div>

            <div class="btn-row">
                <button class="btn btn-dim" onclick="goSignStep(2)">&#8592; Back</button>
                <button class="btn btn-red" id="signBtn" onclick="executeSign()">&#9998; Sign &amp; Send</button>
            </div>
        </div>
    </div>

    <!-- STEP 4: Complete -->
    <div class="sign-step" id="signStep4">
        <div class="sign-card" style="text-align:center;">
            <div style="font-size:4rem; margin:1rem 0; color:var(--sign-green);">&#10003;</div>
            <h2 style="color:var(--sign-green); font-size:1.3rem;">Document Signed</h2>
            <p class="desc" id="signResult" style="max-width:500px; margin:.5rem auto 1.5rem;"></p>
            <div id="signHashes" class="doc-info" style="max-width:500px; margin:0 auto;"></div>
            <div class="btn-row" style="justify-content:center; margin-top:1.5rem;">
                <button class="btn btn-gold" onclick="location.reload()">&#128196; Sign Another Document</button>
                <a href="/sovereignty" class="btn btn-outline">Sovereignty Declaration</a>
            </div>
        </div>
    </div>

<?php endif; ?>

</div>

<script>
let currentDoc = null; // { doc_id, filename, sha3, pages, size }
let sigStrokes = [];
let currentStroke = [];
let isDrawing = false;

// ── UPLOAD ──
const fileInput = document.getElementById('fileInput');
const uploadZone = document.getElementById('uploadZone');

if (fileInput) {
    fileInput.addEventListener('change', handleUpload);

    uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('dragover'); });
    uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('dragover'));
    uploadZone.addEventListener('drop', e => {
        e.preventDefault();
        uploadZone.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            handleUpload();
        }
    });
}

async function handleUpload() {
    const file = fileInput.files[0];
    if (!file) return;
    if (file.type !== 'application/pdf') {
        showUploadStatus('Only PDF files are accepted.', 'error');
        return;
    }
    if (file.size > 20 * 1024 * 1024) {
        showUploadStatus('File too large (max 20MB).', 'error');
        return;
    }

    showUploadStatus('Uploading and hashing...', 'loading');

    const form = new FormData();
    form.append('action', 'upload');
    form.append('document', file);
    form.append('signer_name', '<?= $isCommander ? "Danny William Perez" : "" ?>');

    try {
        const res = await fetch('/api/sign.php', { method: 'POST', body: form });
        const data = await res.json();
        if (data.error) throw new Error(data.error);

        currentDoc = data;
        showUploadStatus(
            `<strong>${data.filename}</strong> uploaded.<br>` +
            `<span style="font-family:monospace;font-size:.7rem;color:var(--sign-gold);">SHA3-256: ${data.sha3_256}</span>`,
            'success'
        );

        // Auto-advance after 1s
        setTimeout(() => goSignStep(2), 800);
    } catch (err) {
        showUploadStatus('Upload failed: ' + err.message, 'error');
    }
}

function showUploadStatus(msg, type) {
    const el = document.getElementById('uploadStatus');
    el.style.display = 'block';
    const colors = { error: 'var(--sign-red)', success: 'var(--sign-green)', loading: 'var(--sign-gold)' };
    el.innerHTML = `<div style="padding:.8rem;border-radius:8px;background:rgba(255,255,255,.03);border:1px solid ${colors[type]};color:${colors[type]};font-size:.85rem;">${msg}</div>`;
}

// ── STEP NAVIGATION ──
function goSignStep(n) {
    if (n === 2 && currentDoc) {
        // Populate doc meta
        document.getElementById('docMeta').innerHTML = `
            <div class="doc-stat"><div class="ds-label">File</div><div class="ds-val">${currentDoc.filename}</div></div>
            <div class="doc-stat"><div class="ds-label">Pages</div><div class="ds-val">${currentDoc.pages}</div></div>
            <div class="doc-stat"><div class="ds-label">Size</div><div class="ds-val">${(currentDoc.size_bytes/1024).toFixed(1)} KB</div></div>
            <div class="doc-stat"><div class="ds-label">Doc ID</div><div class="ds-val">#${currentDoc.doc_id}</div></div>
            <div class="doc-stat full"><div class="ds-label">SHA3-256</div><div class="ds-val" style="font-family:monospace;font-size:.68rem;">${currentDoc.sha3_256}</div></div>
        `;
    }
    if (n === 3) initSigCanvas();

    document.querySelectorAll('.sign-step').forEach(s => s.classList.remove('active'));
    document.getElementById('signStep' + n).classList.add('active');

    document.querySelectorAll('.sp-item').forEach(s => {
        const sn = parseInt(s.dataset.s);
        s.classList.remove('active', 'done');
        if (sn === n) s.classList.add('active');
        else if (sn < n) s.classList.add('done');
    });
}

// ── SIGNATURE CANVAS ──
function initSigCanvas() {
    const c = document.getElementById('sigCanvas');
    const rect = c.parentElement.getBoundingClientRect();
    c.width = rect.width;
    c.height = 200;
    sigStrokes = [];
    drawSig();
}

function drawSig() {
    const c = document.getElementById('sigCanvas');
    const ctx = c.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, c.width, c.height);
    ctx.strokeStyle = '#111';
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';

    for (const stroke of sigStrokes) {
        if (stroke.length < 2) continue;
        ctx.beginPath();
        ctx.moveTo(stroke[0].x, stroke[0].y);
        for (let i = 1; i < stroke.length; i++) ctx.lineTo(stroke[i].x, stroke[i].y);
        ctx.stroke();
    }
}

function clearSig() { sigStrokes = []; drawSig(); }
function undoSig() { sigStrokes.pop(); drawSig(); }

// Touch + mouse events
if (document.getElementById('sigCanvas')) {
    const c = document.getElementById('sigCanvas');

    function getPos(e) {
        const r = c.getBoundingClientRect();
        const t = e.touches ? e.touches[0] : e;
        return { x: t.clientX - r.left, y: t.clientY - r.top };
    }

    function startDraw(e) { e.preventDefault(); isDrawing = true; currentStroke = [getPos(e)]; }
    function moveDraw(e) {
        if (!isDrawing) return;
        e.preventDefault();
        currentStroke.push(getPos(e));
        const allStrokes = [...sigStrokes, currentStroke];
        // Redraw
        const ctx = c.getContext('2d');
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, c.width, c.height);
        ctx.strokeStyle = '#111';
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';
        for (const s of allStrokes) {
            if (s.length < 2) continue;
            ctx.beginPath();
            ctx.moveTo(s[0].x, s[0].y);
            for (let i = 1; i < s.length; i++) ctx.lineTo(s[i].x, s[i].y);
            ctx.stroke();
        }
    }
    function endDraw() { if (!isDrawing) return; isDrawing = false; if (currentStroke.length > 1) sigStrokes.push(currentStroke); currentStroke = []; }

    c.addEventListener('mousedown', startDraw);
    c.addEventListener('mousemove', moveDraw);
    c.addEventListener('mouseup', endDraw);
    c.addEventListener('mouseleave', endDraw);
    c.addEventListener('touchstart', startDraw, { passive: false });
    c.addEventListener('touchmove', moveDraw, { passive: false });
    c.addEventListener('touchend', endDraw);
}

// ── EXECUTE SIGN ──
async function executeSign() {
    if (sigStrokes.length === 0) {
        alert('Please draw your signature first.');
        return;
    }
    if (!currentDoc) {
        alert('No document uploaded.');
        return;
    }

    const btn = document.getElementById('signBtn');
    btn.disabled = true;
    btn.textContent = 'Signing...';

    // Get signature as base64 PNG
    const c = document.getElementById('sigCanvas');
    const sigData = c.toDataURL('image/png');

    const form = new FormData();
    form.append('action', 'sign');
    form.append('doc_id', currentDoc.doc_id);
    form.append('signature', sigData);
    form.append('recipient_email', document.getElementById('recipientEmail').value);
    form.append('recipient_name', document.getElementById('recipientName').value);
    form.append('signer_name', document.getElementById('signerName').value);

    try {
        const res = await fetch('/api/sign.php', { method: 'POST', body: form });
        const data = await res.json();
        if (data.error) throw new Error(data.error);

        // Show results
        document.getElementById('signResult').innerHTML =
            `Your document has been signed and ${data.notified ? 'your lawyer has been notified' : 'is ready for download'}.` +
            `<br><br><a href="${data.verify_url}" style="color:var(--sign-gold);">${data.verify_url}</a>`;

        document.getElementById('signHashes').innerHTML = `
            <div class="doc-stat full"><div class="ds-label">Original SHA3-256</div><div class="ds-val" style="font-family:monospace;font-size:.68rem;">${data.original_hash_sha3}</div></div>
            <div class="doc-stat full"><div class="ds-label">Signed SHA3-256</div><div class="ds-val" style="font-family:monospace;font-size:.68rem;">${data.signed_hash_sha3}</div></div>
            <div class="doc-stat"><div class="ds-label">Signed At</div><div class="ds-val">${data.signed_at}</div></div>
            <div class="doc-stat"><div class="ds-label">Signature Page</div><div class="ds-val">Page ${data.signature_page} of ${data.pages}</div></div>
        `;

        goSignStep(4);
    } catch (err) {
        alert('Signing failed: ' + err.message);
        btn.disabled = false;
        btn.textContent = '✎ Sign & Send';
    }
}
</script>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
