# PC Hardware Inventory System - Complete Documentation Script

## System Overview

The PC Hardware Inventory System is a web-based application for ACLC College of Ormoc to manage and track PC hardware components. This document provides a comprehensive guide demonstrating each page of the system and explaining how all functions work.

**Technology Stack:**
- Backend: PHP 7.4+
- Database: MySQL/MariaDB
- Frontend: Bootstrap 5, JavaScript
- Authentication: Session-based with password hashing

**Default Credentials:**
- Admin: `admin` / `password123`
- Staff: `staff01` / `password123`

---

# Page 1: Login Page (`login.php`)

## Purpose
The entry point for user authentication to access the system.

## Functions Demonstrated

### Function 1: User Authentication
```php
// How it works:
// 1. Sanitize user input to prevent XSS
$username = sanitizeInput($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// 2. Query database using prepared statements (SQL injection prevention)
$stmt = $conn->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();

// 3. Verify password using PHP's secure password_verify()
if (verifyPassword($password, $user['password'])) {
    // 4. Update login tracking in database
    $update_login_stmt = $conn->prepare("UPDATE users SET last_login = NOW(), session_start = NOW(), is_active = 1, last_activity = NOW() WHERE id = ?");
    
    // 5. Create secure session
    setUserSession($user);
    
    // 6. Redirect to dashboard
    header('Location: dashboard.php');
}
```

### Function 2: Remember Me Feature
```php
// Stores username in cookie for 30 days
if ($remember) {
    setcookie('remember_user', $username, time() + (86400 * 30), '/');
}

// Pre-populates username field on next visit
$rememberedUser = $_COOKIE['remember_user'] ?? '';
```

### Function 3: Session Redirect (Already Logged In)
```php
// Checks if user already has active session
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}
```

### Function 4: Password Visibility Toggle
```javascript
// Toggle between password and text input types
document.getElementById('togglePassword').addEventListener('click', function() {
    const password = document.getElementById('password');
    if (password.type === 'password') {
        password.type = 'text';
        // Change icon to eye-slash
    } else {
        password.type = 'password';
        // Change icon to eye
    }
});
```

### Function 5: Form Validation with Loading Overlay
```javascript
// Bootstrap validation + custom loading overlay
form.addEventListener('submit', function(event) {
    form.classList.add('was-validated');
    if (!form.checkValidity()) {
        event.preventDefault();
        return;
    }
    showLoginLoading(); // Show spinner during authentication
});
```

## Security Features
- SQL Injection Prevention: Prepared statements
- XSS Prevention: sanitizeInput() function
- Password Security: bcrypt hashing
- CSRF Protection: Session tokens

---

# Page 2: Dashboard (`dashboard.php`)

## Purpose
Main landing page showing inventory overview with real-time statistics.

## Functions Demonstrated

### Function 1: Statistics Calculation
```php
// Total hardware items (excluding soft-deleted)
$result = $conn->query("SELECT COUNT(*) as count FROM hardware WHERE deleted_at IS NULL");
$stats['total_hardware'] = $row['count'];

// Quantity breakdown by status
$result = $conn->query("SELECT 
    SUM(total_quantity) as total, 
    SUM(in_use_quantity) as in_use, 
    SUM(unused_quantity) as available, 
    SUM(damaged_quantity) as damaged, 
    SUM(repair_quantity) as repair 
    FROM hardware WHERE deleted_at IS NULL");
```

### Function 2: Welcome Banner with Personalization
```php
// Displays user's name and current date
<h2>Welcome back, <?php echo escapeOutput($_SESSION['full_name']); ?>!</h2>
<p><?php echo date('l, F j, Y'); ?></p> <!-- e.g., "Monday, December 1, 2025" -->
```

