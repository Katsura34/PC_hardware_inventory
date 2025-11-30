<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Calculate and store login duration before clearing session, and set user as inactive
if (isLoggedIn() && isset($_SESSION['login_time'])) {
    $user_id = $_SESSION['user_id'];
    $login_time = $_SESSION['login_time'];
    $duration = time() - $login_time; // Duration in seconds
    
    // Update the user's last login duration and set as inactive
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE users SET last_login_duration = ?, is_active = 0 WHERE id = ?");
    $stmt->bind_param("ii", $duration, $user_id);
    $stmt->execute();
    $stmt->close();
} elseif (isLoggedIn()) {
    // If login_time is not set but user is logged in, still set user as inactive
    $user_id = $_SESSION['user_id'];
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

// Clear session and redirect to login
clearSession();
header('Location: login.php');
exit();
?>
