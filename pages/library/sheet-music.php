<?php
session_start();
require_once '../../includes/db.php';

// Fetch all compositions that have sheet music
$query = "
    SELECT 
        c.composition_id,
        c.title,
        c.sheet_music_file,
        c.difficulty_level,
        comp.name as composer_name
    FROM compositions c
    LEFT JOIN composers comp ON c.composer_id = comp.composer_id
    WHERE c.sheet_music_file IS NOT NULL
    ORDER BY c.title ASC
";

$sheet_music = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Sheet Music Library - Classical Music Hub</title>
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
        }

        .nav-link:hover {
            color: var(--text-primary);
            background: rgba(254, 245, 231, 0.05);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .sheet-music-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            padding: 1rem;
        }

        .sheet-music-card {
            background: var(--bg-light);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 1.5rem;
            transition: all 0.3s ease;
            animation: fadeIn 0.5s ease forwards;
            opacity: 0;
        }

        .sheet-music-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .sheet-music-title {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .composer-name {
            color: var(--accent);
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }

        .metadata {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.3s ease;
            color: var(--text-primary);
        }

        .btn-primary {
            background: var(--accent);
        }

        .btn-primary:hover {
            background: #9b8265;
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

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--bg-light);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--accent);
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <header class="main-header">
        <div class="header-content">
            <a href="../dashboard/enthusiast/enthusiast_dashboard.php" class="nav-link">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <h1>Sheet Music Library</h1>
        </div>
    </header>

    <div class="container">
        <div class="sheet-music-grid">
            <?php if ($sheet_music->num_rows === 0): ?>
                <div class="empty-state">
                    <i class="fas fa-music"></i>
                    <h2>No Sheet Music Available</h2>
                    <p>Check back later for new sheet music</p>
                </div>
            <?php else: ?>
                <?php while ($item = $sheet_music->fetch_assoc()): ?>
                    <div class="sheet-music-card">
                        <h3 class="sheet-music-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                        <?php if ($item['composer_name']): ?>
                            <div class="composer-name">
                                <i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($item['composer_name']); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($item['difficulty_level']): ?>
                            <div class="metadata">
                                <i class="fas fa-layer-group"></i>
                                <?php echo htmlspecialchars($item['difficulty_level']); ?>
                            </div>
                        <?php endif; ?>
                        <a href="<?php echo '../../' . htmlspecialchars($item['sheet_music_file']); ?>" class="btn btn-primary"
                            target="_blank">
                            <i class="fas fa-file-pdf"></i> View Sheet Music
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>