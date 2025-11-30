<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/security.php';

// Require admin
requireAdmin();

$pageTitle = 'User Management - PC Hardware Inventory';
$conn = getDBConnection();

// Handle delete
if (isset($_GET['delete']) && validateInt($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Prevent deleting yourself
    if ($id === $_SESSION['user_id']) {
        redirectWithMessage(BASE_PATH . 'pages/users.php', 'You cannot delete your own account.', 'error');
    }
    
    // Delete user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        redirectWithMessage(BASE_PATH . 'pages/users.php', 'User deleted successfully.', 'success');
    } else {
        redirectWithMessage(BASE_PATH . 'pages/users.php', 'Failed to delete user.', 'error');
    }
    $stmt->close();
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    $username = sanitizeForDB($conn, $_POST['username']);
    $full_name = sanitizeForDB($conn, $_POST['full_name']);
    $role = sanitizeForDB($conn, $_POST['role']);
    $password = $_POST['password'];
    
    if ($action === 'add') {
        // Check if username already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            redirectWithMessage(BASE_PATH . 'pages/users.php', 'Username already exists.', 'error');
        }
        $check_stmt->close();
        
        // Hash password
        $hashed_password = hashPassword($password);
        
        $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $hashed_password, $full_name, $role);
        
        if ($stmt->execute()) {
            redirectWithMessage(BASE_PATH . 'pages/users.php', 'User added successfully.', 'success');
        } else {
            redirectWithMessage(BASE_PATH . 'pages/users.php', 'Failed to add user.', 'error');
        }
        $stmt->close();
        
    } elseif ($action === 'edit' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        
        // If password is provided, update it
        if (!empty($password)) {
            $hashed_password = hashPassword($password);
            $stmt = $conn->prepare("UPDATE users SET username=?, password=?, full_name=?, role=? WHERE id=?");
            $stmt->bind_param("ssssi", $username, $hashed_password, $full_name, $role, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=?, full_name=?, role=? WHERE id=?");
            $stmt->bind_param("sssi", $username, $full_name, $role, $id);
        }
        
        if ($stmt->execute()) {
            redirectWithMessage(BASE_PATH . 'pages/users.php', 'User updated successfully.', 'success');
        } else {
            redirectWithMessage(BASE_PATH . 'pages/users.php', 'Failed to update user.', 'error');
        }
        $stmt->close();
    }
}

