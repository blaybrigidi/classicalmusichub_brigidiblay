<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../../index.php');
    exit();
}

// Fetch basic statistics
$stats = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM users WHERE role != 'admin') as total_users,
        (SELECT COUNT(*) FROM communities) as total_communities,
        (SELECT COUNT(*) FROM reports WHERE status = 'pending') as pending_reports
")->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Classical Music Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #fef5e7;
            --accent: #c9a959;
            --glass: rgba(254, 245, 231, 0.03);
            --border: rgba(254, 245, 231, 0.1);
            --glass-hover: rgba(254, 245, 231, 0.08);
        }

        .admin-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .admin-card {
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 2rem;
            transition: all 0.3s ease;
        }

        .admin-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
        }

        .stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--glass);
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            color: var(--accent);
            margin-bottom: 0.5rem;
        }

        .action-btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--accent);
            color: var(--primary);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <div class="page-container">
        <?php include '../../../includes/components/sidebar.php'; ?>

        <main class="main-content">
            <h1>Admin Dashboard</h1>

            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_communities']; ?></div>
                    <div class="stat-label">Communities</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['pending_reports']; ?></div>
                    <div class="stat-label">Pending Reports</div>
                </div>
            </div>

            <div class="admin-actions">
                <div class="admin-card">
                    <h2>User Management</h2>
                    <p>Manage user accounts and roles</p>
                    <a href="users/manage.php" class="action-btn">Manage Users</a>
                </div>

                <div class="admin-card">
                    <h2>Content Moderation</h2>
                    <p>Review and moderate reported content</p>
                    <a href="moderation/reports.php" class="action-btn">View Reports</a>
                </div>

                <div class="admin-card">
                    <h2>Community Oversight</h2>
                    <p>Manage and monitor communities</p>
                    <a href="communities/manage.php" class="action-btn">Manage Communities</a>
                </div>
            </div>
        </main>
    </div>
</body>

</html>