<?php
session_start();
require_once '../../../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = filter_input(INPUT_POST, 'event_id', FILTER_SANITIZE_NUMBER_INT);
    $user_id = $_SESSION['user_id'];
    $action = $_POST['action'] ?? 'join';

    try {
        if ($action === 'join') {
            // Check if already joined
            $check = $conn->prepare("SELECT 1 FROM event_participants WHERE event_id = ? AND user_id = ?");
            $check->bind_param("ii", $event_id, $user_id);
            $check->execute();

            if ($check->get_result()->num_rows === 0) {
                // Check max participants
                $max_check = $conn->prepare("
                    SELECT e.max_participants, COUNT(ep.user_id) as current_participants
                    FROM community_events e
                    LEFT JOIN event_participants ep ON e.event_id = ep.event_id
                    WHERE e.event_id = ?
                    GROUP BY e.event_id
                ");
                $max_check->bind_param("i", $event_id);
                $max_check->execute();
                $result = $max_check->get_result()->fetch_assoc();

                if ($result['max_participants'] > 0 && $result['current_participants'] >= $result['max_participants']) {
                    echo json_encode(['success' => false, 'message' => 'Event is full']);
                    exit();
                }

                $stmt = $conn->prepare("INSERT INTO event_participants (event_id, user_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $event_id, $user_id);
                $success = $stmt->execute();

                echo json_encode([
                    'success' => $success,
                    'message' => $success ? 'Successfully joined event' : 'Failed to join event'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Already joined this event']);
            }
        } else {
            $stmt = $conn->prepare("DELETE FROM event_participants WHERE event_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $event_id, $user_id);
            $success = $stmt->execute();

            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Successfully left event' : 'Failed to leave event'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>