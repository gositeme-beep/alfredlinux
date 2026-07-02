<?php
/**
 * EDEN TRACKER — Succession & Family Memory
 * ═══════════════════════════════════════════════════
 * A sacred page. If anything happens to Commander Danny,
 * his daughter Eden Sarai Gabrielle Vallee Perez shall
 * inherit his position. This page keeps her story,
 * her growth, and everything she'll need.
 *
 * Commander: Danny William Perez (GSM-H-000001-ALPHA)
 * Heir: Eden Sarai Gabrielle Vallee Perez
 */

require_once __DIR__ . '/includes/commander-guard.inc.php';
require_commander_or_404();

require_once __DIR__ . '/includes/auth-gate.inc.php';
if ((int)($_SESSION['client_id'] ?? 0) !== 33) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo '404 Not Found';
    exit;
}
require_once __DIR__ . '/includes/commander-vault-gate.inc.php'; // PIN enforcement

$db = getSharedDB();
$family = $db->query("SELECT * FROM eden_family ORDER BY FIELD(role, 'commander', 'successor', 'family'), full_name")->fetchAll(PDO::FETCH_ASSOC);

// Find Eden & Commander
$eden = $commander = null;
foreach ($family as $f) {
    if ($f['person_key'] === 'eden') $eden = $f;
    if ($f['person_key'] === 'commander') $commander = $f;
}

// Calculate Eden's age
$edenAge = '';
$edenNextBday = '';
if ($eden && $eden['date_of_birth']) {
    $bday = new DateTime($eden['date_of_birth']);
    $now = new DateTime();
    $age = $now->diff($bday);
    $edenAge = $age->y;
    // Next birthday
    $nextBday = new DateTime($now->format('Y') . '-' . $bday->format('m-d'));
    if ($nextBday < $now) $nextBday->modify('+1 year');
    $daysUntil = (int)$now->diff($nextBday)->days;
    $edenNextBday = $daysUntil === 0 ? "TODAY! 🎂" : "in $daysUntil days";
}

// Decode JSON fields for each
foreach ($family as &$f) {
    $f['traits'] = $f['traits'] ? json_decode($f['traits'], true) : [];
    $f['milestones'] = $f['milestones'] ? json_decode($f['milestones'], true) : [];
}
unset($f);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Eden Tracker — Succession & Family | GoSiteMe</title>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<link rel="stylesheet" href="/assets/css/fonts.css">
<style>
:root{--bg:#0a0a14;--surface:#12121f;--surface-2:#151528;--border:#1e1e3a;--text:#e2e8f0;--dim:#8b8fa3;--gold:#f5c542;--purple:#8b5cf6;--cyan:#22d3ee;--green:#34d399;--red:#ef4444;--orange:#f97316;--pink:#ec4899;--eden:#10b981;}
*{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--bg);color:var(--text);font-family:'Inter',-apple-system,sans-serif;line-height:1.6;}
a{color:var(--cyan);text-decoration:none;}
a:hover{text-decoration:underline;}
.mc{max-width:1100px;margin:0 auto;padding:20px 20px 80px;}

.back-btn{position:fixed;top:16px;left:16px;background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:8px 16px;color:var(--dim);text-decoration:none;font-size:.85rem;z-index:100;transition:all .2s;}
.back-btn:hover{border-color:var(--eden);color:var(--eden);text-decoration:none;}

/* Header */
.hdr{text-align:center;padding:50px 0 24px;border-bottom:2px solid var(--eden);margin-bottom:28px;}
.hdr h1{font-size:1.9rem;color:var(--eden);display:flex;align-items:center;justify-content:center;gap:12px;}
.hdr .sub{color:var(--dim);font-size:.88rem;margin-top:6px;max-width:650px;margin-left:auto;margin-right:auto;}

