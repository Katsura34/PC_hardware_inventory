#!/usr/bin/env php
<?php
/**
 * Background CSV Import Job Processor
 * 
 * This script processes CSV imports asynchronously in the background.
 * It's designed to handle 10,000+ records efficiently with:
 * - CSV streaming (SplFileObject)
 * - Batch inserts (1000 per batch)
 * - Single query preload of existing hardware
 * - Progress tracking to file
 * - Database transactions
 * 
 * Usage: php process_import_job.php <job_id> <csv_path> <user_id> <user_name> <default_location>
 */

// Prevent direct browser access
if (php_sapi_name() !== 'cli') {
    die('This script must be run from command line');
}

// Get command line arguments
if ($argc < 5) {
    die("Usage: php process_import_job.php <job_id> <csv_path> <user_id> <user_name> [default_location]\n");
}

$jobId = $argv[1];
$csvPath = $argv[2];
$userId = (int)$argv[3];
$userName = $argv[4];
$defaultLocation = isset($argv[5]) ? $argv[5] : '';

// Define paths
define('BASE_DIR', dirname(__DIR__));
$progressFile = sys_get_temp_dir() . "/import_progress_{$jobId}.json";

// Include required files
require_once BASE_DIR . '/config/database.php';

/**
 * Update progress file
 */
function updateProgress($jobId, $status, $progress, $processed, $total, $imported = 0, $updated = 0, $categoriesCreated = 0, $errors = []) {
    $progressFile = sys_get_temp_dir() . "/import_progress_{$jobId}.json";
    $data = [
        'status' => $status,           // 'processing', 'completed', 'failed'
        'progress' => $progress,       // 0-100
        'processed' => $processed,     // Rows processed
        'total' => $total,             // Total rows
        'imported' => $imported,       // New records
        'updated' => $updated,         // Updated records
        'categories_created' => $categoriesCreated,
        'errors' => $errors,
        'updated_at' => time()
    ];
    file_put_contents($progressFile, json_encode($data, JSON_PRETTY_PRINT));
}

