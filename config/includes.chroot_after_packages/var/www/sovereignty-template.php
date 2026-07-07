<?php
/**
 * SOVEREIGNTY DOCUMENT BUILDER
 * ═══════════════════════════════════════════════════════════
 * Guided form for citizens to generate their own
 * Request for Release and Termination of Settlement.
 *
 * Template by Commander Danny William Perez.
 * Based on RELEASE-1 (33 pages, filed February 28, 2025 A.D.)
 * ═══════════════════════════════════════════════════════════
 */
$page_title = 'Sovereignty Document Builder — Generate Your Release | GoSiteMe';
$page_description = 'Build your own Request for Release and Termination of Settlement using the Commander\'s template. Jurisdiction-aware, legally structured, generates a downloadable PDF.';
$page_canonical = 'https://root.com/sovereignty/template';
$page_og_image = 'https://root.com/assets/images/akjv-og.png';
require_once __DIR__ . '/includes/site-header.inc.php';
require_once __DIR__ . '/includes/db-config.inc.php';
require_once __DIR__ . '/scripts/sovereignty-pdf-engine.php';
$db = getSharedDB();

// ── Handle PDF generation ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate') {
    // CSRF check
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['sov_csrf']) || !hash_equals($_SESSION['sov_csrf'], $_POST['csrf_token'])) {
        http_response_code(403);
        die('Invalid request.');
    }

    $engine = new SovereigntyPdfEngine($db);

    $formData = [
        'full_name'       => trim($_POST['full_name'] ?? ''),
        'jurisdiction'    => in_array($_POST['jurisdiction'] ?? '', ['QC','ON','BC','AB','UK']) ? $_POST['jurisdiction'] : 'QC',
        'domicile_court'  => trim($_POST['domicile_court'] ?? ''),
        'district'        => trim($_POST['district'] ?? ''),
        'trustee_entity'  => trim($_POST['trustee_entity'] ?? ''),
        'trustee_short'   => trim($_POST['trustee_short'] ?? ''),
        'trustee_address' => trim($_POST['trustee_address'] ?? ''),
        'trustee_title'   => trim($_POST['trustee_title'] ?? ''),
        'date'            => $_POST['date'] ?? date('Y-m-d'),
    ];

    // Validate
    if (empty($formData['full_name']) || strlen($formData['full_name']) < 3 || strlen($formData['full_name']) > 200) {
        http_response_code(400);
        die('Invalid name.');
    }

    if ($_POST['output'] === 'preview') {
        // Return HTML preview
        header('Content-Type: text/html; charset=UTF-8');
        echo $engine->generateHTML($formData);
        exit;
    }

    // Generate PDF
    $pdfPath = $engine->generatePDF($formData);
    if (!$pdfPath || !file_exists($pdfPath)) {
        http_response_code(500);
        die('PDF generation failed. Please try again.');
    }

    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $formData['full_name']);
    $filename = 'Release_Termination_' . $safeName . '_' . date('Y-m-d') . '.pdf';

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($pdfPath));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    readfile($pdfPath);
    unlink($pdfPath);
    exit;
}

