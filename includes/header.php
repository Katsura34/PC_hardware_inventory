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
<body>
    <!-- Skip to main content link - HCI Principle: Accessibility & Flexibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <?php if (isLoggedIn()): ?>
    <?php
    // Determine active page for highlighting
    $current_page = basename($_SERVER['PHP_SELF']);
    $current_path = $_SERVER['REQUEST_URI'];
    ?>
    <!-- Navigation Bar - HCI Principle: Consistency & Visibility -->
    <nav class="navbar navbar-expand-lg navbar-dark" role="navigation" aria-label="Main navigation">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo BASE_PATH; ?>dashboard.php" aria-label="Go to Dashboard">
                <img src="<?php echo BASE_PATH; ?>assets/images/logo.png" alt="ACLC Logo" height="48" class="me-2">                               
                <span class="d-none d-md-inline">PC Inventory</span>
                <span class="d-md-none">Inventory</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
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
                <ul class="navbar-nav align-items-lg-center">
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="userDropdown" 
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-avatar d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" 
                                 style="width: 32px; height: 32px;">
                                <i class="bi bi-person-fill" aria-hidden="true"></i>
                            </div>
                            <span class="d-none d-md-inline"><?php echo escapeOutput($_SESSION['full_name']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg" aria-labelledby="userDropdown">
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
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Main Content Area -->
    <main id="main-content" class="container-fluid py-4" role="main">
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
