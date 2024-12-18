<?php
// debug.php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

function debugRegistration($data) {
   // Check required fields
   $required = ['fullname', 'email', 'password', 'username', 'account_type'];
   foreach ($required as $field) {
       if (!isset($data[$field]) || empty(trim($data[$field]))) {
           return "Missing required field: $field";
       }
   }

   global $conn;
   
   try {
       // Check account_type value
       $account_type = $data['account_type'];
       if (!in_array($account_type, ['regular', 'educator'])) {
           return "Invalid account type: $account_type";
       }

       // Check if user exists
       $stmt = $conn->prepare("SELECT username FROM users WHERE username = ? OR email = ?");
       $stmt->bind_param("ss", $data['username'], $data['email']);
       $stmt->execute();
       if ($stmt->get_result()->num_rows > 0) {
           return "Username or email already exists";
       }

       // Check dashboard paths
       $user_path = "../pages/dashboard/user/index.php";
       $educator_path = "../pages/dashboard/educator/index.php";
       
       if (!file_exists($user_path)) {
           return "User dashboard path not found: $user_path";
       }
       if (!file_exists($educator_path)) {
           return "Educator dashboard path not found: $educator_path";
       }

       return "All checks passed. Ready for registration.";

   } catch (Exception $e) {
       return "Database error: " . $e->getMessage();
   }
}

// Usage in process_registration.php:
$debug_result = debugRegistration($_POST);
error_log("Debug result: " . $debug_result);
?>