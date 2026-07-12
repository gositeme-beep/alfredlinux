-- ============================================================================
-- ALFRED AI ASSISTANT PLATFORM — DATABASE SCHEMA
-- ============================================================================
-- File:        alfred_schema.sql
-- Engine:      MySQL 8.0+ / MariaDB 10.5+
-- Charset:     utf8mb4 (full Unicode including emoji)
-- Collation:   utf8mb4_unicode_ci
-- Created:     2026-03-04
-- Description: Production-ready schema for the Alfred upgrade as defined in
--              ALFRED_UPGRADE_MASTERPLAN.md — covers consciousness, fleets,
--              conferences, marketplace, gamification, analytics, and prefs.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

START TRANSACTION;

-- ============================================================================
-- 0. SCHEMA VERSION TRACKING
-- ============================================================================
-- Tracks every migration applied to this database so rollbacks and audits
-- are straightforward.

DROP TABLE IF EXISTS `alfred_schema_version`;
CREATE TABLE `alfred_schema_version` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `version`         VARCHAR(20)     NOT NULL COMMENT 'Semantic version (e.g. 1.0.0)',
    `description`     VARCHAR(255)    NOT NULL COMMENT 'Human-readable migration summary',
    `script_name`     VARCHAR(255)    DEFAULT NULL COMMENT 'Filename of the migration script',
    `checksum`        VARCHAR(64)     DEFAULT NULL COMMENT 'SHA-256 of the script for integrity',
    `applied_by`      VARCHAR(100)    DEFAULT 'system' COMMENT 'User or process that ran the migration',
    `execution_time_ms` INT UNSIGNED  DEFAULT 0 COMMENT 'How long the migration took',
    `applied_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_version` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tracks database schema versions and migration history';


-- ============================================================================
-- 1. ALFRED CONSCIOUSNESS
-- ============================================================================
-- Stores Alfred's per-user consciousness state: emotional modelling,
-- personality traits, learning history, and interaction memory.

DROP TABLE IF EXISTS `alfred_consciousness`;
CREATE TABLE `alfred_consciousness` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`           INT UNSIGNED    NOT NULL COMMENT 'FK to WHMCS tblclients.id',
    `emotional_state`   VARCHAR(50)     NOT NULL DEFAULT 'neutral' COMMENT 'Current emotion label (e.g. calm, excited, empathetic)',
    `mood`              VARCHAR(50)     NOT NULL DEFAULT 'balanced' COMMENT 'Longer-term mood (e.g. upbeat, focused, contemplative)',
    `energy_level`      TINYINT UNSIGNED NOT NULL DEFAULT 80 COMMENT '0-100 energy gauge',
    `learning_history`  JSON            DEFAULT NULL COMMENT 'Structured log of things Alfred has learned about this user',
    `personality_traits` JSON           DEFAULT NULL COMMENT '{"humor":0.7,"formality":0.3,"empathy":0.9, …}',
    `memory_context`    TEXT            DEFAULT NULL COMMENT 'Free-form context Alfred retains between sessions',
    `interaction_count` INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'Cumulative number of interactions with this user',
    `last_interaction`  DATETIME        DEFAULT NULL COMMENT 'Timestamp of the most recent interaction',
    `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_consciousness_user` (`user_id`),
    KEY `idx_consciousness_emotional` (`emotional_state`),
    KEY `idx_consciousness_last` (`last_interaction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Alfred consciousness state per user — emotions, personality, memory';


-- ============================================================================
-- 2. ALFRED FLEETS (Swarm Orchestration)
-- ============================================================================
-- A fleet is a coordinated group of AI agents working toward a common
-- objective under a chosen strategy.

DROP TABLE IF EXISTS `alfred_fleet_agents`;  -- child first to avoid FK issues
DROP TABLE IF EXISTS `alfred_fleets`;
CREATE TABLE `alfred_fleets` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`           INT UNSIGNED    NOT NULL COMMENT 'FK to tblclients.id — fleet owner',
    `fleet_name`        VARCHAR(100)    NOT NULL,
    `objective`         TEXT            NOT NULL COMMENT 'What the fleet is trying to accomplish',
    `status`            ENUM('idle','running','paused','completed','failed')
                                        NOT NULL DEFAULT 'idle',
    `strategy`          ENUM('parallel','pipeline','consensus','competition')
                                        NOT NULL DEFAULT 'parallel' COMMENT 'Execution strategy',
    `agent_count`       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `progress_percent`  TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0-100',
    `results`           JSON            DEFAULT NULL COMMENT 'Aggregated results payload',
    `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `completed_at`      DATETIME        DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_fleets_user`   (`user_id`),
    KEY `idx_fleets_status` (`status`),
    KEY `idx_fleets_created`(`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Fleet/swarm orchestration — groups of agents working toward an objective';


