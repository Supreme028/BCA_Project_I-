<?php
// ==============================================================
// logout.php
// User Logout and Session Termination Script
// ==============================================================

// Include database configuration to ensure session is active
require_once 'config/db.php';

// Unset all session variables
$_SESSION = array();

// If session cookie exists, expire it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session on the server
session_destroy();

// Redirect back to home page
header("Location: index.php");
exit;
?>
