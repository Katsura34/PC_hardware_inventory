<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/security.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $conn = getDBConnection();
        
        // Prepare statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (verifyPassword($password, $user['password'])) {
                // Update last login timestamp and set user as active
                $update_login_stmt = $conn->prepare("UPDATE users SET last_login = NOW(), is_active = 1, last_activity = NOW() WHERE id = ?");
                $update_login_stmt->bind_param("i", $user['id']);
                $update_login_stmt->execute();
                $update_login_stmt->close();
                
                // Set session
                setUserSession($user);
                
                // Set remember me cookie if checked
                if ($remember) {
                    setcookie('remember_user', $username, time() + (86400 * 30), '/');
                }
                
                // Redirect to dashboard
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
        
        $stmt->close();
    }
}

// Get remembered username if exists
$rememberedUser = $_COOKIE['remember_user'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login to PC Hardware Inventory System - ACLC College of Ormoc">
    <meta name="theme-color" content="#1e293b">
    <title>Sign In - PC Hardware Inventory</title>
    
    <!-- Preconnect to CDN for better performance -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Inter Font for better readability -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card" role="main">
            <div class="login-header">
                <img src="<?php echo BASE_PATH; ?>assets/images/logo.png" alt="ACLC College Logo" height="100" class="mb-3">
                <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">PC Hardware Inventory</h1>
                <p class="mb-0" style="font-size: 1rem; font-weight: 500;">ACLC College of Ormoc</p>
                <p class="mb-0 mt-2" style="font-size: 0.875rem; opacity: 0.85;">
                    <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>
                    Sign in to access your account
                </p>
            </div>
            <div class="login-body">
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
                    <div><?php echo escapeOutput($error); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
              
                <form method="POST" action="" class="needs-validation" novalidate aria-label="Login form">
                    <div class="mb-4">
                        <label for="username" class="form-label fw-semibold">
                            <i class="bi bi-person me-1" aria-hidden="true"></i> Username
                        </label>
                        <input type="text" class="form-control form-control-lg" id="username" name="username" 
                               value="<?php echo escapeOutput($rememberedUser); ?>" 
                               placeholder="Enter your username" 
                               autocomplete="username"
                               autofocus
                               required>

                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold">
                            <i class="bi bi-lock me-1" aria-hidden="true"></i> Password
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control form-control-lg" id="password" name="password" 
                                   placeholder="Enter your password" 
                                   autocomplete="current-password"
                                   required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword" 
                                    aria-label="Toggle password visibility" title="Show/Hide password">
                                <i class="bi bi-eye" id="toggleIcon" aria-hidden="true"></i>
                            </button>
                        </div>

                    </div>
                    
                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember" 
                               <?php echo !empty($rememberedUser) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="remember">
                            <i class="bi bi-clock-history me-1" aria-hidden="true"></i>
                            Remember me on this device
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                        <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i> 
                        Sign In
                    </button>
                    
                    <!-- HCI Principle: Visibility - Help text -->
                    <p class="text-center text-muted small mb-0">
                        <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                        Contact your administrator if you need help signing in.
                    </p>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Create loading overlay for login page
        function createLoginLoadingOverlay() {
            if (document.getElementById('loadingOverlay')) return;
            
            const overlayHTML = `
            <div id="loadingOverlay" class="loading-overlay" style="display: none;">
                <div class="loading-content">
                    <div class="spinner-border text-primary loading-spinner" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p id="loadingMessage" class="loading-text mt-3 mb-0">Signing in...</p>
                </div>
            </div>
            <style>
                .loading-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.6);
                    z-index: 9999;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    backdrop-filter: blur(3px);
                }
                .loading-content {
                    text-align: center;
                    background: white;
                    padding: 2rem 3rem;
                    border-radius: 12px;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
                }
                .loading-spinner {
                    width: 3rem;
                    height: 3rem;
                }
                .loading-text {
                    color: #333;
                    font-weight: 500;
                }
            </style>`;
            
            document.body.insertAdjacentHTML('beforeend', overlayHTML);
        }
        
        function showLoginLoading() {
            createLoginLoadingOverlay();
            document.getElementById('loadingOverlay').style.display = 'flex';
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
                    showLoginLoading();
                }, false);
            });
        })();
        
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    </script>
</body>
</html>
