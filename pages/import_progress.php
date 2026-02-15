<?php
/**
 * Import Progress API Endpoint
 * 
 * Returns the current progress of a CSV import job.
 * Designed to be polled every 1 second by the frontend.
 * 
 * Request: GET /pages/import_progress.php?job_id=xxx
 * Response: JSON with status, progress percentage, and counts
 */

header('Content-Type: application/json');

// Validate job_id parameter
if (!isset($_GET['job_id']) || empty($_GET['job_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing job_id parameter'
    ]);
    exit;
}

$jobId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['job_id']); // Sanitize
$progressFile = sys_get_temp_dir() . "/import_progress_{$jobId}.json";

// Check if progress file exists
if (!file_exists($progressFile)) {
    echo json_encode([
        'success' => false,
        'message' => 'Job not found'
    ]);
    exit;
}

// Read progress data
$data = json_decode(file_get_contents($progressFile), true);

if ($data === null) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid progress data'
    ]);
    exit;
}

// Return progress data
echo json_encode([
    'success' => true,
    'status' => $data['status'] ?? 'processing',
    'progress' => $data['progress'] ?? 0,
    'processed' => $data['processed'] ?? 0,
    'total' => $data['total'] ?? 0,
    'imported' => $data['imported'] ?? 0,
    'updated' => $data['updated'] ?? 0,
    'categories_created' => $data['categories_created'] ?? 0,
    'errors' => $data['errors'] ?? [],
    'updated_at' => $data['updated_at'] ?? time()
]);

// Clean up completed jobs after 5 minutes
if (isset($data['status']) && $data['status'] === 'completed') {
    $updatedAt = $data['updated_at'] ?? 0;
    if (time() - $updatedAt > 300) { // 5 minutes
        @unlink($progressFile);
    }
}
