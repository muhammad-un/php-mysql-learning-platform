<?php
// project-root/index.php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in and redirect to appropriate dashboard
if (is_logged_in()) {
    if ($_SESSION['role'] === 'teacher') {
        redirect("teacher/dashboard.php");
    } elseif ($_SESSION['role'] === 'student') {
        redirect("student/dashboard.php");
    } elseif ($_SESSION['role'] === 'admin') {
        redirect("admin/dashboard.php");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Learning Platform</title>
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
                    <?php if (!is_logged_in()): // Show login/register if not logged in ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    <?php else: // Show welcome and logout if logged in ?>
                        <li>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container main-content">
        <h2>Welcome to the Teacher-Student Learning Platform!</h2>
        <?php display_message(); // Display any session messages ?>
        <p>This platform connects teachers and students for effective online learning.</p>
        <p>Teachers can upload video lectures and PDF notes, while students can access these resources and ask questions.</p>

        <?php if (!is_logged_in()): ?>
            <p>Please <a href="login.php">Login</a> or <a href="register.php">Register</a> to get started.</p>
        <?php else: ?>
            <p>You are logged in as a <?php echo htmlspecialchars($_SESSION['role']); ?>. Go to your <a href="<?php echo htmlspecialchars($_SESSION['role']); ?>/dashboard.php">Dashboard</a>.</p>
        <?php endif; ?>
    </div>
</body>
</html>