<?php
// project-root/teacher/view_topic_details.php
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_role('teacher'); // Ensure only teachers (or admins) can access

$teacher_id = $_SESSION['user_id'];

// Validate topic ID from GET request
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_message("Invalid topic ID provided.", "danger");
    redirect("dashboard.php");
}

$topic_id = (int)$_GET['id'];
$topic = null;
$questions = [];

// Fetch topic details
// IMPORTANT: Ensure the teacher owns this topic, or it's an admin accessing it.
$stmt = $conn->prepare("SELECT t.topic_id, t.title, t.description, t.video_url, t.pdf_path, t.upload_date, u.name as teacher_name, t.teacher_id
                        FROM topics t
                        JOIN users u ON t.teacher_id = u.user_id
                        WHERE t.topic_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $topic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $topic = $result->fetch_assoc();
        // Additional security check: Ensure the logged-in teacher owns this topic
        if ($topic['teacher_id'] !== $teacher_id && !is_admin()) {
            set_message("You do not have permission to view this topic.", "danger");
            redirect("dashboard.php");
        }
    } else {
        set_message("Topic not found or you do not have access.", "danger");
        redirect("dashboard.php");
    }
    $stmt->close();
} else {
    error_log("Failed to prepare statement for fetching topic details (teacher): " . $conn->error);
    set_message("Error fetching topic details.", "danger");
    redirect("dashboard.php");
}

// Handle teacher's reply submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_reply'])) {
    $question_id = sanitize_input($_POST['question_id'] ?? '');
    $reply_text = sanitize_input($_POST['reply_text'] ?? '');

    if (empty($reply_text)) {
        set_message("Reply cannot be empty.", "danger");
    } elseif (!is_numeric($question_id)) {
        set_message("Invalid question ID for reply.", "danger");
    } else {
        // Update the question with the teacher's reply
        $stmt_reply = $conn->prepare("UPDATE questions SET reply_text = ? WHERE question_id = ? AND topic_id = ?");
        if ($stmt_reply) {
            $stmt_reply->bind_param("sii", $reply_text, $question_id, $topic_id);
            if ($stmt_reply->execute()) {
                set_message("Reply submitted successfully!", "success");
                redirect("view_topic_details.php?id=" . $topic_id); // Redirect to refresh the page
            } else {
                error_log("Teacher reply failed for q_id: " . $question_id . " Error: " . $stmt_reply->error);
                set_message("Failed to submit reply. Please try again.", "danger");
            }
            $stmt_reply->close();
        } else {
            error_log("Failed to prepare statement for teacher reply: " . $conn->error);
            set_message("An internal error occurred while preparing reply. Please try again.", "danger");
        }
    }
}

