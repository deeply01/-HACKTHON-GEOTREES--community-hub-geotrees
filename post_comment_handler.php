<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/db.php';

$upload_dir = dirname(__DIR__) . '/uploads/';
$relative_web_path = 'uploads/';

$allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
$max_file_size = 5 * 1024 * 1024; // 5 MB

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

function handleFileUpload($file_input, $upload_dir, $relative_web_path, $allowed_types, $max_file_size) {
    if (!isset($file_input) || empty($file_input['name']) || $file_input['size'] == 0) return null;
    if ($file_input['error'] !== UPLOAD_ERR_OK) return null;

    $file_name = $file_input['name'];
    $file_tmp_name = $file_input['tmp_name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed_types)) return null;
    if ($file_input['size'] > $max_file_size) return null;

    $new_file_name = uniqid('img_', true) . '.' . $file_ext;
    $target_file_path = $upload_dir . $new_file_name;

    if (move_uploaded_file($file_tmp_name, $target_file_path)) {
        return $relative_web_path . $new_file_name;
    } else {
        return null;
    }
}

// ---------- COMMENT SUBMISSION ----------
if (isset($_POST['new_comment_submit']) && isset($_SESSION['user_id'])) {
    $tree_id = $_POST['comment_tree_id'] ?? null;
    $comment = trim($_POST['comment_content'] ?? '');
    $user_id = $_SESSION['user_id'];
    $parent_id = !empty($_POST['parent_comment_id']) ? (int)$_POST['parent_comment_id'] : null;
    $image_url = null;

    if (isset($_FILES['comment_image']) && $_FILES['comment_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $image_url = handleFileUpload($_FILES['comment_image'], $upload_dir, $relative_web_path, $allowed_types, $max_file_size);
    }

    if (!$tree_id || empty($comment)) {
        die("Missing tree ID or comment.");
    }

    $stmt = $conn->prepare("INSERT INTO comments (tree_id, user_id, parent_comment_id, content, image_url) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("siiss", $tree_id, $user_id, $parent_id, $comment, $image_url);

    if ($stmt->execute()) {
        header("Location: ../tree_detail.php?tree_id=" . urlencode($tree_id) . "&msg=Comment+posted#comment-section");
        exit();
    } else {
        die("Error saving comment: " . $stmt->error);
    }
}

// ---------- COMMENT UPDATE (within 1 hour) ----------
if (isset($_POST['update_comment_submit']) && isset($_SESSION['user_id'])) {
    $comment_id = $_POST['comment_id'] ?? null;
    $tree_id = $_POST['tree_id'] ?? null;
    $updated_comment = trim($_POST['updated_comment'] ?? '');

    if (!$comment_id || !$updated_comment || !$tree_id) {
        http_response_code(400);
        echo "Incomplete update data.";
        exit();
    }

    $stmt = $conn->prepare("SELECT user_id, created_at FROM comments WHERE id = ?");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $stmt->bind_result($owner_id, $created_at);
    $stmt->fetch();
    $stmt->close();

    if ($owner_id != $_SESSION['user_id']) {
        http_response_code(403);
        echo "Unauthorized update.";
        exit();
    }

    if (strtotime($created_at) < time() - 3600) {
        http_response_code(403);
        echo "Edit window expired.";
        exit();
    }

    $update_stmt = $conn->prepare("UPDATE comments SET content = ? WHERE id = ?");
    $update_stmt->bind_param("si", $updated_comment, $comment_id);

    if ($update_stmt->execute()) {
        echo "success";
    } else {
        http_response_code(500);
        echo "Error updating comment: " . $update_stmt->error;
    }
    exit();
}

// ---------- COMMENT DELETE ----------
if (isset($_POST['delete_comment_submit']) && isset($_SESSION['user_id'])) {
    $comment_id = $_POST['comment_id'] ?? null;
    $tree_id = $_POST['tree_id'] ?? null;

    if (!$comment_id || !$tree_id) {
        die("Missing comment ID or tree ID.");
    }

    $stmt = $conn->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $stmt->bind_result($owner_id);
    $stmt->fetch();
    $stmt->close();

    if ($owner_id != $_SESSION['user_id']) {
        die("Unauthorized action.");
    }

    $delete_stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
    $delete_stmt->bind_param("i", $comment_id);

    if ($delete_stmt->execute()) {
        header("Location: ../tree_detail.php?tree_id=" . urlencode($tree_id) . "&msg=Comment+deleted#comment-section");
        exit();
    } else {
        die("Error deleting comment: " . $delete_stmt->error);
    }
}
?>
