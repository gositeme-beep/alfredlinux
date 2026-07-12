-- Free Village Network - Database Schema
-- Three Dimensions: The Commons, The Ledger, The Land

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================
-- THE LAND: Physical Village Nodes (created first for foreign keys)
-- ============================================

-- Villages/Locations
CREATE TABLE IF NOT EXISTS `villages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `name_fr` varchar(255) DEFAULT NULL,
  `slug` varchar(255) NOT NULL UNIQUE,
  `description` text DEFAULT NULL,
  `description_fr` text DEFAULT NULL,
  `location_address` varchar(500) DEFAULT NULL,
  `location_lat` decimal(10,8) DEFAULT NULL,
  `location_lng` decimal(11,8) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Canada',
  `steward_id` int(11) DEFAULT NULL,
  `status` enum('active','forming','archived') DEFAULT 'forming',
  `founded_date` date DEFAULT NULL,
  `website_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_slug` (`slug`),
  KEY `idx_steward` (`steward_id`),
  KEY `idx_location` (`location_lat`, `location_lng`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- THE COMMONS: Social Connection & Dialogue
-- ============================================

-- Users/Members Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `email` varchar(255) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `language_preference` enum('en','fr') DEFAULT 'en',
  `village_id` int(11) DEFAULT NULL,
  `role` enum('citizen','steward','creator','admin') DEFAULT 'citizen',
  `status` enum('active','suspended','pending') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_village` (`village_id`),
  KEY `idx_email` (`email`),
  KEY `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Posts/Feed Content
CREATE TABLE IF NOT EXISTS `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `village_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `content_type` enum('text','image','video','event','story') DEFAULT 'text',
  `media_url` varchar(500) DEFAULT NULL,
  `language` enum('en','fr','both') DEFAULT 'both',
  `is_pinned` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `visibility` enum('public','village','members') DEFAULT 'public',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_village` (`village_id`),
  KEY `idx_created` (`created_at`),
  KEY `idx_visibility` (`visibility`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`village_id`) REFERENCES `villages`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comments on Posts
CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_post` (`post_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_parent` (`parent_id`),
  FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`parent_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events
CREATE TABLE IF NOT EXISTS `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `village_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `title_fr` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `description_fr` text DEFAULT NULL,
  `event_type` enum('gathering','workshop','celebration','governance','other') DEFAULT 'gathering',
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `location_lat` decimal(10,8) DEFAULT NULL,
  `location_lng` decimal(11,8) DEFAULT NULL,
  `max_attendees` int(11) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_village` (`village_id`),
  KEY `idx_start_date` (`start_date`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`village_id`) REFERENCES `villages`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event Attendees
CREATE TABLE IF NOT EXISTS `event_attendees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('attending','maybe','not_attending') DEFAULT 'attending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attendee` (`event_id`, `user_id`),
  KEY `idx_event` (`event_id`),
  KEY `idx_user` (`user_id`),
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reactions (likes, etc.)
CREATE TABLE IF NOT EXISTS `reactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `target_type` enum('post','comment','event') NOT NULL,
  `target_id` int(11) NOT NULL,
  `reaction_type` enum('like','love','support','celebrate') DEFAULT 'like',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_reaction` (`user_id`, `target_type`, `target_id`),
  KEY `idx_target` (`target_type`, `target_id`),
  KEY `idx_user` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- THE LEDGER: Governance & Transparency
-- ============================================

-- Proposals/Votes
CREATE TABLE IF NOT EXISTS `proposals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `village_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `title_fr` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `description_fr` text DEFAULT NULL,
  `proposal_type` enum('governance','treasury','policy','project') DEFAULT 'governance',
  `status` enum('draft','open','voting','passed','rejected','closed') DEFAULT 'draft',
  `voting_start` datetime DEFAULT NULL,
  `voting_end` datetime DEFAULT NULL,
  `min_quorum` int(11) DEFAULT NULL,
  `blockchain_tx_hash` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_village` (`village_id`),
  KEY `idx_status` (`status`),
  KEY `idx_voting` (`voting_start`, `voting_end`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`village_id`) REFERENCES `villages`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Votes
CREATE TABLE IF NOT EXISTS `votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proposal_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vote` enum('yes','no','abstain') NOT NULL,
  `weight` decimal(10,2) DEFAULT 1.00,
  `blockchain_tx_hash` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vote` (`proposal_id`, `user_id`),
  KEY `idx_proposal` (`proposal_id`),
  KEY `idx_user` (`user_id`),
  FOREIGN KEY (`proposal_id`) REFERENCES `proposals`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Treasury Transactions
