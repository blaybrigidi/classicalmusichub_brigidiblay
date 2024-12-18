<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Fetch statistics
$stats = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM users WHERE role != 'admin') as total_users,
        (SELECT COUNT(*) FROM users WHERE role = 'educator') as total_educators,
        (SELECT COUNT(*) FROM users WHERE role = 'enthusiast') as total_enthusiasts,
        (SELECT COUNT(*) FROM compositions) as total_compositions,
        (SELECT COUNT(*) FROM composers) as total_composers,
        (SELECT COUNT(*) FROM communities) as total_communities
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
            display: flex;
            /* Change from grid to flex */
            flex-direction: column;
            /* Adjust layout to stack vertically */
            min-height: 100vh;
        }

        .main-content {
            padding: 2rem;
        }

        .dashboard-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .dashboard-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(45deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
        }

        .stat-number {
            font-size: 2.5rem;
            color: var(--accent);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--primary);
            font-size: 1rem;
            opacity: 0.8;
        }

        .admin-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .action-card {
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 2rem;
            transition: all 0.3s ease;
        }

        .action-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
            background: var(--glass-hover);
        }

        .action-card h2 {
            color: var(--accent);
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .action-card p {
            margin-bottom: 1.5rem;
            opacity: 0.8;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
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

        .logout-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 1rem;
            margin-left: auto;
        }

        .logout-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <div class="page-container">
        <main class="main-content">
            <div class="dashboard-header">
                <h1>Admin Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                <a href="../auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_educators']; ?></div>
                    <div class="stat-label">Educators</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_enthusiasts']; ?></div>
                    <div class="stat-label">Enthusiasts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_compositions']; ?></div>
                    <div class="stat-label">Compositions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_composers']; ?></div>
                    <div class="stat-label">Composers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_communities']; ?></div>
                    <div class="stat-label">Communities</div>
                </div>
            </div>

            <div class="admin-actions">
                <div class="action-card">
                    <h2><i class="fas fa-users"></i> User Management</h2>
                    <p>Manage user accounts, roles, and permissions</p>
                    <a href="manage_users.php" class="action-btn">
                        <i class="fas fa-cog"></i> Manage Users
                    </a>
                </div>

                <div class="action-card">
                    <h2><i class="fas fa-music"></i> Content Management</h2>
                    <p>Manage compositions and musical content</p>
                    <a href="manage_music.php" class="action-btn">
                        <i class="fas fa-cog"></i> Manage Content
                    </a>
                </div>

                <div class="action-card">
                    <h2><i class="fas fa-user"></i> Composer Management</h2>
                    <p>Manage composer profiles and information</p>
                    <a href="manage_composers.php" class="action-btn">
                        <i class="fas fa-cog"></i> Manage Composers
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>

</html>