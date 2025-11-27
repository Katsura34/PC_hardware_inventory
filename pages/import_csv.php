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
$updated = 0;
$errors = [];

// Get default location if provided
$defaultLocation = isset($_POST['defaultLocation']) ? sanitizeForDB($conn, trim($_POST['defaultLocation'])) : '';

try {
    // Open the CSV file
    if (($handle = fopen($file, 'r')) !== false) {
        // Skip header row
        $header = fgetcsv($handle);
        
        // Expected CSV format (header row is skipped, data is processed by column position)
        // Column 2 (index 1) accepts either category name (e.g., "CPU", "RAM") or category_id (numeric)
        // This allows staff to use friendly category names instead of memorizing IDs
        $expected_headers = ['name', 'category', 'type', 'brand', 'model', 'serial_number', 
                           'unused_quantity', 'in_use_quantity', 'damaged_quantity', 'repair_quantity', 'location'];
        
        // Build a lookup map for category names to IDs
        $category_map = [];
        $cat_result = $conn->query("SELECT id, name FROM categories");
        while ($cat_row = $cat_result->fetch_assoc()) {
            $category_map[strtolower(trim($cat_row['name']))] = $cat_row['id'];
        }
        
        $line = 1;
        while (($data = fgetcsv($handle)) !== false) {
            $line++;
            
            // Skip empty lines
            if (empty(array_filter($data))) {
                continue;
            }
            
            // Validate minimum required fields (10 columns minimum: name through repair_quantity; location is optional as column 11 or via default selection)
            if (count($data) < 10) {
                $errors[] = "Line $line: Insufficient columns (minimum 10 required)";
                continue;
            }
            
            // Parse data
            $name = sanitizeForDB($conn, trim($data[0]));
            
            // Handle category - support both ID (numeric) and name (text)
            $category_value = trim($data[1]);
            if (is_numeric($category_value)) {
                // If it's a number, use it as category_id directly
                $category_id = (int)$category_value;
            } else {
                // If it's text, look up the category by name (case-insensitive)
                $category_key = strtolower($category_value);
                if (isset($category_map[$category_key])) {
                    $category_id = $category_map[$category_key];
                } else {
                    $errors[] = "Line $line: Unknown category '$category_value'";
                    continue;
                }
            }
            
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
            
            // Check for duplicate: same name, serial_number, brand, and category
            $check_stmt = $conn->prepare("SELECT id, unused_quantity, in_use_quantity, damaged_quantity, repair_quantity, total_quantity 
                                          FROM hardware 
                                          WHERE name = ? AND serial_number = ? AND brand = ? AND category_id = ? AND deleted_at IS NULL");
            $check_stmt->bind_param("sssi", $name, $serial_number, $brand, $category_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $existing = $check_result->fetch_assoc();
            $check_stmt->close();
            
            if ($existing) {
                // Duplicate found - add quantities to existing hardware
                $new_unused = $existing['unused_quantity'] + $unused_quantity;
                $new_in_use = $existing['in_use_quantity'] + $in_use_quantity;
                $new_damaged = $existing['damaged_quantity'] + $damaged_quantity;
                $new_repair = $existing['repair_quantity'] + $repair_quantity;
                $new_total = $new_unused + $new_in_use + $new_damaged + $new_repair;
                
                $update_stmt = $conn->prepare("UPDATE hardware SET 
                                              unused_quantity = ?, in_use_quantity = ?, damaged_quantity = ?, repair_quantity = ?, total_quantity = ?
                                              WHERE id = ?");
                $update_stmt->bind_param("iiiiii", $new_unused, $new_in_use, $new_damaged, $new_repair, $new_total, $existing['id']);
                
                if ($update_stmt->execute()) {
                    // Get category name for history logging
                    $cat_stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
                    $cat_stmt->bind_param("i", $category_id);
                    $cat_stmt->execute();
                    $cat_result = $cat_stmt->get_result();
                    $cat_data = $cat_result->fetch_assoc();
                    $category_name = $cat_data ? $cat_data['name'] : 'Unknown';
                    $cat_stmt->close();
                    
                    // Log to history
                    $user_id = $_SESSION['user_id'];
                    $user_name = $_SESSION['full_name'];
                    $quantity_change = $total_quantity; // Added quantity
                    
                    $log_stmt = $conn->prepare("INSERT INTO inventory_history (hardware_id, hardware_name, category_name, serial_number, 
                                               user_id, user_name, action_type, quantity_change, 
                                               old_unused, old_in_use, old_damaged, old_repair, 
                                               new_unused, new_in_use, new_damaged, new_repair) 
                                               VALUES (?, ?, ?, ?, ?, ?, 'Updated', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $log_stmt->bind_param("isssisiiiiiiiii", $existing['id'], $name, $category_name, $serial_number, 
                                         $user_id, $user_name, $quantity_change, 
                                         $existing['unused_quantity'], $existing['in_use_quantity'], 
                                         $existing['damaged_quantity'], $existing['repair_quantity'],
                                         $new_unused, $new_in_use, $new_damaged, $new_repair);
                    $log_stmt->execute();
                    $log_stmt->close();
                    
                    $updated++;
                } else {
                    $errors[] = "Line $line: Failed to update existing item - " . $update_stmt->error;
                }
                $update_stmt->close();
            } else {
                // No duplicate - insert new hardware
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
        }
        
        fclose($handle);
    }
    
    $message = "Successfully imported $imported new record(s)";
    if ($updated > 0) {
        $message .= ", updated $updated existing record(s) (duplicates had quantities added)";
    }
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
        'updated' => $updated,
        'errors' => count($errors)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error processing CSV: ' . $e->getMessage()
    ]);
}
