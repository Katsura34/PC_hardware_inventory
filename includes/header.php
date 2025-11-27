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
    
    <!-- Base path for JavaScript -->
    <script>window.BASE_PATH = '<?php echo BASE_PATH; ?>';</script>
</head>
<body class="<?php echo isLoggedIn() ? 'has-sidebar' : ''; ?>">
    
    <?php if (isLoggedIn()): ?>
    <?php
    // Determine active page for highlighting
    $current_page = basename($_SERVER['PHP_SELF']);
    $current_path = $_SERVER['REQUEST_URI'];
    ?>
    
    <!-- Mobile Top Bar -->
    <nav class="mobile-topbar d-lg-none" role="navigation" aria-label="Mobile navigation">
        <div class="d-flex justify-content-between align-items-center w-100">
            <button class="btn btn-link text-white p-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Open menu">
                <i class="bi bi-list" style="font-size: 1.5rem;"></i>
            </button>
            <a class="navbar-brand d-flex align-items-center" href="<?php echo BASE_PATH; ?>dashboard.php" aria-label="Go to Dashboard">
                <img src="<?php echo BASE_PATH; ?>assets/images/logo.png" alt="ACLC Logo" height="36" class="me-2">
                <span>PC Inventory</span>
            </a>
            <div class="dropdown">
                <button class="btn btn-link text-white p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="User menu">
                    <i class="bi bi-person-circle" style="font-size: 1.5rem;"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                    <li class="px-3 py-2">
                        <div class="fw-semibold"><?php echo escapeOutput($_SESSION['full_name']); ?></div>
                        <small class="text-muted">
                            <span class="badge <?php echo $_SESSION['role'] === 'admin' ? 'bg-danger' : 'bg-secondary'; ?>">
                                <?php echo escapeOutput(ucfirst($_SESSION['role'])); ?>
                            </span>
                        </small>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="<?php echo BASE_PATH; ?>logout.php">
                            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                            <span>Sign Out</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Mobile Off-canvas Sidebar -->
    <div class="offcanvas offcanvas-start sidebar-offcanvas d-lg-none" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
        <div class="offcanvas-header">
            <div class="d-flex align-items-center">
                <img src="<?php echo BASE_PATH; ?>assets/images/logo.png" alt="ACLC Logo" height="40" class="me-2">
                <h5 class="offcanvas-title mb-0" id="mobileSidebarLabel">PC Inventory</h5>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>" 
                           href="<?php echo BASE_PATH; ?>dashboard.php"
                           <?php echo ($current_page === 'dashboard.php') ? 'aria-current="page"' : ''; ?>>
                            <i class="bi bi-speedometer2" aria-hidden="true"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page === 'hardware.php') ? 'active' : ''; ?>" 
                           href="<?php echo BASE_PATH; ?>pages/hardware.php"
                           <?php echo ($current_page === 'hardware.php') ? 'aria-current="page"' : ''; ?>>
                            <i class="bi bi-cpu" aria-hidden="true"></i>
                            <span>Hardware</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page === 'history.php') ? 'active' : ''; ?>" 
                           href="<?php echo BASE_PATH; ?>pages/history.php"
                           <?php echo ($current_page === 'history.php') ? 'aria-current="page"' : ''; ?>>
                            <i class="bi bi-clock-history" aria-hidden="true"></i>
                            <span>History</span>
                        </a>
                    </li>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page === 'users.php') ? 'active' : ''; ?>" 
                           href="<?php echo BASE_PATH; ?>pages/users.php"
                           <?php echo ($current_page === 'users.php') ? 'aria-current="page"' : ''; ?>>
                            <i class="bi bi-people" aria-hidden="true"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo escapeOutput($_SESSION['full_name']); ?></div>
                        <span class="badge <?php echo $_SESSION['role'] === 'admin' ? 'bg-danger' : 'bg-secondary'; ?> badge-sm">
                            <?php echo escapeOutput(ucfirst($_SESSION['role'])); ?>
                        </span>
                    </div>
                </div>
                <a href="<?php echo BASE_PATH; ?>logout.php" class="btn btn-outline-light btn-sm w-100 mt-3">
                    <i class="bi bi-box-arrow-right me-1"></i> Sign Out
                </a>
            </div>
        </div>
    </div>
    
    <!-- Desktop Sidebar -->
    <aside class="sidebar d-none d-lg-flex" role="navigation" aria-label="Main navigation">
        <div class="sidebar-header">
            <a href="<?php echo BASE_PATH; ?>dashboard.php" class="sidebar-brand">
                <img src="<?php echo BASE_PATH; ?>assets/images/logo.png" alt="ACLC Logo" height="45">
                <span>PC Inventory</span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>" 
                       href="<?php echo BASE_PATH; ?>dashboard.php"
                       <?php echo ($current_page === 'dashboard.php') ? 'aria-current="page"' : ''; ?>>
                        <i class="bi bi-speedometer2" aria-hidden="true"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'hardware.php') ? 'active' : ''; ?>" 
                       href="<?php echo BASE_PATH; ?>pages/hardware.php"
                       <?php echo ($current_page === 'hardware.php') ? 'aria-current="page"' : ''; ?>>
                        <i class="bi bi-cpu" aria-hidden="true"></i>
                        <span>Hardware</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'history.php') ? 'active' : ''; ?>" 
                       href="<?php echo BASE_PATH; ?>pages/history.php"
                       <?php echo ($current_page === 'history.php') ? 'aria-current="page"' : ''; ?>>
                        <i class="bi bi-clock-history" aria-hidden="true"></i>
                        <span>History</span>
                    </a>
                </li>
                <?php if (isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'users.php') ? 'active' : ''; ?>" 
                       href="<?php echo BASE_PATH; ?>pages/users.php"
                       <?php echo ($current_page === 'users.php') ? 'aria-current="page"' : ''; ?>>
                        <i class="bi bi-people" aria-hidden="true"></i>
                        <span>Users</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo escapeOutput($_SESSION['full_name']); ?></div>
                    <span class="badge <?php echo $_SESSION['role'] === 'admin' ? 'bg-danger' : 'bg-secondary'; ?> badge-sm">
                        <?php echo escapeOutput(ucfirst($_SESSION['role'])); ?>
                    </span>
                </div>
            </div>
            <a href="<?php echo BASE_PATH; ?>logout.php" class="btn btn-outline-light btn-sm w-100 mt-3">
                <i class="bi bi-box-arrow-right me-1"></i> Sign Out
            </a>
        </div>
    </aside>
    <?php endif; ?>
    
    <!-- Main Content Area -->
    <main id="main-content" class="main-content" role="main">
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
