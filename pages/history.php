<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/security.php';

// Require login
requireLogin();

$pageTitle = 'Inventory History - PC Hardware Inventory';
$conn = getDBConnection();

// Get inventory history
$history = [];
$result = $conn->query("SELECT ih.*, h.name as hardware_name, u.full_name as user_name, c.name as category_name
                       FROM inventory_history ih
                       LEFT JOIN hardware h ON ih.hardware_id = h.id
                       LEFT JOIN users u ON ih.user_id = u.id
                       LEFT JOIN categories c ON h.category_id = c.id
                       ORDER BY ih.action_date DESC
                       LIMIT 100");
while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="text-gradient mb-1">
            <i class="bi bi-clock-history"></i> Inventory History
        </h1>
        <p class="text-muted">Track all changes made to hardware inventory</p>
    </div>
</div>

<!-- History Table -->
<div class="card table-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-table"></i> Recent Activities</h5>
        <input type="text" id="searchInput" class="form-control form-control-sm" style="max-width: 300px;" 
               placeholder="Search..." onkeyup="searchTable('searchInput', 'historyTable')">
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="historyTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Hardware</th>
                        <th>Category</th>
                        <th>Action</th>
                        <th>User</th>
                        <th>Change</th>
                        <th>Before</th>
                        <th>After</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No history records found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($history as $item): ?>
                    <tr>
                        <td><small><?php echo date('M d, Y H:i', strtotime($item['action_date'])); ?></small></td>
                        <td><strong><?php echo escapeOutput($item['hardware_name']); ?></strong></td>
                        <td><span class="badge bg-primary"><?php echo escapeOutput($item['category_name'] ?: 'N/A'); ?></span></td>
                        <td>
                            <?php
                            $badge_class = 'bg-info';
                            if ($item['action_type'] === 'Added') $badge_class = 'bg-success';
                            if ($item['action_type'] === 'Updated') $badge_class = 'bg-warning';
                            if ($item['action_type'] === 'Removed') $badge_class = 'bg-danger';
                            ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo escapeOutput($item['action_type']); ?></span>
                        </td>
                        <td><small><?php echo escapeOutput($item['user_name']); ?></small></td>
                        <td>
                            <?php
                            $change = $item['quantity_change'];
                            if ($change > 0) {
                                echo '<span class="badge bg-success">+' . $change . '</span>';
                            } elseif ($change < 0) {
                                echo '<span class="badge bg-danger">' . $change . '</span>';
                            } else {
                                echo '<span class="badge bg-secondary">0</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <small class="text-muted">
                                A:<?php echo $item['old_unused']; ?> | 
                                U:<?php echo $item['old_in_use']; ?> | 
                                D:<?php echo $item['old_damaged']; ?> | 
                                R:<?php echo $item['old_repair']; ?>
                            </small>
                        </td>
                        <td>
                            <small class="text-muted">
                                A:<?php echo $item['new_unused']; ?> | 
                                U:<?php echo $item['new_in_use']; ?> | 
                                D:<?php echo $item['new_damaged']; ?> | 
                                R:<?php echo $item['new_repair']; ?>
                            </small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    <small class="text-muted">
        <strong>Legend:</strong> A = Available | U = In Use | D = Damaged | R = In Repair
    </small>
</div>

<?php include '../includes/footer.php'; ?>
