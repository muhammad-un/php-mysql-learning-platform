<?php
// project-root/student/view_topic.php
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_role('student'); // Ensure only students (or admins) can access

$student_id = $_SESSION['user_id'];

// Validate topic ID from GET request
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_message("Invalid topic ID provided.", "danger");
    redirect("dashboard.php");
}

$topic_id = (int)$_GET['id'];
$topic = null;
$questions = [];

// Fetch topic details
$stmt = $conn->prepare("SELECT t.topic_id, t.title, t.description, t.video_url, t.pdf_path, t.upload_date, u.name as teacher_name
                        FROM topics t
                        JOIN users u ON t.teacher_id = u.user_id
                        WHERE t.topic_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $topic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $topic = $result->fetch_assoc();
    } else {
        set_message("Topic not found.", "danger");
        redirect("dashboard.php");
    }
    $stmt->close();
} else {
    error_log("Failed to prepare statement for fetching topic details (student): " . $conn->error);
    set_message("Error fetching topic details.", "danger");
    redirect("dashboard.php");
}

// Handle student's question submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_question'])) {
    $question_text = sanitize_input($_POST['question_text'] ?? '');

    if (empty($question_text)) {
        set_message("Question cannot be empty.", "danger");
    } else {
        // Insert new question into database
        $stmt_insert_q = $conn->prepare("INSERT INTO questions (topic_id, student_id, question_text) VALUES (?, ?, ?)");
        if ($stmt_insert_q) {
            $stmt_insert_q->bind_param("iis", $topic_id, $student_id, $question_text);
            if ($stmt_insert_q->execute()) {
                set_message("Your question has been submitted!", "success");
                redirect("view_topic.php?id=" . $topic_id); // Redirect to refresh the page and show the new question
            } else {
                error_log("Student question submission failed for student_id: " . $student_id . " Error: " . $stmt_insert_q->error);
                set_message("Failed to submit question. Please try again.", "danger");
            }
            $stmt_insert_q->close();
        } else {
            error_log("Failed to prepare statement for student question: " . $conn->error);
            set_message("An internal error occurred while preparing question. Please try again.", "danger");
        }
    }
}

// Fetch all questions and replies for this topic
$stmt_fetch_q = $conn->prepare("SELECT q.question_id, q.question_text, q.reply_text, q.timestamp, u.name as student_name
                               FROM questions q
                               JOIN users u ON q.student_id = u.user_id
                               WHERE q.topic_id = ?
                               ORDER BY q.timestamp ASC"); // Order by ASC to show older questions first
if ($stmt_fetch_q) {
    $stmt_fetch_q->bind_param("i", $topic_id);
    $stmt_fetch_q->execute();
    $result_q = $stmt_fetch_q->get_result();
    while ($row_q = $result_q->fetch_assoc()) {
        $questions[] = $row_q;
    }
    $stmt_fetch_q->close();
} else {
    error_log("Failed to prepare statement for fetching questions for topic (student view): " . $conn->error);
    set_message("Error fetching questions for this topic.", "danger");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Topic: <?php echo htmlspecialchars($topic['title']); ?></title>
    <link rel="stylesheet" href="../style.css">
    <style>
        /* Specific styles for video/PDF display within this page */
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
                    <li>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> (Student)</li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container main-content">
        <h2>Topic: <?php echo htmlspecialchars($topic['title']); ?></h2>
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
            <h3>Questions & Answers</h3>
            <form action="view_topic.php?id=<?php echo $topic_id; ?>" method="POST">
                <div class="form-group">
                    <label for="question_text">Ask a question:</label>
                    <textarea id="question_text" name="question_text" rows="3" required placeholder="Type your question here..."></textarea>
                </div>
                <button type="submit" name="submit_question" class="btn btn-primary">Submit Question</button>
            </form>

            <?php if (empty($questions)): ?>
                <p>No questions have been asked for this topic yet.</p>
            <?php else: ?>
                <?php foreach ($questions as $q): ?>
                    <div class="question-item">
                        <p><strong><?php echo htmlspecialchars($q['student_name']); ?> asked:</strong> <?php echo nl2br(htmlspecialchars($q['question_text'])); ?></p>
                        <small>Asked on: <?php echo date('Y-m-d H:i', strtotime($q['timestamp'])); ?></small>
                        <?php if (!empty($q['reply_text'])): ?>
                            <div class="reply">
                                <p><strong>Teacher Reply:</strong> <?php echo nl2br(htmlspecialchars($q['reply_text'])); ?></p>
                            </div>
                        <?php else: ?>
                            <p class="alert alert-info alert-sm" style="margin-top:10px;">Awaiting teacher reply.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>