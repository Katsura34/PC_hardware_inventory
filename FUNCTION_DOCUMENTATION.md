# PC Hardware Inventory System - Function Reference (By Page)

---

## LOGIN PAGE (login.php)

**Purpose**: User authentication page

### What's Inside:
| Function/Feature | What It Does |
|-----------------|--------------|
| Login Form | Accepts username and password |
| `verifyPassword()` | Checks if password is correct |
| `setUserSession()` | Creates session after successful login |
| Remember Me | Saves username in cookie for 30 days |
| Password Toggle | Shows/hides password field |

### How It Works:
1. User enters username and password
2. System checks credentials in database
3. If correct → creates session → goes to dashboard
4. If wrong → shows error message

---

## LOGOUT PAGE (logout.php)

**Purpose**: Ends user session

### What's Inside:
| Function/Feature | What It Does |
|-----------------|--------------|
| `clearSession()` | Destroys all session data |
| Redirect | Sends user back to login page |

---

## DASHBOARD (dashboard.php)

**Purpose**: Shows overview and statistics

### What's Inside:
| Function/Feature | What It Does |
|-----------------|--------------|
| `requireLogin()` | Blocks access if not logged in |
| Total Hardware Count | Shows number of hardware items |
| Quantity Statistics | Shows total, available, in-use, damaged, repair counts |
| Recent Hardware | Lists last 5 added items |
| Low Stock Alert | Shows items with less than 2 available |
| Categories Summary | Shows item count per category |

### Statistics Cards:
- Total Items
- Total Quantity
- Available
- In Use
- Damaged
- In Repair

---

## HARDWARE PAGE (pages/hardware.php)

**Purpose**: Main page for managing hardware inventory

### What's Inside:

#### View Functions:
| Function/Feature | What It Does |
|-----------------|--------------|
| Hardware Table | Lists all hardware with details |
| Search | Filters table by text |
| Filter by Category | Shows only selected category |
| Filter by Brand | Shows only selected brand |
| Filter by Model | Shows only selected model |
| Pagination | Shows 20 items per page |
| View Trash | Shows deleted items (admin only) |

#### Add Functions:
| Function/Feature | What It Does |
|-----------------|--------------|
| Add Hardware Modal | Form to create new hardware |
| Add Category | Creates new category inline |
| Add Location | Creates new location inline |

#### Edit Functions:
| Function/Feature | What It Does |
|-----------------|--------------|
| `editHardware()` | Opens edit modal with item data |
| Edit Modal | Form to update hardware details |
| Batch Status Update | Changes status for multiple items |

#### Delete Functions:
| Function/Feature | What It Does |
|-----------------|--------------|
| Delete | Soft deletes item (can restore) |
| Batch Delete | Deletes multiple selected items |
| Restore | Brings back deleted item (admin) |
| Permanent Delete | Removes forever (admin) |

#### Import/Export Functions:
| Function/Feature | What It Does |
|-----------------|--------------|
| Import CSV | Bulk import from CSV file |
| Export CSV | Download hardware as CSV |
| `exportHardwareToCSV()` | Creates CSV file |
| `exportFilteredCSV()` | Exports with category filter |

#### Batch Operations:
| Function/Feature | What It Does |
|-----------------|--------------|
| Select All Checkbox | Selects all items |
| `toggleSelectAll()` | Checks/unchecks all |
| `getSelectedIds()` | Gets IDs of checked items |
| `batchDelete()` | Deletes all selected |
| `showBatchStatusModal()` | Opens status update form |

#### Helper Functions:
| Function/Feature | What It Does |
|-----------------|--------------|
| `toggleHardwareSearch()` | Shows/hides search panel |
| `clearHardwareSearch()` | Clears search input |
| `confirmRestore()` | Confirms restore action |
| `confirmPermanentDelete()` | Confirms permanent delete |

---

## USERS PAGE (pages/users.php) - Admin Only

**Purpose**: Manage user accounts

### What's Inside:

#### View Functions:
| Function/Feature | What It Does |
|-----------------|--------------|
| `requireAdmin()` | Blocks non-admin users |
| Users Table | Lists all user accounts |
| Search | Filters users by name/username |
| Pagination | Shows 20 users per page |

#### Add Functions:
| Function/Feature | What It Does |
|-----------------|--------------|
| Add User Modal | Form to create new user |
| `hashPassword()` | Encrypts password |

#### Edit Functions:
| Function/Feature | What It Does |
|-----------------|--------------|
| `editUser()` | Opens edit modal |
| Edit Modal | Form to update user |
| Password Change | Optional - leave blank to keep current |

#### Delete Functions:
| Function/Feature | What It Does |
|-----------------|--------------|
| Delete User | Removes user account |
| Self-Delete Prevention | Cannot delete your own account |

#### Helper Functions:
| Function/Feature | What It Does |
|-----------------|--------------|
| `toggleSearchFilter()` | Shows/hides search |
| `clearSearch()` | Clears search input |

---

## HISTORY PAGE (pages/history.php)

**Purpose**: Audit trail - shows all inventory changes

### What's Inside:

#### View Functions:
| Function/Feature | What It Does |
|-----------------|--------------|
| `requireLogin()` | Blocks if not logged in |
| History Table | Lists all changes |
| Search | Filters by text |
| Pagination | Shows 20 records per page |

