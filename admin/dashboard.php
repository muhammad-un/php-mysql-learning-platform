<?php
// project-root/admin/dashboard.php
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_role('admin'); // Ensure only admins can access

$admin_name = $_SESSION['user_name'];

// --- Fetch Dashboard Stats ---
$total_users = 0;
$total_teachers = 0;
$total_students = 0;
$total_topics = 0;
$total_questions = 0;
$unanswered_questions = 0;

// Total Users
$stmt_users = $conn->prepare("SELECT COUNT(*) AS count FROM users");
if ($stmt_users) {
    $stmt_users->execute();
    $result_users = $stmt_users->get_result();
    $total_users = $result_users->fetch_assoc()['count'];
    $stmt_users->close();
}

// Total Teachers
$stmt_teachers = $conn->prepare("SELECT COUNT(*) AS count FROM users WHERE role = 'teacher'");
if ($stmt_teachers) {
    $stmt_teachers->execute();
    $result_teachers = $stmt_teachers->get_result();
    $total_teachers = $result_teachers->fetch_assoc()['count'];
    $stmt_teachers->close();
}

// Total Students
$stmt_students = $conn->prepare("SELECT COUNT(*) AS count FROM users WHERE role = 'student'");
if ($stmt_students) {
    $stmt_students->execute();
    $result_students = $stmt_students->get_result();
    $total_students = $result_students->fetch_assoc()['count'];
    $stmt_students->close();
}

// Total Topics
$stmt_topics = $conn->prepare("SELECT COUNT(*) AS count FROM topics");
if ($stmt_topics) {
    $stmt_topics->execute();
    $result_topics = $stmt_topics->get_result();
    $total_topics = $result_topics->fetch_assoc()['count'];
    $stmt_topics->close();
}

// Total Questions
$stmt_questions = $conn->prepare("SELECT COUNT(*) AS count FROM questions");
if ($stmt_questions) {
    $stmt_questions->execute();
    $result_questions = $stmt_questions->get_result();
    $total_questions = $result_questions->fetch_assoc()['count'];
    $stmt_questions->close();
}

// Unanswered Questions
$stmt_unanswered = $conn->prepare("SELECT COUNT(*) AS count FROM questions WHERE reply_text IS NULL OR reply_text = ''");
if ($stmt_unanswered) {
    $stmt_unanswered->execute();
    $result_unanswered = $stmt_unanswered->get_result();
    $unanswered_questions = $result_unanswered->fetch_assoc()['count'];
    $stmt_unanswered->close();
}
// You might add error logging for each of these if they fail,
// but for a dashboard, a missing count is less critical than a full page error.

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .stat-card {
            background: #e9f5ff; /* Light blue */
            border-left: 5px solid #007bff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            text-align: center;
        }
        .stat-card h4 {
            margin-top: 0;
            color: #333;
            font-size: 1.2em;
        }
        .stat-card p {
            font-size: 2.5em;
            font-weight: bold;
            color: #007bff;
            margin: 0;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div id="branding">
                <h1><a href="../index.php">Learning Platform</a></h1>
            </div>
            <nav>
                <ul>
                    <li>Welcome, <?php echo htmlspecialchars($admin_name); ?> (Admin)</li>
                    <li><a href="manage_users.php">Manage Users</a></li>
                    <li><a href="manage_topics.php">Manage Topics</a></li>
                    <li><a href="manage_questions.php">Manage Q&A</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container main-content">
        <h2>Admin Dashboard</h2>
        <?php display_message(); ?>

        <h3>Platform Overview</h3>
        <div class="stats-grid">
            <div class="stat-card">
                <h4>Total Users</h4>
                <p><?php echo $total_users; ?></p>
            </div>
            <div class="stat-card">
                <h4>Teachers</h4>
                <p><?php echo $total_teachers; ?></p>
            </div>
            <div class="stat-card">
                <h4>Students</h4>
                <p><?php echo $total_students; ?></p>
            </div>
            <div class="stat-card">
                <h4>Total Topics</h4>
                <p><?php echo $total_topics; ?></p>
            </div>
            <div class="stat-card">
                <h4>Total Questions</h4>
                <p><?php echo $total_questions; ?></p>
            </div>
            <div class="stat-card">
                <h4>Unanswered Q's</h4>
                <p><?php echo $unanswered_questions; ?></p>
            </div>
        </div>

        <h3 style="margin-top: 40px;">Admin Actions</h3>
        <p>Use the navigation above to manage various aspects of the platform:</p>
        <ul>
            <li><strong>Manage Users:</strong> View, edit, or delete user accounts (teachers, students, and other admins).</li>
            <li><strong>Manage Topics:</strong> Review, edit, or delete any uploaded learning topics.</li>
            <li><strong>Manage Q&A:</strong> Oversee all student questions and teacher replies, or reply if needed.</li>
        </ul>
    </div>
</body>
</html>