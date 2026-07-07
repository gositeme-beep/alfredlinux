const API = '/api/healthcare.php';

async function hGet(action, params = '') {
    try { const r = await fetch(`${API}?action=${action}${params}`, {credentials:'same-origin'}); return await r.json(); }
    catch(e) { console.warn('API error:', action, e); return {success:false}; }
}

async function loadDashboard() {
    const d = await hGet('hc_dashboard');
    if (d.success && d.dashboard) {
        const k = d.dashboard;
        document.getElementById('kpi-patients').textContent = k.total_patients || 0;
        document.getElementById('kpi-appts').textContent = k.todays_appointments || 0;
        document.getElementById('kpi-unsigned').textContent = k.unsigned_notes || 0;
        document.getElementById('kpi-labs').textContent = k.pending_labs || 0;
        document.getElementById('kpi-intakes').textContent = k.pending_intakes || 0;
    }
}

async function loadTodaysAppts() {
    const d = await hGet('appt_today');
    const el = document.getElementById('todays-appts');
    if (d.success && d.appointments && d.appointments.length > 0) {
        el.innerHTML = `<table class="hc-table"><thead><tr><th>Time</th><th>Patient</th><th>Type</th><th>Status</th><th>Notes</th></tr></thead><tbody>${
            d.appointments.map(a => `<tr>
                <td>${a.appointment_time?.slice(11,16) || '--'}</td>
                <td>${a.patient_name || 'Patient #' + a.patient_id}</td>
                <td>${a.appointment_type || '--'}</td>
                <td><span class="hc-status hc-s-${a.status === 'completed' ? 'completed' : a.status === 'cancelled' ? 'cancelled' : 'scheduled'}">${a.status}</span></td>
                <td style="color:var(--hc-muted);max-width:200px;overflow:hidden;text-overflow:ellipsis;">${a.notes || ''}</td>
            </tr>`).join('')
        }</tbody></table>`;
    } else { el.innerHTML = '<div style="color:var(--hc-muted);text-align:center;padding:1rem;">No appointments today</div>'; }
}

async function loadPatients() {
    const d = await hGet('patient_list', '&limit=8');
    const el = document.getElementById('patients-list');
    if (d.success && d.patients && d.patients.length > 0) {
        el.innerHTML = `<table class="hc-table"><thead><tr><th>Name</th><th>DOB</th><th>Status</th></tr></thead><tbody>${
            d.patients.map(p => `<tr><td>${p.first_name || ''} ${p.last_name || ''}</td><td style="color:var(--hc-muted)">${p.date_of_birth || '--'}</td><td><span class="hc-status hc-s-active">${p.status || 'active'}</span></td></tr>`).join('')
        }</tbody></table>`;
    } else { el.innerHTML = '<div style="color:var(--hc-muted);text-align:center;padding:1rem;">No patients yet</div>'; }
}

