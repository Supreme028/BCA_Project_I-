<?php
// ==============================================================
// config/db.php
// Database Configuration and Connection Script
// ==============================================================

// Database connection parameters (default for XAMPP / WAMP)
$host     = "localhost";
$username = "root";
$password = "";
$database = "movie_booking_db";

// Connect to MySQL database using procedural mysqli
$conn = mysqli_connect($host, $username, $password, $database);

// Check if connection succeeded
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Set character set to utf8mb4 for unicode/special character support
mysqli_set_charset($conn, "utf8mb4");

// Start PHP session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
