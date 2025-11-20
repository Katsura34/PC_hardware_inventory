<?php
if (!isset($pageTitle)) {
    $pageTitle = 'PC Hardware Inventory';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escapeOutput($pageTitle); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/PC_hardware_inventory/assets/css/style.css">
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <?php
    // Determine active page for highlighting
    $current_page = basename($_SERVER['PHP_SELF']);
    $current_path = $_SERVER['REQUEST_URI'];
    ?>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="/PC_hardware_inventory/dashboard.php">
                <img src="/PC_hardware_inventory/assets/images/logo.svg" alt="Logo" height="32" class="me-2">
                <span>PC Inventory - ACLC Ormoc</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>" href="/PC_hardware_inventory/dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page === 'hardware.php') ? 'active' : ''; ?>" href="/PC_hardware_inventory/pages/hardware.php">
                            <i class="bi bi-cpu"></i> Hardware
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page === 'history.php') ? 'active' : ''; ?>" href="/PC_hardware_inventory/pages/history.php">
                            <i class="bi bi-clock-history"></i> History
                        </a>
                    </li>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page === 'users.php') ? 'active' : ''; ?>" href="/PC_hardware_inventory/pages/users.php">
                            <i class="bi bi-people"></i> Users
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav align-items-lg-center">
                    <!-- Location Dropdown Filter -->
                    <li class="nav-item dropdown me-2">
                        <a class="nav-link dropdown-toggle" href="#" id="locationDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-geo-alt"></i> <span class="d-lg-inline">Location</span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item location-filter" href="#" data-location="all">All Locations</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item location-filter" href="#" data-location="Lab 1">Lab 1</a></li>
                            <li><a class="dropdown-item location-filter" href="#" data-location="Lab 2">Lab 2</a></li>
                            <li><a class="dropdown-item location-filter" href="#" data-location="Lab 3">Lab 3</a></li>
                            <li><a class="dropdown-item location-filter" href="#" data-location="Lab 4">Lab 4</a></li>
                            <li><a class="dropdown-item location-filter" href="#" data-location="Office">Office</a></li>
                            <li><a class="dropdown-item location-filter" href="#" data-location="Storage">Storage</a></li>
                        </ul>
                    </li>
                    <!-- CSV Import Button -->
                    <li class="nav-item me-2">
                        <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#importCSVModal">
                            <i class="bi bi-upload"></i> <span class="d-none d-lg-inline">Import CSV</span>
                        </button>
                    </li>
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo escapeOutput($_SESSION['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text"><small class="text-muted">Role: <?php echo escapeOutput(ucfirst($_SESSION['role'])); ?></small></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/PC_hardware_inventory/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- CSV Import Modal -->
    <div class="modal fade" id="importCSVModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-upload"></i> Import Hardware from CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="importCSVForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <strong>CSV Format:</strong> name, category_id, type, brand, model, serial_number, unused_quantity, in_use_quantity, damaged_quantity, repair_quantity, location
                        </div>
                        <div class="mb-3">
                            <label for="csvFile" class="form-label">Select CSV File</label>
                            <input type="file" class="form-control" id="csvFile" name="csvFile" accept=".csv" required>
                        </div>
                        <div id="importPreview" class="d-none">
                            <h6>Preview (First 5 rows):</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered" id="previewTable">
                                    <thead></thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="importBtn">
                            <i class="bi bi-upload"></i> Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="container-fluid py-4">
        <?php
        // Display flash messages
        $flash = getFlashMessage();
        if ($flash):
            $alertClass = 'alert-info';
            if ($flash['type'] === 'success') $alertClass = 'alert-success';
            if ($flash['type'] === 'error') $alertClass = 'alert-danger';
            if ($flash['type'] === 'warning') $alertClass = 'alert-warning';
        ?>
        <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
            <?php echo escapeOutput($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
