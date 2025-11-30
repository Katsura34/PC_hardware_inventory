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
    <meta name="description" content="PC Hardware Inventory System - ACLC College of Ormoc">
    <meta name="theme-color" content="#1e293b">
    <title><?php echo escapeOutput($pageTitle); ?></title>
    
    <!-- Preconnect to CDN for better performance -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Inter Font for better readability -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>assets/css/style.css">
    
    <!-- HCI Theme Enhancements -->
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>assets/css/theme-hci.css">
    
    <!-- Base path for JavaScript -->
    <script>window.BASE_PATH = '<?php echo BASE_PATH; ?>';</script>
</head>
<body class="<?php echo isLoggedIn() ? 'has-topbar' : ''; ?>">
    
    <?php if (isLoggedIn()): ?>
    <?php
    // Determine active page for highlighting
    $current_page = basename($_SERVER['PHP_SELF']);
    $current_path = $_SERVER['REQUEST_URI'];
    ?>
    
    <!-- Skip to main content link for accessibility -->
    <a href="#main-content" class="skip-link visually-hidden-focusable">Skip to main content</a>
    
    <!-- Top Navigation Bar - HCI: Clean, consistent navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark topbar-nav sticky-top" role="navigation" aria-label="Main navigation">
        <div class="container-fluid px-3 px-lg-4">
            <!-- Brand/Logo -->
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo BASE_PATH; ?>dashboard.php" aria-label="Go to Dashboard">
                <img src="<?php echo BASE_PATH; ?>assets/images/logo.png" alt="ACLC Logo" height="40" class="d-inline-block">
                <span class="d-none d-sm-inline fw-bold">PC Hardware Inventory</span>
                <span class="d-sm-none fw-bold">PC Inventory</span>
            </a>
            
            <!-- Mobile Toggle Button -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" 
                    aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navigation Links -->
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>" 
                           href="<?php echo BASE_PATH; ?>dashboard.php"
                           <?php echo ($current_page === 'dashboard.php') ? 'aria-current="page"' : ''; ?>>
                            <i class="bi bi-speedometer2 me-1" aria-hidden="true"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page === 'hardware.php') ? 'active' : ''; ?>" 
                           href="<?php echo BASE_PATH; ?>pages/hardware.php"
                           <?php echo ($current_page === 'hardware.php') ? 'aria-current="page"' : ''; ?>>
                            <i class="bi bi-cpu me-1" aria-hidden="true"></i>
                            Hardware
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page === 'history.php') ? 'active' : ''; ?>" 
                           href="<?php echo BASE_PATH; ?>pages/history.php"
                           <?php echo ($current_page === 'history.php') ? 'aria-current="page"' : ''; ?>>
                            <i class="bi bi-clock-history me-1" aria-hidden="true"></i>
                            Audit Trail
                        </a>
                    </li>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page === 'users.php') ? 'active' : ''; ?>" 
                           href="<?php echo BASE_PATH; ?>pages/users.php"
                           <?php echo ($current_page === 'users.php') ? 'aria-current="page"' : ''; ?>>
                            <i class="bi bi-people me-1" aria-hidden="true"></i>
                            Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page === 'backup.php') ? 'active' : ''; ?>" 
                           href="<?php echo BASE_PATH; ?>pages/backup.php"
                           <?php echo ($current_page === 'backup.php') ? 'aria-current="page"' : ''; ?>>
                            <i class="bi bi-database me-1" aria-hidden="true"></i>
                            Backup
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Right Side: User Menu -->
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="userDropdown" 
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-avatar-nav">
                                <i class="bi bi-person-fill" aria-hidden="true"></i>
                            </div>
                            <span class="d-none d-md-inline"><?php echo escapeOutput($_SESSION['full_name']); ?></span>
                            <span class="badge-role-nav <?php echo $_SESSION['role'] === 'admin' ? 'badge-admin' : 'badge-staff'; ?>">
                                <?php echo escapeOutput(ucfirst($_SESSION['role'])); ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg" aria-labelledby="userDropdown">
                            <li class="dropdown-header">
                                <div class="fw-semibold"><?php echo escapeOutput($_SESSION['full_name']); ?></div>
                                <small class="text-muted">Logged in as <?php echo escapeOutput($_SESSION['role']); ?></small>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="<?php echo BASE_PATH; ?>pages/profile.php">
                                    <i class="bi bi-person-gear" aria-hidden="true"></i>
                                    Profile Settings
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="#" data-bs-toggle="modal" data-bs-target="#whatsNewModal">
                                    <i class="bi bi-megaphone" aria-hidden="true"></i>
                                    What's New
                                    <span class="badge bg-primary ms-auto">Updates</span>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="<?php echo BASE_PATH; ?>logout.php">
                                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                                    Sign Out
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- What's New Modal -->
    <div class="modal fade" id="whatsNewModal" tabindex="-1" aria-labelledby="whatsNewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="whatsNewModalLabel">
                        <i class="bi bi-megaphone me-2" aria-hidden="true"></i>
                        What's New - System Updates
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Update Log Entries -->
                    <div class="update-log">
                        <!-- Latest Update -->
                        <div class="update-entry mb-4">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-success me-2">NEW</span>
                                <h6 class="mb-0">Version 2.0 - November 2025</h6>
                            </div>
                            <ul class="list-unstyled ms-3">
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    <strong>Profile Settings:</strong> Users can now change their username, full name, and password from the Profile Settings page.
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    <strong>Password Confirmation:</strong> Password changes now require confirmation before being applied.
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    <strong>What's New Section:</strong> Added this updates log so users can see recent system changes.
                                </li>
                            </ul>
                        </div>
                        
                        <hr>
 
                       
                        
                        <!-- Initial Release -->
                        <div class="update-entry">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-secondary me-2">v1.0</span>
                                <h6 class="mb-0">Initial Release</h6>
                            </div>
                            <ul class="list-unstyled ms-3">
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-primary me-2"></i>
                                    <strong>Hardware Inventory:</strong> Add, edit, and manage PC hardware inventory.
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-primary me-2"></i>
                                    <strong>User Management:</strong> Admin can manage staff accounts.
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-primary me-2"></i>
                                    <strong>Audit Trail:</strong> Track all inventory changes with detailed history.
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-primary me-2"></i>
                                    <strong>Backup & Restore:</strong> Database backup functionality for admins.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        <i class="bi bi-check-circle me-1"></i>
                        Got it!
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Main Content Area -->
    <main id="main-content" class="main-content-topbar" role="main">
        <div class="container-fluid py-4">
        <?php
        // Display flash messages - HCI Principle: Feedback
        $flash = getFlashMessage();
        if ($flash):
            $alertClass = 'alert-info';
            $alertIcon = 'bi-info-circle-fill';
            if ($flash['type'] === 'success') {
                $alertClass = 'alert-success';
                $alertIcon = 'bi-check-circle-fill';
            }
            if ($flash['type'] === 'error') {
                $alertClass = 'alert-danger';
                $alertIcon = 'bi-exclamation-triangle-fill';
            }
            if ($flash['type'] === 'warning') {
                $alertClass = 'alert-warning';
                $alertIcon = 'bi-exclamation-circle-fill';
            }
        ?>
        <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="bi <?php echo $alertIcon; ?> me-2" aria-hidden="true"></i>
            <div><?php echo escapeOutput($flash['message']); ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
