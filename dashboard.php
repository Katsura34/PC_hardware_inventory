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
    'repair' => 0,
    'out_of_stock' => 0
];

// Total hardware items (exclude soft-deleted)
$result = $conn->query("SELECT COUNT(*) as count FROM hardware WHERE deleted_at IS NULL");
if ($row = $result->fetch_assoc()) {
    $stats['total_hardware'] = $row['count'];
}

// Total quantities (exclude soft-deleted)
$result = $conn->query("SELECT SUM(total_quantity) as total, SUM(in_use_quantity) as in_use, 
                        SUM(unused_quantity) as available, SUM(damaged_quantity) as damaged, 
                        SUM(repair_quantity) as repair FROM hardware WHERE deleted_at IS NULL");
if ($row = $result->fetch_assoc()) {
    $stats['total_quantity'] = $row['total'] ?? 0;
    $stats['in_use'] = $row['in_use'] ?? 0;
    $stats['available'] = $row['available'] ?? 0;
    $stats['damaged'] = $row['damaged'] ?? 0;
    $stats['repair'] = $row['repair'] ?? 0;
}

// Get recent hardware (exclude soft-deleted)
$recentHardware = [];
$result = $conn->query("SELECT h.*, c.name as category_name FROM hardware h 
                       LEFT JOIN categories c ON h.category_id = c.id 
                       WHERE h.deleted_at IS NULL
                       ORDER BY h.date_added DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $recentHardware[] = $row;
}

// Get low stock items (exclude soft-deleted, unused quantity < 2)
$lowStock = [];
$result = $conn->query("SELECT h.*, c.name as category_name FROM hardware h 
                       LEFT JOIN categories c ON h.category_id = c.id 
                       WHERE h.unused_quantity < 2 AND h.total_quantity > 0 AND h.deleted_at IS NULL
                       ORDER BY h.unused_quantity ASC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $lowStock[] = $row;
}

// Get out of stock count
$result = $conn->query("SELECT COUNT(*) as count FROM hardware WHERE unused_quantity = 0 AND deleted_at IS NULL");
if ($row = $result->fetch_assoc()) {
    $stats['out_of_stock'] = $row['count'];
}

// Get out of stock items (unused quantity = 0, exclude soft-deleted)
$outOfStock = [];
$result = $conn->query("SELECT h.*, c.name as category_name FROM hardware h 
                       LEFT JOIN categories c ON h.category_id = c.id 
                       WHERE h.unused_quantity = 0 AND h.deleted_at IS NULL
                       ORDER BY h.name ASC LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $outOfStock[] = $row;
}

// Get categories summary (exclude soft-deleted)
$categories = [];
$result = $conn->query("SELECT c.name, COUNT(h.id) as count, SUM(h.total_quantity) as total 
                       FROM categories c 
                       LEFT JOIN hardware h ON c.id = h.category_id AND h.deleted_at IS NULL
                       GROUP BY c.id, c.name 
                       ORDER BY count DESC");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <!-- Welcome Banner - HCI Principle: User-centered Design -->
        <div class="welcome-banner mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                <div>
                    <h2 class="mb-1">
                        <i class="bi bi-hand-wave me-2" aria-hidden="true"></i>
                        Welcome back, <?php echo escapeOutput($_SESSION['full_name']); ?>!
                    </h2>
                    <p class="mb-0 opacity-75">
                        <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>
                        <?php echo date('l, F j, Y'); ?>
                    </p>
                </div>
                <div class="mt-3 mt-md-0">
                    <a href="<?php echo BASE_PATH; ?>pages/hardware.php" class="btn btn-light">
                        <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>
                        Add Hardware
                    </a>
                </div>
            </div>
        </div>
        

        
        <h1 class="text-gradient mb-1">
            <i class="bi bi-speedometer2" aria-hidden="true"></i> Dashboard
        </h1>
        <p class="text-muted">Overview of your hardware inventory at a glance</p>
    </div>
</div>

<!-- Statistics Cards - HCI Principle: Visibility & Mapping -->
<div class="row g-3 mb-4" role="region" aria-label="Inventory Statistics">
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card border-primary" role="article" aria-label="Total Items">
            <div class="card-body text-center">
                <i class="bi bi-boxes stat-icon text-primary" aria-hidden="true"></i>
                <div class="stat-value text-primary"><?php echo $stats['total_hardware']; ?></div>
                <div class="stat-label">Total Items</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card border-info" role="article" aria-label="Total Quantity">
            <div class="card-body text-center">
                <i class="bi bi-layers stat-icon text-info" aria-hidden="true"></i>
                <div class="stat-value text-info"><?php echo $stats['total_quantity']; ?></div>
                <div class="stat-label">Total Quantity</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card border-success" role="article" aria-label="Available Items">
            <div class="card-body text-center">
                <i class="bi bi-check-circle stat-icon text-success" aria-hidden="true"></i>
                <div class="stat-value text-success"><?php echo $stats['available']; ?></div>
                <div class="stat-label">Available</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card border-warning" role="article" aria-label="Items In Use">
            <div class="card-body text-center">
                <i class="bi bi-play-circle stat-icon text-warning" aria-hidden="true"></i>
                <div class="stat-value text-warning"><?php echo $stats['in_use']; ?></div>
                <div class="stat-label">In Use</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card border-danger" role="article" aria-label="Damaged Items">
            <div class="card-body text-center">
                <i class="bi bi-exclamation-triangle stat-icon text-danger" aria-hidden="true"></i>
                <div class="stat-value text-danger"><?php echo $stats['damaged']; ?></div>
                <div class="stat-label">Damaged</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card border-secondary" role="article" aria-label="Items In Repair">
            <div class="card-body text-center">
                <i class="bi bi-tools stat-icon text-secondary" aria-hidden="true"></i>
                <div class="stat-value text-secondary"><?php echo $stats['repair']; ?></div>
                <div class="stat-label">In Repair</div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section - HCI Principle: Visibility & Mapping -->
<div class="row g-3 mb-4" role="region" aria-label="Inventory Charts">
    <!-- Inventory Status Distribution Chart -->
    <div class="col-lg-6">
        <div class="card table-card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Inventory Status Distribution</h5>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <div class="chart-container" style="position: relative; width: 100%; max-width: 350px;">
                    <canvas id="statusChart" aria-label="Inventory status distribution chart" role="img"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Category Distribution Chart -->
    <div class="col-lg-6">
        <div class="card table-card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Items by Category</h5>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <div class="chart-container" style="position: relative; width: 100%; height: 280px;">
                    <canvas id="categoryChart" aria-label="Items by category chart" role="img"></canvas>
                </div>
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
                <a href="<?php echo BASE_PATH; ?>pages/hardware.php" class="btn btn-sm btn-light">View All</a>
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

<!-- Out of Stock Items -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-x-circle text-danger"></i> Out of Stock Items</h5>
                <span class="badge bg-danger"><?php echo count($outOfStock); ?> items</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Total Qty</th>
                                <th>In Use</th>
                                <th>Damaged</th>
                                <th>In Repair</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($outOfStock)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-success py-4">
                                    <i class="bi bi-check-circle"></i> No items out of stock!
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($outOfStock as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo escapeOutput($item['name']); ?></strong>
                                    <?php if (!empty($item['brand'])): ?>
                                    <br><small class="text-muted"><?php echo escapeOutput($item['brand']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-primary"><?php echo escapeOutput($item['category_name']); ?></span></td>
                                <td><?php echo $item['total_quantity']; ?></td>
                                <td><span class="badge bg-warning"><?php echo $item['in_use_quantity']; ?></span></td>
                                <td><span class="badge bg-danger"><?php echo $item['damaged_quantity']; ?></span></td>
                                <td><span class="badge bg-secondary"><?php echo $item['repair_quantity']; ?></span></td>
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

<!-- Chart.js Initialization -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inventory Status Distribution - Doughnut Chart
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        // Data from PHP
        const statusData = {
            available: <?php echo (int)$stats['available']; ?>,
            inUse: <?php echo (int)$stats['in_use']; ?>,
            damaged: <?php echo (int)$stats['damaged']; ?>,
            repair: <?php echo (int)$stats['repair']; ?>
        };
        
        // Only create chart if there's data
        const totalStatus = statusData.available + statusData.inUse + statusData.damaged + statusData.repair;
        
        if (totalStatus > 0) {
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Available', 'In Use', 'Damaged', 'In Repair'],
                    datasets: [{
                        data: [statusData.available, statusData.inUse, statusData.damaged, statusData.repair],
                        backgroundColor: [
                            '#059669', // Success green - Available
                            '#d97706', // Warning amber - In Use
                            '#dc2626', // Danger red - Damaged
                            '#64748b'  // Secondary gray - In Repair
                        ],
                        borderColor: '#ffffff',
                        borderWidth: 3,
                        hoverBorderWidth: 4,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '55%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                font: {
                                    family: "'Inter', sans-serif",
                                    size: 12,
                                    weight: '500'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleFont: {
                                family: "'Inter', sans-serif",
                                size: 14,
                                weight: '600'
                            },
                            bodyFont: {
                                family: "'Inter', sans-serif",
                                size: 13
                            },
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    const value = context.raw;
                                    const percentage = Math.round((value / totalStatus) * 100);
                                    return context.label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    }
                }
            });
        } else {
            // Show empty state message
            statusCtx.parentElement.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i><p class="mb-0">No inventory data available</p></div>';
        }
    }
    
    // Category Distribution - Bar Chart
    const categoryCtx = document.getElementById('categoryChart');
    if (categoryCtx) {
        // Data from PHP - using json_encode for safe JavaScript injection
        const categoryLabels = <?php echo json_encode(array_map(function($c) { return $c['name']; }, $categories), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        const categoryCounts = <?php echo json_encode(array_map(function($c) { return (int)($c['count'] ?? 0); }, $categories)); ?>;
        const categoryTotals = <?php echo json_encode(array_map(function($c) { return (int)($c['total'] ?? 0); }, $categories)); ?>;
        
        if (categoryLabels.length > 0 && categoryCounts.some(c => c > 0)) {
            new Chart(categoryCtx, {
                type: 'bar',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        label: 'Number of Items',
                        data: categoryCounts,
                        backgroundColor: '#2563eb',
                        borderColor: '#1d4ed8',
                        borderWidth: 1,
                        borderRadius: 6,
                        hoverBackgroundColor: '#1d4ed8'
                    }, {
                        label: 'Total Quantity',
                        data: categoryTotals,
                        backgroundColor: '#059669',
                        borderColor: '#047857',
                        borderWidth: 1,
                        borderRadius: 6,
                        hoverBackgroundColor: '#047857'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    family: "'Inter', sans-serif",
                                    size: 11
                                },
                                maxRotation: 45,
                                minRotation: 0
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#e2e8f0'
                            },
                            ticks: {
                                font: {
                                    family: "'Inter', sans-serif",
                                    size: 11
                                },
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'rect',
                                font: {
                                    family: "'Inter', sans-serif",
                                    size: 12,
                                    weight: '500'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleFont: {
                                family: "'Inter', sans-serif",
                                size: 14,
                                weight: '600'
                            },
                            bodyFont: {
                                family: "'Inter', sans-serif",
                                size: 13
                            },
                            padding: 12,
                            cornerRadius: 8
                        }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    }
                }
            });
        } else {
            // Show empty state message
            categoryCtx.parentElement.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i><p class="mb-0">No category data available</p></div>';
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
