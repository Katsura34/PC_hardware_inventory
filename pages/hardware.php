<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/security.php';

// Require login
requireLogin();

$pageTitle = 'Hardware Management - PC Hardware Inventory';
$conn = getDBConnection();

// Handle delete (soft delete)
if (isset($_GET['delete']) && validateInt($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Get hardware details before deleting for history log
    $old_stmt = $conn->prepare("SELECT name, total_quantity, unused_quantity, in_use_quantity, damaged_quantity, repair_quantity FROM hardware WHERE id = ? AND deleted_at IS NULL");
    $old_stmt->bind_param("i", $id);
    $old_stmt->execute();
    $old_result = $old_stmt->get_result();
    $old_data = $old_result->fetch_assoc();
    $old_stmt->close();
    
    if ($old_data) {
        // Get additional data for history logging (denormalized)
        $detail_stmt = $conn->prepare("SELECT h.name, h.serial_number, c.name as category_name 
                                       FROM hardware h 
                                       LEFT JOIN categories c ON h.category_id = c.id 
                                       WHERE h.id = ?");
        $detail_stmt->bind_param("i", $id);
        $detail_stmt->execute();
        $detail_result = $detail_stmt->get_result();
        $detail_data = $detail_result->fetch_assoc();
        $detail_stmt->close();
        
        // Log to history before deleting with denormalized data
        $user_id = $_SESSION['user_id'];
        $user_name = $_SESSION['full_name'];
        $quantity_change = -$old_data['total_quantity'];
        
        $log_stmt = $conn->prepare("INSERT INTO inventory_history (hardware_id, hardware_name, category_name, serial_number, 
                                   user_id, user_name, action_type, quantity_change, 
                                   old_unused, old_in_use, old_damaged, old_repair, 
                                   new_unused, new_in_use, new_damaged, new_repair) 
                                   VALUES (?, ?, ?, ?, ?, ?, 'Deleted', ?, ?, ?, ?, ?, 0, 0, 0, 0)");
        $log_stmt->bind_param("isssisiiiii", $id, $detail_data['name'], $detail_data['category_name'], 
                             $detail_data['serial_number'], $user_id, $user_name, $quantity_change, 
                             $old_data['unused_quantity'], $old_data['in_use_quantity'], 
                             $old_data['damaged_quantity'], $old_data['repair_quantity']);
        $log_stmt->execute();
        $log_stmt->close();
    }
    
    // Soft delete hardware (set deleted_at timestamp)
    $stmt = $conn->prepare("UPDATE hardware SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        redirectWithMessage(BASE_PATH . 'pages/hardware.php', 'Hardware deleted successfully.', 'success');
    } else {
        redirectWithMessage(BASE_PATH . 'pages/hardware.php', 'Failed to delete hardware.', 'error');
    }
    $stmt->close();
}

// Handle restore (admin only)
if (isset($_GET['restore']) && validateInt($_GET['restore']) && isAdmin()) {
    $id = (int)$_GET['restore'];
    
    // Restore hardware
    $stmt = $conn->prepare("UPDATE hardware SET deleted_at = NULL WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        // Log to history
        $detail_stmt = $conn->prepare("SELECT h.name, h.serial_number, h.total_quantity, h.unused_quantity, h.in_use_quantity, h.damaged_quantity, h.repair_quantity, c.name as category_name 
                                       FROM hardware h 
                                       LEFT JOIN categories c ON h.category_id = c.id 
                                       WHERE h.id = ?");
        $detail_stmt->bind_param("i", $id);
        $detail_stmt->execute();
        $detail_result = $detail_stmt->get_result();
        $detail_data = $detail_result->fetch_assoc();
        $detail_stmt->close();
        
        if ($detail_data) {
            $user_id = $_SESSION['user_id'];
            $user_name = $_SESSION['full_name'];
            $log_stmt = $conn->prepare("INSERT INTO inventory_history (hardware_id, hardware_name, category_name, serial_number, 
                                       user_id, user_name, action_type, quantity_change, 
                                       old_unused, old_in_use, old_damaged, old_repair, 
                                       new_unused, new_in_use, new_damaged, new_repair) 
                                       VALUES (?, ?, ?, ?, ?, ?, 'Restored', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $log_stmt->bind_param("isssisiiiiiiiii", $id, $detail_data['name'], $detail_data['category_name'], 
                                 $detail_data['serial_number'], $user_id, $user_name, $detail_data['total_quantity'], 
                                 0, 0, 0, 0,
                                 $detail_data['unused_quantity'], $detail_data['in_use_quantity'], 
                                 $detail_data['damaged_quantity'], $detail_data['repair_quantity']);
            $log_stmt->execute();
            $log_stmt->close();
        }
        
        redirectWithMessage(BASE_PATH . 'pages/hardware.php', 'Hardware restored successfully.', 'success');
    } else {
        redirectWithMessage(BASE_PATH . 'pages/hardware.php', 'Failed to restore hardware.', 'error');
    }
    $stmt->close();
}

// Handle permanent delete (admin only)
if (isset($_GET['permanent_delete']) && validateInt($_GET['permanent_delete']) && isAdmin()) {
    $id = (int)$_GET['permanent_delete'];
    
    // Permanently delete hardware
    $stmt = $conn->prepare("DELETE FROM hardware WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        redirectWithMessage(BASE_PATH . 'pages/hardware.php', 'Hardware permanently deleted.', 'success');
    } else {
        redirectWithMessage(BASE_PATH . 'pages/hardware.php', 'Failed to permanently delete hardware.', 'error');
    }
    $stmt->close();
}

// Handle batch delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_action']) && $_POST['batch_action'] === 'delete') {
    $ids = isset($_POST['ids']) ? array_map('intval', $_POST['ids']) : [];
    
    if (!empty($ids)) {
        $deleted_count = 0;
        foreach ($ids as $id) {
            // Get hardware details before deleting for history log
            $old_stmt = $conn->prepare("SELECT h.name, h.serial_number, h.total_quantity, h.unused_quantity, h.in_use_quantity, h.damaged_quantity, h.repair_quantity, c.name as category_name 
                                       FROM hardware h 
                                       LEFT JOIN categories c ON h.category_id = c.id 
                                       WHERE h.id = ? AND h.deleted_at IS NULL");
            $old_stmt->bind_param("i", $id);
            $old_stmt->execute();
            $old_result = $old_stmt->get_result();
            $old_data = $old_result->fetch_assoc();
            $old_stmt->close();
            
            if ($old_data) {
                // Log to history
                $user_id = $_SESSION['user_id'];
                $user_name = $_SESSION['full_name'];
                $quantity_change = -$old_data['total_quantity'];
                
                $log_stmt = $conn->prepare("INSERT INTO inventory_history (hardware_id, hardware_name, category_name, serial_number, 
                                           user_id, user_name, action_type, quantity_change, 
                                           old_unused, old_in_use, old_damaged, old_repair, 
                                           new_unused, new_in_use, new_damaged, new_repair) 
                                           VALUES (?, ?, ?, ?, ?, ?, 'Deleted', ?, ?, ?, ?, ?, 0, 0, 0, 0)");
                $log_stmt->bind_param("isssisiiiii", $id, $old_data['name'], $old_data['category_name'], 
                                     $old_data['serial_number'], $user_id, $user_name, $quantity_change, 
                                     $old_data['unused_quantity'], $old_data['in_use_quantity'], 
                                     $old_data['damaged_quantity'], $old_data['repair_quantity']);
                $log_stmt->execute();
                $log_stmt->close();
                
                // Soft delete
                $del_stmt = $conn->prepare("UPDATE hardware SET deleted_at = NOW() WHERE id = ?");
                $del_stmt->bind_param("i", $id);
                if ($del_stmt->execute() && $del_stmt->affected_rows > 0) {
                    $deleted_count++;
                }
                $del_stmt->close();
            }
        }
        
        if ($deleted_count > 0) {
            redirectWithMessage(BASE_PATH . 'pages/hardware.php', "$deleted_count item(s) deleted successfully.", 'success');
        } else {
            redirectWithMessage(BASE_PATH . 'pages/hardware.php', 'No items were deleted.', 'error');
        }
    } else {
        redirectWithMessage(BASE_PATH . 'pages/hardware.php', 'No items selected.', 'error');
    }
}

// Handle batch status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_action']) && $_POST['batch_action'] === 'update_status') {
    $ids = isset($_POST['ids']) ? array_map('intval', $_POST['ids']) : [];
    $status_type = isset($_POST['status_type']) ? sanitizeInput($_POST['status_type']) : '';
    $quantity_change = isset($_POST['quantity_change']) ? (int)$_POST['quantity_change'] : 0;
    
    if (!empty($ids) && !empty($status_type) && $quantity_change != 0) {
        $updated_count = 0;
        // Use a strict whitelist to prevent SQL injection
        $valid_statuses = [
            'unused_quantity' => 'unused_quantity',
            'in_use_quantity' => 'in_use_quantity', 
            'damaged_quantity' => 'damaged_quantity',
            'repair_quantity' => 'repair_quantity'
        ];
        
        if (isset($valid_statuses[$status_type])) {
            // Get the safe column name from our whitelist
            $safe_column = $valid_statuses[$status_type];
            
            foreach ($ids as $id) {
                // Get current values
                $old_stmt = $conn->prepare("SELECT h.*, c.name as category_name FROM hardware h LEFT JOIN categories c ON h.category_id = c.id WHERE h.id = ? AND h.deleted_at IS NULL");
                $old_stmt->bind_param("i", $id);
                $old_stmt->execute();
                $old_result = $old_stmt->get_result();
                $old_data = $old_result->fetch_assoc();
                $old_stmt->close();
                
                if ($old_data) {
                    // Calculate new values
                    $new_value = max(0, $old_data[$safe_column] + $quantity_change);
                    
                    // Update the status - using safe column name from whitelist
                    // Build query based on which column to update
                    switch ($safe_column) {
                        case 'unused_quantity':
                            $update_stmt = $conn->prepare("UPDATE hardware SET unused_quantity = ?, total_quantity = ? + in_use_quantity + damaged_quantity + repair_quantity WHERE id = ?");
                            break;
                        case 'in_use_quantity':
                            $update_stmt = $conn->prepare("UPDATE hardware SET in_use_quantity = ?, total_quantity = unused_quantity + ? + damaged_quantity + repair_quantity WHERE id = ?");
                            break;
                        case 'damaged_quantity':
                            $update_stmt = $conn->prepare("UPDATE hardware SET damaged_quantity = ?, total_quantity = unused_quantity + in_use_quantity + ? + repair_quantity WHERE id = ?");
                            break;
                        case 'repair_quantity':
                            $update_stmt = $conn->prepare("UPDATE hardware SET repair_quantity = ?, total_quantity = unused_quantity + in_use_quantity + damaged_quantity + ? WHERE id = ?");
                            break;
                    }
                    $update_stmt->bind_param("iii", $new_value, $new_value, $id);
                    
                    if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {
                        // Get updated values for history
                        $new_stmt = $conn->prepare("SELECT unused_quantity, in_use_quantity, damaged_quantity, repair_quantity, total_quantity FROM hardware WHERE id = ?");
                        $new_stmt->bind_param("i", $id);
                        $new_stmt->execute();
                        $new_result = $new_stmt->get_result();
                        $new_data = $new_result->fetch_assoc();
                        $new_stmt->close();
                        
                        // Log to history
                        $user_id = $_SESSION['user_id'];
                        $user_name = $_SESSION['full_name'];
                        $total_change = $new_data['total_quantity'] - ($old_data['unused_quantity'] + $old_data['in_use_quantity'] + $old_data['damaged_quantity'] + $old_data['repair_quantity']);
                        
                        $log_stmt = $conn->prepare("INSERT INTO inventory_history (hardware_id, hardware_name, category_name, serial_number, 
                                                   user_id, user_name, action_type, quantity_change, 
                                                   old_unused, old_in_use, old_damaged, old_repair, 
                                                   new_unused, new_in_use, new_damaged, new_repair) 
                                                   VALUES (?, ?, ?, ?, ?, ?, 'Updated', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $log_stmt->bind_param("isssisiiiiiiiii", $id, $old_data['name'], $old_data['category_name'], 
                                             $old_data['serial_number'], $user_id, $user_name, $total_change, 
                                             $old_data['unused_quantity'], $old_data['in_use_quantity'], 
                                             $old_data['damaged_quantity'], $old_data['repair_quantity'],
                                             $new_data['unused_quantity'], $new_data['in_use_quantity'], 
                                             $new_data['damaged_quantity'], $new_data['repair_quantity']);
                        $log_stmt->execute();
                        $log_stmt->close();
                        
                        $updated_count++;
                    }
                    $update_stmt->close();
                }
            }
            
            if ($updated_count > 0) {
                redirectWithMessage(BASE_PATH . 'pages/hardware.php', "$updated_count item(s) updated successfully.", 'success');
            } else {
                redirectWithMessage(BASE_PATH . 'pages/hardware.php', 'No items were updated.', 'error');
            }
        } else {
            redirectWithMessage(BASE_PATH . 'pages/hardware.php', 'Invalid status type.', 'error');
        }
    } else {
        redirectWithMessage(BASE_PATH . 'pages/hardware.php', 'Missing required fields.', 'error');
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    $name = sanitizeForDB($conn, $_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $type = sanitizeForDB($conn, $_POST['type']);
    $brand = sanitizeForDB($conn, $_POST['brand']);
    $model = sanitizeForDB($conn, $_POST['model']);
    $serial_number = sanitizeForDB($conn, $_POST['serial_number']);
    $unused_quantity = (int)$_POST['unused_quantity'];
    $in_use_quantity = (int)$_POST['in_use_quantity'];
    $damaged_quantity = (int)$_POST['damaged_quantity'];
    $repair_quantity = (int)$_POST['repair_quantity'];
    $total_quantity = $unused_quantity + $in_use_quantity + $damaged_quantity + $repair_quantity;
    $location = sanitizeForDB($conn, $_POST['location']);
    
    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO hardware (name, category_id, type, brand, model, serial_number, 
                               total_quantity, unused_quantity, in_use_quantity, damaged_quantity, repair_quantity, location) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sissssiiiiis", $name, $category_id, $type, $brand, $model, $serial_number, 
                         $total_quantity, $unused_quantity, $in_use_quantity, $damaged_quantity, $repair_quantity, $location);
        
        if ($stmt->execute()) {
            $hardware_id = $conn->insert_id;
            
            // Get category name for history logging (denormalized)
            $cat_stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
            $cat_stmt->bind_param("i", $category_id);
            $cat_stmt->execute();
            $cat_result = $cat_stmt->get_result();
            $cat_data = $cat_result->fetch_assoc();
            $category_name = $cat_data['name'];
            $cat_stmt->close();
            
            // Log to history with denormalized data
            $user_id = $_SESSION['user_id'];
            $user_name = $_SESSION['full_name'];
            $log_stmt = $conn->prepare("INSERT INTO inventory_history (hardware_id, hardware_name, category_name, serial_number, 
                                       user_id, user_name, action_type, quantity_change, 
                                       old_unused, old_in_use, old_damaged, old_repair, 
                                       new_unused, new_in_use, new_damaged, new_repair) 
                                       VALUES (?, ?, ?, ?, ?, ?, 'Added', ?, 0, 0, 0, 0, ?, ?, ?, ?)");
            $log_stmt->bind_param("isssisiiiii", $hardware_id, $name, $category_name, $serial_number, 
                                 $user_id, $user_name, $total_quantity, 
                                 $unused_quantity, $in_use_quantity, $damaged_quantity, $repair_quantity);
            $log_stmt->execute();
            $log_stmt->close();
            
            redirectWithMessage(BASE_PATH . 'pages/hardware.php', 'Hardware added successfully.', 'success');
        } else {
            redirectWithMessage(BASE_PATH . 'pages/hardware.php', 'Failed to add hardware.', 'error');
        }
        $stmt->close();
        
    } elseif ($action === 'edit' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        
        // Get old values
        $old_stmt = $conn->prepare("SELECT unused_quantity, in_use_quantity, damaged_quantity, repair_quantity FROM hardware WHERE id = ?");
        $old_stmt->bind_param("i", $id);
        $old_stmt->execute();
        $old_result = $old_stmt->get_result();
        $old_data = $old_result->fetch_assoc();
        $old_stmt->close();
        
        // Update hardware
        $stmt = $conn->prepare("UPDATE hardware SET name=?, category_id=?, type=?, brand=?, model=?, serial_number=?, 
                               total_quantity=?, unused_quantity=?, in_use_quantity=?, damaged_quantity=?, 
                               repair_quantity=?, location=? WHERE id=?");
        $stmt->bind_param("sissssiiiiisi", $name, $category_id, $type, $brand, $model, $serial_number, 
                         $total_quantity, $unused_quantity, $in_use_quantity, $damaged_quantity, $repair_quantity, $location, $id);
        
        if ($stmt->execute()) {
            // Get hardware and category name for history logging (denormalized)
            $detail_stmt = $conn->prepare("SELECT h.name, h.serial_number, c.name as category_name 
                                          FROM hardware h 
                                          LEFT JOIN categories c ON h.category_id = c.id 
                                          WHERE h.id = ?");
            $detail_stmt->bind_param("i", $id);
            $detail_stmt->execute();
            $detail_result = $detail_stmt->get_result();
            $detail_data = $detail_result->fetch_assoc();
            $detail_stmt->close();
            
            // Log to history with denormalized data
            $user_id = $_SESSION['user_id'];
            $user_name = $_SESSION['full_name'];
            $quantity_change = $total_quantity - ($old_data['unused_quantity'] + $old_data['in_use_quantity'] + 
                                                   $old_data['damaged_quantity'] + $old_data['repair_quantity']);
            
            $log_stmt = $conn->prepare("INSERT INTO inventory_history (hardware_id, hardware_name, category_name, serial_number, 
                                       user_id, user_name, action_type, quantity_change, 
                                       old_unused, old_in_use, old_damaged, old_repair, 
                                       new_unused, new_in_use, new_damaged, new_repair) 
                                       VALUES (?, ?, ?, ?, ?, ?, 'Updated', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $log_stmt->bind_param("isssisiiiiiiiii", $id, $detail_data['name'], $detail_data['category_name'], 
                                 $detail_data['serial_number'], $user_id, $user_name, $quantity_change, 
                                 $old_data['unused_quantity'], $old_data['in_use_quantity'], 
                                 $old_data['damaged_quantity'], $old_data['repair_quantity'],
                                 $unused_quantity, $in_use_quantity, $damaged_quantity, $repair_quantity);
            $log_stmt->execute();
            $log_stmt->close();
            
            redirectWithMessage(BASE_PATH . 'pages/hardware.php', 'Hardware updated successfully.', 'success');
        } else {
            redirectWithMessage(BASE_PATH . 'pages/hardware.php', 'Failed to update hardware.', 'error');
        }
        $stmt->close();
    }
}

// Get filter parameters
$filter_category = isset($_GET['filter_category']) ? (int)$_GET['filter_category'] : 0;
$filter_brand = isset($_GET['filter_brand']) ? sanitizeInput($_GET['filter_brand']) : '';
$filter_model = isset($_GET['filter_model']) ? sanitizeInput($_GET['filter_model']) : '';
$show_deleted = isset($_GET['show_deleted']) && $_GET['show_deleted'] === '1' && isAdmin();

// Pagination settings
$records_per_page = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Build count query for pagination - respect soft deletes
$deleted_filter = $show_deleted ? "" : " AND h.deleted_at IS NULL";
$count_query = "SELECT COUNT(*) as total FROM hardware h WHERE 1=1" . $deleted_filter;

// Build query with filters
$query = "SELECT h.*, c.name as category_name FROM hardware h 
          LEFT JOIN categories c ON h.category_id = c.id 
          WHERE 1=1" . $deleted_filter;

$params = [];
$types = "";

if ($filter_category > 0) {
    $query .= " AND h.category_id = ?";
    $count_query .= " AND h.category_id = ?";
    $params[] = $filter_category;
    $types .= "i";
}

if (!empty($filter_brand)) {
    $query .= " AND h.brand LIKE ?";
    $count_query .= " AND h.brand LIKE ?";
    $params[] = "%" . $filter_brand . "%";
    $types .= "s";
}

if (!empty($filter_model)) {
    $query .= " AND h.model LIKE ?";
    $count_query .= " AND h.model LIKE ?";
    $params[] = "%" . $filter_model . "%";
    $types .= "s";
}

// Get total count for pagination
$total_records = 0;
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_records = $count_result->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $count_result = $conn->query($count_query);
    $total_records = $count_result->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $records_per_page);

// Add pagination to main query
$query .= " ORDER BY h.date_added DESC LIMIT ? OFFSET ?";
$params[] = $records_per_page;
$params[] = $offset;
$types .= "ii";

// Get hardware with filters and pagination
$hardware = [];
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $hardware[] = $row;
}
$stmt->close();

// Build pagination URL parameters
$pagination_params = array_filter([
    'filter_category' => $filter_category ?: null,
    'filter_brand' => $filter_brand ?: null,
    'filter_model' => $filter_model ?: null,
    'show_deleted' => $show_deleted ? '1' : null
]);

// Get all categories for dropdown
$categories = [];
$result = $conn->query("SELECT * FROM categories ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Get distinct brands for filter dropdown (exclude soft-deleted items)
$brands = [];
$result = $conn->query("SELECT DISTINCT brand FROM hardware WHERE brand IS NOT NULL AND brand != '' AND deleted_at IS NULL ORDER BY brand");
while ($row = $result->fetch_assoc()) {
    if (!empty($row['brand'])) {
        $brands[] = $row['brand'];
    }
}

// Get distinct models for filter dropdown (exclude soft-deleted items)
$models = [];
$result = $conn->query("SELECT DISTINCT model FROM hardware WHERE model IS NOT NULL AND model != '' AND deleted_at IS NULL ORDER BY model");
while ($row = $result->fetch_assoc()) {
    if (!empty($row['model'])) {
        $models[] = $row['model'];
    }
}

// Get distinct locations for dropdown (exclude soft-deleted items)
$locations = [];
$result = $conn->query("SELECT DISTINCT location FROM hardware WHERE location IS NOT NULL AND location != '' AND deleted_at IS NULL ORDER BY location");
while ($row = $result->fetch_assoc()) {
    if (!empty($row['location'])) {
        $locations[] = $row['location'];
    }
}
// Add default locations if not present
$default_locations = ['Lab 1', 'Lab 2', 'Lab 3', 'Lab 4', 'Office', 'Storage', 'Warehouse'];
foreach ($default_locations as $loc) {
    if (!in_array($loc, $locations)) {
        $locations[] = $loc;
    }
}
sort($locations);

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
                    <i class="bi bi-cpu"></i> Hardware Management
                </h1>
                <p class="text-muted">Manage your hardware inventory</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importCSVModal">
                    <i class="bi bi-upload"></i> Import CSV
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHardwareModal">
                    <i class="bi bi-plus-circle"></i> Add Hardware
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hardware Table -->
<div class="card table-card">
    <div class="card-header card-header-primary">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <h5 class="mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-table" aria-hidden="true"></i> <?php echo $show_deleted ? 'All Hardware (Including Deleted)' : 'All Hardware'; ?>
                <span class="badge bg-light text-primary ms-2"><?php echo $total_records; ?></span>
            </h5>
            <div class="d-flex gap-2 align-items-center">
                <?php if (isAdmin()): ?>
                <!-- Show Deleted Toggle (Admin Only) -->
                <a href="?<?php echo http_build_query(array_merge($pagination_params, ['show_deleted' => $show_deleted ? null : '1', 'page' => 1])); ?>" 
                   class="btn btn-sm <?php echo $show_deleted ? 'btn-warning' : 'btn-outline-secondary'; ?>"
                   title="<?php echo $show_deleted ? 'Hide deleted items' : 'Show deleted items'; ?>">
                    <i class="bi bi-trash<?php echo $show_deleted ? '-fill' : ''; ?>"></i>
                    <span class="d-none d-sm-inline"><?php echo $show_deleted ? 'Hide Deleted' : 'Show Deleted'; ?></span>
                </a>
                <?php endif; ?>
                <!-- Toggle Search Button -->
                <button class="btn btn-sm btn-light" type="button" id="toggleSearchBtn" 
                        aria-expanded="false" aria-controls="searchFilterPanel"
                        onclick="toggleHardwareSearch()">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <span class="d-none d-sm-inline">Search</span>
                </button>
                <!-- Filter Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-sm <?php echo ($filter_category > 0 || !empty($filter_brand) || !empty($filter_model)) ? 'btn-warning' : 'btn-light'; ?>" type="button" id="filterDropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                        <i class="bi bi-funnel<?php echo ($filter_category > 0 || !empty($filter_brand) || !empty($filter_model)) ? '-fill' : ''; ?>"></i>
                        <span class="d-none d-sm-inline"> Filters</span>
                        <?php if ($filter_category > 0 || !empty($filter_brand) || !empty($filter_model)): ?>
                        <span class="badge bg-dark text-white ms-1"><?php echo count(array_filter([$filter_category > 0, !empty($filter_brand), !empty($filter_model)])); ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end filter-dropdown p-3 shadow-lg" aria-labelledby="filterDropdown" style="min-width: 300px;">
                        <form method="GET" id="filterForm">
                            <?php if ($show_deleted): ?>
                            <input type="hidden" name="show_deleted" value="1">
                            <?php endif; ?>
                            <h6 class="dropdown-header px-0 mb-2"><i class="bi bi-funnel me-1"></i> Filter Hardware</h6>
                            <div class="mb-3">
                                <label for="filter_category" class="form-label small mb-1">Category</label>
                                <select class="form-select form-select-sm" id="filter_category" name="filter_category">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $filter_category == $cat['id'] ? 'selected' : ''; ?>><?php echo escapeOutput($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="filter_brand" class="form-label small mb-1">Brand</label>
                                <select class="form-select form-select-sm" id="filter_brand" name="filter_brand">
                                    <option value="">All Brands</option>
                                    <?php foreach ($brands as $brand): ?>
                                    <option value="<?php echo escapeOutput($brand); ?>" <?php echo $filter_brand === $brand ? 'selected' : ''; ?>><?php echo escapeOutput($brand); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="filter_model" class="form-label small mb-1">Model</label>
                                <select class="form-select form-select-sm" id="filter_model" name="filter_model">
                                    <option value="">All Models</option>
                                    <?php foreach ($models as $model): ?>
                                    <option value="<?php echo escapeOutput($model); ?>" <?php echo $filter_model === $model ? 'selected' : ''; ?>><?php echo escapeOutput($model); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                                    <i class="bi bi-check-lg"></i> Apply
                                </button>
                                <a href="<?php echo BASE_PATH; ?>pages/hardware.php<?php echo $show_deleted ? '?show_deleted=1' : ''; ?>" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-x-lg"></i> Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                <button class="btn btn-sm btn-light" onclick="showExportModal()">
                    <i class="bi bi-download"></i><span class="d-none d-sm-inline"> Export</span>
                </button>
            </div>
        </div>
        <!-- Collapsible Search Panel -->
        <div class="search-filter-panel collapse mt-3" id="searchFilterPanel">
            <div class="search-box">
                <i class="bi bi-search search-icon" aria-hidden="true"></i>
                <input type="text" id="searchInput" class="form-control" 
                       placeholder="Search hardware by name, category, brand, model..." 
                       aria-label="Search hardware"
                       onkeyup="searchTable('searchInput', 'hardwareTable')">
                <button class="btn btn-sm btn-outline-secondary position-absolute end-0 me-2" 
                        type="button" onclick="clearHardwareSearch()" 
                        style="top: 50%; transform: translateY(-50%);"
                        aria-label="Clear search">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <?php if ($filter_category > 0 || !empty($filter_brand) || !empty($filter_model)): ?>
        <div class="filter-tags d-flex flex-wrap gap-2 align-items-center mt-3 pt-3" style="border-top: 1px solid rgba(255,255,255,0.2);">
            <small class="text-white-50 me-1"><i class="bi bi-funnel-fill"></i> Active filters:</small>
            <?php if ($filter_category > 0): 
                $cat_name = '';
                foreach ($categories as $cat) {
                    if ($cat['id'] == $filter_category) {
                        $cat_name = $cat['name'];
                        break;
                    }
                }
            ?>
            <span class="badge bg-light text-primary d-flex align-items-center gap-1">
                Category: <?php echo escapeOutput($cat_name); ?>
                <a href="?<?php echo http_build_query(array_filter(['filter_brand' => $filter_brand ?: null, 'filter_model' => $filter_model ?: null])); ?>" class="text-primary text-decoration-none ms-1" title="Remove filter">&times;</a>
            </span>
            <?php endif; ?>
            <?php if (!empty($filter_brand)): ?>
            <span class="badge bg-light text-primary d-flex align-items-center gap-1">
                Brand: <?php echo escapeOutput($filter_brand); ?>
                <a href="?<?php echo http_build_query(array_filter(['filter_category' => $filter_category ?: null, 'filter_model' => $filter_model ?: null])); ?>" class="text-primary text-decoration-none ms-1" title="Remove filter">&times;</a>
            </span>
            <?php endif; ?>
            <?php if (!empty($filter_model)): ?>
            <span class="badge bg-light text-primary d-flex align-items-center gap-1">
                Model: <?php echo escapeOutput($filter_model); ?>
                <a href="?<?php echo http_build_query(array_filter(['filter_category' => $filter_category ?: null, 'filter_brand' => $filter_brand ?: null])); ?>" class="text-primary text-decoration-none ms-1" title="Remove filter">&times;</a>
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <!-- Batch Operations Toolbar -->
    <div class="card-body bg-light border-bottom py-2 d-none" id="batchToolbar">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="text-muted"><strong id="selectedCount">0</strong> items selected</span>
            <div class="vr d-none d-sm-block"></div>
            <button type="button" class="btn btn-sm btn-warning" onclick="showBatchStatusModal()">
                <i class="bi bi-pencil-square"></i> Update Status
            </button>
            <button type="button" class="btn btn-sm btn-danger" onclick="batchDelete()">
                <i class="bi bi-trash"></i> Delete Selected
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSelection()">
                <i class="bi bi-x-lg"></i> Clear Selection
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-hci mb-0" id="hardwareTable">
                <thead>
                    <tr>
                        <th scope="col" style="width: 40px;">
                            <input type="checkbox" class="form-check-input" id="selectAllCheckbox" aria-label="Select all items" onclick="toggleSelectAll(this)">
                        </th>
                        <th scope="col">Name</th>
                        <th scope="col" class="d-none d-md-table-cell">Category</th>
                        <th scope="col" class="d-none d-lg-table-cell">Brand/Model</th>
                        <th scope="col" class="d-none d-lg-table-cell">Serial</th>
                        <th scope="col">Total</th>
                        <th>Available</th>
                        <th class="d-none d-md-table-cell">In Use</th>
                        <th class="d-none d-lg-table-cell">Damaged</th>
                        <th class="d-none d-lg-table-cell">Repair</th>
                        <th class="d-none d-lg-table-cell">Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($hardware)): ?>
                    <tr>
                        <td colspan="12" class="text-center text-muted py-4">No hardware found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($hardware as $item): ?>
                    <?php $isDeleted = !empty($item['deleted_at']); ?>
                    <tr class="<?php echo $isDeleted ? 'table-secondary opacity-75' : ''; ?>" data-item-id="<?php echo $item['id']; ?>">
                        <td class="text-center">
                            <?php if (!$isDeleted): ?>
                            <input type="checkbox" class="form-check-input item-checkbox" value="<?php echo $item['id']; ?>" aria-label="Select <?php echo escapeOutput($item['name']); ?>">
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo escapeOutput($item['name']); ?></strong>
                            <?php if ($isDeleted): ?>
                            <span class="badge bg-danger ms-1">Deleted</span>
                            <?php endif; ?>
                            <!-- Show category on mobile -->
                            <div class="d-md-none">
                                <small><span class="badge bg-primary"><?php echo escapeOutput($item['category_name']); ?></span></small>
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell"><span class="badge bg-primary"><?php echo escapeOutput($item['category_name']); ?></span></td>
                        <td class="d-none d-lg-table-cell">
                            <small>
                                <?php echo escapeOutput($item['brand'] ?: '-'); ?><br>
                                <?php echo escapeOutput($item['model'] ?: '-'); ?>
                            </small>
                        </td>
                        <td class="d-none d-lg-table-cell"><small class="text-muted"><?php echo escapeOutput($item['serial_number'] ?: '-'); ?></small></td>
                        <td><span class="badge bg-info"><?php echo $item['total_quantity']; ?></span></td>
                        <td><span class="badge bg-success"><?php echo $item['unused_quantity']; ?></span></td>
                        <td class="d-none d-md-table-cell"><span class="badge bg-warning"><?php echo $item['in_use_quantity']; ?></span></td>
                        <td class="d-none d-lg-table-cell"><span class="badge bg-danger"><?php echo $item['damaged_quantity']; ?></span></td>
                        <td class="d-none d-lg-table-cell"><span class="badge bg-secondary"><?php echo $item['repair_quantity']; ?></span></td>
                        <td class="d-none d-lg-table-cell"><small><?php echo escapeOutput($item['location'] ?: '-'); ?></small></td>
                        <td>
                            <?php if ($isDeleted): ?>
                                <?php if (isAdmin()): ?>
                                <a href="?restore=<?php echo $item['id']; ?>&<?php echo http_build_query($pagination_params); ?>" class="btn btn-sm btn-success" 
                                   onclick="return confirm('Are you sure you want to restore this hardware?')">
                                    <i class="bi bi-arrow-counterclockwise"></i><span class="d-none d-sm-inline"> Restore</span>
                                </a>
                                <a href="?permanent_delete=<?php echo $item['id']; ?>&<?php echo http_build_query($pagination_params); ?>" class="btn btn-sm btn-danger" 
                                   onclick="return confirmDelete('Are you sure you want to PERMANENTLY delete this hardware? This cannot be undone.', this)">
                                    <i class="bi bi-x-circle"></i><span class="d-none d-sm-inline"> Permanent</span>
                                </a>
                                <?php endif; ?>
                            <?php else: ?>
                            <button class="btn btn-sm btn-info" onclick='editHardware(<?php echo htmlspecialchars(json_encode($item), ENT_QUOTES, "UTF-8"); ?>)'>
                                <i class="bi bi-pencil"></i><span class="d-none d-sm-inline"> Edit</span>
                            </button>
                            <a href="?delete=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger" 
                               onclick="return confirmDelete('Are you sure you want to delete this hardware?', this)">
                                <i class="bi bi-trash"></i><span class="d-none d-sm-inline"> Delete</span>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="card-footer bg-light">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <small class="text-muted">
                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> items
            </small>
            <nav aria-label="Hardware pagination">
                <ul class="pagination pagination-sm mb-0">
                    <!-- First Page -->
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => 1])); ?>" aria-label="First">
                            <i class="bi bi-chevron-double-left"></i>
                        </a>
                    </li>
                    <!-- Previous Page -->
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $page - 1])); ?>" aria-label="Previous">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php
                    // Calculate page range to display
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif;
                    
                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $i])); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor;
                    
                    if ($end_page < $total_pages): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <!-- Next Page -->
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $page + 1])); ?>" aria-label="Next">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                    <!-- Last Page -->
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $total_pages])); ?>" aria-label="Last">
                            <i class="bi bi-chevron-double-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Add Hardware Modal -->
