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

// Get default location if provided
$defaultLocation = isset($_POST['defaultLocation']) ? sanitizeForDB($conn, trim($_POST['defaultLocation'])) : '';

try {
    // Open the CSV file
    if (($handle = fopen($file, 'r')) !== false) {
        // Skip header row
        $header = fgetcsv($handle);
        
        // Validate header - minimum 10 columns required (location is optional if default is set)
        $expected_headers = ['name', 'category_id', 'type', 'brand', 'model', 'serial_number', 
                           'unused_quantity', 'in_use_quantity', 'damaged_quantity', 'repair_quantity', 'location'];
        
        $line = 1;
        while (($data = fgetcsv($handle)) !== false) {
            $line++;
            
            // Skip empty lines
            if (empty(array_filter($data))) {
                continue;
            }
            
            // Validate minimum required fields (10 columns, location can be in column 11 or use default)
            if (count($data) < 10) {
                $errors[] = "Line $line: Insufficient columns (minimum 10 required)";
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
            
            // Use default location if set, otherwise use CSV column 11 if available
            if (!empty($defaultLocation)) {
                $location = $defaultLocation;
            } else {
                $location = isset($data[10]) ? sanitizeForDB($conn, trim($data[10])) : '';
            }
            
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
                
                // Get category name for history logging (denormalized)
                $cat_stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
                $cat_stmt->bind_param("i", $category_id);
                $cat_stmt->execute();
                $cat_result = $cat_stmt->get_result();
                $cat_data = $cat_result->fetch_assoc();
                $category_name = $cat_data ? $cat_data['name'] : 'Unknown';
                $cat_stmt->close();
                
                // Log to history with denormalized data
                $user_id = $_SESSION['user_id'];
                $user_name = $_SESSION['full_name'];
                $log_stmt = $conn->prepare("INSERT INTO inventory_history (hardware_id, hardware_name, category_name, serial_number, 
                                           user_id, user_name, action_type, quantity_change, 
                                           old_unused, old_in_use, old_damaged, old_repair, 
                                           new_unused, new_in_use, new_damaged, new_repair) 
                                           VALUES (?, ?, ?, ?, ?, ?, 'Added', ?, 0, 0, 0, 0, ?, ?, ?, ?)");
                $log_stmt->bind_param("isssisiiiii", $hardware_id, $name, $category_name, $serial_number, 
                                     $user_id, $user_name, $total_quantity, 
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
