<?php
/**
 * Tool Name Humanization Helpers
 * 
 * Converts internal snake_case tool IDs to user-friendly display names.
 * Used by api/tools.php, alfred-chat.php, and any UI that shows tool names.
 * 
 * @see TOOL_REGISTRY.md for the full naming convention spec.
 */

/**
 * Hand-curated display name overrides for tools that don't auto-humanize well.
 * Add entries here when humanizeToolName() produces a wrong or ugly result.
 */
const TOOL_DISPLAY_OVERRIDES = [
    // Abbreviations & acronyms that need special casing
    'three_d_print_slicer'    => '3D Print Slicer',
    'ci_cd_pipeline'          => 'CI/CD Pipeline',
    'two_factor_setup'        => 'Two-Factor Setup',
    'setup_2fa'               => 'Two-Factor Authentication Setup',
    'og_preview'              => 'OpenGraph Preview',
    'client_sso_login'        => 'SSO Login',
    'setup_ci_cd'             => 'CI/CD Setup',

    // WordPress
    'wp_install'              => 'WordPress Install',
    'wp_install_plugin'       => 'WordPress Plugin Install',
    'wp_install_theme'        => 'WordPress Theme Install',
    'wp_list_plugins'         => 'WordPress Plugins',
    'wp_list_themes'          => 'WordPress Themes',
    'wp_remove_plugin'        => 'Remove WordPress Plugin',
    'wp_search_plugins'       => 'Search WordPress Plugins',
    'wp_search_themes'        => 'Search WordPress Themes',
    'wp_site_info'            => 'WordPress Site Info',
    'wp_update_all'           => 'WordPress Update All',
    'wp_db_optimize'          => 'WordPress DB Optimize',

    // Platform names
    'a2a_send_task'           => 'Agent-to-Agent Task',
    'a2a_discover'            => 'Agent-to-Agent Discovery',
    'a2a_list_tasks'          => 'Agent-to-Agent Tasks',
    'a2a_publish_card'        => 'Agent-to-Agent Publish Card',
    'k8s_manage'              => 'Kubernetes Manager',
    'da_git_status'           => 'Git Status',
    'da_git_log'              => 'Git Log',
    'da_git_diff'             => 'Git Diff',
    'rag_query'               => 'RAG Query',
    'rag_ingest'              => 'RAG Ingest',
    'rag_delete'              => 'RAG Delete',
    'rag_list_collections'    => 'RAG Collections',

    // Medical/Legal acronyms
    'soap_note_writer'        => 'SOAP Note Writer',
    'iep_goal_writer'         => 'IEP Goal Writer',
    'hipaa_compliance'        => 'HIPAA Compliance',
    'gdpr_audit'              => 'GDPR Audit',

    // Tools where dropping prefix improves readability
    'get_my_calls'            => 'My Calls',
    'get_my_profile'          => 'My Profile',
    'get_my_services'         => 'My Services',
    'list_my_agents'          => 'My AI Agents',
    'list_my_phones'          => 'My Phone Numbers',
    'list_my_phone_numbers'   => 'My Phone Numbers',
    'create_my_agent'         => 'Create AI Agent',
    'update_my_agent'         => 'Update AI Agent',
    'delete_my_agent'         => 'Delete AI Agent',

    // Shorthands
    'db_query'                => 'Database Query',
    'db_schema'               => 'Database Schema',
    'db_list'                 => 'List Databases',
    'db_migrate'              => 'Database Migration',
    'db_stats'                => 'Database Statistics',
    'db_backup'               => 'Database Backup',
    'pg_manage'               => 'PostgreSQL Manager',
    'ssh_exec'                => 'SSH Execute',
    'sftp_transfer'           => 'SFTP Transfer',
    'mcp_connect'             => 'MCP Connect',
    'mcp_disconnect'          => 'MCP Disconnect',
    'mcp_list_servers'        => 'MCP Servers',
    'mcp_call_tool'           => 'MCP Call Tool',

    // Complex names
    'voice_onboard'           => 'Voice Onboarding',
    'legal_draft_motion'      => 'Draft Legal Motion',
    'legal_fax_court'         => 'Fax Court Documents',
    'legal_call_court'        => 'Call Court Clerk',
    'legal_court_directory'   => 'Court Directory',
    'legal_case_status'       => 'Case Status',
    'legal_list_cases'        => 'Legal Cases',
    'legal_resume_case'       => 'Resume Legal Case',
    'legal_identify'          => 'Legal Identification',
    'legal_search'            => 'Legal Search (CanLII)',
    'legal_update_case'       => 'Update Legal Case',
    'science_lab_simulator'   => 'Virtual Science Lab',
    'safe_web_search'         => 'Safe Web Search (Kids)',
    'sel_activity_generator'  => 'SEL Activity Generator',

    // Financial Module — Stripe
    'stripe_tax_calculate'     => 'Stripe Tax Calculate',
    'stripe_tax_rates'         => 'Stripe Tax Rates',
    'stripe_connect_onboard'   => 'Stripe Connect Onboard',
    'stripe_connect_status'    => 'Stripe Connect Status',
    'stripe_connect_payout'    => 'Stripe Connect Payout',
    'stripe_meter_create'      => 'Stripe Billing Meter',
    'stripe_meter_report'      => 'Stripe Meter Report',
    'stripe_card_create'       => 'Stripe Issue Card',
    'stripe_card_list'         => 'Stripe Cards',
    'stripe_card_transactions' => 'Stripe Card Transactions',

    // Financial Module — Accounting
    'chart_of_accounts'     => 'Chart of Accounts',
    'journal_create'        => 'Create Journal Entry',
    'journal_list'          => 'Journal Entries',
    'profit_loss'           => 'Profit & Loss',
    'balance_sheet'         => 'Balance Sheet',
    'trial_balance'         => 'Trial Balance',
    'cash_flow'             => 'Cash Flow Statement',
    'xero_connect'          => 'Connect Xero',
    'xero_sync'             => 'Sync Xero',
    'xero_invoices'         => 'Xero Invoices',
    'xero_create_invoice'   => 'Create Xero Invoice',
    'qbo_connect'           => 'Connect QuickBooks',
    'qbo_sync'              => 'Sync QuickBooks',
    'auto_categorize'       => 'Auto-Categorize Transactions',
    'reconcile'             => 'Bank Reconciliation',

    // Financial Module — Banking
    'plaid_link'            => 'Link Bank Account (Plaid)',
    'plaid_balances'        => 'Bank Balances (Plaid)',
    'plaid_transactions'    => 'Bank Transactions (Plaid)',
    'mercury_accounts'      => 'Mercury Accounts',
    'mercury_balance'       => 'Mercury Balance',
    'mercury_transfer'      => 'Mercury Transfer',
    'wise_balances'         => 'Wise Balances',
    'wise_transfer'         => 'Wise Transfer',
    'wise_rates'            => 'Wise Exchange Rates',
    'all_balances'          => 'All Bank Balances',

    // Financial Module — Payouts
    'payout_create'         => 'Create Payout',
    'payout_batch'          => 'Batch Payout',
    'payout_list'           => 'Payout History',
    'payout_stats'          => 'Payout Statistics',
    'paypal_mass_payout'    => 'PayPal Mass Payout',
    'deel_contracts'        => 'Deel Contracts',
    'deel_pay'              => 'Deel Payment',
    'contractor_add'        => 'Add Contractor',
    'contractor_list'       => 'Contractors',
    'contractor_pay'        => 'Pay Contractor',
    'affiliate_pending'     => 'Pending Affiliates',
    'affiliate_payout'      => 'Affiliate Payout',

    // Financial Module — Analytics
    'saas_mrr'              => 'Monthly Recurring Revenue',
    'saas_arr'              => 'Annual Recurring Revenue',
    'saas_churn'            => 'Churn Rate',
    'saas_ltv'              => 'Lifetime Value',
    'revenue_trend'         => 'Revenue Trend',
    'cohort_analysis'       => 'Cohort Analysis',
    'dashboard_kpis'        => 'Dashboard KPIs',
    'profitwell_metrics'    => 'ProfitWell Metrics',
    'forecast_revenue'      => 'Revenue Forecast',
    'forecast_churn'        => 'Churn Forecast',
    'forecast_cashflow'     => 'Cash Flow Forecast',

    // Financial Module — Tax
    'tax_obligations'       => 'Tax Obligations',
    'tax_upcoming'          => 'Upcoming Tax Deadlines',
    'tax_summary'           => 'Tax Summary',
    'taxjar_calculate'      => 'TaxJar Calculate',
    'taxjar_rates'          => 'TaxJar Rates',
    'taxjar_nexus'          => 'TaxJar Nexus',
    'koinly_sync'           => 'Koinly Crypto Sync',
    'koinly_gains'          => 'Koinly Capital Gains',
    'estimate_quarterly_tax'=> 'Quarterly Tax Estimate',
    'gst_report'            => 'GST/HST Report',

    // Financial Module — Trading
    'kraken_ticker'         => 'Kraken Ticker',
    'kraken_balance'        => 'Kraken Balance',
    'kraken_order'          => 'Kraken Order',
    'coinbase_prices'       => 'Coinbase Prices',
    'coinbase_order'        => 'Coinbase Order',
    'oneinch_quote'         => '1inch DEX Quote',
    'oneinch_swap'          => '1inch DEX Swap',
    'lifi_quote'            => 'Li.Fi Bridge Quote',
    'lifi_routes'           => 'Li.Fi Bridge Routes',
    'evm_balance'           => 'EVM Wallet Balance',
    'evm_gas'               => 'EVM Gas Price',
    'evm_tokens'            => 'EVM Token Balances',
    'trading_portfolio'     => 'Trading Portfolio',
    'daily_trade_limit'     => 'Daily Trade Limit',

    // Gamification
    'gamify_profile'           => 'Gamification Profile',
    'gamify_award_xp'          => 'Award XP',
    'gamify_leaderboard'       => 'XP Leaderboard',
    'gamify_achievements'      => 'Achievements List',
    'gamify_my_achievements'   => 'My Achievements',
    'gamify_check_streak'      => 'Check Streak',
    'gamify_daily_challenge'   => 'Daily Challenge',
    'gamify_complete_challenge'=> 'Complete Challenge',
    'gamify_xp_history'        => 'XP History',
    'gamify_stats'             => 'Gamification Stats',

    // Reporting Engine
    'report_usage'             => 'Usage Report',
    'report_revenue'           => 'Revenue Report',
    'report_agent_performance' => 'Agent Performance',
    'report_tool_usage'        => 'Tool Usage Report',
    'report_client'            => 'Client Report',
    'report_dashboard_kpis'    => 'Dashboard KPIs',
    'report_conversations'     => 'Conversation Stats',
    'report_growth'            => 'Growth Metrics',
    'report_save'              => 'Save Report',
    'report_saved_list'        => 'Saved Reports',
    'report_export'            => 'Export Report',
    'report_schedule'          => 'Schedule Report',

    // Marketplace
    'marketplace_browse'       => 'Browse Marketplace',
    'marketplace_search'       => 'Search Marketplace',
    'marketplace_categories'   => 'Marketplace Categories',
    'marketplace_detail'       => 'Item Details',
    'marketplace_featured'     => 'Featured Items',
    'marketplace_trending'     => 'Trending Items',
    'marketplace_install'      => 'Install Item',
    'marketplace_uninstall'    => 'Uninstall Item',
    'marketplace_my_installs'  => 'My Installs',
    'marketplace_rate'         => 'Rate Item',
    'marketplace_review'       => 'Write Review',
    'marketplace_reviews'      => 'Item Reviews',
    'marketplace_wishlist_add' => 'Add to Wishlist',
    'marketplace_wishlist_remove' => 'Remove from Wishlist',
    'marketplace_my_wishlist'  => 'My Wishlist',
    'marketplace_stats'        => 'Marketplace Stats',

    // Small Business / CRM
    'crm_contacts_list'        => 'CRM Contacts',
    'crm_contact_create'       => 'Create Contact',
    'crm_contact_update'       => 'Update Contact',
    'crm_contact_detail'       => 'Contact Details',
    'crm_contact_search'       => 'Search Contacts',
    'crm_activity_log'         => 'Activity Log',
    'crm_activity_create'      => 'Log Activity',
    'time_log'                 => 'Time Log',
    'time_create'              => 'Log Time',
    'time_summary'             => 'Time Summary',
    'biz_projects_list'        => 'Projects List',
    'biz_project_create'       => 'Create Project',
    'biz_project_update'       => 'Update Project',
    'biz_project_detail'       => 'Project Details',
    'biz_tasks_list'           => 'Tasks List',
    'biz_task_create'          => 'Create Task',
    'biz_task_update'          => 'Update Task',
    'biz_invoice_create'       => 'Create Invoice',
    'biz_invoice_list'         => 'Invoice List',
    'biz_invoice_detail'       => 'Invoice Details',
    'biz_invoice_send'         => 'Send Invoice',
    'biz_invoice_from_time'    => 'Invoice from Time',
    'biz_dashboard'            => 'Business Dashboard',

    // Collaboration & Conferencing
    'collab_create_session'    => 'Create Session',
    'collab_join_session'      => 'Join Session',
    'collab_leave_session'     => 'Leave Session',
    'collab_end_session'       => 'End Session',
    'collab_my_sessions'       => 'My Sessions',
    'collab_session_detail'    => 'Session Details',
    'collab_invite'            => 'Invite to Session',
    'collab_doc_create'        => 'Create Document',
    'collab_doc_update'        => 'Update Document',
    'collab_doc_get'           => 'Get Document',
    'collab_doc_list'          => 'Document List',
    'collab_doc_revisions'     => 'Document Revisions',
    'collab_doc_lock'          => 'Lock Document',
    'collab_doc_unlock'        => 'Unlock Document',
    'collab_wb_create'         => 'Create Whiteboard',
    'collab_wb_update'         => 'Update Whiteboard',
    'collab_wb_get'            => 'Get Whiteboard',
    'collab_conf_create'       => 'Create Conference',
    'collab_conf_join'         => 'Join Conference',
    'collab_conf_leave'        => 'Leave Conference',
    'collab_conf_end'          => 'End Conference',
    'collab_conf_toggle'       => 'Toggle Media',
    'collab_conf_status'       => 'Conference Status',
    'collab_chat_send'         => 'Send Chat',
    'collab_chat_history'      => 'Chat History',
    'collab_poll_create'       => 'Create Poll',
    'collab_poll_vote'         => 'Vote on Poll',
    'collab_poll_results'      => 'Poll Results',

    // Healthcare
    'hc_patient_create'        => 'Create Patient',
    'hc_patient_update'        => 'Update Patient',
    'hc_patient_list'          => 'Patient List',
    'hc_patient_detail'        => 'Patient Details',
    'hc_patient_search'        => 'Search Patients',
    'hc_soap_create'           => 'Create SOAP Note',
    'hc_soap_update'           => 'Update SOAP Note',
    'hc_soap_list'             => 'SOAP Notes List',
    'hc_soap_detail'           => 'SOAP Note Detail',
    'hc_soap_sign'             => 'Sign SOAP Note',
    'hc_med_add'               => 'Add Medication',
    'hc_med_update'            => 'Update Medication',
    'hc_med_list'              => 'Medication List',
    'hc_med_interactions'      => 'Med Interactions',
    'hc_appt_create'           => 'Schedule Appointment',
    'hc_appt_update'           => 'Update Appointment',
    'hc_appt_list'             => 'Appointment List',
    'hc_appt_today'            => 'Today\'s Schedule',
    'hc_appt_cancel'           => 'Cancel Appointment',
    'hc_intake_create'         => 'Create Intake Form',
    'hc_intake_submit'         => 'Submit Intake',
    'hc_intake_list'           => 'Intake Forms List',
    'hc_vitals_record'         => 'Record Vitals',
    'hc_vitals_history'        => 'Vitals History',
    'hc_lab_order'             => 'Order Lab Test',
    'hc_lab_result'            => 'Record Lab Result',
    'hc_lab_list'              => 'Lab Results List',
    'hc_dashboard'             => 'Healthcare Dashboard',
    'hc_audit_log'             => 'HIPAA Audit Log',
];