### Function 3: Recent Hardware Query
```php
// Get 5 most recently added items with category join
$result = $conn->query("SELECT h.*, c.name as category_name FROM hardware h 
                       LEFT JOIN categories c ON h.category_id = c.id 
                       WHERE h.deleted_at IS NULL
                       ORDER BY h.date_added DESC LIMIT 5");
```

### Function 4: Low Stock Alert System
```php
// Items with unused_quantity < 2 (low stock threshold)
$result = $conn->query("SELECT h.*, c.name as category_name FROM hardware h 
                       LEFT JOIN categories c ON h.category_id = c.id 
                       WHERE h.unused_quantity < 2 AND h.total_quantity > 0 AND h.deleted_at IS NULL
                       ORDER BY h.unused_quantity ASC LIMIT 5");
```

### Function 5: Out of Stock Detection
```php
// Items with zero available quantity
$result = $conn->query("SELECT h.*, c.name as category_name FROM hardware h 
                       LEFT JOIN categories c ON h.category_id = c.id 
                       WHERE h.unused_quantity = 0 AND h.deleted_at IS NULL
                       ORDER BY h.name ASC LIMIT 10");
```

### Function 6: Categories Summary Aggregation
```php
// Group hardware by category with counts and totals
$result = $conn->query("SELECT c.name, COUNT(h.id) as count, SUM(h.total_quantity) as total 
                       FROM categories c 
                       LEFT JOIN hardware h ON c.id = h.category_id AND h.deleted_at IS NULL
                       GROUP BY c.id, c.name 
                       ORDER BY count DESC");
```

## Access Control
```php
requireLogin(); // Both Admin and Staff can view
```

---

# Page 3: Hardware Management (`pages/hardware.php`)

## Purpose
Core CRUD functionality for managing hardware inventory items.

## Functions Demonstrated

### Function 1: Add New Hardware
```php
// Insert new hardware item
$stmt = $conn->prepare("INSERT INTO hardware 
    (name, category_id, type, brand, model, serial_number, 
     total_quantity, unused_quantity, in_use_quantity, damaged_quantity, repair_quantity, location) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sissssiiiiis", $name, $category_id, $type, $brand, $model, $serial_number, 
                 $total_quantity, $unused_quantity, $in_use_quantity, $damaged_quantity, $repair_quantity, $location);

// Log to history with denormalized data
$log_stmt = $conn->prepare("INSERT INTO inventory_history 
    (hardware_id, hardware_name, category_name, serial_number, 
     user_id, user_name, action_type, quantity_change, 
     old_unused, old_in_use, old_damaged, old_repair, 
     new_unused, new_in_use, new_damaged, new_repair) 
    VALUES (?, ?, ?, ?, ?, ?, 'Added', ?, 0, 0, 0, 0, ?, ?, ?, ?)");
```

### Function 2: Edit Hardware with Change Tracking
```php
// Get old values before update
$old_stmt = $conn->prepare("SELECT unused_quantity, in_use_quantity, damaged_quantity, repair_quantity FROM hardware WHERE id = ?");

// Update hardware
$stmt = $conn->prepare("UPDATE hardware SET name=?, category_id=?, type=?, brand=?, model=?, serial_number=?, 
                       total_quantity=?, unused_quantity=?, in_use_quantity=?, damaged_quantity=?, 
                       repair_quantity=?, location=? WHERE id=?");

// Log change with old AND new values for audit trail
$quantity_change = $total_quantity - ($old_data['unused_quantity'] + $old_data['in_use_quantity'] + 
                                       $old_data['damaged_quantity'] + $old_data['repair_quantity']);
```

### Function 3: Soft Delete (Non-Destructive)
```php
// Instead of DELETE, set deleted_at timestamp
$stmt = $conn->prepare("UPDATE hardware SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL");

// Item is hidden from normal views but data preserved
// WHERE deleted_at IS NULL in all queries
```

### Function 4: Restore from Trash
```php
// Set deleted_at back to NULL
$stmt = $conn->prepare("UPDATE hardware SET deleted_at = NULL WHERE id = ?");

// Log restoration to history
$log_stmt->bind_param("...", 'Restored', ...);
```

