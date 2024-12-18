<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's favorite compositions
$favorites_stmt = $conn->prepare("
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
        f.created_at as favorited_at
    FROM favorites f
    JOIN compositions c ON f.composition_id = c.composition_id
    LEFT JOIN composers comp ON c.composer_id = comp.composer_id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
");

$favorites_stmt->bind_param("i", $user_id);
$favorites_stmt->execute();
$favorites = $favorites_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Favorites - Classical Music Hub</title>
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
        }

        .main-content {
            padding: 2rem;
            width: 100%;
            max-width: 1200px;
            margin: 0 2rem;
        }

        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(254, 245, 231, 0.1);
        }

        .page-header h1 {
            font-size: 2.5rem;
            background: linear-gradient(45deg, var(--text-primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .favorites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            padding: 0.5rem;
        }

        .composition-card {
            background: rgba(254, 245, 231, 0.03);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(254, 245, 231, 0.1);
            animation: fadeInUp 0.6s ease-out;
            animation-fill-mode: both;
        }

        .composition-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
            background: rgba(254, 245, 231, 0.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .composition-card:nth-child(2) {
            animation-delay: 0.1s;
        }

        .composition-card:nth-child(3) {
            animation-delay: 0.2s;
        }

        .composition-card:nth-child(4) {
            animation-delay: 0.3s;
        }

        .composition-card:nth-child(5) {
            animation-delay: 0.4s;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes heartBeat {
            0% {
                transform: scale(1);
            }

            14% {
                transform: scale(1.3);
            }

            28% {
                transform: scale(1);
            }

            42% {
                transform: scale(1.3);
            }

            70% {
                transform: scale(1);
            }
        }

        .favorite-animation {
            animation: heartBeat 1.3s ease-in-out;
        }

        .composition-preview {
            height: 160px;
            background: rgba(254, 245, 231, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .composition-preview i {
            font-size: 3rem;
            color: var(--accent);
            transition: all 0.3s ease;
        }

        .composition-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.3s ease;
        }

        .composition-card:hover .composition-preview img,
        .composition-card:hover .composition-preview i {
            transform: scale(1.1);
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
            color: var(--text-secondary);
            margin-bottom: 1rem;
            font-size: 0.9rem;
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
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--accent);
            color: var(--text-primary);
        }

        .btn-secondary {
            background: rgba(254, 245, 231, 0.1);
            color: var(--text-primary);
        }

        .btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
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
            color: var(--accent);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--text-secondary);
        }

        .favorited-at {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-top: 0.5rem;
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
                    <li><a href="../compositions/list.php"><i class="fas fa-music"></i> Browse Music</a></li>
                    <li><a href="../dashboard/educator/composers/manage.php"><i class="fas fa-user"></i> Composers</a></li>
                    <li><a href="../dashboard/educator/timeline/manage.php"><i class="fas fa-clock"></i> Timeline</a></li>
                <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'enthusiast'): ?>
                    <li><a href="../dashboard/enthusiast/enthusiast_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    </li>
                    <li><a href="../compositions/list.php"><i class="fas fa-music"></i> Browse Music</a></li>
                    <li><a href="../dashboard/enthusiast/playlists.php"><i class="fas fa-list"></i> My Playlists</a></li>
                    <li><a href="#" class="active"><i class="fas fa-heart"></i> Favorites</a></li>
                <?php else: ?>
                    <li><a href="../../index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="../compositions/list.php"><i class="fas fa-music"></i> Browse Music</a></li>
                    <li><a href="../../auth/login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                <?php endif; ?>
            </ul>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>My Favorites</h1>
                <p>Your collection of favorite classical compositions</p>
            </div>

            <?php if ($favorites->num_rows === 0): ?>
                <div class="empty-state">
                    <i class="fas fa-heart"></i>
                    <h3>No Favorites Yet</h3>
                    <p>Start adding compositions to your favorites to see them here!</p>
                </div>
            <?php else: ?>
                <div class="favorites-grid">
                    <?php while ($composition = $favorites->fetch_assoc()): ?>
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
                                    <?php if ($composition['period']): ?>
                                        <span><i class="fas fa-clock"></i>
                                            <?php echo htmlspecialchars($composition['period']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($composition['genre']): ?>
                                        <span><i class="fas fa-tag"></i>
                                            <?php echo htmlspecialchars($composition['genre']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($composition['difficulty_level']): ?>
                                        <span><i class="fas fa-layer-group"></i>
                                            <?php echo htmlspecialchars($composition['difficulty_level']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="composition-actions">
                                    <?php if ($composition['sheet_music_file']): ?>
                                        <a href="<?php echo '../../' . htmlspecialchars($composition['sheet_music_file']); ?>"
                                            class="btn btn-primary" target="_blank">
                                            <i class="fas fa-file-pdf"></i> View Sheet Music
                                        </a>
                                    <?php endif; ?>
                                    <button class="btn btn-secondary"
                                        onclick="toggleFavorite(<?php echo $composition['composition_id']; ?>, this)">
                                        <i class="fas fa-heart"></i> Remove
                                    </button>
                                </div>
                                <div class="favorited-at">
                                    Added to favorites on <?php echo date('M j, Y', strtotime($composition['favorited_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function toggleFavorite(compositionId, button) {
            const card = button.closest('.composition-card');
            button.querySelector('i').classList.add('favorite-animation');

            fetch('../compositions/toggle_favorite.php', {
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
                    if (data.success) {
                        // Fade out animation before removal
                        card.style.animation = 'fadeOut 0.5s ease-out forwards';
                        setTimeout(() => {
                            card.remove();
                            // If no more favorites, show empty state
                            const grid = document.querySelector('.favorites-grid');
                            if (!grid.children.length) {
                                location.reload(); // Reload to show empty state
                            }
                        }, 500);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating favorite status');
                });
        }

        // Remove animation class after animation ends
        document.addEventListener('animationend', function (e) {
            if (e.animationName === 'heartBeat') {
                e.target.classList.remove('favorite-animation');
            }
        });
    </script>
</body>

</html>