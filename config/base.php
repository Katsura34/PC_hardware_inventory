<?php
/**
 * Base Path Configuration
 * 
 * This file defines the BASE_PATH constant dynamically, allowing the application
 * to work both at the document root (/) and inside a subdirectory.
 * 
 * The BASE_PATH is detected using dirname($_SERVER['SCRIPT_NAME']) to find
 * the application's root relative to the web server's document root.
 * 
 * Usage in PHP: BASE_PATH . 'pages/hardware.php'
 * Usage in JS:  window.BASE_PATH is set in the header for client-side scripts
 * 
 * To force a specific base path, set the BASE_PATH constant before including this file:
 *   define('BASE_PATH', '/my-subdirectory/');
 */

if (!defined('BASE_PATH')) {
    // Detect base path dynamically from the current script location
    // This allows the app to run at document root or in a subdirectory
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    
    // Normalize: ensure it ends with a single slash, or is just '/' for root
    if ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') {
        define('BASE_PATH', '/');
    } else {
        // For pages in subdirectories (e.g., /pages/hardware.php), go up to app root
        // We assume the app structure: config/, pages/, includes/, assets/ are at app root
        $parts = explode('/', trim($scriptDir, '/'));
        
        // Remove known subdirectories to get to app root
        $knownSubdirs = ['pages', 'config', 'includes', 'assets'];
        while (!empty($parts) && in_array(end($parts), $knownSubdirs)) {
            array_pop($parts);
        }
        
        $basePath = empty($parts) ? '/' : '/' . implode('/', $parts) . '/';
        define('BASE_PATH', $basePath);
    }
}

// Session and activity tracking constants
if (!defined('ACTIVITY_UPDATE_INTERVAL')) {
    define('ACTIVITY_UPDATE_INTERVAL', 60); // Seconds between activity updates
}
if (!defined('SESSION_TIMEOUT_MINUTES')) {
    define('SESSION_TIMEOUT_MINUTES', 15); // Minutes before user is marked as inactive
}
?>
