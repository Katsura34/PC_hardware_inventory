# Database Migration Guide

## Overview
This guide explains how to migrate your existing PC Hardware Inventory database to the new schema that uses denormalized data in the inventory_history table.

## Why This Migration?
The previous version had foreign key constraints on the `inventory_history` table that referenced `hardware` and `users` tables. This caused errors when trying to delete users or hardware items:
- **Error**: `Cannot delete or update a parent row: a foreign key constraint fails`

The new schema stores actual data (names, serial numbers, etc.) directly in the history table instead of just IDs, making the history permanent and independent.

## For New Installations
If you're installing the system for the first time, simply run:
```sql
source database.sql
```

This will create all tables with the correct structure.

## For Existing Installations
If you already have data in your system, follow these steps:

### Step 1: Backup Your Database
**IMPORTANT**: Always backup before running migrations!
```bash
mysqldump -u root -p pc_inventory > backup_pc_inventory_$(date +%Y%m%d).sql
```

### Step 2: Run the Migration Script
```bash
mysql -u root -p pc_inventory < migration_denormalize_history.sql
```

Or from MySQL command line:
```sql
USE pc_inventory;
source migration_denormalize_history.sql;
```

### Step 3: Verify the Migration
After running the migration, check that:
1. The foreign key constraints are removed
2. New columns (hardware_name, category_name, serial_number, user_name) are added
3. Existing history records are populated with data
4. All records have non-NULL hardware_name values

The migration script will display a summary at the end.

## Changes Made

### Database Schema Changes:
1. **Removed Foreign Key Constraints**
   - `inventory_history_ibfk_1` (hardware_id reference)
   - `inventory_history_ibfk_2` (user_id reference)

2. **Added Denormalized Columns**
   - `hardware_name` VARCHAR(255) NOT NULL
   - `category_name` VARCHAR(100)
   - `serial_number` VARCHAR(100)
   - `user_name` VARCHAR(255)

3. **Modified Existing Columns**
   - `hardware_id` - Now nullable (INT DEFAULT NULL)
   - `user_id` - Now nullable (INT DEFAULT NULL)

### Application Changes:
1. **CSV Import** - Moved from navbar to Hardware page
2. **Location Field** - Changed to datalist (dropdown with custom input)
3. **History Logging** - All operations now save denormalized data
4. **History Display** - Shows saved data with fallback to current data

## Rollback (If Needed)
If you need to rollback, restore from your backup:
```bash
mysql -u root -p pc_inventory < backup_pc_inventory_YYYYMMDD.sql
```

## Troubleshooting

### Error: "Column already exists"
This means the migration was partially run before. You can skip those ALTER TABLE statements or drop and recreate the table.

### Error: "Cannot drop foreign key"
The constraint name might be different. Check actual constraint names:
```sql
SELECT CONSTRAINT_NAME 
FROM information_schema.TABLE_CONSTRAINTS 
WHERE TABLE_SCHEMA = 'pc_inventory' 
AND TABLE_NAME = 'inventory_history' 
AND CONSTRAINT_TYPE = 'FOREIGN KEY';
```

Then drop them manually:
```sql
ALTER TABLE inventory_history DROP FOREIGN KEY actual_constraint_name_here;
```

## Support
If you encounter issues during migration, please:
1. Check the error messages carefully
2. Ensure you have proper database permissions
3. Verify your MySQL version is compatible (5.7+ or 8.0+)
4. Create an issue on GitHub with the error details
