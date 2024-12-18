<?php
session_start();
require_once '../../../../includes/config.php';
require_once '../../../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'educator') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$event_id = isset($data['event_id']) ? (int) $data['event_id'] : 0;

if (!$event_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
    exit();
}

$stmt = $conn->prepare("DELETE FROM timeline_events WHERE event_id = ?");
$stmt->bind_param("i", $event_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
?>