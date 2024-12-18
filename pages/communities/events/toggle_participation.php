<?php
session_start();
require_once '../../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in first']);
    exit;
}

$event_id = $_POST['event_id'] ?? 0;
$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

try {
    if ($action === 'join') {
        $stmt = $conn->prepare("INSERT INTO event_participants (event_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $event_id, $user_id);
        $success = $stmt->execute();
        $message = $success ? 'Successfully joined the event' : 'Failed to join event';
    } elseif ($action === 'leave') {
        $stmt = $conn->prepare("DELETE FROM event_participants WHERE event_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $event_id, $user_id);
        $success = $stmt->execute();
        $message = $success ? 'Successfully left the event' : 'Failed to leave event';
    } else {
        throw new Exception('Invalid action');
    }

    echo json_encode(['success' => $success, 'message' => $message]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>