<div class="modal fade" id="addHardwareModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Hardware</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Hardware Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Category *</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo escapeOutput($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="type" class="form-label">Type</label>
                            <input type="text" class="form-control" id="type" name="type">
                        </div>
                        <div class="col-md-4">
                            <label for="brand" class="form-label">Brand</label>
                            <input type="text" class="form-control" id="brand" name="brand">
                        </div>
                        <div class="col-md-4">
                            <label for="model" class="form-label">Model</label>
                            <input type="text" class="form-control" id="model" name="model">
                        </div>
                        <div class="col-md-6">
                            <label for="serial_number" class="form-label">Serial Number</label>
                            <input type="text" class="form-control" id="serial_number" name="serial_number">
                        </div>
                        <div class="col-md-6">
                            <label for="location" class="form-label">Location</label>
                            <select class="form-select location-select" id="location" name="location">
                                <option value="">Select Location</option>
                                <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo escapeOutput($loc); ?>"><?php echo escapeOutput($loc); ?></option>
                                <?php endforeach; ?>
                                <option value="__add_new__">+ Add New Location...</option>
                            </select>
                        </div>
                        <div class="col-12"><hr></div>
                        <div class="col-md-3">
                            <label for="unused_quantity" class="form-label">Available</label>
                            <input type="number" class="form-control" id="unused_quantity" name="unused_quantity" value="0" min="0" required>
                        </div>
                        <div class="col-md-3">
                            <label for="in_use_quantity" class="form-label">In Use</label>
                            <input type="number" class="form-control" id="in_use_quantity" name="in_use_quantity" value="0" min="0" required>
                        </div>
                        <div class="col-md-3">
                            <label for="damaged_quantity" class="form-label">Damaged</label>
                            <input type="number" class="form-control" id="damaged_quantity" name="damaged_quantity" value="0" min="0" required>
                        </div>
                        <div class="col-md-3">
                            <label for="repair_quantity" class="form-label">In Repair</label>
                            <input type="number" class="form-control" id="repair_quantity" name="repair_quantity" value="0" min="0" required>
                        </div>
                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> <strong>Total Quantity:</strong> <span id="total_quantity">0</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Hardware</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Hardware Modal -->
