<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/choose_role.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$selected_category = isset($_GET['category']) ? $_GET['category'] : 'all';

$query = "
   SELECT 
       c.*, 
       COUNT(DISTINCT cm.user_id) as member_count,
       COUNT(DISTINCT d.discussion_id) as discussion_count,
       MAX(CASE WHEN cm.user_id = ? THEN 1 ELSE 0 END) as is_member
   FROM communities c
   LEFT JOIN community_members cm ON c.community_id = cm.community_id
   LEFT JOIN discussions d ON c.community_id = d.community_id
   WHERE 1=1";

if ($selected_category !== 'all') {
    $query .= " AND c.category = ?";
}

$query .= " GROUP BY c.community_id ORDER BY member_count DESC";

$stmt = $conn->prepare($query);

if ($selected_category !== 'all') {
    $stmt->bind_param("is", $user_id, $selected_category);
} else {
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$communities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stats = $conn->prepare("
    SELECT 
        (SELECT COUNT(DISTINCT c.community_id) 
         FROM communities c 
         JOIN community_members cm ON c.community_id = cm.community_id 
         WHERE cm.user_id = ?) as joined_communities,
        (SELECT COUNT(DISTINCT d.discussion_id) 
         FROM discussions d 
         WHERE d.user_id = ?) as user_discussions
    FROM dual
");
$stats->bind_param("ii", $user_id, $user_id);
$stats->execute();
$stats_result = $stats->get_result();
$stats_data = $stats_result->fetch_assoc();

$joined_communities = $stats_data['joined_communities'] ?? 0;
$user_discussions = $stats_data['user_discussions'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Communities - Classical Music Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #fef5e7;
            --accent: #c9a959;
            --glass: rgba(254, 245, 231, 0.03);
            --border: rgba(254, 245, 231, 0.1);
            --glass-hover: rgba(254, 245, 231, 0.08);
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
        }

        .navbar {
            background: var(--glass);
            backdrop-filter: blur(10px);
            padding: 1.5rem 3rem;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1600px;
            margin: 0 auto;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .back-btn {
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            color: var(--accent);
            transform: translateX(-5px);
        }

        .page-title {
            font-size: 1.8rem;
            background: linear-gradient(45deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .communities-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2.5rem;
            max-width: 1600px;
            margin: 0 auto;
            padding: 3rem;
        }

        .community-card {
            background: var(--glass);
            border-radius: 20px;
            padding: 2.5rem;
            border: 1px solid var(--border);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .community-card::before {
            content: '';
            position: absolute;
            inset: -50%;
            background: radial-gradient(circle, rgba(201, 169, 89, 0.1) 0%, transparent 70%);
            transform: rotate(0deg);
            transition: transform 0.6s ease;
        }

        .community-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
            background: var(--glass-hover);
        }

        .community-header {
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
            position: relative;
        }

        .community-emoji {
            font-size: 3rem;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--glass);
            border-radius: 16px;
            border: 2px solid var(--border);
        }

        .community-info {
            flex: 1;
        }

        .community-info h3 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(45deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .community-stats {
            display: flex;
            gap: 1.5rem;
            font-size: 0.9rem;
            color: rgba(254, 245, 231, 0.6);
        }

        .community-stats i {
            color: var(--accent);
        }

        .community-description {
            font-size: 1.1rem;
            line-height: 1.6;
            color: rgba(254, 245, 231, 0.8);
            margin: 1rem 0;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .community-actions {
            display: flex;
            gap: 1rem;
            margin-top: auto;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 12px;
            border: none;
            cursor: pointer !important;
            font-weight: 500;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            flex: 1;
            z-index: 1;
            position: relative;
        }

        .read-more,
        .join-btn {
            pointer-events: all !important;
            cursor: pointer !important;
        }

        .join-btn {
            background: linear-gradient(45deg, var(--accent), #d4b877);
            color: #000;
            transition: all 0.3s ease;
            cursor: pointer;
            pointer-events: auto;
        }

        .read-more {
            background: var(--glass);
            color: var(--primary);
            border: 1px solid var(--border);
            cursor: pointer;
            pointer-events: auto;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(201, 169, 89, 0.1);
        }

        .joined {
            position: relative;
            overflow: hidden;
            background: var(--glass) !important;
            color: var(--accent) !important;
            border: 1px solid var(--accent);
        }

        .joined span {
            display: inline-block;
            transition: transform 0.3s ease;
        }

        .joined .hover-text {
            position: absolute;
            left: 50%;
            transform: translateX(-50%) translateY(100%);
            transition: transform 0.3s ease;
        }

        .joined:hover span {
            transform: translateY(-100%);
        }

        .joined:hover .hover-text {
            transform: translateX(-50%) translateY(0);
        }

        .joined:hover {
            background: rgba(201, 169, 89, 0.1) !important;
            color: #ff4444 !important;
            border-color: #ff4444;
        }

        @media (max-width: 1200px) {
            .communities-grid {
                grid-template-columns: 1fr;
            }
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 1;
        }

        .modal-content {
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2.5rem;
            max-width: 600px;
            width: 90%;
            position: relative;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }

        .modal.show .modal-content {
            transform: translateY(0);
        }

        .close-modal {
            position: absolute;
            right: 1.5rem;
            top: 1.5rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--primary);
            transition: all 0.3s ease;
        }

        .close-modal:hover {
            color: var(--accent);
            transform: rotate(90deg);
        }

        .modal-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .modal-emoji {
            font-size: 3rem;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--glass);
            border-radius: 16px;
            border: 2px solid var(--border);
        }

        .modal-title {
            font-size: 2rem;
            background: linear-gradient(45deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .modal-stats {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            color: rgba(254, 245, 231, 0.6);
        }

        .modal-description {
            color: var(--primary);
            line-height: 1.8;
            margin-bottom: 2rem;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
        }

        .view-community {
            background: linear-gradient(45deg, var(--accent), #d4b877);
            color: #000;
            cursor: pointer;
            pointer-events: auto;
        }

        .read-more:hover {
            background: var(--glass-hover);
            border-color: var(--accent);
        }

        .join-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(201, 169, 89, 0.2);
        }

        .page-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            max-width: 1800px;
            margin: 0 auto;
            padding: 2rem;
        }

        .communities-sidebar {
            background: var(--glass);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid var(--border);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .sidebar-section {
            margin-bottom: 2rem;
        }

        .sidebar-section h3 {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: var(--accent);
        }

        .category-list,
        .your-communities {
            list-style: none;
        }

        .category-list li a,
        .your-communities li a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            color: var(--primary);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .category-list li.active a,
        .category-list li a:hover,
        .your-communities li a:hover {
            background: var(--glass-hover);
            color: var(--accent);
        }

        .welcome-section {
            background: var(--glass);
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 2.5rem;
            border: 1px solid var(--border);
        }

        .welcome-section h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .welcome-text {
            font-size: 1.1rem;
            line-height: 1.8;
            color: rgba(254, 245, 231, 0.8);
            margin-bottom: 2rem;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .feature {
            text-align: center;
            padding: 1.5rem;
            background: var(--glass-hover);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .feature:hover {
            transform: translateY(-5px);
        }

        .feature i {
            font-size: 2rem;
            color: var(--accent);
            margin-bottom: 1rem;
        }

        .feature h3 {
            margin-bottom: 0.5rem;
            color: var(--primary);
        }

        .feature p {
            color: rgba(254, 245, 231, 0.7);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        .stat-item {
            text-align: center;
            padding: 1.25rem;
            background: var(--glass-hover);
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 100px;
        }

        .stat-value {
            display: block;
            font-size: 1.5rem;
            color: var(--accent);
            margin-bottom: 0.25rem;
            white-space: nowrap;
        }

        .stat-label {
            font-size: 0.8rem;
            color: rgba(254, 245, 231, 0.7);
            white-space: nowrap;
        }

        @media (max-width: 1200px) {
            .page-container {
                grid-template-columns: 1fr;
            }

            .communities-sidebar {
                position: static;
                margin-bottom: 2rem;
            }
        }

        .feedback-message {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--glass);
            color: var(--primary);
            padding: 1rem 2rem;
            border-radius: 8px;
            border: 1px solid var(--accent);
            animation: slideIn 0.3s ease, fadeOut 0.3s ease 2.7s;
            z-index: 1000;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }

            to {
                opacity: 0;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="nav-content">
            <div class="nav-left">
                <a href="../dashboard/enthusiast/enthusiast_dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
                <h1 class="page-title">Musical Communities</h1>
            </div>
        </div>
    </nav>

    <div class="page-container">
        <aside class="communities-sidebar">
            <div class="sidebar-section">
                <h3>Categories</h3>
                <ul class="category-list">
                    <li class="<?php echo $selected_category === 'all' ? 'active' : ''; ?>">
                        <a href="?category=all"><i class="fas fa-globe"></i> All Communities</a>
                    </li>
                    <?php
                    $categories = ['Classical', 'Baroque', 'Romantic', 'Contemporary', 'Theory', 'Performance'];
                    $icons = [
                        'Classical' => 'violin',
                        'Baroque' => 'music',
                        'Romantic' => 'guitar',
                        'Contemporary' => 'drum',
                        'Theory' => 'book-open',
                        'Performance' => 'microphone'
                    ];

                    foreach ($categories as $category): ?>
                        <li class="<?php echo $selected_category === $category ? 'active' : ''; ?>">
                            <a href="?category=<?php echo urlencode($category); ?>">
                                <i class="fas fa-<?php echo $icons[$category]; ?>"></i>
                                <?php echo $category; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="sidebar-section">
                <h3>Your Communities</h3>
                <ul class="your-communities">
                    <?php
                    $user_communities = $conn->prepare("
                        SELECT c.* FROM communities c
                        JOIN community_members cm ON c.community_id = cm.community_id
                        WHERE cm.user_id = ?
                        ORDER BY cm.joined_at DESC
                        LIMIT 5
                    ");
                    $user_communities->bind_param("i", $user_id);
                    $user_communities->execute();
                    $joined_communities = $user_communities->get_result();

                    while ($community = $joined_communities->fetch_assoc()): ?>
                        <li>
                            <a href="view.php?id=<?php echo $community['community_id']; ?>">
                                <?php echo htmlspecialchars($community['name']); ?>
                            </a>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>

            <div class="sidebar-section">
                <h3>Quick Stats</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $stats_data['joined_communities'] ?? 0; ?></span>
                        <span class="stat-label">Communities</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $stats_data['user_discussions'] ?? 0; ?></span>
                        <span class="stat-label">Discussions</span>
                    </div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <div class="welcome-section">
                <h2>Welcome to Musical Communities</h2>
                <p class="welcome-text">
                    Join our vibrant community of classical music enthusiasts! Connect with fellow musicians,
                    share your passion for different musical periods, discuss techniques, and explore the rich
                    world of classical music together. Whether you're a performer, composer, or simply an
                    appreciator of classical music, there's a place for you here.
                </p>
                <div class="feature-grid">
                    <div class="feature">
                        <i class="fas fa-users"></i>
                        <h3>Connect</h3>
                        <p>Meet other classical music enthusiasts</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-comments"></i>
                        <h3>Discuss</h3>
                        <p>Share insights and experiences</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-graduation-cap"></i>
                        <h3>Learn</h3>
                        <p>Expand your musical knowledge</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-music"></i>
                        <h3>Explore</h3>
                        <p>Discover new compositions</p>
                    </div>
                </div>
            </div>

            <div class="communities-grid">
                <?php foreach ($communities as $community): ?>
                    <div class="community-card">
                        <div class="community-header">
                            <div class="community-emoji">
                                <?php
                                $emojis = ['🎵', '🎼', '🎹', '🎻', '🎺', '🎸'];
                                echo $emojis[array_rand($emojis)];
                                ?>
                            </div>
                            <div class="community-info">
                                <h3><?php echo htmlspecialchars($community['name']); ?></h3>
                                <div class="community-stats">
                                    <span><i class="fas fa-users"></i> <?php echo $community['member_count']; ?>
                                        members</span>
                                    <span><i class="fas fa-comments"></i> <?php echo $community['discussion_count']; ?>
                                        discussions</span>
                                    <span><i class="fas fa-calendar"></i> Created
                                        <?php echo date('M Y', strtotime($community['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                        <p class="community-description"><?php echo htmlspecialchars($community['description']); ?></p>
                        <div class="community-actions">
                            <button type="button" class="btn read-more"
                                onclick="showCommunityDetails(this.closest('.community-card'))"
                                data-community-id="<?php echo $community['community_id']; ?>">
                                <i class="fas fa-eye"></i> Read More
                            </button>
                            <button type="button"
                                class="btn join-btn <?php echo $community['is_member'] ? 'joined' : ''; ?>"
                                onclick="toggleMembership(<?php echo $community['community_id']; ?>, this)"
                                data-is-member="<?php echo $community['is_member']; ?>">
                                <?php echo $community['is_member'] ? 'Joined' : 'Join Community'; ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <div id="communityModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="modal-header">
                <div class="modal-emoji"></div>
                <h2 class="modal-title"></h2>
            </div>
            <div class="modal-stats"></div>
            <p class="modal-description"></p>
            <div class="modal-actions">
                <button class="btn view-community">View Community</button>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('communityModal');
        const closeModal = document.querySelector('.close-modal');

        function showCommunityDetails(card) {
            console.log('Showing details for card:', card); // Debug
            const emoji = card.querySelector('.community-emoji').innerHTML;
            const title = card.querySelector('h3').textContent;
            const stats = card.querySelector('.community-stats').innerHTML;
            const description = card.querySelector('.community-description').textContent;
            const communityId = card.querySelector('.read-more').dataset.communityId;

            modal.querySelector('.modal-emoji').innerHTML = emoji;
            modal.querySelector('.modal-title').textContent = title;
            modal.querySelector('.modal-stats').innerHTML = stats;
            modal.querySelector('.modal-description').textContent = description;
            modal.querySelector('.view-community').onclick = () => {
                window.location.href = `view.php?id=${communityId}`;
            };

            modal.classList.add('show');
        }

        function toggleMembership(communityId, button) {
            const isMember = button.getAttribute('data-is-member') === '1';
            const action = isMember ? 'leave' : 'join';

            fetch('toggle_membership.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `community_id=${communityId}&action=${action}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update button state
                        button.classList.toggle('joined');
                        button.textContent = isMember ? 'Join Community' : 'Joined';
                        button.setAttribute('data-is-member', isMember ? '0' : '1');

                        // Update member count
                        const memberCountEl = button.closest('.community-card').querySelector('.fa-users').parentElement;
                        let count = parseInt(memberCountEl.textContent);
                        count = isMember ? count - 1 : count + 1;
                        memberCountEl.textContent = `${count} members`;

                        // Show feedback message
                        const feedback = document.createElement('div');
                        feedback.className = 'feedback-message';
                        feedback.textContent = data.message;
                        document.body.appendChild(feedback);
                        setTimeout(() => feedback.remove(), 3000);

                        // Only redirect if joining
                        if (!isMember) {
                            setTimeout(() => {
                                window.location.href = `view.php?id=${communityId}`;
                            }, 500);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Only show alert for actual errors
                    if (!error.message.includes('Successfully')) {
                        const feedback = document.createElement('div');
                        feedback.className = 'feedback-message error';
                        feedback.textContent = 'Failed to process request';
                        document.body.appendChild(feedback);
                        setTimeout(() => feedback.remove(), 3000);
                    }
                });
        }
        // Modal close handlers
        closeModal.addEventListener('click', () => modal.classList.remove('show'));

        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.classList.remove('show');
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('show')) {
                modal.classList.remove('show');
            }
        });
    </script>
</body>

</html>