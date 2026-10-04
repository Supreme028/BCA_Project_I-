<?php
// ==============================================================
// admin/includes/header.php
// Admin Back-Office Common Header & Auth Protection Guard
// ==============================================================

require_once __DIR__ . '/../../config/db.php';

// Prevent browser from caching any admin page — back button won't work after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

// Authentication Guard: Redirect to login if session is not active
$current_page = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['admin_id']) && $current_page !== 'login.php') {
    header("Location: login.php");
    exit;
}

// Extra guard: if somehow on login page but already logged in, go to dashboard
if (isset($_SESSION['admin_id']) && $current_page === 'login.php') {
    header("Location: dashboard.php");
    exit;
}

if (!isset($admin_page_title)) {
    $admin_page_title = "Admin Panel - CineBook";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($admin_page_title); ?></title>

    <!-- Bootstrap 4 CSS CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    
    <!-- Font Awesome Icons CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        body {
            background-color: #0b1120;
            color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .admin-nav {
            background-color: #020617;
            border-bottom: 1px solid #1e293b;
        }
        .admin-card {
            background-color: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 8px;
        }
        .table-dark {
            background-color: #0f172a;
        }
        .table-dark td, .table-dark th {
            border-color: #1e293b;
        }
        .form-control, .custom-select {
            background-color: #1e293b;
            border: 1px solid #334155;
            color: #ffffff;
        }
        .form-control:focus, .custom-select:focus {
            background-color: #1e293b;
            border-color: #f59e0b;
            color: #ffffff;
            box-shadow: 0 0 0 0.2rem rgba(245, 158, 11, 0.25);
        }
    </style>
</head>
<body>

    <?php if (isset($_SESSION['admin_id'])): ?>
    <!-- Top Admin Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark admin-nav sticky-top py-3">
        <div class="container">
            <a class="navbar-brand font-weight-bold text-warning" href="dashboard.php">
                <i class="fas fa-shield-alt mr-2"></i>CineBook <span class="badge badge-warning text-dark ml-1">Admin</span>
            </a>

            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#adminMenu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="adminMenu">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php if ($current_page === 'dashboard.php') echo 'active text-warning font-weight-bold'; ?>" href="dashboard.php">
                            <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php if ($current_page === 'movies.php') echo 'active text-warning font-weight-bold'; ?>" href="movies.php">
                            <i class="fas fa-film mr-1"></i> Movies
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php if ($current_page === 'showtimes.php') echo 'active text-warning font-weight-bold'; ?>" href="showtimes.php">
                            <i class="fas fa-clock mr-1"></i> Showtimes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php if ($current_page === 'bookings.php') echo 'active text-warning font-weight-bold'; ?>" href="bookings.php">
                            <i class="fas fa-receipt mr-1"></i> Bookings
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav ml-auto align-items-center">
                    <li class="nav-item mr-3">
                        <a class="nav-link text-info small" href="../index.php" target="_blank" title="View Public Front Website">
                            <i class="fas fa-external-link-alt mr-1"></i> Public Site
                        </a>
                    </li>
                    <li class="nav-item">
                        <span class="text-light mr-3 small">
                            <i class="fas fa-user-circle mr-1"></i> <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-danger btn-sm" href="logout.php">
                            <i class="fas fa-sign-out-alt mr-1"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Main Content Container for Admin Pages -->
    <main class="py-4" style="flex: 1 0 auto;">
        <div class="container">
