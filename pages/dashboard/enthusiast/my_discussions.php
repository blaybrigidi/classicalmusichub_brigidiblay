<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../../../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'enthusiast') {
    header('Location: ../../../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's discussions across all communities
$discussions_stmt = $conn->prepare("
    SELECT 
        p.*,
        c.name as community_name,
        c.community_id,
        COUNT(DISTINCT pr.reply_id) as reply_count,
        COALESCE(COUNT(DISTINCT pl.user_id), 0) as like_count
    FROM community_posts p
    JOIN communities c ON p.community_id = c.community_id
    LEFT JOIN post_replies pr ON p.post_id = pr.post_id
    LEFT JOIN post_likes pl ON p.post_id = pl.post_id
    WHERE p.user_id = ?
    GROUP BY p.post_id, c.name, c.community_id
    ORDER BY p.created_at DESC
");
$discussions_stmt->bind_param("i", $user_id);
$discussions_stmt->execute();
if (!$discussions_stmt->execute()) {
    echo "Error in discussions query: " . $conn->error;
}
$discussions = $discussions_stmt->get_result();

// Fetch discussions user has participated in (replied to)
$participated_stmt = $conn->prepare("
    SELECT DISTINCT
        p.*,
        c.name as community_name,
        c.community_id,
        COUNT(DISTINCT pr2.reply_id) as reply_count,
        COALESCE(COUNT(DISTINCT pl.user_id), 0) as like_count
    FROM community_posts p
    JOIN communities c ON p.community_id = c.community_id
    JOIN post_replies pr ON p.post_id = pr.post_id
    LEFT JOIN post_replies pr2 ON p.post_id = pr2.post_id
    LEFT JOIN post_likes pl ON p.post_id = pl.post_id
    WHERE pr.user_id = ? AND p.user_id != ?
    GROUP BY p.post_id, c.name, c.community_id
    ORDER BY p.created_at DESC
");
$participated_stmt->bind_param("ii", $user_id, $user_id);
$participated_stmt->execute();
if (!$participated_stmt->execute()) {
    echo "Error in participated query: " . $conn->error;
}
$participated = $participated_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Discussions - Classical Music Hub</title>
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
            background: var(--bg-dark);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-primary);
            text-decoration: none;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            color: var(--accent);
            transform: translateX(-5px);
        }

        .discussion-community a {
            color: var(--accent);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .discussion-community a:hover {
            color: var(--text-primary);
        }

        .discussion-content {
            display: block;
            text-decoration: none;
            color: var(--text-primary);
            font-size: 1.1rem;
            margin: 1rem 0;
            padding: 1rem;
            background: rgba(254, 245, 231, 0.02);
            border-radius: 8px;
            line-height: 1.8;
            transition: all 0.3s ease;
        }

        .discussion-content:hover {
            background: rgba(254, 245, 231, 0.05);
        }

        .discussion-section {
            background: var(--glass);
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid var(--border);
            margin-bottom: 2rem;
        }

        .discussion-card {
            background: rgba(254, 245, 231, 0.03);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .discussion-card:hover {
            transform: translateY(-2px);
            border-color: var(--accent);
            background: var(--glass-hover);
        }

        .discussion-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
            color: var(--text-secondary);
        }

        .discussion-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .discussion-meta i {
            color: var(--accent);
        }

        .section-title {
            font-size: 1.8rem;
            color: var(--text-primary);
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .section-title i {
            color: var(--accent);
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .discussion-meta {
                flex-direction: column;
                gap: 0.75rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="enthusiast_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="discussions-container">
            <section class="discussion-section">
                <h2 class="section-title">
                    <i class="fas fa-pen"></i> My Discussions
                </h2>
                <?php if ($discussions->num_rows > 0): ?>
                    <?php while ($discussion = $discussions->fetch_assoc()): ?>
                        <div class="discussion-card">
                            <div class="discussion-community">
                                <i class="fas fa-users"></i>
                                <a href="../../communities/view.php?id=<?php echo $discussion['community_id']; ?>">
                                    <?php echo htmlspecialchars($discussion['community_name']); ?>
                                </a>
                            </div>
                            <a href="../../communities/view.php?id=<?php echo $discussion['community_id']; ?>&post_id=<?php echo $discussion['post_id']; ?>"
                                class="discussion-content">
                                <?php echo nl2br(htmlspecialchars($discussion['content'])); ?>
                            </a>
                            <div class="discussion-meta">
                                <span><i class="fas fa-calendar"></i>
                                    <?php echo date('M j, Y', strtotime($discussion['created_at'])); ?></span>
                                <span><i class="fas fa-comment"></i> <?php echo $discussion['reply_count']; ?> replies</span>
                                <span><i class="fas fa-heart"></i> <?php echo $discussion['like_count']; ?> likes</span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>You haven't started any discussions yet.</p>
                <?php endif; ?>
            </section>

            <section class="discussion-section">
                <h2 class="section-title">
                    <i class="fas fa-reply"></i> Discussions I've Participated In
                </h2>
                <?php if ($participated->num_rows > 0): ?>
                    <?php while ($discussion = $participated->fetch_assoc()): ?>
                        <div class="discussion-card">
                            <div class="discussion-community">
                                <i class="fas fa-users"></i>
                                <a href="../../communities/view.php?id=<?php echo $discussion['community_id']; ?>">
                                    <?php echo htmlspecialchars($discussion['community_name']); ?>
                                </a>
                            </div>
                            <a href="../../communities/view.php?id=<?php echo $discussion['community_id']; ?>&post_id=<?php echo $discussion['post_id']; ?>"
                                class="discussion-content">
                                <?php echo nl2br(htmlspecialchars($discussion['content'])); ?>
                            </a>
                            <div class="discussion-meta">
                                <span><i class="fas fa-calendar"></i>
                                    <?php echo date('M j, Y', strtotime($discussion['created_at'])); ?></span>
                                <span><i class="fas fa-comment"></i> <?php echo $discussion['reply_count']; ?> replies</span>
                                <span><i class="fas fa-heart"></i> <?php echo $discussion['like_count']; ?> likes</span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>You haven't participated in any discussions yet.</p>
                <?php endif; ?>
            </section>
        </div>
    </div>
</body>

</html>