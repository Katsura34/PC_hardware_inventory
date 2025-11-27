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

// Get all users
$users = [];
$result = $conn->query("SELECT * FROM users ORDER BY date_created DESC");
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

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
                    <i class="bi bi-people"></i> User Management
                </h1>
                <p class="text-muted">Manage system users and permissions</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-plus-circle"></i> Add User
            </button>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="card table-card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
        <h5 class="mb-2 mb-md-0"><i class="bi bi-table"></i> All Users</h5>
        <input type="text" id="searchInput" class="form-control form-control-sm w-100" style="max-width: 300px;" 
               placeholder="Search..." onkeyup="searchTable('searchInput', 'usersTable')">
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="usersTable">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th class="d-none d-md-table-cell">Date Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No users found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <strong><?php echo escapeOutput($user['username']); ?></strong>
                            <?php if ($user['id'] === $_SESSION['user_id']): ?>
                            <span class="badge bg-info">You</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo escapeOutput($user['full_name']); ?></td>
                        <td>
                            <?php if ($user['role'] === 'admin'): ?>
                            <span class="badge bg-danger">Admin</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Staff</span>
                            <?php endif; ?>
                        </td>
                        <td class="d-none d-md-table-cell"><small><?php echo date('M d, Y', strtotime($user['date_created'])); ?></small></td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick='editUser(<?php echo json_encode($user); ?>)'>
                                <i class="bi bi-pencil"></i><span class="d-none d-sm-inline"> Edit</span>
                            </button>
                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                            <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" 
                               onclick="return confirmDelete('Are you sure you want to delete this user?')">
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
</div>

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
// Edit user function
function editUser(user) {
    document.getElementById('edit_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_password').value = '';
    
    const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
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
