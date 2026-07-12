    // ── State ──
    const state = {
        currentStep: 1,
        role: '',
        companyName: '',
        companySize: '',
        useCases: [],
        selectedTemplate: '',
        agentName: '',
        channels: [],
        agentId: null
    };

    const API = '/api/onboarding.php';

    // ── Restore saved progress on load ──
    document.addEventListener('DOMContentLoaded', async () => {
        try {
            const res = await fetch(`${API}?action=status`, { credentials: 'include' });
            const data = await res.json();
            if (data.exists && !data.completed) {
                // Restore state
                if (data.role) {
                    state.role = data.role;
                    document.querySelectorAll('#roleCards .select-card').forEach(c => {
                        if (c.dataset.value === data.role) c.classList.add('selected');
                    });
                }
                if (data.company_name) {
                    state.companyName = data.company_name;
                    document.getElementById('companyName').value = data.company_name;
                }
                if (data.company_size) {
                    state.companySize = data.company_size;
                    document.querySelectorAll('#companySizeGroup .radio-pill').forEach(p => {
                        if (p.dataset.value === data.company_size) p.classList.add('selected');
                    });
                }
                if (data.use_cases && data.use_cases.length) {
                    state.useCases = data.use_cases;
                    document.querySelectorAll('#useCaseCards .select-card').forEach(c => {
                        if (data.use_cases.includes(c.dataset.value)) c.classList.add('selected');
                    });
                }
                if (data.first_agent_id) state.agentId = data.first_agent_id;
                // Navigate to saved step
                if (data.current_step > 1) {
                    goToStep(data.current_step);
                }
            } else if (data.completed) {
                window.location.href = '/dashboard';
            }
        } catch (e) {
            console.error('Failed to load onboarding status', e);
        }
    });

    // ── Step navigation ──
    function goToStep(n) {
        if (n < 1 || n > 5) return;
        state.currentStep = n;

        document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
        document.getElementById('step' + n).classList.add('active');

        // Update progress bar
        const pct = n * 20;
        document.getElementById('progressFill').style.width = pct + '%';

        // Update dots
        document.querySelectorAll('.step-dot').forEach(d => {
            const s = parseInt(d.dataset.step);
            d.classList.remove('active', 'completed');
            if (s === n) d.classList.add('active');
            else if (s < n) d.classList.add('completed');
        });

        // Trigger confetti on step 5
        if (n === 5) {
            populateSummary();
            setTimeout(launchConfetti, 300);
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // ── Step 1: Role selection ──
    function selectRole(el) {
        document.querySelectorAll('#roleCards .select-card').forEach(c => c.classList.remove('selected'));
        el.classList.add('selected');
        state.role = el.dataset.value;
    }

    function selectSize(el) {
        document.querySelectorAll('#companySizeGroup .radio-pill').forEach(p => p.classList.remove('selected'));
        el.classList.add('selected');
        state.companySize = el.dataset.value;
    }

    async function saveStep1() {
        state.companyName = document.getElementById('companyName').value.trim();
        const btn = event.currentTarget;
        btn.classList.add('loading');

        try {
            const res = await fetch(`${API}?action=save-profile`, {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    role: state.role,
                    company_name: state.companyName,
                    company_size: state.companySize
                })
            });
            const data = await res.json();
            if (data.success) {
                goToStep(2);
            } else {
                showToast(data.error || 'Failed to save', 'error');
            }
        } catch (e) {
            showToast('Network error', 'error');
        } finally {
            btn.classList.remove('loading');
        }
    }

    // ── Step 2: Use case toggle ──
    function toggleUseCase(el) {
        el.classList.toggle('selected');
        const val = el.dataset.value;
        if (el.classList.contains('selected')) {
            if (!state.useCases.includes(val)) state.useCases.push(val);
        } else {
            state.useCases = state.useCases.filter(v => v !== val);
        }
    }

    async function saveStep2() {
        const btn = event.currentTarget;
        btn.classList.add('loading');

        try {
            const res = await fetch(`${API}?action=save-use-cases`, {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ use_cases: state.useCases })
            });
            const data = await res.json();
            if (data.success) {
                goToStep(3);
            } else {
                showToast(data.error || 'Failed to save', 'error');
            }
        } catch (e) {
            showToast('Network error', 'error');
        } finally {
            btn.classList.remove('loading');
        }
    }

    // ── Step 3: Template selection ──
    function selectTemplate(el) {
        document.querySelectorAll('.template-card').forEach(c => c.classList.remove('selected'));
        el.classList.add('selected');
        state.selectedTemplate = el.dataset.template;

        // Show customize panel
        const panel = document.getElementById('agentCustomize');
        panel.classList.add('show');

        // Pre-fill agent name
        const nameMap = {
            customer_support: 'Support Agent',
            sales_agent: 'Sales Agent',
            knowledge_base: 'Knowledge Bot',
            voice_receptionist: 'Voice Receptionist'
        };
        document.getElementById('agentName').value = nameMap[state.selectedTemplate] || 'My Agent';
        document.getElementById('createAgentBtn').disabled = false;
    }

    async function saveStep3() {
        state.agentName = document.getElementById('agentName').value.trim();
        if (!state.agentName) {
            showToast('Please enter an agent name', 'error');
            return;
        }

        const btn = document.getElementById('createAgentBtn');
        btn.classList.add('loading');

        try {
            const res = await fetch(`${API}?action=create-first-agent`, {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    template: state.selectedTemplate,
                    agent_name: state.agentName
                })
            });
            const data = await res.json();
            if (data.success) {
                state.agentId = data.agent_id;
                showToast('Agent created!', 'success');
                setTimeout(() => goToStep(4), 600);
            } else {
                showToast(data.error || 'Failed to create agent', 'error');
            }
        } catch (e) {
            showToast('Network error', 'error');
        } finally {
            btn.classList.remove('loading');
        }
    }

    // ── Step 4: Channel selection ──
    function toggleChannel(el) {
        el.classList.toggle('selected');
        const val = el.dataset.channel;
        if (el.classList.contains('selected')) {
            if (!state.channels.includes(val)) state.channels.push(val);
        } else {
            state.channels = state.channels.filter(v => v !== val);
        }
    }

    async function saveStep4() {
        const btn = event.currentTarget;
        btn.classList.add('loading');

        try {
            const res = await fetch(`${API}?action=save-channels`, {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ channels: state.channels })
            });
            const data = await res.json();
            if (data.success) {
                await completeOnboarding();
                goToStep(5);
            } else {
                showToast(data.error || 'Failed to save', 'error');
            }
        } catch (e) {
            showToast('Network error', 'error');
        } finally {
            btn.classList.remove('loading');
        }
    }

    async function skipToComplete() {
        await completeOnboarding();
        goToStep(5);
    }

    async function completeOnboarding() {
        try {
            await fetch(`${API}?action=complete`, {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' }
            });
        } catch (e) {
            console.error('Failed to mark onboarding complete', e);
        }
    }

    // ── Step 5: Summary ──
    function populateSummary() {
        const roleLabels = {
            developer: 'Developer',
            business_owner: 'Business Owner',
            marketing: 'Marketing',
            customer_support: 'Customer Support',
            it_devops: 'IT / DevOps',
            other: 'Other'
        };
        const useCaseLabels = {
            customer_support: 'Customer Support',
            voice_agent: 'Voice Agent',
            tool_automation: 'Tool Automation',
            content_generation: 'Content Generation',
            data_analysis: 'Data Analysis',
            code_assistant: 'Code Assistant',
            lead_generation: 'Lead Generation',
            appointment_scheduling: 'Scheduling'
        };

        document.getElementById('sumRole').textContent = roleLabels[state.role] || '—';
        document.getElementById('sumCompany').textContent = state.companyName || '—';
        document.getElementById('sumUseCases').textContent = state.useCases.map(u => useCaseLabels[u] || u).join(', ') || '—';
        document.getElementById('sumAgent').textContent = state.agentName || 'Skipped';
        document.getElementById('sumChannels').textContent = state.channels.length ? state.channels.map(c => c.replace('_', ' ')).join(', ') : 'None yet';
    }

    function markDontShow() {
        // Already completed — no additional action needed
    }

    // ── Confetti ──
    function launchConfetti() {
        const colors = ['#6c5ce7', '#a29bfe', '#10b981', '#f59e0b', '#00D4FF', '#ef4444', '#ff6b6b', '#ffd93d'];
        for (let i = 0; i < 60; i++) {
            const piece = document.createElement('div');
            piece.className = 'confetti-piece';
            piece.style.left = Math.random() * 100 + 'vw';
            piece.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            piece.style.width = (Math.random() * 8 + 6) + 'px';
            piece.style.height = (Math.random() * 8 + 6) + 'px';
            piece.style.animation = `confettiFall ${Math.random() * 2 + 2}s ease-out ${Math.random() * 0.5}s forwards`;
            document.body.appendChild(piece);
            setTimeout(() => piece.remove(), 4000);
        }
    }

    // ── Toast ──
    function showToast(msg, type = '') {
        if (window.GDSToast) return GDSToast.show(msg, { type: (type || 'info') === 'error' ? 'danger' : (type || 'info') });
    }
