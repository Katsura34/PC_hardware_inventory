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
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>assets/css/style.css">
    
    <!-- Base path for JavaScript -->
    <script>window.BASE_PATH = '<?php echo BASE_PATH; ?>';</script>
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
            <a class="navbar-brand d-flex align-items-center" href="<?php echo BASE_PATH; ?>dashboard.php">
                <img src="<?php echo BASE_PATH; ?>assets/images/logo.svg" alt="Logo" height="32" class="me-2">
                <span>PC Inventory - ACLC Ormoc</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page === 'hardware.php') ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>pages/hardware.php">
                            <i class="bi bi-cpu"></i> Hardware
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page === 'history.php') ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>pages/history.php">
                            <i class="bi bi-clock-history"></i> History
                        </a>
                    </li>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page === 'users.php') ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>pages/users.php">
                            <i class="bi bi-people"></i> Users
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav align-items-lg-center">
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo escapeOutput($_SESSION['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text"><small class="text-muted">Role: <?php echo escapeOutput(ucfirst($_SESSION['role'])); ?></small></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
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