### Function 5: Permanent Delete (Admin Only)
```php
// Check admin permission
if (isAdmin()) {
    // Physical deletion from database
    $stmt = $conn->prepare("DELETE FROM hardware WHERE id = ?");
}
```

### Function 6: CSV Import
```php
// Handler: pages/import_csv.php
// Parse CSV file
if (($handle = fopen($file, 'r')) !== false) {
    $header = fgetcsv($handle); // Skip header row
    
    while (($data = fgetcsv($handle)) !== false) {
        $name = sanitizeForDB($conn, trim($data[0]));
        
        // Smart category handling - accepts name OR ID
        $category_value = trim($data[1]);
        if (is_numeric($category_value)) {
            $category_id = (int)$category_value;
        } else {
            // Look up by name, create if not exists
            if (!isset($category_map[$category_key])) {
                // Auto-create category
                $insert_cat_stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, 'Auto-created from CSV import')");
            }
        }
        
        // Duplicate detection - add quantities instead of creating duplicates
        $check_stmt = $conn->prepare("SELECT id, unused_quantity... FROM hardware 
                                      WHERE name = ? AND serial_number = ? AND brand = ? AND category_id = ?");
        if ($existing) {
            // Add to existing quantities
            $new_unused = $existing['unused_quantity'] + $unused_quantity;
            // UPDATE instead of INSERT
        } else {
            // INSERT new record
        }
    }
}
```

### Function 7: CSV Export
```javascript
// Client-side CSV generation
function exportFilteredCSV() {
    const headers = ['name', 'category', 'type', 'brand', 'model', 'serial_number', 
                     'unused_quantity', 'in_use_quantity', 'damaged_quantity', 'repair_quantity', 'location'];
    
    let csv = [headers.join(',')];
    
    hardwareData.forEach(function(item) {
        let row = [
            '"' + (item.name || '').replace(/"/g, '""') + '"',
            // ... more fields
        ];
        csv.push(row.join(','));
    });
    
    // Trigger download
    const blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = window.URL.createObjectURL(blob);
    a.download = 'hardware_inventory.csv';
    a.click();
}
```

### Function 8: Batch Operations
```javascript
// Select multiple items
function toggleSelectAll(checkbox) {
    document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = checkbox.checked);
    updateBatchToolbar();
}

// Batch delete
function batchDelete() {
    var ids = getSelectedIds();
    showConfirmation('Delete ' + ids.length + ' items?', ...).then(function(confirmed) {
        if (confirmed) {
            // Submit form with all selected IDs
            ids.forEach(id => form.appendChild(createHiddenInput('ids[]', id)));
            form.submit();
        }
    });
}

// Batch status update
function confirmBatchStatusUpdate() {
    // Update specific quantity field (unused, in_use, damaged, repair) for all selected
}
```

### Function 9: Advanced Filtering
```php
// Build dynamic query with filters
$query = "SELECT h.*, c.name as category_name FROM hardware h 
          LEFT JOIN categories c ON h.category_id = c.id WHERE 1=1";

if ($filter_category > 0) {
    $query .= " AND h.category_id = ?";
    $params[] = $filter_category;
}
if (!empty($filter_brand)) {
    $query .= " AND h.brand LIKE ?";
    $params[] = "%" . $filter_brand . "%";
}
// ... more filters
```

### Function 10: Client-Side Search
```javascript
function searchTable(inputId, tableId) {
    var input = document.getElementById(inputId);
    var filter = input.value.toUpperCase();
    var table = document.getElementById(tableId);
    var rows = table.getElementsByTagName('tr');
    
    for (var i = 1; i < rows.length; i++) {
        var cells = rows[i].getElementsByTagName('td');
        var match = false;
        for (var j = 0; j < cells.length; j++) {
            if (cells[j].textContent.toUpperCase().indexOf(filter) > -1) {
                match = true;
                break;
            }
        }
        rows[i].style.display = match ? '' : 'none';
    }
}
```