try {
    // Initialize progress
    updateProgress($jobId, 'processing', 0, 0, 0);
    
    // Validate CSV file exists
    if (!file_exists($csvPath)) {
        updateProgress($jobId, 'failed', 0, 0, 0, 0, 0, 0, ['CSV file not found']);
        exit(1);
    }
    
    // Get database connection
    $conn = getDBConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    // Count total lines for progress tracking (quick scan)
    $totalLines = 0;
    $file = new SplFileObject($csvPath, 'r');
    $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
    while (!$file->eof()) {
        $file->current();
        $totalLines++;
        $file->next();
    }
    $totalLines = max(0, $totalLines - 1); // Subtract header row
    
    // Reset file pointer
    $file = null;
    $file = new SplFileObject($csvPath, 'r');
    $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
    
    // Skip header row
    $file->current();
    $file->next();
    
    // Preload category map (single query)
    $categoryMap = [];
    $catResult = $conn->query("SELECT id, name FROM categories");
    while ($catRow = $catResult->fetch_assoc()) {
        $categoryMap[strtolower(trim($catRow['name']))] = $catRow['id'];
    }
    
    // Preload existing hardware into a hash map for O(1) lookups
    // Key: name|serial|brand|category_id
    $existingHardware = [];
    $hwResult = $conn->query("SELECT id, name, serial_number, brand, category_id, 
                              unused_quantity, in_use_quantity, damaged_quantity, repair_quantity, total_quantity 
                              FROM hardware WHERE deleted_at IS NULL");
    while ($hwRow = $hwResult->fetch_assoc()) {
        $key = strtolower(trim($hwRow['name'])) . '|' . 
               strtolower(trim($hwRow['serial_number'])) . '|' . 
               strtolower(trim($hwRow['brand'])) . '|' . 
               $hwRow['category_id'];
        $existingHardware[$key] = $hwRow;
    }
    
    // Batch processing variables
    $batchSize = 1000;
    $insertBatch = [];
    $updateBatch = [];
    $historyBatch = [];
    
    $imported = 0;
    $updated = 0;
    $categoriesCreated = 0;
    $errors = [];
    $processed = 0;
    $line = 1; // Start at line 1 (data line, header is line 0)
    
    // Process CSV in streaming mode
    while (!$file->eof()) {
        $data = $file->current();
        $file->next();
        $line++;
        
        // Skip empty rows
        if (empty($data) || empty(array_filter($data))) {
            continue;
        }
        
        // Validate minimum columns
        if (count($data) < 10) {
            $errors[] = "Line $line: Insufficient columns (minimum 10 required)";
            continue;
        }
        
        // Parse data
        $name = trim($data[0]);
        $categoryValue = trim($data[1]);
        $type = trim($data[2]);
        $brand = trim($data[3]);
        $model = trim($data[4]);
        $serialNumber = trim($data[5]);
        $unusedQty = (int)$data[6];
        $inUseQty = (int)$data[7];
        $damagedQty = (int)$data[8];
        $repairQty = (int)$data[9];
        $location = !empty($defaultLocation) ? $defaultLocation : (isset($data[10]) ? trim($data[10]) : '');
        $totalQty = $unusedQty + $inUseQty + $damagedQty + $repairQty;
        
        // Validate required fields
        if (empty($name)) {
            $errors[] = "Line $line: Name is required";
            continue;
        }
        
        // Handle category (ID or name)
        $categoryId = 0;
        if (is_numeric($categoryValue)) {
            $categoryId = (int)$categoryValue;
        } else {
            $categoryKey = strtolower($categoryValue);
            if (isset($categoryMap[$categoryKey])) {
                $categoryId = $categoryMap[$categoryKey];
            } else {
                // Create new category
                if (!empty($categoryValue)) {
                    $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                    $desc = "Auto-created from CSV import";
                    $stmt->bind_param("ss", $categoryValue, $desc);
                    if ($stmt->execute()) {
                        $categoryId = $conn->insert_id;
                        $categoryMap[$categoryKey] = $categoryId;
                        $categoriesCreated++;
                    } else {
                        $errors[] = "Line $line: Failed to create category '$categoryValue'";
                        $stmt->close();
                        continue;
                    }
                    $stmt->close();
                } else {
                    $errors[] = "Line $line: Category is required";
                    continue;
                }
            }
        }
        
        if ($categoryId <= 0) {
            $errors[] = "Line $line: Invalid category";
            continue;
        }
        
        // Check for existing hardware using preloaded map
        $lookupKey = strtolower($name) . '|' . strtolower($serialNumber) . '|' . strtolower($brand) . '|' . $categoryId;
        
        if (isset($existingHardware[$lookupKey])) {
            // Update existing hardware
            $existing = $existingHardware[$lookupKey];
            $newUnused = $existing['unused_quantity'] + $unusedQty;
            $newInUse = $existing['in_use_quantity'] + $inUseQty;
            $newDamaged = $existing['damaged_quantity'] + $damagedQty;
            $newRepair = $existing['repair_quantity'] + $repairQty;
            $newTotal = $newUnused + $newInUse + $newDamaged + $newRepair;
            
            $updateBatch[] = [
                'id' => $existing['id'],
                'unused' => $newUnused,
                'in_use' => $newInUse,
                'damaged' => $newDamaged,
                'repair' => $newRepair,
                'total' => $newTotal
            ];
            
            // Get category name for history
            $categoryName = array_search($categoryId, $categoryMap);
            if ($categoryName === false) {
                $categoryName = 'Unknown';
            } else {
                // Convert back to original case
                foreach ($categoryMap as $catName => $catId) {
                    if ($catId === $categoryId) {
                        $categoryName = $catName;
                        break;
                    }
                }
            }
            
            $historyBatch[] = [
                'hardware_id' => $existing['id'],
                'hardware_name' => $name,
                'category_name' => $categoryName,
                'serial_number' => $serialNumber,
                'user_id' => $userId,
                'user_name' => $userName,
                'action_type' => 'Updated',
                'quantity_change' => $totalQty,
                'old_unused' => $existing['unused_quantity'],
                'old_in_use' => $existing['in_use_quantity'],
                'old_damaged' => $existing['damaged_quantity'],
                'old_repair' => $existing['repair_quantity'],
                'new_unused' => $newUnused,
                'new_in_use' => $newInUse,
                'new_damaged' => $newDamaged,
                'new_repair' => $newRepair
            ];
            
            $updated++;
        } else {
            // Insert new hardware
            $insertBatch[] = [
                'name' => $name,
                'category_id' => $categoryId,
                'type' => $type,
                'brand' => $brand,
                'model' => $model,
                'serial_number' => $serialNumber,
                'total_quantity' => $totalQty,
                'unused_quantity' => $unusedQty,
                'in_use_quantity' => $inUseQty,
                'damaged_quantity' => $damagedQty,
                'repair_quantity' => $repairQty,
                'location' => $location
            ];
            
            $imported++;
        }
        
        $processed++;
        
        // Execute batch operations when batch size is reached
        if (count($insertBatch) >= $batchSize || count($updateBatch) >= $batchSize) {
            executeBatchOperations($conn, $insertBatch, $updateBatch, $historyBatch, $userId, $userName, $categoryMap);
            $insertBatch = [];
            $updateBatch = [];
            $historyBatch = [];
        }
        
        // Update progress every 100 rows
        if ($processed % 100 === 0) {
            $progress = $totalLines > 0 ? min(99, round(($processed / $totalLines) * 100)) : 0;
            updateProgress($jobId, 'processing', $progress, $processed, $totalLines, $imported, $updated, $categoriesCreated, array_slice($errors, 0, 10));
        }
    }
    
    // Execute remaining batch operations
    if (!empty($insertBatch) || !empty($updateBatch)) {
        executeBatchOperations($conn, $insertBatch, $updateBatch, $historyBatch, $userId, $userName, $categoryMap);
    }
    
    // Commit transaction
    $conn->commit();
    
    // Update final progress
    updateProgress($jobId, 'completed', 100, $processed, $totalLines, $imported, $updated, $categoriesCreated, array_slice($errors, 0, 10));
    
    // Clean up CSV file
    if (file_exists($csvPath)) {
        @unlink($csvPath);
    }
    
    exit(0);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollback();
    }
    
    $errors[] = 'Fatal error: ' . $e->getMessage();
    updateProgress($jobId, 'failed', 0, $processed ?? 0, $totalLines ?? 0, $imported ?? 0, $updated ?? 0, $categoriesCreated ?? 0, $errors);
    
    // Clean up CSV file
    if (isset($csvPath) && file_exists($csvPath)) {
        @unlink($csvPath);
    }
    
    exit(1);
}

