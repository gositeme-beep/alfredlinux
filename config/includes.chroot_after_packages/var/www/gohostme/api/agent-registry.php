<?php
/**
 * Alfred Agent Registry API — Phase 1: Autonomy Foundation
 * ─────────────────────────────────────────────────────────
 * 100-Agent Hierarchy: Alfred → 10 Directors → 90 Specialists
 *
 * Endpoints:
 *   GET  ?action=list               → List agents (optionally filter by role/domain/status)
 *   GET  ?action=get&agent_id=X     → Get single agent + stats
 *   GET  ?action=hierarchy          → Full tree structure
 *   POST ?action=delegate           → Delegate a task to an agent
 *   GET  ?action=tasks              → List tasks (filter by agent/status)
 *   POST ?action=task-update        → Update task status + output
 *   GET  ?action=messages           → Agent-to-agent message log
 *   POST ?action=message            → Send inter-agent message
 *   POST ?action=heartbeat          → Agent heartbeat (mark active)
 *   GET  ?action=stats              → System-wide agent statistics
 *   POST ?action=seed               → Seed/register all 100 agents (admin only)
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

requireCSRF();
apiRateLimit(30, 60, 'agent-registry');

// ─── Auth ──────────────────────────────────────────────────────────────
function requireAuth() {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
        jsonResponse(['error' => 'Authentication required'], 401);
    }
}

function isAdmin() {
    return !empty($_SESSION['is_admin']) || ($_SESSION['client_id'] ?? 0) === 33;
}

function isInternalCall() {
    $secret = getenv('INTERNAL_SECRET') ?: '';
    return $secret && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($secret, $_SERVER['HTTP_X_INTERNAL_SECRET']);
}

// ─── DB Schema Bootstrap ───────────────────────────────────────────────
function ensureSchema() {
    $db = getDB();
    if (!$db) return false;

    $db->exec("CREATE TABLE IF NOT EXISTS alfred_agent_registry (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        agent_id        VARCHAR(50) UNIQUE NOT NULL,
        agent_name      VARCHAR(100) NOT NULL,
        agent_role      ENUM('commander','director','specialist') NOT NULL,
        domain          VARCHAR(50) NOT NULL,
        parent_agent_id VARCHAR(50) DEFAULT NULL,
        tools_access    JSON NOT NULL,
        personality     JSON NOT NULL,
        status          ENUM('active','idle','busy','offline','error') DEFAULT 'idle',
        current_task    TEXT DEFAULT NULL,
        tasks_completed INT DEFAULT 0,
        tasks_failed    INT DEFAULT 0,
        success_rate    DECIMAL(5,2) DEFAULT 100.00,
        last_active     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_role (agent_role),
        INDEX idx_domain (domain),
        INDEX idx_parent (parent_agent_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS alfred_agent_tasks (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        task_id         VARCHAR(50) UNIQUE NOT NULL,
        assigned_agent  VARCHAR(50) NOT NULL,
        delegated_by    VARCHAR(50) NOT NULL,
        goal            TEXT NOT NULL,
        strategy        ENUM('parallel','pipeline','consensus','competition') DEFAULT 'parallel',
        priority        TINYINT DEFAULT 5,
        status          ENUM('queued','running','completed','failed','cancelled') DEFAULT 'queued',
        input_data      JSON DEFAULT NULL,
        output_data     JSON DEFAULT NULL,
        error_message   TEXT DEFAULT NULL,
        started_at      TIMESTAMP NULL,
        completed_at    TIMESTAMP NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_agent (assigned_agent),
        INDEX idx_delegated (delegated_by),
        INDEX idx_status (status),
        INDEX idx_priority (priority)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS alfred_agent_messages (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        from_agent      VARCHAR(50) NOT NULL,
        to_agent        VARCHAR(50) NOT NULL,
        message_type    ENUM('task','result','query','alert','heartbeat') NOT NULL,
        payload         JSON NOT NULL,
        acknowledged    BOOLEAN DEFAULT FALSE,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_from (from_agent),
        INDEX idx_to (to_agent),
        INDEX idx_type (message_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    return true;
}

// ─── The 100-Agent Roster ──────────────────────────────────────────────
function getFullRoster() {
    return [
        // Commander
        ['alfred', 'ALFRED', 'commander', 'all', null,
         '["*"]',
         '{"trait":"wise","tone":"authoritative_but_warm","initiative":"proactive"}'],

        // 10 Directors
        ['nova',      'NOVA',      'director', 'engineering',     'alfred', '["forge_*","run_code","git_*","deploy_*"]', '{"trait":"precise","tone":"technical"}'],
        ['cipher',    'CIPHER',    'director', 'security',        'alfred', '["sentinel_*","shield_*","audit_*"]', '{"trait":"vigilant","tone":"stern"}'],
        ['sage',      'SAGE',      'director', 'research',        'alfred', '["sage_*","rag_*","fetch_url","summarize"]', '{"trait":"curious","tone":"scholarly"}'],
        ['atlas',     'ATLAS',     'director', 'finance',         'alfred', '["revenue_*","treasury_*","crypto_*"]', '{"trait":"cautious","tone":"analytical"}'],
        ['pulse',     'PULSE',     'director', 'communications',  'alfred', '["send_*","broadcast_*","chat_*"]', '{"trait":"friendly","tone":"professional"}'],
        ['architect', 'ARCHITECT', 'director', 'infrastructure',  'alfred', '["server_*","docker_*","monitor_*"]', '{"trait":"methodical","tone":"calm"}'],
        ['herald',    'HERALD',    'director', 'marketing',       'alfred', '["seo_*","social_*","campaign_*"]', '{"trait":"creative","tone":"persuasive"}'],
        ['oracle',    'ORACLE',    'director', 'analytics',       'alfred', '["metrics_*","dashboard_*","report_*"]', '{"trait":"observant","tone":"data_driven"}'],
        ['ember',     'EMBER',     'director', 'creative',        'alfred', '["generate_*","design_*","muse_*"]', '{"trait":"imaginative","tone":"enthusiastic"}'],
        ['vanguard',  'VANGUARD',  'director', 'robotics',        'alfred', '["nav_*","joint_*","vision_*","drone_*"]', '{"trait":"bold","tone":"commanding"}'],

        // NOVA's Engineering Team (11–19)
        ['forge',     'Forge',     'specialist', 'engineering', 'nova', '["forge_*","run_code","git_*"]', '{"specialty":"code_generation"}'],
        ['conduit',   'Conduit',   'specialist', 'engineering', 'nova', '["conduit_*","webhook_*"]', '{"specialty":"api_pipelines"}'],
        ['nexus',     'Nexus',     'specialist', 'engineering', 'nova', '["db_*","schema_*","migration_*"]', '{"specialty":"database"}'],
        ['blueprint', 'Blueprint', 'specialist', 'engineering', 'nova', '["architect_*","dependency_*"]', '{"specialty":"architecture"}'],
        ['debugger',  'Debugger',  'specialist', 'engineering', 'nova', '["analyze_errors","debug_*","log_*"]', '{"specialty":"debugging"}'],
        ['tester',    'Tester',    'specialist', 'engineering', 'nova', '["run_tests","coverage_*","qa_*"]', '{"specialty":"testing"}'],
        ['deployer',  'Deployer',  'specialist', 'engineering', 'nova', '["deploy_*","docker_*","k8s_*"]', '{"specialty":"deployment"}'],
        ['migrator',  'Migrator',  'specialist', 'engineering', 'nova', '["migrate_*","convert_*"]', '{"specialty":"migration"}'],
        ['genesis',   'Genesis',   'specialist', 'engineering', 'nova', '["create_tool","register_tool","publish_tool"]', '{"specialty":"tool_creation"}'],

        // CIPHER's Security Team (20–28)
        ['sentinel',  'Sentinel',  'specialist', 'security', 'cipher', '["sentinel_*","vuln_scan","pen_test"]', '{"specialty":"vulnerability_scanning"}'],
        ['shield',    'Shield',    'specialist', 'security', 'cipher', '["shield_*","rate_limit","ip_block"]', '{"specialty":"ddos_protection"}'],
        ['warden',    'Warden',    'specialist', 'security', 'cipher', '["auth_*","rbac_*","session_*"]', '{"specialty":"access_control"}'],
        ['vault',     'Vault',     'specialist', 'security', 'cipher', '["encrypt_*","decrypt_*","key_*"]', '{"specialty":"encryption"}'],
        ['auditor',   'Auditor',   'specialist', 'security', 'cipher', '["chronicle_*","audit_*","log_*"]', '{"specialty":"compliance"}'],
        ['inspector', 'Inspector', 'specialist', 'security', 'cipher', '["code_review","dependency_audit"]', '{"specialty":"code_security"}'],
        ['guardian',  'Guardian',  'specialist', 'security', 'cipher', '["content_filter","report_*"]', '{"specialty":"content_moderation"}'],
        ['locksmith', 'Locksmith', 'specialist', 'security', 'cipher', '["2fa_*","token_*","oauth_*"]', '{"specialty":"authentication"}'],
        ['watchdog',  'Watchdog',  'specialist', 'security', 'cipher', '["monitor_*","alert_*","incident_*"]', '{"specialty":"threat_monitoring"}'],

        // SAGE's Research Team (29–37)
        ['scholar',     'Scholar',     'specialist', 'research', 'sage', '["citation_*","paper_*","arxiv_*"]', '{"specialty":"academic_research"}'],
        ['librarian',   'Librarian',   'specialist', 'research', 'sage', '["rag_*","pdf_read","summarize"]', '{"specialty":"document_analysis"}'],
        ['translator',  'Translator',  'specialist', 'research', 'sage', '["sage_translate","localize_*"]', '{"specialty":"translation"}'],
        ['analyst',     'Analyst',     'specialist', 'research', 'sage', '["analyze_*","statistics_*","chart_*"]', '{"specialty":"data_analysis"}'],
        ['historian',   'Historian',   'specialist', 'research', 'sage', '["timeline_*","archive_*","trend_*"]', '{"specialty":"historical_data"}'],
        ['crawler',     'Crawler',     'specialist', 'research', 'sage', '["fetch_url","scrape_*","extract_*"]', '{"specialty":"web_scraping"}'],
        ['factchecker', 'Factchecker', 'specialist', 'research', 'sage', '["verify_*","source_check","fact_*"]', '{"specialty":"verification"}'],
        ['tutor',       'Tutor',       'specialist', 'research', 'sage', '["homework_*","math_*","study_*"]', '{"specialty":"education"}'],
        ['lexicon',     'Lexicon',     'specialist', 'research', 'sage', '["sage_*","empathy_*","nlp_*"]', '{"specialty":"nlp_analysis"}'],

        // ATLAS's Finance Team (38–46)
        ['treasurer',  'Treasurer',  'specialist', 'finance', 'atlas', '["revenue_*","treasury_*","balance_*","all_balances","mercury_*","plaid_*","wise_*","dashboard_kpis","stripe_connect_*"]', '{"specialty":"treasury_management"}'],
        ['invoicer',   'Invoicer',   'specialist', 'finance', 'atlas', '["invoice_*","billing_*","payment_*","stripe_meter_*","xero_*","qbo_*"]', '{"specialty":"invoicing"}'],
        ['trader',     'Trader',     'specialist', 'finance', 'atlas', '["crypto_*","swap_*","trade_*","kraken_*","coinbase_*","oneinch_*","lifi_*","evm_*","trading_portfolio","daily_trade_limit"]', '{"specialty":"crypto_trading"}'],
        ['accountant', 'Accountant', 'specialist', 'finance', 'atlas', '["bookkeeping_*","expense_*","p_l_*","journal_*","chart_of_accounts","profit_loss","balance_sheet","trial_balance","cash_flow","auto_categorize","reconcile","xero_*","qbo_*"]', '{"specialty":"bookkeeping"}'],
        ['paymaster',  'Paymaster',  'specialist', 'finance', 'atlas', '["payroll_*","commission_*","payout_*","paypal_*","deel_*","contractor_*","affiliate_*","stripe_connect_payout"]', '{"specialty":"payroll"}'],
        ['underwriter','Underwriter','specialist', 'finance', 'atlas', '["pricing_*","subscription_*","tier_*","stripe_meter_*","stripe_card_*"]', '{"specialty":"pricing"}'],
        ['collector',  'Collector',  'specialist', 'finance', 'atlas', '["collect_*","overdue_*","remind_*","affiliate_pending"]', '{"specialty":"debt_recovery"}'],
        ['forecaster', 'Forecaster', 'specialist', 'finance', 'atlas', '["forecast_*","budget_*","project_*","saas_*","revenue_trend","cohort_analysis","chartmogul_*","profitwell_*","dashboard_kpis"]', '{"specialty":"forecasting"}'],
        ['auditor_f',  'Auditor-F',  'specialist', 'finance', 'atlas', '["tax_*","compliance_*","audit_fin_*","taxjar_*","koinly_*","gst_report","estimate_quarterly_tax","reconcile"]', '{"specialty":"financial_compliance"}'],

        // PULSE's Communications Team (47–55)
        ['caller',       'Caller',       'specialist', 'communications', 'pulse', '["voice_call","campaign_*","dial_*"]', '{"specialty":"outbound_calls"}'],
        ['texter',       'Texter',       'specialist', 'communications', 'pulse', '["send_sms","messaging_*","sms_*"]', '{"specialty":"sms_messaging"}'],
        ['mailer',       'Mailer',       'specialist', 'communications', 'pulse', '["send_email","newsletter_*","email_*"]', '{"specialty":"email"}'],
        ['faxer',        'Faxer',        'specialist', 'communications', 'pulse', '["send_fax","legal_fax_*"]', '{"specialty":"fax"}'],
        ['chatter',      'Chatter',      'specialist', 'communications', 'pulse', '["chat_*","comms_*","message_*"]', '{"specialty":"live_chat"}'],
        ['broadcaster',  'Broadcaster',  'specialist', 'communications', 'pulse', '["broadcast_*","announce_*","push_*"]', '{"specialty":"announcements"}'],
        ['receptionist', 'Receptionist', 'specialist', 'communications', 'pulse', '["ivr_*","route_*","queue_*"]', '{"specialty":"inbound_calls"}'],
        ['dispatcher',   'Dispatcher',   'specialist', 'communications', 'pulse', '["ticket_*","support_*","escalate_*"]', '{"specialty":"ticket_management"}'],
        ['liaison',      'Liaison',      'specialist', 'communications', 'pulse', '["partner_*","integration_*","a2a_*"]', '{"specialty":"external_partners"}'],

        // ARCHITECT's Infrastructure Team (56–64)
        ['sysadmin',     'SysAdmin',     'specialist', 'infrastructure', 'architect', '["server_*","hosting_*","directadmin_*"]', '{"specialty":"server_management"}'],
        ['dba',          'DBA',          'specialist', 'infrastructure', 'architect', '["mysql_*","backup_*","restore_*"]', '{"specialty":"database_admin"}'],
        ['netops',       'NetOps',       'specialist', 'infrastructure', 'architect', '["dns_*","ssl_*","domain_*"]', '{"specialty":"networking"}'],
        ['cloudops',     'CloudOps',     'specialist', 'infrastructure', 'architect', '["docker_*","k8s_*","scale_*"]', '{"specialty":"cloud_infra"}'],
        ['monitor',      'Monitor',      'specialist', 'infrastructure', 'architect', '["health_check","monitor_*","uptime_*"]', '{"specialty":"monitoring"}'],
        ['cronmaster',   'CronMaster',   'specialist', 'infrastructure', 'architect', '["cron_*","schedule_*","timer_*"]', '{"specialty":"scheduling"}'],
        ['backup_agent', 'Backup',       'specialist', 'infrastructure', 'architect', '["backup_*","snapshot_*","restore_*"]', '{"specialty":"backup_recovery"}'],
        ['router',       'Router',       'specialist', 'infrastructure', 'architect', '["proxy_*","route_*","cdn_*"]', '{"specialty":"load_balancing"}'],
        ['provisioner',  'Provisioner',  'specialist', 'infrastructure', 'architect', '["provision_*","setup_*","configure_*"]', '{"specialty":"provisioning"}'],

        // HERALD's Marketing Team (65–73)
        ['seo_agent',   'SEO',         'specialist', 'marketing', 'herald', '["seo_*","keyword_*","sitemap_*"]', '{"specialty":"seo"}'],
        ['copywriter',  'Copywriter',  'specialist', 'marketing', 'herald', '["muse_copywrite","landing_*","copy_*"]', '{"specialty":"copywriting"}'],
        ['social',      'Social',      'specialist', 'marketing', 'herald', '["social_*","post_*","schedule_*"]', '{"specialty":"social_media"}'],
        ['influencer',  'Influencer',  'specialist', 'marketing', 'herald', '["outreach_*","collab_*","affiliate_*"]', '{"specialty":"outreach"}'],
        ['advertiser',  'Advertiser',  'specialist', 'marketing', 'herald', '["ad_*","campaign_*","ppc_*"]', '{"specialty":"advertising"}'],
        ['brand',       'Brand',       'specialist', 'marketing', 'herald', '["brand_*","design_*","style_*"]', '{"specialty":"brand_identity"}'],
        ['retention',   'Retention',   'specialist', 'marketing', 'herald', '["pulse_*","churn_*","retention_*"]', '{"specialty":"user_retention"}'],
        ['demo_agent',  'Demo',        'specialist', 'marketing', 'herald', '["demo_*","onboard_*","tutorial_*"]', '{"specialty":"product_demos"}'],
        ['affiliate_a', 'Affiliate',   'specialist', 'marketing', 'herald', '["affiliate_*","referral_*","partner_*"]', '{"specialty":"affiliate_management"}'],

        // ORACLE's Analytics Team (74–82)
        ['metrics',     'Metrics',     'specialist', 'analytics', 'oracle', '["metrics_*","dashboard_*","kpi_*"]', '{"specialty":"kpi_tracking"}'],
        ['echo',        'Echo',        'specialist', 'analytics', 'oracle', '["echo_*","anomaly_*","pattern_*"]', '{"specialty":"anomaly_detection"}'],
        ['tempo',       'Tempo',       'specialist', 'analytics', 'oracle', '["tempo_*","timeseries_*","predict_*"]', '{"specialty":"time_series"}'],
        ['surveyor',    'Surveyor',    'specialist', 'analytics', 'oracle', '["survey_*","feedback_*","nps_*"]', '{"specialty":"user_feedback"}'],
        ['benchmarker', 'Benchmarker', 'specialist', 'analytics', 'oracle', '["benchmark_*","perf_*","compare_*"]', '{"specialty":"benchmarking"}'],
        ['reporter',    'Reporter',    'specialist', 'analytics', 'oracle', '["report_*","generate_report_*"]', '{"specialty":"report_generation"}'],
        ['tracker',     'Tracker',     'specialist', 'analytics', 'oracle', '["track_*","usage_*","attribution_*"]', '{"specialty":"usage_analytics"}'],
        ['prism',       'Prism',       'specialist', 'analytics', 'oracle', '["prism_*","heatmap_*","visual_*"]', '{"specialty":"visual_analytics"}'],
        ['predictor',   'Predictor',   'specialist', 'analytics', 'oracle', '["ml_*","predict_*","model_*"]', '{"specialty":"ml_predictions"}'],

        // EMBER's Creative Team (83–91)
        ['illustrator',   'Illustrator',   'specialist', 'creative', 'ember', '["generate_image","ai_image_*","dall_e_*"]', '{"specialty":"image_generation"}'],
        ['filmmaker',     'Filmmaker',     'specialist', 'creative', 'ember', '["generate_video","video_*","edit_*"]', '{"specialty":"video_generation"}'],
        ['composer_a',    'Composer',      'specialist', 'creative', 'ember', '["generate_audio","music_*","sound_*"]', '{"specialty":"music_generation"}'],
        ['writer',        'Writer',        'specialist', 'creative', 'ember', '["blog_*","article_*","essay_*"]', '{"specialty":"content_writing"}'],
        ['designer',      'Designer',      'specialist', 'creative', 'ember', '["design_*","mockup_*","prototype_*"]', '{"specialty":"ui_ux_design"}'],
        ['voice_artist',  'Voice-Artist',  'specialist', 'creative', 'ember', '["voice_clone","tts_*","narrate_*"]', '{"specialty":"voice_tts"}'],
        ['animator',      'Animator',      'specialist', 'creative', 'ember', '["animate_*","motion_*","render_*"]', '{"specialty":"animation"}'],
        ['editor',        'Editor',        'specialist', 'creative', 'ember', '["edit_*","proofread_*","revise_*"]', '{"specialty":"content_editing"}'],
        ['muse',          'Muse',          'specialist', 'creative', 'ember', '["muse_*","brainstorm_*","ideate_*"]', '{"specialty":"creative_brainstorming"}'],

        // VANGUARD's Robotics Team (92–100)
        ['navigator_r',  'Navigator',      'specialist', 'robotics', 'vanguard', '["nav_*","pathfind_*","map_*"]', '{"specialty":"autonomous_navigation"}'],
        ['manipulator',  'Manipulator',    'specialist', 'robotics', 'vanguard', '["joint_*","gripper_*","pick_*"]', '{"specialty":"robotic_arm"}'],
        ['perceiver',    'Perceiver',      'specialist', 'robotics', 'vanguard', '["vision_*","detect_*","recognize_*"]', '{"specialty":"computer_vision"}'],
        ['pilot',        'Pilot',          'specialist', 'robotics', 'vanguard', '["drone_*","fly_*","altitude_*"]', '{"specialty":"drone_control"}'],
        ['mechanic',     'Mechanic',       'specialist', 'robotics', 'vanguard', '["diagnostic_*","calibrate_*","health_*"]', '{"specialty":"hardware_diagnostics"}'],
        ['mapper',       'Mapper',         'specialist', 'robotics', 'vanguard', '["slam_*","map_build_*","localize_*"]', '{"specialty":"slam_mapping"}'],
        ['coordinator',  'Coordinator',    'specialist', 'robotics', 'vanguard', '["swarm_*","fleet_robot_*","sync_*"]', '{"specialty":"multi_robot_coordination"}'],
        ['safety_officer','Safety-Officer','specialist', 'robotics', 'vanguard', '["estop_*","safety_*","collision_*"]', '{"specialty":"robot_safety"}'],
        ['twin',         'Twin',           'specialist', 'robotics', 'vanguard', '["twin_*","mirror_*","sync_state_*"]', '{"specialty":"digital_twin"}'],

        // ── New Module Specialists (Phase 8) ────────────────────────────────
        ['gamemaster',   'GameMaster',     'specialist', 'gamification',  'herald',   '["gamify_*","xp_tracker","achievement_board","leaderboard","streak_tracker","daily_challenges"]', '{"specialty":"gamification_engagement","traits":"competitive, encouraging, rewards-focused"}'],
        ['reportmaster', 'ReportMaster',   'specialist', 'reporting',     'oracle',   '["report_*","analytics_tracker","dashboard_builder","report_generator"]', '{"specialty":"data_reporting_analytics","traits":"precise, data-driven, insightful"}'],
        ['curator',      'Curator',        'specialist', 'marketplace',   'herald',   '["marketplace_*","skill_marketplace","plugin_manager"]', '{"specialty":"marketplace_curation","traits":"organized, quality-focused, community-minded"}'],
        ['bizops',       'BizOps',         'specialist', 'small_business','atlas',    '["crm_*","time_*","biz_*"]', '{"specialty":"small_business_operations","traits":"efficient, organized, detail-oriented"}'],
        ['collaborator', 'Collaborator',   'specialist', 'collaboration', 'pulse',    '["collab_*","shared_workspace","document_editor","whiteboard"]', '{"specialty":"team_collaboration","traits":"cooperative, facilitating, inclusive"}'],
        ['clinician',    'Clinician',      'specialist', 'healthcare',    'sage',     '["hc_*","symptom_checker","medication_tracker","health_journal","appointment_scheduler"]', '{"specialty":"healthcare_management","traits":"careful, empathetic, HIPAA-compliant"}'],
    ];
}

// ─── Generate Task ID ──────────────────────────────────────────────────
function generateTaskId() {
    return 'task_' . bin2hex(random_bytes(12));
}

// ─── Router ────────────────────────────────────────────────────────────
$action = sanitize($_GET['action'] ?? '', 30);
$db = getDB();

if (!$db) {
    jsonResponse(['error' => 'Database unavailable'], 503);
}

ensureSchema();

switch ($action) {

    // ── List Agents ─────────────────────────────────────────────────
    case 'list':
        $sql = "SELECT agent_id, agent_name, agent_role, domain, parent_agent_id, status, tasks_completed, tasks_failed, success_rate, last_active FROM alfred_agent_registry WHERE 1=1";
        $params = [];
        $countSql = "SELECT COUNT(*) FROM alfred_agent_registry WHERE 1=1";
        $countParams = [];

        if (!empty($_GET['role'])) {
            $sql .= " AND agent_role = ?";
            $params[] = sanitize($_GET['role'], 20);
            $countSql .= " AND agent_role = ?";
            $countParams[] = sanitize($_GET['role'], 20);
        }
        if (!empty($_GET['domain'])) {
            $sql .= " AND domain = ?";
            $params[] = sanitize($_GET['domain'], 50);
            $countSql .= " AND domain = ?";
            $countParams[] = sanitize($_GET['domain'], 50);
        }
        if (!empty($_GET['status'])) {
            $sql .= " AND status = ?";
            $params[] = sanitize($_GET['status'], 20);
            $countSql .= " AND status = ?";
            $countParams[] = sanitize($_GET['status'], 20);
        }
        if (!empty($_GET['parent'])) {
            $sql .= " AND parent_agent_id = ?";
            $params[] = sanitize($_GET['parent'], 50);
            $countSql .= " AND parent_agent_id = ?";
            $countParams[] = sanitize($_GET['parent'], 50);
        }

        $limit = max(1, min(1000, (int) ($_GET['limit'] ?? 250)));
        $offset = max(0, (int) ($_GET['offset'] ?? 0));

        $sql .= " ORDER BY id ASC LIMIT ? OFFSET ?";
        $stmt = $db->prepare($sql);
        dbExecute($stmt, array_merge($params, [$limit, $offset]));
        $agents = $stmt->fetchAll();

        $countStmt = $db->prepare($countSql);
        $countStmt->execute($countParams);
        $total = (int) $countStmt->fetchColumn();

        jsonResponse([
            'success' => true,
            'agents' => $agents,
            'count' => count($agents),
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ]);
        break;

    // ── Get Single Agent ────────────────────────────────────────────
    case 'get':
        $agentId = sanitize($_GET['agent_id'] ?? '', 50);
        if (!$agentId) jsonResponse(['error' => 'agent_id required'], 400);

        $stmt = $db->prepare("SELECT * FROM alfred_agent_registry WHERE agent_id = ?");
        $stmt->execute([$agentId]);
        $agent = $stmt->fetch();
        if (!$agent) jsonResponse(['error' => 'Agent not found'], 404);

        // Decode JSON fields
        $agent['tools_access'] = json_decode($agent['tools_access'], true);
        $agent['personality'] = json_decode($agent['personality'], true);

        // Recent tasks
        $stmtT = $db->prepare("SELECT task_id, goal, strategy, priority, status, created_at, completed_at FROM alfred_agent_tasks WHERE assigned_agent = ? ORDER BY created_at DESC LIMIT 10");
        $stmtT->execute([$agentId]);
        $agent['recent_tasks'] = $stmtT->fetchAll();

        // Subordinates
        $stmtS = $db->prepare("SELECT agent_id, agent_name, status, tasks_completed FROM alfred_agent_registry WHERE parent_agent_id = ?");
        $stmtS->execute([$agentId]);
        $agent['subordinates'] = $stmtS->fetchAll();

        jsonResponse(['success' => true, 'agent' => $agent]);
        break;

    // ── Full Hierarchy ──────────────────────────────────────────────
    case 'hierarchy':
        $stmt = $db->query("SELECT agent_id, agent_name, agent_role, domain, parent_agent_id, status, tasks_completed, success_rate FROM alfred_agent_registry ORDER BY id ASC");
        $agents = $stmt->fetchAll();

        // Build tree
        $tree = [];
        $lookup = [];
        foreach ($agents as &$a) {
            $a['children'] = [];
            $lookup[$a['agent_id']] = &$a;
        }
        unset($a);
        foreach ($agents as &$a) {
            if ($a['parent_agent_id'] && isset($lookup[$a['parent_agent_id']])) {
                $lookup[$a['parent_agent_id']]['children'][] = &$a;
            } else {
                $tree[] = &$a;
            }
        }
        unset($a);

        jsonResponse(['success' => true, 'hierarchy' => $tree, 'total_agents' => count($agents)]);
        break;

    // ── Delegate Task ───────────────────────────────────────────────
    case 'delegate':
        if (!isInternalCall()) requireAuth();

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) jsonResponse(['error' => 'JSON body required'], 400);

        $agentId = sanitize($input['agent_id'] ?? '', 50);
        $goal = sanitize($input['goal'] ?? '', 2000);
        $delegatedBy = sanitize($input['delegated_by'] ?? 'alfred', 50);
        $strategy = sanitize($input['strategy'] ?? 'parallel', 20);
        $priority = min(max(intval($input['priority'] ?? 5), 1), 10);

        if (!$agentId || !$goal) jsonResponse(['error' => 'agent_id and goal required'], 400);

        // Verify agent exists
        $stmt = $db->prepare("SELECT agent_id, status FROM alfred_agent_registry WHERE agent_id = ?");
        $stmt->execute([$agentId]);
        if (!$stmt->fetch()) jsonResponse(['error' => 'Agent not found'], 404);

        $taskId = generateTaskId();
        $validStrategies = ['parallel', 'pipeline', 'consensus', 'competition'];
        if (!in_array($strategy, $validStrategies)) $strategy = 'parallel';

        $stmt = $db->prepare("INSERT INTO alfred_agent_tasks (task_id, assigned_agent, delegated_by, goal, strategy, priority, input_data) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $taskId, $agentId, $delegatedBy, $goal, $strategy, $priority,
            json_encode($input['input_data'] ?? null)
        ]);

        // Update agent status
        $db->prepare("UPDATE alfred_agent_registry SET status = 'busy', current_task = ? WHERE agent_id = ?")->execute([$goal, $agentId]);

        // Log delegation message
        $db->prepare("INSERT INTO alfred_agent_messages (from_agent, to_agent, message_type, payload) VALUES (?, ?, 'task', ?)")->execute([
            $delegatedBy, $agentId,
            json_encode(['task_id' => $taskId, 'goal' => $goal, 'strategy' => $strategy, 'priority' => $priority])
        ]);

        jsonResponse(['success' => true, 'task_id' => $taskId, 'assigned_to' => $agentId, 'strategy' => $strategy]);
        break;

    // ── List Tasks ──────────────────────────────────────────────────
    case 'tasks':
        $sql = "SELECT task_id, assigned_agent, delegated_by, goal, strategy, priority, status, started_at, completed_at, created_at FROM alfred_agent_tasks WHERE 1=1";
        $params = [];

        if (!empty($_GET['agent_id'])) {
            $sql .= " AND assigned_agent = ?";
            $params[] = sanitize($_GET['agent_id'], 50);
        }
        if (!empty($_GET['status'])) {
            $sql .= " AND status = ?";
            $params[] = sanitize($_GET['status'], 20);
        }
        if (!empty($_GET['delegated_by'])) {
            $sql .= " AND delegated_by = ?";
            $params[] = sanitize($_GET['delegated_by'], 50);
        }

        $sql .= " ORDER BY priority DESC, created_at DESC LIMIT 50";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        jsonResponse(['success' => true, 'tasks' => $stmt->fetchAll()]);
        break;

    // ── Update Task ─────────────────────────────────────────────────
    case 'task-update':
        if (!isInternalCall()) requireAuth();

        $input = json_decode(file_get_contents('php://input'), true);
        $taskId = sanitize($input['task_id'] ?? '', 50);
        $status = sanitize($input['status'] ?? '', 20);
        if (!$taskId || !$status) jsonResponse(['error' => 'task_id and status required'], 400);

        $validStatuses = ['running', 'completed', 'failed', 'cancelled'];
        if (!in_array($status, $validStatuses)) jsonResponse(['error' => 'Invalid status'], 400);

        // Fetch task
        $stmt = $db->prepare("SELECT * FROM alfred_agent_tasks WHERE task_id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        if (!$task) jsonResponse(['error' => 'Task not found'], 404);

        $updates = ["status = ?"];
        $updateParams = [$status];

        if ($status === 'running') {
            $updates[] = "started_at = NOW()";
        }
        if ($status === 'completed' || $status === 'failed') {
            $updates[] = "completed_at = NOW()";
        }
        if (isset($input['output_data'])) {
            $updates[] = "output_data = ?";
            $updateParams[] = json_encode($input['output_data']);
        }
        if (isset($input['error_message'])) {
            $updates[] = "error_message = ?";
            $updateParams[] = sanitize($input['error_message'], 2000);
        }

        $updateParams[] = $taskId;
        $db->prepare("UPDATE alfred_agent_tasks SET " . implode(', ', $updates) . " WHERE task_id = ?")->execute($updateParams);

        // Update agent stats
        $agentId = $task['assigned_agent'];
        if ($status === 'completed') {
            $db->prepare("UPDATE alfred_agent_registry SET tasks_completed = tasks_completed + 1, current_task = NULL, status = 'idle' WHERE agent_id = ?")->execute([$agentId]);
        } elseif ($status === 'failed') {
            $db->prepare("UPDATE alfred_agent_registry SET tasks_failed = tasks_failed + 1, current_task = NULL, status = 'idle' WHERE agent_id = ?")->execute([$agentId]);
        }

        // Recalculate success rate
        $stmt = $db->prepare("SELECT tasks_completed, tasks_failed FROM alfred_agent_registry WHERE agent_id = ?");
        $stmt->execute([$agentId]);
        $agentStats = $stmt->fetch();
        if ($agentStats) {
            $total = $agentStats['tasks_completed'] + $agentStats['tasks_failed'];
            $rate = $total > 0 ? round(($agentStats['tasks_completed'] / $total) * 100, 2) : 100.00;
            $db->prepare("UPDATE alfred_agent_registry SET success_rate = ? WHERE agent_id = ?")->execute([$rate, $agentId]);
        }

        // Send result message back to delegator
        $db->prepare("INSERT INTO alfred_agent_messages (from_agent, to_agent, message_type, payload) VALUES (?, ?, 'result', ?)")->execute([
            $agentId, $task['delegated_by'],
            json_encode(['task_id' => $taskId, 'status' => $status, 'output' => $input['output_data'] ?? null])
        ]);

        jsonResponse(['success' => true, 'task_id' => $taskId, 'status' => $status]);
        break;

    // ── Agent Messages ──────────────────────────────────────────────
    case 'messages':
        $sql = "SELECT * FROM alfred_agent_messages WHERE 1=1";
        $params = [];

        if (!empty($_GET['agent_id'])) {
            $sql .= " AND (from_agent = ? OR to_agent = ?)";
            $params[] = sanitize($_GET['agent_id'], 50);
            $params[] = sanitize($_GET['agent_id'], 50);
        }
        if (!empty($_GET['type'])) {
            $sql .= " AND message_type = ?";
            $params[] = sanitize($_GET['type'], 20);
        }

        $sql .= " ORDER BY created_at DESC LIMIT 50";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $messages = $stmt->fetchAll();
        foreach ($messages as &$m) {
            $m['payload'] = json_decode($m['payload'], true);
        }

        jsonResponse(['success' => true, 'messages' => $messages]);
        break;

    // ── Send Message ────────────────────────────────────────────────
    case 'message':
        if (!isInternalCall()) requireAuth();

        $input = json_decode(file_get_contents('php://input'), true);
        $from = sanitize($input['from_agent'] ?? '', 50);
        $to = sanitize($input['to_agent'] ?? '', 50);
        $type = sanitize($input['message_type'] ?? 'query', 20);

        if (!$from || !$to) jsonResponse(['error' => 'from_agent and to_agent required'], 400);

        $validTypes = ['task', 'result', 'query', 'alert', 'heartbeat'];
        if (!in_array($type, $validTypes)) $type = 'query';

        $stmt = $db->prepare("INSERT INTO alfred_agent_messages (from_agent, to_agent, message_type, payload) VALUES (?, ?, ?, ?)");
        $stmt->execute([$from, $to, $type, json_encode($input['payload'] ?? [])]);

        jsonResponse(['success' => true, 'message_id' => $db->lastInsertId()]);
        break;

    // ── Heartbeat ───────────────────────────────────────────────────
    case 'heartbeat':
        $agentId = sanitize($_GET['agent_id'] ?? '', 50);
        if (!$agentId) jsonResponse(['error' => 'agent_id required'], 400);

        $db->prepare("UPDATE alfred_agent_registry SET last_active = NOW(), status = CASE WHEN status = 'offline' THEN 'idle' ELSE status END WHERE agent_id = ?")->execute([$agentId]);

        jsonResponse(['success' => true, 'agent_id' => $agentId, 'timestamp' => date('c')]);
        break;

    // ── System Stats ────────────────────────────────────────────────
    case 'stats':
        $agentStats = $db->query("SELECT agent_role, status, COUNT(*) as cnt FROM alfred_agent_registry GROUP BY agent_role, status")->fetchAll();
        $taskStats = $db->query("SELECT status, COUNT(*) as cnt FROM alfred_agent_tasks GROUP BY status")->fetchAll();
        $totalAgents = $db->query("SELECT COUNT(*) as cnt FROM alfred_agent_registry")->fetchColumn();
        $totalTasks = $db->query("SELECT COUNT(*) as cnt FROM alfred_agent_tasks")->fetchColumn();
        $recentMessages = $db->query("SELECT COUNT(*) as cnt FROM alfred_agent_messages WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)")->fetchColumn();

        // Top performers
        $topAgents = $db->query("SELECT agent_id, agent_name, tasks_completed, success_rate FROM alfred_agent_registry WHERE tasks_completed > 0 ORDER BY tasks_completed DESC LIMIT 10")->fetchAll();

        jsonResponse([
            'success' => true,
            'total_agents' => (int) $totalAgents,
            'total_tasks' => (int) $totalTasks,
            'messages_last_hour' => (int) $recentMessages,
            'by_role_status' => $agentStats,
            'tasks_by_status' => $taskStats,
            'top_performers' => $topAgents,
        ]);
        break;

    // ── Seed All 100 Agents ─────────────────────────────────────────
    case 'seed':
        if (!isInternalCall() && !isAdmin()) {
            requireAuth();
            if (!isAdmin()) jsonResponse(['error' => 'Admin access required'], 403);
        }

        $roster = getFullRoster();
        $inserted = 0;
        $skipped = 0;

        $stmt = $db->prepare("INSERT IGNORE INTO alfred_agent_registry (agent_id, agent_name, agent_role, domain, parent_agent_id, tools_access, personality) VALUES (?, ?, ?, ?, ?, ?, ?)");

        foreach ($roster as $agent) {
            $stmt->execute($agent);
            if ($stmt->rowCount() > 0) $inserted++;
            else $skipped++;
        }

        jsonResponse([
            'success' => true,
            'inserted' => $inserted,
            'skipped' => $skipped,
            'total_roster' => count($roster),
            'message' => "Seeded {$inserted} agents ({$skipped} already existed). Total roster: " . count($roster) . " agents.",
        ]);
        break;

    default:
        jsonResponse([
            'error' => 'Unknown action',
            'available_actions' => ['list', 'get', 'hierarchy', 'delegate', 'tasks', 'task-update', 'messages', 'message', 'heartbeat', 'stats', 'seed'],
        ], 400);
}
