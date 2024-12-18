<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

// Fetch timeline events with composer information
$events_query = "
    SELECT 
        e.*,
        c.name as composer_name,
        c.birth_date,
        c.death_date,
        c.nationality,
        c.biography
    FROM timeline_events e
    LEFT JOIN composers c ON e.composer_id = c.composer_id
    ORDER BY e.event_date ASC
";

$events = $conn->query($events_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Timeline - Classical Music Hub</title>
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

        .timeline {
            position: relative;
            max-width: 1200px;
            margin: 4rem auto;
            padding: 2rem;
        }

        .timeline::after {
            content: '';
            position: absolute;
            width: 2px;
            background: var(--timeline-line);
            top: 0;
            bottom: 0;
            left: 50%;
            margin-left: -1px;
            box-shadow: 0 0 15px var(--timeline-line);
        }

        .timeline-item {
            padding: 10px 40px;
            position: relative;
            width: 50%;
            animation: fadeIn 0.5s ease-out forwards;
            opacity: 0;
        }

        .timeline-item::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            background: var(--timeline-dot);
            border-radius: 50%;
            top: 15px;
            z-index: 1;
            box-shadow: 0 0 10px var(--timeline-dot);
        }

        .left {
            left: 0;
        }

        .right {
            left: 50%;
        }

        .left::after {
            right: -10px;
        }

        .right::after {
            left: -10px;
        }

        .timeline-content {
            padding: 25px;
            background: rgba(201, 169, 89, 0.05);
            border: 1px solid var(--border);
            border-radius: 15px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .timeline-content:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
            box-shadow: 0 5px 15px rgba(201, 169, 89, 0.1);
        }

        .event-date {
            font-family: 'Didot', serif;
            font-size: 1.4rem;
            color: var(--accent);
            margin-bottom: 0.8rem;
            letter-spacing: 1px;
        }

        .event-title {
            font-size: 1.6rem;
            margin-bottom: 1rem;
            color: var(--primary);
            font-family: 'Didot', serif;
            letter-spacing: 0.5px;
        }

        .event-description {
            color: rgba(254, 245, 231, 0.7);
            line-height: 1.6;
        }

        .composer-info {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(201, 169, 89, 0.2);
        }

        .composer-details {
            font-family: 'Didot', serif;
            color: var(--accent);
        }

        .composer-details small {
            color: rgba(254, 245, 231, 0.7);
            font-style: italic;
        }

        .sidebar {
            background: rgba(201, 169, 89, 0.05);
            border-right: 1px solid var(--border);
            height: 100vh;
            position: fixed;
            padding: 2rem 1.5rem;
            width: 250px;
            backdrop-filter: blur(10px);
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
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.95), rgba(26, 26, 26, 0.95));
        }

        .page-container {
            display: flex;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--accent);
            text-decoration: none;
            margin-bottom: 2rem;
            font-family: 'Didot', serif;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            transform: translateX(-5px);
            color: var(--primary);
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

        @media screen and (max-width: 768px) {
            .timeline::after {
                left: 31px;
            }

            .timeline-item {
                width: 100%;
                padding-left: 70px;
                padding-right: 25px;
            }

            .timeline-item.right {
                left: 0;
            }

            .left::after,
            .right::after {
                left: 19px;
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }
    </style>
</head>

<body class="timeline-view">
    <div class="page-container">
        <?php include '../../includes/components/sidebar.php'; ?>

        <main class="main-content">
            <div class="timeline">
                <?php
                $count = 0;
                while ($event = $events->fetch_assoc()):
                    $position = $count % 2 == 0 ? 'left' : 'right';
                    ?>
                    <div class="timeline-item <?php echo $position; ?>"
                        style="animation-delay: <?php echo $count * 0.2; ?>s">
                        <div class="timeline-content">
                            <div class="event-date">
                                <?php echo date('Y', strtotime($event['event_date'])); ?>
                            </div>
                            <h3 class="event-title">
                                <?php echo htmlspecialchars($event['title']); ?>
                            </h3>
                            <p><?php echo htmlspecialchars($event['description']); ?></p>

                            <?php if ($event['composer_name']): ?>
                                <div class="composer-info">
                                    <div class="composer-details">
                                        <div><?php echo htmlspecialchars($event['composer_name']); ?></div>
                                        <small>
                                            <?php
                                            if ($event['birth_date'] && $event['death_date']) {
                                                echo date('Y', strtotime($event['birth_date'])) . ' - ' .
                                                    date('Y', strtotime($event['death_date']));
                                            }
                                            ?>
                                        </small>
                                        <?php if ($event['nationality']): ?>
                                            <div><?php echo htmlspecialchars($event['nationality']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                    $count++;
                endwhile;
                ?>
            </div>
        </main>
    </div>
</body>

</html>