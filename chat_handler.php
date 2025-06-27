<?php
require '../config/db.php'; // Adjust the path if needed

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch') {
    $query = "SELECT * FROM chat_messages ORDER BY created_at ASC";
    $result = $conn->query($query);
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }

    echo json_encode($messages);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'send') {
    $message = $_POST['message'] ?? '';
    $sender = $_POST['sender'] ?? 'user';
    $user_id = $_POST['user_id'] ?? 0;

    if (trim($message) === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Message is empty']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, message, sender, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $user_id, $message, $sender);
    $stmt->execute();

    echo json_encode(['status' => 'success']);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
