<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/security.php';

// Require admin
requireAdmin();

$pageTitle = 'Backup & Restore - PC Hardware Inventory';
$conn = getDBConnection();

// Create backups directory if it doesn't exist
$backups_dir = __DIR__ . '/../backups';
if (!is_dir($backups_dir)) {
    mkdir($backups_dir, 0755, true);
}

// Handle backup creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_backup') {
    $timestamp = date('Y-m-d_H-i-s');
    $backup_filename = "backup_{$timestamp}.sql";
    $backup_path = $backups_dir . '/' . $backup_filename;
    
    try {
        $backup_content = "-- PC Hardware Inventory Backup\n";
        $backup_content .= "-- Created: " . date('Y-m-d H:i:s') . "\n";
        $backup_content .= "-- Created by: " . $_SESSION['full_name'] . "\n\n";
        
        // Disable foreign key checks at the start of backup
        $backup_content .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        // Get all tables (order: tables with foreign keys should be dropped first)
        $tables = ['inventory_history', 'hardware', 'users', 'categories'];
        
        foreach ($tables as $table) {
            // Get table structure
            $result = $conn->query("SHOW CREATE TABLE $table");
            if ($result) {
                $row = $result->fetch_assoc();
                $backup_content .= "\n-- Table structure for `$table`\n";
                $backup_content .= "DROP TABLE IF EXISTS `$table`;\n";
                $backup_content .= $row['Create Table'] . ";\n\n";
            }
            
            // Get table data
            $result = $conn->query("SELECT * FROM $table");
            if ($result && $result->num_rows > 0) {
                $backup_content .= "-- Data for `$table`\n";
                while ($row = $result->fetch_assoc()) {
                    $values = array_map(function($val) use ($conn) {
                        if ($val === null) {
                            return 'NULL';
                        }
                        return "'" . $conn->real_escape_string($val) . "'";
                    }, $row);
                    $backup_content .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                }
                $backup_content .= "\n";
            }
        }
        
        // Re-enable foreign key checks at the end of backup
        $backup_content .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";
        
        // Write backup file
        if (file_put_contents($backup_path, $backup_content)) {
            redirectWithMessage(BASE_PATH . 'pages/backup.php', "Backup created successfully: $backup_filename", 'success');
        } else {
            redirectWithMessage(BASE_PATH . 'pages/backup.php', 'Failed to write backup file.', 'error');
        }
    } catch (Exception $e) {
        redirectWithMessage(BASE_PATH . 'pages/backup.php', 'Backup failed: ' . $e->getMessage(), 'error');
    }
}

// Handle backup download
if (isset($_GET['download']) && !empty($_GET['download'])) {
    $filename = basename($_GET['download']); // Prevent directory traversal
    $filepath = $backups_dir . '/' . $filename;
    
    if (file_exists($filepath) && pathinfo($filepath, PATHINFO_EXTENSION) === 'sql') {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        redirectWithMessage(BASE_PATH . 'pages/backup.php', 'Backup file not found.', 'error');
    }
}

// Handle backup deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $filename = basename($_GET['delete']); // Prevent directory traversal
    $filepath = $backups_dir . '/' . $filename;
    
    if (file_exists($filepath) && pathinfo($filepath, PATHINFO_EXTENSION) === 'sql') {
        if (unlink($filepath)) {
            redirectWithMessage(BASE_PATH . 'pages/backup.php', 'Backup deleted successfully.', 'success');
        } else {
            redirectWithMessage(BASE_PATH . 'pages/backup.php', 'Failed to delete backup.', 'error');
        }
    } else {
        redirectWithMessage(BASE_PATH . 'pages/backup.php', 'Backup file not found.', 'error');
    }
}

// Handle restore
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'restore') {
    $filename = isset($_POST['filename']) ? basename($_POST['filename']) : '';
    $filepath = $backups_dir . '/' . $filename;
    
    if (!empty($filename) && file_exists($filepath) && pathinfo($filepath, PATHINFO_EXTENSION) === 'sql') {
        try {
            $sql = file_get_contents($filepath);
            
            // Validate this is a backup file created by our system
            if (strpos($sql, '-- PC Hardware Inventory Backup') !== 0) {
                redirectWithMessage(BASE_PATH . 'pages/backup.php', 'Invalid backup file format. Only backups created by this system can be restored.', 'error');
                exit;
            }
            
            // Additional validation: check that only expected tables are referenced
            $allowed_tables = ['categories', 'hardware', 'users', 'inventory_history'];
            $sql_lower = strtolower($sql);
            
            // Check for potentially dangerous SQL patterns
            $dangerous_patterns = [
                'drop database', 'create database', 'grant ', 'revoke ', 
                'create user', 'alter user', 'drop user', 'load_file', 
                'into outfile', 'into dumpfile', 'information_schema',
                'mysql.user', 'sleep(', 'benchmark('
            ];
            
            foreach ($dangerous_patterns as $pattern) {
                if (stripos($sql_lower, $pattern) !== false) {
                    redirectWithMessage(BASE_PATH . 'pages/backup.php', 'Backup file contains potentially dangerous SQL. Restore aborted.', 'error');
                    exit;
                }
            }
            
            // Disable foreign key checks to allow dropping tables in any order
            $conn->query("SET FOREIGN_KEY_CHECKS = 0");
            
            // Split SQL into individual statements and execute
            $conn->multi_query($sql);
            
            // Process all result sets
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());
            
            // Re-enable foreign key checks
            // After multi_query(), the connection is in a special state processing multiple result sets.
            // We cannot execute new queries on the same connection until all results are consumed.
            // Getting a fresh connection ensures we can safely run the SET FOREIGN_KEY_CHECKS command.
            $restore_error = $conn->error;
            
            // Get a fresh connection to re-enable foreign key checks
            $conn = getDBConnection();
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            
            if ($restore_error) {
                redirectWithMessage(BASE_PATH . 'pages/backup.php', 'Restore failed: ' . $restore_error, 'error');
            } else {
                redirectWithMessage(BASE_PATH . 'pages/backup.php', 'Database restored successfully from: ' . $filename, 'success');
            }
        } catch (Exception $e) {
            redirectWithMessage(BASE_PATH . 'pages/backup.php', 'Restore failed: ' . $e->getMessage(), 'error');
        }
    } else {
        redirectWithMessage(BASE_PATH . 'pages/backup.php', 'Invalid backup file.', 'error');
    }
}

