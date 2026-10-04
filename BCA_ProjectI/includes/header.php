<?php
// ==============================================================
// includes/header.php
// Common Header & Navigation Bar for Customer-Facing Pages
// ==============================================================

// Ensure database and session are initialized
require_once __DIR__ . '/../config/db.php';

// Set default page title if not specified
if (!isset($page_title)) {
    $page_title = "Movie Ticket Booking System";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>

    <!-- Bootstrap 4 CSS CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    
    <!-- Font Awesome Icons CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Custom Project CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand font-weight-bold text-warning" href="index.php">
                <i class="fas fa-film mr-2"></i>CineBook
            </a>
            
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="index.php">
                            <i class="fas fa-home mr-1"></i> Home
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav ml-auto align-items-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Logged-in Customer Menu -->
                        <li class="nav-item">
                            <a class="nav-link text-light" href="my-bookings.php">
                                <i class="fas fa-ticket-alt mr-1"></i> My Bookings
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-warning" href="#" id="userDrop" data-toggle="dropdown">
                                <i class="fas fa-user-circle mr-1"></i>
                                <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item" href="my-bookings.php">My Bookings</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <i class="fas fa-sign-out-alt mr-1"></i> Logout
                                </a>
                            </div>
                        </li>
                    <?php else: ?>
                        <!-- Guest Menu -->
                        <li class="nav-item">
                            <a class="nav-link text-white" href="login.php">
                                <i class="fas fa-sign-in-alt mr-1"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-warning btn-sm text-dark font-weight-bold ml-2 px-3" href="register.php">
                                Register
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Small Admin Portal link -->
                    <li class="nav-item ml-lg-3 mt-2 mt-lg-0">
                        <a class="nav-link text-muted small" href="admin/login.php" title="Staff Portal">
                            <i class="fas fa-user-shield"></i> Admin
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Content Container Starts Here (closed in includes/footer.php) -->
    <main class="py-4">
