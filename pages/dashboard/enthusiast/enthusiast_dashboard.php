<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'enthusiast') {
    header('Location: ../../../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$user_stmt = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// Fetch recent playlists
$playlists_stmt = $conn->prepare("
   SELECT p.*, COUNT(pi.composition_id) as track_count 
   FROM playlists p 
   LEFT JOIN playlist_items pi ON p.playlist_id = pi.playlist_id
   WHERE p.user_id = ? 
   GROUP BY p.playlist_id
   ORDER BY p.created_at DESC 
   LIMIT 5
");
$playlists_stmt->bind_param("i", $user_id);
$playlists_stmt->execute();
$recent_playlists = $playlists_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch favorite compositions
$favorites_stmt = $conn->prepare("
   SELECT c.title, comp.name as composer_name, c.audio_path, c.sheet_music_path 
   FROM favorites f 
   JOIN compositions c ON f.composition_id = c.composition_id 
   JOIN composers comp ON c.composer_id = comp.composer_id
   WHERE f.user_id = ? 
   ORDER BY f.created_at DESC 
   LIMIT 5
");
$favorites_stmt->bind_param("i", $user_id);
$favorites_stmt->execute();
$recent_favorites = $favorites_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch composers user interacts with most
$composer_interactions = $conn->prepare("
   SELECT DISTINCT c.composer_id, c.name, c.era, COUNT(*) as interaction_count
   FROM composers c
   JOIN compositions comp ON c.composer_id = comp.composer_id
   LEFT JOIN favorites f ON comp.composition_id = f.composition_id
   LEFT JOIN comments com ON c.composer_id = com.composer_id
   WHERE f.user_id = ? OR com.user_id = ?
   GROUP BY c.composer_id
   ORDER BY interaction_count DESC
   LIMIT 5
");
$composer_interactions->bind_param("ii", $user_id, $user_id);
$composer_interactions->execute();
$top_composers = $composer_interactions->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch recent activity
$activity_stmt = $conn->prepare("
   SELECT 'comment' as type, created_at, content, NULL as composition_title
   FROM comments 
   WHERE user_id = ?
   UNION
   SELECT 'favorite' as type, f.created_at, NULL as content, c.title as composition_title
   FROM favorites f
   JOIN compositions c ON f.composition_id = c.composition_id
   WHERE f.user_id = ?
   ORDER BY created_at DESC
   LIMIT 5
");
$activity_stmt->bind_param("ii", $user_id, $user_id);
$activity_stmt->execute();
$recent_activity = $activity_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Recent Favorites
$user_favorites = $conn->query("
    SELECT c.*, comp.name as composer_name
    FROM user_favorites uf
    JOIN compositions c ON uf.composition_id = c.composition_id
    LEFT JOIN composers comp ON c.composer_id = comp.composer_id
    WHERE uf.user_id = $user_id
    ORDER BY uf.created_at DESC
    LIMIT 5
");

// Top Composers (based on user's favorites)
$favorite_composers = $conn->query("
    SELECT comp.*, COUNT(*) as favorite_count
    FROM user_favorites uf
    JOIN compositions c ON uf.composition_id = c.composition_id
    JOIN composers comp ON c.composer_id = comp.composer_id
    WHERE uf.user_id = $user_id
    GROUP BY comp.composer_id
    ORDER BY favorite_count DESC
    LIMIT 5
");

// Recent Activity
$user_activity = $conn->query("
    (SELECT 'favorite' as type, uf.created_at, c.title, comp.name as composer_name
    FROM user_favorites uf
    JOIN compositions c ON uf.composition_id = c.composition_id
    LEFT JOIN composers comp ON c.composer_id = comp.composer_id
    WHERE uf.user_id = $user_id)
    UNION
    (SELECT 'playlist' as type, pi.added_at as created_at, c.title, comp.name as composer_name
    FROM playlist_items pi
    JOIN compositions c ON pi.composition_id = c.composition_id
    LEFT JOIN composers comp ON c.composer_id = comp.composer_id
    JOIN playlists p ON pi.playlist_id = p.playlist_id
    WHERE p.user_id = $user_id)
    ORDER BY created_at DESC
    LIMIT 10
");

function timeAgo($datetime)
{
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0)
        return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0)
        return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0)
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0)
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0)
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'just now';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dashboard - Classical Music Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>
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

    .dashboard-header {
        background: rgba(254, 245, 231, 0.05);
        padding: 1.5rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }

    .dashboard-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(254, 245, 231, 0.2), transparent);
    }

    .user-welcome {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .profile-pic {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        border: 2px solid var(--accent);
    }

    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .quick-action {
        background: rgba(254, 245, 231, 0.05);
        padding: 1.5rem;
        border-radius: 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 1px solid var(--border-color);
        text-decoration: none;
        color: var(--text-primary);
    }

    .quick-action:hover {
        transform: translateY(-2px);
        background: rgba(254, 245, 231, 0.08);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    }

    .quick-action i {
        font-size: 1.5rem;
        color: var(--accent);
        margin-bottom: 0.75rem;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .dashboard-card {
        background: rgba(254, 245, 231, 0.05);
        border-radius: 12px;
        padding: 1.5rem;
        position: relative;
        overflow: hidden;
        border: 1px solid var(--border-color);
        transition: all 0.3s ease;
    }

    .dashboard-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid rgba(254, 245, 231, 0.1);
    }

    .card-header h3 {
        color: var(--text-primary);
        font-weight: 500;
    }

    .btn {
        background: var(--accent);
        color: var(--text-primary);
        padding: 0.5rem 1rem;
        border-radius: 8px;
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 0.9rem;
        border: 1px solid var(--border-color);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(139, 115, 85, 0.3);
    }

    .card-content ul {
        list-style: none;
    }

    .card-content li {
        padding: 0.75rem;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .card-content li:hover {
        background: rgba(254, 245, 231, 0.05);
    }

    .card-content a {
        color: var(--text-primary);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .play-btn {
        color: var(--accent);
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .play-btn:hover {
        color: var(--text-primary);
    }

    @media (max-width: 768px) {
        .dashboard-container {
            grid-template-columns: 1fr;
        }

        .sidebar {
            display: none;
        }

        .dashboard-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-logo">Classical Music Hub</div>
            <ul class="sidebar-menu">
                <li><a href="#" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="playlists.php"><i class="fas fa-list"></i> My Playlists</a></li>
                <li><a href="../../compositions/list.php"><i class="fas fa-music"></i> Browse Music</a></li>
                <li><a href="../../library/favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
                <li><a href="../../library/timeline.php"><i class="fas fa-clock"></i> Timeline</a>
                <li><a href="../../communities/index.php"><i class="fas fa-users"></i> Community</a></li>
                <li><a href="../../settings/settings.php"><i class="fas fa-cog"></i> Settings</a></li>
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
                <div class="quick-action" onclick="location.href='../../playlists/create.php'">
                    <i class="fas fa-plus"></i>
                    <p>Create Playlist</p>
                </div>
                <div class="quick-action" onclick="location.href='../../library/sheet-music.php'">
                    <i class="fas fa-file-pdf"></i>
                    <p>Sheet Music</p>
                </div>
                <div class="quick-action" onclick="location.href='../../communities/index.php'">
                    <i class="fas fa-users"></i>
                    <p>Communities</p>
                </div>
                <div class="quick-action" onclick="location.href='my_discussions.php'">
                    <i class="fas fa-comments"></i>
                    <p>My Discussions</p>
                </div>
            </section>

            <div class="dashboard-grid">
                <!-- Playlists Card -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>My Playlists</h3>
                        <a href="../../playlists/create.php" class="btn">Create New</a>
                    </div>
                    <div class="card-content">
                        <ul>
                            <?php if (!empty($recent_playlists)):
                                $playlist_count = 0;
                                foreach ($recent_playlists as $playlist):
                                    if ($playlist_count >= 3)
                                        break;
                                    ?>
                                    <li>
                                        <div class="playlist-item">
                                            <a href="../../playlists/view.php?id=<?php echo $playlist['playlist_id']; ?>">
                                                <?php echo htmlspecialchars($playlist['name']); ?>
                                                <span class="track-count"><?php echo $playlist['track_count']; ?> tracks</span>
                                            </a>
                                        </div>
                                    </li>
                                    <?php
                                    $playlist_count++;
                                endforeach;
                            else: ?>
                                <li>No playlists created yet</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="card-header">
                        <h3>My Communities</h3>
                        <a href="../../communities/index.php" class="btn">Explore Communities</a>
                    </div>
                    <div class="card-content">
                        <ul>
                            <?php
                            $communities_stmt = $conn->prepare("
                SELECT c.* FROM communities c
                JOIN community_members cm ON c.community_id = cm.community_id
                WHERE cm.user_id = ?
                ORDER BY cm.joined_at DESC LIMIT 5
            ");
                            $communities_stmt->bind_param("i", $user_id);
                            $communities_stmt->execute();
                            $my_communities = $communities_stmt->get_result();

                            while ($community = $my_communities->fetch_assoc()):
                                ?>
                                <li>
                                    <a href="../communities/view.php?id=<?php echo $community['community_id']; ?>">
                                        <?php echo htmlspecialchars($community['name']); ?>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                </div>

                <!-- Favorites Card -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Recent Favorites</h3>
                        <a href="../../library/favorites.php" class="btn">View All</a>
                    </div>
                    <div class="card-content">
                        <ul>
                            <?php foreach ($recent_favorites as $favorite): ?>
                                <li>
                                    <div class="favorite-item">
                                        <span class="title"><?php echo htmlspecialchars($favorite['title']); ?></span>
                                        <span
                                            class="composer"><?php echo htmlspecialchars($favorite['composer_name']); ?></span>
                                        <?php if ($favorite['audio_path']): ?>
                                            <i class="fas fa-play play-btn"
                                                data-audio="<?php echo htmlspecialchars($favorite['audio_path']); ?>"></i>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Activity Feed -->



                <!-- Add this section to your dashboard -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Upcoming Events</h3>
                        <a href="../../communities/events/list.php" class="btn">View All</a>
                    </div>
                    <div class="card-content">
                        <?php
                        // Fetch upcoming events for communities the user is part of
                        $events_stmt = $conn->prepare("
                            SELECT 
                                e.*, 
                                c.name as community_name
                            FROM community_events e
                            JOIN communities c ON e.community_id = c.community_id
                            JOIN community_members cm ON c.community_id = cm.community_id
                            WHERE cm.user_id = ? 
                            AND e.event_date >= NOW()
                            ORDER BY e.event_date ASC
                            LIMIT 3
                        ");
                        $events_stmt->bind_param("i", $_SESSION['user_id']);
                        $events_stmt->execute();
                        $upcoming_events = $events_stmt->get_result();
                        ?>

                        <?php if ($upcoming_events->num_rows > 0): ?>
                            <ul class="events-list">
                                <?php while ($event = $upcoming_events->fetch_assoc()): ?>
                                    <li class="event-item">
                                        <div class="event-info">
                                            <h4><?php echo htmlspecialchars($event['title']); ?></h4>
                                            <p class="event-meta">
                                                <span><i class="fas fa-users"></i>
                                                    <?php echo htmlspecialchars($event['community_name']); ?></span>
                                                <span><i class="fas fa-calendar"></i>
                                                    <?php echo date('M j, Y g:i A', strtotime($event['event_date'])); ?></span>
                                            </p>
                                        </div>
                                        <a href="../communities/events/view.php?id=<?php echo $event['event_id']; ?>"
                                            class="btn">
                                            View Event
                                        </a>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <p>No upcoming events</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Favorites -->


                <!-- Top Composers -->
                <div class="dashboard-card">
                    <h2><i class="fas fa-user"></i> Your Top Composers</h2>
                    <div class="list-content">
                        <?php if (!empty($top_composers)): ?>
                            <?php foreach ($top_composers as $composer): ?>
                                <div class="list-item">
                                    <span class="title"><?php echo htmlspecialchars($composer['name']); ?></span>
                                    <span class="subtitle"><?php echo $composer['interaction_count']; ?> interactions</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="list-item">No favorite composers yet</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="dashboard-card">
                    <h2><i class="fas fa-clock"></i> Recent Activity</h2>
                    <div class="list-content">
                        <?php if ($user_activity && $user_activity->num_rows > 0): ?>
                            <?php while ($activity = $user_activity->fetch_assoc()): ?>
                                <div class="list-item">
                                    <i
                                        class="fas <?php echo $activity['type'] === 'favorite' ? 'fa-heart' : 'fa-music'; ?>"></i>
                                    <span class="title"><?php echo htmlspecialchars($activity['title']); ?></span>
                                    <span class="subtitle">
                                        <?php echo $activity['type'] === 'favorite' ? 'Favorited' : 'Added to playlist'; ?>
                                        <?php echo timeAgo($activity['created_at']); ?>
                                    </span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="list-item">No recent activity</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>


    <script>
        // Audio Player functionality
        let currentAudio = null;
        let currentPlayButton = null;

        function togglePlay(button, audioPath) {
            // If there's already something playing, stop it
            if (currentAudio) {
                currentAudio.pause();
                currentAudio.currentTime = 0;
                currentPlayButton.innerHTML = '<i class="fas fa-play"></i>';

                // If clicking same button, just stop
                if (currentPlayButton === button) {
                    currentAudio = null;
                    currentPlayButton = null;
                    return;
                }
            }

            // Create new audio element if not playing current audio
            if (!currentAudio || currentPlayButton !== button) {
                currentAudio = new Audio(audioPath);
                currentPlayButton = button;

                currentAudio.addEventListener('ended', function () {
                    button.innerHTML = '<i class="fas fa-play"></i>';
                    currentAudio = null;
                    currentPlayButton = null;
                });

                currentAudio.addEventListener('playing', function () {
                    button.innerHTML = '<i class="fas fa-pause"></i>';
                });

                currentAudio.addEventListener('error', function () {
                    alert('Error playing audio file');
                    button.innerHTML = '<i class="fas fa-play"></i>';
                    currentAudio = null;
                    currentPlayButton = null;
                });

                currentAudio.play();
            }
        }

        // Volume Control
        const volumeSlider = document.getElementById('volume-slider');
        if (volumeSlider) {
            volumeSlider.addEventListener('input', function () {
                if (currentAudio) {
                    currentAudio.volume = this.value;
                }
            });
        }

        // Add play buttons to tracks
        document.querySelectorAll('.play-btn').forEach(button => {
            button.addEventListener('click', function () {
                const audioPath = this.getAttribute('data-audio');
                if (audioPath) {
                    togglePlay(this, audioPath);
                }
            });
        });

        // Progress bar functionality
        let progressInterval;

        function updateProgress() {
            if (currentAudio && currentAudio.duration) {
                const progressBar = document.getElementById('audio-progress');
                const currentTime = document.getElementById('current-time');
                const duration = document.getElementById('duration');

                if (progressBar) {
                    const progress = (currentAudio.currentTime / currentAudio.duration) * 100;
                    progressBar.value = progress;
                }

                if (currentTime) {
                    currentTime.textContent = formatTime(currentAudio.currentTime);
                }

                if (duration) {
                    duration.textContent = formatTime(currentAudio.duration);
                }
            }
        }

        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            seconds = Math.floor(seconds % 60);
            return `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }

        // Add this HTML for the audio controls
        const audioControlsHTML = `
<div class="audio-controls" style="display: none;">
    <div class="progress-container">
        <span id="current-time">0:00</span>
        <input type="range" id="audio-progress" value="0" min="0" max="100">
        <span id="duration">0:00</span>
    </div>
    <div class="volume-container">
        <i class="fas fa-volume-up"></i>
        <input type="range" id="volume-slider" min="0" max="1" step="0.1" value="1">
    </div>
</div>
`;

        // Insert audio controls into the page
        document.body.insertAdjacentHTML('beforeend', audioControlsHTML);

        // Show/hide audio controls
        function toggleAudioControls(show) {
            const controls = document.querySelector('.audio-controls');
            if (controls) {
                controls.style.display = show ? 'block' : 'none';
            }
        }

        // Update progress bar when audio is playing
        document.addEventListener('DOMContentLoaded', function () {
            setInterval(updateProgress, 100);

            // Progress bar click handling
            const progressBar = document.getElementById('audio-progress');
            if (progressBar) {
                progressBar.addEventListener('click', function (e) {
                    if (currentAudio) {
                        const percent = e.offsetX / this.offsetWidth;
                        currentAudio.currentTime = percent * currentAudio.duration;
                    }
                });
            }
        });
    </script>
</body>

</html>