/**
 * Acronyms that should be fully uppercased when found as a word in tool names.
 */
const TOOL_ACRONYMS = [
    'sms', 'dns', 'ssl', 'seo', 'pdf', 'html', 'css', 'api', 'ai',
    'mcp', 'ci', 'cd', 'ide', 'url', 'sso', 'cors', 'gdpr', 'ccpa',
    'hipaa', 'okr', 'roi', 'sla', 'kpi', 'crm', 'nps', 'iep', 'gpa',
    'cma', 'mls', 'csv', 'sql', 'ssh', 'sftp', 'http', 'https', 'ip',
    'vpn', 'cdn', 'aws', 'gcp', 'iot', 'ar', 'vr', 'xp', 'ui', 'ux',
    'qa', 'llm', 'rag', 'sbar', 'cme', 'ce', 'oci', 'wp',
];

/**
 * Category prefixes that get stripped from display names.
 * The category provides context, so "legal_search" displays as "Legal Search"
 * but "cortex_set_goal" displays as "Set Goal".
 */
const TOOL_STRIP_PREFIXES = [
    'cortex_', 'nexus_', 'echo_', 'sentinel_', 'empathy_', 'tempo_',
    'pulse_', 'muse_', 'sage_', 'prism_', 'forge_', 'conduit_',
    'commerce_', 'fleet_', 'chronicle_', 'alfred_', 'messaging_',
    'autopilot_', 'architect_',
];