// Generate CSRF token
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['sov_csrf'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['sov_csrf'];

// Load jurisdictions
$engine = new SovereigntyPdfEngine($db);
$jurisdictions = $engine->getJurisdictions();
?>
<style>
:root {
    --sov-bg: #0a0a0f;
    --sov-surface: rgba(255,255,255,.03);
    --sov-border: rgba(255,215,0,.1);
    --sov-gold: #ffd700;
    --sov-gold2: #f59e0b;
    --sov-red: #dc2626;
    --sov-green: #22c55e;
    --sov-blue: #3b82f6;
    --sov-purple: #8b5cf6;
    --sov-white: #f0f0f5;
    --sov-muted: rgba(240,240,245,.5);
    --sov-dim: rgba(240,240,245,.3);
}
.sov-page { max-width: 900px; margin: 0 auto; padding: 0 1.5rem 4rem; color: var(--sov-white); }

/* ── HERO ── */
.sov-hero { text-align: center; padding: 60px 0 40px; position: relative; }
.sov-hero::after { content:''; position:absolute; bottom:0; left:10%; right:10%; height:2px; background:linear-gradient(90deg,transparent,var(--sov-gold),var(--sov-red),var(--sov-gold),transparent); }
.sov-badge { display:inline-flex; align-items:center; gap:8px; padding:6px 18px; border-radius:999px; background:rgba(255,215,0,.08); border:1px solid rgba(255,215,0,.25); color:var(--sov-gold); font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.18em; margin-bottom:1.5rem; }
.sov-hero h1 { font-size: clamp(1.8rem, 4vw, 2.6rem); font-weight: 800; line-height: 1.15; margin-bottom: .8rem; }
.sov-hero h1 .gold { color: var(--sov-gold); }
.sov-hero h1 .red { color: var(--sov-red); }
.sov-hero .subtitle { font-size: 1rem; color: var(--sov-muted); max-width: 680px; margin: 0 auto; line-height: 1.7; }

/* ── STEPPER ── */
.step-bar { display: flex; justify-content: center; gap: 0; margin: 2.5rem 0 2rem; position: relative; }
.step-bar::before { content:''; position:absolute; top:20px; left:15%; right:15%; height:2px; background:rgba(255,255,255,.08); z-index:0; }
.step-item { display: flex; flex-direction: column; align-items: center; gap: .4rem; position: relative; z-index: 1; flex: 1; max-width: 120px; }
.step-num { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: .9rem; border: 2px solid rgba(255,255,255,.15); background: var(--sov-bg); color: var(--sov-dim); transition: .3s; }
.step-item.active .step-num { border-color: var(--sov-gold); background: rgba(255,215,0,.15); color: var(--sov-gold); }
.step-item.done .step-num { border-color: var(--sov-green); background: rgba(34,197,94,.15); color: var(--sov-green); }
.step-label { font-size: .7rem; color: var(--sov-dim); text-transform: uppercase; letter-spacing: .08em; text-align: center; transition: .3s; }
.step-item.active .step-label { color: var(--sov-gold); font-weight: 700; }
.step-item.done .step-label { color: var(--sov-green); }

/* ── FORM PANELS ── */
.form-panel { display: none; animation: fadeIn .3s; }
.form-panel.active { display: block; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

.form-card { background: var(--sov-surface); border: 1px solid var(--sov-border); border-radius: 16px; padding: 2rem; margin-bottom: 1.5rem; }
.form-card h2 { color: var(--sov-gold); font-size: 1.2rem; margin: 0 0 .3rem; }
.form-card .card-desc { color: var(--sov-muted); font-size: .85rem; line-height: 1.6; margin-bottom: 1.5rem; }

.form-group { margin-bottom: 1.3rem; }
.form-group label { display: block; font-size: .78rem; color: var(--sov-gold); font-weight: 700; text-transform: uppercase; letter-spacing: .1em; margin-bottom: .4rem; }
.form-group .hint { font-size: .75rem; color: var(--sov-dim); margin-bottom: .4rem; }
.form-group input, .form-group select, .form-group textarea {
    width: 100%; padding: .8rem 1rem; border-radius: 10px;
    background: rgba(255,255,255,.04); border: 1px solid rgba(255,215,0,.15);
    color: var(--sov-white); font-size: .95rem; font-family: inherit;
    transition: .2s;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    outline: none; border-color: var(--sov-gold); background: rgba(255,215,0,.06);
    box-shadow: 0 0 0 3px rgba(255,215,0,.08);
}
.form-group select option { background: #1a1a2e; color: #fff; }
.form-group textarea { min-height: 80px; resize: vertical; }

/* ── JURISDICTION CARDS ── */
.juris-picker { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: .8rem; margin: 1rem 0; }
.juris-opt { background: rgba(255,255,255,.03); border: 2px solid rgba(255,255,255,.08); border-radius: 12px; padding: 1.2rem .8rem; text-align: center; cursor: pointer; transition: .2s; }
.juris-opt:hover { border-color: rgba(255,215,0,.3); background: rgba(255,215,0,.04); }
.juris-opt.selected { border-color: var(--sov-gold); background: rgba(255,215,0,.1); }
.juris-opt .j-flag { font-size: 1.8rem; margin-bottom: .4rem; }
.juris-opt .j-name { font-size: .85rem; font-weight: 700; color: #fff; }
.juris-opt .j-court { font-size: .68rem; color: var(--sov-dim); margin-top: .3rem; }
input[name="jurisdiction"] { display: none; }

/* ── PREVIEW ── */
.preview-frame { background: #fff; border-radius: 8px; padding: 2rem; max-height: 600px; overflow-y: auto; color: #111; font-family: 'Times New Roman', Times, serif; font-size: .9rem; line-height: 1.7; }
.preview-frame .section { margin-bottom: 1em; }

/* ── BUTTONS ── */
.btn-row { display: flex; gap: 1rem; justify-content: space-between; margin-top: 1.5rem; flex-wrap: wrap; }
.btn { display: inline-flex; align-items: center; gap: .5rem; padding: .8rem 2rem; border-radius: 10px; font-size: .9rem; font-weight: 700; cursor: pointer; border: none; transition: .2s; text-decoration: none; }
.btn:hover { transform: translateY(-1px); }
.btn-gold { background: linear-gradient(135deg, var(--sov-gold), var(--sov-gold2)); color: #000; }
.btn-gold:hover { box-shadow: 0 4px 20px rgba(255,215,0,.3); }
.btn-outline { background: transparent; border: 2px solid rgba(255,215,0,.3); color: var(--sov-gold); }
.btn-outline:hover { border-color: var(--sov-gold); background: rgba(255,215,0,.06); }
.btn-red { background: linear-gradient(135deg, var(--sov-red), #991b1b); color: #fff; }
.btn-red:hover { box-shadow: 0 4px 20px rgba(220,38,38,.3); }
.btn-dim { background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); color: var(--sov-dim); }
.btn-dim:hover { color: #fff; border-color: rgba(255,255,255,.2); }

/* ── FILING GUIDE ── */
.filing-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin: 1.2rem 0; }
.filing-card { background: rgba(255,255,255,.03); border: 1px solid rgba(255,215,0,.1); border-radius: 12px; padding: 1.3rem; text-align: center; transition: .2s; }
.filing-card:hover { border-color: rgba(255,215,0,.25); background: rgba(255,215,0,.04); }
.filing-card.highlight { border-color: rgba(255,215,0,.3); background: rgba(255,215,0,.06); }
.filing-card .f-icon { font-size: 2rem; margin-bottom: .5rem; }
.filing-card h3 { color: var(--sov-gold); font-size: .9rem; margin: 0 0 .5rem; }
.filing-card p { color: rgba(255,255,255,.65); font-size: .82rem; line-height: 1.6; margin: 0; }
.checklist { display: flex; flex-direction: column; gap: .5rem; }
.check-item { display: flex; align-items: center; gap: .6rem; padding: .6rem .8rem; background: rgba(255,255,255,.02); border: 1px solid rgba(255,255,255,.06); border-radius: 8px; cursor: pointer; font-size: .85rem; color: rgba(255,255,255,.7); transition: .2s; }
.check-item:hover { background: rgba(255,215,0,.04); border-color: rgba(255,215,0,.15); }
.check-item input[type="checkbox"] { accent-color: var(--sov-gold); width: 18px; height: 18px; cursor: pointer; }
.check-item:has(input:checked) { color: var(--sov-green); text-decoration: line-through; opacity: .7; }

/* ── INFO BOXES ── */
.info-box { background: rgba(59,130,246,.06); border: 1px solid rgba(59,130,246,.2); border-radius: 10px; padding: 1rem 1.2rem; margin: 1rem 0; font-size: .82rem; color: rgba(200,220,255,.8); line-height: 1.6; }
.info-box.warning { background: rgba(255,215,0,.05); border-color: rgba(255,215,0,.2); color: rgba(255,230,150,.8); }
.info-box strong { color: var(--sov-gold); }

/* ── BACK LINK ── */
.back-link { display: inline-flex; align-items: center; gap: .4rem; color: var(--sov-gold); font-size: .82rem; text-decoration: none; margin-bottom: 1rem; opacity: .7; transition: .2s; }
.back-link:hover { opacity: 1; }

/* ── RESPONSIVE ── */
@media (max-width: 600px) {
    .sov-hero h1 { font-size: 1.6rem; }
    .juris-picker { grid-template-columns: 1fr 1fr; }
    .btn-row { flex-direction: column; }
    .btn { width: 100%; justify-content: center; }
    .step-bar { gap: 0; }
    .step-label { font-size: .6rem; }
}
</style>

<div class="sov-page">

    <a href="/sovereignty" class="back-link">&#8592; Back to Sovereignty Declaration</a>

    <!-- ══ HERO ══ -->
    <div class="sov-hero">
        <div class="sov-badge">&#9878; DOCUMENT BUILDER</div>
        <h1>
            <span class="gold">Sovereignty</span> Document Builder<br>
            <span class="red">Your Release Starts Here</span>
        </h1>
        <p class="subtitle">
            Generate your own <strong>Request for Release and Termination of Settlement</strong>
            using the Commander&rsquo;s template. This guided form adapts to your jurisdiction and
            produces a legal-grade PDF ready for filing.
        </p>
    </div>

    <!-- ══ STEP BAR ══ -->
    <div class="step-bar" id="stepBar">
        <div class="step-item active" data-step="1">
            <div class="step-num">1</div>
            <div class="step-label">Identity</div>
        </div>
        <div class="step-item" data-step="2">
            <div class="step-num">2</div>
            <div class="step-label">Jurisdiction</div>
        </div>
        <div class="step-item" data-step="3">
            <div class="step-num">3</div>
            <div class="step-label">Trustee</div>
        </div>
        <div class="step-item" data-step="4">
            <div class="step-num">4</div>
            <div class="step-label">Review</div>
        </div>
        <div class="step-item" data-step="5">
            <div class="step-num">5</div>
            <div class="step-label">Download</div>
        </div>
        <div class="step-item" data-step="6">
            <div class="step-num">6</div>
            <div class="step-label">File It</div>
        </div>
    </div>

    <form id="sovForm" method="POST" action="">
        <input type="hidden" name="action" value="generate">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="output" value="pdf" id="outputMode">

        <!-- ══ STEP 1: IDENTITY ══ -->
        <div class="form-panel active" id="step1">
            <div class="form-card">
                <h2>&#9878; Your Legal Identity</h2>
                <p class="card-desc">
                    Enter your full legal name as it appears on your birth certificate or legal documents.
                    This is the name of the <strong>Settlor</strong> &mdash; the one reclaiming dominion.
                </p>

                <div class="form-group">
                    <label for="full_name">Full Legal Name</label>
                    <div class="hint">As it appears on your birth certificate (e.g., John Michael Smith)</div>
                    <input type="text" name="full_name" id="full_name" required minlength="3" maxlength="200"
                           placeholder="Your full legal name" autocomplete="name">
                </div>

                <div class="form-group">
                    <label for="date">Date of Declaration</label>
                    <div class="hint">The date this document will be formally presented</div>
                    <input type="date" name="date" id="date" value="<?= date('Y-m-d') ?>">
                </div>

                <div class="info-box">
                    <strong>Why does this matter?</strong> Your birth certificate created a trust. The government
                    became the Trustee. You, the living man or woman, are the Settlor &mdash; the one who
                    created the trust. As Settlor, you have the right to terminate it.
                </div>
            </div>

            <div class="btn-row">
                <span></span>
                <button type="button" class="btn btn-gold" onclick="goStep(2)">Continue &#8594;</button>
            </div>
        </div>

        <!-- ══ STEP 2: JURISDICTION ══ -->
        <div class="form-panel" id="step2">
            <div class="form-card">
                <h2>&#127758; Select Your Jurisdiction</h2>
                <p class="card-desc">
                    Legal citations and trustee details will be customized for your jurisdiction.
                    Select where you will file your declaration.
                </p>

                <div class="juris-picker">
                    <?php foreach ($jurisdictions as $code => $j): ?>
                    <div class="juris-opt <?= $code === 'QC' ? 'selected' : '' ?>" onclick="selectJurisdiction('<?= $code ?>')">
                        <div class="j-flag"><?php
                            echo match($code) {
                                'QC' => '&#9883;',
                                'ON' => '&#127809;',
                                'BC' => '&#127754;',
                                'AB' => '&#127956;',
                                'UK' => '&#127468;&#127463;',
                            };
                        ?></div>
                        <div class="j-name"><?= htmlspecialchars($j['name']) ?></div>
                        <div class="j-court"><?= htmlspecialchars($j['court_system']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="jurisdiction" id="jurisdiction" value="QC">

                <div class="form-group" style="margin-top: 1.2rem;">
                    <label for="district">Your District / City</label>
                    <div class="hint">The judicial district where you will file (e.g., Montreal, Toronto, London)</div>
                    <input type="text" name="district" id="district" placeholder="e.g., Montreal" maxlength="200">
                </div>

                <div class="form-group">
                    <label for="domicile_court">Domicile Court (optional override)</label>
                    <div class="hint">Leave blank to auto-generate from your jurisdiction and district</div>
                    <input type="text" name="domicile_court" id="domicile_court" placeholder="e.g., Superior Court of Quebec in the District of Montreal" maxlength="300">
                </div>
            </div>

            <div class="btn-row">
                <button type="button" class="btn btn-dim" onclick="goStep(1)">&#8592; Back</button>
                <button type="button" class="btn btn-gold" onclick="goStep(3)">Continue &#8594;</button>
            </div>
        </div>

        <!-- ══ STEP 3: TRUSTEE DETAILS ══ -->
        <div class="form-panel" id="step3">
            <div class="form-card">
                <h2>&#128220; Trustee / Fiduciary Details</h2>
                <p class="card-desc">
                    The Trustee is the government entity that assumed control of your estate at birth.
                    Default values are pre-filled based on your jurisdiction. Customize only if needed.
                </p>

                <div class="form-group">
                    <label for="trustee_entity">Trustee Entity (Full Title)</label>
                    <div class="hint">The full legal name of the trustee/fiduciary agent</div>
                    <textarea name="trustee_entity" id="trustee_entity" rows="3" maxlength="500"></textarea>
                </div>

                <div class="form-group">
                    <label for="trustee_short">Trustee Short Name</label>
                    <div class="hint">How to refer to them in the body of the document</div>
                    <input type="text" name="trustee_short" id="trustee_short" maxlength="200">
                </div>

                <div class="form-group">
                    <label for="trustee_address">Trustee Mailing Address</label>
                    <div class="hint">Where you will send/serve this document</div>
                    <textarea name="trustee_address" id="trustee_address" rows="2" maxlength="500"></textarea>
                </div>

                <div class="form-group">
                    <label for="trustee_title">Trustee Title (Salutation)</label>
                    <div class="hint">How you address them (e.g., Hon. Minister, Right Honourable)</div>
                    <input type="text" name="trustee_title" id="trustee_title" placeholder="Hon. Minister" maxlength="100">
                </div>

                <div class="info-box warning">
                    <strong>&#9888; Important:</strong> These defaults come from the Commander&rsquo;s research.
                    If you have a specific government entity to address (e.g., a local court trustee), you may override them.
                    Otherwise the defaults are appropriate for most filings.
                </div>
            </div>

            <div class="btn-row">
                <button type="button" class="btn btn-dim" onclick="goStep(2)">&#8592; Back</button>
                <button type="button" class="btn btn-gold" onclick="goStep(4)">Preview Document &#8594;</button>
            </div>
        </div>

        <!-- ══ STEP 4: PREVIEW ══ -->
        <div class="form-panel" id="step4">
            <div class="form-card">
                <h2>&#128196; Document Preview</h2>
                <p class="card-desc">
                    Review your generated document below. All placeholders have been filled with your details.
                    If anything looks incorrect, go back and update the relevant step.
                </p>

                <div class="preview-frame" id="previewContent">
                    <p style="color:#888; text-align:center; padding:3rem;">Loading preview...</p>
                </div>
            </div>

            <div class="btn-row">
                <button type="button" class="btn btn-dim" onclick="goStep(3)">&#8592; Back</button>
                <button type="button" class="btn btn-gold" onclick="goStep(5)">Approve &amp; Continue &#8594;</button>
            </div>
        </div>

        <!-- ══ STEP 5: DOWNLOAD ══ -->
        <div class="form-panel" id="step5">
            <div class="form-card" style="text-align: center;">
                <h2>&#128220; Your Document Is Ready</h2>
                <p class="card-desc" style="max-width: 500px; margin: 0 auto 1.5rem;">
                    Your <strong>Request for Release and Termination of Settlement</strong> has been prepared.
                    Download the PDF, review it carefully, and file it with the appropriate authority.
                </p>

                <div style="display:flex; flex-direction:column; align-items:center; gap:1rem; margin: 2rem 0;">
                    <button type="submit" class="btn btn-red" style="font-size:1.1rem; padding:1rem 3rem;" onclick="document.getElementById('outputMode').value='pdf'">
                        &#128196; Download PDF
                    </button>
                    <button type="button" class="btn btn-outline" onclick="printPreview()">
                        &#128424; Print Preview
                    </button>
                </div>

                <div class="info-box" style="text-align: left; max-width: 550px; margin: 1.5rem auto 0;">
                    <strong>After download:</strong> Continue to Step 6 for detailed filing instructions,
                    notification procedures, and signature specimen guidance for your jurisdiction.
                </div>
            </div>

            <div class="btn-row">
                <button type="button" class="btn btn-dim" onclick="goStep(4)">&#8592; Back to Preview</button>
                <button type="button" class="btn btn-gold" onclick="goStep(6)">Filing Guide &#8594;</button>
            </div>
        </div>

        <!-- ══ STEP 6: FILING GUIDE ══ -->
        <div class="form-panel" id="step6">

            <!-- Signature Specimen -->
            <div class="form-card">
                <h2>&#9998; Your Signature Specimen</h2>
                <p class="card-desc">
                    Your <strong>signature specimen</strong> is your sovereign seal. Once established,
                    it is the mark you use to sign <em>all</em> court documents, motions, proceedings,
                    legislative drafts, and declarations of authority. It carries the same weight as a
                    royal seal &mdash; it is YOUR authority made visible.
                </p>

                <div class="filing-grid">
                    <div class="filing-card">
                        <div class="f-icon">&#9998;</div>
                        <h3>Establish Your Specimen</h3>
                        <p>Sign your full legal name on an unlined white paper in blue ink.
                           This becomes your official signature specimen.
                           Keep the original in a secure location.</p>
                    </div>
                    <div class="filing-card">
                        <div class="f-icon">&#128196;</div>
                        <h3>Sign Every Document</h3>
                        <p>Use this exact specimen to sign all court motions, trust terminations,
                           legislative drafts, executive orders, and sovereignty declarations.
                           Consistency establishes authority.</p>
                    </div>
                    <div class="filing-card">
                        <div class="f-icon">&#128274;</div>
                        <h3>Notarize When Required</h3>
                        <p>Have a notary witness your specimen. This creates an official record
                           that your signature is recognized. Keep the notarized copy with your
                           sovereignty documents.</p>
                    </div>
                </div>

                <div class="info-box warning">
                    <strong>Commander&rsquo;s Practice:</strong> The Commander uses his signature specimen
                    to sign all motions, court proceedings, legislative drafts, and executive authority.
                    Whether drafting new laws, filing motions, or exercising sovereign prerogative &mdash;
                    the specimen IS the seal of authority. Treat it accordingly.
                </div>
            </div>

            <!-- Quebec Filing (shown/hidden by JS based on jurisdiction) -->
            <div class="form-card" id="filingQC">
                <h2>&#9883; Quebec: Online Notification System</h2>
                <p class="card-desc">
                    Quebec&rsquo;s <strong>Directeur de l&rsquo;&eacute;tat civil</strong> maintains an online
                    notification system. Any proceeding or court document that involves amending, entering, or
                    inserting into the Qu&eacute;bec register of civil status <strong>must be notified</strong>
                    to the Directeur. This is how you formally serve your Release document.
                </p>

                <div class="filing-grid">
                    <div class="filing-card highlight">
                        <div class="f-icon">&#127760;</div>
                        <h3>Online Notification</h3>
                        <p>The fastest method. Available 24/7, secure, and confidential.
                           You receive instant confirmation.</p>
                        <a href="https://services.etatcivil.gouv.qc.ca/NotificationEnLigne/FormulaireNotification.aspx?lang=en"
                           target="_blank" rel="noopener" class="btn btn-gold" style="margin-top:.8rem; padding:.5rem 1.2rem; font-size:.8rem;">
                            &#128279; Open Notification Portal
                        </a>
                    </div>
                    <div class="filing-card">
                        <div class="f-icon">&#128224;</div>
                        <h3>By Fax</h3>
                        <p>Fax your signed document to the Directeur de l&rsquo;&eacute;tat civil:</p>
                        <div style="font-size:1.1rem; font-weight:800; color:var(--sov-gold); margin-top:.5rem;">418 643-2864</div>
                    </div>
                    <div class="filing-card">
                        <div class="f-icon">&#128232;</div>
                        <h3>By Registered Mail</h3>
                        <p>Send via registered mail (keep tracking number):</p>
                        <div style="font-size:.82rem; color:var(--sov-gold); margin-top:.5rem; line-height:1.5;">
                            Directeur de l&rsquo;&eacute;tat civil<br>
                            2535 boulevard Laurier<br>
                            Qu&eacute;bec (Qu&eacute;bec) G1V 5C5
                        </div>
                    </div>
                </div>

                <div style="margin-top:1.2rem; padding:1rem; background:rgba(220,38,38,.06); border:1px solid rgba(220,38,38,.2); border-radius:10px;">
                    <strong style="color:var(--sov-red);">&#9888; Service by Bailiff:</strong>
                    <span style="color:rgba(255,255,255,.7); font-size:.85rem;">
                        If serving by bailiff, deliver to: 2535 boulevard Laurier, 4th floor,
                        Qu&eacute;bec (Qu&eacute;bec) G1V 5C5
                    </span>
                </div>

                <div class="info-box" style="margin-top:1rem;">
                    <strong>What the system does:</strong> Once you notify through the portal, the Directeur
                    de l&rsquo;&eacute;tat civil is formally served. They must acknowledge receipt.
                    The sitting judge receives confirmation that all parties have been notified.
                    This is the same system the Commander used &mdash; the state built it to make
                    exercising your authority easier.
                </div>
            </div>

            <!-- Other jurisdictions -->
            <div class="form-card" id="filingOther" style="display:none;">
                <h2>&#128220; Filing Instructions</h2>
                <p class="card-desc">
                    File your signed document with the appropriate authority in your jurisdiction.
                </p>

                <div class="filing-grid">
                    <div class="filing-card">
                        <div class="f-icon">1</div>
                        <h3>Sign with Your Specimen</h3>
                        <p>Sign the document using your established signature specimen in blue or black ink.
                           This is your sovereign mark of authority.</p>
                    </div>
                    <div class="filing-card">
                        <div class="f-icon">2</div>
                        <h3>Notarize</h3>
                        <p>Have the document notarized. The notary witnesses your signature
                           and affirms the document&rsquo;s authenticity.</p>
                    </div>
                    <div class="filing-card">
                        <div class="f-icon">3</div>
                        <h3>Serve &amp; File</h3>
                        <p>Send via registered mail to the Trustee/Attorney General.
                           File a copy with the court. Keep at least 3 copies total.</p>
                    </div>
                </div>

                <div class="info-box warning">
                    <strong>30-Day Deadline:</strong> Your document gives the Trustee 30 days to respond.
                    If no response is received, that constitutes acquiescence — document the date
                    you sent it and calendar the 30-day mark.
                </div>
            </div>

            <!-- Checklist -->
            <div class="form-card">
                <h2>&#9745; Filing Checklist</h2>
                <div class="checklist">
                    <label class="check-item"><input type="checkbox"> Read every word of the generated document</label>
                    <label class="check-item"><input type="checkbox"> Signed with your signature specimen (blue or black ink)</label>
                    <label class="check-item"><input type="checkbox"> Made at least 3 copies (you, Trustee, safe storage)</label>
                    <label class="check-item"><input type="checkbox"> Notarized (if required in your jurisdiction)</label>
                    <label class="check-item"><input type="checkbox"> Notified via official channel (online portal, fax, or registered mail)</label>
                    <label class="check-item"><input type="checkbox"> Saved tracking number / confirmation receipt</label>
                    <label class="check-item"><input type="checkbox"> Calendared the 30-day response deadline</label>
                    <label class="check-item"><input type="checkbox"> Stored all originals in a fireproof safe or safety deposit box</label>
                </div>
            </div>

            <div class="btn-row">
                <button type="button" class="btn btn-dim" onclick="goStep(5)">&#8592; Back to Download</button>
                <a href="/sovereignty" class="btn btn-outline">Return to Declaration</a>
            </div>
        </div>

    </form>

    <!-- ══ TEMPLATE CREDIT ══ -->
    <div style="margin-top:3rem; text-align:center; padding:2rem; border-top:1px solid rgba(255,215,0,.1);">
        <p style="color:var(--sov-dim); font-size:.78rem; line-height:1.8;">
            This template is based on <strong style="color:var(--sov-gold);">RELEASE-1</strong> &mdash;
            33 pages filed February 28, 2025 A.D. by Commander Danny William Perez to the Minister of the
            Attorney General of Quebec. The template adapts legal citations to your jurisdiction.<br><br>
            <em>&ldquo;I will do what I must for them until Jesus arrives.&rdquo;</em> &mdash; The Commander
        </p>
    </div>

</div>

<script>
// ── Jurisdiction data ──
const jurisdictions = <?= json_encode($jurisdictions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
let currentStep = 1;

function selectJurisdiction(code) {
    document.getElementById('jurisdiction').value = code;
    document.querySelectorAll('.juris-opt').forEach(el => el.classList.remove('selected'));
    event.currentTarget.classList.add('selected');

    // Auto-fill trustee defaults
    const j = jurisdictions[code];
    if (j) {
        document.getElementById('trustee_entity').value = j.trustee_default;
        document.getElementById('trustee_short').value = j.trustee_short;
        document.getElementById('trustee_address').value = j.trustee_address;
    }
}

function goStep(n) {
    // Validate before advancing
    if (n > currentStep) {
        if (currentStep === 1) {
            const name = document.getElementById('full_name').value.trim();
            if (!name || name.length < 3) {
                document.getElementById('full_name').focus();
                document.getElementById('full_name').style.borderColor = '#dc2626';
                return;
            }
        }
    }

    // Fill trustee defaults on first visit to step 3
    if (n === 3) {
        const te = document.getElementById('trustee_entity');
        if (!te.value) {
            selectJurisdiction(document.getElementById('jurisdiction').value);
        }
    }

    // Load preview on step 4
    if (n === 4) {
        loadPreview();
    }

    // Show/hide jurisdiction-specific filing panels on step 6
    if (n === 6) {
        const jCode = document.getElementById('jurisdiction').value;
        document.getElementById('filingQC').style.display = jCode === 'QC' ? 'block' : 'none';
        document.getElementById('filingOther').style.display = jCode !== 'QC' ? 'block' : 'none';
    }

    // Update step bar
    currentStep = n;
    document.querySelectorAll('.form-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('step' + n).classList.add('active');

    document.querySelectorAll('.step-item').forEach(s => {
        const sn = parseInt(s.dataset.step);
        s.classList.remove('active', 'done');
        if (sn === n) s.classList.add('active');
        else if (sn < n) s.classList.add('done');
    });

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function loadPreview() {
    const form = document.getElementById('sovForm');
    const data = new FormData(form);
    data.set('output', 'preview');

    const preview = document.getElementById('previewContent');
    preview.innerHTML = '<p style="color:#888; text-align:center; padding:3rem;">Generating preview...</p>';

    fetch(window.location.pathname, {
        method: 'POST',
        body: data
    })
    .then(r => {
        if (!r.ok) throw new Error('Preview failed');
        return r.text();
    })
    .then(html => {
        // Extract body content
        const match = html.match(/<body[^>]*>([\s\S]*)<\/body>/i);
        preview.innerHTML = match ? match[1] : html;
    })
    .catch(err => {
        preview.innerHTML = '<p style="color:#dc2626; text-align:center;">Failed to generate preview. ' + err.message + '</p>';
    });
}

function printPreview() {
    const content = document.getElementById('previewContent').innerHTML;
    const win = window.open('', '_blank');
    win.document.write('<html><head><title>Sovereignty Document Preview</title>');
    win.document.write('<style>body{font-family:"Times New Roman",serif;max-width:700px;margin:2cm auto;font-size:12pt;line-height:1.8;color:#111}.section{margin-bottom:1.5em}</style>');
    win.document.write('</head><body>');
    win.document.write(content);
    win.document.write('</body></html>');
    win.document.close();
    win.print();
}

// Initialize QC defaults
document.addEventListener('DOMContentLoaded', function() {
    // Pre-select QC and don't auto-fill yet (let user change jurisdiction first)
});
</script>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