/**
 * Execute batch insert and update operations
 */
function executeBatchOperations($conn, &$insertBatch, &$updateBatch, &$historyBatch, $userId, $userName, $categoryMap) {
    // Execute batch inserts
    if (!empty($insertBatch)) {
        $values = [];
        $params = [];
        $types = '';
        
        foreach ($insertBatch as $item) {
            $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params[] = $item['name'];
            $params[] = $item['category_id'];
            $params[] = $item['type'];
            $params[] = $item['brand'];
            $params[] = $item['model'];
            $params[] = $item['serial_number'];
            $params[] = $item['total_quantity'];
            $params[] = $item['unused_quantity'];
            $params[] = $item['in_use_quantity'];
            $params[] = $item['damaged_quantity'];
            $params[] = $item['repair_quantity'];
            $params[] = $item['location'];
            $types .= 'sissssiiiiis';
        }
        
        if (!empty($values)) {
            $sql = "INSERT INTO hardware (name, category_id, type, brand, model, serial_number, 
                    total_quantity, unused_quantity, in_use_quantity, damaged_quantity, repair_quantity, location) 
                    VALUES " . implode(', ', $values);
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->close();
            
            // Add history records for new inserts
            $firstInsertId = $conn->insert_id;
            foreach ($insertBatch as $idx => $item) {
                $hardwareId = $firstInsertId + $idx;
                
                // Get category name
                $categoryName = 'Unknown';
                foreach ($categoryMap as $catName => $catId) {
                    if ($catId === $item['category_id']) {
                        $categoryName = $catName;
                        break;
                    }
                }
                
                $historyBatch[] = [
                    'hardware_id' => $hardwareId,
                    'hardware_name' => $item['name'],
                    'category_name' => $categoryName,
                    'serial_number' => $item['serial_number'],
                    'user_id' => $userId,
                    'user_name' => $userName,
                    'action_type' => 'Added',
                    'quantity_change' => $item['total_quantity'],
                    'old_unused' => 0,
                    'old_in_use' => 0,
                    'old_damaged' => 0,
                    'old_repair' => 0,
                    'new_unused' => $item['unused_quantity'],
                    'new_in_use' => $item['in_use_quantity'],
                    'new_damaged' => $item['damaged_quantity'],
                    'new_repair' => $item['repair_quantity']
                ];
            }
        }
    }
    
    // Execute batch updates
    if (!empty($updateBatch)) {
        foreach ($updateBatch as $item) {
            $stmt = $conn->prepare("UPDATE hardware SET 
                                    unused_quantity = ?, in_use_quantity = ?, damaged_quantity = ?, 
                                    repair_quantity = ?, total_quantity = ? 
                                    WHERE id = ?");
            $stmt->bind_param("iiiiii", $item['unused'], $item['in_use'], $item['damaged'], 
                             $item['repair'], $item['total'], $item['id']);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Execute batch history inserts
    if (!empty($historyBatch)) {
        $values = [];
        $params = [];
        $types = '';
        
        foreach ($historyBatch as $hist) {
            $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params[] = $hist['hardware_id'];
            $params[] = $hist['hardware_name'];
            $params[] = $hist['category_name'];
            $params[] = $hist['serial_number'];
            $params[] = $hist['user_id'];
            $params[] = $hist['user_name'];
            $params[] = $hist['action_type'];
            $params[] = $hist['quantity_change'];
            $params[] = $hist['old_unused'];
            $params[] = $hist['old_in_use'];
            $params[] = $hist['old_damaged'];
            $params[] = $hist['old_repair'];
            $params[] = $hist['new_unused'];
            $params[] = $hist['new_in_use'];
            $params[] = $hist['new_damaged'];
            $params[] = $hist['new_repair'];
            $types .= 'isssisiiiiiiiii';
        }
        
        if (!empty($values)) {
            $sql = "INSERT INTO inventory_history (hardware_id, hardware_name, category_name, serial_number, 
                    user_id, user_name, action_type, quantity_change, 
                    old_unused, old_in_use, old_damaged, old_repair, 
                    new_unused, new_in_use, new_damaged, new_repair) 
                    VALUES " . implode(', ', $values);
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->close();
        }
    }
}
