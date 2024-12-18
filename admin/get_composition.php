<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$composition_id = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT * FROM compositions WHERE composition_id = ?");
$stmt->bind_param("i", $composition_id);
$stmt->execute();
$composition = $stmt->get_result()->fetch_assoc();

header('Content-Type: application/json');
echo json_encode($composition);