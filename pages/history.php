<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/security.php';

// Require login
requireLogin();

$pageTitle = 'Audit Trail - PC Hardware Inventory';
$conn = getDBConnection();

// Pagination settings
$records_per_page = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Get filter parameters
$action_filter = isset($_GET['action']) ? sanitizeInput($_GET['action']) : '';
$date_from = isset($_GET['date_from']) ? sanitizeInput($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitizeInput($_GET['date_to']) : '';

// Build base query for counting
$count_query = "SELECT COUNT(*) as total FROM inventory_history ih WHERE 1=1";

// Build query with filters
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

$params = [];
$types = "";

// Add action filter
if (!empty($action_filter) && in_array($action_filter, ['Added', 'Updated', 'Deleted', 'Restored'])) {
    $query .= " AND ih.action_type = ?";
    $count_query .= " AND ih.action_type = ?";
    $params[] = $action_filter;
    $types .= "s";
}

// Add date range filters
if (!empty($date_from)) {
    $query .= " AND DATE(ih.action_date) >= ?";
    $count_query .= " AND DATE(ih.action_date) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $query .= " AND DATE(ih.action_date) <= ?";
    $count_query .= " AND DATE(ih.action_date) <= ?";
    $params[] = $date_to;
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
$query .= " ORDER BY ih.action_date DESC LIMIT ? OFFSET ?";
$params[] = $records_per_page;
$params[] = $offset;
$types .= "ii";

// Get inventory history with filters and pagination
$history = [];
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}
$stmt->close();

// Build pagination URL parameters
$pagination_params = array_filter([
    'action' => $action_filter ?: null,
    'date_from' => $date_from ?: null,
    'date_to' => $date_to ?: null
]);

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="system-branding">
            <h6><i class="bi bi-building"></i> ACLC COLLEGE OF ORMOC - PC HARDWARE INVENTORY SYSTEM</h6>
        </div>
        <h1 class="text-gradient mb-1">
            <i class="bi bi-clock-history"></i> Audit Trail
        </h1>
        <p class="text-muted">Complete audit trail of all hardware inventory changes</p>
    </div>
</div>

<!-- History Table -->
<div class="card table-card">
    <div class="card-header card-header-primary">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <h5 class="mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-table" aria-hidden="true"></i> Activity Log
                <span class="badge bg-light text-primary ms-2"><?php echo $total_records; ?></span>
            </h5>
            <div class="d-flex gap-2 align-items-center">
                <!-- Toggle Search Button -->
                <button class="btn btn-sm btn-light" type="button" id="toggleSearchBtn" 
                        aria-expanded="false" aria-controls="searchFilterPanel"
                        onclick="toggleHistorySearch()">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <span class="d-none d-sm-inline">Search</span>
                </button>
                <!-- Filter Dropdown -->
                <?php 
                $active_filter_count = (!empty($action_filter) ? 1 : 0) + (!empty($date_from) ? 1 : 0) + (!empty($date_to) ? 1 : 0);
                $has_filters = $active_filter_count > 0;
                ?>
                <button class="btn btn-sm <?php echo $has_filters ? 'btn-warning' : 'btn-light'; ?>" 
                        type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" 
                        aria-expanded="false" aria-controls="filterCollapse">
                    <i class="bi bi-funnel<?php echo $has_filters ? '-fill' : ''; ?>"></i>
                    <span class="d-none d-sm-inline"> Filters</span>
                    <?php if ($has_filters): ?>
                    <span class="badge bg-dark text-white ms-1"><?php echo $active_filter_count; ?></span>
                    <?php endif; ?>
                </button>
            </div>
        </div>
        <!-- Collapsible Search Panel -->
        <div class="search-filter-panel collapse mt-3" id="searchFilterPanel">
            <div class="search-box">
                <i class="bi bi-search search-icon" aria-hidden="true"></i>
                <input type="text" id="searchInput" class="form-control" 
                       placeholder="Search history by hardware name, category, user..." 
                       aria-label="Search history"
                       onkeyup="searchTable('searchInput', 'historyTable')">
                <button class="btn btn-sm btn-outline-secondary position-absolute end-0 me-2" 
                        type="button" onclick="clearHistorySearch()" 
                        style="top: 50%; transform: translateY(-50%);"
                        aria-label="Clear search">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <!-- Collapsible Filters Panel -->
        <div class="collapse mt-3" id="filterCollapse">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3 col-sm-6">
                <label for="action" class="form-label small mb-1 text-white-50">Action Type</label>
                <select class="form-select form-select-sm" id="action" name="action">
                    <option value="">All Actions</option>
                    <option value="Added" <?php echo $action_filter === 'Added' ? 'selected' : ''; ?>>Added</option>
                    <option value="Updated" <?php echo $action_filter === 'Updated' ? 'selected' : ''; ?>>Updated</option>
                    <option value="Deleted" <?php echo $action_filter === 'Deleted' ? 'selected' : ''; ?>>Deleted</option>
                    <option value="Restored" <?php echo $action_filter === 'Restored' ? 'selected' : ''; ?>>Restored</option>
                </select>
            </div>
            <div class="col-md-3 col-sm-6">
                <label for="date_from" class="form-label small mb-1 text-white-50"><i class="bi bi-calendar-event me-1"></i>Start Date</label>
                <input type="date" class="form-control form-control-sm" id="date_from" name="date_from" value="<?php echo escapeOutput($date_from); ?>" title="Filter records from this date">
            </div>
            <div class="col-md-3 col-sm-6">
                <label for="date_to" class="form-label small mb-1 text-white-50"><i class="bi bi-calendar-event me-1"></i>End Date</label>
                <input type="date" class="form-control form-control-sm" id="date_to" name="date_to" value="<?php echo escapeOutput($date_to); ?>" title="Filter records up to this date">
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-light btn-sm flex-grow-1">
                        <i class="bi bi-check-lg"></i> Apply
                    </button>
                    <a href="<?php echo BASE_PATH; ?>pages/history.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-x-lg"></i> Clear
                    </a>
                </div>
            </div>
        </form>
        </div>
        <?php if ($has_filters): ?>
        <div class="filter-tags d-flex flex-wrap gap-2 align-items-center mt-3 pt-3" style="border-top: 1px solid rgba(255,255,255,0.2);">
            <small class="text-white-50 me-1"><i class="bi bi-funnel-fill"></i> Active filters:</small>
            <?php if (!empty($action_filter)): ?>
            <span class="badge bg-light text-primary d-flex align-items-center gap-1">
                Action: <?php echo escapeOutput($action_filter); ?>
                <a href="?<?php echo http_build_query(array_filter(['date_from' => $date_from ?: null, 'date_to' => $date_to ?: null])); ?>" class="text-primary text-decoration-none ms-1" title="Remove filter">&times;</a>
            </span>
            <?php endif; ?>
            <?php if (!empty($date_from)): ?>
            <span class="badge bg-light text-primary d-flex align-items-center gap-1">
                From: <?php echo escapeOutput($date_from); ?>
                <a href="?<?php echo http_build_query(array_filter(['action' => $action_filter ?: null, 'date_to' => $date_to ?: null])); ?>" class="text-primary text-decoration-none ms-1" title="Remove filter">&times;</a>
            </span>
            <?php endif; ?>
            <?php if (!empty($date_to)): ?>
            <span class="badge bg-light text-primary d-flex align-items-center gap-1">
                To: <?php echo escapeOutput($date_to); ?>
                <a href="?<?php echo http_build_query(array_filter(['action' => $action_filter ?: null, 'date_from' => $date_from ?: null])); ?>" class="text-primary text-decoration-none ms-1" title="Remove filter">&times;</a>
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
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
                            if ($item['action_type'] === 'Restored') $badge_class = 'bg-primary';
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
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="card-footer bg-light">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <small class="text-muted">
                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> records
            </small>
            <nav aria-label="History pagination">
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

<div class="mt-4">
    <div class="card border-0 bg-light">
        <div class="card-body">
            <h6 class="mb-3"><i class="bi bi-info-circle"></i> Understanding the Audit Trail</h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <strong>Action Types:</strong>
                    <ul class="mb-0 mt-2">
                        <li><span class="badge bg-success">Added</span> - New hardware item was added to inventory</li>
                        <li><span class="badge bg-warning">Updated</span> - Hardware quantities or details were modified</li>
                        <li><span class="badge bg-danger">Deleted</span> - Hardware item was deleted from inventory</li>
                        <li><span class="badge bg-primary">Restored</span> - Deleted hardware item was restored</li>
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

<script>
// Toggle Search Filter Panel for History
function toggleHistorySearch() {
    var panel = document.getElementById('searchFilterPanel');
    var btn = document.getElementById('toggleSearchBtn');
    var isExpanded = panel.classList.contains('show');
    
    if (isExpanded) {
        panel.classList.remove('show');
        btn.setAttribute('aria-expanded', 'false');
        btn.innerHTML = '<i class="bi bi-search" aria-hidden="true"></i><span class="d-none d-sm-inline"> Search</span>';
        // Clear search when hiding
        clearHistorySearch();
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

// Clear Search Input for History
function clearHistorySearch() {
    var input = document.getElementById('searchInput');
    if (input) {
        input.value = '';
        searchTable('searchInput', 'historyTable');
    }
}

// Keyboard shortcut for search (/ key) - only add if not already added
if (!window.historyPageKeyboardHandlerAdded) {
    window.historyPageKeyboardHandlerAdded = true;
    document.addEventListener('keydown', function(e) {
        if (e.key === '/' && !e.target.matches('input, textarea, select')) {
            e.preventDefault();
            var panel = document.getElementById('searchFilterPanel');
            if (panel && !panel.classList.contains('show')) {
                toggleHistorySearch();
            } else if (panel) {
                document.getElementById('searchInput').focus();
            }
        }
        // Escape to close search
        if (e.key === 'Escape') {
            var panel = document.getElementById('searchFilterPanel');
            if (panel && panel.classList.contains('show')) {
                toggleHistorySearch();
            }
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>