async function loadSOAP() {
    const d = await hGet('soap_list', '&limit=6');
    const el = document.getElementById('soap-list');
    if (d.success && d.notes && d.notes.length > 0) {
        el.innerHTML = `<table class="hc-table"><thead><tr><th>Patient</th><th>Date</th><th>Signed</th></tr></thead><tbody>${
            d.notes.map(n => `<tr><td>Patient #${n.patient_id}</td><td style="color:var(--hc-muted)">${n.encounter_date || ''}</td><td>${n.signed_at ? '<span class="hc-status hc-s-completed">Signed</span>' : '<span class="hc-status hc-s-pending">Draft</span>'}</td></tr>`).join('')
        }</tbody></table>`;
    } else { el.innerHTML = '<div style="color:var(--hc-muted);text-align:center;padding:1rem;">No SOAP notes yet</div>'; }
}

async function loadMeds() {
    const d = await hGet('med_list', '&status=active&limit=8');
    const el = document.getElementById('meds-list');
    if (d.success && d.medications && d.medications.length > 0) {
        el.innerHTML = `<table class="hc-table"><thead><tr><th>Medication</th><th>Dosage</th><th>Frequency</th></tr></thead><tbody>${
            d.medications.map(m => `<tr><td>${m.medication_name}</td><td>${m.dosage || '--'}</td><td style="color:var(--hc-muted)">${m.frequency || '--'}</td></tr>`).join('')
        }</tbody></table>`;
    } else { el.innerHTML = '<div style="color:var(--hc-muted);text-align:center;padding:1rem;">No active medications</div>'; }
}

async function loadLabs() {
    const d = await hGet('lab_list', '&limit=6');
    const el = document.getElementById('labs-list');
    if (d.success && d.labs && d.labs.length > 0) {
        el.innerHTML = `<table class="hc-table"><thead><tr><th>Test</th><th>Status</th><th>Ordered</th></tr></thead><tbody>${
            d.labs.map(l => {
                const sc = l.status === 'completed' ? 'hc-s-completed' : l.status === 'reviewed' ? 'hc-s-active' : 'hc-s-pending';
                return `<tr><td>${l.test_name}</td><td><span class="hc-status ${sc}">${l.status}</span></td><td style="color:var(--hc-muted)">${l.ordered_at?.slice(0,10) || ''}</td></tr>`;
            }).join('')
        }</tbody></table>`;
    } else { el.innerHTML = '<div style="color:var(--hc-muted);text-align:center;padding:1rem;">No lab orders</div>'; }
}

async function loadVitals() {
    const d = await hGet('vitals_history', '&limit=1');
    const el = document.getElementById('vitals-display');
    if (d.success && d.vitals && d.vitals.length > 0) {
        const v = d.vitals[0];
        el.innerHTML = `<div class="hc-vitals-grid">
            <div class="hc-vital"><div class="hc-vital-label">Blood Pressure</div><div class="hc-vital-value">${v.bp_systolic || '--'}/${v.bp_diastolic || '--'}</div></div>
            <div class="hc-vital"><div class="hc-vital-label">Heart Rate</div><div class="hc-vital-value">${v.heart_rate || '--'} bpm</div></div>
            <div class="hc-vital"><div class="hc-vital-label">Temperature</div><div class="hc-vital-value">${v.temperature || '--'}°F</div></div>
            <div class="hc-vital"><div class="hc-vital-label">SpO2</div><div class="hc-vital-value">${v.oxygen_saturation || '--'}%</div></div>
            <div class="hc-vital"><div class="hc-vital-label">Weight</div><div class="hc-vital-value">${v.weight || '--'} lbs</div></div>
            <div class="hc-vital"><div class="hc-vital-label">BMI</div><div class="hc-vital-value">${v.bmi || '--'}</div></div>
        </div>
        <div style="text-align:right;color:var(--hc-muted);font-size:.75rem;margin-top:.5rem;">Recorded: ${v.recorded_at?.slice(0,10) || 'N/A'}</div>`;
    } else { el.innerHTML = '<div style="color:var(--hc-muted);text-align:center;padding:1rem;">No vitals recorded yet</div>'; }
}

async function loadAudit() {
    const d = await hGet('audit_log', '&limit=10');
    const el = document.getElementById('audit-log');
    if (d.success && d.audit_log && d.audit_log.length > 0) {
        el.innerHTML = `<table class="hc-table"><thead><tr><th>Action</th><th>Resource</th><th>User</th><th>Time</th></tr></thead><tbody>${
            d.audit_log.map(a => `<tr><td>${a.action}</td><td>${a.resource_type} #${a.resource_id}</td><td>User #${a.client_id}</td><td style="color:var(--hc-muted)">${a.created_at?.slice(0,16).replace('T',' ') || ''}</td></tr>`).join('')
        }</tbody></table>`;
    } else { el.innerHTML = '<div style="color:var(--hc-muted);text-align:center;padding:1rem;">No audit entries</div>'; }
}

function showNewPatient() {
    const modal = document.createElement('div');
    modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.7);display:flex;align-items:center;justify-content:center;z-index:9999;';
    modal.innerHTML = `<div style="background:var(--hc-card);border:1px solid var(--hc-border);border-radius:1rem;padding:2rem;max-width:500px;width:90%;">
        <h2 style="margin:0 0 1rem;">New Patient</h2>
        <form onsubmit="return createPatient(this)">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:.75rem;">
                <input name="first_name" placeholder="First name" required style="padding:.5rem;border-radius:.4rem;border:1px solid var(--hc-border);background:var(--hc-bg);color:var(--hc-text);">
                <input name="last_name" placeholder="Last name" required style="padding:.5rem;border-radius:.4rem;border:1px solid var(--hc-border);background:var(--hc-bg);color:var(--hc-text);">
            </div>
            <input name="date_of_birth" type="date" placeholder="Date of Birth" style="width:100%;padding:.5rem;border-radius:.4rem;border:1px solid var(--hc-border);background:var(--hc-bg);color:var(--hc-text);margin-bottom:.75rem;box-sizing:border-box;">
            <input name="email" type="email" placeholder="Email" style="width:100%;padding:.5rem;border-radius:.4rem;border:1px solid var(--hc-border);background:var(--hc-bg);color:var(--hc-text);margin-bottom:.75rem;box-sizing:border-box;">
            <input name="phone" placeholder="Phone" style="width:100%;padding:.5rem;border-radius:.4rem;border:1px solid var(--hc-border);background:var(--hc-bg);color:var(--hc-text);margin-bottom:.75rem;box-sizing:border-box;">
            <div style="display:flex;gap:.75rem;justify-content:flex-end;">
                <button type="button" class="hc-btn" onclick="this.closest('div[style]').parentElement.remove()">Cancel</button>
                <button type="submit" class="hc-btn hc-btn-primary">Add Patient</button>
            </div>
        </form>
    </div>`;
    modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
    document.body.appendChild(modal);
}

async function createPatient(form) {
    const fd = new FormData(form);
    const body = Object.fromEntries(fd);
    try {
        const r = await fetch(`${API}?action=patient_create`, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json'}, body:JSON.stringify(body)});
        const d = await r.json();
        if (d.success) { form.closest('div[style*="fixed"]').remove(); refreshAll(); }
        else { alert(d.error || 'Failed to create patient'); }
    } catch(e) { alert('Network error'); }
    return false;
}

function refreshAll() { loadDashboard(); loadTodaysAppts(); loadPatients(); loadSOAP(); loadMeds(); loadLabs(); loadVitals(); loadAudit(); }
refreshAll();
