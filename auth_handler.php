<?php
// handlers/auth_handler.php

// Ensure session is started and database connection is available
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$feedback_message = '';
$message_type = ''; // 'success' or 'error'

// --- Handle User Registration ---
if (isset($_POST['register_submit'])) {
    $new_username = $_POST['register_username'];
    $new_email = $_POST['register_email'];
    $new_password = $_POST['register_password'];

    // Basic validation
    if (empty($new_username) || empty($new_email) || empty($new_password)) {
        $feedback_message = "All fields are required.";
        $message_type = 'error';
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $feedback_message = "Invalid email format.";
        $message_type = 'error';
    } else {
        // Hash the password for security
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Prepare and execute statement to prevent SQL injection
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        if ($stmt === false) {
             $feedback_message = "Error preparing statement: " . $conn->error;
             $message_type = 'error';
        } else {
            $stmt->bind_param("sss", $new_username, $new_email, $hashed_password);
            if ($stmt->execute()) {
                $feedback_message = "Registration successful! Please log in.";
                $message_type = 'success';
                // Optional: Automatically log in the user or redirect
                // $_SESSION['user_id'] = $stmt->insert_id;
                // $_SESSION['username'] = $new_username;
                // header('Location: index.php'); exit;
            } else {
                if ($conn->errno == 1062) { // Duplicate entry error
                    $feedback_message = "Username or Email already exists.";
                } else {
                    $feedback_message = "Error registering user: " . $stmt->error;
                }
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}

// --- Handle User Login ---
if (isset($_POST['login_submit'])) {
    $login_identifier = $_POST['login_identifier']; // Can be username or email
    $login_password = $_POST['login_password'];

    if (empty($login_identifier) || empty($login_password)) {
        $feedback_message = "Both fields are required.";
        $message_type = 'error';
    } else {
        // Check if identifier is email or username
        $field = filter_var($login_identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE {$field} = ?");
        if ($stmt === false) {
            $feedback_message = "Error preparing statement: " . $conn->error;
            $message_type = 'error';
        } else {
            $stmt->bind_param("s", $login_identifier);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (password_verify($login_password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $feedback_message = "Welcome, " . htmlspecialchars($user['username']) . "!";
                    $message_type = 'success';
                    header('Location: index.php'); // Redirect to avoid re-submission on refresh
                    exit;
                } else {
                    $feedback_message = "Incorrect password.";
                    $message_type = 'error';
                }
            } else {
                $feedback_message = "User not found.";
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}

// --- Handle User Logout ---
if (isset($_GET['logout'])) {
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    header('Location: index.php'); // Redirect to home page
    exit;
}
?>
