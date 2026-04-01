<?php
// /Gymora/api/get_messages.php

// 1. Start output buffering to catch rogue spaces
ob_start(); 

// 2. Hide errors from the output so they don't corrupt JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../config/db.php';
require_once '../config/session.php';
require_once '../dss/audit_logger.php'; 

if (!isLoggedIn() || !isset($_GET['other_user_id'])) {
    ob_clean(); // Wipe buffer
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized or missing data']);
    exit();
}

$my_id = $_SESSION['user_id'];
$other_id = intval($_GET['other_user_id']);
$last_id = intval($_GET['last_message_id'] ?? 0);

try {
    $stmt = $pdo->prepare("
        SELECT id, sender_id, content, sent_at 
        FROM messages 
        WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
        AND id > ?
        ORDER BY id ASC
    ");
    $stmt->execute([$my_id, $other_id, $other_id, $my_id, $last_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // --- GDPR AUDIT LOGGING ---
    $medical_keywords = ['pain', 'injury', 'blood pressure', 'doctor', 'medication', 'surgery', 'dizzy', 'hypertension'];
    $medical_data_viewed = false;
    
    foreach ($messages as $msg) {
        foreach ($medical_keywords as $word) {
            if (stripos($msg['content'], $word) !== false) {
                $medical_data_viewed = true;
                break 2;
            }
        }
    }
    
    if ($medical_data_viewed) {
        logAudit($my_id, 'READ_MEDICAL_CHAT', 'messages', $other_id);
    }
    // --------------------------

    if (count($messages) > 0) {
        $updateStmt = $pdo->prepare("UPDATE messages SET read_at = NOW() WHERE receiver_id = ? AND sender_id = ? AND read_at IS NULL");
        $updateStmt->execute([$my_id, $other_id]);
    }

    foreach ($messages as &$msg) {
        $msg['time_formatted'] = date('M j, g:i a', strtotime($msg['sent_at']));
        $msg['is_mine'] = ($msg['sender_id'] == $my_id) ? true : false;
    }

    // 3. WIPE the buffer clean of any accidental spaces, set headers, and send pure JSON
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'messages' => $messages]);
    exit();

} catch (PDOException $e) {
    ob_clean();
    header('Content-Type: application/json');
    // We pass the actual database error back so you can see if the table is missing!
    echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
    exit();
}
?>