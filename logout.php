<?php
// project-root/logout.php
require_once 'includes/db.php'; // Required to ensure session_start() is called if not already.
require_once 'includes/functions.php';

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
// This effectively logs the user out by deleting their session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

set_message("You have been logged out.", "success");
redirect("login.php"); // Redirect to the login page after logout
?>