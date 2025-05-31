<?php
require_once 'auth.php';
if(!$auth->isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retail POS - <?php echo $pageTitle ?? 'Dashboard'; ?></title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dark-mode.css" id="darkModeStyle" disabled>
</head>
<body>
<div class="alert-container fixed-top mt-5" style="z-index: 2000;"></div>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">Retail POS</h4>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'index.php' && $current_dir == 'dashboard') ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>modules/dashboard/">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'new.php') ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>modules/sales/new.php">
                                <i class="fas fa-cash-register me-2"></i>New Sale
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_dir == 'products') ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>modules/products/">
                                <i class="fas fa-boxes me-2"></i>Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_dir == 'customers') ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>modules/customers/">
                                <i class="fas fa-users me-2"></i>Customers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'index.php' && $current_dir == 'sales') ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>modules/sales/">
                                <i class="fas fa-receipt me-2"></i>Sales History
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_dir == 'reports') ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>modules/reports/">
                                <i class="fas fa-chart-bar me-2"></i>Reports
                            </a>
                        </li>
                        <?php if($auth->hasRole('admin')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_dir == 'users') ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>modules/users/">
                                <i class="fas fa-user-cog me-2"></i>User Management
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="darkModeToggle">
                                <i class="fas fa-moon"></i> Dark Mode
                            </button>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i> <?php echo $_SESSION['full_name']; ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>