<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'enthusiast') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);
$playlist_id = (int) $data['playlist_id'];
$composition_id = (int) $data['composition_id'];

// Verify the playlist belongs to the user
$stmt = $conn->prepare("SELECT user_id FROM playlists WHERE playlist_id = ?");
$stmt->bind_param("i", $playlist_id);
$stmt->execute();
$result = $stmt->get_result();
$playlist = $result->fetch_assoc();

if (!$playlist || $playlist['user_id'] !== $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Invalid playlist']);
    exit();
}

// Add the composition to the playlist
$stmt = $conn->prepare("INSERT INTO playlist_items (playlist_id, composition_id) VALUES (?, ?)");
$stmt->bind_param("ii", $playlist_id, $composition_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error adding to playlist']);
}
?>