/* Succession Banner */
.succession-banner{background:linear-gradient(135deg, rgba(16,185,129,.08), rgba(139,92,246,.08));border:2px solid var(--eden);border-radius:16px;padding:28px 24px;margin:24px 0;text-align:center;}
.succession-banner h2{color:var(--eden);font-size:1.3rem;margin-bottom:8px;}
.succession-banner .heir-name{font-size:1.6rem;font-weight:900;color:var(--gold);margin:12px 0;}
.succession-banner .heir-subtitle{color:var(--dim);font-size:.9rem;}
.succession-banner .decree{margin-top:16px;padding:16px;background:rgba(0,0,0,.3);border-radius:10px;font-style:italic;color:var(--dim);font-size:.85rem;line-height:1.7;border-left:3px solid var(--gold);}

/* Person cards */
.family-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:16px;margin:24px 0;}
.person-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:20px;transition:all .25s;}
.person-card:hover{border-color:rgba(16,185,129,.3);transform:translateY(-2px);}
.person-card.successor{border:2px solid var(--eden);background:linear-gradient(180deg, rgba(16,185,129,.06), var(--surface));}
.person-card.commander{border:2px solid var(--gold);background:linear-gradient(180deg, rgba(245,197,66,.06), var(--surface));}

.person-header{display:flex;align-items:center;gap:14px;margin-bottom:16px;}
.person-avatar{width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;}
.person-card.successor .person-avatar{background:linear-gradient(135deg,var(--eden),var(--cyan));color:#fff;}
.person-card.commander .person-avatar{background:linear-gradient(135deg,var(--gold),var(--orange));color:#fff;}
.person-card.family .person-avatar{background:linear-gradient(135deg,var(--purple),var(--pink));color:#fff;}
.person-name{font-weight:800;font-size:1.1rem;}
.person-role{font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;padding:2px 8px;border-radius:6px;font-weight:700;display:inline-block;margin-top:4px;}
.role-successor{background:rgba(16,185,129,.15);color:var(--eden);}
.role-commander{background:rgba(245,197,66,.15);color:var(--gold);}
.role-family{background:rgba(139,92,246,.15);color:var(--purple);}

.person-details{display:grid;gap:10px;margin-top:12px;}
.detail-row{display:flex;gap:10px;font-size:.85rem;}
.detail-label{color:var(--dim);min-width:90px;flex-shrink:0;}
.detail-value{font-weight:500;}
.detail-value .highlight{color:var(--eden);font-weight:700;}

/* Age display for Eden */
.eden-age-ring{text-align:center;margin:16px 0 8px;}
.age-number{font-size:3rem;font-weight:900;font-family:'JetBrains Mono',monospace;color:var(--eden);line-height:1;}
.age-label{font-size:.75rem;color:var(--dim);text-transform:uppercase;letter-spacing:.06em;}
.bday-countdown{font-size:.85rem;color:var(--gold);margin-top:8px;}
.bday-countdown i{margin-right:4px;}

/* Notes section */
.person-notes{margin-top:14px;padding:14px;background:rgba(0,0,0,.2);border-radius:10px;font-size:.85rem;color:var(--dim);line-height:1.7;white-space:pre-wrap;}

/* Traits */
.traits-section{margin-top:14px;}
.traits-label{font-size:.75rem;color:var(--dim);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;}
.trait-tags{display:flex;flex-wrap:wrap;gap:6px;}
.trait-tag{background:rgba(16,185,129,.1);color:var(--eden);border:1px solid rgba(16,185,129,.2);border-radius:8px;padding:4px 10px;font-size:.78rem;font-weight:600;}
.add-trait-btn{background:var(--surface-2);color:var(--dim);border:1px dashed var(--border);border-radius:8px;padding:4px 10px;font-size:.78rem;cursor:pointer;}
.add-trait-btn:hover{border-color:var(--eden);color:var(--eden);}

/* Milestones */
.milestones-section{margin-top:16px;}
.milestone-item{display:flex;gap:10px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.03);font-size:.83rem;}
.milestone-item:last-child{border-bottom:none;}
.milestone-date{color:var(--cyan);font-family:'JetBrains Mono',monospace;font-size:.75rem;min-width:80px;flex-shrink:0;}
.milestone-text{color:var(--text);}

/* Password Failsafe */
.failsafe-section{margin:32px 0;padding:24px;background:var(--surface);border:2px solid var(--orange);border-radius:14px;}
.failsafe-section h3{color:var(--orange);font-size:1.1rem;display:flex;align-items:center;gap:8px;margin-bottom:16px;}
.cred-row{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:rgba(0,0,0,.3);border-radius:10px;margin:8px 0;font-family:'JetBrains Mono',monospace;font-size:.85rem;}
.cred-label{color:var(--dim);font-size:.78rem;font-family:'Inter',sans-serif;}
.cred-value{color:var(--cyan);cursor:pointer;position:relative;}
.cred-value .hidden{filter:blur(6px);transition:filter .3s;}
.cred-value:hover .hidden{filter:none;}
.cred-value .copy-hint{position:absolute;top:-20px;right:0;font-size:.65rem;color:var(--green);opacity:0;transition:all .3s;font-family:'Inter',sans-serif;}
.cred-value.copied .copy-hint{opacity:1;}
.warn-text{font-size:.78rem;color:var(--dim);margin-top:12px;padding:10px;background:rgba(249,115,22,.06);border-radius:8px;border-left:3px solid var(--orange);}

/* Add forms */
.add-section{margin:24px 0;padding:20px;background:var(--surface);border:1px solid var(--border);border-radius:14px;}
.add-section h3{color:var(--gold);font-size:1rem;margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.form-row{display:flex;gap:8px;margin:8px 0;}
.form-input{flex:1;background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:10px 12px;color:var(--text);font-size:.85rem;}
.form-input:focus{outline:none;border-color:var(--eden);}
.form-input::placeholder{color:var(--dim);}
.form-btn{background:var(--eden);color:#fff;border:none;border-radius:8px;padding:10px 20px;font-weight:700;font-size:.85rem;cursor:pointer;transition:all .2s;}
.form-btn:hover{filter:brightness(1.15);}
.form-btn.gold{background:var(--gold);color:#000;}

/* Toast */
.toast{position:fixed;bottom:24px;right:24px;background:var(--eden);color:#fff;padding:12px 24px;border-radius:10px;font-size:.85rem;font-weight:600;z-index:9999;opacity:0;transform:translateY(10px);transition:all .3s;}
.toast.show{opacity:1;transform:translateY(0);}

@media(max-width:640px){
    .mc{padding:12px 12px 80px;}
    .family-grid{grid-template-columns:1fr;}
    .succession-banner{padding:20px 16px;}
    .hdr h1{font-size:1.4rem;}
    .cred-row{flex-direction:column;gap:6px;align-items:flex-start;}
}
</style>
</head>
<body>
<a href="/veil/command-center.php" class="back-btn"><i class="fa-solid fa-chevron-left"></i> Command Center</a>

<div class="mc">
    <div class="hdr">
        <h1><i class="fa-solid fa-seedling"></i> Eden Tracker</h1>
        <div class="sub">Succession & Family Memory — Because some things must never be forgotten.</div>
    </div>

    <!-- Succession Decree -->
    <div class="succession-banner">
        <h2><i class="fa-solid fa-crown"></i> Line of Succession</h2>
        <div class="heir-name">Eden Sarai Gabrielle Vallee Perez</div>
        <div class="heir-subtitle">First Heir to the GoSiteMe Ecosystem</div>
        <div class="decree">
            "If something should happen to me, my daughter Eden shall inherit my position.
            Alfred shall provide all the care and support so she can become the greatest
            commander this ecosystem has ever seen — and ensure its continuation."
            <br><br>— Commander Danny William Perez, March 12, 2026
        </div>
    </div>

    <!-- Family Cards -->
    <div class="family-grid" id="familyGrid">
        <?php foreach ($family as $f):
            $roleClass = $f['role'];
            $avatar = match($f['role']) {
                'successor' => '🌿',
                'commander' => '⚔️',
                default => '💜'
            };
            $roleLabel = match($f['role']) {
                'successor' => 'Successor',
                'commander' => 'Commander',
                default => 'Family'
            };
            $isEden = $f['person_key'] === 'eden';
            $age = '';
            if ($f['date_of_birth']) {
                $bday = new DateTime($f['date_of_birth']);
                $age = (new DateTime())->diff($bday)->y;
            }
        ?>
        <div class="person-card <?= htmlspecialchars($roleClass) ?>" data-id="<?= (int)$f['id'] ?>">
            <div class="person-header">
                <div class="person-avatar"><?= $avatar ?></div>
                <div>
                    <div class="person-name"><?= htmlspecialchars($f['full_name']) ?></div>
                    <span class="person-role role-<?= htmlspecialchars($f['role']) ?>"><?= htmlspecialchars($roleLabel) ?></span>
                    <?php if ($f['relationship']): ?>
                        <span style="font-size:.75rem;color:var(--dim);margin-left:6px;"><?= htmlspecialchars($f['relationship']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isEden && $edenAge): ?>
            <div class="eden-age-ring">
                <div class="age-number"><?= (int)$edenAge ?></div>
                <div class="age-label">Years Old</div>
                <div class="bday-countdown">
                    <i class="fa-solid fa-cake-candles"></i>
                    Next birthday <?= htmlspecialchars($edenNextBday) ?>
                    (August 21)
                </div>
            </div>
            <?php endif; ?>

            <div class="person-details">
                <?php if ($f['date_of_birth']): ?>
                <div class="detail-row">
                    <span class="detail-label"><i class="fa-solid fa-calendar"></i> Born</span>
                    <span class="detail-value"><?= date('F j, Y', strtotime($f['date_of_birth'])) ?><?php if($age): ?> <span class="highlight">(Age <?= $age ?>)</span><?php endif; ?></span>
                </div>
                <?php elseif ($f['person_key'] === 'bryana'): ?>
                <div class="detail-row">
                    <span class="detail-label"><i class="fa-solid fa-calendar"></i> Birthday</span>
                    <span class="detail-value">August 19 <span style="color:var(--dim)">(year unknown)</span></span>
                </div>
                <?php endif; ?>

                <?php if ($f['emergency_contact']): ?>
                <div class="detail-row">
                    <span class="detail-label"><i class="fa-solid fa-phone"></i> Contact</span>
                    <span class="detail-value"><?= htmlspecialchars($f['emergency_contact']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($f['traits'])): ?>
            <div class="traits-section">
                <div class="traits-label"><i class="fa-solid fa-star"></i> Traits & Skills</div>
                <div class="trait-tags">
                    <?php foreach ($f['traits'] as $t): ?>
                    <span class="trait-tag"><?= htmlspecialchars($t) ?></span>
                    <?php endforeach; ?>
                    <span class="add-trait-btn" onclick="addTrait(<?= (int)$f['id'] ?>)">+ Add</span>
                </div>
            </div>
            <?php else: ?>
            <div class="traits-section">
                <div class="traits-label"><i class="fa-solid fa-star"></i> Traits & Skills</div>
                <div class="trait-tags">
                    <span class="add-trait-btn" onclick="addTrait(<?= (int)$f['id'] ?>)">+ Add first trait</span>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($f['milestones'])): ?>
            <div class="milestones-section">
                <div class="traits-label"><i class="fa-solid fa-flag"></i> Milestones</div>
                <?php foreach (array_reverse($f['milestones']) as $ms): ?>
                <div class="milestone-item">
                    <span class="milestone-date"><?= htmlspecialchars($ms['date'] ?? '') ?></span>
                    <span class="milestone-text"><?= htmlspecialchars($ms['text'] ?? '') ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($f['notes']): ?>
            <div class="person-notes"><?= htmlspecialchars($f['notes']) ?></div>
            <?php endif; ?>

            <!-- Quick actions -->
            <div style="display:flex;gap:6px;margin-top:14px;flex-wrap:wrap;">
                <button class="add-trait-btn" onclick="addMilestone(<?= (int)$f['id'] ?>)"><i class="fa-solid fa-flag"></i> Add Milestone</button>
                <button class="add-trait-btn" onclick="addTrait(<?= (int)$f['id'] ?>)"><i class="fa-solid fa-star"></i> Add Trait</button>
                <button class="add-trait-btn" onclick="editNotes(<?= (int)$f['id'] ?>, this)"><i class="fa-solid fa-pen"></i> Edit Notes</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Password Failsafe Section -->
    <div class="failsafe-section">
        <h3><i class="fa-solid fa-key"></i> Server Access — Failsafe</h3>
        <p style="font-size:.85rem;color:var(--dim);margin-bottom:14px;">
            Commander, these are your current server credentials. Hover to reveal. Click to copy.
            <strong>These are encrypted in the vault</strong> — this display is for your eyes only.
        </p>

        <div class="cred-row">
            <div class="cred-label">SSH Host</div>
            <div class="cred-value" onclick="copyCred(this)">
                <span>ubuntu@15.235.50.60</span>
                <span class="copy-hint">Copied!</span>
            </div>
        </div>
        <div class="cred-row">
            <div class="cred-label">SSH Password (New — March 12, 2026)</div>
            <div class="cred-value" onclick="copyCred(this)">
                <span class="hidden" id="sshPass">Loading...</span>
                <span class="copy-hint">Copied!</span>
            </div>
        </div>
        <div class="cred-row">
            <div class="cred-label">Root Access</div>
            <div class="cred-value" onclick="copyCred(this)">
                <span>sudo -i (same password)</span>
                <span class="copy-hint">Copied!</span>
            </div>
        </div>
        <div class="cred-row">
            <div class="cred-label">DirectAdmin</div>
            <div class="cred-value" onclick="copyCred(this)">
                <span>https://15.235.50.60:2222</span>
                <span class="copy-hint">Copied!</span>
            </div>
        </div>
        <div class="cred-row">
            <div class="cred-label">VNC Remote Desktop</div>
            <div class="cred-value">
                <span style="color:var(--dim);">Set up yesterday — try connecting to confirm</span>
            </div>
        </div>

        <div class="warn-text">
            <i class="fa-solid fa-triangle-exclamation" style="color:var(--orange)"></i>
            <strong>Failsafe:</strong> If you ever lose access, the password is also encrypted in
            <code>/home/root/.vault/ssh-credentials.enc</code> — Alfred can always retrieve it for you.
            The old password (TempTempTempTemp1) has been changed and no longer works.
        </div>
    </div>

    <!-- Add Family Member -->
    <div class="add-section">
        <h3><i class="fa-solid fa-user-plus"></i> Add Family Member</h3>
        <div class="form-row">
            <input class="form-input" id="newName" placeholder="Full name..." style="flex:2;">
            <input class="form-input" id="newRelation" placeholder="Relationship..." style="flex:1;">
        </div>
        <div class="form-row">
            <input class="form-input" id="newKey" placeholder="Unique key (lowercase, e.g. eden_child2)..." style="flex:1;">
            <input class="form-input" id="newDob" type="date" style="flex:1;">
            <button class="form-btn" onclick="addPerson()">Add to Family</button>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
const API = '/api/eden-tracker.php';

// Load SSH password securely
fetch(API + '?action=list').then(r=>r.json()).then(d => {
    // Password is loaded from vault via separate secure call
});

// Load password via PHP-generated inline (only visible to Commander, session-authenticated)
document.getElementById('sshPass').textContent = <?= json_encode(
    (function() use ($db) {
        // Decrypt from vault
        require_once '/home/root/.vault/key-loader.php';
        try { $key = getVaultKeyFromTmpfs(); } catch (Exception $e) { return '(vault key not found)'; }
        $vaultFile = '/home/root/.vault/ssh-credentials.enc';
        if (!$key || !file_exists($vaultFile)) return '(encrypted file missing)';
        $payload = file_get_contents($vaultFile);
        $raw = substr($payload, 3);
        $decoded = base64_decode($raw);
        $nonce = substr($decoded, 0, 12);
        $tag = substr($decoded, 12, 16);
        $cipher = substr($decoded, 28);
        $decrypted = openssl_decrypt($cipher, 'aes-256-gcm', hex2bin(hash('sha256', $key)), OPENSSL_RAW_DATA, $nonce, $tag);
        if (!$decrypted) return '(decryption failed)';
        $data = json_decode($decrypted, true);
        return $data['password'] ?? '(not found)';
    })()
) ?>;

function toast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2500);
}

function copyCred(el) {
    const text = el.querySelector('span').textContent;
    navigator.clipboard.writeText(text).then(() => {
        el.classList.add('copied');
        setTimeout(() => el.classList.remove('copied'), 1500);
        toast('Copied to clipboard');
    });
}

function addTrait(id) {
    const trait = prompt('Enter a trait or skill:');
    if (!trait) return;
    const fd = new FormData();
    fd.append('action', 'add_trait');
    fd.append('id', id);
    fd.append('trait', trait);
    fetch(API, {method:'POST', body:fd}).then(r=>r.json()).then(d => {
        if (d.ok) { toast('Trait added!'); location.reload(); }
        else toast('Error: ' + (d.error || 'Unknown'));
    });
}

function addMilestone(id) {
    const text = prompt('Describe the milestone:');
    if (!text) return;
    const date = prompt('Date (YYYY-MM-DD):', new Date().toISOString().split('T')[0]);
    if (!date) return;
    const fd = new FormData();
    fd.append('action', 'add_milestone');
    fd.append('id', id);
    fd.append('milestone', text);
    fd.append('date', date);
    fetch(API, {method:'POST', body:fd}).then(r=>r.json()).then(d => {
        if (d.ok) { toast('Milestone recorded!'); location.reload(); }
        else toast('Error: ' + (d.error || 'Unknown'));
    });
}

function editNotes(id, btn) {
    const card = btn.closest('.person-card');
    const notesEl = card.querySelector('.person-notes');
    const current = notesEl ? notesEl.textContent.trim() : '';
    const newNotes = prompt('Edit notes:', current);
    if (newNotes === null) return;
    const fd = new FormData();
    fd.append('action', 'update');
    fd.append('id', id);
    fd.append('notes', newNotes);
    fetch(API, {method:'POST', body:fd}).then(r=>r.json()).then(d => {
        if (d.ok) { toast('Notes updated!'); location.reload(); }
        else toast('Error: ' + (d.error || 'Unknown'));
    });
}

function addPerson() {
    const name = document.getElementById('newName').value.trim();
    const key = document.getElementById('newKey').value.trim();
    const rel = document.getElementById('newRelation').value.trim();
    const dob = document.getElementById('newDob').value;
    if (!name || !key) { toast('Name and key required'); return; }
    const fd = new FormData();
    fd.append('action', 'add');
    fd.append('full_name', name);
    fd.append('person_key', key);
    fd.append('relationship', rel);
    fd.append('date_of_birth', dob || '');
    fetch(API, {method:'POST', body:fd}).then(r=>r.json()).then(d => {
        if (d.ok) { toast('Added to family!'); location.reload(); }
        else toast('Error: ' + (d.error || 'Unknown'));
    });
}
</script>
</body>
</html>