-- ============================================================================
-- 3. ALFRED FLEET AGENTS
-- ============================================================================
-- Individual agents that belong to a fleet, each with a role and task.

CREATE TABLE `alfred_fleet_agents` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `fleet_id`          INT UNSIGNED    NOT NULL COMMENT 'FK to alfred_fleets.id',
    `agent_name`        VARCHAR(100)    NOT NULL COMMENT 'Display name of the agent instance',
    `agent_role`        VARCHAR(50)     NOT NULL DEFAULT 'generalist' COMMENT 'leader, specialist, generalist, reviewer',
    `task`              TEXT            DEFAULT NULL COMMENT 'Specific task assigned to this agent',
    `status`            ENUM('queued','running','completed','failed','cancelled')
                                        NOT NULL DEFAULT 'queued',
    `result`            JSON            DEFAULT NULL COMMENT 'Output payload from the agent',
    `started_at`        DATETIME        DEFAULT NULL,
    `completed_at`      DATETIME        DEFAULT NULL,
    `error_log`         TEXT            DEFAULT NULL COMMENT 'Error details if status = failed',
    PRIMARY KEY (`id`),
    KEY `idx_fagent_fleet`  (`fleet_id`),
    KEY `idx_fagent_status` (`status`),
    CONSTRAINT `fk_fagent_fleet` FOREIGN KEY (`fleet_id`)
        REFERENCES `alfred_fleets` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Individual agents within a fleet — each has a role and task';


-- ============================================================================
-- 4. ALFRED CONFERENCES (Voice Conference Rooms)
-- ============================================================================
-- Multi-participant voice/video conference rooms with transcription and
-- agenda tracking.

