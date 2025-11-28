# PC Hardware Inventory System - Function Documentation

## Table of Contents
1. [Overview](#overview)
2. [Configuration Files](#configuration-files)
3. [Authentication Files](#authentication-files)
4. [Core Page Files](#core-page-files)
5. [Include Files](#include-files)
6. [JavaScript Functions](#javascript-functions)
7. [Database Functions](#database-functions)
8. [Security Functions](#security-functions)
9. [Common Use Cases](#common-use-cases)
10. [Defense Preparation Q&A](#defense-preparation-qa)

---

## Overview

The PC Hardware Inventory System is a web-based application designed for ACLC College of Ormoc to manage and track PC hardware components. The system follows a Model-View-Controller (MVC) inspired pattern with:

- **Configuration Files** (`config/`) - Database connection, session management, security functions
- **Include Files** (`includes/`) - Reusable header and footer templates
- **Page Files** (`pages/`) - Individual page logic and views
- **Assets** (`assets/`) - CSS stylesheets and JavaScript files

### Technology Stack
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB 10.2+
- **Frontend**: Bootstrap 5, Bootstrap Icons, Custom CSS
- **Security**: Password hashing, prepared statements, XSS prevention, session management

---

## Configuration Files

### 1. `config/database.php`

**Purpose**: Establishes and manages database connections.

#### Constants
| Constant | Value | Description |
|----------|-------|-------------|
| `DB_HOST` | `'localhost'` | Database server hostname |
| `DB_USER` | `'root'` | Database username |
| `DB_PASS` | `''` | Database password |
| `DB_NAME` | `'pc_inventory'` | Database name |

#### Functions

##### `getDBConnection()`
```php
function getDBConnection()
```
**Purpose**: Creates and returns a singleton database connection.

**Parameters**: None

**Returns**: `mysqli` - Database connection object

**Usage**:
```php
$conn = getDBConnection();
$result = $conn->query("SELECT * FROM hardware");
```

**Features**:
- Uses singleton pattern (creates only one connection per request)
- Sets UTF-8 character encoding (`utf8mb4`)
- Handles connection errors gracefully
- Logs errors to PHP error log

---

##### `closeDBConnection()`
```php
function closeDBConnection()
```
**Purpose**: Closes the active database connection.

**Parameters**: None

**Returns**: void

**Usage**:
```php
closeDBConnection(); // Called at end of script if needed
```

---

### 2. `config/session.php`

**Purpose**: Manages user sessions, authentication state, and role-based access control.

#### Dependencies
- Requires `config/base.php` for `BASE_PATH` constant

#### Session Configuration
- HTTP-only cookies enabled (prevents JavaScript access)
- Uses cookies exclusively for session transport (not URL parameters)
- SameSite: Strict (prevents CSRF attacks)
- Session regeneration every 30 minutes (prevents session fixation)

#### Functions

##### `isLoggedIn()`
```php
function isLoggedIn()
```
**Purpose**: Checks if a user is currently logged in.

**Parameters**: None

**Returns**: `bool` - `true` if user is logged in, `false` otherwise

**Usage**:
```php
if (isLoggedIn()) {
    // User is authenticated
}
```

---

##### `isAdmin()`
```php
function isAdmin()
```
**Purpose**: Checks if the current user has admin role.

**Parameters**: None

**Returns**: `bool` - `true` if user is admin, `false` otherwise

**Usage**:
```php
if (isAdmin()) {
    // Show admin-only features
}
```

---

##### `requireLogin()`
```php
function requireLogin()
```
**Purpose**: Redirects to login page if user is not authenticated.

**Parameters**: None

**Returns**: void (redirects or exits)

**Usage**:
```php
requireLogin(); // Place at top of protected pages
```

---

##### `requireAdmin()`
```php
function requireAdmin()
```
**Purpose**: Requires admin role. Redirects to dashboard if not admin, or to login if not authenticated.

**Parameters**: None

**Returns**: void (redirects or exits)

**Usage**:
```php
requireAdmin(); // Place at top of admin-only pages (users.php, backup.php)
```

---

##### `getCurrentUser()`
```php
function getCurrentUser()
```
**Purpose**: Returns current user information from session.

**Parameters**: None

**Returns**: `array|null` - User data array or null if not logged in

**Array Structure**:
```php
[
    'id' => int,
    'username' => string,
    'full_name' => string,
    'role' => string // 'admin' or 'staff'
]
```

---

##### `setUserSession($user)`
```php
function setUserSession($user)
```
**Purpose**: Sets user session data after successful login.

**Parameters**:
| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | `array` | User data from database query |

**Required Array Keys**:
- `id` - User ID
- `username` - Username
- `full_name` - Full name
- `role` - User role

**Usage**:
```php
$user = $result->fetch_assoc();
setUserSession($user);
```

---

##### `clearSession()`
```php
function clearSession()
```
**Purpose**: Completely destroys user session (logout).

**Parameters**: None

**Returns**: void

**Actions**:
- Clears all session variables
- Destroys session cookie
- Destroys session data

---

### 3. `config/security.php`

**Purpose**: Provides security helper functions for input sanitization, output escaping, password handling, and validation.

#### Functions

##### `sanitizeInput($data)`
```php
function sanitizeInput($data)
```
**Purpose**: Sanitizes user input for display (trims, strips slashes, escapes HTML).

**Parameters**:
| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | `mixed` | Input data (string or array) |

**Returns**: `mixed` - Sanitized data

**Usage**:
```php
$username = sanitizeInput($_POST['username']);
```

---

##### `sanitizeForDB($conn, $data)`
```php
function sanitizeForDB($conn, $data)
```
**Purpose**: Sanitizes input for safe database insertion.

**Parameters**:
| Parameter | Type | Description |
|-----------|------|-------------|
| `$conn` | `mysqli` | Database connection |
| `$data` | `string` | Input data |

**Returns**: `string` - Database-safe string

**Usage**:
```php
$conn = getDBConnection();
$name = sanitizeForDB($conn, $_POST['name']);
```

---

##### `generateCSRFToken()`
```php
function generateCSRFToken()
```
**Purpose**: Generates a CSRF token for form protection.

**Parameters**: None

**Returns**: `string` - 64-character hex token

**Usage**:
```php
// In form
<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
```

---

##### `verifyCSRFToken($token)`
```php
function verifyCSRFToken($token)
```
**Purpose**: Verifies submitted CSRF token matches session token.

**Parameters**:
| Parameter | Type | Description |
|-----------|------|-------------|
| `$token` | `string` | Submitted token |

**Returns**: `bool` - `true` if token is valid

---

##### `hashPassword($password)`
```php
function hashPassword($password)
```
**Purpose**: Creates secure hash of password using PHP's `password_hash()`.

**Parameters**:
| Parameter | Type | Description |
|-----------|------|-------------|
| `$password` | `string` | Plain text password |

**Returns**: `string` - Hashed password

**Usage**:
```php
$hashed = hashPassword($_POST['password']);
```

---

##### `verifyPassword($password, $hash)`
```php
function verifyPassword($password, $hash)
```
**Purpose**: Verifies password against stored hash.

**Parameters**:
| Parameter | Type | Description |
|-----------|------|-------------|
| `$password` | `string` | Plain text password |
| `$hash` | `string` | Stored hash |

**Returns**: `bool` - `true` if password matches

---

##### `escapeOutput($data)`
```php
function escapeOutput($data)
```
**Purpose**: Escapes output for safe HTML display (prevents XSS).

**Parameters**:
| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | `string` | Data to escape |

**Returns**: `string` - HTML-safe string

**Usage**:
```php
echo escapeOutput($user['full_name']);
```

---

##### `validateInt($value)`
```php
function validateInt($value)
```
**Purpose**: Validates that a value is a valid integer.

**Parameters**:
| Parameter | Type | Description |
|-----------|------|-------------|
| `$value` | `mixed` | Value to validate |

**Returns**: `bool` - `true` if valid integer

---

##### `redirectWithMessage($location, $message, $type)`
```php
function redirectWithMessage($location, $message, $type = 'success')
```
**Purpose**: Redirects to a page with a flash message.

**Parameters**:
| Parameter | Type | Description |
|-----------|------|-------------|
| `$location` | `string` | URL to redirect to |
| `$message` | `string` | Message to display |
| `$type` | `string` | Message type: 'success', 'error', 'warning', 'info' |

**Usage**:
```php
redirectWithMessage(BASE_PATH . 'pages/hardware.php', 'Item added successfully', 'success');
```

---

##### `getFlashMessage()`
```php
function getFlashMessage()
```
**Purpose**: Retrieves and clears flash message from session.

**Parameters**: None

**Returns**: `array|null` - Message data or null
```php
['message' => string, 'type' => string]
```

---

### 4. `config/base.php`

**Purpose**: Dynamically detects and sets the `BASE_PATH` constant for URL generation.

#### Constants
| Constant | Description |
|----------|-------------|
| `BASE_PATH` | Application base path (e.g., `/` or `/PC_hardware_inventory/`) |

**How It Works**:
1. Analyzes `$_SERVER['SCRIPT_NAME']` to find current script path
2. Removes known subdirectories (`pages`, `config`, `includes`, `assets`)
3. Returns path to application root

**Usage in PHP**:
```php
$url = BASE_PATH . 'pages/hardware.php';
```

**Usage in JavaScript** (set in header.php):
```javascript
window.BASE_PATH // Available globally
```

---

## Authentication Files

### 1. `login.php`

**Purpose**: Handles user authentication and login form.

#### Flow
1. Check if user already logged in → redirect to dashboard
2. Display login form
3. On POST: validate credentials
4. If valid: set session, redirect to dashboard
5. If invalid: show error message

#### Key Operations

**Login Validation**:
```php
// Uses prepared statement to prevent SQL injection
$stmt = $conn->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
```

**Password Verification**:
```php
if (verifyPassword($password, $user['password'])) {
    setUserSession($user);
    header('Location: dashboard.php');
}
```

**Remember Me Feature**:
- Sets cookie `remember_user` for 30 days
- Pre-fills username on return visits

---

### 2. `logout.php`

**Purpose**: Logs out user and redirects to login page.

#### Flow
1. Call `clearSession()` to destroy session
2. Redirect to `login.php`

---

## Core Page Files

### 1. `dashboard.php`

**Purpose**: Main dashboard showing inventory statistics and summaries.

#### Access Control
- Requires login (`requireLogin()`)

#### Data Displayed
| Section | Description |
|---------|-------------|
| Statistics Cards | Total items, quantities, status breakdown |
| Recent Hardware | Last 5 added items |
| Low Stock Alert | Items with unused quantity < 2 |
| Categories Summary | Item count per category |

#### Database Queries

**Total Hardware Count**:
```php
$conn->query("SELECT COUNT(*) as count FROM hardware WHERE deleted_at IS NULL");
```

**Quantity Statistics**:
```php
$conn->query("SELECT SUM(total_quantity) as total, 
              SUM(in_use_quantity) as in_use, 
              SUM(unused_quantity) as available, 
              SUM(damaged_quantity) as damaged, 
              SUM(repair_quantity) as repair 
              FROM hardware WHERE deleted_at IS NULL");
```

---

### 2. `pages/hardware.php`

**Purpose**: Main hardware management page - CRUD operations, filtering, import/export.

#### Access Control
- Requires login (`requireLogin()`)

#### Features
1. **List Hardware** - Paginated table with filters
2. **Add Hardware** - Modal form
3. **Edit Hardware** - Modal form with pre-filled data
4. **Delete Hardware** - Soft delete (sets `deleted_at`)
5. **Restore Hardware** - Admin only
6. **Permanent Delete** - Admin only
7. **Batch Operations** - Multi-select delete/status update
8. **CSV Import** - Bulk import from CSV file
9. **CSV Export** - Export to CSV with filters

#### Key Functions

**Soft Delete**:
```php
$stmt = $conn->prepare("UPDATE hardware SET deleted_at = NOW() WHERE id = ?");
```

**Add Hardware**:
```php
$stmt = $conn->prepare("INSERT INTO hardware (name, category_id, type, brand, model, 
    serial_number, total_quantity, unused_quantity, in_use_quantity, 
    damaged_quantity, repair_quantity, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
```

**Filter System**:
- `filter_category` - Filter by category ID
- `filter_brand` - Filter by brand name
- `filter_model` - Filter by model
- `show_deleted` - Include soft-deleted items
- `deleted_only` - Show only deleted items (trash view)

#### Pagination
- 20 records per page
- Uses `LIMIT` and `OFFSET` with prepared statements

#### History Logging
All operations log to `inventory_history` table with:
- Hardware details (denormalized)
- User who made change
- Action type (Added, Updated, Deleted, Restored)
- Quantity changes
- Before/after status values

---

### 3. `pages/users.php`

**Purpose**: User account management (Admin only).

#### Access Control
- Requires admin (`requireAdmin()`)

#### Features
1. **List Users** - Paginated table
2. **Add User** - Create new user account
3. **Edit User** - Update user details/password
4. **Delete User** - Remove user (cannot delete self)

#### Key Operations

**Add User**:
```php
$hashed_password = hashPassword($password);
$stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
```

**Edit User (with optional password change)**:
```php
if (!empty($password)) {
    // Update including password
} else {
    // Update without changing password
}
```

**Delete Protection**:
```php
if ($id === $_SESSION['user_id']) {
    redirectWithMessage(..., 'You cannot delete your own account.', 'error');
}
```

---

### 4. `pages/history.php`

**Purpose**: Audit trail/activity log of all inventory changes.

#### Access Control
- Requires login (`requireLogin()`)

#### Features
1. **View History** - Chronological list of all changes
2. **Filter by Action** - Added, Updated, Deleted, Restored
3. **Filter by Date Range** - Start and end date
4. **Search** - Client-side table search

#### Data Structure (inventory_history table)
| Column | Description |
|--------|-------------|
| `hardware_id` | Reference to hardware (may be deleted) |
| `hardware_name` | Denormalized hardware name |
| `category_name` | Denormalized category name |
| `serial_number` | Denormalized serial number |
| `user_id` | Reference to user (may be deleted) |
| `user_name` | Denormalized user name |
| `action_type` | Added, Updated, Deleted, Restored |
| `quantity_change` | Net change in total quantity |
| `old_*` | Previous status values |
| `new_*` | New status values |

#### Denormalization Benefits
- History preserved even after hardware/user deletion
- No foreign key constraint errors
- Complete audit information always available

---

### 5. `pages/backup.php`

**Purpose**: Database backup and restore functionality (Admin only).

#### Access Control
- Requires admin (`requireAdmin()`)

#### Features
1. **Create Backup** - Generate SQL backup file
2. **List Backups** - Show available backup files
3. **Download Backup** - Download backup as .sql file
4. **Restore Backup** - Restore database from backup
5. **Delete Backup** - Remove backup file

#### Backup Process
```php
// Tables backed up in order
$tables = ['inventory_history', 'hardware', 'users', 'categories'];

// Includes:
// - Table structure (CREATE TABLE)
// - Table data (INSERT statements)
// - Foreign key handling (SET FOREIGN_KEY_CHECKS)
```

#### Restore Validation
- Verifies backup was created by this system
- Checks for dangerous SQL patterns
- Disables/re-enables foreign key checks

#### Security
- File path validation (basename only)
- Only .sql files allowed
- Directory traversal prevention

---

### 6. `pages/import_csv.php`

**Purpose**: AJAX endpoint for CSV import functionality.

#### Access Control
- Requires login (`requireLogin()`)
- POST method only
- Returns JSON response

#### CSV Format
```csv
name,category,type,brand,model,serial_number,unused_quantity,in_use_quantity,damaged_quantity,repair_quantity,location
```

#### Import Logic
1. Parse CSV file
2. For each row:
   - Look up category by name (or ID)
   - If category doesn't exist, create it
   - Check for duplicate hardware
   - If duplicate: add quantities to existing
   - If new: insert new record
3. Log all changes to history
4. Return summary JSON

#### Duplicate Detection
Matches on:
- `name`
- `serial_number`
- `brand`
- `category_id`

---

## Include Files

### 1. `includes/header.php`

**Purpose**: Common HTML header, navigation bar, and page structure start.

#### Components
1. **HTML Head** - Meta tags, CSS links, title
2. **Top Navigation Bar** - Logo, menu items, user dropdown
3. **Flash Message Display** - Success/error/warning alerts
4. **Main Content Container Start**

#### Navigation Items
| Item | URL | Access |
|------|-----|--------|
| Dashboard | `/dashboard.php` | All users |
| Hardware | `/pages/hardware.php` | All users |
| Audit Trail | `/pages/history.php` | All users |
| Users | `/pages/users.php` | Admin only |
| Backup | `/pages/backup.php` | Admin only |

#### Active Page Highlighting
```php
$current_page = basename($_SERVER['PHP_SELF']);
// Class 'active' added to current page's nav-link
```

---

### 2. `includes/footer.php`

**Purpose**: Common HTML footer and closing tags.

#### Components
1. **Footer Content** - Version, copyright, institution
2. **JavaScript Includes** - Bootstrap, main.js, ui-enhancements.js
3. **Form Double-Submit Prevention**
4. **Closing HTML Tags**

---

## JavaScript Functions

### File: `assets/js/main.js`

#### Loading Functions

##### `showLoading(message, subtext)`
**Purpose**: Shows loading overlay during operations.
```javascript
showLoading('Processing...');
showLoading('Deleting...', 'Please wait');
```

##### `hideLoading()`
**Purpose**: Hides loading overlay.
```javascript
hideLoading();
```

##### `updateLoadingProgress(percent)`
**Purpose**: Updates progress bar in loading overlay (0-100).
```javascript
updateLoadingProgress(50); // 50% complete
```

---

#### Confirmation Functions

##### `showConfirmation(message, title, buttonText, type)`
**Purpose**: Shows custom confirmation modal.
```javascript
showConfirmation(
    'Are you sure?',
    'Confirm Delete',
    'Delete',
    'danger'
).then(confirmed => {
    if (confirmed) {
        // User clicked confirm
    }
});
```

**Types**: `'danger'`, `'warning'`, `'info'`, `'primary'`

##### `confirmDelete(message, element)`
**Purpose**: Handles delete link confirmation.
```javascript
// In onclick handler
onclick="return confirmDelete('Delete this item?', this)"
```

---

#### Alert Functions

##### `showAlert(message, title, type)`
**Purpose**: Shows custom alert modal.
```javascript
showAlert('Operation completed', 'Success', 'success');
```

**Types**: `'success'`, `'error'`, `'warning'`, `'info'`

---

#### Table Functions

##### `searchTable(inputId, tableId)`
**Purpose**: Client-side table search/filter.
```javascript
// In input onkeyup
onkeyup="searchTable('searchInput', 'hardwareTable')"
```

##### `exportTableToCSV(tableId, filename)`
**Purpose**: Exports table data to CSV file.
```javascript
exportTableToCSV('hardwareTable', 'hardware_export.csv');
```

---

#### Form Functions

##### `calculateTotal()`
**Purpose**: Calculates total quantity from component quantities.
- Automatically attached to quantity input fields
- Updates `total_quantity` display

##### `validateForm(formId)`
**Purpose**: Validates form before submission.
```javascript
if (validateForm('hardwareForm')) {
    // Form is valid
}
```

---

#### Toast Notifications

##### `showToast(message, type, duration)`
**Purpose**: Shows non-intrusive toast notification.
```javascript
showToast('Item saved', 'success', 4000);
```

**Types**: `'success'`, `'error'`, `'warning'`, `'info'`

---

### File: `assets/js/ui-enhancements.js`

#### HCI Module

##### `HCI.Toast.show(message, type, duration)`
**Purpose**: Enhanced toast notification system.
```javascript
HCI.Toast.success('Operation completed');
HCI.Toast.error('Something went wrong');
```

##### `HCI.announce(message, priority)`
**Purpose**: Screen reader announcement.
```javascript
HCI.announce('5 results found', 'polite');
```

---

#### Keyboard Navigation

| Key | Action |
|-----|--------|
| `/` | Focus search input |
| `Escape` | Close search panel/modal |
| `Alt+A` | Open Add modal |
| `Arrow Up/Down` | Navigate table rows |
| `Enter` | Edit selected row |
| `Delete` | Delete selected row |

---

## Database Functions

### Table Structure

#### `hardware` Table
| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `name` | VARCHAR | Hardware name |
| `category_id` | INT | Foreign key to categories |
| `type` | VARCHAR | Hardware type |
| `brand` | VARCHAR | Brand name |
| `model` | VARCHAR | Model number |
| `serial_number` | VARCHAR | Serial number |
| `total_quantity` | INT | Total items (calculated) |
| `unused_quantity` | INT | Available items |
| `in_use_quantity` | INT | Items in use |
| `damaged_quantity` | INT | Damaged items |
| `repair_quantity` | INT | Items in repair |
| `location` | VARCHAR | Physical location |
| `date_added` | DATETIME | Creation timestamp |
| `deleted_at` | DATETIME | Soft delete timestamp |

#### `categories` Table
| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `name` | VARCHAR | Category name |
| `description` | VARCHAR | Description |

#### `users` Table
| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `username` | VARCHAR | Login username |
| `password` | VARCHAR | Hashed password |
| `full_name` | VARCHAR | Display name |
| `role` | ENUM | 'admin' or 'staff' |
| `date_created` | DATETIME | Creation timestamp |

#### `inventory_history` Table
| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `hardware_id` | INT | Reference to hardware |
| `hardware_name` | VARCHAR | Denormalized name |
| `category_name` | VARCHAR | Denormalized category |
| `serial_number` | VARCHAR | Denormalized serial |
| `user_id` | INT | Reference to user |
| `user_name` | VARCHAR | Denormalized user |
| `action_type` | ENUM | Added/Updated/Deleted/Restored |
| `quantity_change` | INT | Net quantity change |
| `old_*` | INT | Previous status values |
| `new_*` | INT | New status values |
| `action_date` | DATETIME | When action occurred |

---

## Common Use Cases

### Adding New Hardware
1. Navigate to Hardware page
2. Click "Add Hardware" button
3. Fill in required fields (Name, Category)
4. Enter quantities for each status
5. Select location
6. Click "Add Hardware"
7. System logs action to history

### Updating Hardware Quantities
1. Find item in Hardware table
2. Click "Edit" button
3. Modify quantity fields
4. Click "Update Hardware"
5. System logs changes with before/after values

### Bulk Import from CSV
1. Navigate to Hardware page
2. Click "Import CSV"
3. Select CSV file
4. Optionally select default location
5. Review preview
6. Click "Import"
7. System creates/updates records and logs all changes

### Viewing Activity History
1. Navigate to Audit Trail
2. Use filters to narrow results:
   - Action type (Added, Updated, etc.)
   - Date range
3. Use search for specific items
4. Review before/after values

### Creating Backup (Admin)
1. Navigate to Backup page
2. Click "Create Backup Now"
3. Backup file created with timestamp
4. Download or keep for later restore

### Restoring from Backup (Admin)
1. Navigate to Backup page
2. Find desired backup
3. Click "Restore"
4. Confirm the action
5. Database restored to backup state

---

## Defense Preparation Q&A

### Architecture Questions

**Q: What design pattern does the system follow?**
A: The system follows an MVC-inspired pattern with:
- Configuration files serving as the "Model" layer
- PHP page files combining Controller and View logic
- Include files providing reusable templates

**Q: How is security implemented?**
A: Multiple layers:
1. **Authentication**: Session-based with secure cookies
2. **Authorization**: Role-based (admin/staff)
3. **Input Validation**: `sanitizeInput()`, `sanitizeForDB()`
4. **Output Escaping**: `escapeOutput()` for XSS prevention
5. **SQL Injection**: Prepared statements throughout
6. **Password Storage**: PHP's `password_hash()` with bcrypt

**Q: How does the session management work?**
A: 
1. HTTP-only cookies prevent JavaScript access
2. SameSite=Strict prevents CSRF
3. Sessions regenerated every 30 minutes
4. `clearSession()` properly destroys all session data

### Database Questions

**Q: Why are history records denormalized?**
A: To preserve complete audit trail even when:
- Hardware items are deleted
- Users are removed from system
- Categories are modified
This prevents foreign key errors and ensures historical accuracy.

**Q: How does soft delete work?**
A: Instead of `DELETE`, items have `deleted_at` timestamp set:
```php
UPDATE hardware SET deleted_at = NOW() WHERE id = ?
```
This allows:
- Recovery of deleted items
- Historical data preservation
- No foreign key violations

**Q: How is pagination implemented?**
A: Using MySQL LIMIT and OFFSET:
```php
$offset = ($page - 1) * $records_per_page;
$query .= " LIMIT ? OFFSET ?";
```

### Functionality Questions

**Q: How does CSV import handle duplicates?**
A: Duplicates are detected by matching name, serial_number, brand, and category_id. If found:
- Quantities are added to existing record
- History shows "Updated" action
- User is notified of merged records

**Q: How does the batch operation system work?**
A: Users can:
1. Select multiple items via checkboxes
2. Choose action (delete or status update)
3. Confirm action
4. System processes each item and logs to history

**Q: What HCI principles are applied?**
A:
1. **Feedback**: Loading overlays, toast notifications, confirmation dialogs
2. **Visibility**: Status badges, icons, progress indicators
3. **Error Prevention**: Confirmation before destructive actions
4. **Flexibility**: Keyboard shortcuts, multiple navigation methods
5. **Accessibility**: ARIA labels, focus management, screen reader support

### Technical Questions

**Q: How is BASE_PATH determined dynamically?**
A: `config/base.php` analyzes `$_SERVER['SCRIPT_NAME']`:
1. Gets directory of current script
2. Removes known subdirectories
3. Returns path to application root
This allows the app to work in any subdirectory.

**Q: How are flash messages implemented?**
A:
1. `redirectWithMessage()` stores message in session
2. Header.php calls `getFlashMessage()`
3. Message is displayed and cleared from session
4. Auto-dismisses after 5 seconds

**Q: How does the backup system validate files?**
A: Multiple checks:
1. File must start with `-- PC Hardware Inventory Backup`
2. Checks for dangerous SQL patterns
3. Only allows .sql extension
4. Uses `basename()` to prevent directory traversal

---

## Quick Reference Card

### Key Files
| File | Purpose |
|------|---------|
| `login.php` | User authentication |
| `dashboard.php` | Statistics overview |
| `pages/hardware.php` | Hardware CRUD |
| `pages/users.php` | User management (Admin) |
| `pages/history.php` | Audit trail |
| `pages/backup.php` | Backup/restore (Admin) |

### Key Functions
| Function | Purpose |
|----------|---------|
| `getDBConnection()` | Get database connection |
| `requireLogin()` | Protect page - require auth |
| `requireAdmin()` | Protect page - require admin |
| `sanitizeInput()` | Clean user input |
| `escapeOutput()` | Safe HTML output |
| `redirectWithMessage()` | Redirect with flash message |

### Key JavaScript
| Function | Purpose |
|----------|---------|
| `showLoading()` | Show loading overlay |
| `showConfirmation()` | Confirmation dialog |
| `confirmDelete()` | Delete confirmation |
| `searchTable()` | Client-side search |

### Default Credentials
- **Admin**: `admin` / `password123`
- **Staff**: `staff01` / `password123`

---

*Document Version: 2.0*
*Last Updated: November 2025*
*For: ACLC College of Ormoc - PC Hardware Inventory System*
