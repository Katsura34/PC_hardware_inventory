<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/security.php';

// Require login
requireLogin();

$pageTitle = 'Hardware Management - PC Hardware Inventory';
$conn = getDBConnection();

// Handle delete
if (isset($_GET['delete']) && validateInt($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Get hardware details before deleting for history log
    $old_stmt = $conn->prepare("SELECT name, total_quantity, unused_quantity, in_use_quantity, damaged_quantity, repair_quantity FROM hardware WHERE id = ?");
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
    
    // Delete hardware
    $stmt = $conn->prepare("DELETE FROM hardware WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        redirectWithMessage(BASE_PATH . 'pages/hardware.php', 'Hardware deleted successfully.', 'success');
    } else {
        redirectWithMessage(BASE_PATH . 'pages/hardware.php', 'Failed to delete hardware.', 'error');
    }
    $stmt->close();
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

// Build query with filters
$query = "SELECT h.*, c.name as category_name FROM hardware h 
          LEFT JOIN categories c ON h.category_id = c.id 
          WHERE 1=1";

$params = [];
$types = "";

if ($filter_category > 0) {
    $query .= " AND h.category_id = ?";
    $params[] = $filter_category;
    $types .= "i";
}

if (!empty($filter_brand)) {
    $query .= " AND h.brand LIKE ?";
    $params[] = "%" . $filter_brand . "%";
    $types .= "s";
}

if (!empty($filter_model)) {
    $query .= " AND h.model LIKE ?";
    $params[] = "%" . $filter_model . "%";
    $types .= "s";
}

$query .= " ORDER BY h.date_added DESC";

// Get all hardware with filters
$hardware = [];
if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}
while ($row = $result->fetch_assoc()) {
    $hardware[] = $row;
}

// Get all categories for dropdown
$categories = [];
$result = $conn->query("SELECT * FROM categories ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Get distinct brands for filter dropdown
$brands = [];
$result = $conn->query("SELECT DISTINCT brand FROM hardware WHERE brand IS NOT NULL AND brand != '' ORDER BY brand");
while ($row = $result->fetch_assoc()) {
    if (!empty($row['brand'])) {
        $brands[] = $row['brand'];
    }
}

// Get distinct models for filter dropdown
$models = [];
$result = $conn->query("SELECT DISTINCT model FROM hardware WHERE model IS NOT NULL AND model != '' ORDER BY model");
while ($row = $result->fetch_assoc()) {
    if (!empty($row['model'])) {
        $models[] = $row['model'];
    }
}

// Get distinct locations for dropdown
$locations = [];
$result = $conn->query("SELECT DISTINCT location FROM hardware WHERE location IS NOT NULL AND location != '' ORDER BY location");
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
    <div class="card-header">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
            <h5 class="mb-2 mb-md-0"><i class="bi bi-table"></i> All Hardware</h5>
            <div class="d-flex gap-2 align-items-center">
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search..." style="min-width: 150px;"
                       onkeyup="searchTable('searchInput', 'hardwareTable')">
                <!-- Filter Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-sm <?php echo ($filter_category > 0 || !empty($filter_brand) || !empty($filter_model)) ? 'btn-primary' : 'btn-outline-primary'; ?>" type="button" id="filterDropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                        <i class="bi bi-funnel<?php echo ($filter_category > 0 || !empty($filter_brand) || !empty($filter_model)) ? '-fill' : ''; ?>"></i>
                        <span class="d-none d-sm-inline"> Filters</span>
                        <?php if ($filter_category > 0 || !empty($filter_brand) || !empty($filter_model)): ?>
                        <span class="badge bg-white text-primary ms-1"><?php echo count(array_filter([$filter_category > 0, !empty($filter_brand), !empty($filter_model)])); ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end filter-dropdown p-3 shadow-lg" aria-labelledby="filterDropdown" style="min-width: 300px;">
                        <form method="GET" id="filterForm">
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
                                <a href="<?php echo BASE_PATH; ?>pages/hardware.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-x-lg"></i> Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                <button class="btn btn-sm btn-success" onclick="exportHardwareToCSV()">
                    <i class="bi bi-download"></i><span class="d-none d-sm-inline"> Export</span>
                </button>
            </div>
        </div>
        <?php if ($filter_category > 0 || !empty($filter_brand) || !empty($filter_model)): ?>
        <div class="filter-tags d-flex flex-wrap gap-2 align-items-center">
            <small class="text-muted me-1"><i class="bi bi-funnel-fill"></i> Active filters:</small>
            <?php if ($filter_category > 0): 
                $cat_name = '';
                foreach ($categories as $cat) {
                    if ($cat['id'] == $filter_category) {
                        $cat_name = $cat['name'];
                        break;
                    }
                }
            ?>
            <span class="badge bg-primary d-flex align-items-center gap-1">
                Category: <?php echo escapeOutput($cat_name); ?>
                <a href="?<?php echo http_build_query(array_filter(['filter_brand' => $filter_brand ?: null, 'filter_model' => $filter_model ?: null])); ?>" class="text-white text-decoration-none ms-1" title="Remove filter">&times;</a>
            </span>
            <?php endif; ?>
            <?php if (!empty($filter_brand)): ?>
            <span class="badge bg-primary d-flex align-items-center gap-1">
                Brand: <?php echo escapeOutput($filter_brand); ?>
                <a href="?<?php echo http_build_query(array_filter(['filter_category' => $filter_category ?: null, 'filter_model' => $filter_model ?: null])); ?>" class="text-white text-decoration-none ms-1" title="Remove filter">&times;</a>
            </span>
            <?php endif; ?>
            <?php if (!empty($filter_model)): ?>
            <span class="badge bg-primary d-flex align-items-center gap-1">
                Model: <?php echo escapeOutput($filter_model); ?>
                <a href="?<?php echo http_build_query(array_filter(['filter_category' => $filter_category ?: null, 'filter_brand' => $filter_brand ?: null])); ?>" class="text-white text-decoration-none ms-1" title="Remove filter">&times;</a>
            </span>
            <?php endif; ?>
            <small class="text-muted ms-2">(<?php echo count($hardware); ?> items)</small>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="hardwareTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th class="d-none d-md-table-cell">Category</th>
                        <th class="d-none d-lg-table-cell">Brand/Model</th>
                        <th class="d-none d-lg-table-cell">Serial</th>
                        <th>Total</th>
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
                        <td colspan="11" class="text-center text-muted py-4">No hardware found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($hardware as $item): ?>
                    <tr>
                        <td>
                            <strong><?php echo escapeOutput($item['name']); ?></strong>
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
                            <button class="btn btn-sm btn-info" onclick='editHardware(<?php echo htmlspecialchars(json_encode($item), ENT_QUOTES, "UTF-8"); ?>)'>
                                <i class="bi bi-pencil"></i><span class="d-none d-sm-inline"> Edit</span>
                            </button>
                            <a href="?delete=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger" 
                               onclick="return confirmDelete('Are you sure you want to delete this hardware?', this)">
                                <i class="bi bi-trash"></i><span class="d-none d-sm-inline"> Delete</span>
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
</script>

<?php include '../includes/footer.php'; ?>