### Function 11: Inline Category Creation (AJAX)
```php
// AJAX handler for adding category without page reload
if ($_POST['action'] === 'add_category') {
    header('Content-Type: application/json');
    
    $category_name = sanitizeForDB($conn, trim($_POST['name']));
    
    // Check for duplicates
    $check_stmt = $conn->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(?)");
    
    // Insert new category
    $insert_stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    
    echo json_encode(['success' => true, 'id' => $new_id, 'name' => $category_name]);
    exit;
}
```

```javascript
// Frontend AJAX call
fetch(window.location.href, {
    method: 'POST',
    body: formData  // action=add_category, name=..., description=...
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        // Add new option to all category dropdowns
        document.querySelectorAll('.category-select').forEach(select => {
            var newOption = document.createElement('option');
            newOption.value = data.id;
            newOption.textContent = data.name;
            select.insertBefore(newOption, select.querySelector('[value="__add_new__"]'));
        });
        // Select the new category
        activeCategoryDropdown.value = data.id;
    }
});
```

### Function 12: Pagination
```php
$records_per_page = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

$query .= " ORDER BY h.date_added DESC LIMIT ? OFFSET ?";
$stmt->bind_param($types . "ii", ...$params, $records_per_page, $offset);
```

---

# Page 4: History/Audit Trail (`pages/history.php`)

## Purpose
Complete audit log of all inventory changes for accountability and compliance.

## Functions Demonstrated

### Function 1: Denormalized History Query
```php
// Uses COALESCE to show stored values if related records deleted
$query = "SELECT ih.*, 
    COALESCE(h.name, ih.hardware_name) as hardware_name, 
    COALESCE(h.serial_number, ih.serial_number) as serial_number, 
    COALESCE(u.full_name, ih.user_name) as user_name, 
    COALESCE(c.name, ih.category_name) as category_name
FROM inventory_history ih
LEFT JOIN hardware h ON ih.hardware_id = h.id
LEFT JOIN users u ON ih.user_id = u.id
LEFT JOIN categories c ON h.category_id = c.id
WHERE 1=1";
```

### Function 2: Action Type Filter
```php
// Filter by specific action types
$valid_actions = ['Added', 'Updated', 'Deleted', 'Restored'];
if (!empty($action_filter) && in_array($action_filter, $valid_actions)) {
    $query .= " AND ih.action_type = ?";
    $params[] = $action_filter;
}
```

### Function 3: Date Range Filter
```php
// Filter by date range
if (!empty($date_from)) {
    $query .= " AND DATE(ih.action_date) >= ?";
    $params[] = $date_from;
}
if (!empty($date_to)) {
    $query .= " AND DATE(ih.action_date) <= ?";
    $params[] = $date_to;
}
```

### Function 4: Quantity Change Visualization
```php
// Display what changed between states
$changes = [];
if ($item['old_unused'] != $item['new_unused']) {
    $changes[] = [
        'label' => 'Available', 
        'icon' => 'bi-check-circle', 
        'class' => 'text-success', 
        'old' => $item['old_unused'], 
        'new' => $item['new_unused']
    ];
}
// ... check in_use, damaged, repair

// Display with arrows showing direction
$diff = $change['new'] - $change['old'];
$arrow = $diff > 0 ? '↑' : '↓';
echo $change['label'] . ': ' . $change['new'] . ' (' . $arrow . abs($diff) . ')';
```

### Function 5: Action Type Badge Styling
```php
$badge_class = 'bg-info';
if ($item['action_type'] === 'Added') $badge_class = 'bg-success';      // Green
if ($item['action_type'] === 'Updated') $badge_class = 'bg-warning';    // Yellow
if ($item['action_type'] === 'Deleted') $badge_class = 'bg-danger';     // Red
if ($item['action_type'] === 'Restored') $badge_class = 'bg-primary';   // Blue
```

