<?php
session_start();
require_once '../../../includes/db.php';

$event_id = $_GET['id'] ?? 0;

// Fetch event details
$stmt = $conn->prepare("
    SELECT 
        e.*,
        u.username as creator_name,
        c.name as community_name,
        c.community_id,
        COUNT(DISTINCT ep.user_id) as participant_count,
        MAX(CASE WHEN ep.user_id = ? THEN 1 ELSE 0 END) as is_participant
    FROM community_events e
    JOIN users u ON e.created_by = u.user_id
    JOIN communities c ON e.community_id = c.community_id
    LEFT JOIN event_participants ep ON e.event_id = ep.event_id
    WHERE e.event_id = ?
    GROUP BY e.event_id
");

$stmt->bind_param("ii", $_SESSION['user_id'] ?? 0, $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
    header('Location: ../../communities/view.php?id=' . $event['community_id']);
    exit();
}

// Fetch participants
$participants_stmt = $conn->prepare("
    SELECT u.username 
    FROM event_participants ep
    JOIN users u ON ep.user_id = u.user_id
    WHERE ep.event_id = ?
    ORDER BY ep.joined_at ASC
");
$participants_stmt->bind_param("i", $event_id);
$participants_stmt->execute();
$participants = $participants_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($event['title']); ?> - Classical Music Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #fef5e7;
            --accent: #c9a959;
            --glass: rgba(254, 245, 231, 0.03);
            --border: rgba(254, 245, 231, 0.1);
            --glass-hover: rgba(254, 245, 231, 0.08);
            --text-secondary: rgba(254, 245, 231, 0.7);
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
            min-height: 100vh;
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            margin-bottom: 2rem;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            transform: translateX(-5px);
            color: var(--accent);
        }

        .event-card {
            background: var(--glass);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid var(--border);
        }

        .event-header {
            margin-bottom: 2rem;
        }

        .event-title {
            font-size: 2rem;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .event-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
        }

        .meta-item i {
            color: var(--accent);
            width: 20px;
        }

        .event-description {
            margin: 2rem 0;
            color: var(--text-secondary);
            line-height: 1.8;
        }

        .event-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-join {
            background: linear-gradient(45deg, var(--accent), #d4b877);
            color: #000;
            border: none;
        }

        .btn-leave {
            background: transparent;
            color: var(--primary);
            border: 1px solid var(--border);
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-join:hover {
            filter: brightness(1.1);
        }

        .btn-leave:hover {
            border-color: #ff4444;
            color: #ff4444;
        }

        .participants-section {
            margin-top: 2rem;
            background: var(--glass);
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid var(--border);
        }

        .participants-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .participant {
            text-align: center;
        }

        .participant-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--glass);
            margin: 0 auto 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--accent);
            border: 2px solid var(--border);
        }

        .participant-name {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="../../communities/view.php?id=<?php echo $event['community_id']; ?>&tab=events" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Events
        </a>

        <div class="event-card">
            <div class="event-header">
                <h1 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h1>
                <div class="event-meta">
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo date('F j, Y g:i A', strtotime($event['event_date'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($event['location']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-users"></i>
                        <span><?php echo $event['participant_count']; ?> participants</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-user"></i>
                        <span>Organized by <?php echo htmlspecialchars($event['creator_name']); ?></span>
                    </div>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="event-actions">
                        <?php if (!$event['is_participant']): ?>
                            <button class="btn btn-join" onclick="toggleParticipation('join')">
                                <i class="fas fa-plus"></i> Join Event
                            </button>
                        <?php else: ?>
                            <button class="btn btn-leave" onclick="toggleParticipation('leave')">
                                <i class="fas fa-times"></i> Leave Event
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="event-description">
                <h2>About this Event</h2>
                <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
            </div>
        </div>

        <div class="participants-section">
            <h2>Participants</h2>
            <div class="participants-grid">
                <?php while ($participant = $participants->fetch_assoc()): ?>
                    <div class="participant">
                        <div class="participant-avatar">
                            <?php echo strtoupper(substr($participant['username'], 0, 2)); ?>
                        </div>
                        <div class="participant-name">
                            <?php echo htmlspecialchars($participant['username']); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleParticipation(action) {
            fetch('../toggle_participation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `event_id=<?php echo $event_id; ?>&action=${action}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to process request');
                });
        }
    </script>
</body>

</html>