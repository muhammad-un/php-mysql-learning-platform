<?php
// project-root/includes/db.php

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Your MySQL username (default for XAMPP)
define('DB_PASS', 'mysql');     // Your MySQL password (default for XAMPP is empty)
define('DB_NAME', 'learning_platform'); // The database name you created

// Establish database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // Log the error for debugging (do NOT display sensitive info on live server)
    error_log("Database connection failed: " . $conn->connect_error, 0);
    // Display a user-friendly error message
    die("Error: Could not connect to the database. Please try again later.");
}

// Set character set to UTF-8 for proper encoding
$conn->set_charset("utf8mb4");

// --- IMPORTANT: Error Reporting Settings ---
// For development: display all errors.
// For production: set display_errors to 0 and rely on error_log().
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
// This prevents "headers already sent" errors if session_start() is called multiple times.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>