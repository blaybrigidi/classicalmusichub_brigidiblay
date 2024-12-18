<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

// Fetch all composers with timeline events
$composers_query = "
    SELECT DISTINCT 
        c.composer_id,
        c.name,
        c.era,
        c.nationality,
        COUNT(e.event_id) as event_count,
        MIN(e.event_date) as earliest_event,
        MAX(e.event_date) as latest_event
    FROM composers c
    JOIN timeline_events e ON c.composer_id = e.composer_id
    GROUP BY c.composer_id
    ORDER BY c.name ASC
";

$composers = $conn->query($composers_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Timeline Selection - Classical Music Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #fef5e7;
            --accent: #c9a959;
            --glass: rgba(254, 245, 231, 0.03);
            --border: rgba(254, 245, 231, 0.1);
            --glass-hover: rgba(254, 245, 231, 0.08);
            --timeline-dot: #c9a959;
            --timeline-line: rgba(201, 169, 89, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #000000, #1a1a1a);
            color: var(--primary);
            font-family: 'Futura', sans-serif;
        }

        .page-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        .main-content {
            padding: 2rem;
        }

        .page-header {
            margin-bottom: 3rem;
        }

        .page-header h1 {
            font-family: 'Didot', serif;
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
            letter-spacing: 0.5px;
        }

        .page-header p {
            color: rgba(254, 245, 231, 0.7);
            font-size: 1.1rem;
        }

        .composer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2.5rem;
            padding: 0 2rem;
        }

        .composer-card {
            background: rgba(201, 169, 89, 0.05);
            border: 1px solid rgba(201, 169, 89, 0.2);
            border-radius: 15px;
            padding: 25px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .composer-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
            box-shadow: 0 5px 15px rgba(201, 169, 89, 0.1);
        }

        .composer-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--timeline-dot), transparent);
        }

        .composer-name {
            font-size: 1.6rem;
            color: var(--primary);
            margin-bottom: 1.2rem;
            letter-spacing: 0.5px;
            font-family: 'Didot', serif;
        }

        .composer-era {
            color: var(--accent);
            font-family: 'Didot', serif;
            font-size: 1.1rem;
            margin-bottom: 0.8rem;
            letter-spacing: 1px;
        }

        .composer-nationality {
            color: rgba(254, 245, 231, 0.7);
            font-size: 1rem;
            font-style: italic;
            margin-bottom: 1.2rem;
        }

        .timeline-info {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(201, 169, 89, 0.2);
            font-size: 1rem;
            color: rgba(254, 245, 231, 0.7);
        }

        .view-timeline {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--accent);
            text-decoration: none;
            margin-top: 1.5rem;
            font-size: 1rem;
            font-family: 'Didot', serif;
            letter-spacing: 0.5px;
        }

        .view-timeline:hover {
            color: var(--timeline-dot);
            transform: translateX(5px);
        }

        @media screen and (max-width: 1024px) {
            .page-container {
                grid-template-columns: 1fr;
            }

            .composer-grid {
                padding: 0 1rem;
            }
        }

        .sidebar {
            background: rgba(201, 169, 89, 0.05);
            border-right: 1px solid var(--border);
            height: 100vh;
            position: fixed;
            padding: 2rem 1.5rem;
            width: 250px;
        }

        .sidebar-logo {
            font-family: 'Didot', serif;
            font-size: 1.5rem;
            color: var(--accent);
            text-align: center;
            margin-bottom: 3rem;
            letter-spacing: 1px;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu a {
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            margin: 0.5rem 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(201, 169, 89, 0.1);
        }

        .sidebar-menu i {
            margin-right: 1rem;
            color: var(--accent);
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }

        .page-container {
            display: flex;
        }

        @media screen and (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <div class="page-container">
        <?php include '../../includes/components/sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>Composer Timelines</h1>
                <p>Select a composer to view their timeline of significant events and compositions</p>
            </div>

            <div class="composer-grid">
                <?php while ($composer = $composers->fetch_assoc()): ?>
                    <div class="composer-card"
                        onclick="window.location.href='view_timeline.php?composer_id=<?php echo $composer['composer_id']; ?>'">
                        <h2 class="composer-name"><?php echo htmlspecialchars($composer['name']); ?></h2>
                        <div class="composer-era"><?php echo htmlspecialchars($composer['era']); ?></div>
                        <div class="composer-nationality"><?php echo htmlspecialchars($composer['nationality']); ?></div>
                        <div class="timeline-info">
                            <span><?php echo $composer['event_count']; ?> events</span>
                            <span><?php echo date('Y', strtotime($composer['earliest_event'])); ?> -
                                <?php echo date('Y', strtotime($composer['latest_event'])); ?></span>
                        </div>
                        <a href="view_timeline.php?composer_id=<?php echo $composer['composer_id']; ?>"
                            class="view-timeline">
                            <i class="fas fa-clock"></i> View Timeline
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        </main>
    </div>
</body>

</html>