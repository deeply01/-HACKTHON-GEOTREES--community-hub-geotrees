<?php
session_start(); 
require '../config/db.php';

// Helper function to get best matching auto response
function getAutoReply($conn, $userMessage) {
    $userMessage = strtolower(trim($userMessage));

    // 1. Try exact match
    $stmt = $conn->prepare("SELECT * FROM auto_responses WHERE trigger_keyword = ? AND response_type = 'exact' AND is_active = 1 ORDER BY priority DESC LIMIT 1");
    $stmt->bind_param("s", $userMessage);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        incrementUsage($conn, $row['id']);
        return $row['response_text'];
    }

    // 2. Try contains match
    $stmt = $conn->prepare("SELECT * FROM auto_responses WHERE response_type = 'contains' AND is_active = 1 ORDER BY priority DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (stripos($userMessage, $row['trigger_keyword']) !== false) {
            incrementUsage($conn, $row['id']);
            return $row['response_text'];
        }
    }

    // 3. Fallback
    $stmt = $conn->prepare("SELECT * FROM auto_responses WHERE response_type = 'fallback' AND is_active = 1 ORDER BY priority DESC LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        incrementUsage($conn, $row['id']);
        return $row['response_text'];
    }

    return "I'm sorry, I didn't understand that. Please try again.";
}

function incrementUsage($conn, $responseId) {
    $stmt = $conn->prepare("UPDATE auto_responses SET usage_count = usage_count + 1 WHERE id = ?");
    $stmt->bind_param("i", $responseId);
    $stmt->execute();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['action'] === 'send') {
    $message = $_POST['message'] ?? '';
    $sender = $_POST['sender'] ?? 'user';
    $user_id = $_POST['user_id'] ?? 0;

    if (empty($message) || !$user_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid message or user ID.']);
        exit;
    }

    // Save message
    $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, message, sender, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $user_id, $message, $sender);
    $stmt->execute();

    if ($sender === 'user') {
        $reply = getAutoReply($conn, $message);
        $stmt2 = $conn->prepare("INSERT INTO chat_messages (user_id, message, sender, created_at) VALUES (?, ?, 'bot', NOW())");
        $stmt2->bind_param("is", $user_id, $reply);
        $stmt2->execute();
    }

    echo json_encode(['status' => 'ok']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_GET['action'] === 'fetch') {
    $user_id = $_GET['user_id'] ?? 0;

    if (!$user_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing user ID.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT sender, message, created_at FROM chat_messages WHERE user_id = ? ORDER BY created_at ASC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }

    echo json_encode($messages);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request.']);
exit;