DROP TABLE IF EXISTS `alfred_conferences`;
CREATE TABLE `alfred_conferences` (
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `host_user_id`          INT UNSIGNED    NOT NULL COMMENT 'FK to tblclients.id — conference host',
    `topic`                 VARCHAR(255)    NOT NULL,
    `room_code`             VARCHAR(20)     NOT NULL COMMENT 'Unique join code (e.g. ALF-XKCD-1234)',
    `max_participants`      SMALLINT UNSIGNED NOT NULL DEFAULT 10,
    `current_participants`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `status`                ENUM('waiting','active','ended')
                                            NOT NULL DEFAULT 'waiting',
    `recording_url`         VARCHAR(500)    DEFAULT NULL COMMENT 'URL to the stored recording',
    `transcript`            LONGTEXT        DEFAULT NULL COMMENT 'Full conference transcript',
    `agenda`                JSON            DEFAULT NULL COMMENT '[{"item":"Intro","duration_min":5}, …]',
    `created_at`            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ended_at`              DATETIME        DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_room_code` (`room_code`),
    KEY `idx_conf_host`     (`host_user_id`),
    KEY `idx_conf_status`   (`status`),
    KEY `idx_conf_created`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Voice conference rooms with transcription and agenda support';


-- ============================================================================
-- 4b. ALFRED CALL LOG
-- ============================================================================
-- Individual call records with transcripts, AI summaries, and cost tracking.

DROP TABLE IF EXISTS `alfred_call_log`;
CREATE TABLE `alfred_call_log` (
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `call_id`               VARCHAR(64)     NOT NULL COMMENT 'External call identifier',
    `client_id`             INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'FK to tblclients.id — call owner',
    `caller_number`         VARCHAR(50)     DEFAULT NULL,
    `started_at`            DATETIME        DEFAULT NULL,
    `ended_at`              DATETIME        DEFAULT NULL,
    `duration_seconds`      INT UNSIGNED    NOT NULL DEFAULT 0,
    `ended_reason`          VARCHAR(100)    DEFAULT NULL,
    `transcript`            MEDIUMTEXT      DEFAULT NULL,
    `summary`               TEXT            DEFAULT NULL,
    `success_evaluation`    VARCHAR(10)     DEFAULT NULL COMMENT 'true/false/unknown',
    `recording_url`         TEXT            DEFAULT NULL,
    `cost_usd`              DECIMAL(8,4)    NOT NULL DEFAULT 0.0000,
    `created_at`            DATETIME        DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_call_id`     (`call_id`),
    KEY `idx_call_client`       (`client_id`),
    KEY `idx_call_started`      (`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Individual call records with transcripts, AI summaries, and cost tracking';


-- ============================================================================
-- 5. ALFRED MARKETPLACE ITEMS
-- ============================================================================
-- Tool marketplace where users can publish and discover tools, templates,
-- workflows, and integrations.

DROP TABLE IF EXISTS `alfred_marketplace_items`;
CREATE TABLE `alfred_marketplace_items` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `seller_user_id`    INT UNSIGNED    NOT NULL COMMENT 'FK to tblclients.id — item publisher',
    `item_type`         ENUM('tool','template','workflow','integration')
                                        NOT NULL DEFAULT 'tool',
    `title`             VARCHAR(200)    NOT NULL,
    `description`       TEXT            DEFAULT NULL,
    `price`             DECIMAL(10,2)   NOT NULL DEFAULT 0.00 COMMENT '0.00 = free',
    `currency`          CHAR(3)         NOT NULL DEFAULT 'USD' COMMENT 'ISO 4217 code',
    `category`          VARCHAR(80)     DEFAULT NULL COMMENT 'E.g. devops, security, ai, design',
    `tags`              JSON            DEFAULT NULL COMMENT '["php","deployment","ci-cd"]',
    `downloads`         INT UNSIGNED    NOT NULL DEFAULT 0,
    `rating`            DECIMAL(3,2)    DEFAULT NULL COMMENT 'Average rating 0.00-5.00',
    `review_count`      INT UNSIGNED    NOT NULL DEFAULT 0,
    `status`            ENUM('draft','active','suspended','sold')
                                        NOT NULL DEFAULT 'draft',
    `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_mkt_seller`    (`seller_user_id`),
    KEY `idx_mkt_type`      (`item_type`),
    KEY `idx_mkt_status`    (`status`),
    KEY `idx_mkt_category`  (`category`),
    KEY `idx_mkt_created`   (`created_at`),
    KEY `idx_mkt_rating`    (`rating` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Marketplace listings — tools, templates, workflows, integrations';


-- ============================================================================
-- 6. ALFRED ACHIEVEMENTS (Gamification — Badges)
-- ============================================================================
-- Records each achievement a user has unlocked, including tier and XP
-- reward.

DROP TABLE IF EXISTS `alfred_achievements`;
CREATE TABLE `alfred_achievements` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`           INT UNSIGNED    NOT NULL COMMENT 'FK to tblclients.id',
    `achievement_name`  VARCHAR(100)    NOT NULL,
    `achievement_type`  VARCHAR(50)     NOT NULL COMMENT 'deployer, security, creator, mentor, explorer, etc.',
    `badge_tier`        ENUM('bronze','silver','gold','platinum','diamond')
                                        NOT NULL DEFAULT 'bronze',
    `xp_awarded`        INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'XP granted when unlocked',
    `criteria_met`      JSON            DEFAULT NULL COMMENT 'Snapshot of criteria values at unlock time',
    `unlocked_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ach_user`      (`user_id`),
    KEY `idx_ach_type`      (`achievement_type`),
    KEY `idx_ach_tier`      (`badge_tier`),
    KEY `idx_ach_unlocked`  (`unlocked_at`),
    UNIQUE KEY `uq_ach_user_name` (`user_id`, `achievement_name`)
        COMMENT 'Prevent duplicate achievements per user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Gamification — achievements and badges unlocked by users';


-- ============================================================================
-- 7. ALFRED STREAKS (Engagement Tracking)
-- ============================================================================
-- Tracks daily engagement streaks to encourage consistent usage.

DROP TABLE IF EXISTS `alfred_streaks`;
CREATE TABLE `alfred_streaks` (
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`               INT UNSIGNED    NOT NULL COMMENT 'FK to tblclients.id',
    `streak_type`           VARCHAR(50)     NOT NULL DEFAULT 'daily_login' COMMENT 'daily_login, code_deploy, tool_use, etc.',
    `current_count`         INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'Current consecutive streak',
    `longest_count`         INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'All-time longest streak',
    `last_action_date`      DATE            DEFAULT NULL COMMENT 'Date of last qualifying action',
    `streak_started`        DATE            DEFAULT NULL COMMENT 'When the current streak began',
    `streak_broken_count`   INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'How many times streak was broken',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_streak_user_type` (`user_id`, `streak_type`),
    KEY `idx_streak_user`   (`user_id`),
    KEY `idx_streak_last`   (`last_action_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='User engagement streaks — daily usage, deployments, etc.';


-- ============================================================================
-- 8. ALFRED XP LEDGER (Experience Points)
-- ============================================================================
-- Immutable ledger of every XP transaction. The user's total XP is the
-- SUM of all rows for that user_id.

DROP TABLE IF EXISTS `alfred_xp`;
CREATE TABLE `alfred_xp` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED    NOT NULL COMMENT 'FK to tblclients.id',
    `xp_amount`     INT             NOT NULL COMMENT 'Can be negative for penalties',
    `action_type`   VARCHAR(80)     NOT NULL COMMENT 'tool_use, deploy, review, streak_bonus, achievement, referral, etc.',
    `source_tool`   VARCHAR(100)    DEFAULT NULL COMMENT 'Which tool generated this XP (NULL for non-tool actions)',
    `multiplier`    DECIMAL(3,2)    NOT NULL DEFAULT 1.00 COMMENT 'Bonus multiplier (streak, event, etc.)',
    `notes`         VARCHAR(255)    DEFAULT NULL COMMENT 'Human-readable context',
    `earned_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_xp_user`       (`user_id`),
    KEY `idx_xp_action`     (`action_type`),
    KEY `idx_xp_earned`     (`earned_at`),
    KEY `idx_xp_source`     (`source_tool`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='XP ledger — every experience-point transaction is an immutable row';


-- ============================================================================
-- 9. ALFRED TOOL USAGE (Analytics)
-- ============================================================================
-- Detailed per-invocation analytics for every tool Alfred exposes.

DROP TABLE IF EXISTS `alfred_tool_usage`;
CREATE TABLE `alfred_tool_usage` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`           INT UNSIGNED    NOT NULL COMMENT 'FK to tblclients.id',
    `tool_name`         VARCHAR(100)    NOT NULL,
    `category`          VARCHAR(60)     DEFAULT NULL COMMENT 'devops, security, code, content, etc.',
    `execution_time_ms` INT UNSIGNED    DEFAULT NULL COMMENT 'Wall-clock time in milliseconds',
    `success`           TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '1 = success, 0 = failure',
    `input_summary`     TEXT            DEFAULT NULL COMMENT 'Sanitized summary of input (no secrets)',
    `output_summary`    TEXT            DEFAULT NULL COMMENT 'Sanitized summary of output',
    `used_at`           TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_usage_user`    (`user_id`),
    KEY `idx_usage_tool`    (`tool_name`),
    KEY `idx_usage_cat`     (`category`),
    KEY `idx_usage_date`    (`used_at`),
    KEY `idx_usage_success` (`success`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tool usage analytics — one row per tool invocation';


-- ============================================================================
-- 10. ALFRED USER PREFERENCES
-- ============================================================================
-- Per-user settings: voice, language, theme, notifications, accessibility.

DROP TABLE IF EXISTS `alfred_user_preferences`;
CREATE TABLE `alfred_user_preferences` (
    `id`                        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`                   INT UNSIGNED    NOT NULL COMMENT 'FK to tblclients.id',
    `preferred_voice`           VARCHAR(50)     DEFAULT 'alloy' COMMENT 'TTS voice identifier',
    `language`                  VARCHAR(10)     NOT NULL DEFAULT 'en' COMMENT 'ISO 639-1 language code',
    `timezone`                  VARCHAR(60)     NOT NULL DEFAULT 'UTC' COMMENT 'IANA timezone (e.g. America/Toronto)',
    `theme`                     VARCHAR(30)     NOT NULL DEFAULT 'auto' COMMENT 'light, dark, auto, high-contrast',
    `notification_settings`     JSON            DEFAULT NULL COMMENT '{"email":true,"push":true,"sms":false, …}',
    `accessibility_settings`    JSON            DEFAULT NULL COMMENT '{"screen_reader":false,"large_text":false, …}',
    `created_at`                TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_prefs_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='User preferences and settings for the Alfred experience';


-- ============================================================================
-- 11. SUPPLEMENTARY: ALFRED LEARNING JOURNAL
--     (From ALFRED_UPGRADE_MASTERPLAN.md §10.2)
-- ============================================================================
-- Persistent journal where Alfred records preferences, patterns, insights,
-- mistakes, and achievements it observes about each user.

DROP TABLE IF EXISTS `alfred_learning_journal`;
CREATE TABLE `alfred_learning_journal` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED    NOT NULL COMMENT 'FK to tblclients.id',
    `entry_type`    ENUM('preference','pattern','insight','mistake','achievement')
                                    NOT NULL,
    `content`       TEXT            NOT NULL,
    `confidence`    DECIMAL(3,2)    NOT NULL DEFAULT 0.50 COMMENT '0.00-1.00 confidence score',
    `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_lj_user`   (`user_id`),
    KEY `idx_lj_type`   (`entry_type`),
    KEY `idx_lj_created`(`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Alfred learning journal — observations and insights per user';


-- ============================================================================
-- 12. SUPPLEMENTARY: ALFRED USER XP SUMMARY (Materialized View)
-- ============================================================================
-- Denormalized summary for fast dashboard reads. Updated by application
-- code or a scheduled event after XP changes.

DROP TABLE IF EXISTS `alfred_user_xp_summary`;
CREATE TABLE `alfred_user_xp_summary` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `client_id`         INT UNSIGNED    NOT NULL COMMENT 'FK to tblclients.id',
    `total_xp`          INT UNSIGNED    NOT NULL DEFAULT 0,
    `level`             SMALLINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Derived from total_xp thresholds',
    `title`             VARCHAR(100)    DEFAULT 'Newcomer' COMMENT 'Display title for this level',
    `streak_days`       INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'Current daily streak length',
    `longest_streak`    INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'All-time longest daily streak',
    `last_active`       DATE            DEFAULT NULL,
    `tools_used`        INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'Distinct tools used lifetime',
    `problems_solved`   INT UNSIGNED    NOT NULL DEFAULT 0,
    `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_xp_summary_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Denormalized XP summary per user — for fast dashboard queries';


-- ============================================================================
-- DEFAULT DATA — ACHIEVEMENTS CATALOGUE
-- ============================================================================
-- Seed achievements so the UI can display them as "locked" before users
-- earn them. user_id = 0 represents the system/catalogue entry.

INSERT INTO `alfred_achievements`
    (`user_id`, `achievement_name`, `achievement_type`, `badge_tier`, `xp_awarded`, `criteria_met`, `unlocked_at`)
VALUES
    -- Deployer track
    (0, 'First Deploy',            'deployer', 'bronze',   50,  '{"deploys":1}',                       NOW()),
    (0, 'Deploy Streak 7',         'deployer', 'silver',   150, '{"consecutive_deploys":7}',            NOW()),
    (0, 'Deploy Streak 30',        'deployer', 'gold',     500, '{"consecutive_deploys":30}',           NOW()),
    (0, 'Zero-Downtime Master',    'deployer', 'platinum', 1000,'{"zero_downtime_deploys":50}',         NOW()),
    (0, 'Ship It Legend',          'deployer', 'diamond',  2500,'{"total_deploys":500}',                NOW()),

    -- Security track
    (0, 'Bug Spotter',             'security', 'bronze',   50,  '{"vulnerabilities_found":1}',          NOW()),
    (0, 'Security Sweep',          'security', 'silver',   200, '{"full_scans":10}',                    NOW()),
    (0, 'Fortress Builder',        'security', 'gold',     600, '{"hardened_servers":5}',               NOW()),
    (0, 'Cyber Sentinel',          'security', 'platinum', 1200,'{"threats_blocked":100}',              NOW()),
    (0, 'Impenetrable',            'security', 'diamond',  3000,'{"zero_breach_days":365}',             NOW()),

    -- Creator track
    (0, 'First Tool',              'creator',  'bronze',   50,  '{"tools_published":1}',                NOW()),
    (0, 'Toolsmith',               'creator',  'silver',   200, '{"tools_published":5}',                NOW()),
    (0, 'Marketplace Star',        'creator',  'gold',     700, '{"marketplace_downloads":100}',        NOW()),
    (0, 'Open Source Hero',        'creator',  'platinum', 1500,'{"marketplace_downloads":1000}',       NOW()),
    (0, 'Platform Architect',      'creator',  'diamond',  3500,'{"tools_published":25,"avg_rating":4.5}', NOW()),

    -- Explorer track
    (0, 'Curious Mind',            'explorer', 'bronze',   30,  '{"unique_tools_used":5}',              NOW()),
    (0, 'Tool Tourist',            'explorer', 'silver',   120, '{"unique_tools_used":20}',             NOW()),
    (0, 'Swiss Army Dev',          'explorer', 'gold',     400, '{"unique_tools_used":50}',             NOW()),
    (0, 'Polyglot',                'explorer', 'platinum', 900, '{"languages_used":10}',                NOW()),
    (0, 'Renaissance Dev',         'explorer', 'diamond',  2000,'{"unique_tools_used":100,"categories":10}', NOW()),

    -- Mentor track
    (0, 'Helpful Hand',            'mentor',   'bronze',   40,  '{"users_helped":1}',                   NOW()),
    (0, 'Community Guide',         'mentor',   'silver',   160, '{"users_helped":10}',                  NOW()),
    (0, 'Knowledge Sharer',        'mentor',   'gold',     500, '{"articles_written":5}',               NOW()),
    (0, 'Master Mentor',           'mentor',   'platinum', 1100,'{"users_helped":50,"avg_rating":4.0}', NOW()),
    (0, 'Alfred Ambassador',       'mentor',   'diamond',  2800,'{"users_helped":200,"referrals":20}',  NOW());


-- ============================================================================
-- PERSONALITY TRAITS
-- ============================================================================
-- Stores per-user personality traits that Alfred adapts to.
-- Used by the consciousness subsystem and the chat-engine bridge.

CREATE TABLE IF NOT EXISTS `alfred_personality` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `client_id`       INT UNSIGNED    NOT NULL COMMENT 'FK to tblclients.id',
    `trait_name`      VARCHAR(50)     NOT NULL,
    `trait_value`     TEXT            NOT NULL,
    `confidence`      DECIMAL(3,2)   NOT NULL DEFAULT 1.00 COMMENT '0.00–1.00 confidence score',
    `active`          TINYINT(1)     NOT NULL DEFAULT 1,
    `last_triggered`  TIMESTAMP      NULL DEFAULT NULL,
    `created_at`      TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_personality_trait` (`client_id`, `trait_name`),
    KEY `idx_personality_client_active` (`client_id`, `active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-user personality traits Alfred adapts to';


-- ============================================================================
-- USER PROFILES
-- ============================================================================
-- Extended profile data for deep personalization — communication style,
-- goals, skills, onboarding state, etc.

CREATE TABLE IF NOT EXISTS `alfred_user_profiles` (
    `id`                     INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `client_id`              INT UNSIGNED    NOT NULL COMMENT 'FK to tblclients.id',
    `display_name`           VARCHAR(255)    DEFAULT NULL,
    `avatar_url`             VARCHAR(512)    DEFAULT NULL,
    `bio`                    TEXT            DEFAULT NULL,
    `skills`                 JSON            DEFAULT NULL,
    `preferences`            JSON            DEFAULT NULL,
    `goals`                  JSON            DEFAULT NULL,
    `communication_style`    JSON            DEFAULT NULL,
    `timezone`               VARCHAR(64)     DEFAULT NULL,
    `language`               VARCHAR(10)     DEFAULT 'en',
    `onboarding_completed`   TINYINT(1)     NOT NULL DEFAULT 0,
    `created_at`             TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`             TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_profile_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Extended user profiles for Alfred personalization';


-- ============================================================================
-- USER XP (GAMIFICATION)
-- ============================================================================
-- Tracks per-user XP, level, streaks, and activity metrics.
-- Referenced by the consciousness relationship_score & growth_tracker endpoints.

CREATE TABLE IF NOT EXISTS `alfred_user_xp` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `client_id`       INT UNSIGNED    NOT NULL COMMENT 'FK to tblclients.id',
    `total_xp`        INT UNSIGNED    NOT NULL DEFAULT 0,
    `level`           SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `streak_days`     INT UNSIGNED    NOT NULL DEFAULT 0,
    `last_active`     DATE            DEFAULT NULL,
    `tools_used`      INT UNSIGNED    NOT NULL DEFAULT 0,
    `problems_solved` INT UNSIGNED    NOT NULL DEFAULT 0,
    `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_xp_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-user XP, level, and activity metrics for gamification';


-- ============================================================================
-- DEFAULT DATA — XP REWARD VALUES
-- ============================================================================
-- Reference table so the application knows how much XP each action type
-- awards. Stored as XP ledger entries with user_id = 0.

INSERT INTO `alfred_xp`
    (`user_id`, `xp_amount`, `action_type`, `source_tool`, `multiplier`, `notes`)
VALUES
    (0, 10,  'tool_use',          NULL,              1.00, 'Default XP for using any tool'),
    (0, 25,  'deploy',            'deploy-manager',  1.00, 'Successful deployment'),
    (0, 5,   'chat_interaction',  'alfred-chat',     1.00, 'Meaningful chat interaction'),
    (0, 50,  'streak_bonus_7',    NULL,              1.50, '7-day streak bonus (1.5x multiplier)'),
    (0, 100, 'streak_bonus_30',   NULL,              2.00, '30-day streak bonus (2x multiplier)'),
    (0, 15,  'security_scan',     'security-scanner',1.00, 'Running a security scan'),
    (0, 20,  'code_review',       'code-reviewer',   1.00, 'Completing a code review'),
    (0, 30,  'marketplace_publish', 'marketplace',   1.00, 'Publishing a marketplace item'),
    (0, 5,   'marketplace_review','marketplace',     1.00, 'Leaving a marketplace review'),
    (0, 75,  'fleet_complete',    'fleet-manager',   1.00, 'Completing a fleet objective'),
    (0, 40,  'conference_host',   'conference',      1.00, 'Hosting a conference session'),
    (0, 10,  'daily_login',       NULL,              1.00, 'Daily login reward'),
    (0, 200, 'achievement_unlock',NULL,              1.00, 'Bonus for unlocking any achievement'),
    (0, 500, 'referral',          NULL,              1.00, 'Referring a new user who signs up'),
    (0, 35,  'problem_solved',    NULL,              1.00, 'Solving a support/dev problem');


-- ============================================================================
-- DEFAULT DATA — SCHEMA VERSION ENTRY
-- ============================================================================

INSERT INTO `alfred_schema_version`
    (`version`, `description`, `script_name`, `checksum`, `applied_by`, `execution_time_ms`)
VALUES
    ('1.0.0', 'Initial Alfred platform schema — consciousness, fleets, conferences, marketplace, gamification, analytics, preferences',
     'alfred_schema.sql', NULL, 'system', 0);


-- ============================================================================
-- COMMIT & RESTORE
-- ============================================================================

COMMIT;

SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- HELPFUL VIEWS (Optional — uncomment to install)
-- ============================================================================

/*
-- Leaderboard view: top users by XP
CREATE OR REPLACE VIEW `v_alfred_leaderboard` AS
SELECT
    x.user_id,
    SUM(x.xp_amount * x.multiplier) AS total_xp,
    COUNT(DISTINCT a.id)             AS achievement_count,
    s.current_count                  AS daily_streak
FROM `alfred_xp` x
LEFT JOIN `alfred_achievements` a ON a.user_id = x.user_id AND a.user_id != 0
LEFT JOIN `alfred_streaks` s      ON s.user_id = x.user_id AND s.streak_type = 'daily_login'
WHERE x.user_id != 0
GROUP BY x.user_id, s.current_count
ORDER BY total_xp DESC;

-- Tool popularity view
CREATE OR REPLACE VIEW `v_alfred_tool_popularity` AS
SELECT
    tool_name,
    category,
    COUNT(*)                          AS total_uses,
    SUM(success)                      AS successes,
    ROUND(AVG(execution_time_ms), 0)  AS avg_time_ms,
    ROUND(SUM(success)/COUNT(*)*100,1) AS success_pct
FROM `alfred_tool_usage`
GROUP BY tool_name, category
ORDER BY total_uses DESC;
*/

-- ============================================================================
-- END OF SCHEMA
-- ============================================================================
