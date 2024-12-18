<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$composer_id = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT * FROM composers WHERE composer_id = ?");
$stmt->bind_param("i", $composer_id);
$stmt->execute();
$composer = $stmt->get_result()->fetch_assoc();

header('Content-Type: application/json');
echo json_encode($composer);