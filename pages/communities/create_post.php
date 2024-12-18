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
$content = trim($_POST['content'] ?? '');

if (!$community_id || !$content) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

// Check if user is a member
$member_check = $conn->prepare("SELECT * FROM community_members WHERE community_id = ? AND user_id = ?");
$member_check->bind_param("ii", $community_id, $user_id);
$member_check->execute();

if ($member_check->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'You must be a member to post']);
    exit();
}

$stmt = $conn->prepare("INSERT INTO community_posts (community_id, user_id, content) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $community_id, $user_id, $content);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Post created successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error creating post']);
}