---

# Page 5: User Management (`pages/users.php`)

## Purpose
Manage system user accounts (Admin only).

## Functions Demonstrated

### Function 1: Add New User
```php
// Check username uniqueness
$check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");

// Hash password securely
$hashed_password = hashPassword($password); // Uses password_hash() with bcrypt

// Insert user
$stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $username, $hashed_password, $full_name, $role);
```

### Function 2: Edit User (Conditional Password Update)
```php
// If password provided, update it; otherwise keep current
if (!empty($password)) {
    $hashed_password = hashPassword($password);
    $stmt = $conn->prepare("UPDATE users SET username=?, password=?, full_name=?, role=? WHERE id=?");
    $stmt->bind_param("ssssi", $username, $hashed_password, $full_name, $role, $id);
} else {
    $stmt = $conn->prepare("UPDATE users SET username=?, full_name=?, role=? WHERE id=?");
    $stmt->bind_param("sssi", $username, $full_name, $role, $id);
}
```

### Function 3: Self-Delete Prevention
```php
// Cannot delete your own account
if ($id === $_SESSION['user_id']) {
    redirectWithMessage('You cannot delete your own account.', 'error');
}
```

### Function 4: Live Online Status Calculation
```php
// Determine if user is online based on is_active flag and timeout
$is_user_active = !empty($user['is_active']) && $user['is_active'] == 1;

// Also verify last_activity is within timeout period
if ($is_user_active && !empty($user['last_activity'])) {
    $last_activity_dt = new DateTime($user['last_activity'], $ph_timezone);
    $current_dt = new DateTime('now', $ph_timezone);
    $timeout_seconds = SESSION_TIMEOUT_MINUTES * 60;
    
    if ($current_dt->getTimestamp() - $last_activity_dt->getTimestamp() > $timeout_seconds) {
        $is_user_active = false; // Timed out
    }
}
```

### Function 5: Live Session Duration Counter
```javascript
// Server-client time synchronization
var serverTimeAtLoad = <?php echo $server_timestamp_ms; ?>;
var clientTimeAtLoad = Date.now();
var serverClientTimeOffset = serverTimeAtLoad - clientTimeAtLoad;

function formatDuration(seconds) {
    if (seconds < 60) return seconds + ' sec';
    else if (seconds < 3600) {
        var minutes = Math.floor(seconds / 60);
        var secs = seconds % 60;
        return minutes + ' min' + (secs > 0 ? ' ' + secs + ' sec' : '');
    } else {
        var hours = Math.floor(seconds / 3600);
        var minutes = Math.floor((seconds % 3600) / 60);
        return hours + ' hr' + (minutes > 0 ? ' ' + minutes + ' min' : '');
    }
}

function updateLiveSessionDurations() {
    var currentTimeSec = Math.floor((Date.now() + serverClientTimeOffset) / 1000);
    
    document.querySelectorAll('.live-session-badge').forEach(badge => {
        var sessionStartEpoch = parseInt(badge.getAttribute('data-session-start'));
        var duration = Math.max(0, currentTimeSec - sessionStartEpoch);
        badge.querySelector('.live-duration').textContent = formatDuration(duration);
    });
}

// Update every second
setInterval(updateLiveSessionDurations, 1000);
```

### Function 6: Auto-Refresh Table (AJAX Polling)
```javascript
// Fetch fresh data every 5 seconds
function refreshUsersTable() {
    fetch('api_users.php?page=' + currentPage)
        .then(response => response.json())
        .then(data => {
            // Regenerate table rows with new data
            var tbody = document.querySelector('#usersTable tbody');
            var newContent = '';
            
            data.users.forEach(user => {
                newContent += generateUserRow(user);
            });
            
            tbody.innerHTML = newContent;
            updateLiveSessionDurations();
        });
}

setInterval(refreshUsersTable, 5000);
```

