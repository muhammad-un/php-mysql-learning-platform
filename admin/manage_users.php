<?php
// project-root/admin/manage_users.php
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_role('admin'); // Only admins can access

// Handle user deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $user_id_to_delete = (int)$_GET['id'];

    // Prevent admin from deleting their own account
    if ($user_id_to_delete == $_SESSION['user_id']) {
        set_message("You cannot delete your own account.", "danger");
        redirect("manage_users.php");
    }

    // Check if the user to delete is the *only* admin
    $stmt_check_admin = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    if ($stmt_check_admin) {
        $stmt_check_admin->execute();
        $result_check_admin = $stmt_check_admin->get_result();
        $admin_count = $result_check_admin->fetch_row()[0];
        $stmt_check_admin->close();

        // Get the role of the user to be deleted
        $stmt_get_role = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
        if ($stmt_get_role) {
            $stmt_get_role->bind_param("i", $user_id_to_delete);
            $stmt_get_role->execute();
            $result_get_role = $stmt_get_role->get_result();
            $user_to_delete_role = $result_get_role->fetch_assoc()['role'];
            $stmt_get_role->close();

            if ($user_to_delete_role === 'admin' && $admin_count <= 1) {
                set_message("Cannot delete the last administrator account.", "danger");
                redirect("manage_users.php");
            }
        }
    }


    // Delete related data first (topics, questions) to maintain referential integrity
    // For teachers, delete their topics and associated questions
    $stmt_delete_teacher_topics = $conn->prepare("DELETE FROM topics WHERE teacher_id = ?");
    if ($stmt_delete_teacher_topics) {
        $stmt_delete_teacher_topics->bind_param("i", $user_id_to_delete);
        $stmt_delete_teacher_topics->execute();
        $stmt_delete_teacher_topics->close();
    }

    // For students, delete their questions
    $stmt_delete_student_questions = $conn->prepare("DELETE FROM questions WHERE student_id = ?");
    if ($stmt_delete_student_questions) {
        $stmt_delete_student_questions->bind_param("i", $user_id_to_delete);
        $stmt_delete_student_questions->execute();
        $stmt_delete_student_questions->close();
    }


    // Finally, delete the user
    $stmt_delete_user = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    if ($stmt_delete_user) {
        $stmt_delete_user->bind_param("i", $user_id_to_delete);
        if ($stmt_delete_user->execute()) {
            if ($stmt_delete_user->affected_rows > 0) {
                set_message("User deleted successfully!", "success");
            } else {
                set_message("User not found.", "warning");
            }
        } else {
            error_log("User deletion failed: " . $stmt_delete_user->error);
            set_message("Error deleting user. Please try again.", "danger");
        }
        $stmt_delete_user->close();
    } else {
        error_log("Failed to prepare user deletion statement: " . $conn->error);
        set_message("An internal error occurred.", "danger");
    }
    redirect("manage_users.php");
}

// Handle user role update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_role') {
    $user_id_to_update = sanitize_input($_POST['user_id'] ?? '');
    $new_role = sanitize_input($_POST['new_role'] ?? '');

    if (!is_numeric($user_id_to_update) || !in_array($new_role, ['student', 'teacher', 'admin'])) {
        set_message("Invalid user ID or role.", "danger");
    } else {
        // Prevent admin from changing their own role (or demoting themselves from last admin)
        if ($user_id_to_update == $_SESSION['user_id']) {
            if ($new_role !== 'admin') {
                $stmt_check_admin = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
                if ($stmt_check_admin) {
                    $stmt_check_admin->execute();
                    $result_check_admin = $stmt_check_admin->get_result();
                    $admin_count = $result_check_admin->fetch_row()[0];
                    $stmt_check_admin->close();

                    if ($admin_count <= 1) {
                        set_message("Cannot demote the last administrator account.", "danger");
                        redirect("manage_users.php");
                    }
                }
            } else {
                // If it's the current admin and the new role is admin, no change needed.
                set_message("Your role is already Admin.", "info");
                redirect("manage_users.php");
            }
        }

        $stmt_update = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
        if ($stmt_update) {
            $stmt_update->bind_param("si", $new_role, $user_id_to_update);
            if ($stmt_update->execute()) {
                set_message("User role updated successfully!", "success");
            } else {
                error_log("User role update failed for user_id: " . $user_id_to_update . " Error: " . $stmt_update->error);
                set_message("Failed to update user role. Please try again.", "danger");
            }
            $stmt_update->close();
        } else {
            error_log("Failed to prepare user role update statement: " . $conn->error);
            set_message("An internal error occurred.", "danger");
        }
    }
    redirect("manage_users.php");
}

// Fetch all users
$users = [];
$stmt = $conn->prepare("SELECT user_id, name, email, role, registration_date FROM users ORDER BY registration_date DESC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();
} else {
    error_log("Failed to prepare statement for fetching all users (admin): " . $conn->error);
    set_message("Error fetching user list.", "danger");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
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
                    <li><a href="manage_topics.php">Manage Topics</a></li>
                    <li><a href="manage_questions.php">Manage Q&A</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container main-content">
        <h2>Manage Users</h2>
        <?php display_message(); ?>

        <?php if (empty($users)): ?>
            <p>No users registered yet.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registered On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <form action="manage_users.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="update_role">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <select name="new_role" onchange="this.form.submit()">
                                        <option value="student" <?php echo ($user['role'] == 'student') ? 'selected' : ''; ?>>Student</option>
                                        <option value="teacher" <?php echo ($user['role'] == 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                                        <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </form>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($user['registration_date'])); ?></td>
                            <td>
                                <?php if ($user['user_id'] != $_SESSION['user_id']): // Prevent deleting own account ?>
                                    <a href="manage_users.php?action=delete&id=<?php echo $user['user_id']; ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Are you sure you want to delete this user? This will also delete their topics and questions!');">Delete</a>
                                <?php else: ?>
                                    <span class="btn btn-warning btn-sm" disabled title="Cannot delete your own account">Self</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>