// Pagination settings
$records_per_page = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Get total count for pagination
$count_result = $conn->query("SELECT COUNT(*) as total FROM users");
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get users with pagination
$users = [];
$stmt = $conn->prepare("SELECT * FROM users ORDER BY date_created DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();

// Build pagination URL parameters (empty for users page - no filters)
$pagination_params = [];

include '../includes/header.php';
?>

<!-- Skip to main content link for accessibility -->
<a href="#usersTable" class="visually-hidden-focusable">Skip to users table</a>

<div class="row mb-4">
    <div class="col-12">

        <!-- Page Header with Toolbar - HCI: Clear hierarchy -->
        <div class="page-header-hci">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-people" aria-hidden="true"></i> User Management
                    </h1>
                    <p class="subtitle">Manage system users and permissions</p>
                </div>
                <!-- Primary CTA - HCI: Visible and prominent -->
                <button class="btn btn-primary-cta" data-bs-toggle="modal" data-bs-target="#addUserModal" aria-label="Add new user">
                    <i class="bi bi-plus-circle" aria-hidden="true"></i> Add User
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Users Table - HCI: Semantic markup, keyboard accessible -->
<div class="card table-card" role="region" aria-label="Users list">
    <div class="card-header card-header-primary">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <h5 class="mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-table" aria-hidden="true"></i> All Users
                <span class="badge bg-light text-primary ms-2"><?php echo $total_records; ?></span>
                <!-- Live update indicator -->
                <span class="badge bg-success ms-2 live-indicator" title="Table updates automatically every 5 seconds">
                    <i class="bi bi-broadcast" aria-hidden="true"></i>
                    <span class="d-none d-sm-inline">Live</span>
                </span>
            </h5>
            <!-- Toggle Search Button - HCI: Show/Hide filter -->
            <button class="btn btn-sm btn-light" type="button" id="toggleSearchBtn" 
                    aria-expanded="false" aria-controls="searchFilterPanel"
                    onclick="toggleSearchFilter()">
                <i class="bi bi-search" aria-hidden="true"></i>
                <span class="d-none d-sm-inline">Search</span>
            </button>
        </div>
        <!-- Collapsible Search Panel -->
        <div class="search-filter-panel collapse mt-3" id="searchFilterPanel">
            <div class="search-box">
                <i class="bi bi-search search-icon" aria-hidden="true"></i>
                <input type="text" id="searchInput" class="form-control" 
                       placeholder="Search users by name, username, or role..." 
                       aria-label="Search users"
                       onkeyup="searchTable('searchInput', 'usersTable')">
                <button class="btn btn-sm btn-outline-secondary position-absolute end-0 me-2" 
                        type="button" onclick="clearSearch()" 
                        style="top: 50%; transform: translateY(-50%);"
                        aria-label="Clear search">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-hci mb-0" id="usersTable" role="grid">
                <thead>
                    <tr>
                        <th scope="col">Username</th>
                        <th scope="col">Full Name</th>
                        <th scope="col">Role</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="d-none d-md-table-cell">Last Login</th>
                        <th scope="col" class="d-none d-lg-table-cell">Session Duration</th>
                        <th scope="col" class="d-none d-xl-table-cell">Date Created</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8">
                            <!-- Empty State - HCI: Clear guidance -->
                            <div class="empty-state-hci">
                                <i class="bi bi-people empty-icon" aria-hidden="true"></i>
                                <h5>No users found</h5>
                                <p>Get started by adding your first user to the system.</p>
                                <button class="btn btn-primary-cta" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                    <i class="bi bi-plus-circle" aria-hidden="true"></i> Add First User
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($users as $user): ?>
                    <?php
                    // Format login duration for display
                    $login_duration_display = 'Never logged in';
                    if (!empty($user['last_login_duration'])) {
                        $duration_seconds = (int)$user['last_login_duration'];
                        if ($duration_seconds < 60) {
                            $login_duration_display = $duration_seconds . ' sec';
                        } elseif ($duration_seconds < 3600) {
                            $minutes = floor($duration_seconds / 60);
                            $seconds = $duration_seconds % 60;
                            $login_duration_display = $minutes . ' min' . ($seconds > 0 ? ' ' . $seconds . ' sec' : '');
                        } else {
                            $hours = floor($duration_seconds / 3600);
                            $minutes = floor(($duration_seconds % 3600) / 60);
                            $login_duration_display = $hours . ' hr' . ($minutes > 0 ? ' ' . $minutes . ' min' : '');
                        }
                    }
                    ?>
                    <tr tabindex="0" role="row" data-user-id="<?php echo $user['id']; ?>">
                        <td data-label="Username">
                            <div class="d-flex align-items-center gap-2">
                                <!-- User Avatar -->
                                <div class="user-avatar-sm" style="width: 32px; height: 32px; border-radius: 50%; background: var(--bg-page, #f5f7f9); display: flex; align-items: center; justify-content: center; color: var(--primary, #1e6fb8);">
                                    <i class="bi bi-person-fill" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <strong><?php echo escapeOutput($user['username']); ?></strong>
                                    <?php if ($user['id'] === $_SESSION['user_id']): ?>
                                    <span class="badge bg-info badge-sm ms-1">You</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td data-label="Full Name"><?php echo escapeOutput($user['full_name']); ?></td>
                        <td data-label="Role">
                            <!-- Role Badges - HCI: Visual distinction with muted styling -->
                            <?php if ($user['role'] === 'admin'): ?>
                            <span class="badge-role badge-role-admin" title="Administrator">Admin</span>
                            <?php else: ?>
                            <span class="badge-role badge-role-staff" title="Staff member">Staff</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Status">
                            <?php 
                            // Check if user is active based on is_active flag and last_activity
                            $is_user_active = !empty($user['is_active']) && $user['is_active'] == 1;
                            // Also check if last_activity was within the timeout period for accuracy
                            if ($is_user_active && !empty($user['last_activity'])) {
                                $last_activity_time = strtotime($user['last_activity']);
                                $timeout_seconds = SESSION_TIMEOUT_MINUTES * 60;
                                if (time() - $last_activity_time > $timeout_seconds) {
                                    $is_user_active = false;
                                }
                            }
                            ?>
                            <?php if ($is_user_active): ?>
                            <span class="badge bg-success d-inline-flex align-items-center gap-1" title="User is currently online">
                                <span class="status-dot status-online"></span>
                                Online
                            </span>
                            <?php else: ?>
                            <span class="badge bg-secondary d-inline-flex align-items-center gap-1" title="User is offline">
                                <span class="status-dot status-offline"></span>
                                Offline
                            </span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Last Login" class="d-none d-md-table-cell">
                            <?php if (!empty($user['last_login'])): ?>
                            <small class="text-muted">
                                <i class="bi bi-clock me-1" aria-hidden="true"></i>
                                <?php echo date('M d, Y H:i', strtotime($user['last_login'])); ?>
                            </small>
                            <?php else: ?>
                            <small class="text-muted">
                                <i class="bi bi-dash-circle me-1" aria-hidden="true"></i>
                                Never
                            </small>
                            <?php endif; ?>
                        </td>
                        <td data-label="Session Duration" class="d-none d-lg-table-cell">
                            <?php if ($is_user_active && !empty($user['last_login'])): ?>
                            <?php 
                            // Set timezone to Philippines (Asia/Manila, UTC+8)
                            $ph_timezone = new DateTimeZone('Asia/Manila');
                            
                            // Create DateTime object from login timestamp in the database's timezone (assumed server timezone)
                            $login_datetime = new DateTime($user['last_login']);
                            // Convert to Philippines timezone
                            $login_datetime->setTimezone($ph_timezone);
                            $login_timestamp = $login_datetime->getTimestamp();
                            
                            // Get current time in Philippines timezone
                            $current_ph_time = new DateTime('now', $ph_timezone);
                            $current_timestamp = $current_ph_time->getTimestamp();
                            
                            // Validate that login_timestamp is valid
                            if ($login_timestamp !== false):
                                // Calculate initial duration: current Philippines time - login time
                                $initial_duration = max(0, $current_timestamp - $login_timestamp);
                            ?>
                            <!-- Live session counter for online users -->
                            <small>
                                <span class="badge bg-success live-session-badge" 
                                      data-login-timestamp="<?php echo $login_timestamp * 1000; ?>"
                                      title="Current session started at <?php echo $login_datetime->format('M d, Y H:i'); ?> (PH Time)">
                                    <i class="bi bi-play-circle me-1" aria-hidden="true"></i>
                                    <span class="live-duration">Calculating...</span>
                                </span>
                            </small>
                            <?php else: ?>
                            <!-- Invalid date format fallback -->
                            <small class="text-muted">-</small>
                            <?php endif; ?>
                            <?php elseif (!empty($user['last_login_duration'])): ?>
                            <!-- Last session duration for offline users -->
                            <small>
                                <span class="badge bg-secondary" title="Last session duration">
                                    <i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>
                                    <?php echo escapeOutput($login_duration_display); ?>
                                </span>
                            </small>
                            <?php else: ?>
                            <small class="text-muted">-</small>
                            <?php endif; ?>
                        </td>
                        <td data-label="Date Created" class="d-none d-xl-table-cell">
                            <small class="text-muted">
                                <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>
                                <?php echo date('M d, Y', strtotime($user['date_created'])); ?>
                            </small>
                        </td>
                        <td data-label="Actions">
                            <div class="d-flex gap-1 flex-wrap">
                                <!-- Edit Button - HCI: Icon + label -->
                                <button class="btn btn-action btn-info" 
                                        onclick='editUser(<?php echo htmlspecialchars(json_encode($user), ENT_QUOTES, "UTF-8"); ?>)'
                                        aria-label="Edit user <?php echo escapeOutput($user['username']); ?>">
                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                    <span class="d-none d-sm-inline">Edit</span>
                                </button>
                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                <!-- Delete Button - HCI: Confirmation required -->
                                <a href="?delete=<?php echo $user['id']; ?>" 
                                   class="btn btn-action btn-danger" 
                                   onclick="return confirmDelete('Are you sure you want to delete user &quot;<?php echo escapeOutput($user['username']); ?>&quot;? This action cannot be undone.', this)"
                                   aria-label="Delete user <?php echo escapeOutput($user['username']); ?>">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                    <span class="d-none d-sm-inline">Delete</span>
                                </a>
                                <?php endif; ?>
                            </div>
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
                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> users
            </small>
            <nav aria-label="Users pagination">
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

<script>
// Toggle Search Filter Panel
function toggleSearchFilter() {
    var panel = document.getElementById('searchFilterPanel');
    var btn = document.getElementById('toggleSearchBtn');
    var isExpanded = panel.classList.contains('show');
    
    if (isExpanded) {
        panel.classList.remove('show');
        btn.setAttribute('aria-expanded', 'false');
        btn.innerHTML = '<i class="bi bi-search" aria-hidden="true"></i><span class="d-none d-sm-inline"> Search</span>';
        // Clear search when hiding
        clearSearch();
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

// Clear Search Input
function clearSearch() {
    var input = document.getElementById('searchInput');
    if (input) {
        input.value = '';
        searchTable('searchInput', 'usersTable');
    }
}

// Keyboard shortcut for search (/ key) - only add if not already added
if (!window.usersPageKeyboardHandlerAdded) {
    window.usersPageKeyboardHandlerAdded = true;
    document.addEventListener('keydown', function(e) {
        if (e.key === '/' && !e.target.matches('input, textarea, select')) {
            e.preventDefault();
            var panel = document.getElementById('searchFilterPanel');
            if (panel && !panel.classList.contains('show')) {
                toggleSearchFilter();
            } else if (panel) {
                document.getElementById('searchInput').focus();
            }
        }
        // Escape to close search
        if (e.key === 'Escape') {
            var panel = document.getElementById('searchFilterPanel');
            if (panel && panel.classList.contains('show')) {
                toggleSearchFilter();
            }
        }
    });
}

// ============ Live Session Duration Counter ============
// Get current time in Philippines timezone (Asia/Manila, UTC+8)
function getPhilippinesTime() {
    // Get current UTC time
    var now = new Date();
    // Philippines is UTC+8, so add 8 hours in milliseconds
    var utcTime = now.getTime() + (now.getTimezoneOffset() * 60000);
    var philippinesOffset = 8 * 60 * 60000; // 8 hours in milliseconds
    return utcTime + philippinesOffset;
}

// Format duration in seconds to human-readable string
function formatDuration(seconds) {
    if (seconds < 60) {
        return seconds + ' sec';
    } else if (seconds < 3600) {
        var minutes = Math.floor(seconds / 60);
        var secs = seconds % 60;
        return minutes + ' min' + (secs > 0 ? ' ' + secs + ' sec' : '');
    } else {
        var hours = Math.floor(seconds / 3600);
        var minutes = Math.floor((seconds % 3600) / 60);
        return hours + ' hr' + (minutes > 0 ? ' ' + minutes + ' min' : '');
    }
}

// Update all live session duration counters
function updateLiveSessionDurations() {
    var badges = document.querySelectorAll('.live-session-badge');
    // Get current Philippines time in milliseconds
    var currentPhTime = getPhilippinesTime();
    
    badges.forEach(function(badge) {
        // Get login timestamp in milliseconds (already converted to PH time on server)
        var loginTimestamp = parseInt(badge.getAttribute('data-login-timestamp'), 10);
        if (!isNaN(loginTimestamp) && loginTimestamp > 0) {
            // Calculate duration: current Philippines time - login timestamp
            var durationMs = currentPhTime - loginTimestamp;
            var duration = Math.max(0, Math.floor(durationMs / 1000));
            var durationSpan = badge.querySelector('.live-duration');
            if (durationSpan) {
                durationSpan.textContent = formatDuration(duration);
            }
        }
    });
}

// ============ Live Table Updates (React-like behavior) ============
var liveTableIntervalId = null;
var liveSessionIntervalId = null;
var currentPage = <?php echo $page; ?>;
var isUpdating = false;

// Store user data for edit operations
var usersDataCache = {};

// Escape HTML to prevent XSS
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Handle edit button click using event delegation
function handleEditClick(userId) {
    var user = usersDataCache[userId];
    if (user) {
        editUser(user);
    }
}

// Generate table row HTML for a user
function generateUserRow(user) {
    // Cache user data for edit operations
    usersDataCache[user.id] = user;
    
    var statusBadge = user.is_active 
        ? '<span class="badge bg-success d-inline-flex align-items-center gap-1" title="User is currently online"><span class="status-dot status-online"></span>Online</span>'
        : '<span class="badge bg-secondary d-inline-flex align-items-center gap-1" title="User is offline"><span class="status-dot status-offline"></span>Offline</span>';
    
    var roleBadge = user.role === 'admin'
        ? '<span class="badge-role badge-role-admin" title="Administrator">Admin</span>'
        : '<span class="badge-role badge-role-staff" title="Staff member">Staff</span>';
    
    var youBadge = user.is_current_user ? '<span class="badge bg-info badge-sm ms-1">You</span>' : '';
    
    var lastLoginHtml = user.last_login_display 
        ? '<small class="text-muted"><i class="bi bi-clock me-1" aria-hidden="true"></i>' + escapeHtml(user.last_login_display) + '</small>'
        : '<small class="text-muted"><i class="bi bi-dash-circle me-1" aria-hidden="true"></i>Never</small>';
    
    var sessionDurationHtml = '';
    if (user.is_active && user.login_timestamp_ms) {
        sessionDurationHtml = '<small><span class="badge bg-success live-session-badge" data-login-timestamp="' + user.login_timestamp_ms + '" title="Current session started at ' + escapeHtml(user.login_display) + ' (PH Time)"><i class="bi bi-play-circle me-1" aria-hidden="true"></i><span class="live-duration">Calculating...</span></span></small>';
    } else if (user.last_login_duration_display) {
        sessionDurationHtml = '<small><span class="badge bg-secondary" title="Last session duration"><i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>' + escapeHtml(user.last_login_duration_display) + '</span></small>';
    } else {
        sessionDurationHtml = '<small class="text-muted">-</small>';
    }
    
    var deleteButton = !user.is_current_user 
        ? '<a href="?delete=' + parseInt(user.id, 10) + '" class="btn btn-action btn-danger" onclick="return confirmDelete(\'Are you sure you want to delete user &quot;' + escapeHtml(user.username) + '&quot;? This action cannot be undone.\', this)" aria-label="Delete user ' + escapeHtml(user.username) + '"><i class="bi bi-trash" aria-hidden="true"></i><span class="d-none d-sm-inline">Delete</span></a>'
        : '';
    
    return '<tr tabindex="0" role="row" data-user-id="' + parseInt(user.id, 10) + '">' +
        '<td data-label="Username">' +
            '<div class="d-flex align-items-center gap-2">' +
                '<div class="user-avatar-sm" style="width: 32px; height: 32px; border-radius: 50%; background: var(--bg-page, #f5f7f9); display: flex; align-items: center; justify-content: center; color: var(--primary, #1e6fb8);">' +
                    '<i class="bi bi-person-fill" aria-hidden="true"></i>' +
                '</div>' +
                '<div>' +
                    '<strong>' + escapeHtml(user.username) + '</strong>' + youBadge +
                '</div>' +
            '</div>' +
        '</td>' +
        '<td data-label="Full Name">' + escapeHtml(user.full_name) + '</td>' +
        '<td data-label="Role">' + roleBadge + '</td>' +
        '<td data-label="Status">' + statusBadge + '</td>' +
        '<td data-label="Last Login" class="d-none d-md-table-cell">' + lastLoginHtml + '</td>' +
        '<td data-label="Session Duration" class="d-none d-lg-table-cell">' + sessionDurationHtml + '</td>' +
        '<td data-label="Date Created" class="d-none d-xl-table-cell">' +
            '<small class="text-muted"><i class="bi bi-calendar3 me-1" aria-hidden="true"></i>' + escapeHtml(user.date_created) + '</small>' +
        '</td>' +
        '<td data-label="Actions">' +
            '<div class="d-flex gap-1 flex-wrap">' +
                '<button class="btn btn-action btn-info" data-user-id="' + parseInt(user.id, 10) + '" onclick="handleEditClick(' + parseInt(user.id, 10) + ')" aria-label="Edit user ' + escapeHtml(user.username) + '">' +
                    '<i class="bi bi-pencil" aria-hidden="true"></i>' +
                    '<span class="d-none d-sm-inline">Edit</span>' +
                '</button>' +
                deleteButton +
            '</div>' +
        '</td>' +
    '</tr>';
}

// Fetch and update table data
function refreshUsersTable() {
    if (isUpdating) return;
    isUpdating = true;
    
    fetch('api_users.php?page=' + currentPage)
        .then(function(response) {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(function(data) {
            if (!data.success) {
                console.error('API error:', data.error);
                return;
            }
            
            var tbody = document.querySelector('#usersTable tbody');
            if (!tbody) return;
            
            // Generate new table content
            var newContent = '';
            if (data.users.length === 0) {
                newContent = '<tr><td colspan="8">' +
                    '<div class="empty-state-hci">' +
                        '<i class="bi bi-people empty-icon" aria-hidden="true"></i>' +
                        '<h5>No users found</h5>' +
                        '<p>Get started by adding your first user to the system.</p>' +
                        '<button class="btn btn-primary-cta" data-bs-toggle="modal" data-bs-target="#addUserModal">' +
                            '<i class="bi bi-plus-circle" aria-hidden="true"></i> Add First User' +
                        '</button>' +
                    '</div>' +
                '</td></tr>';
            } else {
                data.users.forEach(function(user) {
                    newContent += generateUserRow(user);
                });
            }
            
            // Update table body
            tbody.innerHTML = newContent;
            
            // Update total count badge
            var countBadge = document.querySelector('.card-header-primary .badge');
            if (countBadge) {
                countBadge.textContent = data.pagination.total_records;
            }
            
            // Immediately update session durations for the new content
            updateLiveSessionDurations();
        })
        .catch(function(error) {
            console.error('Error fetching users:', error);
        })
        .finally(function() {
            isUpdating = false;
        });
}

// Initialize live updates
document.addEventListener('DOMContentLoaded', function() {
    // Initial update for session durations
    updateLiveSessionDurations();
    
    // Update session durations every second
    liveSessionIntervalId = setInterval(updateLiveSessionDurations, 1000);
    
    // Refresh table data every 5 seconds for live updates (like React)
    liveTableIntervalId = setInterval(refreshUsersTable, 5000);
});

// Cleanup intervals when leaving page
window.addEventListener('beforeunload', function() {
    if (liveSessionIntervalId) clearInterval(liveSessionIntervalId);
    if (liveTableIntervalId) clearInterval(liveTableIntervalId);
});
</script>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role *</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="edit_password" name="password" minlength="6">
                        <small class="text-muted">Leave blank to keep current password</small>
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Role *</label>
                        <select class="form-select" id="edit_role" name="role" required>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Edit user function - opens modal directly
function editUser(user) {
    document.getElementById('edit_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_password').value = '';
    
    const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
    editModal.show();
}

// Form validation with confirmation for edit form and loading screens
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
            
            // Check if this is the edit user form (has edit action)
            var actionInput = form.querySelector('input[name="action"]');
            if (actionInput && actionInput.value === 'edit') {
                event.preventDefault();
                var userName = document.getElementById('edit_username').value;
                showConfirmation(
                    'Are you sure you want to update user "' + userName + '"?',
                    'Confirm Update',
                    'Update',
                    'warning'
                ).then(function(confirmed) {
                    if (confirmed) {
                        showLoading('Updating user...');
                        form.submit();
                    }
                });
                return;
            }
            
            // Show loading for add action
            if (actionInput && actionInput.value === 'add') {
                showLoading('Adding user...');
            }
        }, false);
    });
})();
</script>

<?php include '../includes/footer.php'; ?>