## Access Control
```php
requireAdmin(); // Only admins can access this page
```

---

# Page 6: Profile Settings (`pages/profile.php`)

## Purpose
Self-service profile and password management for users.

## Functions Demonstrated

### Function 1: Load Current User Data
```php
$stmt = $conn->prepare("SELECT id, username, full_name, role FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $result->fetch_assoc();

// Invalid session handling
if (!$user) {
    redirectWithMessage('logout.php', 'User session invalid. Please log in again.', 'error');
}
```

### Function 2: Update Profile Information
```php
// Check username uniqueness (excluding self)
$check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
$check_stmt->bind_param("si", $username, $_SESSION['user_id']);

// Update user profile
$update_stmt = $conn->prepare("UPDATE users SET username = ?, full_name = ? WHERE id = ?");
$update_stmt->bind_param("ssi", $username, $full_name, $_SESSION['user_id']);

// Update session immediately
if ($update_stmt->execute()) {
    $_SESSION['username'] = $username;
    $_SESSION['full_name'] = $full_name;
}
```

### Function 3: Change Password with Verification
```php
// Verify current password first
$pwd_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$pwd_stmt->bind_param("i", $_SESSION['user_id']);

if (!verifyPassword($current_password, $pwd_row['password'])) {
    $error = 'Current password is incorrect.';
} else {
    // Validate new password
    if (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New password and confirmation do not match.';
    } else {
        // Update password
        $hashed_password = hashPassword($new_password);
        $update_pwd_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update_pwd_stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
    }
}
```

### Function 4: Password Visibility Toggle
```javascript
function togglePassword(fieldId) {
    var field = document.getElementById(fieldId);
    var icon = document.getElementById(fieldId + '_icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
```

### Function 5: Password Change Confirmation Dialog
```javascript
function confirmPasswordChange() {
    // Client-side validation
    if (!currentPassword || !newPassword || !confirmPassword) {
        showAlert('Please fill in all password fields.', 'Validation Error', 'error');
        return;
    }
    if (newPassword.length < 6) {
        showAlert('New password must be at least 6 characters.', 'Validation Error', 'error');
        return;
    }
    if (newPassword !== confirmPassword) {
        showAlert('Passwords do not match.', 'Validation Error', 'error');
        return;
    }
    
    // Show confirmation
    showConfirmation(
        'Are you sure you want to change your password? You will need to use your new password the next time you log in.',
        'Confirm Password Change',
        'Change Password',
        'warning'
    ).then(function(confirmed) {
        if (confirmed) {
            showLoading('Changing password...');
            form.submit();
        }
    });
}
```

---

# Page 7: Backup & Restore (`pages/backup.php`)

## Purpose
Database backup and restore functionality for disaster recovery (Admin only).

## Functions Demonstrated

### Function 1: Create Database Backup
```php
$timestamp = date('Y-m-d_H-i-s');
$backup_filename = "backup_{$timestamp}.sql";

// Build SQL backup content
$backup_content = "-- PC Hardware Inventory Backup\n";
$backup_content .= "-- Created: " . date('Y-m-d H:i:s') . "\n";
$backup_content .= "-- Created by: " . $_SESSION['full_name'] . "\n\n";

// Disable foreign key checks for safe restore
$backup_content .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

// Export tables in dependency order
$tables = ['inventory_history', 'hardware', 'users', 'categories'];

foreach ($tables as $table) {
    // Export table structure
    $result = $conn->query("SHOW CREATE TABLE $table");
    $row = $result->fetch_assoc();
    $backup_content .= "DROP TABLE IF EXISTS `$table`;\n";
    $backup_content .= $row['Create Table'] . ";\n\n";
    
    // Export table data
    $result = $conn->query("SELECT * FROM $table");
    while ($row = $result->fetch_assoc()) {
        $values = array_map(function($val) use ($conn) {
            return $val === null ? 'NULL' : "'" . $conn->real_escape_string($val) . "'";
        }, $row);
        $backup_content .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
    }
}

$backup_content .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";

// Save to file
file_put_contents($backup_path, $backup_content);
```

