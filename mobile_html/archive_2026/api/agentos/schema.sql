-- ═══════════════════════════════════════════════════════════════
-- GSM Alfred OS — Database Schema v1.0
-- The operating system layer for autonomous AI agents
-- ═══════════════════════════════════════════════════════════════

-- ── 1. CAPABILITY GRAPH ─────────────────────────────────────────
-- Every action in the platform is a typed, risk-scored capability

CREATE TABLE IF NOT EXISTS agentos_capabilities (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    capability_id   VARCHAR(128) NOT NULL UNIQUE,
    display_name    VARCHAR(255) NOT NULL,
    description     TEXT,
    capability_type VARCHAR(32) NOT NULL DEFAULT 'action',
    category        VARCHAR(64) NOT NULL DEFAULT 'general',
    input_schema    JSON,
    output_schema   JSON,
    risk_level      ENUM('low','medium','high','critical') NOT NULL DEFAULT 'low',
    requires_simulation TINYINT(1) NOT NULL DEFAULT 0,
    requires_approval   TINYINT(1) NOT NULL DEFAULT 0,
    max_retries     TINYINT UNSIGNED NOT NULL DEFAULT 3,
    timeout_ms      INT UNSIGNED NOT NULL DEFAULT 30000,
    provider        VARCHAR(64) NOT NULL DEFAULT 'native',
    endpoint        VARCHAR(512),
    enabled         TINYINT(1) NOT NULL DEFAULT 1,
    version         VARCHAR(16) NOT NULL DEFAULT '1.0.0',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_risk (risk_level),
    INDEX idx_provider (provider)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 2. SKILL ENGINE ────────────────────────────────────────────
-- Reusable goal-oriented behaviors composed from capabilities

CREATE TABLE IF NOT EXISTS agentos_skills (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    skill_id        VARCHAR(128) NOT NULL UNIQUE,
    display_name    VARCHAR(255) NOT NULL,
    description     TEXT,
    category        VARCHAR(64) NOT NULL DEFAULT 'general',
    preconditions   JSON,
    postconditions  JSON,
    retry_policy    JSON,
    fallback_skill  VARCHAR(128),
    risk_level      ENUM('low','medium','high','critical') NOT NULL DEFAULT 'low',
    requires_approval TINYINT(1) NOT NULL DEFAULT 0,
    enabled         TINYINT(1) NOT NULL DEFAULT 1,
    version         VARCHAR(16) NOT NULL DEFAULT '1.0.0',
    author          VARCHAR(64) DEFAULT 'system',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS agentos_skill_steps (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    skill_id        VARCHAR(128) NOT NULL,
    step_order      SMALLINT UNSIGNED NOT NULL,
    capability_id   VARCHAR(128) NOT NULL,
    input_mapping   JSON COMMENT 'Maps skill inputs to capability inputs',
    output_mapping  JSON COMMENT 'Maps capability outputs to skill context',
    `condition`     VARCHAR(512) COMMENT 'JSONPath condition to execute this step',
    on_failure      ENUM('abort','skip','retry','fallback') NOT NULL DEFAULT 'abort',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_skill_step (skill_id, step_order),
    INDEX idx_capability (capability_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 3. TASK GRAPH (DAG) ────────────────────────────────────────
-- Multi-step plans as directed acyclic graphs

CREATE TABLE IF NOT EXISTS agentos_tasks (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id         VARCHAR(64) NOT NULL UNIQUE,
    parent_task_id  VARCHAR(64),
    user_id         INT UNSIGNED,
    agent_id        VARCHAR(64) NOT NULL DEFAULT 'alfred',
    goal            TEXT NOT NULL,
    status          ENUM('pending','planning','ready','running','paused',
                        'waiting_approval','simulating','completed',
                        'failed','cancelled','rolled_back','sandbox') NOT NULL DEFAULT 'pending',
    priority        TINYINT UNSIGNED NOT NULL DEFAULT 5,
    plan            JSON COMMENT 'The generated execution plan',
    context         JSON COMMENT 'Accumulated context during execution',
    result          JSON,
    error           TEXT,
    started_at      TIMESTAMP NULL,
    completed_at    TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_agent (agent_id),
    INDEX idx_status (status),
    INDEX idx_parent (parent_task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS agentos_task_nodes (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id         VARCHAR(64) NOT NULL,
    node_id         VARCHAR(64) NOT NULL,
    node_type       ENUM('capability','skill','subtask','decision','gate') NOT NULL,
    reference_id    VARCHAR(128) NOT NULL COMMENT 'capability_id, skill_id, or subtask_id',
    label           VARCHAR(255),
    input_data      JSON,
    output_data     JSON,
    status          ENUM('pending','running','completed','failed','skipped','blocked') NOT NULL DEFAULT 'pending',
    started_at      TIMESTAMP NULL,
    completed_at    TIMESTAMP NULL,
    duration_ms     INT UNSIGNED,
    error           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_task_node (task_id, node_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS agentos_task_edges (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id         VARCHAR(64) NOT NULL,
    from_node       VARCHAR(64) NOT NULL,
    to_node         VARCHAR(64) NOT NULL,
    edge_type       ENUM('sequence','parallel','conditional','fallback') NOT NULL DEFAULT 'sequence',
    `condition`     VARCHAR(512),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_edge (task_id, from_node, to_node),
    INDEX idx_task (task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 4. MEMORY STORE (5 Types) ──────────────────────────────────

-- Episodic: What happened in previous runs
CREATE TABLE IF NOT EXISTS agentos_memory_episodic (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED,
    agent_id        VARCHAR(64) NOT NULL DEFAULT 'alfred',
    episode_type    VARCHAR(64) NOT NULL COMMENT 'task_execution, conversation, error, discovery',
    summary         TEXT NOT NULL,
    details         JSON,
    outcome         ENUM('success','failure','partial','unknown') NOT NULL DEFAULT 'unknown',
    importance      TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT '1-10 scale',
    task_id         VARCHAR(64),
    embedding_hash  VARCHAR(64) COMMENT 'For similarity search',
    recalled_count  INT UNSIGNED NOT NULL DEFAULT 0,
    last_recalled   TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_agent (user_id, agent_id),
    INDEX idx_type (episode_type),
    INDEX idx_importance (importance DESC),
    INDEX idx_task (task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Semantic: Facts the agent has learned
CREATE TABLE IF NOT EXISTS agentos_memory_semantic (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED,
    agent_id        VARCHAR(64) NOT NULL DEFAULT 'alfred',
    domain          VARCHAR(64) NOT NULL DEFAULT 'general',
    fact_key        VARCHAR(255) NOT NULL,
    fact_value      TEXT NOT NULL,
    confidence      DECIMAL(3,2) NOT NULL DEFAULT 0.80 COMMENT '0.00-1.00',
    source          VARCHAR(255) COMMENT 'Where this fact came from',
    verified        TINYINT(1) NOT NULL DEFAULT 0,
    embedding_hash  VARCHAR(64),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_agent_fact (agent_id, domain, fact_key),
    INDEX idx_user (user_id),
    INDEX idx_domain (domain),
    INDEX idx_confidence (confidence DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Procedural: How to do tasks (learned recipes)
CREATE TABLE IF NOT EXISTS agentos_memory_procedural (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agent_id        VARCHAR(64) NOT NULL DEFAULT 'alfred',
    procedure_name  VARCHAR(255) NOT NULL,
    trigger_pattern VARCHAR(512) NOT NULL COMMENT 'When to use this procedure',
    steps           JSON NOT NULL COMMENT 'Ordered list of capability/skill calls',
    success_rate    DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    times_used      INT UNSIGNED NOT NULL DEFAULT 0,
    last_used       TIMESTAMP NULL,
    learned_from    VARCHAR(64) COMMENT 'task_id that taught this',
    enabled         TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_procedure (agent_id, procedure_name),
    INDEX idx_trigger (trigger_pattern(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Spatial: Where things are in the world
CREATE TABLE IF NOT EXISTS agentos_memory_spatial (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    world_id        VARCHAR(64) NOT NULL DEFAULT 'default',
    entity_id       VARCHAR(128) NOT NULL,
    entity_type     VARCHAR(64) NOT NULL COMMENT 'robot, object, device, zone, avatar',
    position_x      DOUBLE,
    position_y      DOUBLE,
    position_z      DOUBLE,
    orientation     JSON COMMENT 'Quaternion or euler angles',
    properties      JSON COMMENT 'Size, color, state, etc.',
    parent_entity   VARCHAR(128),
    last_observed   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    observed_by     VARCHAR(64) COMMENT 'agent_id that last saw it',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_world_entity (world_id, entity_id),
    INDEX idx_type (entity_type),
    INDEX idx_parent (parent_entity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Relational: Who owns what, what connects to what
CREATE TABLE IF NOT EXISTS agentos_memory_relational (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_type    VARCHAR(64) NOT NULL COMMENT 'user, agent, device, service, entity',
    subject_id      VARCHAR(128) NOT NULL,
    relation        VARCHAR(64) NOT NULL COMMENT 'owns, depends_on, controls, monitors, etc.',
    object_type     VARCHAR(64) NOT NULL,
    object_id       VARCHAR(128) NOT NULL,
    weight          DECIMAL(3,2) NOT NULL DEFAULT 1.00 COMMENT 'Strength of relation',
    metadata        JSON,
    valid_from      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    valid_until     TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_relation (subject_type, subject_id, relation, object_type, object_id),
    INDEX idx_subject (subject_type, subject_id),
    INDEX idx_object (object_type, object_id),
    INDEX idx_relation (relation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 5. WORLD STATE ─────────────────────────────────────────────
-- Live environment state tracking

CREATE TABLE IF NOT EXISTS agentos_world_state (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    world_id        VARCHAR(64) NOT NULL DEFAULT 'default',
    state_key       VARCHAR(255) NOT NULL,
    state_value     JSON NOT NULL,
    state_type      VARCHAR(64) NOT NULL DEFAULT 'environment',
    expected_value  JSON COMMENT 'What the agent expects (drift detection)',
    drift_detected  TINYINT(1) NOT NULL DEFAULT 0,
    observed_by     VARCHAR(64),
    observed_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_world_key (world_id, state_key),
    INDEX idx_type (state_type),
    INDEX idx_drift (drift_detected)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS agentos_world_entities (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    world_id        VARCHAR(64) NOT NULL DEFAULT 'default',
    entity_id       VARCHAR(128) NOT NULL,
    entity_type     ENUM('robot','device','sensor','avatar','object','zone','service') NOT NULL,
    display_name    VARCHAR(255),
    status          ENUM('online','offline','busy','error','idle','maintenance') NOT NULL DEFAULT 'offline',
    properties      JSON,
    capabilities    JSON COMMENT 'What this entity can do',
    twin_data       JSON COMMENT 'Digital twin state',
    last_heartbeat  TIMESTAMP NULL,
    owner_id        INT UNSIGNED,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_world_entity (world_id, entity_id),
    INDEX idx_type (entity_type),
    INDEX idx_status (status),
    INDEX idx_owner (owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 6. POLICY ENGINE ───────────────────────────────────────────
-- Safety kernel — gating, approval, risk management

CREATE TABLE IF NOT EXISTS agentos_policies (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    policy_id       VARCHAR(128) NOT NULL UNIQUE,
    display_name    VARCHAR(255) NOT NULL,
    description     TEXT,
    scope           ENUM('global','user','agent','capability','skill') NOT NULL DEFAULT 'global',
    scope_target    VARCHAR(128) COMMENT 'Specific user/agent/capability this applies to',
    priority        TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT 'Higher priority wins',
    enabled         TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS agentos_policy_rules (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    policy_id       VARCHAR(128) NOT NULL,
    rule_order      SMALLINT UNSIGNED NOT NULL,
    condition_expr  VARCHAR(1024) NOT NULL COMMENT 'JSONPath or expression',
    action          ENUM('allow','deny','require_approval','require_simulation',
                        'rate_limit','log','alert','escalate') NOT NULL,
    action_params   JSON,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_policy_rule (policy_id, rule_order),
    INDEX idx_policy (policy_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS agentos_approvals (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    approval_id     VARCHAR(64) NOT NULL UNIQUE,
    task_id         VARCHAR(64),
    node_id         VARCHAR(64),
    capability_id   VARCHAR(128),
    requested_by    VARCHAR(64) NOT NULL COMMENT 'Agent requesting',
    requested_for   INT UNSIGNED COMMENT 'User ID',
    action_summary  TEXT NOT NULL,
    risk_level      ENUM('low','medium','high','critical') NOT NULL,
    risk_score      DECIMAL(5,2),
    status          ENUM('pending','approved','denied','expired','auto_approved') NOT NULL DEFAULT 'pending',
    decided_by      VARCHAR(64),
    decided_at      TIMESTAMP NULL,
    expires_at      TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_task (task_id),
    INDEX idx_user (requested_for)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 7. SIMULATION ENGINE ───────────────────────────────────────

CREATE TABLE IF NOT EXISTS agentos_simulations (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sim_id          VARCHAR(64) NOT NULL UNIQUE,
    task_id         VARCHAR(64),
    sim_type        ENUM('dry_run','sandbox','prediction','replay') NOT NULL DEFAULT 'dry_run',
    input_state     JSON NOT NULL,
    expected_state  JSON,
    actual_state    JSON,
    actions_taken   JSON,
    outcome         ENUM('safe','unsafe','warning','error','inconclusive') NOT NULL DEFAULT 'inconclusive',
    risk_score      DECIMAL(5,2),
    anomalies       JSON,
    duration_ms     INT UNSIGNED,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_task (task_id),
    INDEX idx_outcome (outcome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 8. AUDIT TRAIL ─────────────────────────────────────────────
-- Immutable, append-only log of all agent actions

CREATE TABLE IF NOT EXISTS agentos_audit_log (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trace_id        VARCHAR(64) NOT NULL,
    task_id         VARCHAR(64),
    node_id         VARCHAR(64),
    agent_id        VARCHAR(64) NOT NULL,
    user_id         INT UNSIGNED,
    action_type     VARCHAR(64) NOT NULL COMMENT 'observe, plan, simulate, execute, verify, learn, approve, deny',
    capability_id   VARCHAR(128),
    input_summary   JSON,
    output_summary  JSON,
    decision_reason TEXT COMMENT 'Why the agent chose this action',
    risk_level      ENUM('low','medium','high','critical'),
    status          ENUM('started','completed','failed','blocked','rolled_back') NOT NULL,
    duration_ms     INT UNSIGNED,
    metadata        JSON,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_trace (trace_id),
    INDEX idx_task (task_id),
    INDEX idx_agent (agent_id),
    INDEX idx_user (user_id),
    INDEX idx_action (action_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 9. DEVICE REGISTRY ─────────────────────────────────────────

CREATE TABLE IF NOT EXISTS agentos_devices (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id       VARCHAR(128) NOT NULL UNIQUE,
    device_type     ENUM('robot','iot_sensor','iot_actuator','camera','microphone',
                        'speaker','display','controller','custom') NOT NULL,
    display_name    VARCHAR(255),
    protocol        ENUM('ros2','mqtt','http','websocket','serial','custom') NOT NULL DEFAULT 'http',
    connection_url  VARCHAR(512),
    auth_token_hash VARCHAR(64),
    capabilities    JSON COMMENT 'What this device can do',
    status          ENUM('online','offline','error','maintenance') NOT NULL DEFAULT 'offline',
    last_heartbeat  TIMESTAMP NULL,
    telemetry       JSON COMMENT 'Latest sensor readings',
    owner_id        INT UNSIGNED,
    safety_config   JSON COMMENT 'Deadman switch, limits, zones',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (device_type),
    INDEX idx_status (status),
    INDEX idx_owner (owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 10. AGENT SESSIONS ─────────────────────────────────────────

CREATE TABLE IF NOT EXISTS agentos_agent_sessions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id      VARCHAR(64) NOT NULL UNIQUE,
    agent_id        VARCHAR(64) NOT NULL,
    user_id         INT UNSIGNED,
    status          ENUM('active','idle','terminated') NOT NULL DEFAULT 'active',
    current_task_id VARCHAR(64),
    observations    JSON COMMENT 'Current sensory input buffer',
    goals           JSON COMMENT 'Active goals stack',
    context         JSON COMMENT 'Accumulated context',
    loop_count      INT UNSIGNED NOT NULL DEFAULT 0,
    last_loop_at    TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_agent (agent_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 11. TELEMETRY HISTORY ──────────────────────────────────────

CREATE TABLE IF NOT EXISTS agentos_telemetry_history (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id       VARCHAR(128) NOT NULL,
    metric_name     VARCHAR(128) NOT NULL,
    metric_value    DOUBLE NOT NULL,
    unit            VARCHAR(32),
    metadata        JSON,
    recorded_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_device_metric (device_id, metric_name),
    INDEX idx_recorded (recorded_at),
    INDEX idx_device_time (device_id, recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 12. DEVICE GROUPS ──────────────────────────────────────────

CREATE TABLE IF NOT EXISTS agentos_device_groups (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id        VARCHAR(128) NOT NULL UNIQUE,
    display_name    VARCHAR(255) NOT NULL,
    description     TEXT,
    device_ids      JSON NOT NULL COMMENT 'Array of device_ids in this group',
    metadata        JSON,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 13. DIGITAL TWIN SNAPSHOTS ─────────────────────────────────

CREATE TABLE IF NOT EXISTS agentos_twin_snapshots (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id       VARCHAR(128) NOT NULL,
    snapshot_type   ENUM('auto','manual','alert','checkpoint') NOT NULL DEFAULT 'auto',
    twin_state      JSON NOT NULL COMMENT 'Full device state at snapshot time',
    telemetry       JSON COMMENT 'Telemetry readings at snapshot time',
    trigger_event   VARCHAR(128),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_device (device_id),
    INDEX idx_type (snapshot_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
