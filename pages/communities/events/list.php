<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../../auth/login.php');
    exit();
}

// Fetch all upcoming events for communities the user is part of
$events_stmt = $conn->prepare("
    SELECT 
        e.*, 
        c.name as community_name,
        COUNT(ep.user_id) as participant_count
    FROM community_events e
    JOIN communities c ON e.community_id = c.community_id
    JOIN community_members cm ON c.community_id = cm.community_id
    LEFT JOIN event_participants ep ON e.event_id = ep.event_id
    WHERE cm.user_id = ? 
    AND e.event_date >= NOW()
    GROUP BY e.event_id
    ORDER BY e.event_date ASC
");
$events_stmt->bind_param("i", $_SESSION['user_id']);
$events_stmt->execute();
$events = $events_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Upcoming Events - Classical Music Hub</title>
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
            overflow-x: hidden;
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

        .events-header {
            background: rgba(254, 245, 231, 0.05);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .events-list {
            display: grid;
            gap: 1.5rem;
        }

        .event-card {
            background: rgba(254, 245, 231, 0.05);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .event-card:hover {
            transform: translateY(-2px);
            background: rgba(254, 245, 231, 0.08);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .event-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .event-meta {
            display: flex;
            gap: 1rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .event-meta i {
            color: var(--accent);
            width: 20px;
        }

        .event-description {
            margin-bottom: 1rem;
            color: var(--text-secondary);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--accent);
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(139, 115, 85, 0.3);
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-primary);
            text-decoration: none;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }

        .back-btn:hover {
            transform: translateX(-5px);
            color: var(--accent);
        }

        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-logo">Classical Music Hub</div>
            <ul class="sidebar-menu">
                <li><a href="../../dashboard/enthusiast/enthusiast_dashboard.php"><i class="fas fa-home"></i>
                        Dashboard</a></li>
                <li><a href="../../dashboard/enthusiast/playlists.php"><i class="fas fa-list"></i> My Playlists</a></li>
                <li><a href="../../compositions/list.php"><i class="fas fa-music"></i> Browse Music</a></li>
                <li><a href="../../library/favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
                <li><a href="../../communities/index.php" class="active"><i class="fas fa-users"></i> Community</a></li>
                <li><a href="../../settings/settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <a href="../../dashboard/enthusiast/enthusiast_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>

            <div class="events-header">
                <h1>Upcoming Events</h1>
            </div>

            <div class="events-list">
                <?php if ($events->num_rows > 0): ?>
                    <?php while ($event = $events->fetch_assoc()): ?>
                        <div class="event-card">
                            <h2 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h2>
                            <div class="event-meta">
                                <span><i class="fas fa-users"></i>
                                    <?php echo htmlspecialchars($event['community_name']); ?></span>
                                <span><i class="fas fa-calendar"></i>
                                    <?php echo date('M j, Y g:i A', strtotime($event['event_date'])); ?></span>
                                <span><i class="fas fa-user-friends"></i> <?php echo $event['participant_count']; ?>
                                    participants</span>
                            </div>
                            <p class="event-description"><?php echo htmlspecialchars($event['description']); ?></p>
                            <a href="view.php?id=<?php echo $event['event_id']; ?>" class="btn">
                                <i class="fas fa-info-circle"></i> Event Details
                            </a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No upcoming events found.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>