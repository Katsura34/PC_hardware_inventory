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
                    <tr tabindex="0" role="row">
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
                            // Also check if last_activity was within the last 15 minutes for accuracy
                            if ($is_user_active && !empty($user['last_activity'])) {
                                $last_activity_time = strtotime($user['last_activity']);
                                $timeout_seconds = 15 * 60; // 15 minutes
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
                            <?php if (!empty($user['last_login_duration'])): ?>
                            <small>
                                <span class="badge bg-secondary">
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

<!-- Status indicator styles -->
<style>
.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

.status-online {
    background-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
    animation: pulse-online 2s infinite;
}

.status-offline {
    background-color: #6b7280;
}

@keyframes pulse-online {
    0% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
    }
    70% {
        box-shadow: 0 0 0 6px rgba(16, 185, 129, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
    }
}
</style>

<?php include '../includes/footer.php'; ?>
