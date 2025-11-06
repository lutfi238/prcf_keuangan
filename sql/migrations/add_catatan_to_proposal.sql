-- Migration: Add catatan_fm column to proposal table
-- Description: Adds column to store Finance Manager's revision notes when rejecting proposals
-- Date: 2025-11-06

-- Add catatan_fm column to proposal table
ALTER TABLE `proposal` 
ADD COLUMN IF NOT EXISTS `catatan_fm` TEXT NULL DEFAULT NULL AFTER `status`;

