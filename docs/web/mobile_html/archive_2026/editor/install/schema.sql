-- GoCodeMe Online Editor Database Schema
-- Run this in your MySQL database

-- Projects table - stores user projects
CREATE TABLE IF NOT EXISTS `editor_projects` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NOT NULL COMMENT 'WHMCS user ID',
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `html_content` LONGTEXT NULL,
    `css_content` LONGTEXT NULL,
    `js_content` LONGTEXT NULL,
    `thumbnail` VARCHAR(500) NULL,
    `is_public` TINYINT(1) DEFAULT 0,
    `is_published` TINYINT(1) DEFAULT 0,
    `published_url` VARCHAR(500) NULL,
    `published_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_slug` (`slug`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI Generation history - track AI usage and costs
CREATE TABLE IF NOT EXISTS `editor_ai_history` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NOT NULL,
    `project_id` INT(11) UNSIGNED NULL,
    `prompt` TEXT NOT NULL,
    `response` LONGTEXT NULL,
    `model` VARCHAR(50) NOT NULL DEFAULT 'gpt-4',
    `tokens_used` INT(11) DEFAULT 0,
    `cost_cents` DECIMAL(10,4) DEFAULT 0,
    `status` ENUM('pending', 'success', 'error') DEFAULT 'pending',
    `error_message` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_project_id` (`project_id`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User settings/preferences
CREATE TABLE IF NOT EXISTS `editor_user_settings` (
    `user_id` INT(11) UNSIGNED NOT NULL,
    `theme` VARCHAR(20) DEFAULT 'dark',
    `font_size` INT(3) DEFAULT 14,
    `auto_save` TINYINT(1) DEFAULT 1,
    `ai_monthly_limit` INT(11) DEFAULT 100 COMMENT 'AI generations per month',
    `ai_used_this_month` INT(11) DEFAULT 0,
    `ftp_host` VARCHAR(255) NULL,
    `ftp_user` VARCHAR(255) NULL,
    `ftp_pass_encrypted` TEXT NULL,
    `ftp_path` VARCHAR(500) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Project versions (for history/undo)
CREATE TABLE IF NOT EXISTS `editor_project_versions` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `project_id` INT(11) UNSIGNED NOT NULL,
    `version_number` INT(11) NOT NULL DEFAULT 1,
    `html_content` LONGTEXT NULL,
    `css_content` LONGTEXT NULL,
    `js_content` LONGTEXT NULL,
    `commit_message` VARCHAR(500) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_project_id` (`project_id`),
    KEY `idx_version` (`project_id`, `version_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Templates library
CREATE TABLE IF NOT EXISTS `editor_templates` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `thumbnail` VARCHAR(500) NULL,
    `html_content` LONGTEXT NULL,
    `css_content` LONGTEXT NULL,
    `js_content` LONGTEXT NULL,
    `is_premium` TINYINT(1) DEFAULT 0,
    `sort_order` INT(11) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_slug` (`slug`),
    KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
