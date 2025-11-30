-- ==========================================
-- MIGRATION: Add user login tracking fields
-- ==========================================
-- This migration adds fields to track user login activity:
-- - last_login: Timestamp of the user's most recent login
-- - last_login_duration: Duration of the user's last session in seconds
-- - is_active: Whether user is currently logged in (1) or not (0)
-- - last_activity: Timestamp of user's last activity for timeout detection
--
-- Run this migration if you have an existing database
-- ==========================================

USE pc_inventory;

-- Add last_login column if it doesn't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL DEFAULT NULL;

-- Add last_login_duration column if it doesn't exist  
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login_duration INT NULL DEFAULT NULL;

-- Add is_active column to track if user is currently logged in
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 0;

-- Add last_activity column to track user's last activity
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_activity TIMESTAMP NULL DEFAULT NULL;

-- Note: For MySQL versions that don't support IF NOT EXISTS, use:
-- ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL;
-- ALTER TABLE users ADD COLUMN last_login_duration INT NULL DEFAULT NULL;
-- ALTER TABLE users ADD COLUMN is_active TINYINT(1) DEFAULT 0;
-- ALTER TABLE users ADD COLUMN last_activity TIMESTAMP NULL DEFAULT NULL;
-- (These will fail harmlessly if columns already exist)
