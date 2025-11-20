# Quick Testing Guide

This guide helps you quickly test the changes made to the PC Hardware Inventory system.

## Prerequisites
- MySQL/MariaDB installed
- PHP 7.4+ installed
- Web server (Apache/Nginx) or PHP built-in server
- Sample CSV file available (`sample_hardware.csv`)

## Setup Test Environment

### 1. Install Fresh Database
```bash
# Create database and import schema
mysql -u root -p
```
```sql
DROP DATABASE IF EXISTS pc_inventory;
source database.sql;
exit;
```

### 2. Start Web Server
```bash
# Using PHP built-in server
cd /path/to/PC_hardware_inventory
php -S localhost:8000
```

### 3. Login
- URL: http://localhost:8000/login.php
- Username: `admin`
- Password: `password123`

## Test Cases

### Test 1: Location Datalist
**Purpose:** Verify location dropdown works correctly

1. Navigate to Hardware page
2. Click "Add Hardware" button
3. Check the Location field
4. ✅ Should show a datalist with suggestions (Lab 1, Lab 2, etc.)
5. Try typing a location
6. Try selecting from dropdown
7. Submit the form
8. ✅ Hardware should be created with the location

**Expected Result:** Location field allows both selection and custom input

### Test 2: CSV Import on Hardware Page
**Purpose:** Verify CSV import is accessible from Hardware page

1. Navigate to Hardware page
2. ✅ "Import CSV" button should be visible next to "Add Hardware"
3. ✅ Import CSV button should NOT be in the navbar
4. Click "Import CSV" button
5. ✅ Modal should open with file upload
6. Select `sample_hardware.csv`
7. ✅ Preview should show first 5 rows
8. Click "Import"
9. ✅ Records should be imported successfully
10. Check History page
11. ✅ All imported items should be in history with action "Added"

**Expected Result:** CSV import works from Hardware page, not from navbar

### Test 3: Hardware Deletion with History
**Purpose:** Verify hardware deletion saves complete history

1. Navigate to Hardware page
2. Note down a hardware item's details (name, serial, category)
3. Click "Delete" on that item
4. Confirm deletion
5. ✅ Item should be deleted
6. Navigate to History page
7. ✅ Find the deleted item in history
8. ✅ History should show:
   - Hardware name (not "null" or "Deleted Item")
   - Category name
   - Serial number
   - Action type: "Deleted"
   - Badge: "Deleted from System"
   - User who deleted it

**Expected Result:** Complete history preserved even after deletion

### Test 4: User Deletion (No Foreign Key Error)
**Purpose:** Verify users can be deleted without errors

**⚠️ CRITICAL TEST - This was the main bug**

1. Login as admin
2. Navigate to Users page
3. Create a test user (e.g., "testuser")
4. Logout and login as the test user
5. Add a hardware item
6. Logout and login as admin
7. Go to Users page
8. Delete the test user
9. ✅ User should be deleted WITHOUT any error
10. ✅ NO error message: "Cannot delete or update a parent row: a foreign key constraint fails"
11. Navigate to History page
12. ✅ History entries by deleted user should still show the username

**Expected Result:** User deleted successfully, history preserved

### Test 5: History Shows Denormalized Data
**Purpose:** Verify history shows saved data, not references

1. Add a hardware item "Test Item A" in category "CPU"
2. Navigate to History
3. ✅ History shows "Test Item A" and "CPU"
4. Go back to Hardware
5. Edit "Test Item A" to "Test Item B" and change category to "RAM"
6. Navigate to History
7. ✅ Both history entries exist:
   - Old entry shows "Test Item A" with "CPU"
   - New entry shows "Test Item B" with "RAM"
8. Delete the hardware item
9. Navigate to History
10. ✅ All three history entries still show the correct names

**Expected Result:** History always shows the actual data at the time of action

### Test 6: Multiple Locations
**Purpose:** Verify location consistency

1. Add hardware with location "Lab 1"
2. Add hardware with location "Lab 2"
3. Add hardware with location "Office"
4. Edit any hardware
5. ✅ Location dropdown should now include all three locations
6. Try adding custom location "Storage Room"
7. ✅ Should accept custom location
8. Edit another hardware
9. ✅ Location dropdown now includes "Storage Room"

**Expected Result:** Location datalist adapts to existing locations

## Migration Testing (For Existing Installations)

### Test 7: Database Migration
**Purpose:** Verify migration works on existing database

1. Create backup: `mysqldump -u root -p pc_inventory > backup.sql`
2. Add some test data (hardware, users, history)
3. Run migration: `mysql -u root -p pc_inventory < migration_denormalize_history.sql`
4. ✅ Migration should complete without errors
5. Check existing history records
6. ✅ All should have hardware_name, user_name populated
7. Test user deletion
8. ✅ Should work without foreign key errors

**Expected Result:** Migration preserves all data and removes constraints

## Common Issues

### Issue: "Column already exists"
**Solution:** Migration was already run or partial run. Check current schema.

### Issue: CSV import fails
**Solution:** Check CSV format matches: name,category_id,type,brand,model,serial_number,unused_quantity,in_use_quantity,damaged_quantity,repair_quantity,location

### Issue: Location dropdown doesn't show
**Solution:** Clear browser cache or check JavaScript console for errors

### Issue: History shows "null" for deleted items
**Solution:** Ensure denormalized data is being saved. Check PHP error logs.

## Success Criteria

All tests should pass with:
- ✅ Location datalist works in Add and Edit forms
- ✅ CSV import button appears on Hardware page only
- ✅ CSV import works and logs history
- ✅ Hardware deletion preserves complete history
- ✅ User deletion works without foreign key errors
- ✅ History displays denormalized data correctly
- ✅ Migration script works on existing databases

## Reporting Issues

If any test fails, please report with:
1. Test case number
2. Expected result
3. Actual result
4. Error messages (if any)
5. Browser and PHP version
6. MySQL version

## Performance Check

After testing, verify:
- History page loads in < 2 seconds (100 records)
- Hardware page loads in < 1 second
- CSV import handles at least 100 records
- No memory errors during import

---

**Testing Completed:** ___/___/____
**Tested By:** _________________
**Result:** PASS / FAIL
**Notes:** _____________________
