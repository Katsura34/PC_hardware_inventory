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
$result = $conn->query("SELECT ih.*, 
                              COALESCE(h.name, ih.hardware_name) as hardware_name, 
                              COALESCE(h.serial_number, ih.serial_number) as serial_number, 
                              COALESCE(u.full_name, ih.user_name) as user_name, 
                              COALESCE(c.name, ih.category_name) as category_name
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
        <div class="system-branding">
            <h6><i class="bi bi-building"></i> ACLC COLLEGE OF ORMOC - PC HARDWARE INVENTORY SYSTEM</h6>
        </div>
        <h1 class="text-gradient mb-1">
            <i class="bi bi-clock-history"></i> Inventory History
        </h1>
        <p class="text-muted">Complete audit trail of all hardware inventory changes</p>
    </div>
</div>

<!-- History Table -->
<div class="card table-card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
        <h5 class="mb-2 mb-md-0"><i class="bi bi-table"></i> Activity Log</h5>
        <input type="text" id="searchInput" class="form-control form-control-sm w-100" style="max-width: 300px;" 
               placeholder="Search history..." onkeyup="searchTable('searchInput', 'historyTable')">
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="historyTable">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Hardware Item</th>
                        <th class="d-none d-md-table-cell">Category</th>
                        <th>Action Type</th>
                        <th class="d-none d-lg-table-cell">Modified By</th>
                        <th class="d-none d-lg-table-cell">Quantity Change</th>
                        <th>Previous Status</th>
                        <th>New Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size: 32px; opacity: 0.5;"></i>
                            <br>No history records found
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($history as $item): ?>
                    <tr>
                        <td>
                            <small><strong><?php echo date('M d, Y', strtotime($item['action_date'])); ?></strong></small>
                            <br>
                            <small class="text-muted"><?php echo date('h:i A', strtotime($item['action_date'])); ?></small>
                        </td>
                        <td>
                            <strong><?php echo escapeOutput($item['hardware_name'] ?: 'Deleted Item'); ?></strong>
                            <?php if (!empty($item['serial_number'])): ?>
                            <br><small class="text-muted">SN: <?php echo escapeOutput($item['serial_number']); ?></small>
                            <?php endif; ?>
                            <?php if (empty($item['hardware_id']) || $item['action_type'] === 'Deleted'): ?>
                            <br><small class="badge bg-secondary">Deleted from System</small>
                            <?php endif; ?>
                            <!-- Show category and user on mobile (inline) -->
                            <div class="d-md-none mt-1">
                                <small><span class="badge bg-primary"><?php echo escapeOutput($item['category_name'] ?: 'N/A'); ?></span></small>
                            </div>
                            <div class="d-lg-none mt-1">
                                <small class="text-muted">By: <?php echo escapeOutput($item['user_name'] ?: 'Unknown'); ?></small>
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell"><span class="badge bg-primary"><?php echo escapeOutput($item['category_name'] ?: 'N/A'); ?></span></td>
                        <td>
                            <?php
                            $badge_class = 'bg-info';
                            if ($item['action_type'] === 'Added') $badge_class = 'bg-success';
                            if ($item['action_type'] === 'Updated') $badge_class = 'bg-warning';
                            if ($item['action_type'] === 'Deleted') $badge_class = 'bg-danger';
                            ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo escapeOutput($item['action_type']); ?></span>
                            <!-- Show quantity change on mobile (inline) -->
                            <div class="d-lg-none mt-1">
                                <?php
                                $change = $item['quantity_change'];
                                if ($change > 0) {
                                    echo '<span class="badge bg-success"><i class="bi bi-arrow-up"></i> +' . $change . '</span>';
                                } elseif ($change < 0) {
                                    echo '<span class="badge bg-danger"><i class="bi bi-arrow-down"></i> ' . $change . '</span>';
                                } else {
                                    echo '<span class="badge bg-secondary">No Change</span>';
                                }
                                ?>
                            </div>
                        </td>
                        <td class="d-none d-lg-table-cell"><small><?php echo escapeOutput($item['user_name'] ?: 'Unknown'); ?></small></td>
                        <td class="d-none d-lg-table-cell">
                            <?php
                            $change = $item['quantity_change'];
                            if ($change > 0) {
                                echo '<span class="badge bg-success"><i class="bi bi-arrow-up"></i> +' . $change . '</span>';
                            } elseif ($change < 0) {
                                echo '<span class="badge bg-danger"><i class="bi bi-arrow-down"></i> ' . $change . '</span>';
                            } else {
                                echo '<span class="badge bg-secondary">No Change</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <small>
                                <?php
                                $changes = [];
                                if ($item['old_unused'] != $item['new_unused']) {
                                    $changes[] = ['label' => 'Available', 'icon' => 'bi-check-circle', 'class' => 'text-success', 'old' => $item['old_unused'], 'new' => $item['new_unused']];
                                }
                                if ($item['old_in_use'] != $item['new_in_use']) {
                                    $changes[] = ['label' => 'In Use', 'icon' => 'bi-play-circle', 'class' => 'text-warning', 'old' => $item['old_in_use'], 'new' => $item['new_in_use']];
                                }
                                if ($item['old_damaged'] != $item['new_damaged']) {
                                    $changes[] = ['label' => 'Damaged', 'icon' => 'bi-exclamation-triangle', 'class' => 'text-danger', 'old' => $item['old_damaged'], 'new' => $item['new_damaged']];
                                }
                                if ($item['old_repair'] != $item['new_repair']) {
                                    $changes[] = ['label' => 'In Repair', 'icon' => 'bi-tools', 'class' => 'text-secondary', 'old' => $item['old_repair'], 'new' => $item['new_repair']];
                                }
                                
                                if (empty($changes)) {
                                    echo '<span class="text-muted">No changes</span>';
                                } else {
                                    foreach ($changes as $change) {
                                        echo '<div><span class="' . $change['class'] . '"><i class="bi ' . $change['icon'] . '"></i> ' . $change['label'] . ':</span> ' . $change['old'] . '</div>';
                                    }
                                }
                                ?>
                            </small>
                        </td>
                        <td>
                            <small>
                                <?php
                                if (empty($changes)) {
                                    echo '<span class="text-muted">-</span>';
                                } else {
                                    foreach ($changes as $change) {
                                        $diff = $change['new'] - $change['old'];
                                        $arrow = $diff > 0 ? '↑' : '↓';
                                        $diffClass = $diff > 0 ? 'text-success' : 'text-danger';
                                        echo '<div><span class="' . $change['class'] . '"><i class="bi ' . $change['icon'] . '"></i> ' . $change['label'] . ':</span> ' . $change['new'] . ' <span class="' . $diffClass . '">(' . $arrow . abs($diff) . ')</span></div>';
                                    }
                                }
                                ?>
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

<div class="mt-4">
    <div class="card border-0 bg-light">
        <div class="card-body">
            <h6 class="mb-3"><i class="bi bi-info-circle"></i> Understanding the History Log</h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <strong>Action Types:</strong>
                    <ul class="mb-0 mt-2">
                        <li><span class="badge bg-success">Added</span> - New hardware item was added to inventory</li>
                        <li><span class="badge bg-warning">Updated</span> - Hardware quantities or details were modified</li>
                        <li><span class="badge bg-danger">Deleted</span> - Hardware item was deleted from inventory</li>
                    </ul>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Status Categories:</strong>
                    <ul class="mb-0 mt-2">
                        <li><i class="bi bi-check-circle text-success"></i> <strong>Available:</strong> Items ready for use</li>
                        <li><i class="bi bi-play-circle text-warning"></i> <strong>In Use:</strong> Items currently deployed</li>
                        <li><i class="bi bi-exclamation-triangle text-danger"></i> <strong>Damaged:</strong> Items that are broken</li>
                        <li><i class="bi bi-tools text-secondary"></i> <strong>In Repair:</strong> Items being fixed</li>
                    </ul>
                </div>
            </div>
            <div class="alert alert-info mt-3 mb-0">
                <i class="bi bi-lightbulb"></i> <strong>Tip:</strong> Use the search box above to filter history by hardware name, category, user, or action type. Compare "Previous Status" and "New Status" columns to see exactly what changed.
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
