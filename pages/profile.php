<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/security.php';

// Require login
requireLogin();

$pageTitle = 'Profile Settings - PC Hardware Inventory';
$conn = getDBConnection();

$error = '';
$success = '';

// Get current user data
$stmt = $conn->prepare("SELECT id, username, full_name, role FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        // Update username and full name
        $username = sanitizeForDB($conn, $_POST['username']);
        $full_name = sanitizeForDB($conn, $_POST['full_name']);
        
        if (empty($username) || empty($full_name)) {
            $error = 'Username and full name are required.';
        } else {
            // Check if username already exists (for another user)
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $check_stmt->bind_param("si", $username, $_SESSION['user_id']);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error = 'Username already exists. Please choose a different one.';
            } else {
                // Update user profile
                $update_stmt = $conn->prepare("UPDATE users SET username = ?, full_name = ? WHERE id = ?");
                $update_stmt->bind_param("ssi", $username, $full_name, $_SESSION['user_id']);
                
                if ($update_stmt->execute()) {
                    // Update session data
                    $_SESSION['username'] = $username;
                    $_SESSION['full_name'] = $full_name;
                    $user['username'] = $username;
                    $user['full_name'] = $full_name;
                    $success = 'Profile updated successfully.';
                } else {
                    $error = 'Failed to update profile. Please try again.';
                }
                $update_stmt->close();
            }
            $check_stmt->close();
        }
    } elseif ($action === 'change_password') {
        // Change password
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All password fields are required.';
        } elseif (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters long.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New password and confirmation do not match.';
        } else {
            // Verify current password
            $pwd_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $pwd_stmt->bind_param("i", $_SESSION['user_id']);
            $pwd_stmt->execute();
            $pwd_result = $pwd_stmt->get_result();
            $pwd_row = $pwd_result->fetch_assoc();
            $pwd_stmt->close();
            
            if (!verifyPassword($current_password, $pwd_row['password'])) {
                $error = 'Current password is incorrect.';
            } else {
                // Update password
                $hashed_password = hashPassword($new_password);
                $update_pwd_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_pwd_stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
                
                if ($update_pwd_stmt->execute()) {
                    $success = 'Password changed successfully.';
                } else {
                    $error = 'Failed to change password. Please try again.';
                }
                $update_pwd_stmt->close();
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <!-- Page Header -->
        <div class="page-header-hci">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-person-gear" aria-hidden="true"></i> Profile Settings
                    </h1>
                    <p class="subtitle">Manage your account information and password</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($error)): ?>
<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
    <div><?php echo escapeOutput($error); ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if (!empty($success)): ?>
<div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
    <i class="bi bi-check-circle-fill me-2" aria-hidden="true"></i>
    <div><?php echo escapeOutput($success); ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Profile Information Card -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-person-circle me-2" aria-hidden="true"></i>
                    Profile Information
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo escapeOutput($user['username']); ?>" required>
                        <div class="form-text">Your unique login username.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo escapeOutput($user['full_name']); ?>" required>
                        <div class="form-text">Your display name shown throughout the system.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="<?php echo escapeOutput(ucfirst($user['role'])); ?>" disabled>
                        <div class="form-text">Your role cannot be changed. Contact an administrator if needed.</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1" aria-hidden="true"></i>
                        Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Change Password Card -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-key me-2" aria-hidden="true"></i>
                    Change Password
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate id="changePasswordForm">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')" 
                                    aria-label="Toggle password visibility">
                                <i class="bi bi-eye" id="current_password_icon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   minlength="6" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')"
                                    aria-label="Toggle password visibility">
                                <i class="bi bi-eye" id="new_password_icon"></i>
                            </button>
                        </div>
                        <div class="form-text">Minimum 6 characters.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   minlength="6" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')"
                                    aria-label="Toggle password visibility">
                                <i class="bi bi-eye" id="confirm_password_icon"></i>
                            </button>
                        </div>
                        <div class="form-text">Re-enter your new password to confirm.</div>
                    </div>
                    
                    <button type="button" class="btn btn-warning" onclick="confirmPasswordChange()">
                        <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>
                        Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
function togglePassword(fieldId) {
    var field = document.getElementById(fieldId);
    var icon = document.getElementById(fieldId + '_icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// Confirm password change before submission
function confirmPasswordChange() {
    var form = document.getElementById('changePasswordForm');
    var currentPassword = document.getElementById('current_password').value;
    var newPassword = document.getElementById('new_password').value;
    var confirmPassword = document.getElementById('confirm_password').value;
    
    // Basic validation
    if (!currentPassword || !newPassword || !confirmPassword) {
        showAlert('Please fill in all password fields.', 'Validation Error', 'error');
        return;
    }
    
    if (newPassword.length < 6) {
        showAlert('New password must be at least 6 characters long.', 'Validation Error', 'error');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        showAlert('New password and confirmation do not match.', 'Validation Error', 'error');
        return;
    }
    
    // Show confirmation dialog
    showConfirmation(
        'Are you sure you want to change your password? You will need to use your new password the next time you log in.',
        'Confirm Password Change',
        'Change Password',
        'warning'
    ).then(function(confirmed) {
        if (confirmed) {
            showLoading('Changing password...');
            form.submit();
        }
    });
}

// Form validation
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
            
            // Show loading for profile update
            if (form.querySelector('input[name="action"][value="update_profile"]')) {
                showLoading('Saving changes...');
            }
        }, false);
    });
})();
</script>

<?php include '../includes/footer.php'; ?>
