-- ==========================================
-- MIGRATION SCRIPT: Denormalize Inventory History
-- ==========================================
-- This migration removes foreign key constraints from inventory_history
-- and adds denormalized fields to prevent deletion errors
-- Run this script on existing databases to upgrade to the new schema
-- ==========================================

USE pc_inventory;

-- Step 1: Drop foreign key constraints if they exist
-- Note: Constraint names may vary, so we use ALTER TABLE with IF EXISTS style checks

SET @constraint_check = (
    SELECT COUNT(*) 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = 'pc_inventory' 
    AND TABLE_NAME = 'inventory_history' 
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

-- Drop foreign keys if they exist
ALTER TABLE inventory_history DROP FOREIGN KEY IF EXISTS inventory_history_ibfk_1;
ALTER TABLE inventory_history DROP FOREIGN KEY IF EXISTS inventory_history_ibfk_2;

-- Alternative method for older MySQL versions that don't support IF EXISTS
-- You may need to run these individually if the above doesn't work
-- ALTER TABLE inventory_history DROP FOREIGN KEY inventory_history_ibfk_1;
-- ALTER TABLE inventory_history DROP FOREIGN KEY inventory_history_ibfk_2;

-- Step 2: Add new denormalized columns
ALTER TABLE inventory_history 
ADD COLUMN IF NOT EXISTS hardware_name VARCHAR(255) DEFAULT NULL AFTER hardware_id,
ADD COLUMN IF NOT EXISTS category_name VARCHAR(100) DEFAULT NULL AFTER hardware_name,
ADD COLUMN IF NOT EXISTS serial_number VARCHAR(100) DEFAULT NULL AFTER category_name,
ADD COLUMN IF NOT EXISTS user_name VARCHAR(255) DEFAULT NULL AFTER user_id;

-- Step 3: Populate existing records with denormalized data
-- This backfills the new columns with data from related tables
UPDATE inventory_history ih
LEFT JOIN hardware h ON ih.hardware_id = h.id
LEFT JOIN users u ON ih.user_id = u.id
LEFT JOIN categories c ON h.category_id = c.id
SET 
    ih.hardware_name = COALESCE(ih.hardware_name, h.name),
    ih.category_name = COALESCE(ih.category_name, c.name),
    ih.serial_number = COALESCE(ih.serial_number, h.serial_number),
    ih.user_name = COALESCE(ih.user_name, u.full_name)
WHERE ih.hardware_name IS NULL OR ih.user_name IS NULL;

-- Step 4: Make hardware_id and user_id nullable (optional references)
ALTER TABLE inventory_history 
MODIFY COLUMN hardware_id INT DEFAULT NULL,
MODIFY COLUMN user_id INT DEFAULT NULL;

-- Step 5: Update NOT NULL constraint on hardware_name
ALTER TABLE inventory_history 
MODIFY COLUMN hardware_name VARCHAR(255) NOT NULL;

-- Step 6: Verify the changes
SELECT 
    'Migration completed successfully!' as Status,
    COUNT(*) as TotalHistoryRecords,
    SUM(CASE WHEN hardware_name IS NOT NULL THEN 1 ELSE 0 END) as RecordsWithHardwareName,
    SUM(CASE WHEN user_name IS NOT NULL THEN 1 ELSE 0 END) as RecordsWithUserName
FROM inventory_history;

-- Optional: Show the new table structure
DESCRIBE inventory_history;
