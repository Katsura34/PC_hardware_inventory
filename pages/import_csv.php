<?php
/**
 * CSV Import Controller - Optimized for Background Processing
 * 
 * This controller now dispatches CSV imports to a background job
 * instead of processing them synchronously. This enables:
 * - Non-blocking UI
 * - Real-time progress updates
 * - Better performance for large files (10,000+ rows)
 */

// Suppress PHP errors from outputting HTML - capture them instead
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start output buffering to catch any unexpected output
ob_start();

// Custom error handler to capture errors and return JSON
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Shutdown handler to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    $fatal_error_types = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if ($error !== null && in_array($error['type'], $fatal_error_types)) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'A server error occurred while processing the CSV file. Please try again or contact support if the problem persists.'
        ]);
    }
});

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/security.php';

// Require login
requireLogin();

// Clear any output that might have been generated during includes
ob_clean();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

try {
    // Generate unique job ID
    $jobId = uniqid('import_', true);
    
    // Save uploaded file to temporary location
    $uploadedFile = $_FILES['csvFile']['tmp_name'];
    $tempDir = sys_get_temp_dir();
    $csvPath = $tempDir . "/csv_import_{$jobId}.csv";
    
    if (!move_uploaded_file($uploadedFile, $csvPath)) {
        throw new Exception('Failed to save uploaded file');
    }
    
    // Get user info and default location
    $userId = $_SESSION['user_id'];
    $userName = $_SESSION['full_name'];
    $defaultLocation = isset($_POST['defaultLocation']) ? trim($_POST['defaultLocation']) : '';
    
    // Build command to execute background job
    $phpBinary = PHP_BINARY;
    $scriptPath = __DIR__ . '/process_import_job.php';
    
    // Escape arguments for shell
    $jobIdEsc = escapeshellarg($jobId);
    $csvPathEsc = escapeshellarg($csvPath);
    $userIdEsc = escapeshellarg($userId);
    $userNameEsc = escapeshellarg($userName);
    $defaultLocationEsc = escapeshellarg($defaultLocation);
    
    // Execute background process (platform-specific)
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows: use start /B for background execution
        $command = "start /B \"\" \"$phpBinary\" \"$scriptPath\" $jobIdEsc $csvPathEsc $userIdEsc $userNameEsc $defaultLocationEsc > NUL 2>&1";
        pclose(popen($command, 'r'));
    } else {
        // Unix/Linux: use nohup and redirect output
        $command = "nohup \"$phpBinary\" \"$scriptPath\" $jobIdEsc $csvPathEsc $userIdEsc $userNameEsc $defaultLocationEsc > /dev/null 2>&1 &";
        exec($command);
    }
    
    // Return job ID immediately to frontend
    echo json_encode([
        'success' => true,
        'job_id' => $jobId,
        'message' => 'Import job started. Please wait for completion...'
    ]);
    
} catch (Exception $e) {
    if (ob_get_level()) {
        ob_clean();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Error starting import: ' . $e->getMessage()
    ]);
}

// End output buffering and send response
ob_end_flush();
