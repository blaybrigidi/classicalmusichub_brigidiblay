<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../../../../includes/config.php';
require_once '../../../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'educator') {
    header('Location: ../../../../auth/login.php');
    exit();
}

// Fetch all composers with their details
$composers = $conn->query("
    SELECT 
        c.*,
        COUNT(comp.composition_id) as composition_count,
        COUNT(DISTINCT f.user_id) as follower_count
    FROM composers c
    LEFT JOIN compositions comp ON c.composer_id = comp.composer_id
    LEFT JOIN composer_followers f ON c.composer_id = f.composer_id
    GROUP BY c.composer_id
    ORDER BY c.name ASC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Composers - Classical Music Hub</title>
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
            background: var(--bg-dark);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--bg-dark) 0%, rgba(15, 15, 15, 0.95) 100%);
        }

        .sidebar {
            background: var(--bg-light);
            padding: 2rem;
            border-right: 1px solid var(--border-color);
        }

        .sidebar-logo {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--text-primary);
            text-decoration: none;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu a {
            color: var(--text-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-bottom: 0.5rem;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(254, 245, 231, 0.1);
        }

        .sidebar-menu i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
            color: var(--accent);
        }

        .main-content {
            padding: 2rem;
            overflow-y: auto;
        }

        .composers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
        }

        .composer-card {
            background: rgba(254, 245, 231, 0.05);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .composer-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .composer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .composer-name {
            font-size: 1.25rem;
            color: var(--text-primary);
        }

        .composer-era {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .composer-stats {
            display: flex;
            gap: 1rem;
            margin: 1rem 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-primary);
        }

        .btn-edit {
            background: var(--accent);
        }

        .btn-delete {
            background: #e74c3c;
        }

        .add-composer {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--accent);
            color: var(--text-primary);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .add-composer:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
        }

        .page-header {
            background: rgba(254, 245, 231, 0.05);
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }

        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 600;
        }

        .page-header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(254, 245, 231, 0.05);
            border-radius: 12px;
            margin: 2rem;
            border: 2px dashed var(--border-color);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--accent);
            margin-bottom: 1.5rem;
            opacity: 0.8;
        }

        .empty-state h2 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .empty-state p {
            color: var(--text-secondary);
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }

        .btn-primary {
            background: var(--accent);
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .composer-card {
            background: rgba(254, 245, 231, 0.03);
            backdrop-filter: blur(10px);
            padding: 2rem;
        }

        .composer-name {
            font-size: 1.4rem;
            font-weight: 600;
        }

        .composer-era {
            background: rgba(139, 115, 85, 0.2);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
        }

        .composer-stats {
            background: rgba(254, 245, 231, 0.03);
            padding: 0.8rem;
            border-radius: 8px;
            margin: 1.5rem 0;
        }

        .composer-stats span {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .composer-stats i {
            color: var(--accent);
            font-size: 1.1rem;
        }

        .action-buttons {
            border-top: 1px solid var(--border-color);
            padding-top: 1.5rem;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 0.8rem 1.2rem;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .btn i {
            font-size: 1.1rem;
        }

        .btn-edit:hover {
            background: rgba(139, 115, 85, 0.8);
        }

        .btn-delete {
            background: rgba(231, 76, 60, 0.8);
        }

        .btn-delete:hover {
            background: rgba(231, 76, 60, 1);
        }

        .add-composer {
            width: 65px;
            height: 65px;
            font-size: 1.8rem;
            box-shadow: 0 4px 20px rgba(139, 115, 85, 0.4);
        }

        /* Add smooth transitions */
        .composer-card,
        .btn,
        .composer-stats,
        .composer-era {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Add hover effects */
        .composer-stats:hover {
            background: rgba(254, 245, 231, 0.05);
        }

        .composer-era:hover {
            background: rgba(139, 115, 85, 0.3);
        }

        /* Add responsive adjustments */
        @media (max-width: 1200px) {
            .composers-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 1.5rem;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .composer-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-logo">Classical Music Hub</div>
            <ul class="sidebar-menu">
                <li><a href="../educators_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="manage.php" class="active"><i class="fas fa-music"></i> Manage Composers</a></li>
                <li><a href="../timeline/manage.php"><i class="fas fa-clock"></i> Timeline</a></li>
                <li><a href="../compositions/manage.php"><i class="fas fa-file-audio"></i> Compositions</a></li>
                <li><a href="../community/manage.php"><i class="fas fa-users"></i> Community</a></li>
                <li><a href="../../../settings/settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>Manage Composers</h1>
                <p>Add, edit, and manage classical music composers</p>
            </div>

            <?php if ($composers->num_rows === 0): ?>
                <div class="empty-state">
                    <i class="fas fa-music"></i>
                    <h2>No Composers Yet</h2>
                    <p>Start by adding your first composer to the database</p>
                    <a href="add.php" class="btn btn-primary">Add Your First Composer</a>
                </div>
            <?php else: ?>
                <div class="composers-grid">
                    <?php while ($composer = $composers->fetch_assoc()): ?>
                        <div class="composer-card">
                            <div class="composer-header">
                                <h3 class="composer-name"><?php echo htmlspecialchars($composer['name']); ?></h3>
                                <span class="composer-era"><?php echo htmlspecialchars($composer['era']); ?></span>
                            </div>
                            <div class="composer-stats">
                                <span><i class="fas fa-music"></i> <?php echo $composer['composition_count']; ?> works</span>
                                <span><i class="fas fa-users"></i> <?php echo $composer['follower_count']; ?> followers</span>
                            </div>
                            <div class="action-buttons">
                                <a href="edit.php?id=<?php echo $composer['composer_id']; ?>" class="btn btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button class="btn btn-delete"
                                    onclick="deleteComposer(<?php echo $composer['composer_id']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>

            <a href="add.php" class="add-composer">
                <i class="fas fa-plus"></i>
            </a>
        </main>
    </div>

    <script>
        function deleteComposer(composerId) {
            if (confirm('Are you sure you want to delete this composer? This action cannot be undone.')) {
                fetch('delete_composer.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ composer_id: composerId })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error deleting composer: ' + data.message);
                        }
                    });
            }
        }
    </script>
</body>

</html>