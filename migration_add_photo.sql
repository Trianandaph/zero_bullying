-- Migration: Add photo column to posts table
-- Date: 2026-03-03

ALTER TABLE `posts` ADD COLUMN `photo` VARCHAR(255) NULL DEFAULT NULL AFTER `content`;

-- Update posts table structure to include photo field
-- This allows users to upload images with their posts
