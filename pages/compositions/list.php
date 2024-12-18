<?php
session_start();
require_once '../../includes/db.php';

// Enhanced error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// First, let's test if we can access the compositions table at all
$test_query = $conn->query("DESCRIBE compositions");
if (!$test_query) {
    echo "<pre>";
    echo "Error accessing compositions table: " . $conn->error . "\n";
    echo "Error code: " . $conn->errno . "\n";
    print_r($conn);
    echo "</pre>";
    exit();
}

// Let's try a simpler query first
try {
    $compositions = $conn->query("
        SELECT 
            c.composition_id,
            c.title,
            c.period,
            c.genre,
            c.difficulty_level,
            c.description,
            c.sheet_music_file,
            c.preview_file,
            comp.name as composer_name,
            (SELECT COUNT(*) FROM playlist_items pi WHERE pi.composition_id = c.composition_id) as playlist_count
        FROM compositions c
        LEFT JOIN composers comp ON c.composer_id = comp.composer_id
    ");

    if (!$compositions) {
        throw new Exception($conn->error);
    }

} catch (Exception $e) {
    echo "<pre>";
    echo "Database Error Details:\n";
    echo "Error Message: " . $e->getMessage() . "\n";
    echo "Error Code: " . $conn->errno . "\n";
    echo "Query State: " . $conn->sqlstate . "\n";
    print_r($conn->error_list);
    echo "</pre>";
    exit();
}

// Fetch user's playlists if logged in
$user_playlists = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $playlists = $conn->query("SELECT * FROM playlists WHERE user_id = $user_id ORDER BY name");
    while ($playlist = $playlists->fetch_assoc()) {
        $user_playlists[] = $playlist;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Compositions - Classical Music Hub</title>
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
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .compositions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            padding: 1rem;
        }

        .composition-card {
            background: var(--bg-light);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: all 0.3s ease;
            animation: fadeIn 0.5s ease forwards;
            opacity: 0;
        }

        .composition-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .composition-preview {
            height: 200px;
            background: var(--bg-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid var(--border-color);
        }

        .composition-preview i {
            font-size: 3rem;
            color: var(--accent);
        }

        .composition-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .composition-details {
            padding: 1.5rem;
        }

        .composition-title {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .composition-composer {
            color: var(--accent);
            font-size: 0.95rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .composition-metadata {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .composition-metadata span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .composition-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--text-primary);
        }

        .btn-primary {
            background: var(--accent);
        }

        .btn-primary:hover {
            background: #9b8265;
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: rgba(254, 245, 231, 0.1);
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

        /* Animation delays */
        .composition-card:nth-child(1) {
            animation-delay: 0.1s;
        }

        .composition-card:nth-child(2) {
            animation-delay: 0.2s;
        }

        .composition-card:nth-child(3) {
            animation-delay: 0.3s;
        }

        .composition-card:nth-child(4) {
            animation-delay: 0.4s;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
        }

        .modal-content {
            background: var(--bg-light);
            margin: 15% auto;
            padding: 2rem;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            width: 80%;
            max-width: 500px;
            position: relative;
            animation: slideIn 0.3s ease;
        }

        .close {
            position: absolute;
            right: 1rem;
            top: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-secondary);
        }

        .close:hover {
            color: var(--text-primary);
        }

        .playlist-list {
            margin-top: 1.5rem;
        }

        .playlist-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .playlist-item:last-child {
            border-bottom: none;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Update and add these styles in your <style> section */
        .main-header {
            background: var(--bg-light);
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
        }

        .header-left,
        .header-right {
            flex: 1;
            display: flex;
            align-items: center;
        }

        .header-right {
            justify-content: flex-end;
        }

        .header-center {
            flex: 2;
            text-align: center;
        }

        .header-center h1 {
            font-size: 1.75rem;
            color: var(--text-primary);
            margin: 0;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .nav-link {
            color: var(--text-secondary);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            border: 1px solid transparent;
        }

        .nav-link:hover {
            color: var(--text-primary);
            background: rgba(254, 245, 231, 0.05);
            border-color: var(--border-color);
        }

        .nav-link i {
            font-size: 1.1rem;
        }

        /* Update container to account for header */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            min-height: calc(100vh - 84px);
            /* Adjust based on header height */
        }

        /* Add responsive styles */
        @media (max-width: 768px) {
            .header-content {
                padding: 0 1rem;
                gap: 1rem;
            }

            .header-center h1 {
                font-size: 1.25rem;
            }

            .nav-link {
                padding: 0.5rem 0.75rem;
            }

            .nav-link span {
                display: none;
            }
        }

        .btn-favorite {
            background: none;
            border: none;
            color: #ff6b6b;
            cursor: pointer;
            padding: 0.5rem;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .btn-favorite:hover {
            transform: scale(1.1);
        }

        .btn-favorite.favorited {
            color: #ff6b6b;
        }

        .btn-favorite:not(.favorited) {
            color: #ccc;
        }

        /* Grid layout with sidebar */
        .page-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        .main-content {
            padding: 2rem;
            width: 100%;
        }

        .compositions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            padding: 0.5rem;
        }

        /* Sidebar styles */
        .sidebar {
            background: rgba(31, 31, 31, 0.8);
            padding: 2rem;
            border-right: 1px solid rgba(254, 245, 231, 0.1);
            backdrop-filter: blur(10px);
            height: 100vh;
            position: sticky;
            top: 0;
        }

        .sidebar-logo {
            color: var(--text-primary);
            font-size: 1.5rem;
            margin-bottom: 2rem;
            font-weight: bold;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu a {
            color: var(--text-secondary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-bottom: 0.5rem;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(254, 245, 231, 0.1);
            color: var(--text-primary);
        }

        .sidebar-menu i {
            width: 20px;
            text-align: center;
        }

        /* Main content layout improvements */
        .main-content {
            padding: 3rem;
            width: 100%;
            max-width: 1600px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 2.5rem;
        }

        .page-header h1 {
            font-size: 2.25rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .page-header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        /* Grid layout refinements */
        .compositions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2.5rem;
            padding: 0;
        }

        /* Card improvements */
        .composition-card {
            background: var(--bg-light);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .composition-preview {
            height: 220px;
            position: relative;
            overflow: hidden;
        }

        .composition-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .composition-card:hover .composition-preview img {
            transform: scale(1.05);
        }

        .composition-details {
            padding: 1.75rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .composition-title {
            font-size: 1.35rem;
            margin-bottom: 0.75rem;
            line-height: 1.4;
        }

        .composition-composer {
            margin-bottom: 1.25rem;
            font-size: 1rem;
        }

        .composition-metadata {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .composition-metadata span {
            font-size: 0.9rem;
        }

        .composition-actions {
            margin-top: auto;
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .btn-favorite {
            padding: 0.5rem;
            font-size: 1.4rem;
            border-radius: 8px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 107, 107, 0.1);
        }

        /* Empty state styling */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem 2rem;
            background: var(--bg-light);
            border-radius: 16px;
            border: 1px solid var(--border-color);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--accent);
            margin-bottom: 1.5rem;
        }

        .empty-state h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .main-content {
                padding: 2rem 1.5rem;
            }

            .compositions-grid {
                gap: 1.5rem;
            }

            .composition-preview {
                height: 180px;
            }

            .composition-details {
                padding: 1.25rem;
            }
        }

        @media (max-width: 480px) {
            .composition-metadata {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="page-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">Classical Music Hub</div>
            <ul class="sidebar-menu">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'educator'): ?>
                    <li><a href="../dashboard/educator/educators_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    </li>
                    <li><a href="#" class="active"><i class="fas fa-music"></i> Browse Music</a></li>
                    <li><a href="../dashboard/educator/composers/manage.php"><i class="fas fa-user"></i> Composers</a></li>
                    <li><a href="../dashboard/educator/timeline/manage.php"><i class="fas fa-clock"></i> Timeline</a></li>
                <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'enthusiast'): ?>
                    <li><a href="../dashboard/enthusiast/enthusiast_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    </li>
                    <li><a href="#" class="active"><i class="fas fa-music"></i> Browse Music</a></li>
                    <li><a href="../dashboard/enthusiast/playlists.php"><i class="fas fa-list"></i> My Playlists</a></li>
                    <li><a href="../library/favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
                <?php else: ?>
                    <li><a href="../../index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="#" class="active"><i class="fas fa-music"></i> Browse Music</a></li>
                    <li><a href="../../auth/login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                <?php endif; ?>
            </ul>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>Classical Music Library</h1>
                <p>Browse our collection of classical compositions</p>
            </div>
            <div class="compositions-grid">
                <?php if ($compositions->num_rows === 0): ?>
                    <div class="empty-state">
                        <i class="fas fa-music"></i>
                        <h2>No Compositions Available</h2>
                        <p>Check back later for new compositions</p>
                    </div>
                <?php else: ?>
                    <?php while ($composition = $compositions->fetch_assoc()): ?>
                        <div class="composition-card">
                            <div class="composition-preview">
                                <?php if ($composition['preview_file']): ?>
                                    <img src="<?php echo '../../' . htmlspecialchars($composition['preview_file']); ?>"
                                        alt="Preview">
                                <?php else: ?>
                                    <i class="fas fa-music"></i>
                                <?php endif; ?>
                            </div>
                            <div class="composition-details">
                                <h3 class="composition-title"><?php echo htmlspecialchars($composition['title']); ?></h3>
                                <?php if ($composition['composer_name']): ?>
                                    <div class="composition-composer">
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($composition['composer_name']); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="composition-metadata">
                                    <?php if (!empty($composition['period'])): ?>
                                        <span><i class="fas fa-clock"></i>
                                            <?php echo htmlspecialchars($composition['period']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($composition['genre'])): ?>
                                        <span><i class="fas fa-tag"></i>
                                            <?php echo htmlspecialchars($composition['genre']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($composition['difficulty_level'])): ?>
                                        <span><i class="fas fa-layer-group"></i>
                                            <?php echo htmlspecialchars($composition['difficulty_level']); ?></span>
                                    <?php endif; ?>
                                    <span><i class="fas fa-list"></i>
                                        In <?php echo (int) $composition['playlist_count']; ?> playlists</span>
                                </div>
                                <div class="composition-actions">
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <?php
                                        // Check if user has favorited this composition
                                        $fav_check = $conn->prepare("SELECT user_id FROM favorites WHERE user_id = ? AND composition_id = ?");
                                        $fav_check->bind_param("ii", $_SESSION['user_id'], $composition['composition_id']);
                                        $fav_check->execute();
                                        $is_favorited = $fav_check->get_result()->num_rows > 0;
                                        ?>
                                        <button class="btn-favorite <?php echo $is_favorited ? 'favorited' : ''; ?>"
                                            onclick="toggleFavorite(<?php echo $composition['composition_id']; ?>, this)">
                                            <i class="<?php echo $is_favorited ? 'fas' : 'far'; ?> fa-heart"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($composition['sheet_music_file']): ?>
                                        <a href="<?php echo '../../' . htmlspecialchars($composition['sheet_music_file']); ?>"
                                            class="btn btn-primary" target="_blank">
                                            <i class="fas fa-file-pdf"></i> View Sheet Music
                                        </a>
                                    <?php endif; ?>
                                    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'enthusiast'): ?>
                                        <button class="btn btn-secondary"
                                            onclick="addToPlaylist(<?php echo $composition['composition_id']; ?>)">
                                            <i class="fas fa-plus"></i> Add to Playlist
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Add this for debugging -->
    <?php
    if (isset($conn->error)) {
    }
    ?>

    <!-- Add this right before closing </body> tag -->
    <div id="playlistModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add to Playlist</h2>
            <div class="playlist-list">
                <?php if (empty($user_playlists)): ?>
                    <p>You don't have any playlists yet. <a href="../dashboard/enthusiast/playlists.php">Create one?</a></p>
                <?php else: ?>
                    <?php foreach ($user_playlists as $playlist): ?>
                        <div class="playlist-item">
                            <span><?php echo htmlspecialchars($playlist['name']); ?></span>
                            <button onclick="confirmAddToPlaylist(<?php echo $playlist['playlist_id']; ?>)"
                                class="btn btn-secondary">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        let selectedCompositionId = null;
        const modal = document.getElementById('playlistModal');

        function addToPlaylist(compositionId) {
            selectedCompositionId = compositionId;
            modal.style.display = "block";
        }

        function confirmAddToPlaylist(playlistId) {
            if (!selectedCompositionId) return;

            fetch('add_to_playlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    playlist_id: playlistId,
                    composition_id: selectedCompositionId
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Added to playlist successfully!');
                        modal.style.display = "none";
                    } else {
                        alert(data.message || 'Error adding to playlist');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error adding to playlist');
                });
        }

        // Close modal when clicking X or outside
        document.querySelector('.close').onclick = function () {
            modal.style.display = "none";
        }

        window.onclick = function (event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        function toggleFavorite(compositionId, button) {
            fetch('toggle_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    composition_id: compositionId
                })
            })
                .then(response => response.json())
                .then(data => {
                    console.log('Response:', data);
                    if (data.success) {
                        if (data.favorited) {
                            button.classList.add('favorited');
                            button.querySelector('i').classList.remove('far');
                            button.querySelector('i').classList.add('fas');
                        } else {
                            button.classList.remove('favorited');
                            button.querySelector('i').classList.remove('fas');
                            button.querySelector('i').classList.add('far');
                        }
                    } else {
                        console.error('Error message:', data.message);
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Error updating favorite status');
                });
        }
    </script>
</body>

</html>