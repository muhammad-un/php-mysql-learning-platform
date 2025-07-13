<?php
// project-root/teacher/upload_topic.php
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_role('teacher'); // Ensure only teachers (or admins) can access

$teacher_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize_input($_POST['title'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $video_url = sanitize_input($_POST['video_url'] ?? ''); // For YouTube links or external URLs
    $pdf_path = ''; // Will store the path if a PDF is uploaded

    $errors = [];

    // --- Input Validation ---
    if (empty($title)) {
        $errors[] = "Topic title is required.";
    }
    if (empty($description)) {
        $errors[] = "Topic description is required.";
    }
    // Ensure either a video URL or a video file is provided
    if (empty($video_url) && (!isset($_FILES['video_file']['tmp_name']) || $_FILES['video_file']['error'] == UPLOAD_ERR_NO_FILE)) {
        $errors[] = "Please provide a video URL or upload a video file.";
    }
    // PDF notes are required
    if (!isset($_FILES['pdf_file']['tmp_name']) || $_FILES['pdf_file']['error'] != UPLOAD_ERR_OK) {
        $errors[] = "PDF notes are required or there was an upload error (Error Code: " . ($_FILES['pdf_file']['error'] ?? 'N/A') . ").";
    }

    // --- Handle PDF Upload ---
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] == UPLOAD_ERR_OK) {
        $pdf_file = $_FILES['pdf_file'];
        $pdf_target_dir = "../uploads/pdf/"; // Relative to this script, points to project-root/uploads/pdf/
        $pdf_file_extension = strtolower(pathinfo($pdf_file['name'], PATHINFO_EXTENSION));
        $pdf_allowed_extensions = ['pdf'];
        $pdf_max_size = 50 * 1024 * 1024; // 50 MB (in bytes)

        // Validate PDF file type and size
        if (!in_array($pdf_file_extension, $pdf_allowed_extensions)) {
            $errors[] = "Only PDF files are allowed for notes. Detected: ." . $pdf_file_extension;
        }
        if ($pdf_file['size'] > $pdf_max_size) {
            $errors[] = "PDF file size exceeds the 50MB limit.";
        }

        // Attempt to move uploaded PDF if no errors yet
        if (empty($errors)) {
            // Generate a unique name for the PDF file to prevent conflicts
            $pdf_unique_name = uniqid('pdf_', true) . '.' . $pdf_file_extension;
            $pdf_target_file = $pdf_target_dir . $pdf_unique_name;

            // Ensure the upload directory exists and is writable
            if (!is_dir($pdf_target_dir)) {
                mkdir($pdf_target_dir, 0755, true); // Create directory if it doesn't exist
            }
            if (!is_writable($pdf_target_dir)) {
                error_log("PDF upload directory not writable: " . $pdf_target_dir);
                $errors[] = "Server error: PDF upload directory not writable. Please contact support.";
            } else {
                if (!move_uploaded_file($pdf_file['tmp_name'], $pdf_target_file)) {
                    $errors[] = "Failed to upload PDF file. Please check server permissions or try again.";
                    error_log("PDF upload failed: " . $pdf_file['name'] . " to " . $pdf_target_file . " - PHP Error: " . error_get_last()['message']);
                } else {
                    $pdf_path = "uploads/pdf/" . $pdf_unique_name; // Path to store in DB (relative to project root)
                }
            }
        }
    }

    // --- Handle Video Upload (if chosen instead of URL) ---
    $final_video_source = $video_url; // Default to URL
    if (empty($video_url) && isset($_FILES['video_file']) && $_FILES['video_file']['error'] == UPLOAD_ERR_OK) {
        $video_file = $_FILES['video_file'];
        $video_target_dir = "../uploads/videos/"; // Relative to this script
        $video_file_extension = strtolower(pathinfo($video_file['name'], PATHINFO_EXTENSION));
        $video_allowed_extensions = ['mp4', 'webm', 'ogg']; // Common web video formats
        $video_max_size = 500 * 1024 * 1024; // 500 MB (in bytes)

        // Validate video file type and size
        if (!in_array($video_file_extension, $video_allowed_extensions)) {
            $errors[] = "Only MP4, WebM, or Ogg video files are allowed. Detected: ." . $video_file_extension;
        }
        if ($video_file['size'] > $video_max_size) {
            $errors[] = "Video file size exceeds the 500MB limit.";
        }

        // Attempt to move uploaded video if no errors yet
        if (empty($errors)) {
            $video_unique_name = uniqid('video_', true) . '.' . $video_file_extension;
            $video_target_file = $video_target_dir . $video_unique_name;

            // Ensure the upload directory exists and is writable
            if (!is_dir($video_target_dir)) {
                mkdir($video_target_dir, 0755, true);
            }
            if (!is_writable($video_target_dir)) {
                error_log("Video upload directory not writable: " . $video_target_dir);
                $errors[] = "Server error: Video upload directory not writable. Please contact support.";
            } else {
                if (!move_uploaded_file($video_file['tmp_name'], $video_target_file)) {
                    $errors[] = "Failed to upload video file. Please check server permissions or try again.";
                    error_log("Video upload failed: " . $video_file['name'] . " to " . $video_target_file . " - PHP Error: " . error_get_last()['message']);
                } else {
                    $final_video_source = "uploads/videos/" . $video_unique_name; // Path to store in DB
                }
            }
        }
    }


    // If no errors after all validations and uploads
    if (empty($errors)) {
        // Insert topic into database using prepared statement
        $stmt = $conn->prepare("INSERT INTO topics (teacher_id, title, description, video_url, pdf_path) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("issss", $teacher_id, $title, $description, $final_video_source, $pdf_path);

            if ($stmt->execute()) {
                set_message("Topic uploaded successfully!", "success");
                redirect("dashboard.php"); // Redirect to dashboard on success
            } else {
                // Log database execution error
                error_log("Topic upload failed for teacher_id: " . $teacher_id . " Error: " . $stmt->error);
                set_message("Failed to upload topic to database. Please try again.", "danger");

                // --- IMPORTANT: Clean up uploaded files if DB insert fails ---
                if (!empty($pdf_path) && file_exists("../" . $pdf_path)) {
                    unlink("../" . $pdf_path); // Delete the PDF file
                }
                // Check if video was a local upload before attempting to delete
                if (!empty($final_video_source) && strpos($final_video_source, 'uploads/videos/') !== false && file_exists("../" . $final_video_source)) {
                    unlink("../" . $final_video_source); // Delete the video file
                }
            }
            $stmt->close();
        } else {
             // Log database preparation error
             error_log("Failed to prepare statement for topic upload: " . $conn->error);
             set_message("An internal server error occurred during topic creation. Please try again.", "danger");
             // No files to delete here as preparation failed before execution
        }
    } else {
        // Display all accumulated errors to the user
        foreach ($errors as $error) {
            set_message($error, "danger");
        }
        // If file uploads succeeded but DB insert failed (and cleaned up),
        // or if file uploads themselves failed, no further action needed here.
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload New Topic</title>
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
                    <li>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> (Teacher)</li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container main-content">
        <h2>Upload New Topic</h2>
        <?php display_message(); ?>
        <form action="upload_topic.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Topic Title:</label>
                <input type="text" id="title" name="title" required
                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label for="video_url">Video URL (YouTube/Vimeo embed link, optional if uploading file):</label>
                <input type="text" id="video_url" name="video_url"
                       placeholder="e.g., https://www.youtube.com/watch?v=dQw4w9WgXcQ or https://vimeo.com/your-video-id"
                       value="<?php echo htmlspecialchars($_POST['video_url'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>OR Upload Video File (MP4, WebM, Ogg - max 500MB):</label>
                <input type="file" id="video_file" name="video_file" accept="video/mp4,video/webm,video/ogg">
            </div>
            <div class="form-group">
                <label for="pdf_file">Upload PDF Notes (max 50MB):</label>
                <input type="file" id="pdf_file" name="pdf_file" accept="application/pdf" required>
            </div>
            <button type="submit" class="btn btn-success">Upload Topic</button>
        </form>
    </div>
</body>
</html>