// Fetch questions for this topic
$stmt_fetch_q = $conn->prepare("SELECT q.question_id, q.question_text, q.reply_text, q.timestamp, u.name as student_name
                               FROM questions q
                               JOIN users u ON q.student_id = u.user_id
                               WHERE q.topic_id = ?
                               ORDER BY q.timestamp DESC");
if ($stmt_fetch_q) {
    $stmt_fetch_q->bind_param("i", $topic_id);
    $stmt_fetch_q->execute();
    $result_q = $stmt_fetch_q->get_result();
    while ($row_q = $result_q->fetch_assoc()) {
        $questions[] = $row_q;
    }
    $stmt_fetch_q->close();
} else {
    error_log("Failed to prepare statement for fetching questions for topic (teacher view): " . $conn->error);
    set_message("Error fetching questions for this topic.", "danger");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Topic Details: <?php echo htmlspecialchars($topic['title']); ?></title>
    <link rel="stylesheet" href="../style.css">
    <style>
        /* Re-using some styles from student/view_topic.php for consistency */
        .video-container {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
            max-width: 100%;
            background: #000;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .video-container iframe,
        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }
        .questions-section {
            margin-top: 30px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .question-item {
            background: #fdfdfd;
            border: 1px solid #e9e9e9;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .question-item strong {
            color: #444;
        }
        .question-item .reply {
            margin-top: 15px;
            padding: 12px 15px;
            background: #e6f2ff; /* Light blue */
            border-left: 4px solid #007bff;
            font-style: italic;
            border-radius: 5px;
        }
        .question-item .reply p {
            margin: 0;
        }
        .reply-form {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #ddd;
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
                    <li>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> (Teacher)</li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="upload_topic.php">Upload New Topic</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container main-content">
        <h2>Topic Details: <?php echo htmlspecialchars($topic['title']); ?></h2>
        <?php display_message(); ?>

        <p><strong>Teacher:</strong> <?php echo htmlspecialchars($topic['teacher_name']); ?></p>
        <p><strong>Uploaded:</strong> <?php echo date('Y-m-d H:i', strtotime($topic['upload_date'])); ?></p>
        <p><?php echo nl2br(htmlspecialchars($topic['description'])); ?></p>

        <?php if (!empty($topic['video_url'])): ?>
            <h3>Video Lecture</h3>
            <div class="video-container">
                <?php
                $video_url_display = htmlspecialchars($topic['video_url']);
                // Basic check for YouTube/Vimeo embeds. More robust parsing might be needed.
                if (strpos($video_url_display, 'http://googleusercontent.com/youtube.com/') !== false) {
                    $youtube_id = '';
                    // Try to extract YouTube ID from standard URL or short URL
                    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $video_url_display, $matches)) {
                        $youtube_id = $matches[1];
                    }

                    if ($youtube_id) {
                        echo '<iframe src="https://www.youtube.com/watch?v=RaH75OuHge8' . $youtube_id . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                    } else {
                        echo '<p class="alert alert-warning">Invalid YouTube URL provided. Please check the link.</p>';
                    }
                } elseif (strpos($video_url_display, 'vimeo.com/') !== false) {
                     $vimeo_id = '';
                     if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $video_url_display, $matches)) {
                         $vimeo_id = $matches[1];
                     }
                     if ($vimeo_id) {
                         echo '<iframe src="http://player.vimeo.com/video/' . $vimeo_id . '?byline=0&portrait=0" width="640" height="360" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
                     } else {
                         echo '<p class="alert alert-warning">Invalid Vimeo URL provided. Please check the link.</p>';
                     }
                } elseif (strpos($video_url_display, 'uploads/videos/') !== false) {
                    // Assume it's a locally uploaded video
                    $video_path_relative_to_current_file = '../' . $video_url_display;
                    if (file_exists($video_path_relative_to_current_file)) {
                        echo '<video controls><source src="' . htmlspecialchars($video_path_relative_to_current_file) . '" type="video/mp4">Your browser does not support the video tag.</video>';
                    } else {
                        echo '<p class="alert alert-warning">Local video file not found on server.</p>';
                        error_log("Missing local video file: " . $video_path_relative_to_current_file);
                    }
                } else {
                    echo '<p class="alert alert-info">Video link: <a href="' . $video_url_display . '" target="_blank">' . $video_url_display . '</a></p>';
                    echo '<p class="alert alert-warning">The provided video URL is not a recognized embeddable format (YouTube/Vimeo) or a local file. It will open in a new tab.</p>';
                }
                ?>
            </div>
        <?php else: ?>
            <p class="alert alert-info">No video lecture provided for this topic.</p>
        <?php endif; ?>

        <h3>PDF Notes</h3>
        <?php if (!empty($topic['pdf_path'])): ?>
            <?php
            $pdf_path_relative_to_current_file = '../' . $topic['pdf_path'];
            if (file_exists($pdf_path_relative_to_current_file)) {
                echo '<p><a href="' . htmlspecialchars($pdf_path_relative_to_current_file) . '" target="_blank" class="btn btn-info">View/Download PDF Notes</a></p>';
            } else {
                echo '<p class="alert alert-warning">PDF file not found on server.</p>';
                error_log("Missing PDF file: " . $pdf_path_relative_to_current_file);
            }
            ?>
        <?php else: ?>
            <p class="alert alert-info">No PDF notes provided for this topic.</p>
        <?php endif; ?>

        <div class="questions-section">
            <h3>Student Questions & Your Replies</h3>
            <?php if (empty($questions)): ?>
                <p>No questions have been asked for this topic yet.</p>
            <?php else: ?>
                <?php foreach ($questions as $q): ?>
                    <div class="question-item">
                        <p><strong><?php echo htmlspecialchars($q['student_name']); ?> asked:</strong> <?php echo nl2br(htmlspecialchars($q['question_text'])); ?></p>
                        <small>Asked on: <?php echo date('Y-m-d H:i', strtotime($q['timestamp'])); ?></small>

                        <?php if (!empty($q['reply_text'])): ?>
                            <div class="reply">
                                <p><strong>Your Reply:</strong> <?php echo nl2br(htmlspecialchars($q['reply_text'])); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="reply-form">
                                <form action="view_topic_details.php?id=<?php echo $topic_id; ?>" method="POST">
                                    <input type="hidden" name="question_id" value="<?php echo $q['question_id']; ?>">
                                    <div class="form-group">
                                        <label for="reply_text_<?php echo $q['question_id']; ?>">Reply to this question:</label>
                                        <textarea id="reply_text_<?php echo $q['question_id']; ?>" name="reply_text" rows="2" required></textarea>
                                    </div>
                                    <button type="submit" name="submit_reply" class="btn btn-success btn-sm">Submit Reply</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>