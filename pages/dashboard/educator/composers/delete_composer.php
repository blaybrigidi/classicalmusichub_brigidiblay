<?php
session_start();
require_once '../../../../includes/config.php';
require_once '../../../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'educator') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
$composer_id = isset($data['composer_id']) ? (int) $data['composer_id'] : 0;

if (!$composer_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid composer ID']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Delete related records first
    $conn->query("DELETE FROM composer_followers WHERE composer_id = $composer_id");
    $conn->query("DELETE FROM compositions WHERE composer_id = $composer_id");
    $conn->query("DELETE FROM timeline_events WHERE composer_id = $composer_id");

    // Delete the composer
    $delete_stmt = $conn->prepare("DELETE FROM composers WHERE composer_id = ?");
    $delete_stmt->bind_param("i", $composer_id);

    if ($delete_stmt->execute()) {
        $conn->commit();
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Error deleting composer");
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>