-- Run this SQL in your database to create the gallery table
-- Copy and paste this into phpMyAdmin SQL tab

CREATE TABLE IF NOT EXISTS `bio_gallery` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `image_order` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `image_order` (`image_order`),
  CONSTRAINT `bio_gallery_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Make sure all _enabled columns exist for all platforms
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `facebook_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `instagram_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `twitter_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `linkedin_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `youtube_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `tiktok_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `github_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `pinterest_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `snapchat_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `discord_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `twitch_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `telegram_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `whatsapp_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `spotify_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `reddit_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `website_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `email_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `phone_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `threads_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `bluesky_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `mastodon_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `medium_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `substack_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `patreon_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `onlyfans_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `cashapp_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `venmo_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `paypal_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `bio_links` ADD COLUMN IF NOT EXISTS `line_enabled` TINYINT(1) DEFAULT 1;
