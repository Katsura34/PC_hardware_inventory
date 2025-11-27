-- ==========================================
-- Migration: Add Soft Delete to Hardware Table
-- ==========================================

-- Add deleted_at column for soft deletes
ALTER TABLE hardware ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- Create index for better performance when filtering deleted items
CREATE INDEX idx_hardware_deleted_at ON hardware(deleted_at);