<div class="modal fade" id="editHardwareModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Hardware</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_name" class="form-label">Hardware Name *</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_category_id" class="form-label">Category *</label>
                            <select class="form-select" id="edit_category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo escapeOutput($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_type" class="form-label">Type</label>
                            <input type="text" class="form-control" id="edit_type" name="type">
                        </div>
                        <div class="col-md-4">
                            <label for="edit_brand" class="form-label">Brand</label>
                            <input type="text" class="form-control" id="edit_brand" name="brand">
                        </div>
                        <div class="col-md-4">
                            <label for="edit_model" class="form-label">Model</label>
                            <input type="text" class="form-control" id="edit_model" name="model">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_serial_number" class="form-label">Serial Number</label>
                            <input type="text" class="form-control" id="edit_serial_number" name="serial_number">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_location" class="form-label">Location</label>
                            <select class="form-select location-select" id="edit_location" name="location">
                                <option value="">Select Location</option>
                                <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo escapeOutput($loc); ?>"><?php echo escapeOutput($loc); ?></option>
                                <?php endforeach; ?>
                                <option value="__add_new__">+ Add New Location...</option>
                            </select>
                        </div>
                        <div class="col-12"><hr></div>
                        <div class="col-md-3">
                            <label for="edit_unused_quantity" class="form-label">Available</label>
                            <input type="number" class="form-control" id="edit_unused_quantity" name="unused_quantity" value="0" min="0" required>
                        </div>
                        <div class="col-md-3">
                            <label for="edit_in_use_quantity" class="form-label">In Use</label>
                            <input type="number" class="form-control" id="edit_in_use_quantity" name="in_use_quantity" value="0" min="0" required>
                        </div>
                        <div class="col-md-3">
                            <label for="edit_damaged_quantity" class="form-label">Damaged</label>
                            <input type="number" class="form-control" id="edit_damaged_quantity" name="damaged_quantity" value="0" min="0" required>
                        </div>
                        <div class="col-md-3">
                            <label for="edit_repair_quantity" class="form-label">In Repair</label>
                            <input type="number" class="form-control" id="edit_repair_quantity" name="repair_quantity" value="0" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Hardware</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- CSV Import Modal -->
