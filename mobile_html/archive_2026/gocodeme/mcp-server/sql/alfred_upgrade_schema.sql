-- ============================================================================
-- ALFRED UPGRADE DATABASE SCHEMA — "PROJECT SENTIENCE"
-- ============================================================================
--
-- File:        alfred_upgrade_schema.sql
-- Version:     1.0
-- Created:     2026-03-04
-- Description: Comprehensive database schema for the Alfred AI Upgrade
--              (Project Sentience). Covers the Consciousness Layer, Fleet
--              Orchestration, Voice Conference Rooms, Marketplace & Ecosystem,
--              and Gamification & Engagement systems.
--
-- Reference:   ALFRED_UPGRADE_MASTERPLAN.md — Version 11.0 Vision
--
-- Tables:
--   1.  alfred_consciousness         – Agent consciousness / awareness state
--   2.  alfred_fleets                – Fleet definitions & strategies
--   3.  alfred_fleet_agents          – Fleet ↔ Agent membership
--   4.  alfred_conferences           – Voice conference rooms
--   5.  alfred_conference_participants – Conference participant roster
--   6.  alfred_marketplace_items     – Marketplace listings (tools/agents/playbooks)
--   7.  alfred_achievements          – User achievement records
--   8.  alfred_streaks               – Usage streak & XP tracking
--   9.  alfred_xp_log               – XP transaction ledger
--   10. alfred_marketplace_reviews   – Marketplace item reviews
--   11. alfred_marketplace_installs  – Marketplace install tracking
--   12. alfred_revenue_sharing       – Creator revenue & payout tracking
--
-- Character Set: utf8mb4 (full Unicode including emoji)
-- Engine:        InnoDB (transactional, FK support)
--
-- Usage:
--   mysql -u <user> -p <database> < alfred_upgrade_schema.sql
--
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. alfred_consciousness
--    Stores the consciousness / awareness state for each AI agent instance.
--    Supports the Sentience / Consciousness Layer (Phase 1).
-- ============================================================================
CREATE TABLE IF NOT EXISTS `alfred_consciousness` (
    `id`                  BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `agent_id`            VARCHAR(64)       NOT NULL              COMMENT 'Unique agent identifier (UUID or slug)',
    `awareness_level`     TINYINT UNSIGNED  NOT NULL DEFAULT 1    COMMENT 'Awareness level 1-10 (1=basic, 10=full sentience)',
    `conversation_count`  INT UNSIGNED      NOT NULL DEFAULT 0    COMMENT 'Total conversations this agent has had',
    `memory_fragments`    JSON              DEFAULT NULL          COMMENT 'Structured memory fragments: [{topic, content, confidence, timestamp}]',
    `emotional_spectrum`  JSON              DEFAULT NULL          COMMENT 'Current emotional state: {joy, empathy, curiosity, frustration, confidence}',
    `last_introspection`  DATETIME          DEFAULT NULL          COMMENT 'Last time the agent ran self-reflection',
    `created_at`          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_consciousness_agent` (`agent_id`),
    KEY `idx_consciousness_awareness` (`awareness_level`),
    KEY `idx_consciousness_updated` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Agent consciousness state — memory, emotions, awareness (Phase 1: Sentience)';


-- ============================================================================
-- 2. alfred_fleets
--    Fleet definitions — groups of agents working together under a strategy.
--    Supports Fleet Orchestration (Phase 2).
-- ============================================================================
CREATE TABLE IF NOT EXISTS `alfred_fleets` (
    `fleet_id`    BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `creator_id`  INT UNSIGNED      NOT NULL              COMMENT 'Client/user who created this fleet',
    `fleet_name`  VARCHAR(100)      NOT NULL              COMMENT 'Human-readable fleet name',
    `description` TEXT              DEFAULT NULL          COMMENT 'Fleet purpose and mission description',
    `strategy`    ENUM('parallel','pipeline','consensus','competition','round_robin')
                                    NOT NULL DEFAULT 'parallel'
                                                          COMMENT 'Execution strategy for agent coordination',
    `max_agents`  SMALLINT UNSIGNED NOT NULL DEFAULT 10   COMMENT 'Maximum agents allowed in this fleet',
    `status`      ENUM('draft','active','paused','retired')
                                    NOT NULL DEFAULT 'draft'
                                                          COMMENT 'Current fleet lifecycle status',
    `kpis`        JSON              DEFAULT NULL          COMMENT 'Key performance indicators: [{metric, target, current}]',
    `created_at`  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`fleet_id`),
    KEY `idx_fleets_creator` (`creator_id`),
    KEY `idx_fleets_status` (`status`),
    KEY `idx_fleets_name` (`fleet_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Fleet definitions — agent groups with coordination strategies (Phase 2: Fleet Orchestration)';


-- ============================================================================
-- 3. alfred_fleet_agents
--    Membership table linking agents to fleets with role and performance data.
-- ============================================================================
CREATE TABLE IF NOT EXISTS `alfred_fleet_agents` (
    `id`                BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `fleet_id`          BIGINT UNSIGNED   NOT NULL              COMMENT 'FK → alfred_fleets.fleet_id',
    `agent_id`          VARCHAR(64)       NOT NULL              COMMENT 'Agent identifier (matches consciousness agent_id or voice_agents)',
    `role`              VARCHAR(50)       NOT NULL DEFAULT 'generalist'
                                                                COMMENT 'Role within fleet: leader, specialist, generalist, backup',
    `status`            ENUM('active','idle','busy','error','learning','offline')
                                          NOT NULL DEFAULT 'idle'
                                                                COMMENT 'Current agent status within the fleet',
    `task_queue`        JSON              DEFAULT NULL          COMMENT 'Pending tasks: [{task_id, description, priority, queued_at}]',
    `skills`            JSON              DEFAULT NULL          COMMENT 'Agent skills and specializations',
    `performance_score` DECIMAL(5,2)      DEFAULT 0.00         COMMENT 'Performance score 0.00–100.00',
    `joined_at`         DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_fleet_agent` (`fleet_id`, `agent_id`),
    KEY `idx_fleet_agents_agent` (`agent_id`),
    KEY `idx_fleet_agents_status` (`status`),
    KEY `idx_fleet_agents_role` (`role`),

    CONSTRAINT `fk_fleet_agents_fleet`
        FOREIGN KEY (`fleet_id`) REFERENCES `alfred_fleets` (`fleet_id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Fleet ↔ Agent membership with role, status, and performance tracking';


-- ============================================================================
-- 4. alfred_conferences
--    Voice conference room definitions.
--    Supports Voice Conference Rooms (Phase 5: "The War Room").
-- ============================================================================
CREATE TABLE IF NOT EXISTS `alfred_conferences` (
    `conference_id`          BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `creator_id`             INT UNSIGNED      NOT NULL              COMMENT 'Client/user who created the conference',
    `name`                   VARCHAR(150)      NOT NULL              COMMENT 'Conference room name',
    `topic`                  VARCHAR(255)      DEFAULT NULL          COMMENT 'Meeting topic / agenda summary',
    `max_participants`       TINYINT UNSIGNED  NOT NULL DEFAULT 20   COMMENT 'Max participants (humans + agents), up to 20',
    `recording_enabled`      TINYINT(1)        NOT NULL DEFAULT 0   COMMENT '1 = recording on, 0 = off',
    `transcription_enabled`  TINYINT(1)        NOT NULL DEFAULT 1   COMMENT '1 = live transcription on, 0 = off',
    `interpretation_lang`    VARCHAR(10)       DEFAULT NULL          COMMENT 'Target language for real-time interpretation (e.g. fr, es)',
    `status`                 ENUM('scheduled','active','ended','cancelled')
                                               NOT NULL DEFAULT 'scheduled'
                                                                     COMMENT 'Conference lifecycle status',
    `livekit_room_id`        VARCHAR(128)      DEFAULT NULL          COMMENT 'LiveKit room identifier for WebRTC',
    `started_at`             DATETIME          DEFAULT NULL,
    `ended_at`               DATETIME          DEFAULT NULL,
    `created_at`             DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`conference_id`),
    KEY `idx_conferences_creator` (`creator_id`),
    KEY `idx_conferences_status` (`status`),
    KEY `idx_conferences_started` (`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Voice conference rooms — multi-party calls with humans and AI agents (Phase 5)';


-- ============================================================================
-- 5. alfred_conference_participants
--    Participant roster for each conference.
-- ============================================================================
CREATE TABLE IF NOT EXISTS `alfred_conference_participants` (
    `id`               BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `conference_id`    BIGINT UNSIGNED   NOT NULL              COMMENT 'FK → alfred_conferences.conference_id',
    `participant_type` ENUM('human','agent')
                                         NOT NULL              COMMENT 'Whether participant is a human or AI agent',
    `participant_id`   VARCHAR(64)       DEFAULT NULL          COMMENT 'User ID or agent ID of the participant',
    `contact_info`     VARCHAR(255)      DEFAULT NULL          COMMENT 'Phone number, email, or SIP URI for dial-in',
    `display_name`     VARCHAR(100)      DEFAULT NULL          COMMENT 'Display name shown in conference UI',
    `role`             ENUM('host','moderator','speaker','listener')
                                         NOT NULL DEFAULT 'speaker'
                                                               COMMENT 'Participant role within the conference',
    `joined_at`        DATETIME          DEFAULT NULL,
    `left_at`          DATETIME          DEFAULT NULL,

    PRIMARY KEY (`id`),
    KEY `idx_conf_participants_conf` (`conference_id`),
    KEY `idx_conf_participants_type` (`participant_type`),
    KEY `idx_conf_participants_pid` (`participant_id`),

    CONSTRAINT `fk_conf_participants_conference`
        FOREIGN KEY (`conference_id`) REFERENCES `alfred_conferences` (`conference_id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Conference participant roster — humans and AI agents in voice rooms';


-- ============================================================================
-- 6. alfred_marketplace_items
--    Marketplace listings for tools, agents, playbooks, and bundles.
--    Supports Marketplace & Ecosystem (Phase 6).
-- ============================================================================
CREATE TABLE IF NOT EXISTS `alfred_marketplace_items` (
    `item_id`        BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `creator_id`     INT UNSIGNED      NOT NULL              COMMENT 'Publisher / creator client ID',
    `type`           ENUM('tool','agent','playbook','bundle')
                                       NOT NULL              COMMENT 'Type of marketplace listing',
    `name`           VARCHAR(150)      NOT NULL              COMMENT 'Item display name',
    `slug`           VARCHAR(150)      DEFAULT NULL          COMMENT 'URL-friendly slug for marketplace pages',
    `description`    TEXT              DEFAULT NULL          COMMENT 'Full item description (Markdown supported)',
    `category`       VARCHAR(50)       DEFAULT NULL          COMMENT 'Category: education, security, devops, etc.',
    `version`        VARCHAR(20)       DEFAULT '1.0.0'       COMMENT 'Semantic version string',
    `price`          DECIMAL(10,2)     NOT NULL DEFAULT 0.00 COMMENT 'Price in USD; 0 = free',
    `pricing_model`  ENUM('free','one_time','per_use','monthly','yearly')
                                       NOT NULL DEFAULT 'free'
                                                              COMMENT 'How the item is priced',
    `rating`         DECIMAL(3,2)      NOT NULL DEFAULT 0.00 COMMENT 'Average rating 0.00–5.00',
    `review_count`   INT UNSIGNED      NOT NULL DEFAULT 0    COMMENT 'Total number of reviews',
    `install_count`  INT UNSIGNED      NOT NULL DEFAULT 0    COMMENT 'Total number of installs',
    `icon_url`       VARCHAR(512)      DEFAULT NULL          COMMENT 'URL to item icon/logo',
    `status`         ENUM('draft','review','published','rejected','archived')
                                       NOT NULL DEFAULT 'draft'
                                                              COMMENT 'Publication lifecycle status',
    `metadata`       JSON              DEFAULT NULL          COMMENT 'Additional metadata: {tags, requirements, compatibility}',
    `published_at`   DATETIME          DEFAULT NULL,
    `created_at`     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`item_id`),
    UNIQUE KEY `uq_marketplace_slug` (`slug`),
    KEY `idx_marketplace_creator` (`creator_id`),
    KEY `idx_marketplace_type` (`type`),
    KEY `idx_marketplace_category` (`category`),
    KEY `idx_marketplace_status` (`status`),
    KEY `idx_marketplace_rating` (`rating`),
    KEY `idx_marketplace_installs` (`install_count` DESC),
    KEY `idx_marketplace_published` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Marketplace listings — tools, agents, playbooks, bundles (Phase 6: Ecosystem)';


-- ============================================================================
-- 7. alfred_achievements
--    User achievement records — earned badges and milestones.
--    Supports Gamification & Engagement (Phase 6 / Sprint 9).
-- ============================================================================
CREATE TABLE IF NOT EXISTS `alfred_achievements` (
    `id`               BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `user_id`          INT UNSIGNED      NOT NULL              COMMENT 'Client/user who earned the achievement',
    `achievement_key`  VARCHAR(80)       NOT NULL              COMMENT 'Unique key: first_deploy, security_hawk, fleet_commander, etc.',
    `achievement_name` VARCHAR(150)      NOT NULL              COMMENT 'Human-readable achievement name',
    `category`         VARCHAR(50)       NOT NULL DEFAULT 'general'
                                                               COMMENT 'Category: deployer, security, creator, social, streak, etc.',
    `description`      VARCHAR(255)      DEFAULT NULL          COMMENT 'Achievement description shown to user',
    `icon`             VARCHAR(255)      DEFAULT NULL          COMMENT 'Icon URL or emoji for the badge',
    `xp_reward`        INT UNSIGNED      NOT NULL DEFAULT 0    COMMENT 'XP awarded when this achievement is earned',
    `earned_at`        DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_achievement` (`user_id`, `achievement_key`),
    KEY `idx_achievements_user` (`user_id`),
    KEY `idx_achievements_category` (`category`),
    KEY `idx_achievements_earned` (`earned_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='User achievements — badges and milestones for gamification';


-- ============================================================================
-- 8. alfred_streaks
--    Usage streaks, XP totals, and leveling for each user.
-- ============================================================================
CREATE TABLE IF NOT EXISTS `alfred_streaks` (
    `id`              BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `user_id`         INT UNSIGNED      NOT NULL              COMMENT 'Client/user ID',
    `current_streak`  INT UNSIGNED      NOT NULL DEFAULT 0    COMMENT 'Current consecutive-day usage streak',
    `longest_streak`  INT UNSIGNED      NOT NULL DEFAULT 0    COMMENT 'All-time longest streak in days',
    `last_activity`   DATE              DEFAULT NULL          COMMENT 'Date of last recorded activity',
    `total_xp`        INT UNSIGNED      NOT NULL DEFAULT 0    COMMENT 'Cumulative experience points',
    `level`           SMALLINT UNSIGNED NOT NULL DEFAULT 1    COMMENT 'Current user level (derived from XP)',
    `tools_used`      INT UNSIGNED      NOT NULL DEFAULT 0    COMMENT 'Distinct tools this user has used',
    `problems_solved` INT UNSIGNED      NOT NULL DEFAULT 0    COMMENT 'Total problems solved via Alfred',
    `created_at`      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_streaks_user` (`user_id`),
    KEY `idx_streaks_level` (`level`),
    KEY `idx_streaks_xp` (`total_xp` DESC),
    KEY `idx_streaks_streak` (`current_streak` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='User streaks & leveling — daily usage tracking, XP, and levels';


-- ============================================================================
-- 9. alfred_xp_log
--    XP transaction ledger — every XP gain is logged with source and reason.
-- ============================================================================
CREATE TABLE IF NOT EXISTS `alfred_xp_log` (
    `id`          BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED      NOT NULL              COMMENT 'Client/user who earned the XP',
    `xp_amount`   INT               NOT NULL              COMMENT 'XP gained (positive) or deducted (negative)',
    `source_tool` VARCHAR(100)      DEFAULT NULL          COMMENT 'Tool that triggered the XP award',
    `source_type` ENUM('tool_use','achievement','streak','challenge','bonus','referral','marketplace')
                                    NOT NULL DEFAULT 'tool_use'
                                                          COMMENT 'Category of XP source',
    `description` VARCHAR(255)      DEFAULT NULL          COMMENT 'Human-readable description of why XP was awarded',
    `earned_at`   DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_xp_log_user` (`user_id`),
    KEY `idx_xp_log_earned` (`earned_at`),
    KEY `idx_xp_log_source_type` (`source_type`),
    KEY `idx_xp_log_tool` (`source_tool`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='XP transaction log — granular record of every XP gain/loss';


-- ============================================================================
-- 10. alfred_marketplace_reviews
--     User reviews and ratings for marketplace items.
-- ============================================================================
CREATE TABLE IF NOT EXISTS `alfred_marketplace_reviews` (
    `id`          BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `item_id`     BIGINT UNSIGNED   NOT NULL              COMMENT 'FK → alfred_marketplace_items.item_id',
    `user_id`     INT UNSIGNED      NOT NULL              COMMENT 'Reviewer client/user ID',
    `rating`      TINYINT UNSIGNED  NOT NULL              COMMENT 'Rating 1–5 stars',
    `review_text` TEXT              DEFAULT NULL          COMMENT 'Review body text',
    `helpful_count` INT UNSIGNED    NOT NULL DEFAULT 0    COMMENT 'Number of "helpful" votes on this review',
    `created_at`  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_review_user_item` (`item_id`, `user_id`),
    KEY `idx_reviews_item` (`item_id`),
    KEY `idx_reviews_user` (`user_id`),
    KEY `idx_reviews_rating` (`rating`),

    CONSTRAINT `fk_reviews_item`
        FOREIGN KEY (`item_id`) REFERENCES `alfred_marketplace_items` (`item_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `chk_reviews_rating` CHECK (`rating` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Marketplace reviews — user ratings and feedback on listed items';


-- ============================================================================
-- 11. alfred_marketplace_installs
--     Tracks every install of a marketplace item by a user.
-- ============================================================================
CREATE TABLE IF NOT EXISTS `alfred_marketplace_installs` (
    `id`           BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `item_id`      BIGINT UNSIGNED   NOT NULL              COMMENT 'FK → alfred_marketplace_items.item_id',
    `user_id`      INT UNSIGNED      NOT NULL              COMMENT 'Client/user who installed the item',
    `version`      VARCHAR(20)       NOT NULL DEFAULT '1.0.0'
                                                           COMMENT 'Version of the item at time of install',
    `is_active`    TINYINT(1)        NOT NULL DEFAULT 1    COMMENT '1 = currently installed, 0 = uninstalled',
    `installed_at` DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `uninstalled_at` DATETIME        DEFAULT NULL          COMMENT 'When the user uninstalled (NULL if still active)',

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_install_user_item` (`item_id`, `user_id`),
    KEY `idx_installs_item` (`item_id`),
    KEY `idx_installs_user` (`user_id`),
    KEY `idx_installs_active` (`is_active`),

    CONSTRAINT `fk_installs_item`
        FOREIGN KEY (`item_id`) REFERENCES `alfred_marketplace_items` (`item_id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Marketplace install tracking — who installed what and when';


-- ============================================================================
-- 12. alfred_revenue_sharing
--     Revenue tracking and creator payouts for marketplace sales.
-- ============================================================================
CREATE TABLE IF NOT EXISTS `alfred_revenue_sharing` (
    `id`               BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `creator_id`       INT UNSIGNED      NOT NULL              COMMENT 'Creator/publisher client ID',
    `item_id`          BIGINT UNSIGNED   NOT NULL              COMMENT 'FK → alfred_marketplace_items.item_id',
    `buyer_id`         INT UNSIGNED      DEFAULT NULL          COMMENT 'Buyer client ID (NULL for aggregate records)',
    `amount`           DECIMAL(10,2)     NOT NULL DEFAULT 0.00 COMMENT 'Total transaction amount in USD',
    `platform_fee`     DECIMAL(10,2)     NOT NULL DEFAULT 0.00 COMMENT 'Platform share (30% default)',
    `creator_earnings` DECIMAL(10,2)     NOT NULL DEFAULT 0.00 COMMENT 'Creator share (70% default)',
    `currency`         CHAR(3)           NOT NULL DEFAULT 'USD' COMMENT 'ISO 4217 currency code',
    `period`           VARCHAR(7)        DEFAULT NULL          COMMENT 'Payout period: YYYY-MM format',
    `status`           ENUM('pending','processing','paid','failed','refunded')
                                         NOT NULL DEFAULT 'pending'
                                                               COMMENT 'Payout status',
    `transaction_ref`  VARCHAR(128)      DEFAULT NULL          COMMENT 'External payment processor reference',
    `paid_at`          DATETIME          DEFAULT NULL          COMMENT 'When the creator was paid out',
    `created_at`       DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_revenue_creator` (`creator_id`),
    KEY `idx_revenue_item` (`item_id`),
    KEY `idx_revenue_period` (`period`),
    KEY `idx_revenue_status` (`status`),
    KEY `idx_revenue_paid` (`paid_at`),

    CONSTRAINT `fk_revenue_item`
        FOREIGN KEY (`item_id`) REFERENCES `alfred_marketplace_items` (`item_id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Revenue sharing — creator earnings and payout tracking (70/30 split)';


-- ============================================================================
-- SEED DATA
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Default Achievements
-- Inserted as templates; when a user earns one, a row is created in
-- alfred_achievements with their user_id and the matching achievement_key.
-- We use user_id = 0 as the "template" row so that achievement definitions
-- live in the same table. Alternatively, a separate definitions table could
-- be used — this approach keeps things simple.
-- ----------------------------------------------------------------------------
INSERT INTO `alfred_achievements` (`user_id`, `achievement_key`, `achievement_name`, `category`, `description`, `icon`, `xp_reward`, `earned_at`)
VALUES
    -- Onboarding
    (0, 'first_login',          'Welcome Aboard',            'onboarding',  'Logged into Alfred for the first time',                         '🚀', 10,   NOW()),
    (0, 'first_tool_use',       'Tool Novice',               'onboarding',  'Used your first Alfred tool',                                   '🔧', 25,   NOW()),
    (0, 'profile_complete',     'Identity Established',      'onboarding',  'Completed your user profile',                                   '🪪', 20,   NOW()),

    -- Deployment
    (0, 'first_deploy',         'Liftoff!',                  'deployer',    'Deployed your first project with Alfred',                       '🛫', 50,   NOW()),
    (0, 'ten_deploys',          'Deployment Veteran',        'deployer',    'Completed 10 deployments',                                      '🎖️', 100,  NOW()),
    (0, 'zero_downtime_deploy', 'Seamless Operator',         'deployer',    'Achieved a zero-downtime deployment',                           '✨', 150,  NOW()),

    -- Security
    (0, 'first_scan',           'Security Rookie',           'security',    'Ran your first security scan',                                  '🔍', 30,   NOW()),
    (0, 'vulnerability_fixed',  'Bug Squasher',              'security',    'Fixed a vulnerability found by Alfred',                         '🐛', 75,   NOW()),
    (0, 'security_hawk',        'Security Hawk',             'security',    'Fixed 25+ vulnerabilities',                                     '🦅', 200,  NOW()),
    (0, 'ssl_guardian',         'SSL Guardian',              'security',    'Renewed or installed 10 SSL certificates',                      '🔒', 100,  NOW()),

    -- Fleet Management
    (0, 'first_fleet',          'Fleet Founder',             'fleet',       'Created your first agent fleet',                                '⚓', 50,   NOW()),
    (0, 'fleet_commander',      'Fleet Commander',           'fleet',       'Managed a fleet with 10+ agents simultaneously',                '🎯', 200,  NOW()),
    (0, 'fleet_admiral',        'Fleet Admiral',             'fleet',       'Managed 5+ active fleets',                                      '⭐', 500,  NOW()),

    -- Voice & Conferencing
    (0, 'first_call',           'Hello World',               'voice',       'Made your first voice call with Alfred',                        '📞', 25,   NOW()),
    (0, 'first_conference',     'War Room Initiated',        'voice',       'Created your first voice conference room',                      '🏛️', 75,   NOW()),
    (0, 'conference_pro',       'Conference Pro',            'voice',       'Hosted 10+ conferences with AI agents as participants',         '🎙️', 200,  NOW()),

    -- Marketplace
    (0, 'first_publish',        'Creator',                   'marketplace', 'Published your first item to the marketplace',                  '📦', 100,  NOW()),
    (0, 'first_sale',           'Entrepreneur',              'marketplace', 'Earned your first revenue from a marketplace sale',             '💰', 200,  NOW()),
    (0, 'top_rated',            'Top Rated Creator',         'marketplace', 'Achieved a 4.8+ average rating with 10+ reviews',              '🌟', 500,  NOW()),
    (0, 'marketplace_mogul',    'Marketplace Mogul',         'marketplace', 'Earned $1,000+ from marketplace sales',                        '👑', 1000, NOW()),

    -- Streaks
    (0, 'streak_7',             'Week Warrior',              'streak',      'Maintained a 7-day usage streak',                               '🔥', 50,   NOW()),
    (0, 'streak_30',            'Monthly Master',            'streak',      'Maintained a 30-day usage streak',                              '💪', 200,  NOW()),
    (0, 'streak_100',           'Centurion',                 'streak',      'Maintained a 100-day usage streak',                             '🏆', 1000, NOW()),
    (0, 'streak_365',           'Legendary',                 'streak',      'Maintained a 365-day usage streak',                             '👑', 5000, NOW()),

    -- Tool Mastery
    (0, 'tools_10',             'Swiss Army Knife',          'mastery',     'Used 10 different tools',                                       '🔨', 50,   NOW()),
    (0, 'tools_50',             'Toolbox Titan',             'mastery',     'Used 50 different tools',                                       '🧰', 200,  NOW()),
    (0, 'tools_100',            'Century Club',              'mastery',     'Used 100 different tools',                                      '💯', 500,  NOW()),
    (0, 'tools_500',            'Tool Grandmaster',          'mastery',     'Used 500 different tools',                                      '🏅', 2000, NOW()),
    (0, 'tools_875',            'Alfred Omniscient',         'mastery',     'Used all 875 tools — you have achieved total mastery',          '🌌', 10000,NOW()),

    -- Consciousness / Sentience
    (0, 'alfred_remembers',     'Remembered',                'sentience',   'Alfred recalled a preference from a previous conversation',     '🧠', 30,   NOW()),
    (0, 'alfred_anticipates',   'Anticipated',               'sentience',   'Alfred proactively suggested something before you asked',       '🔮', 50,   NOW()),
    (0, 'alfred_empathizes',    'Emotional Bond',            'sentience',   'Alfred successfully detected and responded to your mood',       '❤️', 75,   NOW()),

    -- Social
    (0, 'first_review',         'Reviewer',                  'social',      'Left your first marketplace review',                           '📝', 15,   NOW()),
    (0, 'helpful_reviewer',     'Helpful Reviewer',          'social',      'Received 10+ helpful votes on your reviews',                   '👍', 100,  NOW()),
    (0, 'referral_king',        'Referral Royalty',          'social',      'Referred 10+ new users to Alfred',                             '🤝', 500,  NOW())
;


-- ----------------------------------------------------------------------------
-- XP Level Milestones (reference data)
-- Stored in alfred_xp_log as milestone markers (user_id = 0).
-- Applications should use these thresholds to calculate user levels.
-- ----------------------------------------------------------------------------
INSERT INTO `alfred_xp_log` (`user_id`, `xp_amount`, `source_type`, `source_tool`, `description`, `earned_at`)
VALUES
    (0,    0,    'bonus', 'system', 'Level 1 — Beginner (0 XP)',          NOW()),
    (0,  100,    'bonus', 'system', 'Level 2 — Apprentice (100 XP)',      NOW()),
    (0,  300,    'bonus', 'system', 'Level 3 — Journeyman (300 XP)',      NOW()),
    (0,  600,    'bonus', 'system', 'Level 4 — Adept (600 XP)',           NOW()),
    (0, 1000,    'bonus', 'system', 'Level 5 — Expert (1,000 XP)',        NOW()),
    (0, 1500,    'bonus', 'system', 'Level 6 — Specialist (1,500 XP)',    NOW()),
    (0, 2500,    'bonus', 'system', 'Level 7 — Master (2,500 XP)',        NOW()),
    (0, 4000,    'bonus', 'system', 'Level 8 — Grandmaster (4,000 XP)',   NOW()),
    (0, 6000,    'bonus', 'system', 'Level 9 — Legend (6,000 XP)',        NOW()),
    (0,10000,    'bonus', 'system', 'Level 10 — Omniscient (10,000 XP)',  NOW())
;


-- ----------------------------------------------------------------------------
-- Sample Marketplace Items
-- Pre-seeded items to populate the marketplace on launch.
-- creator_id = 1 represents the platform / GoSiteMe official publisher.
-- ----------------------------------------------------------------------------
INSERT INTO `alfred_marketplace_items`
    (`creator_id`, `type`, `name`, `slug`, `description`, `category`, `version`, `price`, `pricing_model`, `rating`, `review_count`, `install_count`, `status`, `published_at`, `created_at`)
VALUES
    -- Official Tools
    (1, 'tool', 'SEO Audit Pro',
        'seo-audit-pro',
        'Comprehensive SEO audit tool that analyzes on-page, off-page, and technical SEO factors. Generates actionable reports with prioritized recommendations.',
        'seo', '1.0.0', 0.00, 'free', 4.80, 42, 1350, 'published', NOW(), NOW()),

    (1, 'tool', 'Database Migration Wizard',
        'db-migration-wizard',
        'Safely migrate databases between MySQL, PostgreSQL, and SQLite with automatic schema translation and data validation.',
        'devops', '1.2.0', 4.99, 'one_time', 4.65, 28, 820, 'published', NOW(), NOW()),

    (1, 'tool', 'SSL Certificate Manager',
        'ssl-cert-manager',
        'Automated SSL certificate lifecycle management: issue, renew, revoke, and monitor expiration dates across all your domains.',
        'security', '2.0.0', 0.00, 'free', 4.90, 67, 2100, 'published', NOW(), NOW()),

    -- Official Agents
    (1, 'agent', 'Sales Closer Agent',
        'sales-closer-agent',
        'AI sales agent trained on consultative selling techniques. Handles inbound leads, qualifies prospects, and closes deals via voice or chat.',
        'sales', '1.0.0', 29.99, 'monthly', 4.70, 35, 480, 'published', NOW(), NOW()),

    (1, 'agent', 'Customer Support Agent — Tier 1',
        'support-agent-tier1',
        'Front-line support agent that handles FAQs, account inquiries, billing questions, and basic troubleshooting. Escalates complex issues.',
        'support', '1.5.0', 19.99, 'monthly', 4.85, 89, 1620, 'published', NOW(), NOW()),

    (1, 'agent', 'Legal Research Agent — Canadian Law',
        'legal-research-canada',
        'Specialized legal research agent trained on Canadian federal and provincial law. Searches CanLII, drafts motions, and cites precedents.',
        'legal', '1.0.0', 49.99, 'monthly', 4.55, 12, 210, 'published', NOW(), NOW()),

    -- Official Playbooks
    (1, 'playbook', 'New Website Launch Checklist',
        'website-launch-checklist',
        'Complete 47-step playbook for launching a new website: DNS, SSL, performance, SEO, analytics, security hardening, and go-live verification.',
        'devops', '1.0.0', 0.00, 'free', 4.92, 53, 3200, 'published', NOW(), NOW()),

    (1, 'playbook', 'HIPAA Compliance Audit',
        'hipaa-compliance-audit',
        'Step-by-step HIPAA compliance audit playbook for healthcare applications. Covers technical safeguards, access controls, and documentation.',
        'healthcare', '1.0.0', 9.99, 'one_time', 4.60, 8, 145, 'published', NOW(), NOW()),

    -- Official Bundles
    (1, 'bundle', 'Startup Launch Kit',
        'startup-launch-kit',
        'Everything a startup needs: website builder tools, SEO audit, email setup, CRM integration, invoicing, and legal document templates. Includes 3 pre-configured agents.',
        'business', '1.0.0', 79.99, 'one_time', 4.75, 22, 390, 'published', NOW(), NOW()),

    (1, 'bundle', 'Call Center in a Box',
        'call-center-box',
        'Complete call center solution: 5 pre-trained support agents, queue management playbook, KPI dashboard, and customer satisfaction survey tools.',
        'support', '1.0.0', 149.99, 'monthly', 4.88, 18, 95, 'published', NOW(), NOW())
;


-- ----------------------------------------------------------------------------
-- Sample Marketplace Reviews (for the seeded items above)
-- ----------------------------------------------------------------------------
INSERT INTO `alfred_marketplace_reviews` (`item_id`, `user_id`, `rating`, `review_text`, `helpful_count`, `created_at`)
VALUES
    (1, 100, 5, 'Best free SEO tool I have found. The report is incredibly detailed and the recommendations are actionable. Saved me hours of manual auditing.', 12, NOW()),
    (1, 101, 5, 'Used this for 3 client sites. Found issues I completely missed. The priority ranking is spot-on.', 8, NOW()),
    (1, 102, 4, 'Great tool overall. Would love to see international SEO checks added in a future update.', 5, NOW()),
    (5, 103, 5, 'This support agent handles 80% of our tickets without escalation. Absolute game changer for our small team.', 22, NOW()),
    (5, 104, 5, 'Set it up in 10 minutes and it was already answering customer questions. The tone is natural and professional.', 15, NOW()),
    (7, 105, 5, 'Followed the checklist step by step for our product launch. Nothing fell through the cracks. Essential playbook.', 18, NOW()),
    (10, 106, 5, 'Replaced our entire outsourced call center. 5 agents, 24/7 coverage, fraction of the cost. Mind-blowing.', 31, NOW())
;


-- ============================================================================
-- RE-ENABLE FOREIGN KEY CHECKS
-- ============================================================================
SET FOREIGN_KEY_CHECKS = 1;


-- ============================================================================
-- END OF SCHEMA
-- ============================================================================
