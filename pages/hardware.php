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
    
    // Delete hardware
    $stmt = $conn->prepare("DELETE FROM hardware WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        redirectWithMessage('/PC_hardware_inventory/pages/hardware.php', 'Hardware deleted successfully.', 'success');
    } else {
        redirectWithMessage('/PC_hardware_inventory/pages/hardware.php', 'Failed to delete hardware.', 'error');
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
            
            // Log to history
            $user_id = $_SESSION['user_id'];
            $log_stmt = $conn->prepare("INSERT INTO inventory_history (hardware_id, user_id, action_type, quantity_change, 
                                       old_unused, old_in_use, old_damaged, old_repair, 
                                       new_unused, new_in_use, new_damaged, new_repair) 
                                       VALUES (?, ?, 'Added', ?, 0, 0, 0, 0, ?, ?, ?, ?)");
            $log_stmt->bind_param("iiiiiiii", $hardware_id, $user_id, $total_quantity, 
                                 $unused_quantity, $in_use_quantity, $damaged_quantity, $repair_quantity);
            $log_stmt->execute();
            $log_stmt->close();
            
            redirectWithMessage('/PC_hardware_inventory/pages/hardware.php', 'Hardware added successfully.', 'success');
        } else {
            redirectWithMessage('/PC_hardware_inventory/pages/hardware.php', 'Failed to add hardware.', 'error');
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
        $stmt->bind_param("sissssiiiiiisi", $name, $category_id, $type, $brand, $model, $serial_number, 
                         $total_quantity, $unused_quantity, $in_use_quantity, $damaged_quantity, $repair_quantity, $location, $id);
        
        if ($stmt->execute()) {
            // Log to history
            $user_id = $_SESSION['user_id'];
            $quantity_change = $total_quantity - ($old_data['unused_quantity'] + $old_data['in_use_quantity'] + 
                                                   $old_data['damaged_quantity'] + $old_data['repair_quantity']);
            
            $log_stmt = $conn->prepare("INSERT INTO inventory_history (hardware_id, user_id, action_type, quantity_change, 
                                       old_unused, old_in_use, old_damaged, old_repair, 
                                       new_unused, new_in_use, new_damaged, new_repair) 
                                       VALUES (?, ?, 'Updated', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $log_stmt->bind_param("iiiiiiiiiiii", $id, $user_id, $quantity_change, 
                                 $old_data['unused_quantity'], $old_data['in_use_quantity'], 
                                 $old_data['damaged_quantity'], $old_data['repair_quantity'],
                                 $unused_quantity, $in_use_quantity, $damaged_quantity, $repair_quantity);
            $log_stmt->execute();
            $log_stmt->close();
            
            redirectWithMessage('/PC_hardware_inventory/pages/hardware.php', 'Hardware updated successfully.', 'success');
        } else {
            redirectWithMessage('/PC_hardware_inventory/pages/hardware.php', 'Failed to update hardware.', 'error');
        }
        $stmt->close();
    }
}

// Get all hardware
$hardware = [];
$result = $conn->query("SELECT h.*, c.name as category_name FROM hardware h 
                       LEFT JOIN categories c ON h.category_id = c.id 
                       ORDER BY h.date_added DESC");
while ($row = $result->fetch_assoc()) {
    $hardware[] = $row;
}

// Get all categories for dropdown
$categories = [];
$result = $conn->query("SELECT * FROM categories ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="text-gradient mb-1">
                    <i class="bi bi-cpu"></i> Hardware Management
                </h1>
                <p class="text-muted">Manage your hardware inventory</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHardwareModal">
                <i class="bi bi-plus-circle"></i> Add Hardware
            </button>
        </div>
    </div>
</div>

<!-- Hardware Table -->
<div class="card table-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-table"></i> All Hardware</h5>
        <div class="d-flex gap-2">
            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search..." 
                   onkeyup="searchTable('searchInput', 'hardwareTable')">
            <button class="btn btn-sm btn-success" onclick="exportTableToCSV('hardwareTable', 'hardware_inventory.csv')">
                <i class="bi bi-download"></i> Export
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="hardwareTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Brand/Model</th>
                        <th>Serial</th>
                        <th>Total</th>
                        <th>Available</th>
                        <th>In Use</th>
                        <th>Damaged</th>
                        <th>Repair</th>
                        <th>Location</th>
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
                        <td><strong><?php echo escapeOutput($item['name']); ?></strong></td>
                        <td><span class="badge bg-primary"><?php echo escapeOutput($item['category_name']); ?></span></td>
                        <td>
                            <small>
                                <?php echo escapeOutput($item['brand'] ?: '-'); ?><br>
                                <?php echo escapeOutput($item['model'] ?: '-'); ?>
                            </small>
                        </td>
                        <td><small class="text-muted"><?php echo escapeOutput($item['serial_number'] ?: '-'); ?></small></td>
                        <td><span class="badge bg-info"><?php echo $item['total_quantity']; ?></span></td>
                        <td><span class="badge bg-success"><?php echo $item['unused_quantity']; ?></span></td>
                        <td><span class="badge bg-warning"><?php echo $item['in_use_quantity']; ?></span></td>
                        <td><span class="badge bg-danger"><?php echo $item['damaged_quantity']; ?></span></td>
                        <td><span class="badge bg-secondary"><?php echo $item['repair_quantity']; ?></span></td>
                        <td><small><?php echo escapeOutput($item['location'] ?: '-'); ?></small></td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick='editHardware(<?php echo json_encode($item); ?>)'>
                                <i class="bi bi-pencil"></i>
                            </button>
                            <a href="?delete=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger" 
                               onclick="return confirmDelete('Are you sure you want to delete this hardware?')">
                                <i class="bi bi-trash"></i>
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
                            <input type="text" class="form-control" id="location" name="location">
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
                            <input type="text" class="form-control" id="edit_location" name="location">
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

<script>
// Edit hardware function
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

// Form validation
(function() {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php include '../includes/footer.php'; ?>
