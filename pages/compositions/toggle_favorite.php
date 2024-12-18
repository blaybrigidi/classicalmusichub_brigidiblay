<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in first']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['composition_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing composition ID']);
    exit();
}

$user_id = $_SESSION['user_id'];
$composition_id = $data['composition_id'];

// Verify composition exists
$comp_check = $conn->prepare("SELECT composition_id FROM compositions WHERE composition_id = ?");
$comp_check->bind_param("i", $composition_id);
$comp_check->execute();
if ($comp_check->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid composition ID']);
    exit();
}

// Check if already favorited
$check_stmt = $conn->prepare("SELECT user_id FROM favorites WHERE user_id = ? AND composition_id = ?");
$check_stmt->bind_param("ii", $user_id, $composition_id);
if (!$check_stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    // Remove from favorites
    $delete_stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND composition_id = ?");
    $delete_stmt->bind_param("ii", $user_id, $composition_id);
    $success = $delete_stmt->execute();
    if (!$success) {
        echo json_encode(['success' => false, 'message' => 'Error removing: ' . $conn->error]);
        exit();
    }

    echo json_encode([
        'success' => $success,
        'favorited' => false,
        'message' => $success ? 'Removed from favorites' : 'Error removing from favorites'
    ]);
} else {
    // Add to favorites
    $insert_stmt = $conn->prepare("INSERT INTO favorites (user_id, composition_id) VALUES (?, ?)");
    $insert_stmt->bind_param("ii", $user_id, $composition_id);
    $success = $insert_stmt->execute();
    if (!$success) {
        echo json_encode(['success' => false, 'message' => 'Error adding: ' . $conn->error]);
        exit();
    }

    echo json_encode([
        'success' => $success,
        'favorited' => true,
        'message' => $success ? 'Added to favorites' : 'Error adding to favorites'
    ]);
}