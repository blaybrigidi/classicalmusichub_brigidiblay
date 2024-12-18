<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Handle user actions (suspend/activate/change role)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? 0;
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'suspend':
            $stmt = $conn->prepare("UPDATE users SET status = 'suspended' WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            break;

        case 'activate':
            $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            break;

        case 'change_role':
            $new_role = $_POST['new_role'];
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
            $stmt->bind_param("si", $new_role, $user_id);
            $stmt->execute();
            break;
    }
}

// Fetch users with pagination
$page = $_GET['page'] ?? 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$users_query = "
    SELECT user_id, username, email, role, status, created_at 
    FROM users 
    WHERE role != 'admin'
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($users_query);
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$users = $stmt->get_result();

// Get total users for pagination
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role != 'admin'")->fetch_assoc()['count'];
$total_pages = ceil($total_users / $per_page);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Users - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #fef5e7;
            --accent: #c9a959;
            --glass: rgba(254, 245, 231, 0.03);
            --border: rgba(254, 245, 231, 0.1);
            --glass-hover: rgba(254, 245, 231, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Futura', sans-serif;
            background: linear-gradient(135deg, #000000, #1a1a1a);
            color: var(--primary);
            min-height: 100vh;
        }

        .page-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        .main-content {
            padding: 2rem;
            margin-left: 250px;
            width: calc(100% - 250px);
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            color: var(--accent);
            font-family: 'Didot', serif;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
            background: var(--glass);
            border-radius: 10px;
            overflow: hidden;
        }

        .users-table th,
        .users-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .users-table th {
            background: rgba(201, 169, 89, 0.1);
            color: var(--accent);
            font-weight: 500;
        }

        .users-table tr:hover {
            background: var(--glass-hover);
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--accent);
            color: var(--primary);
            margin-right: 0.5rem;
        }

        .action-btn:hover {
            opacity: 0.9;
        }

        .action-btn.suspend {
            background: #ff6b6b;
        }

        .action-btn.activate {
            background: #51cf66;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .pagination a {
            padding: 0.5rem 1rem;
            background: var(--glass);
            color: var(--primary);
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .pagination a:hover,
        .pagination a.active {
            background: var(--accent);
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .status-active {
            background: rgba(81, 207, 102, 0.2);
            color: #51cf66;
        }

        .status-suspended {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
        }
    </style>
</head>

<body>
    <div class="page-container">
        <?php include '../includes/components/admin_sidebar.php'; ?>

        <main class="main-content">
            <h1>Manage Users</h1>

            <table class="users-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo ucfirst($user['role']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $user['status']; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php if ($user['status'] === 'active'): ?>
                                    <button class="action-btn suspend"
                                        onclick="updateUser(<?php echo $user['user_id']; ?>, 'suspend')">
                                        Suspend
                                    </button>
                                <?php else: ?>
                                    <button class="action-btn activate"
                                        onclick="updateUser(<?php echo $user['user_id']; ?>, 'activate')">
                                        Activate
                                    </button>
                                <?php endif; ?>

                                <select onchange="changeRole(<?php echo $user['user_id']; ?>, this.value)"
                                    class="action-btn">
                                    <option value="">Change Role</option>
                                    <option value="educator">Educator</option>
                                    <option value="enthusiast">Enthusiast</option>
                                </select>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="<?php echo $page == $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </main>
    </div>

    <script>
        function updateUser(userId, action) {
            const form = new FormData();
            form.append('user_id', userId);
            form.append('action', action);

            fetch('manage_users.php', {
                method: 'POST',
                body: form
            }).then(() => window.location.reload());
        }

        function changeRole(userId, newRole) {
            if (!newRole) return;

            const form = new FormData();
            form.append('user_id', userId);
            form.append('action', 'change_role');
            form.append('new_role', newRole);

            fetch('manage_users.php', {
                method: 'POST',
                body: form
            }).then(() => window.location.reload());
        }
    </script>
</body>

</html>