CREATE TABLE IF NOT EXISTS `treasury_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `village_id` int(11) DEFAULT NULL,
  `transaction_type` enum('deposit','withdrawal','transfer','expense','income') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'CAD',
  `description` text NOT NULL,
  `description_fr` text DEFAULT NULL,
  `proposal_id` int(11) DEFAULT NULL,
  `blockchain_tx_hash` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_village` (`village_id`),
  KEY `idx_proposal` (`proposal_id`),
  KEY `idx_created` (`created_at`),
  FOREIGN KEY (`village_id`) REFERENCES `villages`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`proposal_id`) REFERENCES `proposals`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- THE LAND: Physical Village Nodes (continued)
-- ============================================

-- Add foreign key constraint for steward_id after users table exists
-- ALTER TABLE `villages` ADD CONSTRAINT `fk_villages_steward` FOREIGN KEY (`steward_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;

-- Village Members (many-to-many)
CREATE TABLE IF NOT EXISTS `village_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `village_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('member','steward','visitor') DEFAULT 'member',
  `joined_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_membership` (`village_id`, `user_id`),
  KEY `idx_village` (`village_id`),
  KEY `idx_user` (`user_id`),
  FOREIGN KEY (`village_id`) REFERENCES `villages`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Village Resources/Projects
CREATE TABLE IF NOT EXISTS `village_resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `village_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `resource_type` enum('ecology','art','learning','infrastructure','other') DEFAULT 'other',
  `title` varchar(255) NOT NULL,
  `title_fr` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `description_fr` text DEFAULT NULL,
  `status` enum('planned','active','completed','archived') DEFAULT 'planned',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_village` (`village_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_type` (`resource_type`),
  FOREIGN KEY (`village_id`) REFERENCES `villages`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SYSTEM TABLES
-- ============================================

-- Sessions
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `data` text DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_last_activity` (`last_activity`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `title_fr` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `message_fr` text DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_read` (`is_read`),
  KEY `idx_created` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default founding village: Sainte-Émélie-de-l'Énergie
INSERT INTO `villages` (`name`, `name_fr`, `slug`, `description`, `description_fr`, `location_lat`, `location_lng`, `location_address`, `region`, `country`, `status`, `founded_date`) 
VALUES (
  'Sainte-Émélie-de-l\'Énergie',
  'Sainte-Émélie-de-l\'Énergie',
  'sainte-emelie-de-lenergie',
  'The founding village of the Free Village Network, embodying freedom grounded in place, culture, and stewardship. Located in the heart of Lanaudière.',
  'Le village fondateur du Réseau des Villages Libres, incarnant la liberté ancrée dans le lieu, la culture et la gérance. Situé au cœur de la Lanaudière.',
  46.3167,
  -73.6333,
  'Sainte-Émélie-de-l\'Énergie, QC, Canada',
  'Lanaudière',
  'Canada',
  'active',
  CURDATE()
) ON DUPLICATE KEY UPDATE 
  `name` = 'Sainte-Émélie-de-l\'Énergie',
  `name_fr` = 'Sainte-Émélie-de-l\'Énergie',
  `description` = 'The founding village of the Free Village Network, embodying freedom grounded in place, culture, and stewardship. Located in the heart of Lanaudière.',
  `description_fr` = 'Le village fondateur du Réseau des Villages Libres, incarnant la liberté ancrée dans le lieu, la culture et la gérance. Situé au cœur de la Lanaudière.',
  `location_lat` = 46.3167,
  `location_lng` = -73.6333,
  `location_address` = 'Sainte-Émélie-de-l\'Énergie, QC, Canada',
  `status` = 'active';

-- Add foreign key constraints after all tables are created
ALTER TABLE `villages` ADD CONSTRAINT `fk_villages_steward` FOREIGN KEY (`steward_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;

