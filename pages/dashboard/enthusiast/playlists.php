<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'enthusiast') {
    header('Location: ../../../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle playlist deletion
if (isset($_POST['delete_playlist'])) {
    $playlist_id = filter_var($_POST['delete_playlist'], FILTER_SANITIZE_NUMBER_INT);

    // First delete all playlist items
    $delete_items = $conn->prepare("DELETE FROM playlist_items WHERE playlist_id = ? AND playlist_id IN (SELECT playlist_id FROM playlists WHERE user_id = ?)");
    $delete_items->bind_param("ii", $playlist_id, $user_id);
    $delete_items->execute();

    // Then delete the playlist
    $delete_playlist = $conn->prepare("DELETE FROM playlists WHERE playlist_id = ? AND user_id = ?");
    $delete_playlist->bind_param("ii", $playlist_id, $user_id);

    if ($delete_playlist->execute()) {
        $message = "Playlist successfully deleted.";
    } else {
        $message = "Error deleting playlist.";
    }
}

// Fetch all playlists with track count
$playlists_stmt = $conn->prepare("
    SELECT 
        p.*, 
        COUNT(pi.composition_id) as track_count,
        u.username
    FROM playlists p 
    LEFT JOIN playlist_items pi ON p.playlist_id = pi.playlist_id
    JOIN users u ON p.user_id = u.user_id
    WHERE p.user_id = ?
    GROUP BY p.playlist_id
    ORDER BY p.created_at DESC
");
$playlists_stmt->bind_param("i", $user_id);
$playlists_stmt->execute();
$playlists = $playlists_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get total tracks across all playlists
$total_tracks = 0;
foreach ($playlists as $playlist) {
    $total_tracks += $playlist['track_count'];
}

// Get recent activity
$activity_stmt = $conn->prepare("
    SELECT p.name, p.created_at
    FROM playlists p
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
    LIMIT 5
");
$activity_stmt->bind_param("i", $user_id);
$activity_stmt->execute();
$activities = $activity_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Playlists - Classical Music Hub</title>
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

        .playlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .playlist-card {
            background: rgba(254, 245, 231, 0.03);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(254, 245, 231, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .playlist-card:hover {
            transform: translateY(-2px);
            border-color: #c9a959;
            background: rgba(254, 245, 231, 0.05);
        }

        .playlist-title {
            font-size: 1.2rem;
            color: #fef5e7;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .playlist-meta {
            color: #c4b69c;
            font-size: 0.9rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .playlist-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .playlist-meta i {
            color: #c9a959;
        }

        .create-playlist-btn {
            background: linear-gradient(45deg, #c9a959, #d4b877);
            color: #000;
            text-decoration: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .create-playlist-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
        }

        .sidebar {
            background: var(--bg-light);
            padding: 2rem;
            border-right: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            height: 100vh;
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
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
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
            color: #c9a959;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #c4b69c;
            font-size: 0.9rem;
        }

        .activity-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(254, 245, 231, 0.1);
            color: #c4b69c;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-time {
            font-size: 0.8rem;
            margin-top: 0.25rem;
            color: rgba(254, 245, 231, 0.5);
        }

        .action-btn {
            color: #c4b69c;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: none;
            background: transparent;
            cursor: pointer;
        }

        .action-btn:hover {
            background: rgba(254, 245, 231, 0.05);
            color: #c9a959;
        }

        .playlist-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(254, 245, 231, 0.1);
        }

        .recent-activity {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .activity-item i {
            color: #c9a959;
            margin-right: 0.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(254, 245, 231, 0.03);
            border-radius: 12px;
            border: 1px solid rgba(254, 245, 231, 0.1);
        }

        .empty-state i {
            font-size: 3rem;
            color: #c9a959;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #fef5e7;
        }

        .empty-state p {
            color: #c4b69c;
        }

        @media (max-width: 768px) {
            .page-container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="page-container">
        <aside class="sidebar">
            <div class="sidebar-logo">Classical Music Hub</div>
            <ul class="sidebar-menu">
                <li><a href="enthusiast_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="#" class="active"><i class="fas fa-list"></i> My Playlists</a></li>
                <li><a href="../../compositions/list.php"><i class="fas fa-music"></i> Browse Music</a></li>
                <li><a href="../../library/favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
                <li><a href="../../communities/index.php"><i class="fas fa-users"></i> Community</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <a href="../../playlists/create.php" class="create-playlist-btn">
                <i class="fas fa-plus"></i> Create New Playlist
            </a>

            <?php if (empty($playlists)): ?>
                <div class="empty-state">
                    <i class="fas fa-music"></i>
                    <h3>No Playlists Yet</h3>
                    <p>Create your first playlist to start organizing your favorite classical pieces!</p>
                </div>
            <?php else: ?>
                <div class="playlist-grid">
                    <?php foreach ($playlists as $playlist): ?>
                        <div class="playlist-card"
                            onclick="window.location.href='../../playlists/view.php?id=<?php echo $playlist['playlist_id']; ?>'">
                            <div class="playlist-header">
                                <div>
                                    <div class="playlist-title">
                                        <?php echo htmlspecialchars($playlist['name']); ?>
                                    </div>
                                    <div class="playlist-meta">
                                        <span><i class="fas fa-music"></i> <?php echo $playlist['track_count']; ?> tracks</span>
                                        <span><i class="fas fa-clock"></i> Created
                                            <?php echo date('M j, Y', strtotime($playlist['created_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="playlist-actions" onclick="event.stopPropagation();">
                                <a href="../../playlists/edit.php?id=<?php echo $playlist['playlist_id']; ?>"
                                    class="action-btn">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button class="action-btn" onclick="deletePlaylist(<?php echo $playlist['playlist_id']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function deletePlaylist(playlistId) {
            if (confirm('Are you sure you want to delete this playlist?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'delete_playlist';
                input.value = playlistId;

                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>

</html>