<?php
// project-root/teacher/dashboard.php
require_once '../includes/db.php'; // Path is relative to current directory
require_once '../includes/functions.php';

require_role('teacher'); // Ensure only teachers (or admins) can access

$teacher_id = $_SESSION['user_id'];
$teacher_name = $_SESSION['user_name'];

$topics = [];
// Fetch topics uploaded by the current teacher
$stmt = $conn->prepare("SELECT topic_id, title, description, upload_date FROM topics WHERE teacher_id = ? ORDER BY upload_date DESC");
if ($stmt) {
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $topics[] = $row;
    }
    $stmt->close();
} else {
    // Log database preparation error
    error_log("Failed to prepare statement for fetching teacher topics: " . $conn->error);
    set_message("Error fetching your topics.", "danger");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="../style.css"> </head>
<body>
    <header>
        <div class="container">
            <div id="branding">
                <h1><a href="../index.php">Learning Platform</a></h1>
            </div>
            <nav>
                <ul>
                    <li>Welcome, <?php echo htmlspecialchars($teacher_name); ?> (Teacher)</li>
                    <li><a href="upload_topic.php">Upload New Topic</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container main-content">
        <h2>Teacher Dashboard</h2>
        <?php display_message(); ?>

        <h3>Your Uploaded Topics</h3>
        <?php if (empty($topics)): ?>
            <p>You haven't uploaded any topics yet. <a href="upload_topic.php">Upload your first topic!</a></p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Upload Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topics as $topic): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($topic['title']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars(substr($topic['description'], 0, 100))) . (strlen($topic['description']) > 100 ? '...' : ''); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($topic['upload_date'])); ?></td>
                            <td>
                                <a href="view_topic_details.php?id=<?php echo $topic['topic_id']; ?>" class="btn btn-info btn-sm">View Details / Q&A</a>
                                </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>