/**
 * GoSiteMe Team Engine v2.0
 * Extracted from team.php inline JS
 */
(function(){
    'use strict';

    const API_TEAM = '/api/team.php';
    const API_ENTERPRISE = '/api/enterprise.php';
    let orgData = null;
    let myRole = 'member';

    // ──────── Init ────────
    async function init() {
        try {
            const res = await apiFetch(API_TEAM, { action: 'overview' });
            document.getElementById('twLoading').style.display = 'none';

            if (!res.success || !res.has_org) {
                document.getElementById('twOnboard').style.display = '';
                return;
            }

            orgData = res;
            myRole = res.org.role || 'member';

            // Show workspace
            document.getElementById('twWorkspace').style.display = '';

            // Org badge
            const logo = document.getElementById('twOrgLogo');
            if (res.org.logo_url) {
                logo.innerHTML = '<img src="' + escHtml(res.org.logo_url) + '" alt="Logo">';
            } else {
                logo.textContent = (res.org.name || 'O').charAt(0).toUpperCase();
            }
            document.getElementById('twOrgName').textContent = res.org.name || 'Organization';
            document.getElementById('twOrgPlan').textContent = (res.org.plan || 'starter').toUpperCase();

            // Stats
            document.getElementById('statMembers').textContent = res.stats.members;
            document.getElementById('statTeams').textContent = res.stats.teams;
            document.getElementById('statAgents').textContent = res.stats.shared_agents;
            document.getElementById('statConvs').textContent = res.stats.shared_conversations;

            // Nav badges
            document.getElementById('navMemberCount').textContent = res.stats.members;
            document.getElementById('navTeamCount').textContent = res.stats.teams;
            document.getElementById('navAgentCount').textContent = res.stats.shared_agents;

            // Show settings tab for admin/owner
            if (myRole === 'owner' || myRole === 'admin') {
                document.getElementById('navSettings').style.display = '';
            }

            // Online members
            renderOnlineMembers(res.members || []);

            // Activity feed
            renderActivity(res.recent_activity || []);

        } catch (err) {
            document.getElementById('twLoading').style.display = 'none';
            document.getElementById('twOnboard').style.display = '';
            console.error('Team init error:', err);
        }
    }

    // ──────── Tab switching ────────
    window.switchTab = function(tab) {
        document.querySelectorAll('.tw-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tw-nav-item').forEach(n => n.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        document.querySelector('[data-tab="' + tab + '"]').classList.add('active');

        // Lazy-load data
        if (tab === 'members') loadMembers();
        if (tab === 'teams') loadTeams();
        if (tab === 'agents') loadAgents();
        if (tab === 'conversations') loadConversations();
        if (tab === 'settings') loadSettings();
    };

    // ──────── Render online members ────────
    function renderOnlineMembers(members) {
        const el = document.getElementById('onlineMembersList');
        if (!members.length) {
            el.innerHTML = '<span style="color:var(--al-text-muted);font-size:.85rem;">No members found</span>';
            return;
        }
        el.innerHTML = members.map(m => {
            const name = ((m.firstname || '') + ' ' + (m.lastname || '')).trim() || 'User #' + m.user_id;
            const initials = getInitials(name);
            return '<div style="display:flex;align-items:center;gap:.4rem;background:var(--al-surface);border:1px solid var(--al-border);border-radius:50px;padding:.3rem .7rem .3rem .3rem;">' +
                '<div style="width:28px;height:28px;border-radius:50%;background:var(--al-accent);display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;color:#fff;position:relative;">' +
                    initials +
                    '<span style="position:absolute;bottom:-1px;right:-1px;width:8px;height:8px;border-radius:50%;background:var(--al-green);border:2px solid var(--al-surface);"></span>' +
                '</div>' +
                '<span style="font-size:.82rem;font-weight:500;">' + escHtml(name) + '</span>' +
            '</div>';
        }).join('');
    }

    // ──────── Render activity feed ────────
    function renderActivity(activities) {
        const el = document.getElementById('activityFeed');
        if (!activities.length) {
            el.innerHTML = '<div class="tw-empty"><i class="fas fa-stream"></i><p>No recent activity</p></div>';
            return;
        }
        el.innerHTML = activities.map(a => {
            const name = ((a.firstname || '') + ' ' + (a.lastname || '')).trim() || 'User';
            const initials = getInitials(name);
            const actionText = formatAction(a.action, a.details);
            const time = timeAgo(a.created_at);
            return '<div class="tw-activity-item">' +
                '<div class="tw-activity-avatar">' + initials + '</div>' +
                '<div class="tw-activity-body">' +
                    '<span class="actor">' + escHtml(name) + '</span> ' +
                    '<span class="action-text">' + actionText + '</span>' +
                    '<div class="time">' + time + '</div>' +
                '</div>' +
            '</div>';
        }).join('');
    }

    // ──────── Load Members ────────
    async function loadMembers() {
        try {
            const res = await apiFetch(API_TEAM, { action: 'members-detail' });
            if (!res.success) return;

            const isAdmin = res.my_role === 'owner' || res.my_role === 'admin';
            const el = document.getElementById('membersList');

            // Show/hide admin actions
            const actions = document.getElementById('memberActions');
            if (actions) actions.style.display = isAdmin ? 'flex' : 'none';

            if (!res.members.length) {
                el.innerHTML = '<div class="tw-empty"><i class="fas fa-users"></i><p>No members</p></div>';
                return;
            }

            el.innerHTML = res.members.map(m => {
                const name = ((m.firstname || '') + ' ' + (m.lastname || '')).trim() || 'User #' + m.user_id;
                const initials = getInitials(name);
                const roleCls = 'role-' + (m.role || 'member');
                const lastActive = m.lastlogin ? timeAgo(m.lastlogin) : 'Never';

                let actionsHtml = '';
                if (isAdmin && m.role !== 'owner') {
                    actionsHtml = '<div class="tw-member-actions">' +
                        '<select onchange="changeRole(' + m.user_id + ', this.value)">' +
                            ['admin','manager','member','viewer'].map(r =>
                                '<option value="' + r + '"' + (r === m.role ? ' selected' : '') + '>' + r.charAt(0).toUpperCase() + r.slice(1) + '</option>'
                            ).join('') +
                        '</select>' +
                        '<button class="tw-btn tw-btn-danger tw-btn-sm" onclick="removeMember(' + m.user_id + ')" title="Remove"><i class="fas fa-times"></i></button>' +
                    '</div>';
                }

                return '<div class="tw-member-row">' +
                    '<div class="tw-member-avatar">' + initials + '</div>' +
                    '<div class="tw-member-info"><div class="name">' + escHtml(name) + '</div><div class="email">' + escHtml(m.email || '') + '</div></div>' +
                    '<div class="role-col"><span class="role-badge ' + roleCls + '">' + escHtml(m.role || 'member') + '</span></div>' +
                    '<div class="last-active-col" style="font-size:.78rem;color:var(--al-text-muted);">' + lastActive + '</div>' +
                    '<div>' + actionsHtml + '</div>' +
                '</div>';
            }).join('');
        } catch (err) {
            console.error('Load members error:', err);
        }
    }
    window.loadMembers = loadMembers;

    // ──────── Load Teams ────────
    async function loadTeams() {
        try {
            const res = await apiFetch(API_ENTERPRISE, { action: 'teams' });
            if (!res.success) return;

            const el = document.getElementById('teamsList');
            if (!res.teams.length) {
                el.innerHTML = '<div class="tw-empty"><i class="fas fa-layer-group"></i><p>No teams yet. Create one to get started!</p></div>';
                return;
            }

            el.innerHTML = res.teams.map(t => {
                return '<div class="tw-team-card" onclick="toggleTeamDetail(this)">' +
                    '<h4><i class="fas fa-users-cog"></i> ' + escHtml(t.name) + '</h4>' +
                    '<div class="desc">' + escHtml(t.description || 'No description') + '</div>' +
                    '<div class="meta"><span><i class="fas fa-user"></i> ' + (t.member_count || 0) + ' members</span>' +
                    '<span><i class="fas fa-clock"></i> Created ' + timeAgo(t.created_at) + '</span></div>' +
                    '<div class="tw-team-detail">' +
                        '<p style="font-size:.82rem;color:var(--al-text-sec);margin-bottom:.5rem;">Team ID: ' + t.id + '</p>' +
                        '<button class="tw-btn tw-btn-ghost tw-btn-sm" onclick="event.stopPropagation();"><i class="fas fa-user-plus"></i> Add Member</button>' +
                    '</div>' +
                '</div>';
            }).join('');

            // Populate team filter on conversations tab
            const teamFilter = document.getElementById('filterTeam');
            const shareTeamSelect = document.getElementById('shareConvTeam');
            const opts = '<option value="">All Teams</option>' + res.teams.map(t => '<option value="' + t.id + '">' + escHtml(t.name) + '</option>').join('');
            teamFilter.innerHTML = opts;
            shareTeamSelect.innerHTML = '<option value="">Entire Organization</option>' + res.teams.map(t => '<option value="' + t.id + '">' + escHtml(t.name) + '</option>').join('');
        } catch (err) {
            console.error('Load teams error:', err);
        }
    }

    window.toggleTeamDetail = function(card) {
        card.classList.toggle('expanded');
    };

    // ──────── Load Agents ────────
    async function loadAgents() {
        try {
            const res = await apiFetch(API_TEAM, { action: 'shared-agents' });
            if (!res.success) return;

            const el = document.getElementById('agentsList');
            if (!res.agents.length) {
                el.innerHTML = '<div class="tw-empty"><i class="fas fa-robot"></i><p>No shared agents yet. Share one from your agent list!</p></div>';
                return;
            }

            el.innerHTML = res.agents.map(a => {
                const sharer = ((a.sharer_firstname || '') + ' ' + (a.sharer_lastname || '')).trim() || 'Unknown';
                const permCls = 'perm-' + (a.permissions || 'execute');
                return '<div class="tw-agent-card">' +
                    '<div class="agent-name">Agent #' + a.agent_id + '</div>' +
                    '<div class="agent-meta">Shared by ' + escHtml(sharer) + ' &bull; ' + timeAgo(a.created_at) + '</div>' +
                    '<span class="agent-perm ' + permCls + '">' + escHtml(a.permissions || 'execute') + '</span>' +
                    '<div class="agent-actions">' +
                        '<button class="tw-btn tw-btn-ghost tw-btn-sm" onclick="unshareAgent(' + a.agent_id + ')"><i class="fas fa-times"></i> Unshare</button>' +
                    '</div>' +
                '</div>';
            }).join('');
        } catch (err) {
            console.error('Load agents error:', err);
        }
    }

    // ──────── Load Conversations ────────
    window.loadConversations = async function() {
        try {
            const params = { action: 'shared-conversations' };
            const teamId = document.getElementById('filterTeam').value;
            const dateFrom = document.getElementById('filterDateFrom').value;
            const dateTo = document.getElementById('filterDateTo').value;
            if (teamId) params.team_id = teamId;
            if (dateFrom) params.date_from = dateFrom;
            if (dateTo) params.date_to = dateTo;

            const res = await apiFetch(API_TEAM, params);
            if (!res.success) return;

            const el = document.getElementById('convList');
            if (!res.conversations.length) {
                el.innerHTML = '<div class="tw-empty"><i class="fas fa-comments"></i><p>No shared conversations match your filters</p></div>';
                return;
            }

            el.innerHTML = res.conversations.map(c => {
                const sharer = ((c.sharer_firstname || '') + ' ' + (c.sharer_lastname || '')).trim() || 'Unknown';
                const teamName = c.team_name || 'Entire org';
                return '<div class="tw-conv-card">' +
                    '<div>' +
                        '<div class="conv-title">Conversation: ' + escHtml(c.conv_id) + '</div>' +
                        '<div class="conv-meta">Shared with: ' + escHtml(teamName) + ' &bull; ' + timeAgo(c.created_at) + '</div>' +
                    '</div>' +
                    '<div class="conv-shared-by">by ' + escHtml(sharer) + '</div>' +
                '</div>';
            }).join('');
        } catch (err) {
            console.error('Load conversations error:', err);
        }
    };

    // ──────── Load Settings ────────
    async function loadSettings() {
        try {
            const res = await apiFetch(API_ENTERPRISE, { action: 'org' });
            if (!res.success) return;
            const org = res.organization;
            document.getElementById('settOrgName').value = org.name || '';
            document.getElementById('settOrgLogo').value = org.logo_url || '';
            document.getElementById('settOrgDomain').value = org.domain || '';
            document.getElementById('settOrgPlan').value = (org.plan || 'starter').toUpperCase();
            document.getElementById('settMaxUsers').value = org.max_users || 5;
            document.getElementById('settMaxAgents').value = org.max_agents || 3;
        } catch (err) {
            console.error('Load settings error:', err);
        }
    }

    // ──────── Actions ────────

    // Create org
    window.createOrg = async function() {
        const name = document.getElementById('createOrgName').value.trim();
        const slug = document.getElementById('createOrgSlug').value.trim().toLowerCase();
        if (!name || !slug) return toast('Name and slug are required', 'error');
        if (!/^[a-z0-9\-]{3,100}$/.test(slug)) return toast('Slug must be 3-100 lowercase letters, numbers, hyphens', 'error');

        try {
            const res = await apiPost(API_ENTERPRISE, { action: 'org/create' }, { name, slug });
            if (res.success) {
                toast('Organization created!', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                toast(res.error || 'Failed to create org', 'error');
            }
        } catch (err) {
            toast('Error: ' + err.message, 'error');
        }
    };

    // Join org
    window.joinOrg = async function() {
        const code = document.getElementById('joinCode').value.trim().toUpperCase();
        if (!code) return toast('Enter an invite code', 'error');

        try {
            const res = await apiPost(API_TEAM, { action: 'join' }, { code });
            if (res.success) {
                toast('Joined ' + (res.org_name || 'organization') + '!', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                toast(res.error || 'Failed to join', 'error');
            }
        } catch (err) {
            toast('Error: ' + err.message, 'error');
        }
    };

    // Send invite
    window.sendInvite = async function() {
        const email = document.getElementById('inviteEmail').value.trim();
        const role = document.getElementById('inviteRole').value;
        if (!email) return toast('Email is required', 'error');

        try {
            const res = await apiPost(API_ENTERPRISE, { action: 'members/invite' }, { email, role });
            if (res.success) {
                toast('Invitation sent!', 'success');
                hideModal('inviteModal');
                document.getElementById('inviteEmail').value = '';
            } else {
                toast(res.error || 'Failed', 'error');
            }
        } catch (err) {
            toast('Error: ' + err.message, 'error');
        }
    };

    // Generate invite code
    window.generateInviteCode = async function() {
        const roleId = parseInt(document.getElementById('codeRoleId').value);
        const maxUses = parseInt(document.getElementById('codeMaxUses').value);

        try {
            const res = await apiPost(API_TEAM, { action: 'invite-code' }, { role_id: roleId, max_uses: maxUses });
            if (res.success) {
                document.getElementById('generatedCode').textContent = res.code;
                document.getElementById('generatedCodeBox').style.display = '';
                toast('Invite code generated!', 'success');
            } else {
                toast(res.error || 'Failed', 'error');
            }
        } catch (err) {
            toast('Error: ' + err.message, 'error');
        }
    };

    // Change member role
    window.changeRole = async function(uid, newRole) {
        try {
            const res = await apiPost(API_ENTERPRISE, { action: 'members/role' }, { user_id: uid, role: newRole });
            if (res.success) {
                toast('Role updated', 'success');
            } else {
                toast(res.error || 'Failed', 'error');
                loadMembers();
            }
        } catch (err) {
            toast('Error: ' + err.message, 'error');
        }
    };

    // Remove member
    window.removeMember = async function(uid) {
        if (!confirm('Remove this member from the organization?')) return;
        try {
            const res = await apiPost(API_ENTERPRISE, { action: 'members/remove' }, { user_id: uid });
            if (res.success) {
                toast('Member removed', 'success');
                loadMembers();
            } else {
                toast(res.error || 'Failed', 'error');
            }
        } catch (err) {
            toast('Error: ' + err.message, 'error');
        }
    };

    // Create team
    window.createTeam = async function() {
        const name = document.getElementById('newTeamName').value.trim();
        const description = document.getElementById('newTeamDesc').value.trim();
        if (!name) return toast('Team name is required', 'error');

        try {
            const res = await apiPost(API_ENTERPRISE, { action: 'teams/create' }, { name, description });
            if (res.success) {
                toast('Team created!', 'success');
                hideModal('createTeamModal');
                document.getElementById('newTeamName').value = '';
                document.getElementById('newTeamDesc').value = '';
                loadTeams();
            } else {
                toast(res.error || 'Failed', 'error');
            }
        } catch (err) {
            toast('Error: ' + err.message, 'error');
        }
    };

    // Share agent
    window.shareAgent = async function() {
        const agentId = parseInt(document.getElementById('shareAgentId').value);
        const permissions = document.getElementById('shareAgentPerm').value;
        if (!agentId) return toast('Agent ID is required', 'error');

        try {
            const res = await apiPost(API_TEAM, { action: 'share-agent' }, { agent_id: agentId, permissions });
            if (res.success) {
                toast('Agent shared!', 'success');
                hideModal('shareAgentModal');
                loadAgents();
            } else {
                toast(res.error || 'Failed', 'error');
            }
        } catch (err) {
            toast('Error: ' + err.message, 'error');
        }
    };

    // Unshare agent
    window.unshareAgent = async function(agentId) {
        if (!confirm('Remove this agent from organization sharing?')) return;
        try {
            const res = await apiPost(API_TEAM, { action: 'unshare-agent' }, { agent_id: agentId });
            if (res.success) {
                toast('Agent unshared', 'success');
                loadAgents();
            } else {
                toast(res.error || 'Failed', 'error');
            }
        } catch (err) {
            toast('Error: ' + err.message, 'error');
        }
    };

    // Share conversation
    window.shareConversation = async function() {
        const convId = document.getElementById('shareConvId').value.trim();
        const teamId = document.getElementById('shareConvTeam').value || null;
        if (!convId) return toast('Conversation ID is required', 'error');

        try {
            const res = await apiPost(API_TEAM, { action: 'share-conversation' }, { conv_id: convId, team_id: teamId ? parseInt(teamId) : null });
            if (res.success) {
                toast('Conversation shared!', 'success');
                hideModal('shareConvModal');
                loadConversations();
            } else {
                toast(res.error || 'Failed', 'error');
            }
        } catch (err) {
            toast('Error: ' + err.message, 'error');
        }
    };

    // Save org settings
    window.saveOrgSettings = async function() {
        const data = {
            name: document.getElementById('settOrgName').value.trim(),
            logo_url: document.getElementById('settOrgLogo').value.trim(),
            domain: document.getElementById('settOrgDomain').value.trim(),
            max_users: parseInt(document.getElementById('settMaxUsers').value) || 5,
            max_agents: parseInt(document.getElementById('settMaxAgents').value) || 3,
        };

        try {
            const res = await apiPost(API_ENTERPRISE, { action: 'org/update' }, data);
            if (res.success) {
                toast('Settings saved!', 'success');
            } else {
                toast(res.error || 'Failed', 'error');
            }
        } catch (err) {
            toast('Error: ' + err.message, 'error');
        }
    };

    // Transfer ownership (placeholder)
    window.transferOwnership = function() {
        const newOwner = prompt('Enter the User ID of the new owner:');
        if (!newOwner) return;
        toast('Ownership transfer is not yet implemented via this UI. Please contact support.', 'error');
    };

    // Delete org (placeholder)
    window.deleteOrg = function() {
        if (!confirm('ARE YOU SURE? This will permanently delete the organization, all teams, shared agents, and data. This cannot be undone.')) return;
        if (!confirm('FINAL CONFIRMATION: Type "DELETE" to proceed.')) return;
        toast('Organization deletion is not yet implemented via this UI. Please contact support.', 'error');
    };

    // ──────── Modals ────────
    window.showModal = function(id) {
        document.getElementById(id).classList.add('show');
    };
    window.hideModal = function(id) {
        document.getElementById(id).classList.remove('show');
        if (id === 'inviteCodeModal') {
            document.getElementById('generatedCodeBox').style.display = 'none';
        }
    };

    // Close modal on overlay click
    document.querySelectorAll('.tw-modal-overlay').forEach(ov => {
        ov.addEventListener('click', e => {
            if (e.target === ov) { ov.classList.remove('show'); }
        });
    });

    // ──────── Helpers ────────
    async function apiFetch(endpoint, params) {
        const url = new URL(endpoint, location.origin);
        Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
        const resp = await fetch(url.toString(), { credentials: 'same-origin' });
        return resp.json();
    }

    async function apiPost(endpoint, params, body) {
        const url = new URL(endpoint, location.origin);
        Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
        const resp = await fetch(url.toString(), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(body)
        });
        return resp.json();
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    function getInitials(name) {
        const parts = (name || '').trim().split(/\s+/);
        if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
        return (name || 'U').substring(0, 2).toUpperCase();
    }

    function timeAgo(dateStr) {
        if (!dateStr) return 'Unknown';
        const d = new Date(dateStr.replace(' ', 'T') + (dateStr.includes('Z') ? '' : 'Z'));
        const diff = Math.floor((Date.now() - d.getTime()) / 1000);
        if (diff < 60) return 'just now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
        return d.toLocaleDateString();
    }

    function formatAction(action, details) {
        const map = {
            'agent.shared': 'shared an agent with the organization',
            'agent.unshared': 'removed a shared agent',
            'conversation.shared': 'shared a conversation',
            'member.joined_via_code': 'joined the organization via invite code',
            'invite_code.created': 'generated an invite code',
            'team.created': 'created a new team',
            'member.invited': 'invited a new member',
            'member.removed': 'removed a member',
            'member.role_changed': 'changed a member\'s role',
            'org.created': 'created the organization',
            'org.updated': 'updated organization settings',
        };
        return map[action] || action.replace(/\./g, ' ');
    }

    function toast(msg, type) {
        if (window.GDSToast) return GDSToast.show(msg, { type: (type || 'success') === 'error' ? 'danger' : (type || 'success') });
    }

    // Boot
    init();

})();
