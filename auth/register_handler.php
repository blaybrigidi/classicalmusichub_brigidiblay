<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

$response = ['success' => false, 'error' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Validate username
    if (strlen($username) < 3) {
        $response['error'] = 'Username must be at least 3 characters long';
        echo json_encode($response);
        exit;
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['error'] = 'Please enter a valid email address';
        echo json_encode($response);
        exit;
    }

    // Validate password
    if (strlen($password) < 6) {
        $response['error'] = 'Password must be at least 6 characters long';
        echo json_encode($response);
        exit;
    }

    // Check if username exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $response['error'] = 'Username already exists';
        echo json_encode($response);
        exit;
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $response['error'] = 'Email already registered';
        echo json_encode($response);
        exit;
    }

    // If all validations pass, create the user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['redirect'] = 'login.php';
    } else {
        $response['error'] = 'Registration failed. Please try again.';
    }
}

echo json_encode($response);