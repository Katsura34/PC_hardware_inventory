<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/security.php';

// Require login
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$conn = getDBConnection();
$file = $_FILES['csvFile']['tmp_name'];
$imported = 0;
$errors = [];

try {
    // Open the CSV file
    if (($handle = fopen($file, 'r')) !== false) {
        // Skip header row
        $header = fgetcsv($handle);
        
        // Validate header
        $expected_headers = ['name', 'category_id', 'type', 'brand', 'model', 'serial_number', 
                           'unused_quantity', 'in_use_quantity', 'damaged_quantity', 'repair_quantity', 'location'];
        
        $line = 1;
        while (($data = fgetcsv($handle)) !== false) {
            $line++;
            
            // Skip empty lines
            if (empty(array_filter($data))) {
                continue;
            }
            
            // Validate minimum required fields
            if (count($data) < 11) {
                $errors[] = "Line $line: Insufficient columns";
                continue;
            }
            
            // Parse data
            $name = sanitizeForDB($conn, trim($data[0]));
            $category_id = (int)$data[1];
            $type = sanitizeForDB($conn, trim($data[2]));
            $brand = sanitizeForDB($conn, trim($data[3]));
            $model = sanitizeForDB($conn, trim($data[4]));
            $serial_number = sanitizeForDB($conn, trim($data[5]));
            $unused_quantity = (int)$data[6];
            $in_use_quantity = (int)$data[7];
            $damaged_quantity = (int)$data[8];
            $repair_quantity = (int)$data[9];
            $location = sanitizeForDB($conn, trim($data[10]));
            $total_quantity = $unused_quantity + $in_use_quantity + $damaged_quantity + $repair_quantity;
            
            // Validate required fields
            if (empty($name)) {
                $errors[] = "Line $line: Name is required";
                continue;
            }
            
            if ($category_id <= 0) {
                $errors[] = "Line $line: Invalid category_id";
                continue;
            }
            
            // Insert into database
            $stmt = $conn->prepare("INSERT INTO hardware (name, category_id, type, brand, model, serial_number, 
                                   total_quantity, unused_quantity, in_use_quantity, damaged_quantity, repair_quantity, location) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sissssiiiiis", $name, $category_id, $type, $brand, $model, $serial_number, 
                             $total_quantity, $unused_quantity, $in_use_quantity, $damaged_quantity, $repair_quantity, $location);
            
            if ($stmt->execute()) {
                $hardware_id = $conn->insert_id;
                
                // Log to history
                $user_id = $_SESSION['user_id'];
                $log_stmt = $conn->prepare("INSERT INTO inventory_history (hardware_id, user_id, action_type, quantity_change, 
                                           old_unused, old_in_use, old_damaged, old_repair, 
                                           new_unused, new_in_use, new_damaged, new_repair) 
                                           VALUES (?, ?, 'Added', ?, 0, 0, 0, 0, ?, ?, ?, ?)");
                $log_stmt->bind_param("iiiiiiii", $hardware_id, $user_id, $total_quantity, 
                                     $unused_quantity, $in_use_quantity, $damaged_quantity, $repair_quantity);
                $log_stmt->execute();
                $log_stmt->close();
                
                $imported++;
            } else {
                $errors[] = "Line $line: Failed to insert - " . $stmt->error;
            }
            $stmt->close();
        }
        
        fclose($handle);
    }
    
    $message = "Successfully imported $imported record(s)";
    if (!empty($errors)) {
        $message .= ". Errors: " . implode("; ", array_slice($errors, 0, 5));
        if (count($errors) > 5) {
            $message .= " and " . (count($errors) - 5) . " more";
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'imported' => $imported,
        'errors' => count($errors)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error processing CSV: ' . $e->getMessage()
    ]);
}