#### Filter Functions:
| Function/Feature | What It Does |
|-----------------|--------------|
| Filter by Action | Added, Updated, Deleted, Restored |
| Filter by Date From | Start date |
| Filter by Date To | End date |
| Clear Filters | Removes all filters |

#### Display Information:
| Column | What It Shows |
|--------|--------------|
| Date & Time | When change happened |
| Hardware Item | Name and serial number |
| Category | Hardware category |
| Action Type | Added/Updated/Deleted/Restored |
| Modified By | User who made change |
| Quantity Change | How much changed (+/-) |
| Previous Status | Before values |
| New Status | After values |

#### Helper Functions:
| Function/Feature | What It Does |
|-----------------|--------------|
| `toggleHistorySearch()` | Shows/hides search |
| `clearHistorySearch()` | Clears search input |

---

## BACKUP PAGE (pages/backup.php) - Admin Only

**Purpose**: Database backup and restore

### What's Inside:

#### Backup Functions:
| Function/Feature | What It Does |
|-----------------|--------------|
| `requireAdmin()` | Blocks non-admin users |
| Create Backup | Generates SQL backup file |
| Backup List | Shows all available backups |
| Download Backup | Downloads .sql file |
| Delete Backup | Removes backup file |

#### Restore Functions:
| Function/Feature | What It Does |
|-----------------|--------------|
| Restore Button | Starts restore process |
| `confirmRestore()` | Shows confirmation modal |
| Restore Process | Replaces database with backup |

#### Backup Information:
- Filename with timestamp
- File size
- Creation date

---

## CSV IMPORT (pages/import_csv.php)

**Purpose**: AJAX endpoint for importing CSV files

### What's Inside:
| Function/Feature | What It Does |
|-----------------|--------------|
| `requireLogin()` | Blocks if not logged in |
| File Upload | Receives CSV file |
| Parse CSV | Reads rows from file |
| Category Lookup | Finds or creates categories |
| Duplicate Check | Adds to existing if duplicate |
| Insert New | Creates new hardware record |
| History Logging | Records import in history |

### CSV Format:
```
name,category,type,brand,model,serial_number,unused_quantity,in_use_quantity,damaged_quantity,repair_quantity,location
```

---

## HEADER (includes/header.php)

**Purpose**: Common page header and navigation

### What's Inside:
| Function/Feature | What It Does |
|-----------------|--------------|
| HTML Head | Title, CSS links |
| Navigation Bar | Menu links |
| User Dropdown | Shows logged-in user, logout |
| `getFlashMessage()` | Displays success/error messages |
| Active Page Highlight | Shows current page |

### Navigation Menu:
- Dashboard
- Hardware
- Audit Trail
- Users (admin only)
- Backup (admin only)

---

## FOOTER (includes/footer.php)

**Purpose**: Common page footer

### What's Inside:
| Function/Feature | What It Does |
|-----------------|--------------|
| Footer Text | Version, copyright |
| Bootstrap JS | JavaScript framework |
| main.js | Custom JavaScript functions |
| ui-enhancements.js | UI helper functions |
| Double-Submit Prevention | Disables button after click |

---

## CONFIG FILES

### config/database.php
| Function | What It Does |
|----------|--------------|
| `getDBConnection()` | Creates database connection |
| `closeDBConnection()` | Closes database connection |

### config/session.php
| Function | What It Does |
|----------|--------------|
| `isLoggedIn()` | Checks if user is logged in |
| `isAdmin()` | Checks if user is admin |
| `requireLogin()` | Redirects if not logged in |
| `requireAdmin()` | Redirects if not admin |
| `getCurrentUser()` | Gets current user info |
| `setUserSession()` | Sets session after login |
| `clearSession()` | Destroys session (logout) |

### config/security.php
| Function | What It Does |
|----------|--------------|
| `sanitizeInput()` | Cleans user input |
| `sanitizeForDB()` | Makes safe for database |
| `hashPassword()` | Encrypts password |
| `verifyPassword()` | Checks password |
| `escapeOutput()` | Prevents XSS attacks |
| `validateInt()` | Validates integer |
| `redirectWithMessage()` | Redirects with message |
| `getFlashMessage()` | Gets flash message |

### config/base.php
| Constant | What It Does |
|----------|--------------|
| `BASE_PATH` | Application base URL |

---

## JAVASCRIPT FILES

### assets/js/main.js
| Function | What It Does |
|----------|--------------|
| `showLoading()` | Shows loading spinner |
| `hideLoading()` | Hides loading spinner |
| `showConfirmation()` | Shows confirm dialog |
| `confirmDelete()` | Confirms delete action |
| `showAlert()` | Shows alert popup |
| `searchTable()` | Filters table rows |
| `calculateTotal()` | Adds up quantities |
| `showToast()` | Shows notification |

### assets/js/ui-enhancements.js
| Function | What It Does |
|----------|--------------|
| `HCI.Toast.show()` | Shows toast message |
| `HCI.announce()` | Screen reader announcement |

---

## DEFAULT LOGIN

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | password123 |
| Staff | staff01 | password123 |

---

*ACLC College of Ormoc - PC Hardware Inventory System v2.0*
