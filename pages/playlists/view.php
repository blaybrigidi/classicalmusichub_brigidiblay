<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

$playlist_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Fetch playlist details with track count and total duration
$playlist_stmt = $conn->prepare("
    SELECT 
        p.*,
        COUNT(DISTINCT pi.composition_id) as track_count,
        SEC_TO_TIME(SUM(TIME_TO_SEC(c.duration))) as total_duration
    FROM playlists p
    LEFT JOIN playlist_items pi ON p.playlist_id = pi.playlist_id
    LEFT JOIN compositions c ON pi.composition_id = c.composition_id
    WHERE p.playlist_id = ? AND p.user_id = ?
    GROUP BY p.playlist_id
");
$playlist_stmt->bind_param("ii", $playlist_id, $user_id);
$playlist_stmt->execute();
$playlist = $playlist_stmt->get_result()->fetch_assoc();

if (!$playlist) {
    header('Location: ../dashboard/enthusiast/playlists.php');
    exit();
}

// Fetch tracks in playlist
$tracks_stmt = $conn->prepare("
    SELECT 
        c.*,
        comp.name as composer_name,
        comp.era as composer_era,
        pi.position,
        pi.added_at
    FROM playlist_items pi
    JOIN compositions c ON pi.composition_id = c.composition_id
    JOIN composers comp ON c.composer_id = comp.composer_id
    WHERE pi.playlist_id = ?
    ORDER BY pi.position ASC
");
$tracks_stmt->bind_param("i", $playlist_id);
$tracks_stmt->execute();
$tracks = $tracks_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($playlist['name']); ?> - Classical Music Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #121212;
            --bg-light: #1e1e1e;
            --text-primary: #fef5e7;
            --text-secondary: #c4b69c;
            --accent: #8b7355;
            --border-color: #3a3a3a;
            --glass: rgba(254, 245, 231, 0.03);
            --glass-hover: rgba(254, 245, 231, 0.08);
            --border: rgba(254, 245, 231, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Futura', sans-serif;
            background: linear-gradient(135deg, #000000, #1a1a1a);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.6;
        }

        .page-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--bg-dark) 0%, rgba(15, 15, 15, 0.95) 100%);
        }

        .sidebar {
            background: var(--bg-light);
            padding: 2rem;
            border-right: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            height: 100vh;
        }

        .main-content {
            padding: 2rem;
            overflow-y: auto;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .playlist-header {
            background: var(--glass);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border);
        }

        .playlist-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, var(--text-primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .playlist-stats {
            display: flex;
            gap: 2rem;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .playlist-stats i {
            color: var(--accent);
            margin-right: 0.5rem;
        }

        .tracks-list {
            background: var(--glass);
            border-radius: 12px;
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .track-item {
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            gap: 1.5rem;
            padding: 1.25rem;
            border-bottom: 1px solid var(--border);
            align-items: center;
            transition: all 0.3s ease;
        }

        .track-item:hover {
            background: var(--glass-hover);
        }

        .track-number {
            color: var(--text-secondary);
            font-size: 0.9rem;
            width: 2rem;
            text-align: center;
        }

        .track-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .track-title {
            color: var(--text-primary);
            font-weight: 500;
            font-size: 1.1rem;
        }

        .track-composer {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .track-duration {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .track-actions {
            display: flex;
            gap: 1rem;
        }

        .action-btn {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.75rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-btn:hover {
            color: var(--accent);
            background: rgba(254, 245, 231, 0.1);
        }

        .sidebar-section {
            background: var(--glass);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid var(--border);
            margin-bottom: 1.5rem;
        }

        .sidebar-section h3 {
            color: var(--accent);
            font-size: 1.2rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .stat-card {
            background: rgba(254, 245, 231, 0.02);
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            color: var(--accent);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .btn {
            background: linear-gradient(45deg, var(--accent), #d4b877);
            color: #000;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
            width: 100%;
            justify-content: center;
        }

        .btn:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: linear-gradient(45deg, #ff4444, #ff6b6b);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
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

        @media (max-width: 768px) {
            .page-container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                display: none;
            }

            .track-item {
                grid-template-columns: auto 1fr auto;
            }

            .track-duration {
                display: none;
            }
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
            color: var(--accent);
            transform: translateX(-5px);
        }

        .back-btn i {
            font-size: 1.2rem;
        }
    </style>
</head>

<body>
    <div class="page-container">
        <aside class="sidebar">
            <div class="sidebar-logo">Classical Music Hub</div>
            <ul class="sidebar-menu">
                <li><a href="../dashboard/enthusiast/enthusiast_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                </li>
                <li><a href="../dashboard/enthusiast/playlists.php" class="active"><i class="fas fa-list"></i> My
                        Playlists</a></li>
                <li><a href="../compositions/list.php"><i class="fas fa-music"></i> Browse Music</a></li>
                <li><a href="../library/favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
                <li><a href="../communities/index.php"><i class="fas fa-users"></i> Community</a></li>
                <li><a href="../dashboard/enthusiast/settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <a href="../dashboard/enthusiast/playlists.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Playlists
            </a>

            <div class="playlist-header">
                <h1 class="playlist-title"><?php echo htmlspecialchars($playlist['name']); ?></h1>
                <div class="playlist-stats">
                    <span><i class="fas fa-music"></i> <?php echo $playlist['track_count']; ?> tracks</span>
                    <span><i class="fas fa-clock"></i> <?php echo $playlist['total_duration'] ?? '0:00:00'; ?> total
                        duration</span>
                    <span><i class="fas fa-calendar"></i> Created
                        <?php echo date('M j, Y', strtotime($playlist['created_at'])); ?></span>
                </div>
                <?php if ($playlist['description']): ?>
                    <p class="playlist-description"><?php echo nl2br(htmlspecialchars($playlist['description'])); ?></p>
                <?php endif; ?>
            </div>

            <div class="tracks-list">
                <?php if ($tracks->num_rows > 0): ?>
                    <?php $position = 1;
                    while ($track = $tracks->fetch_assoc()): ?>
                        <div class="track-item">
                            <div class="track-number"><?php echo $position++; ?></div>
                            <div class="track-info">
                                <div class="track-title"><?php echo htmlspecialchars($track['title']); ?></div>
                                <div class="track-composer">
                                    <?php echo htmlspecialchars($track['composer_name']); ?> •
                                    <?php echo htmlspecialchars($track['composer_era']); ?>
                                </div>
                            </div>
                            <div class="track-duration"><?php echo $track['duration']; ?></div>
                            <div class="track-actions">
                                <?php if ($track['audio_path']): ?>
                                    <button class="action-btn play-btn" onclick="playTrack('<?php echo $track['audio_path']; ?>')">
                                        <i class="fas fa-play"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ($track['sheet_music_path']): ?>
                                    <a href="<?php echo $track['sheet_music_path']; ?>" class="action-btn" target="_blank">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No tracks in this playlist yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        let currentAudio = null;
        let currentPlayButton = null;

        function playTrack(audioPath) {
            if (currentAudio) {
                currentAudio.pause();
                currentAudio.currentTime = 0;
                currentPlayButton.innerHTML = '<i class="fas fa-play"></i>';
            }

            const button = event.currentTarget;
            if (currentPlayButton === button) {
                currentAudio = null;
                currentPlayButton = null;
                return;
            }

            currentAudio = new Audio(audioPath);
            currentPlayButton = button;

            currentAudio.play();
            button.innerHTML = '<i class="fas fa-pause"></i>';

            currentAudio.addEventListener('ended', () => {
                button.innerHTML = '<i class="fas fa-play"></i>';
                currentAudio = null;
                currentPlayButton = null;
            });
        }

        function deletePlaylist(playlistId) {
            if (confirm('Are you sure you want to delete this playlist?')) {
                window.location.href = `delete.php?id=${playlistId}`;
            }
        }
    </script>
</body>

</html>