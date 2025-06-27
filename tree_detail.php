<?php
session_start();
require 'config/db.php';

// Get Tree ID from URL
$tree_id = isset($_GET['tree_id']) ? trim($_GET['tree_id']) : null;
if (!$tree_id) {
    die("Tree ID not provided.");
}

// Fetch tree info
$stmt = $conn->prepare("SELECT * FROM trees WHERE tree_id = ?");
$stmt->bind_param("s", $tree_id);
$stmt->execute();
$tree = $stmt->get_result()->fetch_assoc();

// Fetch comments related to tree
$comment_stmt = $conn->prepare("
    SELECT c.id, c.user_id, c.content, c.image_url, c.created_at, u.username 
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.tree_id = ?
    ORDER BY c.created_at DESC
");

$comment_stmt->bind_param("s", $tree_id);
$comment_stmt->execute();
$comments = $comment_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GeoTreess</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">

    <!-- Link to comment.css -->
    <link rel="stylesheet" href="/geotress2/comment.css">
</head>
<body>

<main class="container">

    <a href="#comment-section" class="view-comment-link"></a>

    <section class="new-post-form" id="comment-section">
        <h3>Comment on Tree: <?php echo htmlspecialchars($tree['name'] ?? 'Unknown'); ?></h3>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'Comment posted'): ?>
            <div class="message success">Your comment has been posted successfully!</div>
        <?php endif; ?>

        <form method="post" action="handlers/post_comment_handler.php" enctype="multipart/form-data">
            <input type="hidden" name="comment_tree_id" value="<?php echo htmlspecialchars($tree_id); ?>">

            <label>Tree ID:</label>
            <input type="text" value="<?php echo htmlspecialchars($tree_id); ?>" disabled>

            <label>Topic:</label>
            <input type="text" value="<?php echo htmlspecialchars($tree['name'] ?? 'Unknown'); ?>" disabled>

            <label for="comment_content">Your Comment:</label>
            <textarea name="comment_content" id="comment_content" rows="4" required placeholder="Enter your thoughts here..."></textarea>

            <label for="comment_image">Optional Image:</label>
            <input type="file" name="comment_image" id="comment_image" accept="image/*">

            <button type="submit" name="new_comment_submit">Post Comment</button>
        </form>
    </section>

    <section class="comments-section">
        <h3>Comments</h3>
        <?php if ($comments->num_rows > 0): ?>
            <?php while ($c = $comments->fetch_assoc()): ?>
                <div class="comment-item">
                    <div class="comment-author">
                        <?php echo htmlspecialchars($c['username']); ?>
                        <span class="comment-meta"><?php echo date('F j, Y, g:i a', strtotime($c['created_at'])); ?></span>
                    </div>
                    <p class="comment-text"><?php echo nl2br(htmlspecialchars($c['content'])); ?></p>
                    <?php if (!empty($c['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($c['image_url']); ?>" alt="Comment image">
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $c['user_id']): ?>
                        <div style="margin-top: 12px; display: flex; gap: 10px;">
                            <button onclick="enableEdit(this)" data-id="<?php echo $c['id']; ?>">✏️ Edit</button>

                            <form method="post" action="handlers/post_comment_handler.php" onsubmit="return confirm('Are you sure to delete this comment?');" style="display:inline;">
                                <input type="hidden" name="comment_id" value="<?php echo $c['id']; ?>">
                                <input type="hidden" name="tree_id" value="<?php echo htmlspecialchars($tree_id); ?>">
                                <button type="submit" name="delete_comment_submit">🗑️ Delete</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>

        <?php else: ?>
            <p class="no-comments">No comments yet. Be the first to share your thoughts!</p>
        <?php endif; ?>
<script>
function enableEdit(button) {
    const commentDiv = button.closest('.comment-item');
    const commentText = commentDiv.querySelector('.comment-text');
    const originalText = commentText.innerText;

    // Hide old text
    commentText.style.display = 'none';

    // Create textarea
    const textarea = document.createElement('textarea');
    textarea.name = 'updated_comment';
    textarea.value = originalText;
    textarea.rows = 4;
    textarea.style.width = '100%';

    // Hidden inputs
    const commentId = button.getAttribute('data-id');
    const treeId = "<?php echo htmlspecialchars($tree_id); ?>";

    // Create Save button
    const saveBtn = document.createElement('button');
    saveBtn.textContent = '💾 Save';
    saveBtn.style.marginTop = '10px';
    saveBtn.onclick = function (e) {
        e.preventDefault();

        const updatedText = textarea.value;

        fetch('handlers/post_comment_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                update_comment_submit: '1',
                comment_id: commentId,
                tree_id: treeId,
                updated_comment: updatedText
            })
        })
        .then(res => res.text())
        .then(res => {
            if (res.trim() === 'success') {
                commentText.textContent = updatedText;
                textarea.remove();
                saveBtn.remove();
                commentText.style.display = 'block';
                showPopup();
            } else {
                alert("❌ Failed to update: " + res);
            }
        })
        .catch(err => {
            alert("❌ Error: " + err);
        });
    };

    commentDiv.appendChild(textarea);
    commentDiv.appendChild(saveBtn);
    button.remove(); // remove the "edit" button
}

