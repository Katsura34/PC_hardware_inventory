<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/security.php';

// Require login
requireLogin();

$pageTitle = 'Dashboard - PC Hardware Inventory';
$conn = getDBConnection();

// Get statistics
$stats = [
    'total_hardware' => 0,
    'total_quantity' => 0,
    'in_use' => 0,
    'available' => 0,
    'damaged' => 0,
    'repair' => 0
];

// Total hardware items
$result = $conn->query("SELECT COUNT(*) as count FROM hardware");
if ($row = $result->fetch_assoc()) {
    $stats['total_hardware'] = $row['count'];
}

// Total quantities
$result = $conn->query("SELECT SUM(total_quantity) as total, SUM(in_use_quantity) as in_use, 
                        SUM(unused_quantity) as available, SUM(damaged_quantity) as damaged, 
                        SUM(repair_quantity) as repair FROM hardware");
if ($row = $result->fetch_assoc()) {
    $stats['total_quantity'] = $row['total'] ?? 0;
    $stats['in_use'] = $row['in_use'] ?? 0;
    $stats['available'] = $row['available'] ?? 0;
    $stats['damaged'] = $row['damaged'] ?? 0;
    $stats['repair'] = $row['repair'] ?? 0;
}

// Get recent hardware
$recentHardware = [];
$result = $conn->query("SELECT h.*, c.name as category_name FROM hardware h 
                       LEFT JOIN categories c ON h.category_id = c.id 
                       ORDER BY h.date_added DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $recentHardware[] = $row;
}

// Get low stock items (unused quantity < 2)
$lowStock = [];
$result = $conn->query("SELECT h.*, c.name as category_name FROM hardware h 
                       LEFT JOIN categories c ON h.category_id = c.id 
                       WHERE h.unused_quantity < 2 AND h.total_quantity > 0
                       ORDER BY h.unused_quantity ASC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $lowStock[] = $row;
}

// Get categories summary
$categories = [];
$result = $conn->query("SELECT c.name, COUNT(h.id) as count, SUM(h.total_quantity) as total 
                       FROM categories c 
                       LEFT JOIN hardware h ON c.id = h.category_id 
                       GROUP BY c.id, c.name 
                       ORDER BY count DESC");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="aclc-branding">
            <h6><i class="bi bi-building"></i> ACLC COLLEGE OF ORMOC - PC HARDWARE INVENTORY SYSTEM</h6>
        </div>
        <h1 class="text-gradient mb-1">
            <i class="bi bi-speedometer2"></i> Dashboard
        </h1>
        <p class="text-muted">Welcome back, <?php echo escapeOutput($_SESSION['full_name']); ?>!</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card border-primary">
            <div class="card-body text-center">
                <i class="bi bi-boxes stat-icon text-primary"></i>
                <div class="stat-value text-primary"><?php echo $stats['total_hardware']; ?></div>
                <div class="stat-label">Total Items</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card border-info">
            <div class="card-body text-center">
                <i class="bi bi-layers stat-icon text-info"></i>
                <div class="stat-value text-info"><?php echo $stats['total_quantity']; ?></div>
                <div class="stat-label">Total Quantity</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card border-success">
            <div class="card-body text-center">
                <i class="bi bi-check-circle stat-icon text-success"></i>
                <div class="stat-value text-success"><?php echo $stats['available']; ?></div>
                <div class="stat-label">Available</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card border-warning">
            <div class="card-body text-center">
                <i class="bi bi-play-circle stat-icon text-warning"></i>
                <div class="stat-value text-warning"><?php echo $stats['in_use']; ?></div>
                <div class="stat-label">In Use</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card border-danger">
            <div class="card-body text-center">
                <i class="bi bi-exclamation-triangle stat-icon text-danger"></i>
                <div class="stat-value text-danger"><?php echo $stats['damaged']; ?></div>
                <div class="stat-label">Damaged</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card border-secondary">
            <div class="card-body text-center">
                <i class="bi bi-tools stat-icon text-secondary"></i>
                <div class="stat-value text-secondary"><?php echo $stats['repair']; ?></div>
                <div class="stat-label">In Repair</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Recent Hardware -->
    <div class="col-lg-6">
        <div class="card table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Hardware</h5>
                <a href="pages/hardware.php" class="btn btn-sm btn-light">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Total Qty</th>
                                <th>Available</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentHardware)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No hardware found</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($recentHardware as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo escapeOutput($item['name']); ?></strong>
                                    <?php if (!empty($item['brand'])): ?>
                                    <br><small class="text-muted"><?php echo escapeOutput($item['brand']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-primary"><?php echo escapeOutput($item['category_name']); ?></span></td>
                                <td><?php echo $item['total_quantity']; ?></td>
                                <td><span class="badge bg-success"><?php echo $item['unused_quantity']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Low Stock Items -->
    <div class="col-lg-6">
        <div class="card table-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-exclamation-circle"></i> Low Stock Alert</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Available</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lowStock)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-success py-4">
                                    <i class="bi bi-check-circle"></i> All items in stock!
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($lowStock as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo escapeOutput($item['name']); ?></strong>
                                    <?php if (!empty($item['brand'])): ?>
                                    <br><small class="text-muted"><?php echo escapeOutput($item['brand']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-primary"><?php echo escapeOutput($item['category_name']); ?></span></td>
                                <td><span class="badge bg-warning"><?php echo $item['unused_quantity']; ?></span></td>
                                <td>
                                    <?php if ($item['unused_quantity'] == 0): ?>
                                    <span class="badge bg-danger">Out of Stock</span>
                                    <?php else: ?>
                                    <span class="badge bg-warning">Low Stock</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Categories Summary -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card table-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-grid"></i> Categories Summary</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($categories as $category): ?>
                    <div class="col-md-3">
                        <div class="card border-0 bg-light">
                            <div class="card-body text-center">
                                <h6 class="text-primary mb-2"><?php echo escapeOutput($category['name']); ?></h6>
                                <div class="d-flex justify-content-around">
                                    <div>
                                        <small class="text-muted">Items</small>
                                        <div class="fw-bold"><?php echo $category['count'] ?? 0; ?></div>
                                    </div>
                                    <div>
                                        <small class="text-muted">Total Qty</small>
                                        <div class="fw-bold"><?php echo $category['total'] ?? 0; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
