<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $profile_pic = $_FILES['profile_pic'] ?? null;

    // Validate inputs
    if (empty($username) || empty($email)) {
        $message = "Username and email cannot be empty.";
    } else {
        // Handle profile picture upload
        $profile_pic_path = null;
        if ($profile_pic && $profile_pic['size'] > 0) {
            $upload_dir = '../../uploads/profile_pics/';

            // Create directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_ext = strtolower(pathinfo($profile_pic['name'], PATHINFO_EXTENSION));
            $file_name = uniqid() . '.' . $file_ext;
            $upload_path = $upload_dir . $file_name;

            // Validate file type and size
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            $max_file_size = 5 * 1024 * 1024; // 5MB

            if (in_array($file_ext, $allowed_types) && $profile_pic['size'] <= $max_file_size) {
                if (move_uploaded_file($profile_pic['tmp_name'], $upload_path)) {
                    $profile_pic_path = $upload_path;
                } else {
                    $message = "Failed to upload profile picture.";
                }
            } else {
                $message = "Invalid file type or file too large.";
            }
        }

        // Prepare SQL based on whether profile picture was uploaded
        if ($profile_pic_path) {
            $sql = "UPDATE users SET username = ?, email = ?, profile_pic = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $username, $email, $profile_pic_path, $user_id);
        } else {
            $sql = "UPDATE users SET username = ?, email = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $username, $email, $user_id);
        }

        // Execute the query
        if ($stmt->execute()) {
            $message = "Settings updated successfully.";
        } else {
            $message = "Failed to update settings: " . $stmt->error;
        }
        $stmt->close();
    }

    // Redirect or show message
    if (empty($message)) {
        header('Location: settings.php');
        exit();
    }
}
?>