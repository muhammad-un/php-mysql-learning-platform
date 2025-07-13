<?php
// project-root/admin/manage_questions.php
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_role('admin'); // Only admins can access

// Handle reply submission (Admin can also reply/edit replies)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_reply'])) {
    $question_id = sanitize_input($_POST['question_id'] ?? '');
    $reply_text = sanitize_input($_POST['reply_text'] ?? '');
    $topic_id = sanitize_input($_POST['topic_id'] ?? ''); // Hidden field to redirect back to topic context if needed

    if (empty($reply_text)) {
        set_message("Reply cannot be empty.", "danger");
    } elseif (!is_numeric($question_id) || !is_numeric($topic_id)) {
        set_message("Invalid question ID or topic ID for reply.", "danger");
    } else {
        $stmt_reply = $conn->prepare("UPDATE questions SET reply_text = ? WHERE question_id = ? AND topic_id = ?");
        if ($stmt_reply) {
            $stmt_reply->bind_param("sii", $reply_text, $question_id, $topic_id);
            if ($stmt_reply->execute()) {
                set_message("Reply updated successfully!", "success");
            } else {
                error_log("Admin reply failed for q_id: " . $question_id . " Error: " . $stmt_reply->error);
                set_message("Failed to submit reply. Please try again.", "danger");
            }
            $stmt_reply->close();
        } else {
            error_log("Failed to prepare statement for admin reply: " . $conn->error);
            set_message("An internal error occurred. Please try again.", "danger");
        }
    }
    redirect("manage_questions.php"); // Redirect back to this page
}

// Handle question deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $question_id_to_delete = (int)$_GET['id'];

    $stmt_delete_q = $conn->prepare("DELETE FROM questions WHERE question_id = ?");
    if ($stmt_delete_q) {
        $stmt_delete_q->bind_param("i", $question_id_to_delete);
        if ($stmt_delete_q->execute()) {
            if ($stmt_delete_q->affected_rows > 0) {
                set_message("Question deleted successfully!", "success");
            } else {
                set_message("Question not found.", "warning");
            }
        } else {
            error_log("Question deletion failed: " . $stmt_delete_q->error);
            set_message("Error deleting question. Please try again.", "danger");
        }
        $stmt_delete_q->close();
    } else {
        error_log("Failed to prepare question deletion statement: " . $conn->error);
        set_message("An internal error occurred.", "danger");
    }
    redirect("manage_questions.php");
}


// Fetch all questions with associated topic and user names
$questions = [];
$stmt = $conn->prepare("SELECT q.question_id, q.question_text, q.reply_text, q.timestamp,
                               t.title as topic_title, t.topic_id,
                               su.name as student_name, tu.name as teacher_name
                        FROM questions q
                        JOIN topics t ON q.topic_id = t.topic_id
                        JOIN users su ON q.student_id = su.user_id
                        JOIN users tu ON t.teacher_id = tu.user_id
                        ORDER BY q.timestamp DESC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }
    $stmt->close();
} else {
    error_log("Failed to prepare statement for fetching all questions (admin): " . $conn->error);
    set_message("Error fetching question list.", "danger");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions - Admin</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .reply-form-admin {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #ddd;
        }
        .reply-form-admin textarea {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .reply-form-admin button {
            margin-top: 5px;
        }
        .unanswered-tag {
            background-color: #ffc107; /* Warning yellow */
            color: #333;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
            margin-left: 10px;
        }
        .answered-tag {
            background-color: #28a745; /* Success green */
            color: #fff;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
            margin-left: 10px;
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
                    <li>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> (Admin)</li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="manage_users.php">Manage Users</a></li>
                    <li><a href="manage_topics.php">Manage Topics</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container main-content">
        <h2>Manage Questions & Answers</h2>
        <?php display_message(); ?>

        <?php if (empty($questions)): ?>
            <p>No questions have been asked yet across all topics.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Q ID</th>
                        <th>Topic</th>
                        <th>Student</th>
                        <th>Teacher</th>
                        <th>Question</th>
                        <th>Reply</th>
                        <th>Status</th>
                        <th>Asked On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questions as $q): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($q['question_id']); ?></td>
                            <td><a href="../student/view_topic.php?id=<?php echo $q['topic_id']; ?>" target="_blank"><?php echo htmlspecialchars($q['topic_title']); ?></a></td>
                            <td><?php echo htmlspecialchars($q['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($q['teacher_name']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($q['question_text'])); ?></td>
                            <td>
                                <?php if (!empty($q['reply_text'])): ?>
                                    <div class="reply">
                                        <?php echo nl2br(htmlspecialchars($q['reply_text'])); ?>
                                    </div>
                                    <div class="reply-form-admin">
                                        <form action="manage_questions.php" method="POST">
                                            <input type="hidden" name="action" value="submit_reply">
                                            <input type="hidden" name="question_id" value="<?php echo $q['question_id']; ?>">
                                            <input type="hidden" name="topic_id" value="<?php echo $q['topic_id']; ?>">
                                            <textarea name="reply_text" rows="2" placeholder="Edit reply..."><?php echo htmlspecialchars($q['reply_text']); ?></textarea>
                                            <button type="submit" name="submit_reply" class="btn btn-primary btn-sm">Update Reply</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <div class="reply-form-admin">
                                        <form action="manage_questions.php" method="POST">
                                            <input type="hidden" name="action" value="submit_reply">
                                            <input type="hidden" name="question_id" value="<?php echo $q['question_id']; ?>">
                                            <input type="hidden" name="topic_id" value="<?php echo $q['topic_id']; ?>">
                                            <textarea name="reply_text" rows="2" placeholder="Write a reply... (Teacher or Admin)"></textarea>
                                            <button type="submit" name="submit_reply" class="btn btn-success btn-sm">Submit Reply</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($q['reply_text'])): ?>
                                    <span class="answered-tag">Answered</span>
                                <?php else: ?>
                                    <span class="unanswered-tag">Unanswered</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($q['timestamp'])); ?></td>
                            <td>
                                <a href="manage_questions.php?action=delete&id=<?php echo $q['question_id']; ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Are you sure you want to delete this question?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>