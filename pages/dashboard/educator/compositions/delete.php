<?php
session_start();
require_once '../../../../includes/config.php';
require_once '../../../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'educator') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$composition_id = isset($data['composition_id']) ? (int) $data['composition_id'] : 0;

if (!$composition_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid composition ID']);
    exit();
}

// First get the file paths to delete the actual files
$stmt = $conn->prepare("SELECT sheet_music_file, preview_file FROM compositions WHERE composition_id = ?");
$stmt->bind_param("i", $composition_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if ($result) {
    // Delete the physical files if they exist
    if ($result['sheet_music_file']) {
        $file_path = '../../../../' . $result['sheet_music_file'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    if ($result['preview_file']) {
        $file_path = '../../../../' . $result['preview_file'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
}

// Delete the database record
$delete_stmt = $conn->prepare("DELETE FROM compositions WHERE composition_id = ?");
$delete_stmt->bind_param("i", $composition_id);

if ($delete_stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
?>