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
$community_id = $_POST['community_id'] ?? 0;
$action = $_POST['action'] ?? '';

if (!$community_id || !in_array($action, ['join', 'leave'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

if ($action === 'join') {
    $stmt = $conn->prepare("INSERT INTO community_members (community_id, user_id) VALUES (?, ?)");
} else {
    $stmt = $conn->prepare("DELETE FROM community_members WHERE community_id = ? AND user_id = ?");
}

$stmt->bind_param("ii", $community_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => $action === 'join' ? 'Joined successfully' : 'Left successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating membership']);
}
?>