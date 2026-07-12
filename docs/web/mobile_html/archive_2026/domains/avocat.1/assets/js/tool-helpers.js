/**
 * Tool Name Humanization Helpers (JavaScript)
 * 
 * Converts internal snake_case tool IDs to user-friendly display names.
 * Mirror of includes/tool-helpers.php — keep both in sync.
 *
 * @see TOOL_REGISTRY.md for the full naming convention spec.
 *
 * Usage:
 *   import { humanizeToolName, humanizeCategoryName } from '/assets/js/tool-helpers.js';
 *   humanizeToolName('check_domain')       // → "Check Domain"
 *   humanizeToolName('client_sso_login')   // → "SSO Login"
 *   humanizeCategoryName('students_k12')   // → "K-12 Students"
 */

const TOOL_DISPLAY_OVERRIDES = {
    'three_d_print_slicer':   '3D Print Slicer',
    'ci_cd_pipeline':         'CI/CD Pipeline',
    'two_factor_setup':       'Two-Factor Setup',
    'setup_2fa':              'Two-Factor Authentication Setup',
    'og_preview':             'OpenGraph Preview',
    'client_sso_login':       'SSO Login',
    'setup_ci_cd':            'CI/CD Setup',
    'wp_install':             'WordPress Install',
    'wp_install_plugin':      'WordPress Plugin Install',
    'wp_install_theme':       'WordPress Theme Install',
    'wp_list_plugins':        'WordPress Plugins',
    'wp_list_themes':         'WordPress Themes',
    'wp_remove_plugin':       'Remove WordPress Plugin',
    'wp_search_plugins':      'Search WordPress Plugins',
    'wp_search_themes':       'Search WordPress Themes',
    'wp_site_info':           'WordPress Site Info',
    'wp_update_all':          'WordPress Update All',
    'wp_db_optimize':         'WordPress DB Optimize',
    'a2a_send_task':          'Agent-to-Agent Task',
    'a2a_discover':           'Agent-to-Agent Discovery',
    'a2a_list_tasks':         'Agent-to-Agent Tasks',
    'a2a_publish_card':       'Agent-to-Agent Publish Card',
    'k8s_manage':             'Kubernetes Manager',
    'da_git_status':          'Git Status',
    'da_git_log':             'Git Log',
    'da_git_diff':            'Git Diff',
    'rag_query':              'RAG Query',
    'rag_ingest':             'RAG Ingest',
    'rag_delete':             'RAG Delete',
    'rag_list_collections':   'RAG Collections',
    'soap_note_writer':       'SOAP Note Writer',
    'iep_goal_writer':        'IEP Goal Writer',
    'hipaa_compliance':       'HIPAA Compliance',
    'gdpr_audit':             'GDPR Audit',
    'get_my_calls':           'My Calls',
    'get_my_profile':         'My Profile',
    'get_my_services':        'My Services',
    'list_my_agents':         'My AI Agents',
    'list_my_phones':         'My Phone Numbers',
    'list_my_phone_numbers':  'My Phone Numbers',
    'create_my_agent':        'Create AI Agent',
    'update_my_agent':        'Update AI Agent',
    'delete_my_agent':        'Delete AI Agent',
    'db_query':               'Database Query',
    'db_schema':              'Database Schema',
    'db_list':                'List Databases',
    'db_migrate':             'Database Migration',
    'db_stats':               'Database Statistics',
    'db_backup':              'Database Backup',
    'pg_manage':              'PostgreSQL Manager',
    'ssh_exec':               'SSH Execute',
    'sftp_transfer':          'SFTP Transfer',
    'mcp_connect':            'MCP Connect',
    'mcp_disconnect':         'MCP Disconnect',
    'mcp_list_servers':       'MCP Servers',
    'mcp_call_tool':          'MCP Call Tool',
    'voice_onboard':          'Voice Onboarding',
    'legal_draft_motion':     'Draft Legal Motion',
    'legal_fax_court':        'Fax Court Documents',
    'legal_call_court':       'Call Court Clerk',
    'legal_court_directory':  'Court Directory',
    'legal_case_status':      'Case Status',
    'legal_list_cases':       'Legal Cases',
    'legal_resume_case':      'Resume Legal Case',
    'legal_identify':         'Legal Identification',
    'legal_search':           'Legal Search (CanLII)',
    'legal_update_case':      'Update Legal Case',
    'science_lab_simulator':  'Virtual Science Lab',
    'safe_web_search':        'Safe Web Search (Kids)',
    'sel_activity_generator': 'SEL Activity Generator',
};

