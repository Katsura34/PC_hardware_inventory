<?php
// Include base path configuration
require_once __DIR__ . '/base.php';

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    // Secure session configuration
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    ini_set('session.cookie_samesite', 'Strict');
    
    session_start();
    
    // Regenerate session ID periodically to prevent session fixation
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) {
        // Session started more than 30 minutes ago
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Check if user is admin
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_PATH . 'login.php');
        exit();
    }
    // Update user's last activity timestamp
    updateUserActivity();
}

// Require admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_PATH . 'dashboard.php');
        exit();
    }
}

// Get current user
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role' => $_SESSION['role']
    ];
}

// Set session user data
function setUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['login_time'] = time(); // Track when user logged in
    session_regenerate_id(true);
}

// Update user's last activity timestamp (called on each page request)
function updateUserActivity() {
    if (!isLoggedIn()) {
        return;
    }
    
    // Only update every ACTIVITY_UPDATE_INTERVAL seconds to reduce database load
    $last_update = $_SESSION['last_activity_update'] ?? 0;
    if (time() - $last_update < ACTIVITY_UPDATE_INTERVAL) {
        return;
    }
    
    require_once __DIR__ . '/database.php';
    $conn = getDBConnection();
    
    // Update last activity timestamp
    $stmt = $conn->prepare("UPDATE users SET last_activity = NOW(), is_active = 1 WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    
    $_SESSION['last_activity_update'] = time();
    
    // Also mark users as inactive if they haven't had activity within timeout period
    // Use prepared statement for safety
    $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE last_activity < DATE_SUB(NOW(), INTERVAL ? MINUTE) AND is_active = 1");
    $timeout_minutes = SESSION_TIMEOUT_MINUTES;
    $stmt->bind_param("i", $timeout_minutes);
    $stmt->execute();
    $stmt->close();
}

// Clear session
function clearSession() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}
?>
