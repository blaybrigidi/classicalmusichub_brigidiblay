<?php
$events_stmt = $conn->prepare("
    SELECT 
        e.*, 
        COUNT(DISTINCT ep.user_id) as participant_count,
        MAX(CASE WHEN ep.user_id = ? THEN 1 ELSE 0 END) as is_participant
    FROM community_events e
    LEFT JOIN event_participants ep ON e.event_id = ep.event_id
    WHERE e.community_id = ?
    GROUP BY e.event_id
    ORDER BY e.event_date ASC
");
$events_stmt->bind_param("ii", $_SESSION['user_id'], $community_id);
$events_stmt->execute();
$events = $events_stmt->get_result();
?>

<style>
    .create-event-btn {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        z-index: 100;
        padding: 1rem 2rem;
        background: linear-gradient(45deg, var(--accent), #d4b877);
        border-radius: 12px;
        color: #000;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }

    .create-event-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
    }

    .events-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
        margin-top: 1rem;
    }

    .event-card {
        background: var(--glass);
        border-radius: 12px;
        padding: 1.5rem;
        border: 1px solid var(--border);
        transition: all 0.3s ease;
    }

    .event-card:hover {
        transform: translateY(-2px);
        border-color: var(--accent);
        background: var(--glass-hover);
    }

    .event-title {
        font-size: 1.3rem;
        margin-bottom: 1rem;
        color: var(--primary);
    }

    .event-meta {
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
        margin-bottom: 1rem;
        color: var(--text-secondary);
    }

    .event-meta span {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .event-meta i {
        color: var(--accent);
        width: 20px;
    }

    .event-description {
        margin: 1rem 0;
        color: var(--text-secondary);
        line-height: 1.5;
    }

    .event-actions {
        margin-top: 1.5rem;
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

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.3s ease;
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
</style>

<div class="events-grid">
    <?php if ($events->num_rows === 0): ?>
        <div class="empty-state">
            <i class="fas fa-calendar"></i>
            <h3>No events yet</h3>
            <p>Be the first to create an event in this community!</p>
        </div>
    <?php else: ?>
        <?php while ($event = $events->fetch_assoc()): ?>
            <div class="event-card">
                <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                <div class="event-meta">
                    <span><i class="fas fa-calendar"></i>
                        <?php echo date('M j, Y g:i A', strtotime($event['event_date'])); ?></span>
                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?></span>
                    <span><i class="fas fa-users"></i> <?php echo $event['participant_count']; ?> participants</span>
                </div>
                <p class="event-description"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                <div class="event-actions">
                    <?php if (!$event['is_participant']): ?>
                        <a href="#" onclick="toggleParticipation('join', <?php echo $event['event_id']; ?>)" class="btn btn-join">
                            <i class="fas fa-plus"></i> Join Event
                        </a>
                    <?php else: ?>
                        <a href="#" onclick="toggleParticipation('leave', <?php echo $event['event_id']; ?>)" class="btn btn-leave">
                            <i class="fas fa-times"></i> Leave Event
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>

    <?php if ($community['is_member']): ?>
        <a href="events/create.php?community_id=<?php echo $community_id; ?>" class="create-event-btn">
            <i class="fas fa-plus"></i> Create Event
        </a>
    <?php endif; ?>
</div>

<script>
    function toggleParticipation(action, eventId) {
        fetch('toggle_participation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `event_id=${eventId}&action=${action}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
    }
</script>