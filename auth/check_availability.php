<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$response = ['available' => true, 'message' => '', 'field' => ''];

if (isset($data['username']) && isset($data['email'])) {
    // Check username
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $data['username']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $response = [
            'available' => false,
            'message' => 'This username is already taken',
            'field' => 'username'
        ];
        echo json_encode($response);
        exit;
    }

    // Check email
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $data['email']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $response = [
            'available' => false,
            'message' => 'This email is already registered',
            'field' => 'email'
        ];
    }
}

echo json_encode($response);