<div class="modal fade" id="importCSVModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-upload"></i> Import Hardware from CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="importCSVForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong><i class="bi bi-info-circle"></i> CSV Format:</strong>
                        <br>name, category, type, brand, model, serial_number, unused_quantity, in_use_quantity, damaged_quantity, repair_quantity, location
                        <br><small class="text-muted">First row should be the header. Use category name (e.g., CPU, RAM, SSD) instead of ID. The location column (11th) is optional if you select a default location below.</small>
                    </div>
                    <div class="mb-3">
                        <label for="csvFile" class="form-label">Select CSV File</label>
                        <input type="file" class="form-control" id="csvFile" name="csvFile" accept=".csv" required>
                    </div>
                    <div class="mb-3">
                        <label for="defaultLocation" class="form-label">Default Location (optional)</label>
                        <select class="form-select location-select" id="defaultLocation" name="defaultLocation">
                            <option value="">-- Use location from CSV --</option>
                            <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo escapeOutput($loc); ?>"><?php echo escapeOutput($loc); ?></option>
                            <?php endforeach; ?>
                            <option value="__add_new__">+ Add New Location...</option>
                        </select>
                        <small class="text-muted">If selected, this location will be used for all imported items (overrides CSV location column)</small>
                    </div>
                    <div id="importPreview" class="d-none">
                        <h6>Preview (First 5 rows):</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="previewTable">
                                <thead></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="importBtn">
                        <i class="bi bi-upload"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add New Location Modal -->