const TOOL_ACRONYMS = new Set([
    'sms', 'dns', 'ssl', 'seo', 'pdf', 'html', 'css', 'api', 'ai',
    'mcp', 'ci', 'cd', 'ide', 'url', 'sso', 'cors', 'gdpr', 'ccpa',
    'hipaa', 'okr', 'roi', 'sla', 'kpi', 'crm', 'nps', 'iep', 'gpa',
    'cma', 'mls', 'csv', 'sql', 'ssh', 'sftp', 'http', 'https', 'ip',
    'vpn', 'cdn', 'aws', 'gcp', 'iot', 'ar', 'vr', 'xp', 'ui', 'ux',
    'qa', 'llm', 'rag', 'sbar', 'cme', 'ce', 'oci', 'wp',
]);

const STRIP_PREFIXES = [
    'cortex_', 'nexus_', 'echo_', 'sentinel_', 'empathy_', 'tempo_',
    'pulse_', 'muse_', 'sage_', 'prism_', 'forge_', 'conduit_',
    'commerce_', 'fleet_', 'chronicle_', 'alfred_', 'messaging_',
    'autopilot_', 'architect_',
];

const CATEGORY_DISPLAY_OVERRIDES = {
    'students_k12':       'K-12 Students',
    'seo_marketing':      'SEO & Marketing',
    'ai_media':           'AI Media',
    'future_tech':        'Future Tech',
    'agent_orchestration':'Agent Orchestration',
    'marketplace_tools':  'Marketplace',
    'legal_aid':          'Legal Aid',
    'devops':             'DevOps',
    'ecommerce':          'E-Commerce',
    'small_business':     'Small Business',
    'content_creators':   'Content Creators',
    'real_estate':        'Real Estate',
};

/**
 * Convert a snake_case tool ID to a human-readable display name.
 * @param {string} toolId - Canonical tool ID (e.g., 'check_domain')
 * @param {boolean} [stripPrefix=false] - Strip engine prefixes for card titles
 * @returns {string} Display name (e.g., 'Check Domain')
 */
function humanizeToolName(toolId, stripPrefix = false) {
    if (TOOL_DISPLAY_OVERRIDES[toolId]) return TOOL_DISPLAY_OVERRIDES[toolId];

    let name = toolId;

    if (stripPrefix) {
        for (const prefix of STRIP_PREFIXES) {
            if (name.startsWith(prefix)) {
                name = name.slice(prefix.length);
                break;
            }
        }
    }

    return name
        .split('_')
        .map(w => TOOL_ACRONYMS.has(w.toLowerCase()) ? w.toUpperCase() : w.charAt(0).toUpperCase() + w.slice(1).toLowerCase())
        .join(' ');
}

/**
 * Convert a snake_case category ID to a human-readable label.
 * @param {string} categoryId - Category ID (e.g., 'small_business')
 * @returns {string} Label (e.g., 'Small Business')
 */
function humanizeCategoryName(categoryId) {
    if (CATEGORY_DISPLAY_OVERRIDES[categoryId]) return CATEGORY_DISPLAY_OVERRIDES[categoryId];
    return categoryId.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

/**
 * Strip MCP wire prefix from a tool name.
 * "mcp_gocodeme-files_read_file" → "read_file"
 * @param {string} mcpName
 * @returns {string} Canonical tool ID
 */
function stripMcpPrefix(mcpName) {
    const m = mcpName.match(/^mcp_[a-zA-Z0-9_-]+?_(.+)$/);
    return m ? m[1] : mcpName;
}

/**
 * Convert camelCase VAPI tool name to snake_case canonical ID.
 * "checkDomainAvailability" → "check_domain_availability"
 * @param {string} camelCase
 * @returns {string}
 */
function vapiToCanonical(camelCase) {
    return camelCase
        .replace(/([a-z])([A-Z])/g, '$1_$2')
        .replace(/([A-Z]+)([A-Z][a-z])/g, '$1_$2')
        .toLowerCase();
}

// Export for ES modules, or attach to window for script tags
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { humanizeToolName, humanizeCategoryName, stripMcpPrefix, vapiToCanonical };
} else if (typeof window !== 'undefined') {
    window.humanizeToolName     = humanizeToolName;
    window.humanizeCategoryName = humanizeCategoryName;
    window.stripMcpPrefix       = stripMcpPrefix;
    window.vapiToCanonical      = vapiToCanonical;
}
