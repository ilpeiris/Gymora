<?php
// /Gymora/api/send_message.php
error_reporting(0);
require_once '../config/db.php';
require_once '../config/session.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = intval($_POST['receiver_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    
    if ($receiver_id > 0 && !empty($content)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$sender_id, $receiver_id, $content]);
            
            echo json_encode(['status' => 'success', 'message_id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    }
}
?>