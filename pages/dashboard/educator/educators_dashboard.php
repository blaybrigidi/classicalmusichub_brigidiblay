<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once dirname(__DIR__, 3) . '/includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'educator') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch educator details
$user_stmt = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// Fetch composers for dropdowns
$composers = $conn->query("SELECT * FROM composers ORDER BY name");

// Fetch recent composer updates
$recent_updates = $conn->query("
    SELECT name, updated_at, biography 
    FROM composers 
    ORDER BY updated_at DESC 
    LIMIT 5
");

// Fetch recent compositions
$recent_compositions = $conn->query("
    SELECT c.title, comp.name as composer_name, c.created_at
    FROM compositions c
    JOIN composers comp ON c.composer_id = comp.composer_id
    ORDER BY c.created_at DESC
    LIMIT 5
");

// Fetch recent timeline events
$recent_events = $conn->query("
    SELECT e.*, c.name as composer_name 
    FROM timeline_events e 
    LEFT JOIN composers c ON e.composer_id = c.composer_id 
    ORDER BY e.created_at DESC 
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Educator Dashboard - Classical Music Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-dark: #121212;
            --bg-light: #1e1e1e;
            --text-primary: #fef5e7;
            --text-secondary: #c4b69c;
            --accent: #8b7355;
            --border-color: #3a3a3a;
        }

        body {
            font-family: 'Futura', sans-serif;
            background: linear-gradient(135deg, #000000, #1a1a1a);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.6;
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--bg-primary) 0%, rgba(15, 15, 15, 0.95) 100%);
        }

        .sidebar {
            background: rgba(31, 31, 31, 0.8);
            padding: 2rem;
            border-right: 1px solid rgba(254, 245, 231, 0.1);
            backdrop-filter: blur(10px);
        }

        .sidebar-logo {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--accent-color);
            text-shadow: 0 0 10px rgba(155, 89, 182, 0.5);
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }

        .sidebar-menu a {
            color: var(--text-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .sidebar-menu a::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: var(--hover-color);
            transition: width 0.3s ease;
            z-index: -1;
        }

        .sidebar-menu a:hover::before,
        .sidebar-menu a.active::before {
            width: 100%;
        }

        .sidebar-menu i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
            color: var(--accent-color);
        }

        .main-content {
            padding: 2rem;
            overflow-y: auto;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: rgba(31, 31, 31, 0.5);
            padding: 1rem;
            border-radius: 12px;
        }

        .user-welcome {
            display: flex;
            align-items: center;
        }

        .profile-pic {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 1rem;
            border: 3px solid var(--accent-color);
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .quick-action {
            background: rgba(31, 31, 31, 0.8);
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid rgba(254, 245, 231, 0.1);
        }

        .quick-action:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }

        .quick-action i {
            font-size: 2rem;
            margin-bottom: 0.75rem;
            color: var(--accent-color);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .dashboard-card {
            background: rgba(254, 245, 231, 0.03);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(254, 245, 231, 0.1);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .dashboard-card:hover {
            background: rgba(254, 245, 231, 0.05);
            border-color: var(--accent);
            transform: translateY(-5px);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            border-bottom: 1px solid rgba(254, 245, 231, 0.1);
            padding-bottom: 0.5rem;
        }

        .card-content ul {
            list-style: none;
        }

        .card-content li {
            margin-bottom: 0.75rem;
            padding: 0.5rem;
            border-radius: 8px;
            transition: background 0.3s ease;
        }

        .card-content li:hover {
            background: rgba(254, 245, 231, 0.05);
        }

        .btn {
            background: var(--accent-color);
            color: var(--text-primary);
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(155, 89, 182, 0.3);
        }

        .play-btn {
            color: var(--accent-color);
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .play-btn:hover {
            color: #fef5e7;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                display: none;
                /* Consider replacing with a mobile menu */
            }
        }

        /* Audio Player Styling */
        .audio-controls {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: rgba(31, 31, 31, 0.9);
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(10px);
            z-index: 1000;
        }

        #audio-progress {
            flex-grow: 1;
            margin: 0 1rem;
            accent-color: var(--accent-color);
        }

        #volume-slider {
            width: 100px;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-logo">Classical Music Hub</div>
            <ul class="sidebar-menu">
                <li>
                    <a href="../educator/educators_dashboard.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'educators_dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="compositions/manage.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'list.php' ? 'active' : ''; ?>">
                        <i class="fas fa-music"></i> Browse Music
                    </a>
                </li>

                <li>
                    <a href="../educator/composers/manage.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-edit"></i> Manage Composers
                    </a>
                </li>
                <li>
                    <a href="../educator/timeline/manage.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage.php' ? 'active' : ''; ?>">
                        <i class="fas fa-clock"></i> Manage Timeline
                    </a>
                </li>
                <li>
                    <a href="../../settings/settings.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
                <li>
                    <a href="../../../auth/logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </aside>

        <main class="main-content">
            <header class="dashboard-header">
                <div class="user-welcome">
                    <h1>Welcome, <?php echo htmlspecialchars($user['username']); ?></h1>
                </div>
            </header>

            <section class="quick-actions">
                <div class="quick-action" onclick="location.href='composers/add.php'">
                    <i class="fas fa-plus"></i>
                    <p>Add Composer</p>
                </div>
                <div class="quick-action" onclick="location.href='timeline/add_event.php'">
                    <i class="fas fa-clock"></i>
                    <p>Add Timeline Event</p>
                </div>
                <div class="quick-action" onclick="location.href='compositions/add.php'">
                    <i class="fas fa-file-audio"></i>
                    <p>Upload Composition</p>
                </div>

            </section>

            <div class="dashboard-grid">
                <!-- Recent Updates -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Recent Composer Updates</h3>
                        <a href="composers/manage.php" class="btn">View All</a>
                    </div>
                    <div class="card-content">
                        <ul>
                            <?php while ($update = $recent_updates->fetch_assoc()): ?>
                                <li>
                                    <span><?php echo htmlspecialchars($update['name']); ?></span>
                                    <small><?php echo date('M d', strtotime($update['updated_at'])); ?></small>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                </div>

                <!-- Recent Compositions -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Recent Compositions</h3>
                        <a href="compositions/manage.php" class="btn">View All</a>
                    </div>
                    <div class="card-content">
                        <ul>
                            <?php
                            $recent_compositions = $conn->query("
                                SELECT c.title, comp.name as composer_name, c.created_at
                                FROM compositions c
                                LEFT JOIN composers comp ON c.composer_id = comp.composer_id
                                ORDER BY c.created_at DESC
                                LIMIT 5
                            ");
                            while ($composition = $recent_compositions->fetch_assoc()):
                                ?>
                                <li>
                                    <div class="item-info">
                                        <span
                                            class="item-title"><?php echo htmlspecialchars($composition['title']); ?></span>
                                        <?php if ($composition['composer_name']): ?>
                                            <small
                                                class="item-subtitle"><?php echo htmlspecialchars($composition['composer_name']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <small
                                        class="item-date"><?php echo date('M d', strtotime($composition['created_at'])); ?></small>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                </div>

                <!-- Timeline Events -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Recent Timeline Events</h3>
                        <a href="timeline/manage.php" class="btn">View All</a>
                    </div>
                    <div class="card-content">
                        <ul>
                            <?php while ($event = $recent_events->fetch_assoc()): ?>
                                <li>
                                    <div class="event-info">
                                        <span class="event-title"><?php echo htmlspecialchars($event['title']); ?></span>
                                        <?php if ($event['composer_name']): ?>
                                            <small
                                                class="event-composer"><?php echo htmlspecialchars($event['composer_name']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <small
                                        class="event-date"><?php echo date('M d', strtotime($event['event_date'])); ?></small>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Overview</h3>
                    </div>
                    <div class="card-content">
                        <ul>
                            <li>Total Composers: <?php echo $composers->num_rows; ?></li>
                            <li>Recent Updates: <?php echo $recent_updates->num_rows; ?></li>
                            <li>Timeline Events: <?php echo $recent_events->num_rows; ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>