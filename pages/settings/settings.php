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

// Fetch current user data
try {
    $user_stmt = $conn->prepare("SELECT username, email FROM users WHERE user_id = ?");

    if (!$user_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $user_stmt->bind_param("i", $user_id);

    if (!$user_stmt->execute()) {
        throw new Exception("Execute failed: " . $user_stmt->error);
    }

    $result = $user_stmt->get_result();

    if (!$result) {
        throw new Exception("Get result failed: " . $user_stmt->error);
    }

    $user = $result->fetch_assoc();

    if (!$user) {
        throw new Exception("No user found with ID: " . $user_id);
    }
} catch (Exception $e) {
    $message = "Database error: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Verify current password
        $verify_stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");

        if (!$verify_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $verify_stmt->bind_param("i", $user_id);

        if (!$verify_stmt->execute()) {
            throw new Exception("Execute failed: " . $verify_stmt->error);
        }

        $result = $verify_stmt->get_result();
        $stored_password = $result->fetch_assoc()['password_hash'];

        if (!password_verify($current_password, $stored_password)) {
            throw new Exception("Current password is incorrect.");
        }

        // Update username and email
        $update_stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE user_id = ?");

        if (!$update_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $update_stmt->bind_param("ssi", $username, $email, $user_id);

        if (!$update_stmt->execute()) {
            throw new Exception("Execute failed: " . $update_stmt->error);
        }

        // Update password if provided
        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                throw new Exception("New passwords do not match.");
            }

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $password_stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");

            if (!$password_stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $password_stmt->bind_param("si", $hashed_password, $user_id);

            if (!$password_stmt->execute()) {
                throw new Exception("Execute failed: " . $password_stmt->error);
            }

            $message = "Settings updated successfully including password.";
        } else {
            $message = "Settings updated successfully.";
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Settings - Classical Music Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #121212;
            --bg-light: #1e1e1e;
            --text-primary: #fef5e7;
            --text-secondary: #c4b69c;
            --accent: #8b7355;
            --border-color: #3a3a3a;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Futura', sans-serif;
            background: linear-gradient(135deg, #000000, #1a1a1a);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.6;
        }

        .page-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        /* Sidebar styles */
        .sidebar {
            background: rgba(31, 31, 31, 0.8);
            padding: 2rem;
            border-right: 1px solid rgba(254, 245, 231, 0.1);
            backdrop-filter: blur(10px);
            height: 100vh;
            position: sticky;
            top: 0;
        }

        .sidebar-logo {
            color: var(--text-primary);
            font-size: 1.5rem;
            margin-bottom: 2rem;
            font-weight: bold;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu a {
            color: var(--text-secondary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-bottom: 0.5rem;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(254, 245, 231, 0.1);
            color: var(--text-primary);
        }

        .sidebar-menu i {
            width: 20px;
            text-align: center;
        }

        .main-content {
            padding: 2rem;
            width: 100%;
        }

        .settings-form {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(254, 245, 231, 0.03);
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid rgba(254, 245, 231, 0.1);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .form-group input {
            width: 100%;
            padding: 1rem;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            background: rgba(254, 245, 231, 0.05);
            color: var(--text-primary);
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(254, 245, 231, 0.08);
        }

        .btn-submit {
            background: var(--accent);
            color: var(--text-primary);
            padding: 1rem 2rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1rem;
            width: 100%;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
        }

        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 6px;
            background: rgba(139, 115, 85, 0.1);
            border: 1px solid var(--accent);
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(45deg, var(--text-primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-header p {
            color: var(--text-secondary);
        }
    </style>
</head>

<body>
    <div class="page-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">Classical Music Hub</div>
            <ul class="sidebar-menu">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'educator'): ?>
                    <li><a href="../dashboard/educator/educators_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    </li>
                    <li><a href="../compositions/list.php"><i class="fas fa-music"></i> Browse Music</a></li>
                    <li><a href="../dashboard/educator/composers/manage.php"><i class="fas fa-user"></i> Composers</a></li>
                    <li><a href="../dashboard/educator/timeline/manage.php"><i class="fas fa-clock"></i> Timeline</a></li>
                    <li><a href="#" class="active"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="../../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'enthusiast'): ?>
                    <li><a href="../dashboard/enthusiast/enthusiast_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    </li>
                    <li><a href="../compositions/list.php"><i class="fas fa-music"></i> Browse Music</a></li>
                    <li><a href="../dashboard/enthusiast/playlists.php"><i class="fas fa-list"></i> My Playlists</a></li>
                    <li><a href="../library/favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
                    <li><a href="#" class="active"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="../../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <?php endif; ?>
            </ul>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>Account Settings</h1>
                <p>Manage your account preferences</p>
            </div>

            <?php if ($message): ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form class="settings-form" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username"
                        value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password (leave blank to keep current)</label>
                    <input type="password" id="new_password" name="new_password">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>

                <button type="submit" class="btn-submit">Save Changes</button>
            </form>
        </main>
    </div>
</body>

</html>