function showPopup() {
    const popup = document.getElementById('update-success-popup');
    popup.style.display = 'block';
    setTimeout(() => {
        popup.style.display = 'none';
    }, 3000); // hide after 3 seconds
}
</script>

<!-- ✅ Popup success box -->
<div id="update-success-popup">
    ✅ Comment successfully updated!
</div>

<div id="update-success-popup" style="
    display: none;
    position: fixed;
    top: 20px;
    right: 20px;
    background-color: #4CAF50;
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    font-weight: bold;
    z-index: 9999;
">
    ✅ Comment successfully updated!
</div>
</section>

 <

<script src="assets/js/chat.js"></script>
   
</main>
<!--Pop up after delete button-->
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'Comment deleted'): ?>
    <div id="delete-success-popup">🗑️ Comment successfully deleted!</div>
    <script>
        setTimeout(() => {
            document.getElementById('delete-success-popup').style.display = 'none';
        }, 3000);
    </script>
<?php endif; ?>

<style>
#delete-success-popup {
    position: fixed;
    top: 20px;
    right: 20px;
    background-color: #ff5e57;
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    font-weight: bold;
    z-index: 9999;
}
</style>

<script>
document.getElementById('chat-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const message = document.getElementById('chat-input').value;
    fetch('handlers/chat_handler.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ action: 'send', message: message })
    }).then(() => {
        document.getElementById('chat-input').value = '';
        loadMessages();
    });
});

function loadMessages() {
    fetch('handlers/chat_handler.php?action=fetch')
        .then(res => res.json())
        .then(data => {
            const chatBox = document.getElementById('chat-messages');
            chatBox.innerHTML = '';
            data.forEach(msg => {
                const div = document.createElement('div');
                div.innerHTML = `<strong>${msg.sender}:</strong> ${msg.message}`;
                chatBox.appendChild(div);
            });
            chatBox.scrollTop = chatBox.scrollHeight;
        });
}

setInterval(loadMessages, 2000); // refresh every 2 seconds
window.onload = loadMessages;
</script>
<!--JS live chat-->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const chatBox = document.getElementById("chat-box");
    const chatForm = document.getElementById("chat-form");
    const chatMessage = document.getElementById("chat-message");

    function fetchMessages() {
    fetch('handlers/chat_handler.php?action=fetch')
        .then(response => response.json())
        .then(messages => {
            chatBox.innerHTML = '';
            messages.forEach(msg => {
                const msgDiv = document.createElement('div');
                msgDiv.style.padding = '10px 15px';
                msgDiv.style.marginBottom = '10px';
                msgDiv.style.borderRadius = '12px';
                msgDiv.style.maxWidth = '80%';
                msgDiv.style.wordWrap = 'break-word';
                msgDiv.style.boxShadow = '0 2px 6px rgba(0,0,0,0.05)';

                if (msg.sender === 'admin') {
                    msgDiv.style.backgroundColor = '#e6f4ea'; // light green
                    msgDiv.style.alignSelf = 'flex-start';
                    msgDiv.style.textAlign = 'left';
                    msgDiv.innerHTML = `
                        <strong style="color:#2e7d32">👨‍💼 Admin:</strong><br>
                        ${msg.message}<br>
                        <small style="color:#888">${msg.created_at}</small>
                    `;
                } else {
                    msgDiv.style.backgroundColor = '#d9ecf2'; // light blue
                    msgDiv.style.alignSelf = 'flex-end';
                    msgDiv.style.textAlign = 'right';
                    msgDiv.innerHTML = `
                        <strong style="color:#1565c0">🧍 You:</strong><br>
                        ${msg.message}<br>
                        <small style="color:#888">${msg.created_at}</small>
                    `;
                }

                chatBox.appendChild(msgDiv);
            });

            // Auto-scroll
            chatBox.scrollTop = chatBox.scrollHeight;
        });
}


    chatForm.addEventListener("submit", function (e) {
        e.preventDefault();
        const msg = chatMessage.value.trim();
        if (!msg) return;

        fetch('handlers/chat_handler.php?action=send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `message=${encodeURIComponent(msg)}&sender=user`
        }).then(() => {
            chatMessage.value = '';
            fetchMessages();
        });
    });

    // Fetch every 3 seconds
    setInterval(fetchMessages, 3000);
    fetchMessages();
});
</script>

</body>
</html>