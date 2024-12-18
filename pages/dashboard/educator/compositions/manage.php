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

// Fetch compositions with composer names
$compositions = $conn->query("
    SELECT 
        c.*,
        comp.name as composer_name
    FROM compositions c
    LEFT JOIN composers comp ON c.composer_id = comp.composer_id
    ORDER BY c.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Compositions Management - Classical Music Hub</title>
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

        /* Sidebar styles */
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

        /* Main content styles */
        .main-content {
            padding: 2rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .page-header p {
            color: var(--text-secondary);
        }

        .compositions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            padding: 1rem;
        }

        .composition-card {
            background: var(--bg-light);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: all 0.3s ease;
            animation: fadeIn 0.5s ease forwards;
            opacity: 0;
        }

        .composition-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .composition-preview {
            width: 100%;
            height: 200px;
            background: var(--bg-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid var(--border-color);
        }

        .composition-preview i {
            font-size: 3rem;
            color: var(--accent);
        }

        .composition-details {
            padding: 1.5rem;
        }

        .composition-title {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .composition-composer {
            color: var(--accent);
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .composition-metadata {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .composition-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--text-primary);
        }

        .btn-primary {
            background: var(--accent);
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid var(--border-color);
        }

        .btn-delete {
            background: rgba(231, 76, 60, 0.8);
        }

        .add-composition-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            background: var(--accent);
            color: var(--text-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .add-composition-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Animation delays */
        .composition-card:nth-child(1) {
            animation-delay: 0.1s;
        }

        .composition-card:nth-child(2) {
            animation-delay: 0.2s;
        }

        .composition-card:nth-child(3) {
            animation-delay: 0.3s;
        }

        .composition-card:nth-child(4) {
            animation-delay: 0.4s;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-logo">Classical Music Hub</div>
            <ul class="sidebar-menu">
                <li><a href="../educators_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="../composers/manage.php"><i class="fas fa-music"></i> Manage Composers</a></li>
                <li><a href="../timeline/manage.php"><i class="fas fa-clock"></i> Timeline</a></li>
                <li><a href="manage.php" class="active"><i class="fas fa-file-audio"></i> Compositions</a></li>
                <li><a href="../../../settings/settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>Compositions Management</h1>
                <p>Upload and manage sheet music and compositions</p>
            </div>

            <div class="compositions-grid">
                <?php if ($compositions->num_rows === 0): ?>
                    <div class="empty-state">
                        <i class="fas fa-music"></i>
                        <h2>No Compositions Yet</h2>
                        <p>Start by adding your first composition</p>
                    </div>
                <?php else: ?>
                    <?php while ($composition = $compositions->fetch_assoc()): ?>
                        <div class="composition-card">
                            <div class="composition-preview">
                                <?php if ($composition['preview_file']): ?>
                                    <img src="<?php echo htmlspecialchars($composition['preview_file']); ?>" alt="Preview">
                                <?php else: ?>
                                    <i class="fas fa-music"></i>
                                <?php endif; ?>
                            </div>
                            <div class="composition-details">
                                <h3 class="composition-title"><?php echo htmlspecialchars($composition['title']); ?></h3>
                                <?php if ($composition['composer_name']): ?>
                                    <div class="composition-composer">
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($composition['composer_name']); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="composition-metadata">
                                    <span><i class="fas fa-clock"></i>
                                        <?php echo htmlspecialchars($composition['period']); ?></span>
                                    <span><i class="fas fa-tag"></i>
                                        <?php echo htmlspecialchars($composition['genre']); ?></span>
                                    <span><i class="fas fa-layer-group"></i>
                                        <?php echo htmlspecialchars($composition['difficulty_level']); ?></span>
                                </div>
                                <div class="composition-actions">
                                    <a href="edit.php?id=<?php echo $composition['composition_id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <button class="btn btn-delete"
                                        onclick="deleteComposition(<?php echo $composition['composition_id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>

            <a href="add.php" class="add-composition-btn">
                <i class="fas fa-plus"></i>
            </a>
        </main>
    </div>

    <script>
        function deleteComposition(compositionId) {
            if (confirm('Are you sure you want to delete this composition? This action cannot be undone.')) {
                fetch('delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ composition_id: compositionId })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error deleting composition: ' + data.message);
                        }
                    });
            }
        }
    </script>
</body>

</html>