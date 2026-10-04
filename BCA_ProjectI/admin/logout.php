<?php
// ==============================================================
// admin/logout.php - Administrator Logout Script
// ==============================================================

require_once __DIR__ . '/../config/db.php';

// Clear all session variables
$_SESSION = [];

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Fully destroy the session
session_destroy();

// Redirect to admin login with no-cache headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Location: login.php");
exit;
?>