/**
 * Convert a snake_case tool ID to a human-readable display name.
 *
 * @param string $toolId  Canonical tool ID (e.g., 'check_domain')
 * @param bool   $stripPrefix  Whether to strip engine prefixes (default: false). 
 *                              Set true for card titles within a category context.
 * @return string  Display name (e.g., 'Check Domain')
 */
function humanizeToolName(string $toolId, bool $stripPrefix = false): string {
    // 1. Check overrides first
    if (isset(TOOL_DISPLAY_OVERRIDES[$toolId])) {
        return TOOL_DISPLAY_OVERRIDES[$toolId];
    }

    $name = $toolId;

    // 2. Optionally strip engine prefixes
    if ($stripPrefix) {
        foreach (TOOL_STRIP_PREFIXES as $prefix) {
            if (strpos($name, $prefix) === 0) {
                $name = substr($name, strlen($prefix));
                break;
            }
        }
    }

    // 3. Split on underscores, title-case each word
    $words = explode('_', $name);
    $result = [];

    foreach ($words as $word) {
        $lower = strtolower($word);
        if (in_array($lower, TOOL_ACRONYMS, true)) {
            $result[] = strtoupper($word);
        } else {
            $result[] = ucfirst($lower);
        }
    }

    return implode(' ', $result);
}

