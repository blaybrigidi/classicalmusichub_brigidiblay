<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in first']);
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'] ?? 0;
$content = trim($_POST['content'] ?? '');

if (!$post_id || !$content) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$stmt = $conn->prepare("INSERT INTO post_replies (post_id, user_id, content) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $post_id, $user_id, $content);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Reply posted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error posting reply']);
}
?>