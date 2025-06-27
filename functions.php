<?php
// includes/functions.php

/**
 * Fetches all comments for a given post ID.
 *
 * @param int $tree_id The ID of the post to fetch comments for.
 * @param mysqli $conn The database connection object.
 * @return array An array of comment data.
 */
function getCommentsForPost($tree_id, $conn) {
    $comments = [];
    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT c.content, c.created_at, u.username, c.parent_comment_id
                           FROM comments c
                           JOIN users u ON c.user_id = u.id
                           WHERE c.tree_id = ?
                           ORDER BY c.created_at ASC");

    if ($stmt) {
        $stmt->bind_param("i", $tree_id); // 'i' for integer (tree_id)
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }
        $stmt->close();
    } else {
        // Log the error in a real application
        error_log("Failed to prepare getCommentsForPost statement: " . $conn->error);
    }
    return $comments;
}
?>
