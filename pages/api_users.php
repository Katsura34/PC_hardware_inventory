<?php
/**
 * API endpoint to get users data for live table updates
 * Returns JSON with user data for AJAX requests
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/security.php';

// Set JSON header
header('Content-Type: application/json');

// Require admin authentication
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$conn = getDBConnection();

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
$stmt = $conn->prepare("SELECT id, username, full_name, role, date_created, last_login, last_login_duration, is_active, last_activity, session_start FROM users ORDER BY date_created DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Set timezone to Philippines (Asia/Manila, UTC+8) for all datetime operations
$ph_timezone = new DateTimeZone('Asia/Manila');
$current_ph_time = new DateTime('now', $ph_timezone);
$current_ph_timestamp = $current_ph_time->getTimestamp();
$current_user_id = $_SESSION['user_id'];
$timeout_seconds = SESSION_TIMEOUT_MINUTES * 60;

while ($row = $result->fetch_assoc()) {
    // Check if user is active based on is_active flag and last_activity
    $is_user_active = !empty($row['is_active']) && $row['is_active'] == 1;
    
    // Also check if last_activity was within the timeout period for accuracy
    // Use Philippines timezone for consistency
    if ($is_user_active && !empty($row['last_activity'])) {
        $last_activity_datetime = new DateTime($row['last_activity']);
        $last_activity_datetime->setTimezone($ph_timezone);
        $last_activity_timestamp = $last_activity_datetime->getTimestamp();
        if ($current_ph_timestamp - $last_activity_timestamp > $timeout_seconds) {
            $is_user_active = false;
        }
    }
    
    // Calculate session start timestamp for live session duration (Philippines time)
    $session_start_epoch = null;
    $server_now_epoch = $current_ph_timestamp;
    $login_timestamp_ms = null;
    $login_display = null;
    if ($is_user_active && !empty($row['session_start'])) {
        $session_datetime = new DateTime($row['session_start']);
        $session_datetime->setTimezone($ph_timezone);
        $session_start_epoch = $session_datetime->getTimestamp();
        $login_timestamp_ms = $session_start_epoch * 1000;
        $login_display = $session_datetime->format('M d, Y H:i');
    }
    
    // Format last login duration for offline users
    $login_duration_display = null;
    if (!empty($row['last_login_duration'])) {
        $duration_seconds = (int)$row['last_login_duration'];
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
    
    // Format last login for display using Philippines timezone
    $last_login_display = null;
    if (!empty($row['last_login'])) {
        $last_login_dt = new DateTime($row['last_login']);
        $last_login_dt->setTimezone($ph_timezone);
        $last_login_display = $last_login_dt->format('M d, Y H:i');
    }
    
    // Format date_created using Philippines timezone
    $date_created_dt = new DateTime($row['date_created']);
    $date_created_dt->setTimezone($ph_timezone);
    $date_created_display = $date_created_dt->format('M d, Y');
    
    $users[] = [
        'id' => (int)$row['id'],
        'username' => $row['username'],
        'full_name' => $row['full_name'],
        'role' => $row['role'],
        'date_created' => $date_created_display,
        'last_login' => $row['last_login'],
        'last_login_display' => $last_login_display,
        'last_login_duration' => $row['last_login_duration'],
        'last_login_duration_display' => $login_duration_display,
        'is_active' => $is_user_active,
        'login_timestamp_ms' => $login_timestamp_ms,
        'login_display' => $login_display,
        'session_start_epoch' => $session_start_epoch,
        'server_now_epoch' => $server_now_epoch,
        'is_current_user' => ($row['id'] == $current_user_id)
    ];
}
$stmt->close();

// Return JSON response
echo json_encode([
    'success' => true,
    'users' => $users,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_records' => $total_records,
        'records_per_page' => $records_per_page,
        'offset' => $offset
    ],
    'server_time' => $current_ph_timestamp * 1000
]);
?>