/**
 * Category display name overrides.
 */
const CATEGORY_DISPLAY_OVERRIDES = [
    'students_k12'        => 'K-12 Students',
    'seo_marketing'        => 'SEO & Marketing',
    'ai_media'             => 'AI Media',
    'future_tech'          => 'Future Tech',
    'agent_orchestration'  => 'Agent Orchestration',
    'marketplace_tools'    => 'Marketplace',
    'legal_aid'            => 'Legal Aid',
    'devops'               => 'DevOps',
    'ecommerce'            => 'E-Commerce',
    'small_business'       => 'Small Business',
    'content_creators'     => 'Content Creators',
    'real_estate'          => 'Real Estate',
    'voice_ai'             => 'Voice AI Agents',
    'sms_messaging'        => 'SMS & Messaging',
    'voice_documents'      => 'Voice Documents',
    'jailhouse_legal'      => 'Jailhouse Legal Aid',
];

/**
 * Category icons.
 */
const CATEGORY_ICONS = [
    'students_k12'        => '📚',
    'university'           => '🎓',
    'professionals'        => '💼',
    'small_business'       => '🏪',
    'content_creators'     => '🎬',
    'healthcare'           => '🏥',
    'real_estate'          => '🏠',
    'legal'                => '⚖️',
    'legal_aid'            => '🏛️',
    'parents'              => '👨‍👩‍👧',
    'seniors'              => '🧓',
    'teachers'             => '📐',
    'freelancers'          => '✏️',
    'nonprofits'           => '🎗️',
    'devops'               => '💻',
    'hosting'              => '🌐',
    'ecommerce'            => '🛒',
    'seo_marketing'        => '🔍',
    'communication'        => '💬',
    'security'             => '🛡️',
    'ai_media'             => '🎨',
    'future_tech'          => '⚛️',
    'agent_orchestration'  => '🚀',
    'collaboration'        => '👥',
    'conferencing'         => '📹',
    'reporting'            => '📊',
    'marketplace_tools'    => '🛒',
    'gamification'         => '🎮',
    'offline'              => '📴',
    'voice_ai'             => '🤖',
    'phone'                => '📞',
    'sms_messaging'        => '💬',
    'fax'                  => '📠',
    'calls'                => '📞',
    'campaigns'            => '📣',
    'voice_documents'      => '📝',
    'account'              => '👤',
    'billing'              => '💳',
    'support'              => '🎫',
    'jailhouse_legal'      => '🏛️',
];

