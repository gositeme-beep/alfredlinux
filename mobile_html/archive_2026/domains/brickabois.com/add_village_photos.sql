-- Add village photos table
CREATE TABLE IF NOT EXISTS `village_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `village_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `photo_url` varchar(500) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `caption_fr` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_village` (`village_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_primary` (`village_id`, `is_primary`),
  FOREIGN KEY (`village_id`) REFERENCES `villages`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add photo_url column to villages table for quick access to primary photo
ALTER TABLE `villages` ADD COLUMN IF NOT EXISTS `photo_url` varchar(500) DEFAULT NULL AFTER `website_url`;

