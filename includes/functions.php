<?php
// project-root/includes/functions.php

/**
 * Redirects the user to a specified URL and exits the script.
 * @param string $url The URL to redirect to.
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Sanitizes user input to prevent XSS and SQL Injection.
 * This is a basic sanitizer. For more robust applications, consider libraries like HTML Purifier.
 * @param string $data The input data to sanitize.
 * @return string The sanitized data.
 */
function sanitize_input($data) {
    global $conn; // Access the global database connection object from db.php
    $data = trim($data); // Remove whitespace from the beginning and end of string
    $data = stripslashes($data); // Remove backslashes
    // Convert special characters to HTML entities to prevent XSS.
    // ENT_QUOTES converts both double and single quotes. 'UTF-8' is the encoding.
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    // Escape special characters in a string for use in an SQL statement.
    // Crucial for preventing SQL injection when not using prepared statements directly on every value.
    // However, with prepared statements, this is less critical for values bound, but good for general string safety.
    $data = $conn->real_escape_string($data);
    return $data;
}

/**
 * Sets a session message for displaying notifications (success, error, warning).
 * @param string $message The message text.
 * @param string $type The type of message (e.g., 'success', 'danger', 'warning').
 */
function set_message($message, $type = 'success') {
    $_SESSION['message'] = ['text' => $message, 'type' => $type];
}

/**
 * Displays any existing session message and then clears it.
 */
function display_message() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        // Use htmlspecialchars for the message text to prevent XSS when displaying
        echo '<div class="alert alert-' . htmlspecialchars($message['type']) . '">' . htmlspecialchars($message['text']) . '</div>';
        unset($_SESSION['message']); // Clear the message after displaying it
    }
}

/**
 * Checks if a user is currently logged in.
 * @return bool True if a user_id is set in the session, false otherwise.
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Checks if the logged-in user is an admin.
 * @return bool True if logged in and role is 'admin'.
 */
function is_admin() {
    return is_logged_in() && $_SESSION['role'] === 'admin';
}

/**
 * Checks if the logged-in user is a teacher.
 * @return bool True if logged in and role is 'teacher'.
 */
function is_teacher() {
    return is_logged_in() && $_SESSION['role'] === 'teacher';
}

/**
 * Checks if the logged-in user is a student.
 * @return bool True if logged in and role is 'student'.
 */
function is_student() {
    return is_logged_in() && $_SESSION['role'] === 'student';
}

/**
 * Restricts access to a page based on user role.
 * Redirects to login if not logged in.
 * Redirects to index.php with an error message if unauthorized.
 * Allows 'admin' to access 'teacher' or 'student' pages for management purposes.
 * @param string $required_role The role required to access the page ('teacher', 'student', 'admin').
 */
function require_role($required_role) {
    if (!is_logged_in()) {
        set_message("You must be logged in to access this page.", "danger");
        redirect("login.php");
    }

    $current_role = $_SESSION['role'];

    // If the current role is not the required role AND
    // if the current user is NOT an admin trying to access a teacher/student page
    if ($current_role !== $required_role) {
        if (!($current_role === 'admin' && ($required_role === 'teacher' || $required_role === 'student'))) {
            set_message("You do not have permission to access this page.", "danger");
            redirect("../index.php"); // Redirect to the main index for unauthorized access
        }
    }
}
?>