<div class="modal fade" id="addLocationModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-geo-alt-fill"></i> Add New Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="newLocationName" class="form-label">Location Name *</label>
                    <input type="text" class="form-control" id="newLocationName" placeholder="Enter location name" required>
                    <div class="invalid-feedback">Please enter a location name.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveNewLocationBtn">
                    <i class="bi bi-plus-circle"></i> Add Location
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Track which dropdown triggered the add location modal
var activeLocationDropdown = null;

// Handle location dropdown change to detect "Add New Location" selection
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.location-select').forEach(function(select) {
        select.addEventListener('change', function() {
            if (this.value === '__add_new__') {
                activeLocationDropdown = this;
                // Reset to empty/first option before showing modal
                this.value = '';
                // Show the add location modal
                var addLocationModal = new bootstrap.Modal(document.getElementById('addLocationModal'));
                addLocationModal.show();
            }
        });
    });
    
    // Handle save new location button
    document.getElementById('saveNewLocationBtn').addEventListener('click', function() {
        var newLocationInput = document.getElementById('newLocationName');
        var newLocation = newLocationInput.value.trim();
        
        if (!newLocation) {
            newLocationInput.classList.add('is-invalid');
            return;
        }
        
        newLocationInput.classList.remove('is-invalid');
        
        // Add the new location to all location dropdowns
        document.querySelectorAll('.location-select').forEach(function(select) {
            // Check if location already exists
            var exists = false;
            for (var i = 0; i < select.options.length; i++) {
                if (select.options[i].value === newLocation) {
                    exists = true;
                    break;
                }
            }
            
            if (!exists) {
                // Insert before the "Add New Location" option
                var addNewOption = select.querySelector('option[value="__add_new__"]');
                var newOption = document.createElement('option');
                newOption.value = newLocation;
                newOption.textContent = newLocation;
                select.insertBefore(newOption, addNewOption);
            }
        });
        
        // Select the new location in the dropdown that triggered the modal
        if (activeLocationDropdown) {
            activeLocationDropdown.value = newLocation;
        }
        
        // Clear input and close modal
        newLocationInput.value = '';
        var addLocationModal = bootstrap.Modal.getInstance(document.getElementById('addLocationModal'));
        addLocationModal.hide();
    });
    
    // Clear validation state when modal is hidden
    document.getElementById('addLocationModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('newLocationName').classList.remove('is-invalid');
        document.getElementById('newLocationName').value = '';
    });
});