/**
 * Convert a snake_case category ID to a human-readable label.
 *
 * @param string $categoryId  Category ID (e.g., 'small_business')
 * @return string  Label (e.g., 'Small Business')
 */
function humanizeCategoryName(string $categoryId): string {
    if (isset(CATEGORY_DISPLAY_OVERRIDES[$categoryId])) {
        return CATEGORY_DISPLAY_OVERRIDES[$categoryId];
    }
    return ucwords(str_replace('_', ' ', $categoryId));
}

/**
 * Get the icon for a category.
 *
 * @param string $categoryId  Category ID
 * @return string  Emoji icon or default
 */
function getCategoryIcon(string $categoryId): string {
    return CATEGORY_ICONS[$categoryId] ?? '🔧';
}

/**
 * Strip MCP wire prefix from a tool name.
 * Converts "mcp_gocodeme-files_read_file" → "read_file"
 *
 * @param string $mcpName  MCP wire name
 * @return string  Canonical tool ID
 */
function stripMcpPrefix(string $mcpName): string {
    if (preg_match('/^mcp_[a-zA-Z0-9_-]+?_(.+)$/', $mcpName, $m)) {
        return $m[1];
    }
    return $mcpName;
}

/**
 * Convert a camelCase VAPI tool name to snake_case canonical ID.
 * "checkDomainAvailability" → "check_domain_availability"
 *
 * @param string $camelCase  VAPI-style tool name
 * @return string  Canonical snake_case ID
 */
function vapiToCanonical(string $camelCase): string {
    // Insert underscore before uppercase letters, then lowercase everything
    $snake = preg_replace('/([a-z])([A-Z])/', '$1_$2', $camelCase);
    $snake = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1_$2', $snake);
    return strtolower($snake);
}
