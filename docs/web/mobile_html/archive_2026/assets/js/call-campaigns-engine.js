/* ===== CAMPAIGN BUILDER ENGINE ===== */
const Campaign = (() => {
    let currentStep = 1;
    let csvData = { headers: [], rows: [], fileName: '' };
    let selectedAgent = 'sales';
    const totalSteps = 4;

    function goStep(step) {
        if (step < 1 || step > totalSteps) return;
        currentStep = step;
        // Update tabs
        document.querySelectorAll('.cc-step-tab').forEach(tab => {
            const s = parseInt(tab.dataset.step);
            tab.classList.remove('active');
            if (s === step) tab.classList.add('active');
            if (s < step) tab.classList.add('completed');
        });
        // Update content
        for (let i = 1; i <= totalSteps; i++) {
            const el = document.getElementById('ccStep' + i);
            if (el) el.classList.toggle('active', i === step);
        }
        // Update nav buttons
        const prevBtn = document.getElementById('ccPrevBtn');
        const nextBtn = document.getElementById('ccNextBtn');
        if (prevBtn) prevBtn.style.display = step > 1 ? '' : 'none';
        if (nextBtn) nextBtn.style.display = step < totalSteps ? '' : 'none';
        // Update review on step 4
        if (step === 4) updateReview();
    }

    function next() { goStep(currentStep + 1); }
    function prev() { goStep(currentStep - 1); }

    function handleFile(e) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (ev) => {
            const text = ev.target.result;
            const lines = text.split('\n').filter(l => l.trim());
            if (lines.length < 2) { alert('CSV must have at least a header and one row.'); return; }
            const headers = lines[0].split(',').map(h => h.trim().replace(/^"|"$/g, ''));
            const rows = lines.slice(1).map(l => l.split(',').map(c => c.trim().replace(/^"|"$/g, '')));
            csvData = { headers, rows, fileName: file.name };
            
            // Show summary
            const summary = document.getElementById('ccUploadSummary');
            const info = document.getElementById('ccUploadInfo');
            if (summary && info) {
                info.textContent = `${file.name} — ${rows.length} contacts, ${headers.length} columns`;
                summary.classList.add('visible');
            }

            // Show column mapping
            const mapDiv = document.getElementById('ccColumnMap');
            const mapBody = document.getElementById('ccMapBody');
            if (mapDiv && mapBody) {
                const requiredFields = ['Phone Number', 'First Name', 'Last Name', 'Company', 'Email'];
                mapBody.innerHTML = requiredFields.map(field => {
                    const options = headers.map((h, i) => {
                        const selected = h.toLowerCase().includes(field.split(' ')[0].toLowerCase()) ? 'selected' : '';
                        return `<option value="${i}" ${selected}>${h}</option>`;
                    }).join('');
                    const previewIdx = headers.findIndex(h => h.toLowerCase().includes(field.split(' ')[0].toLowerCase()));
                    const preview = previewIdx >= 0 && rows[0] ? rows[0][previewIdx] || '—' : '—';
                    return `<tr><td style="font-weight:600;font-size:.85rem">${field}</td><td><select>${options}<option value="-1">— Not mapped —</option></select></td><td class="preview">${preview}</td></tr>`;
                }).join('');
                mapDiv.classList.add('visible');
            }
        };
        reader.readAsText(file);
    }

    // Upload zone drag events
    const zone = document.getElementById('ccUploadZone');
    if (zone) {
        zone.addEventListener('dragover', (e) => { e.preventDefault(); zone.classList.add('dragover'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length) {
                const input = document.getElementById('ccFileInput');
                if (input) {
                    const dt = new DataTransfer();
                    dt.items.add(files[0]);
                    input.files = dt.files;
                    handleFile({ target: input });
                }
            }
        });
    }

    function selectAgent(el, type) {
        document.querySelectorAll('.cc-agent-card').forEach(c => c.classList.remove('selected'));
        el.classList.add('selected');
        selectedAgent = type;
    }

    function insertVar(v) {
        const ta = document.getElementById('ccScriptText');
        if (!ta) return;
        const start = ta.selectionStart;
        const end = ta.selectionEnd;
        ta.value = ta.value.substring(0, start) + v + ta.value.substring(end);
        ta.focus();
        ta.selectionStart = ta.selectionEnd = start + v.length;
    }

    function updateReview() {
        const contacts = document.getElementById('ccRevContacts');
        const agent = document.getElementById('ccRevAgent');
        const window_el = document.getElementById('ccRevWindow');
        const tz = document.getElementById('ccRevTZ');
        const pacing = document.getElementById('ccRevPacing');
        const duration = document.getElementById('ccRevDuration');
        const cost = document.getElementById('ccRevCost');

        if (contacts) contacts.textContent = csvData.rows.length.toLocaleString() || '0';
        if (agent) agent.textContent = selectedAgent.charAt(0).toUpperCase() + selectedAgent.slice(1);
        
        const startTime = document.getElementById('ccStartTime');
        const endTime = document.getElementById('ccEndTime');
        if (window_el && startTime && endTime) {
            window_el.textContent = startTime.value + ' - ' + endTime.value;
        }
        const tzEl = document.getElementById('ccTimezone');
        if (tz && tzEl) tz.textContent = tzEl.value.split(' ')[0];

        const pacingEl = document.getElementById('ccPacing');
        if (pacing && pacingEl) pacing.textContent = pacingEl.value;

        // Estimate: ~2 min avg call, with pacing
        const totalContacts = csvData.rows.length || 0;
        const concurrency = parseInt(pacingEl?.value || 3);
        const avgCallMin = 2;
        if (totalContacts > 0) {
            const totalMin = (totalContacts * avgCallMin) / concurrency;
            const hours = Math.floor(totalMin / 60);
            const mins = Math.round(totalMin % 60);
            if (duration) duration.textContent = hours > 0 ? `~${hours}h ${mins}m` : `~${mins}m`;
            const estCost = (totalContacts * avgCallMin * 0.03).toFixed(2);
            if (cost) cost.textContent = '$' + estCost;
        }
    }

    function launch() {
        const btn = document.getElementById('ccLaunchBtn');
        if (!btn) return;
        if (!confirm('Launch this campaign? Calls will begin at the scheduled time.')) return;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Launching...';
        btn.disabled = true;
        setTimeout(() => {
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Campaign Launched!';
            btn.style.background = '#00b894';
            showToast('Campaign launched successfully! Calls will begin at the scheduled time.');
        }, 2000);
    }

    function showToast(msg) {
        if (window.GDSToast) return GDSToast.success(msg);
    }

    return { goStep, next, prev, handleFile, selectAgent, insertVar, launch };
})();

// Animation
const ccStyle = document.createElement('style');
ccStyle.textContent = '@keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}';
document.head.appendChild(ccStyle);
