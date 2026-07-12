        // Load dashboard data
        // ── Date helper functions ──
        function fmtDashDate(dateStr) {
            if (!dateStr || dateStr === '0000-00-00' || dateStr === 'Nov 30, -0001' || dateStr === '-' || dateStr.includes('-0001')) return '—';
            try {
                const d = new Date(dateStr);
                if (isNaN(d.getTime()) || d.getFullYear() < 2000) return '—';
                return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            } catch { return '—'; }
        }
        function isDueSoon(dateStr) {
            if (!dateStr || dateStr === '0000-00-00' || dateStr.includes('-0001')) return false;
            try {
                const d = new Date(dateStr);
                if (isNaN(d.getTime())) return false;
                const days = (d - new Date()) / 86400000;
                return days >= 0 && days <= 7;
            } catch { return false; }
        }
        async function loadDashboard() {
            try {
                const response = await fetch('/api/client.php?action=dashboard', { credentials: 'include' });
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('statServices').textContent = data.stats.active_services;
                    document.getElementById('statDomains').textContent = data.stats.active_domains;
                    document.getElementById('statInvoices').textContent = data.stats.unpaid_invoices;
                    document.getElementById('statTickets').textContent = data.stats.open_tickets;
                    document.getElementById('statDue').textContent = '$' + data.stats.total_due;
                }
            } catch (err) {
                console.error('Dashboard load error:', err);
            }
        }
        
        // Load services
        // ── Premium Services Renderer ──
        const svcGroupIcons = {
            'Hosting': 'fas fa-server', 'AI Development Platform': 'fas fa-code',
            'AI Servers': 'fas fa-microchip', 'Phone Numbers & SIP': 'fas fa-phone-volume',
            'Training & Consultation': 'fas fa-graduation-cap', 'AI Voice Agents': 'fas fa-robot',
            'AI Call Center & Telemarketing': 'fas fa-headset', 'AI SMS & Chat Agents': 'fas fa-comments',
            'AI Document & Fax Services': 'fas fa-file-alt', 'AI Office Suite': 'fas fa-briefcase',
            'Industry Solutions': 'fas fa-building', 'Voice Add-Ons & Minutes': 'fas fa-clock',
            'SSL Certificates': 'fas fa-shield-alt', 'Token Packs': 'fas fa-coins',
            'Web Design': 'fas fa-palette', 'Team Plans': 'fas fa-users',
            'Reseller Plans': 'fas fa-handshake', 'API Access': 'fas fa-plug',
            'Dedicated Server': 'fas fa-database', 'AI Server Support': 'fas fa-life-ring'
        };
        const svcGroupColors = {
            'Hosting': '#00a8ff', 'AI Development Platform': '#7c3aed',
            'AI Servers': '#06b6d4', 'Phone Numbers & SIP': '#22c55e',
            'Training & Consultation': '#f59e0b', 'AI Voice Agents': '#ec4899',
            'AI Call Center & Telemarketing': '#ef4444', 'AI SMS & Chat Agents': '#14b8a6',
            'AI Document & Fax Services': '#8b5cf6', 'AI Office Suite': '#3b82f6',
            'Industry Solutions': '#f97316', 'Voice Add-Ons & Minutes': '#a855f7',
            'SSL Certificates': '#10b981', 'Token Packs': '#eab308', 'Web Design': '#e879f9'
        };

        function svcFmtDate(d) {
            if (!d || d === '0000-00-00' || d.includes('-0001')) return '—';
            try { const dt = new Date(d); return isNaN(dt) || dt.getFullYear() < 2000 ? '—' : dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); } catch { return '—'; }
        }
        function svcIsDueSoon(d) {
            if (!d || d === '0000-00-00') return false;
            try { const dt = new Date(d); const days = (dt - new Date()) / 86400000; return days >= 0 && days <= 7; } catch { return false; }
        }
        function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

        let svcCurrentView = 'cards';
        let svcCurrentFilter = 'all';

        function svcApplyFilter(filter) {
            svcCurrentFilter = filter;
            document.querySelectorAll('.svc-filter').forEach(b => b.classList.toggle('active', b.dataset.filter === filter));
            document.querySelectorAll('.svc-filterable').forEach(el => {
                el.style.display = (filter === 'all' || el.dataset.status === filter) ? '' : 'none';
            });
            document.querySelectorAll('.svc-group').forEach(g => {
                const vis = g.querySelectorAll('.svc-filterable:not([style*="display: none"])').length;
                g.style.display = vis ? '' : 'none';
            });
        }
        function svcToggleView(view) {
            svcCurrentView = view;
            document.querySelectorAll('.svc-view-toggle').forEach(b => b.classList.toggle('active', b.dataset.view === view));
            document.getElementById('svcCardsView').style.display = view === 'cards' ? '' : 'none';
            document.getElementById('svcTableView').style.display = view === 'table' ? '' : 'none';
        }

        async function svcSsoLogin(serviceId, btn) {
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.style.pointerEvents = 'none';
            try {
                const res = await fetch('/pay/api/service-api.php?action=sso&id=' + encodeURIComponent(serviceId), { credentials: 'same-origin' });
                const data = await res.json();
                if (data.success && data.url) window.open(data.url, '_blank');
                else alert(data.error || 'SSO login unavailable');
            } catch { alert('Network error'); }
            finally { btn.innerHTML = orig; btn.style.pointerEvents = ''; }
        }

        async function loadServices() {
            try {
                const response = await fetch('/api/client.php?action=services', { credentials: 'include' });
                const data = await response.json();

                document.getElementById('servicesLoading').style.display = 'none';
                const content = document.getElementById('servicesContent');
                content.style.display = 'block';

                if (!data.success || !data.services || data.services.length === 0) {
                    document.getElementById('svcStatsStrip').style.display = 'none';
                    document.getElementById('svcFilterBar').style.display = 'none';
                    document.getElementById('svcCardsView').innerHTML = `
                        <div class="svc-empty">
                            <div class="svc-empty-icon"><i class="fas fa-rocket"></i></div>
                            <h2>Launch Your First Service</h2>
                            <p>Browse AI agents, hosting plans, phone numbers, and more.</p>
                            <a href="/store" class="btn btn-primary" style="margin-top: 16px;"><i class="fas fa-store"></i> Browse Products</a>
                        </div>`;
                    return;
                }

                const svcs = data.services;
                // Count statuses
                const counts = { all: svcs.length };
                svcs.forEach(s => { const st = s.status.toLowerCase(); counts[st] = (counts[st] || 0) + 1; });

                // Stats strip
                document.getElementById('svcStatsStrip').innerHTML = `
                    <div class="svc-stat"><span class="svc-stat-dot active"></span><span class="svc-stat-value">${counts.active || 0}</span><span class="svc-stat-label">Active</span></div>
                    <div class="svc-stat"><span class="svc-stat-dot pending"></span><span class="svc-stat-value">${counts.pending || 0}</span><span class="svc-stat-label">Pending</span></div>
                    <div class="svc-stat"><span class="svc-stat-dot suspended"></span><span class="svc-stat-value">${counts.suspended || 0}</span><span class="svc-stat-label">Suspended</span></div>
                    <div class="svc-stat"><span class="svc-stat-dot cancelled"></span><span class="svc-stat-value">${(counts.cancelled || 0) + (counts.terminated || 0)}</span><span class="svc-stat-label">Cancelled</span></div>`;

                // Filter bar
                const filterStatuses = [
                    { key: 'all', label: 'All', icon: 'fa-th-large' },
                    { key: 'active', label: 'Active', icon: 'fa-check-circle' },
                    { key: 'pending', label: 'Pending', icon: 'fa-clock' },
                    { key: 'suspended', label: 'Suspended', icon: 'fa-pause-circle' },
                    { key: 'cancelled', label: 'Cancelled', icon: 'fa-times-circle' },
                    { key: 'terminated', label: 'Terminated', icon: 'fa-ban' }
                ];
                const filterBar = document.getElementById('svcFilterBar');
                filterBar.style.display = 'flex';
                filterBar.innerHTML = filterStatuses
                    .filter(f => f.key === 'all' || (counts[f.key] || 0) > 0)
                    .map(f => `<button class="svc-filter${f.key === 'all' ? ' active' : ''}" data-filter="${f.key}" onclick="svcApplyFilter('${f.key}')"><i class="fas ${f.icon}"></i> ${f.label} <span class="svc-filter-count">${f.key === 'all' ? counts.all : counts[f.key]}</span></button>`)
                    .join('') +
                    `<div class="svc-filter-right">
                        <button class="svc-view-toggle active" data-view="cards" onclick="svcToggleView('cards')" title="Card view"><i class="fas fa-th-large"></i></button>
                        <button class="svc-view-toggle" data-view="table" onclick="svcToggleView('table')" title="Table view"><i class="fas fa-list"></i></button>
                    </div>`;

                // Group by product group
                const grouped = {};
                svcs.forEach(s => {
                    const g = s.group || 'Other';
                    if (!grouped[g]) grouped[g] = [];
                    grouped[g].push(s);
                });

                // Card view
                let cardsHTML = '';
                for (const [groupName, groupSvcs] of Object.entries(grouped)) {
                    const gIcon = svcGroupIcons[groupName] || 'fas fa-cube';
                    const gColor = svcGroupColors[groupName] || '#00a8ff';
                    cardsHTML += `<div class="svc-group" data-group="${esc(groupName)}">
                        <div class="svc-group-header">
                            <div class="svc-group-icon" style="background: ${gColor}20; color: ${gColor};"><i class="${gIcon}"></i></div>
                            <div><h2 class="svc-group-title">${esc(groupName)}</h2><span class="svc-group-count">${groupSvcs.length} service${groupSvcs.length !== 1 ? 's' : ''}</span></div>
                        </div>
                        <div class="svc-group-grid">`;

                    groupSvcs.forEach((s, idx) => {
                        const status = s.status.toLowerCase();
                        const isActive = status === 'active';
                        const isPending = status === 'pending';
                        const isHosting = s.product_type === 'hosting' || s.server_module === 'directadmin';
                        const dueSoon = isActive && svcIsDueSoon(s.next_due);
                        const amtRaw = s.amount_raw || 0;

                        cardsHTML += `<div class="svc-card svc-filterable" data-status="${status}" style="--card-accent: ${gColor}; animation-delay: ${idx * 0.06}s;">
                            <div class="svc-card-accent"></div>
                            <div class="svc-card-body">
                                <div class="svc-card-top">
                                    <div class="svc-card-product">
                                        <div class="svc-product-icon" style="background: ${gColor}15; color: ${gColor};"><i class="${gIcon}"></i></div>
                                        <div>
                                            <h3 class="svc-card-title">${esc(s.product)}</h3>
                                            ${s.domain ? `<span class="svc-card-domain"><i class="fas fa-link"></i> ${esc(s.domain)}</span>` : ''}
                                        </div>
                                    </div>
                                    <span class="svc-badge svc-badge-${status}"><span class="svc-badge-dot"></span>${esc(s.status)}</span>
                                </div>
                                <div class="svc-card-info">
                                    <div class="svc-info-item"><span class="svc-info-label"><i class="far fa-calendar"></i> Registered</span><span class="svc-info-value">${svcFmtDate(s.registered)}</span></div>
                                    <div class="svc-info-item"><span class="svc-info-label"><i class="fas fa-sync-alt"></i> Billing</span><span class="svc-info-value">${esc(s.cycle || 'N/A')}</span></div>
                                    <div class="svc-info-item"><span class="svc-info-label"><i class="fas fa-dollar-sign"></i> Amount</span><span class="svc-info-value">${amtRaw > 0 ? esc(s.amount) : '<span class="svc-free">Free</span>'}</span></div>
                                    <div class="svc-info-item"><span class="svc-info-label"><i class="fas fa-hourglass-half"></i> Next Due</span><span class="svc-info-value ${dueSoon ? 'svc-due-soon' : ''}">${svcFmtDate(s.next_due)}</span></div>
                                </div>
                                <div class="svc-card-actions">
                                    <a href="/pay/account/service.php?id=${s.id}" class="btn btn-sm btn-primary svc-manage-btn"><i class="fas fa-cog"></i> Manage</a>
                                    ${isActive ? `<div class="svc-quick-actions">
                                        ${isHosting ? `<a href="#" onclick="svcSsoLogin(${s.id}, this); return false;" class="svc-action-link svc-sso-btn" title="Login to Panel"><i class="fas fa-sign-in-alt"></i></a>` : ''}
                                        <a href="/submit-ticket?service=${s.id}" class="svc-action-link" title="Get Support"><i class="fas fa-headset"></i></a>
                                        ${s.domain ? `<a href="https://${esc(s.domain)}" target="_blank" rel="noopener" class="svc-action-link" title="Visit Site"><i class="fas fa-external-link-alt"></i></a>` : ''}
                                    </div>` : ''}
                                    ${isPending ? '<span class="svc-pending-note"><i class="fas fa-info-circle"></i> Awaiting activation</span>' : ''}
                                </div>
                            </div>
                        </div>`;
                    });

                    cardsHTML += '</div></div>';
                }
                document.getElementById('svcCardsView').innerHTML = cardsHTML;

                // Table view
                let tableHTML = `<table class="svc-full-table"><thead><tr>
                    <th>Product / Service</th><th>Group</th><th>Domain</th>
                    <th>Cycle</th><th style="text-align:right;">Amount</th>
                    <th>Registered</th><th>Next Due</th><th>Status</th><th></th>
                </tr></thead><tbody>`;
                svcs.forEach(s => {
                    const status = s.status.toLowerCase();
                    const gColor = svcGroupColors[s.group] || '#00a8ff';
                    const isHosting = s.product_type === 'hosting' || s.server_module === 'directadmin';
                    const amtRaw = s.amount_raw || 0;
                    tableHTML += `<tr class="svc-filterable" data-status="${status}">
                        <td><div class="svc-tbl-product"><span class="svc-tbl-dot" style="background:${gColor};"></span><strong>${esc(s.product)}</strong></div></td>
                        <td><span class="svc-tbl-group">${esc(s.group || '—')}</span></td>
                        <td>${s.domain ? esc(s.domain) : '—'}</td>
                        <td>${esc(s.cycle || 'N/A')}</td>
                        <td style="text-align:right;">${amtRaw > 0 ? esc(s.amount) : '<span style="color:var(--success)">Free</span>'}</td>
                        <td>${svcFmtDate(s.registered)}</td>
                        <td>${svcFmtDate(s.next_due)}</td>
                        <td><span class="svc-badge svc-badge-${status}"><span class="svc-badge-dot"></span>${esc(s.status)}</span></td>
                        <td class="svc-tbl-actions">
                            ${status === 'active' && isHosting ? `<a href="#" onclick="svcSsoLogin(${s.id}, this); return false;" class="btn btn-sm btn-outline" title="Login to Panel"><i class="fas fa-sign-in-alt"></i></a>` : ''}
                            <a href="/pay/account/service.php?id=${s.id}" class="btn btn-sm btn-outline"><i class="fas fa-cog"></i></a>
                        </td>
                    </tr>`;
                });
                tableHTML += '</tbody></table>';
                document.getElementById('svcTableView').innerHTML = tableHTML;

            } catch (err) {
                console.error('Services load error:', err);
                document.getElementById('servicesLoading').style.display = 'none';
                document.getElementById('servicesContent').style.display = 'block';
                document.getElementById('svcCardsView').innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Could not load services</p></div>';
            }
        }
        
        // Load domains
        async function loadDomains() {
            try {
                const response = await fetch('/api/client.php?action=domains', { credentials: 'include' });
                const data = await response.json();
                
                document.getElementById('domainsLoading').style.display = 'none';
                const content = document.getElementById('domainsContent');
                content.style.display = 'block';
                
                if (data.success && data.domains.length > 0) {
                    content.innerHTML = `
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Domain</th>
                                    <th>Status</th>
                                    <th>Expires</th>
                                    <th>Renewal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.domains.map(d => {
                                    const exp = fmtDashDate(d.expires);
                                    return `
                                    <tr>
                                        <td><strong><i class="fas fa-globe" style="color:var(--success);margin-right:6px;font-size:12px;"></i>${d.domain}</strong></td>
                                        <td><span class="badge badge-${d.status_class}">${d.status}</span></td>
                                        <td${d.expiring_soon ? ' style="color:var(--warning);font-weight:600;"' : ''}>${exp}${d.expiring_soon ? ' <i class="fas fa-exclamation-triangle" style="font-size:10px;"></i>' : ''}</td>
                                        <td>${d.renewal_price}/yr</td>
                                        <td><a href="/domains" class="btn btn-outline btn-sm" style="font-size:11px;">Manage</a></td>
                                    </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                    `;
                } else {
                    content.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-globe"></i>
                            <p>No domains yet</p>
                            <a href="/#domains" class="btn btn-primary" style="margin-top: 16px;">Register a Domain</a>
                        </div>
                    `;
                }
            } catch (err) {
                console.error('Domains load error:', err);
            }
        }
        
        // Load invoices
        async function loadInvoices() {
            try {
                const response = await fetch('/api/client.php?action=invoices', { credentials: 'include' });
                const data = await response.json();
                
                document.getElementById('invoicesLoading').style.display = 'none';
                const content = document.getElementById('invoicesContent');
                content.style.display = 'block';
                
                if (data.success && data.invoices.length > 0) {
                    content.innerHTML = `
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Date</th>
                                    <th>Due Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.invoices.map(i => `
                                    <tr>
                                        <td><strong>#${i.number}</strong></td>
                                        <td>${fmtDashDate(i.date)}</td>
                                        <td>${fmtDashDate(i.due_date)}</td>
                                        <td><strong style="color:var(--text);">${i.total}</strong></td>
                                        <td><span class="badge badge-${i.status_class}">${i.status}</span></td>
                                        <td>
                                            ${i.status === 'Unpaid' 
                                                ? `<a href="${i.pay_url}" class="btn btn-primary btn-sm" style="font-size:11px;" target="_blank"><i class="fas fa-credit-card" style="font-size:10px;"></i> Pay Now</a>` 
                                                : `<a href="${i.pay_url}" class="btn btn-outline btn-sm" style="font-size:11px;" target="_blank">View</a>`}
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                        <div style="text-align:right;margin-top:12px;">
                            <a href="/invoices" style="color:var(--cyan);font-size:13px;text-decoration:none;">View all invoices →</a>
                        </div>
                    `;
                } else {
                    content.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-file-invoice"></i>
                            <p>No invoices yet</p>
                        </div>
                    `;
                }
            } catch (err) {
                console.error('Invoices load error:', err);
            }
        }

        // ── PIN Management ──────────────────────────────────────────

        // Auto-advance PIN digits
        document.querySelectorAll('.pin-digit').forEach((input, index, all) => {
            input.addEventListener('input', (e) => {
                const val = e.target.value.replace(/\D/g, '');
                e.target.value = val ? val[0] : '';
                if (val && index < all.length - 1) all[index + 1].focus();
            });
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) all[index - 1].focus();
            });
        });

        async function loadPinStatus() {
            try {
                const res = await fetch('/api/support-pin.php', {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'status' })
                });
                const data = await res.json();
                const badge = document.getElementById('pinStatusBadge');
                const removeBtn = document.getElementById('removePinBtn');
                const setDate = document.getElementById('pinSetDate');

                if (data.has_pin) {
                    badge.className = 'pin-status-badge set';
                    badge.innerHTML = '<i class="fas fa-circle-check"></i> PIN Active';
                    removeBtn.style.display = 'inline-flex';
                    if (data.set_at) {
                        setDate.textContent = 'PIN last updated: ' + new Date(data.set_at).toLocaleDateString();
                    }
                } else {
                    badge.className = 'pin-status-badge not-set';
                    badge.innerHTML = '<i class="fas fa-circle-xmark"></i> Not Set';
                    removeBtn.style.display = 'none';
                    setDate.textContent = '';
                }
            } catch (err) {
                console.error('PIN status error:', err);
            }
        }

        async function savePin() {
            const pin = ['pin1','pin2','pin3','pin4'].map(id => document.getElementById(id).value).join('');
            const msg = document.getElementById('pinMessage');

            if (pin.length !== 4 || !/^\d{4}$/.test(pin)) {
                showPinMessage('Please enter all 4 digits.', 'error');
                return;
            }

            try {
                const res = await fetch('/api/support-pin.php', {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'set', pin })
                });
                const data = await res.json();
                showPinMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    ['pin1','pin2','pin3','pin4'].forEach(id => document.getElementById(id).value = '');
                    loadPinStatus();
                }
            } catch (err) {
                showPinMessage('Something went wrong. Please try again.', 'error');
            }
        }

        async function removePin() {
            if (!confirm('Remove your support PIN? Alfred will use your phone number to verify you instead.')) return;
            try {
                const res = await fetch('/api/support-pin.php', {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'remove' })
                });
                const data = await res.json();
                showPinMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) loadPinStatus();
            } catch (err) {
                showPinMessage('Something went wrong. Please try again.', 'error');
            }
        }

        function showPinMessage(text, type) {
            const msg = document.getElementById('pinMessage');
            msg.textContent = text;
            msg.className = 'pin-message ' + type;
            setTimeout(() => { msg.className = 'pin-message'; }, 5000);
        }
        
        // ── Load Support Tickets ──────────────────────────────────
        async function loadTickets() {
            try {
                const response = await fetch('/api/client.php?action=tickets', { credentials: 'include' });
                const data = await response.json();

                document.getElementById('ticketsLoading').style.display = 'none';
                const content = document.getElementById('ticketsContent');
                content.style.display = 'block';

                if (data.success && data.tickets.length > 0) {
                    content.innerHTML = `
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Ticket #</th>
                                    <th>Subject</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Last Reply</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.tickets.map(t => `
                                    <tr>
                                        <td><strong>#${t.ticket_id}</strong></td>
                                        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${t.subject}</td>
                                        <td><span style="font-size:12px;">${t.department || '—'}</span></td>
                                        <td><span class="badge badge-${t.status_class}">${t.status}</span></td>
                                        <td><span style="font-size:12px;">${t.priority}</span></td>
                                        <td style="font-size:12px;">${fmtDashDate(t.last_reply)}</td>
                                        <td><a href="${t.view_url}" class="btn btn-outline btn-sm" style="font-size:11px;">View</a></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                        <div style="text-align:right;margin-top:12px;">
                            <a href="/tickets" style="color:var(--cyan);font-size:13px;text-decoration:none;">View all tickets →</a>
                        </div>
                    `;
                } else {
                    content.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-ticket"></i>
                            <p>No support tickets</p>
                            <a href="/submit-ticket" class="btn btn-primary" style="margin-top: 16px;">Open a Ticket</a>
                        </div>
                    `;
                }
            } catch (err) {
                console.error('Tickets load error:', err);
            }
        }

        // ── Load Credit Balance ──────────────────────────────────
        async function loadCredit() {
            try {
                const response = await fetch('/api/client.php?action=credit', { credentials: 'include' });
                const data = await response.json();

                document.getElementById('creditLoading').style.display = 'none';
                const content = document.getElementById('creditContent');
                content.style.display = 'block';

                if (data.success) {
                    let historyHtml = '';
                    if (data.history.length > 0) {
                        historyHtml = `
                            <div class="credit-history">
                                <h4 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 12px;">Recent Transactions</h4>
                                ${data.history.map(h => `
                                    <div class="credit-history-item">
                                        <div>
                                            <div style="font-weight: 500;">${h.description}</div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);">${h.date}</div>
                                        </div>
                                        <span class="credit-amt ${h.amount.startsWith('+') ? 'positive' : 'negative'}">${h.amount}</span>
                                    </div>
                                `).join('')}
                            </div>
                        `;
                    }

                    content.innerHTML = `
                        <div class="credit-row">
                            <div class="credit-balance-card">
                                <div class="credit-amount">${data.balance}</div>
                                <div class="credit-label">Account Credit</div>
                                <a href="/profile#billing" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Funds</a>
                            </div>
                            ${historyHtml || '<div class="credit-history" style="display:flex;align-items:center;justify-content:center;"><p style="color:var(--text-muted);">No credit transactions yet</p></div>'}
                        </div>
                    `;
                }
            } catch (err) {
                console.error('Credit load error:', err);
                document.getElementById('creditLoading').style.display = 'none';
                document.getElementById('creditContent').style.display = 'block';
                document.getElementById('creditContent').innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Failed to load credit balance</p></div>';
            }
        }

        // ── Load Payment Methods ──────────────────────────────────
        async function loadPaymentMethods() {
            try {
                const response = await fetch('/api/client.php?action=payment_methods', { credentials: 'include' });
                const data = await response.json();

                document.getElementById('paymentLoading').style.display = 'none';
                const content = document.getElementById('paymentContent');
                content.style.display = 'block';

                if (data.success && data.payment_methods.length > 0) {
                    content.innerHTML = data.payment_methods.map(pm => `
                        <div class="payment-method-item">
                            <div class="payment-method-icon"><i class="fas ${pm.gateway_icon}"></i></div>
                            <div class="payment-method-info">
                                <div class="pm-desc">${pm.description || 'Payment Method'}</div>
                                <div class="pm-type">${pm.gateway}</div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    content.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-credit-card"></i>
                            <p>No saved payment methods</p>
                            <a href="/payment-methods" class="btn btn-primary" style="margin-top: 16px;">Add Payment Method</a>
                        </div>
                    `;
                }
            } catch (err) {
                console.error('Payment methods load error:', err);
                document.getElementById('paymentLoading').style.display = 'none';
                document.getElementById('paymentContent').style.display = 'block';
                document.getElementById('paymentContent').innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Failed to load payment methods</p></div>';
            }
        }

        // ── Load Email History ──────────────────────────────────
        async function loadEmails() {
            try {
                const response = await fetch('/api/client.php?action=emails', { credentials: 'include' });
                const data = await response.json();

                document.getElementById('emailsLoading').style.display = 'none';
                const content = document.getElementById('emailsContent');
                content.style.display = 'block';

                if (data.success && data.emails.length > 0) {
                    content.innerHTML = data.emails.map(e => `
                        <a href="/view-email?id=${e.id}" class="email-row" style="text-decoration:none;color:inherit;">
                            <div class="email-icon"><i class="fas fa-envelope"></i></div>
                            <div class="email-details">
                                <div class="email-subject">${e.subject}</div>
                                <div class="email-date">${new Date(e.date).toLocaleDateString('en-US', {year:'numeric',month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'})}</div>
                            </div>
                        </a>
                    `).join('');
                } else {
                    content.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-envelope"></i>
                            <p>No emails yet</p>
                        </div>
                    `;
                }
            } catch (err) {
                console.error('Emails load error:', err);
            }
        }

        // ── Load Quotes ──────────────────────────────────
        async function loadQuotes() {
            try {
                const response = await fetch('/api/client.php?action=quotes', { credentials: 'include' });
                const data = await response.json();

                document.getElementById('quotesLoading').style.display = 'none';
                const content = document.getElementById('quotesContent');
                content.style.display = 'block';

                if (data.success && data.quotes.length > 0) {
                    content.innerHTML = `
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Quote #</th>
                                    <th>Subject</th>
                                    <th>Stage</th>
                                    <th>Created</th>
                                    <th>Valid Until</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.quotes.map(q => `
                                    <tr>
                                        <td><strong>#${q.id}</strong></td>
                                        <td>${q.subject}</td>
                                        <td><span class="badge badge-${q.stage_class}">${q.stage}</span></td>
                                        <td>${q.created}</td>
                                        <td>${q.valid_until}</td>
                                        <td>${q.total}</td>
                                        <td><a href="${q.view_url}" class="btn btn-outline btn-sm">View</a></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                } else {
                    content.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-file-contract"></i>
                            <p>No quotes</p>
                        </div>
                    `;
                }
            } catch (err) {
                console.error('Quotes load error:', err);
            }
        }

        // ── Load Profile ──────────────────────────────────
        async function loadProfile() {
            try {
                const response = await fetch('/api/client.php?action=profile', { credentials: 'include' });
                const data = await response.json();

                document.getElementById('profileLoading').style.display = 'none';
                const content = document.getElementById('profileContent');
                content.style.display = 'block';

                if (data.success) {
                    const p = data.profile;
                    const countries = {US:'United States',CA:'Canada',GB:'United Kingdom',AU:'Australia',DE:'Germany',FR:'France',NL:'Netherlands',IN:'India',BR:'Brazil',MX:'Mexico',JP:'Japan',KR:'South Korea',SG:'Singapore',AE:'UAE',ZA:'South Africa'};
                    const countryOptions = Object.entries(countries).map(([code, name]) =>
                        `<option value="${code}" ${code === p.address.country ? 'selected' : ''}>${name}</option>`).join('');

                    content.innerHTML = `
                        <div class="profile-member-since"><i class="fas fa-calendar-check"></i> Member since ${p.member_since}</div>
                        <form id="profileForm" onsubmit="saveProfile(event)">
                            <div class="profile-form-grid">
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input type="text" name="firstname" value="${p.firstname}" required>
                                </div>
                                <div class="form-group">
                                    <label>Last Name</label>
                                    <input type="text" name="lastname" value="${p.lastname}" required>
                                </div>
                                <div class="form-group">
                                    <label>Company</label>
                                    <input type="text" name="companyname" value="${p.company || ''}">
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" value="${p.email}" disabled style="opacity:0.6;cursor:not-allowed;">
                                    <small style="font-size:0.7rem;color:var(--text-muted);">Contact support to change email</small>
                                </div>
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="text" name="phonenumber" value="${p.phone || ''}">
                                </div>
                                <div class="form-group">
                                    <label>Address</label>
                                    <input type="text" name="address1" value="${p.address.line1 || ''}">
                                </div>
                                <div class="form-group">
                                    <label>Address Line 2</label>
                                    <input type="text" name="address2" value="${p.address.line2 || ''}">
                                </div>
                                <div class="form-group">
                                    <label>City</label>
                                    <input type="text" name="city" value="${p.address.city || ''}">
                                </div>
                                <div class="form-group">
                                    <label>State / Province</label>
                                    <input type="text" name="state" value="${p.address.state || ''}">
                                </div>
                                <div class="form-group">
                                    <label>Postal Code</label>
                                    <input type="text" name="postcode" value="${p.address.postcode || ''}">
                                </div>
                                <div class="form-group">
                                    <label>Country</label>
                                    <select name="country">${countryOptions}</select>
                                </div>
                                <div class="form-actions">
                                    <div id="profileMsg" style="font-size:0.85rem;padding:6px 14px;border-radius:8px;display:none;"></div>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                                </div>
                            </div>
                        </form>
                    `;
                }
            } catch (err) {
                console.error('Profile load error:', err);
            }
        }

        async function saveProfile(e) {
            e.preventDefault();
            const form = document.getElementById('profileForm');
            const formData = new FormData(form);
            const payload = {};
            formData.forEach((v, k) => { payload[k] = v; });

            try {
                const res = await fetch('/api/client.php?action=update_profile', {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.AW_CSRF_TOKEN || '' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                const msg = document.getElementById('profileMsg');
                msg.style.display = 'inline-block';
                if (data.success) {
                    msg.textContent = '✓ Profile saved';
                    msg.style.background = 'rgba(16,185,129,0.15)';
                    msg.style.color = '#10b981';
                } else {
                    msg.textContent = data.error || 'Error saving profile';
                    msg.style.background = 'rgba(239,68,68,0.15)';
                    msg.style.color = '#ef4444';
                }
                setTimeout(() => { msg.style.display = 'none'; }, 4000);
            } catch (err) {
                console.error('Profile save error:', err);
            }
        }

        // Smooth scroll
        document.querySelectorAll('.sidebar-nav a[href^="#"]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                document.querySelectorAll('.sidebar-nav a').forEach(a => a.classList.remove('active'));
                this.classList.add('active');
                // Close sidebar on mobile after navigation
                closeSidebar();
            });
        });

        // ── Mobile Sidebar Toggle ──
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const dashSidebar = document.getElementById('dashSidebar');

        function openSidebar() {
            dashSidebar.classList.add('open');
            sidebarOverlay.classList.add('active');
            sidebarToggle.innerHTML = '<i class="fas fa-times"></i>';
            document.body.style.overflow = 'hidden';
        }
        function closeSidebar() {
            dashSidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
            sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
            document.body.style.overflow = '';
        }
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                dashSidebar.classList.contains('open') ? closeSidebar() : openSidebar();
            });
        }
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', closeSidebar);
        }
        
        // Load all data
        loadDashboard();
        loadServices().then(() => {
            // Auto-scroll to #services if arriving from /services redirect
            if (window.location.hash) {
                const el = document.querySelector(window.location.hash);
                if (el) setTimeout(() => el.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
            }
        });
        loadDomains();
        loadInvoices();
        loadTickets();
        loadCredit();
        loadPaymentMethods();
        loadEmails();
        loadQuotes();
        loadProfile();
        loadPinStatus();