### Function 2: Download Backup
```php
// Security: Prevent directory traversal
$filename = basename($_GET['download']);
$filepath = $backups_dir . '/' . $filename;

// Validate file type
if (file_exists($filepath) && pathinfo($filepath, PATHINFO_EXTENSION) === 'sql') {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    exit;
}
```

### Function 3: Restore from Backup
```php
// Validate backup file format
if (strpos($sql, '-- PC Hardware Inventory Backup') !== 0) {
    redirectWithMessage('Invalid backup file format.', 'error');
}

// Check for dangerous SQL patterns
$dangerous_patterns = [
    'drop database', 'create database', 'grant ', 'revoke ', 
    'create user', 'alter user', 'drop user', 'load_file', 
    'into outfile', 'into dumpfile', 'information_schema',
    'mysql.user', 'sleep(', 'benchmark('
];

foreach ($dangerous_patterns as $pattern) {
    if (stripos($sql, $pattern) !== false) {
        redirectWithMessage('Backup file contains dangerous SQL.', 'error');
    }
}

// Execute restore
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->multi_query($sql);

// Process all results
do {
    if ($result = $conn->store_result()) {
        $result->free();
    }
} while ($conn->more_results() && $conn->next_result());

// Re-enable foreign keys (fresh connection needed after multi_query)
$conn = getDBConnection();
$conn->query("SET FOREIGN_KEY_CHECKS = 1");
```

### Function 4: Delete Backup
```php
$filename = basename($_GET['delete']); // Prevent directory traversal
$filepath = $backups_dir . '/' . $filename;

if (file_exists($filepath) && pathinfo($filepath, PATHINFO_EXTENSION) === 'sql') {
    unlink($filepath);
    redirectWithMessage('Backup deleted successfully.', 'success');
}
```

### Function 5: List Available Backups
```php
$backups = [];
if (is_dir($backups_dir)) {
    $files = scandir($backups_dir, SCANDIR_SORT_DESCENDING);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $filepath = $backups_dir . '/' . $file;
            $backups[] = [
                'filename' => $file,
                'size' => filesize($filepath),
                'created' => filemtime($filepath)
            ];
        }
    }
}
```

### Function 6: Restore Confirmation Modal
```javascript
function confirmRestore(filename) {
    document.getElementById('restore_filename').value = filename;
    document.getElementById('restore_filename_display').textContent = filename;
    var modal = new bootstrap.Modal(document.getElementById('restoreModal'));
    modal.show();
}
```

## Access Control
```php
requireAdmin(); // Only admins can backup/restore
```

---

# Page 8: Logout (`logout.php`)

## Purpose
Securely end user session and track login duration.

## Functions Demonstrated

### Function 1: Calculate Session Duration
```php
if (isLoggedIn() && isset($_SESSION['login_time'])) {
    $user_id = $_SESSION['user_id'];
    $login_time = $_SESSION['login_time'];
    $duration = time() - $login_time; // Duration in seconds
    
    // Save duration and set user as offline
    $stmt = $conn->prepare("UPDATE users SET last_login_duration = ?, is_active = 0 WHERE id = ?");
    $stmt->bind_param("ii", $duration, $user_id);
    $stmt->execute();
}
```

### Function 2: Clear Session
```php
clearSession(); // Destroys all session data
header('Location: login.php');
exit();
```

---

# Shared Components

## Header (`includes/header.php`)
- Navigation menu
- User dropdown with profile/logout
- Session activity tracking
- Loading overlay component

## Footer (`includes/footer.php`)
- Bootstrap JavaScript
- Custom JavaScript functions
- Toast notifications
- Confirmation dialogs

