<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $required_fields = ['username', 'email', 'password', 'role'];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
                die("Required field missing: $field");
            }
        }

        // Sanitize inputs
        $username = $conn->real_escape_string(trim($_POST['username']));
        $email = $conn->real_escape_string(trim($_POST['email']));
        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $conn->real_escape_string($_POST['role']);
        $bio = isset($_POST['bio']) ? $conn->real_escape_string(trim($_POST['bio'])) : '';

        $conn->begin_transaction();

        // Check existing user
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        if (!$stmt)
            die("Prepare failed: " . $conn->error);

        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0)
            die("Username or email already exists");

        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role, bio) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt)
            die("Prepare failed: " . $conn->error);

        $stmt->bind_param("sssss", $username, $email, $password_hash, $role, $bio);
        if (!$stmt->execute())
            die("Error creating user: " . $stmt->error);

        $user_id = $conn->insert_id;

        // Handle profile pic
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_dir = '../uploads/profile_pics/';
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true))
                    die("Failed to create upload directory");
            }

            $file_extension = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($file_extension, $allowed_types))
                die("Invalid file type");

            $file_name = $user_id . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $file_path)) {
                $stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE user_id = ?");
                $stmt->bind_param("si", $file_path, $user_id);
                $stmt->execute();
            }
        }

        $conn->commit();

        // Set session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;

        // Add debugging statements
        error_log("Role: " . $role);
        error_log("Session role: " . $_SESSION['role']);

        // Redirect based on role
        if ($role === 'enthusiast') {
            header("Location: ../pages/dashboard/enthusiast/enthusiast_dashboard.php");
            exit();
        } else if ($role === 'educator') {
            header("Location: ../pages/dashboard/educator/educators_dashboard.php");
            exit();
        } else {
            die("Invalid role type");
        }

    } catch (Exception $e) {
        $conn->rollback();
        die("Error: " . $e->getMessage());
    }
} else {
    die("Invalid request method");
}
?>