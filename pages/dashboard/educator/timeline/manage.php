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

// Fetch timeline events with composer names
$events = $conn->query("
    SELECT 
        e.*,
        c.name as composer_name
    FROM timeline_events e
    LEFT JOIN composers c ON e.composer_id = c.composer_id
    ORDER BY e.event_date ASC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Timeline Management - Classical Music Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #121212;
            --bg-light: #1e1e1e;
            --text-primary: #fef5e7;
            --text-secondary: #c4b69c;
            --accent: #8b7355;
            --border-color: #3a3a3a;
            --timeline-line: rgba(139, 115, 85, 0.3);
            --event-bg: rgba(254, 245, 231, 0.05);
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
            overflow-y: auto;
        }

        .page-header {
            margin-bottom: 2rem;
            padding: 0 1.5rem;
        }

        .page-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .page-header p {
            color: var(--text-secondary);
        }

        /* Timeline styles */
        .timeline-container {
            position: relative;
            padding: 3rem;
            margin: 2rem;
            background: var(--bg-light);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            overflow-x: auto;
        }

        .timeline {
            position: relative;
            padding: 2rem 0;
            min-width: max-content;
        }

        .timeline-event {
            position: relative;
            display: inline-block;
            width: 300px;
            margin-right: 100px;
            animation: fadeIn 0.5s ease forwards;
            opacity: 0;
        }

        .timeline-event:nth-child(even) {
            margin-top: 150px;
        }

        .timeline-event:nth-child(odd) {
            margin-bottom: 150px;
        }

        .event-year {
            position: absolute;
            width: 80px;
            height: 80px;
            background: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--text-primary);
            left: 50%;
            transform: translateX(-50%);
            box-shadow: 0 0 20px rgba(139, 115, 85, 0.3);
            z-index: 2;
        }

        .timeline-event:nth-child(odd) .event-year {
            top: -40px;
        }

        .timeline-event:nth-child(even) .event-year {
            bottom: -40px;
        }

        .event-content {
            background: var(--event-bg);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            position: relative;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .event-content:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .event-title {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .event-composer {
            color: var(--accent);
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .event-description {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .event-actions {
            margin-top: 1rem;
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            color: var(--text-primary);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: var(--accent);
        }

        .btn-delete {
            background: rgba(231, 76, 60, 0.8);
        }

        .add-event-btn {
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

        .add-event-btn:hover {
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

        /* Update the empty state styles */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
            background: var(--bg-light);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            margin: 2rem auto;
            max-width: 600px;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--accent);
        }

        .empty-state h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .empty-state p {
            color: var(--text-secondary);
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
                <li><a href="manage.php" class="active"><i class="fas fa-clock"></i> Timeline</a></li>
                <li><a href="../compositions/manage.php"><i class="fas fa-file-audio"></i> Compositions</a></li>
                <li><a href="../community/manage.php"><i class="fas fa-users"></i> Community</a></li>
                <li><a href="../../../settings/settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>Timeline Management</h1>
                <p>Create and manage historical events in classical music</p>
            </div>

            <div class="timeline-container">
                <div class="timeline">
                    <?php if ($events->num_rows === 0): ?>
                        <div class="empty-state">
                            <i class="fas fa-clock"></i>
                            <h2>No Timeline Events Yet</h2>
                            <p>Start by adding your first historical event</p>
                        </div>
                    <?php else: ?>
                        <?php while ($event = $events->fetch_assoc()): ?>
                            <div class="timeline-event">
                                <div class="event-content">
                                    <div class="event-date">
                                        <?php echo date('M j, Y', strtotime($event['event_date'])); ?>
                                    </div>
                                    <div class="event-dot"></div>
                                    <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                                    <?php if ($event['composer_name']): ?>
                                        <div class="event-composer">
                                            <i class="fas fa-music"></i> <?php echo htmlspecialchars($event['composer_name']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <p class="event-description"><?php echo htmlspecialchars($event['description']); ?></p>
                                    <div class="event-actions">
                                        <a href="edit_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <button class="btn btn-delete" onclick="deleteEvent(<?php echo $event['event_id']; ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>

            <a href="add_event.php" class="add-event-btn">
                <i class="fas fa-plus"></i>
            </a>
        </main>
    </div>

    <script>
        function deleteEvent(eventId) {
            if (confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
                fetch('delete_event.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ event_id: eventId })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error deleting event: ' + data.message);
                        }
                    });
            }
        }
    </script>
</body>

</html>