## Security Functions (`config/security.php`)
```php
function sanitizeInput($data) { ... }      // XSS prevention
function sanitizeForDB($conn, $data) { ... } // SQL escaping
function escapeOutput($data) { ... }        // Output encoding
function hashPassword($password) { ... }    // bcrypt hashing
function verifyPassword($password, $hash) { ... } // Password verification
function generateCSRFToken() { ... }        // CSRF protection
```

## Session Functions (`config/session.php`)
```php
function isLoggedIn() { ... }      // Check authentication
function isAdmin() { ... }         // Check admin role
function requireLogin() { ... }    // Redirect if not logged in
function requireAdmin() { ... }    // Redirect if not admin
function setUserSession($user) { ... } // Create session
function clearSession() { ... }    // Destroy session
```

---

# Database Schema

## Tables

### users
| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT | Primary key |
| username | VARCHAR(100) UNIQUE | Login name |
| password | VARCHAR(255) | Hashed password |
| full_name | VARCHAR(255) | Display name |
| role | VARCHAR(50) | User role (admin/staff) |
| is_active | TINYINT(1) | Online status |
| last_login | TIMESTAMP | Last login time |
| session_start | TIMESTAMP | Current session start |
| last_activity | TIMESTAMP | Last activity time |
| last_login_duration | INT | Previous session duration (seconds) |
| date_created | TIMESTAMP | Account creation time |

### categories
| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT | Primary key |
| name | VARCHAR(100) UNIQUE | Category name |
| description | VARCHAR(255) | Category description |

### hardware
| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT | Primary key |
| name | VARCHAR(255) | Hardware name |
| category_id | INT | Foreign key to categories |
| type | VARCHAR(100) | Hardware type |
| brand | VARCHAR(100) | Manufacturer |
| model | VARCHAR(100) | Model number |
| serial_number | VARCHAR(100) | Serial number |
| total_quantity | INT | Sum of all quantities |
| unused_quantity | INT | Available items |
| in_use_quantity | INT | Deployed items |
| damaged_quantity | INT | Broken items |
| repair_quantity | INT | Items in repair |
| location | VARCHAR(100) | Physical location |
| date_added | TIMESTAMP | Creation time |
| deleted_at | TIMESTAMP | Soft delete timestamp |

### inventory_history
| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT | Primary key |
| hardware_id | INT | Reference to hardware (optional) |
| hardware_name | VARCHAR(255) | Denormalized name |
| category_name | VARCHAR(100) | Denormalized category |
| serial_number | VARCHAR(100) | Denormalized serial |
| user_id | INT | Reference to user (optional) |
| user_name | VARCHAR(255) | Denormalized user name |
| action_type | VARCHAR(50) | Added, Updated, Deleted, Restored |
| quantity_change | INT | Net quantity change |
| old_unused | INT | Previous unused qty |
| old_in_use | INT | Previous in-use qty |
| old_damaged | INT | Previous damaged qty |
| old_repair | INT | Previous repair qty |
| new_unused | INT | New unused qty |
| new_in_use | INT | New in-use qty |
| new_damaged | INT | New damaged qty |
| new_repair | INT | New repair qty |
| action_date | TIMESTAMP | When action occurred |

---

# Summary

This PC Hardware Inventory System provides:

1. **Secure Authentication** - Password hashing, session management, role-based access
2. **Complete CRUD Operations** - Add, edit, soft delete, restore, permanent delete
3. **Audit Trail** - Denormalized history preserves data even after deletions
4. **Bulk Operations** - CSV import/export, batch operations
5. **Real-time Updates** - Live session tracking, auto-refresh
6. **Data Protection** - Backup/restore functionality
7. **User Management** - Admin-controlled user accounts
8. **Self-Service** - Profile and password management

All pages follow HCI (Human-Computer Interaction) principles with responsive design, accessibility features, and intuitive user interfaces.