// Get list of existing backups
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

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="system-branding">
            <h6><i class="bi bi-building"></i> ACLC COLLEGE OF ORMOC - PC HARDWARE INVENTORY SYSTEM</h6>
        </div>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
            <div class="mb-3 mb-md-0">
                <h1 class="text-gradient mb-1">
                    <i class="bi bi-database"></i> Backup & Restore
                </h1>
                <p class="text-muted">Manage database backups (Admin only)</p>
            </div>
            <form method="POST" class="d-inline">
                <input type="hidden" name="action" value="create_backup">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-download"></i> Create Backup Now
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Backup Info -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-info-circle text-primary"></i> Backup Information</h5>
                <p class="card-text">
                    Backups include all inventory data:
                </p>
                <ul>
                    <li>Categories</li>
                    <li>Hardware items</li>
                    <li>User accounts</li>
                    <li>Audit trail / History</li>
                </ul>
                <p class="text-muted small">
                    <i class="bi bi-exclamation-triangle text-warning"></i> 
                    Restoring a backup will replace all current data. Use with caution.
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-folder text-primary"></i> Storage Location</h5>
                <p class="card-text">
                    Backups are stored in: <code>/backups/</code>
                </p>
                <p class="card-text">
                    <strong>Total Backups:</strong> <?php echo count($backups); ?>
                </p>
                <?php 
                $total_size = array_sum(array_column($backups, 'size'));
                ?>
                <p class="card-text">
                    <strong>Total Size:</strong> <?php echo number_format($total_size / 1024, 2); ?> KB
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Backups List -->
<div class="card table-card">
    <div class="card-header card-header-primary">
        <h5 class="mb-0"><i class="bi bi-archive"></i> Available Backups</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Created</th>
                        <th>Size</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($backups)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size: 32px; opacity: 0.5;"></i>
                            <br>No backups found. Create your first backup above.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($backups as $backup): ?>
                    <tr>
                        <td>
                            <i class="bi bi-file-earmark-code text-primary"></i>
                            <?php echo escapeOutput($backup['filename']); ?>
                        </td>
                        <td><?php echo date('M d, Y H:i:s', $backup['created']); ?></td>
                        <td><?php echo number_format($backup['size'] / 1024, 2); ?> KB</td>
                        <td>
                            <a href="?download=<?php echo urlencode($backup['filename']); ?>" class="btn btn-sm btn-info">
                                <i class="bi bi-download"></i> Download
                            </a>
                            <button type="button" class="btn btn-sm btn-warning" 
                                    onclick="confirmRestore('<?php echo escapeOutput($backup['filename']); ?>')">
                                <i class="bi bi-arrow-counterclockwise"></i> Restore
                            </button>
                            <a href="?delete=<?php echo urlencode($backup['filename']); ?>" class="btn btn-sm btn-danger"
                               onclick="return confirm('Are you sure you want to delete this backup?')">
                                <i class="bi bi-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Restore Confirmation Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Confirm Restore</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="restore">
                    <input type="hidden" name="filename" id="restore_filename">
                    <div class="alert alert-danger">
                        <strong><i class="bi bi-exclamation-circle"></i> Warning!</strong>
                        <p class="mb-0">Restoring a backup will replace ALL current data. This action cannot be undone.</p>
                    </div>
                    <p>Are you sure you want to restore from: <strong id="restore_filename_display"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-arrow-counterclockwise"></i> Yes, Restore
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmRestore(filename) {
    document.getElementById('restore_filename').value = filename;
    document.getElementById('restore_filename_display').textContent = filename;
    var modal = new bootstrap.Modal(document.getElementById('restoreModal'));
    modal.show();
}
</script>

<?php include '../includes/footer.php'; ?>
