-- Fix cover_image column in bio_links table
-- Run this in phpMyAdmin

-- Check if cover_image column exists, if not add it
ALTER TABLE `bio_links` 
ADD COLUMN IF NOT EXISTS `cover_image` VARCHAR(255) DEFAULT '' AFTER `profile_image`;

-- Verify it was added
SHOW COLUMNS FROM `bio_links` LIKE 'cover_image';