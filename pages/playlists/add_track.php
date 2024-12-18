<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['playlist_id']) || !isset($_POST['composition_id'])) {
    http_response_code(400);
    exit('Invalid request');
}

$playlist_id = (int)$_POST['playlist_id'];
$composition_id = (int)$_POST['composition_id'];

try {
    // Verify playlist ownership
    $stmt = $conn->prepare("SELECT user_id FROM playlists WHERE playlist_id = ?");
    $stmt->bind_param("i", $playlist_id);
    $stmt->execute();
    $playlist = $stmt->get_result()->fetch_assoc();

    if (!$playlist || $playlist['user_id'] !== $_SESSION['user_id']) {
        throw new Exception("Unauthorized");
    }

    // Get the highest position
    $stmt = $conn->prepare("SELECT MAX(position) as max_pos FROM playlist_items WHERE playlist_id = ?");
    $stmt->bind_param("i", $playlist_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $position = ($result['max_pos'] ?? 0) + 1;

    // Add the track
    $stmt = $conn->prepare("INSERT INTO playlist_items (playlist_id, composition_id, position) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $playlist_id, $composition_id, $position);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Error adding track");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}