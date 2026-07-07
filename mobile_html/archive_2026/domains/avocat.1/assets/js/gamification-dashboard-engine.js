const API = '/api/gamification.php';

async function gGet(action, params = '') {
    try {
        const r = await fetch(`${API}?action=${action}${params}`, { credentials: 'same-origin' });
        return await r.json();
    } catch (e) { console.warn('API error:', action, e); return { success: false }; }
}

async function loadProfile() {
    const d = await gGet('profile');
    if (d.success && d.profile) {
        const p = d.profile;
        document.getElementById('kpi-xp').textContent = Number(p.total_xp).toLocaleString();
        document.getElementById('kpi-level').textContent = p.level;
        document.getElementById('kpi-achievements').textContent = p.achievements_earned;
        const next = Math.floor(100 * Math.pow(p.level, 1.5));
        const pct = next > 0 ? Math.min(100, (p.total_xp / next) * 100) : 0;
        document.getElementById('xp-label').textContent = `Level ${p.level}`;
        document.getElementById('xp-next').textContent = `Next: ${next.toLocaleString()} XP`;
        document.getElementById('xp-bar').style.width = pct + '%';
        document.getElementById('level-progress-wrap').style.display = 'block';
    }
}

async function loadStreak() {
    const d = await gGet('check_streak');
    const el = document.getElementById('streak-display');
    if (d.success) {
        document.getElementById('kpi-streak').textContent = (d.current_streak || 0) + ' days';
        const days = ['M','T','W','T','F','S','S'];
        const streak = d.current_streak || 0;
        el.innerHTML = `<div class="gm-streak">${days.map((day, i) => 
            `<div class="gm-streak-day ${i < Math.min(streak, 7) ? 'active' : 'inactive'}">${day}</div>`
        ).join('')}</div>
        <div style="text-align:center;color:var(--gm-muted);font-size:.85rem;">Longest: ${d.longest_streak || 0} days</div>`;
    } else { el.innerHTML = '<div style="color:var(--gm-muted);text-align:center;padding:1rem;">Start your streak today!</div>'; }
}

async function loadLeaderboard() {
    const d = await gGet('leaderboard', '&period=weekly&limit=10');
    const el = document.getElementById('leaderboard');
    if (d.success && d.leaderboard && d.leaderboard.length > 0) {
        const medals = ['🥇','🥈','🥉'];
        el.innerHTML = d.leaderboard.map((u, i) => `<div class="gm-lb-row">
            <div class="gm-lb-rank">${medals[i] || (i+1)}</div>
            <div class="gm-lb-name">${u.username || 'User #' + u.client_id}</div>
            <div class="gm-lb-xp">${Number(u.total_xp || u.xp_earned).toLocaleString()} XP</div>
        </div>`).join('');
        document.getElementById('kpi-rank').textContent = '#' + (d.leaderboard.findIndex(u => u.is_current_user) + 1 || '--');
    } else { el.innerHTML = '<div style="color:var(--gm-muted);text-align:center;padding:1rem;">No rankings yet</div>'; }
}

async function loadAchievements() {
    const d = await gGet('my_achievements');
    const el = document.getElementById('achievements');
    if (d.success && d.achievements && d.achievements.length > 0) {
        el.innerHTML = d.achievements.slice(0, 12).map(a => 
            `<span class="gm-badge ${a.earned_at ? 'earned' : ''}">${a.icon || '🏆'} ${a.name}</span>`
        ).join('');
    } else { el.innerHTML = '<div style="color:var(--gm-muted);text-align:center;padding:1rem;">Complete challenges to earn achievements!</div>'; }
}

async function loadXPHistory() {
    const d = await gGet('xp_history', '&limit=20');
    const el = document.getElementById('xp-history');
    if (d.success && d.history && d.history.length > 0) {
        el.innerHTML = `<table style="width:100%;border-collapse:collapse;font-size:.85rem;">
            <thead><tr><th style="text-align:left;padding:.5rem;color:var(--gm-muted);border-bottom:1px solid var(--gm-border);">Action</th>
            <th style="padding:.5rem;color:var(--gm-muted);border-bottom:1px solid var(--gm-border);">XP</th>
            <th style="padding:.5rem;color:var(--gm-muted);border-bottom:1px solid var(--gm-border);">Date</th></tr></thead>
            <tbody>${d.history.map(h => `<tr>
                <td style="padding:.5rem;border-bottom:1px solid rgba(255,255,255,.05);">${h.action}</td>
                <td style="padding:.5rem;border-bottom:1px solid rgba(255,255,255,.05);color:var(--gm-gold);font-weight:600;">+${h.xp_amount}</td>
                <td style="padding:.5rem;border-bottom:1px solid rgba(255,255,255,.05);color:var(--gm-muted);">${h.created_at?.slice(0,10) || ''}</td>
            </tr>`).join('')}</tbody></table>`;
    } else { el.innerHTML = '<div style="color:var(--gm-muted);text-align:center;padding:1rem;">No XP earned yet — start using Alfred!</div>'; }
}

async function loadDailyChallenge() {
    const d = await gGet('daily_challenge');
    const el = document.getElementById('daily-challenge');
    if (d.success && d.challenge) {
        const c = d.challenge;
        el.innerHTML = `<div style="padding:1rem;text-align:center;">
            <div style="font-size:2rem;margin-bottom:.5rem;">${c.icon || '🎯'}</div>
            <h3 style="margin:0 0 .5rem;font-size:1rem;">${c.name}</h3>
            <p style="color:var(--gm-muted);font-size:.85rem;margin:0 0 1rem;">${c.description}</p>
            <div style="color:var(--gm-gold);font-weight:600;">+${c.xp_reward} XP</div>
            ${c.completed ? '<div style="color:var(--gm-green);margin-top:.5rem;">✅ Completed!</div>' : 
            '<button class="gm-btn gm-btn-primary" style="margin-top:.75rem;" onclick="claimDailyChallenge()">Complete Challenge</button>'}
        </div>`;
    } else { el.innerHTML = '<div style="color:var(--gm-muted);text-align:center;padding:1rem;">No challenge available today</div>'; }
}

async function claimDailyChallenge() {
    const d = await gGet('complete_challenge');
    if (d.success) { alert('Challenge completed! +' + (d.xp_awarded || 0) + ' XP'); refreshAll(); }
    else { alert(d.error || 'Could not complete challenge'); }
}

function refreshAll() {
    loadProfile();
    loadStreak();
    loadLeaderboard();
    loadAchievements();
    loadXPHistory();
    loadDailyChallenge();
}
refreshAll();
