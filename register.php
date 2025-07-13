<?php
// project-root/register.php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect("index.php");
}

$errors = []; // Initialize an array to hold validation errors

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize user inputs immediately
    $name = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? ''; // Password not sanitized here, will be hashed
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = sanitize_input($_POST['role'] ?? '');

    // Server-side validation
    if (empty($name)) {
        $errors[] = "Full Name is required.";
    }
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { // Validate email format
        $errors[] = "Invalid email format.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) { // Minimum password length
        $errors[] = "Password must be at least 6 characters long.";
    }
    if ($password !== $confirm_password) { // Check if passwords match
        $errors[] = "Passwords do not match.";
    }
    // Validate role against allowed values
    if (!in_array($role, ['teacher', 'student'])) {
        $errors[] = "Invalid role selected.";
    }

    // If initial validations pass, check for email uniqueness
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result(); // Store result to check num_rows

            if ($stmt->num_rows > 0) {
                $errors[] = "Email already registered.";
            }
            $stmt->close();
        } else {
            // Log database preparation error
            error_log("Prepare statement failed for email check: " . $conn->error);
            $errors[] = "An internal error occurred. Please try again.";
        }
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash the password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user into database using prepared statement
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                set_message("Registration successful! You can now log in.", "success");
                redirect("login.php"); // Redirect to login page on success
            } else {
                // Log database execution error
                error_log("User registration failed for email: " . $email . " Error: " . $stmt->error);
                set_message("Registration failed. Please try again.", "danger");
            }
            $stmt->close();
        } else {
            // Log database preparation error
            error_log("Prepare statement failed for user insert: " . $conn->error);
            set_message("An internal error occurred. Please try again.", "danger");
        }
    } else {
        // Display all accumulated errors
        foreach ($errors as $error) {
            set_message($error, "danger");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Learning Platform</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="container">
            <div id="branding">
                <h1><a href="index.php">Learning Platform</a></h1>
            </div>
            <nav>
                <ul>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container main-content">
        <h2>Register</h2>
        <?php display_message(); // Display any success or error messages ?>
        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="name">Full Name:</label>
                <input type="text" id="name" name="name" required
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="form-group">
                <label for="role">Register as:</label>
                <select id="role" name="role" required>
                    <option value="">Select Role</option>
                    <option value="teacher" <?php echo (isset($_POST['role']) && $_POST['role'] == 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                    <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] == 'student') ? 'selected' : ''; ?>>Student</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a>.</p>
    </div>
</body>
</html>