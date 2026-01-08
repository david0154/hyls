-- Social Media Videos Table
CREATE TABLE IF NOT EXISTS `bio_social_videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bio_profile_id` int(11) NOT NULL,
  `platform` varchar(50) NOT NULL,
  `video_url` text NOT NULL,
  `embed_code` text NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `autoplay` tinyint(1) DEFAULT 1,
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `bio_profile_id` (`bio_profile_id`),
  KEY `platform` (`platform`),
  FOREIGN KEY (`bio_profile_id`) REFERENCES `bio_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update bio_gallery table to add crop dimensions
ALTER TABLE `bio_gallery` 
  ADD COLUMN `crop_x` int(11) DEFAULT 0,
  ADD COLUMN `crop_y` int(11) DEFAULT 0,
  ADD COLUMN `crop_width` int(11) DEFAULT NULL,
  ADD COLUMN `crop_height` int(11) DEFAULT NULL,
  ADD COLUMN `original_width` int(11) DEFAULT NULL,
  ADD COLUMN `original_height` int(11) DEFAULT NULL;

-- Update bio_profiles table for cover image crop
ALTER TABLE `bio_profiles`
  ADD COLUMN `cover_crop_x` int(11) DEFAULT 0,
  ADD COLUMN `cover_crop_y` int(11) DEFAULT 0,
  ADD COLUMN `cover_crop_width` int(11) DEFAULT NULL,
  ADD COLUMN `cover_crop_height` int(11) DEFAULT NULL;