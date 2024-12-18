<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../../includes/db.php';
require_once '../../includes/config.php';

// Verify database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/choose_role.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$community_id = $_GET['id'] ?? 0;

if (!$community_id) {
    die("No community ID provided");
}

// Fetch community details
$community_stmt = $conn->prepare("
   SELECT c.*, u.username as creator_name, 
          COUNT(DISTINCT cm.user_id) as member_count,
          MAX(CASE WHEN cm.user_id = ? THEN 1 ELSE 0 END) as is_member
   FROM communities c
   LEFT JOIN users u ON c.created_by = u.user_id
   LEFT JOIN community_members cm ON c.community_id = cm.community_id
   WHERE c.community_id = ?
   GROUP BY c.community_id
");

if (!$community_stmt) {
    die("Prepare failed: " . $conn->error);
}

$community_stmt->bind_param("ii", $user_id, $community_id);

if (!$community_stmt->execute()) {
    die("Execute failed: " . $community_stmt->error);
}

$community = $community_stmt->get_result()->fetch_assoc();

if (!$community) {
    die("Community not found");
}

// Fetch posts for this community
$posts_query = "
    SELECT p.*, u.username
    FROM community_posts p
    JOIN users u ON p.user_id = u.user_id
    WHERE p.community_id = ?
    ORDER BY p.created_at DESC
";

$posts_stmt = $conn->prepare($posts_query);
$posts_stmt->bind_param("i", $community_id);
$posts_stmt->execute();
$posts = $posts_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($community['name']); ?> - Classical Music Hub</title>
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
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 2rem;
        }

        .community-header {
            display: flex;
            gap: 2rem;
            align-items: center;
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--border);
        }

        .community-image {
            width: 120px;
            height: 120px;
            border-radius: 20px;
            object-fit: cover;
            border: 2px solid var(--border);
        }

        .community-info h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .community-stats {
            display: flex;
            gap: 2rem;
            color: var(--primary);
            opacity: 0.8;
        }

        .community-stats i {
            color: var(--accent);
            margin-right: 0.5rem;
        }

        .community-description {
            font-size: 1.2rem;
            margin: 2rem 0;
            color: rgba(254, 245, 231, 0.8);
        }

        .posts-section {
            margin-top: 3rem;
        }

        .new-post {
            background: var(--glass);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border);
        }

        .post-textarea {
            width: 100%;
            background: rgba(254, 245, 231, 0.05);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1rem;
            color: var(--primary);
            font-size: 1rem;
            resize: vertical;
            min-height: 100px;
            margin-bottom: 1rem;
        }

        .post {
            background: var(--glass);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
        }

        .post:hover {
            transform: translateY(-2px);
            border-color: var(--accent);
            background: var(--glass-hover);
        }

        .post-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--accent);
            color: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .post-meta {
            font-size: 0.9rem;
            color: rgba(254, 245, 231, 0.6);
        }

        .post-content {
            font-size: 1.1rem;
            margin-top: 1rem;
        }

        .btn {
            background: linear-gradient(45deg, var(--accent), #d4b877);
            color: #000;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
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
            max-width: 1800px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .page-container {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 2rem;
            max-width: 1800px;
            margin: 0 auto;
            padding: 2rem;
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

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--glass);
            border-radius: 12px;
            border: 1px solid var(--border);
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

        .post-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
        }

        .action-btn {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background: var(--glass);
            color: var(--accent);
        }



        .members-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .member-item {
            position: relative;
        }

        .member-avatar {
            width: 100%;
            aspect-ratio: 1;
            border-radius: 8px;
            object-fit: cover;
            transition: all 0.3s ease;
        }

        .member-item:hover .member-avatar {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .view-all-link {
            color: var(--accent);
            text-decoration: none;
            display: inline-block;
            margin-top: 0.5rem;
            transition: all 0.3s ease;
        }

        .view-all-link:hover {
            text-decoration: underline;
        }

        .post-textarea {
            margin: 0;
            flex-grow: 1;
        }

        .page-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            max-width: 1800px;
            margin: 0 auto;
            padding: 2rem;
        }

        .post-header {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .post-time {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .post-author {
            font-weight: 500;
            color: var(--primary);
        }

        @media (max-width: 1200px) {
            .page-container {
                grid-template-columns: 1fr;
            }

            .community-sidebar {
                position: static;
                margin-top: 2rem;
            }
        }

        .reply-form {
            background: var(--glass);
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1rem;
            border: 1px solid var(--border);
        }

        .btn-secondary {
            background: transparent;
            color: var(--text-primary);
            border: 1px solid var(--border);
            padding: 1rem 2rem;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: rgba(254, 245, 231, 0.05);
            border-color: var(--accent);
        }

        .reply-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            justify-content: flex-end;
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

        .replies-section {
            margin-top: 1rem;
            padding-left: 2rem;
            border-left: 2px solid var(--border);
        }

        .reply {
            background: var(--glass);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
            border: 1px solid var(--border);
        }

        .reply-content {
            margin-top: 0.5rem;
            color: var(--text-primary);
        }

        .community-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            background: var(--glass);
            padding: 0.5rem;
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .community-tabs .tab {
            flex: 1;
            padding: 1rem;
            text-align: center;
            text-decoration: none;
            color: var(--primary);
            transition: all 0.3s ease;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .community-tabs .tab:hover {
            background: var(--glass-hover);
            color: var(--accent);
        }

        .community-tabs .tab.active {
            background: linear-gradient(45deg, var(--accent), #d4b877);
            color: #000;
        }

        .community-tabs .tab i {
            font-size: 1.1rem;
        }

        .content-section {
            transition: opacity 0.3s ease;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="nav-content">
            <div class="nav-left">
                <a href="index.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Communities
                </a>
            </div>
            <div class="nav-right">
                <?php if (!$community['is_member']): ?>
                    <button class="btn join-btn" onclick="toggleMembership(<?php echo $community_id; ?>, this)"
                        data-is-member="0">
                        <i class="fas fa-plus"></i> Join Community
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="page-container">
        <main class="main-content">
            <div class="community-header">
                <div class="community-emoji">
                    <?php
                    $emojis = ['🎵', '🎼', '🎹', '����', '🎺', '🎸'];
                    echo $emojis[array_rand($emojis)];
                    ?>
                </div>
                <div class="community-info">
                    <h1><?php echo htmlspecialchars($community['name']); ?></h1>
                    <div class="community-stats">
                        <span><i class="fas fa-users"></i> <?php echo $community['member_count']; ?> members</span>
                        <span><i class="fas fa-calendar"></i> Created
                            <?php echo date('M Y', strtotime($community['created_at'])); ?></span>
                        <span><i class="fas fa-comments"></i> <?php echo count($posts); ?> discussions</span>
                    </div>
                    <p class="community-description"><?php echo htmlspecialchars($community['description']); ?></p>
                </div>
            </div>

            <div class="community-tabs">
                <a href="#discussions" class="tab <?php echo !isset($_GET['tab']) ? 'active' : ''; ?>">
                    <i class="fas fa-comments"></i> Discussions
                </a>
                <a href="#events"
                    class="tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'events' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar"></i> Events
                </a>
                <a href="#members"
                    class="tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'members' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Members
                </a>
            </div>

            <script>
                // Handle tab switching
                document.querySelectorAll('.community-tabs .tab').forEach(tab => {
                    tab.addEventListener('click', function (e) {
                        e.preventDefault();
                        const tabId = this.getAttribute('href').substring(1);

                        // Update URL without reloading
                        const newUrl = `view.php?id=<?php echo $community_id; ?>${tabId !== 'discussions' ? '&tab=' + tabId : ''}`;
                        history.pushState({}, '', newUrl);

                        // Update active states
                        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                        this.classList.add('active');

                        // Show/hide content sections
                        document.querySelectorAll('.content-section').forEach(section => {
                            section.style.display = section.id === tabId ? 'block' : 'none';
                        });
                    });
                });
            </script>

            <div id="discussions" class="content-section"
                style="display: <?php echo !isset($_GET['tab']) || $_GET['tab'] === 'discussions' ? 'block' : 'none'; ?>">
                <div class="posts-section">
                    <?php if ($community['is_member']): ?>
                        <div class="new-post">
                            <form id="postForm">
                                <div class="post-header">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?>
                                    </div>
                                    <textarea class="post-textarea" placeholder="Start a discussion..." required></textarea>
                                </div>
                                <div class="post-actions">
                                    <button type="submit" class="btn">
                                        <i class="fas fa-paper-plane"></i> Post
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>

                    <div class="posts-list">
                        <?php if (empty($posts)): ?>
                            <div class="empty-state">
                                <i class="fas fa-comments"></i>
                                <h3>No discussions yet</h3>
                                <p>Be the first to start a discussion in this community!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($posts as $post): ?>
                                <div class="post" data-post-id="<?php echo $post['post_id']; ?>">
                                    <div class="post-header">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($post['username'], 0, 2)); ?>
                                        </div>
                                        <div class="post-meta">
                                            <div class="post-author"><?php echo htmlspecialchars($post['username']); ?></div>
                                            <div class="post-time">
                                                <?php echo date('M d, Y \a\t h:i A', strtotime($post['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="post-content">
                                        <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                                    </div>
                                    <div class="post-actions">
                                        <button class="action-btn like-btn">
                                            <i class="far fa-heart"></i> Like
                                        </button>
                                        <button class="action-btn reply-btn">
                                            <i class="far fa-comment"></i> Reply
                                        </button>
                                    </div>

                                    <?php
                                    $replies_stmt = $conn->prepare("
                                        SELECT r.*, u.username
                                        FROM post_replies r
                                        JOIN users u ON r.user_id = u.user_id
                                        WHERE r.post_id = ?
                                        ORDER BY r.created_at ASC
                                    ");
                                    $replies_stmt->bind_param("i", $post['post_id']);
                                    $replies_stmt->execute();
                                    $replies = $replies_stmt->get_result();

                                    if ($replies->num_rows > 0): ?>
                                        <div class="replies-section">
                                            <?php while ($reply = $replies->fetch_assoc()): ?>
                                                <div class="reply">
                                                    <div class="post-header">
                                                        <div class="user-avatar">
                                                            <?php echo strtoupper(substr($reply['username'], 0, 2)); ?>
                                                        </div>
                                                        <div class="post-meta">
                                                            <div class="post-author"><?php echo htmlspecialchars($reply['username']); ?>
                                                            </div>
                                                            <div class="post-time">
                                                                <?php echo date('M d, Y \a\t h:i A', strtotime($reply['created_at'])); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="reply-content">
                                                        <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="events" class="content-section"
                style="display: <?php echo isset($_GET['tab']) && $_GET['tab'] === 'events' ? 'block' : 'none'; ?>">


                <?php include 'events/community_events.php'; ?>
            </div>

            <div id="members" class="content-section"
                style="display: <?php echo isset($_GET['tab']) && $_GET['tab'] === 'members' ? 'block' : 'none'; ?>">
                <?php
                $members_stmt = $conn->prepare("
                SELECT u.username, u.user_id
                FROM community_members cm
                JOIN users u ON cm.user_id = u.user_id
                WHERE cm.community_id = ?
                ORDER BY cm.joined_at DESC
            ");
                $members_stmt->bind_param("i", $community_id);
                $members_stmt->execute();
                $members = $members_stmt->get_result();
                ?>

                <div class="members-grid">
                    <?php while ($member = $members->fetch_assoc()): ?>
                        <div class="member">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($member['username'], 0, 2)); ?>
                            </div>
                            <div class="member-name">
                                <?php echo htmlspecialchars($member['username']); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </main>


    </div>

    <script>
        // Post submission
        document.getElementById('postForm')?.addEventListener('submit', function (e) {
            e.preventDefault();
            const textarea = this.querySelector('textarea');
            const content = textarea.value;

            if (!content.trim()) return;

            fetch('create_post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `community_id=<?php echo $community_id; ?>&content=${encodeURIComponent(content)}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear textarea
                        textarea.value = '';

                        // Show success message
                        const feedback = document.createElement('div');
                        feedback.className = 'feedback-message';
                        feedback.textContent = 'Post created successfully!';
                        document.body.appendChild(feedback);

                        setTimeout(() => {
                            feedback.remove();
                            location.reload();
                        }, 1500);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to create post');
                });
        });

        // Like button functionality
        document.querySelectorAll('.like-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                this.classList.toggle('liked');
                const icon = this.querySelector('i');
                icon.classList.toggle('far');
                icon.classList.toggle('fas');
            });
        });

        // Update the reply button functionality
        document.querySelectorAll('.reply-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const post = this.closest('.post');
                const replyForm = document.createElement('div');
                replyForm.className = 'reply-form';
                replyForm.innerHTML = `
                    <div class="post-header">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?>
                        </div>
                        <textarea class="post-textarea" placeholder="Write a reply..."></textarea>
                    </div>
                    <div class="reply-actions">
                        <button class="btn-secondary cancel-reply">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button class="btn submit-reply">
                            <i class="fas fa-reply"></i> Reply
                        </button>
                    </div>
                `;

                // Remove any existing reply forms
                post.querySelectorAll('.reply-form').forEach(form => form.remove());

                // Add new reply form
                post.appendChild(replyForm);

                // Focus textarea
                replyForm.querySelector('textarea').focus();

                // Handle cancel
                replyForm.querySelector('.cancel-reply').addEventListener('click', () => {
                    replyForm.remove();
                });

                // Handle reply submission
                replyForm.querySelector('.submit-reply').addEventListener('click', () => {
                    const content = replyForm.querySelector('textarea').value;
                    if (!content.trim()) return;

                    const postId = post.dataset.postId;
                    console.log('Submitting reply for post:', postId); // Debug

                    fetch('create_reply.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `post_id=${postId}&content=${encodeURIComponent(content)}`
                    })
                        .then(response => response.json())
                        .then(data => {
                            console.log('Reply response:', data); // Debug
                            if (data.success) {
                                // Show success message
                                const feedback = document.createElement('div');
                                feedback.className = 'feedback-message';
                                feedback.textContent = data.message;
                                document.body.appendChild(feedback);

                                // Remove reply form
                                replyForm.remove();

                                // Reload after delay
                                setTimeout(() => {
                                    feedback.remove();
                                    location.reload();
                                }, 1500);
                            } else {
                                throw new Error(data.message || 'Failed to post reply');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert(error.message || 'Failed to post reply');
                        });
                });
            });
        });

        // Join/Leave community
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
                        location.reload();
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    </script>
</body>

</html>