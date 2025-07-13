<?php
// project-root/student/dashboard.php
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_role('student'); // Ensure only students (or admins) can access

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['user_name'];

$topics = [];
// Fetch all available topics from all teachers
$stmt = $conn->prepare("SELECT t.topic_id, t.title, t.description, t.upload_date, u.name as teacher_name
                        FROM topics t
                        JOIN users u ON t.teacher_id = u.user_id
                        ORDER BY t.upload_date DESC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $topics[] = $row;
    }
    $stmt->close();
} else {
    // Log database preparation error
    error_log("Failed to prepare statement for fetching all topics (student): " . $conn->error);
    set_message("Error fetching topics.", "danger");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <header>
        <div class="container">
            <div id="branding">
                <h1><a href="../index.php">Learning Platform</a></h1>
            </div>
            <nav>
                <ul>
                    <li>Welcome, <?php echo htmlspecialchars($student_name); ?> (Student)</li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container main-content">
        <h2>Student Dashboard</h2>
        <?php display_message(); ?>

        <h3>Available Topics</h3>
        <?php if (empty($topics)): ?>
            <p class="alert alert-info">No topics have been uploaded yet. Please check back later!</p>
        <?php else: ?>
            <div class="topic-list">
                <?php foreach ($topics as $topic): ?>
                    <div class="topic-card">
                        <h4><?php echo htmlspecialchars($topic['title']); ?></h4>
                        <p><strong>Teacher:</strong> <?php echo htmlspecialchars($topic['teacher_name']); ?></p>
                        <p><?php echo nl2br(htmlspecialchars(substr($topic['description'], 0, 150))) . (strlen($topic['description']) > 150 ? '...' : ''); ?></p>
                        <p><small>Uploaded on: <?php echo date('Y-m-d H:i', strtotime($topic['upload_date'])); ?></small></p>
                        <a href="view_topic.php?id=<?php echo $topic['topic_id']; ?>" class="btn btn-primary">View Topic</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>