// Hardware data for CSV export (in the correct import format)
var hardwareData = <?php echo json_encode($hardware); ?>;

// Export hardware to CSV in the correct import format
function exportHardwareToCSV() {
    const headers = ['name', 'category', 'type', 'brand', 'model', 'serial_number', 
                     'unused_quantity', 'in_use_quantity', 'damaged_quantity', 'repair_quantity', 'location'];
    
    let csv = [headers.join(',')];
    
    hardwareData.forEach(function(item) {
        let row = [
            '"' + (item.name || '').replace(/"/g, '""') + '"',
            '"' + (item.category_name || '').replace(/"/g, '""') + '"',
            '"' + (item.type || '').replace(/"/g, '""') + '"',
            '"' + (item.brand || '').replace(/"/g, '""') + '"',
            '"' + (item.model || '').replace(/"/g, '""') + '"',
            '"' + (item.serial_number || '').replace(/"/g, '""') + '"',
            item.unused_quantity || 0,
            item.in_use_quantity || 0,
            item.damaged_quantity || 0,
            item.repair_quantity || 0,
            '"' + (item.location || '').replace(/"/g, '""') + '"'
        ];
        csv.push(row.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'hardware_inventory.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// Edit hardware function - opens modal directly
function editHardware(item) {
    document.getElementById('edit_id').value = item.id;
    document.getElementById('edit_name').value = item.name;
    document.getElementById('edit_category_id').value = item.category_id;
    document.getElementById('edit_type').value = item.type || '';
    document.getElementById('edit_brand').value = item.brand || '';
    document.getElementById('edit_model').value = item.model || '';
    document.getElementById('edit_serial_number').value = item.serial_number || '';
    document.getElementById('edit_location').value = item.location || '';
    document.getElementById('edit_unused_quantity').value = item.unused_quantity;
    document.getElementById('edit_in_use_quantity').value = item.in_use_quantity;
    document.getElementById('edit_damaged_quantity').value = item.damaged_quantity;
    document.getElementById('edit_repair_quantity').value = item.repair_quantity;
    
    const editModal = new bootstrap.Modal(document.getElementById('editHardwareModal'));
    editModal.show();
}

// Form validation with confirmation for edit form
(function() {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            form.classList.add('was-validated');
            
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                return;
            }
            
            // Check if this is the edit hardware form (has edit action)
            var actionInput = form.querySelector('input[name="action"]');
            if (actionInput && actionInput.value === 'edit') {
                event.preventDefault();
                var itemName = document.getElementById('edit_name').value;
                showConfirmation(
                    'Are you sure you want to update "' + itemName + '"?',
                    'Confirm Update',
                    'Update',
                    'warning'
                ).then(function(confirmed) {
                    if (confirmed) {
                        showLoading('Updating hardware...');
                        form.submit();
                    }
                });
                return;
            }
            
            // Show loading for add action
            if (actionInput && actionInput.value === 'add') {
                showLoading('Adding hardware...');
            }
        }, false);
    });
})();

// Toggle Search Filter Panel for Hardware
function toggleHardwareSearch() {
    var panel = document.getElementById('searchFilterPanel');
    var btn = document.getElementById('toggleSearchBtn');
    var isExpanded = panel.classList.contains('show');
    
    if (isExpanded) {
        panel.classList.remove('show');
        btn.setAttribute('aria-expanded', 'false');
        btn.innerHTML = '<i class="bi bi-search" aria-hidden="true"></i><span class="d-none d-sm-inline"> Search</span>';
        // Clear search when hiding
        clearHardwareSearch();
    } else {
        panel.classList.add('show');
        btn.setAttribute('aria-expanded', 'true');
        btn.innerHTML = '<i class="bi bi-x-lg" aria-hidden="true"></i><span class="d-none d-sm-inline"> Close</span>';
        // Focus on search input
        setTimeout(function() {
            document.getElementById('searchInput').focus();
        }, 100);
    }
}

// Clear Search Input for Hardware
function clearHardwareSearch() {
    var input = document.getElementById('searchInput');
    if (input) {
        input.value = '';
        searchTable('searchInput', 'hardwareTable');
    }
}

// Keyboard shortcut for search (/ key) - only add if not already added
if (!window.hardwarePageKeyboardHandlerAdded) {
    window.hardwarePageKeyboardHandlerAdded = true;
    document.addEventListener('keydown', function(e) {
        if (e.key === '/' && !e.target.matches('input, textarea, select')) {
            e.preventDefault();
            var panel = document.getElementById('searchFilterPanel');
            if (panel && !panel.classList.contains('show')) {
                toggleHardwareSearch();
            } else if (panel) {
                document.getElementById('searchInput').focus();
            }
        }
        // Escape to close search
        if (e.key === 'Escape') {
            var panel = document.getElementById('searchFilterPanel');
            if (panel && panel.classList.contains('show')) {
                toggleHardwareSearch();
            }
        }
    });
}

// ============ Batch Operations ============
function toggleSelectAll(checkbox) {
    var checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(function(cb) {
        cb.checked = checkbox.checked;
    });
    updateBatchToolbar();
}

function updateBatchToolbar() {
    var selected = document.querySelectorAll('.item-checkbox:checked');
    var toolbar = document.getElementById('batchToolbar');
    var countEl = document.getElementById('selectedCount');
    
    if (selected.length > 0) {
        toolbar.classList.remove('d-none');
        countEl.textContent = selected.length;
    } else {
        toolbar.classList.add('d-none');
    }
    
    // Update select all checkbox state
    var allCheckboxes = document.querySelectorAll('.item-checkbox');
    var selectAll = document.getElementById('selectAllCheckbox');
    if (allCheckboxes.length > 0 && selected.length === allCheckboxes.length) {
        selectAll.checked = true;
        selectAll.indeterminate = false;
    } else if (selected.length > 0) {
        selectAll.checked = false;
        selectAll.indeterminate = true;
    } else {
        selectAll.checked = false;
        selectAll.indeterminate = false;
    }
}

function clearSelection() {
    document.querySelectorAll('.item-checkbox').forEach(function(cb) {
        cb.checked = false;
    });
    document.getElementById('selectAllCheckbox').checked = false;
    updateBatchToolbar();
}

function getSelectedIds() {
    var selected = document.querySelectorAll('.item-checkbox:checked');
    return Array.from(selected).map(function(cb) { return cb.value; });
}

function showBatchStatusModal() {
    var ids = getSelectedIds();
    if (ids.length === 0) {
        alert('Please select at least one item.');
        return;
    }
    document.getElementById('batch_ids').value = JSON.stringify(ids);
    var modal = new bootstrap.Modal(document.getElementById('batchStatusModal'));
    modal.show();
}

function batchDelete() {
    var ids = getSelectedIds();
    if (ids.length === 0) {
        alert('Please select at least one item.');
        return;
    }
    
    if (confirm('Are you sure you want to delete ' + ids.length + ' selected item(s)?')) {
        // Create and submit form
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        var actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'batch_action';
        actionInput.value = 'delete';
        form.appendChild(actionInput);
        
        ids.forEach(function(id) {
            var idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'ids[]';
            idInput.value = id;
            form.appendChild(idInput);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Add event listener for individual checkboxes
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.item-checkbox').forEach(function(cb) {
        cb.addEventListener('change', updateBatchToolbar);
    });
});

// ============ Export Modal ============
function showExportModal() {
    var modal = new bootstrap.Modal(document.getElementById('exportModal'));
    modal.show();
}

function exportFilteredCSV() {
    var exportCategory = document.getElementById('export_category').value;
    var headers = ['name', 'category', 'type', 'brand', 'model', 'serial_number', 
                   'unused_quantity', 'in_use_quantity', 'damaged_quantity', 'repair_quantity', 'location'];
    
    var csv = [headers.join(',')];
    
    hardwareData.forEach(function(item) {
        // Filter by category if selected
        if (exportCategory && item.category_name !== exportCategory) {
            return;
        }
        
        var row = [
            '"' + (item.name || '').replace(/"/g, '""') + '"',
            '"' + (item.category_name || '').replace(/"/g, '""') + '"',
            '"' + (item.type || '').replace(/"/g, '""') + '"',
            '"' + (item.brand || '').replace(/"/g, '""') + '"',
            '"' + (item.model || '').replace(/"/g, '""') + '"',
            '"' + (item.serial_number || '').replace(/"/g, '""') + '"',
            item.unused_quantity || 0,
            item.in_use_quantity || 0,
            item.damaged_quantity || 0,
            item.repair_quantity || 0,
            '"' + (item.location || '').replace(/"/g, '""') + '"'
        ];
        csv.push(row.join(','));
    });
    
    var csvContent = csv.join('\n');
    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    var url = window.URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    var filename = 'hardware_inventory';
    if (exportCategory) {
        filename += '_' + exportCategory.toLowerCase().replace(/[^a-z0-9]/g, '_');
    }
    filename += '_' + new Date().toISOString().split('T')[0] + '.csv';
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
    
    // Close modal
    var modal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
    modal.hide();
}
</script>

<!-- Batch Status Update Modal -->
<div class="modal fade" id="batchStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Batch Status Update</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="batch_action" value="update_status">
                    <input type="hidden" name="batch_ids" id="batch_ids">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> This will update the selected items' status quantities.
                    </div>
                    <div class="mb-3">
                        <label for="status_type" class="form-label">Status Type *</label>
                        <select class="form-select" id="status_type" name="status_type" required>
                            <option value="">Select Status</option>
                            <option value="unused_quantity">Available</option>
                            <option value="in_use_quantity">In Use</option>
                            <option value="damaged_quantity">Damaged</option>
                            <option value="repair_quantity">In Repair</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quantity_change" class="form-label">Quantity Change *</label>
                        <input type="number" class="form-control" id="quantity_change" name="quantity_change" required>
                        <small class="text-muted">Use positive numbers to add, negative to subtract (e.g., -1 to decrease by 1)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" onclick="return submitBatchStatus()">
                        <i class="bi bi-check-lg"></i> Update Selected
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-download"></i> Export Hardware to CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="export_category" class="form-label">Filter by Category (optional)</label>
                    <select class="form-select" id="export_category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo escapeOutput($cat['name']); ?>"><?php echo escapeOutput($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Select a category to export only items from that category</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="exportFilteredCSV()">
                    <i class="bi bi-download"></i> Export CSV
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function submitBatchStatus() {
    var batchIds = document.getElementById('batch_ids').value;
    var ids = JSON.parse(batchIds);
    
    // Add ids as hidden inputs
    var form = document.querySelector('#batchStatusModal form');
    ids.forEach(function(id) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    return true;
}
</script>

<?php include '../includes/footer.php'; ?>
