<?php
// project-root/admin/manage_topics.php
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_role('admin'); // Only admins can access

// Handle topic deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $topic_id_to_delete = (int)$_GET['id'];

    // First, fetch the file paths to delete files from server
    $stmt_get_paths = $conn->prepare("SELECT video_url, pdf_path FROM topics WHERE topic_id = ?");
    if ($stmt_get_paths) {
        $stmt_get_paths->bind_param("i", $topic_id_to_delete);
        $stmt_get_paths->execute();
        $result_paths = $stmt_get_paths->get_result();
        $topic_paths = $result_paths->fetch_assoc();
        $stmt_get_paths->close();

        if ($topic_paths) {
            // Delete associated questions first to maintain referential integrity
            $stmt_delete_questions = $conn->prepare("DELETE FROM questions WHERE topic_id = ?");
            if ($stmt_delete_questions) {
                $stmt_delete_questions->bind_param("i", $topic_id_to_delete);
                $stmt_delete_questions->execute();
                $stmt_delete_questions->close();
            }

            // Delete the topic itself
            $stmt_delete_topic = $conn->prepare("DELETE FROM topics WHERE topic_id = ?");
            if ($stmt_delete_topic) {
                $stmt_delete_topic->bind_param("i", $topic_id_to_delete);
                if ($stmt_delete_topic->execute()) {
                    if ($stmt_delete_topic->affected_rows > 0) {
                        // Delete actual files from server only if DB record deleted successfully
                        if (!empty($topic_paths['pdf_path']) && strpos($topic_paths['pdf_path'], 'uploads/pdf/') !== false) {
                            $pdf_file_path = '../' . $topic_paths['pdf_path']; // Relative to admin folder
                            if (file_exists($pdf_file_path)) {
                                unlink($pdf_file_path);
                            }
                        }
                        if (!empty($topic_paths['video_url']) && strpos($topic_paths['video_url'], 'uploads/videos/') !== false) {
                            $video_file_path = '../' . $topic_paths['video_url']; // Relative to admin folder
                            if (file_exists($video_file_path)) {
                                unlink($video_file_path);
                            }
                        }
                        set_message("Topic and associated files/questions deleted successfully!", "success");
                    } else {
                        set_message("Topic not found.", "warning");
                    }
                } else {
                    error_log("Topic deletion failed: " . $stmt_delete_topic->error);
                    set_message("Error deleting topic. Please try again.", "danger");
                }
                $stmt_delete_topic->close();
            } else {
                error_log("Failed to prepare topic deletion statement: " . $conn->error);
                set_message("An internal error occurred.", "danger");
            }
        } else {
            set_message("Topic not found or already deleted.", "warning");
        }
    } else {
        error_log("Failed to prepare statement for fetching topic paths: " . $conn->error);
        set_message("An internal error occurred.", "danger");
    }
    redirect("manage_topics.php");
}

// Fetch all topics with teacher names
$topics = [];
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
    error_log("Failed to prepare statement for fetching all topics (admin): " . $conn->error);
    set_message("Error fetching topic list.", "danger");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Topics - Admin</title>
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
                    <li>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> (Admin)</li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="manage_users.php">Manage Users</a></li>
                    <li><a href="manage_questions.php">Manage Q&A</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container main-content">
        <h2>Manage Topics</h2>
        <?php display_message(); ?>

        <?php if (empty($topics)): ?>
            <p>No topics have been uploaded yet.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Teacher</th>
                        <th>Description (Snippet)</th>
                        <th>Upload Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topics as $topic): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($topic['topic_id']); ?></td>
                            <td><?php echo htmlspecialchars($topic['title']); ?></td>
                            <td><?php echo htmlspecialchars($topic['teacher_name']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars(substr($topic['description'], 0, 100))) . (strlen($topic['description']) > 100 ? '...' : ''); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($topic['upload_date'])); ?></td>
                            <td>
                                <a href="../teacher/view_topic_details.php?id=<?php echo $topic['topic_id']; ?>" class="btn btn-info btn-sm">View</a>
                                <a href="manage_topics.php?action=delete&id=<?php echo $topic['topic_id']; ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Are you sure you want to delete this topic? This will also delete all associated questions!');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>