<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in first']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$composition_id = (int) $data['composition_id'];
$action = $data['action']; // 'add' or 'remove'

if ($action === 'add') {
    $stmt = $conn->prepare("INSERT IGNORE INTO user_favorites (user_id, composition_id) VALUES (?, ?)");
} else {
    $stmt = $conn->prepare("DELETE FROM user_favorites WHERE user_id = ? AND composition_id = ?");
}

$stmt->bind_param("ii", $_SESSION['user_id'], $composition_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating favorites']);
}
?>