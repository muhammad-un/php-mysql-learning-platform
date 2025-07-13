<?php
// project-root/login.php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect("index.php");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Server-side validation
    if (empty($email) || empty($password)) {
        set_message("Both email and password are required.", "danger");
    } else {
        // Prepare statement to fetch user by email
        $stmt = $conn->prepare("SELECT user_id, name, password, role FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                // Verify the submitted password against the hashed password
                if (password_verify($password, $user['password'])) {
                    // Password is correct, set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['role'] = $user['role'];

                    // Optional: Regenerate session ID to prevent session fixation attacks
                    session_regenerate_id(true);

                    set_message("Welcome back, " . htmlspecialchars($user['name']) . "!", "success");

                    // Redirect based on user role
                    if ($user['role'] === 'teacher') {
                        redirect("teacher/dashboard.php");
                    } elseif ($user['role'] === 'student') {
                        redirect("student/dashboard.php");
                    } elseif ($user['role'] === 'admin') {
                        redirect("admin/dashboard.php");
                    }
                } else {
                    // Invalid password
                    set_message("Invalid email or password.", "danger");
                }
            } else {
                // Email not found
                set_message("Invalid email or password.", "danger");
            }
            $stmt->close();
        } else {
            // Log database preparation error
            error_log("Prepare statement failed for login: " . $conn->error);
            set_message("An internal error occurred. Please try again.", "danger");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Learning Platform</title>
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
        <h2>Login</h2>
        <?php display_message(); // Display any session messages ?>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Register here</a>.</p>
    </div>
</body>
</html>