<?php
if (!isset($_SESSION)) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Meta tags, title, and styles should go here -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <style>
        .sidebar {
            background: var(--glass);
            border-right: 1px solid var(--border);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            padding: 2rem 1.5rem;
            backdrop-filter: blur(10px);
            z-index: 100;
            overflow-y: auto;
            transition: all 0.3s ease;
        }

        .sidebar-logo {
            font-family: 'Didot', serif;
            font-size: 1.5rem;
            color: var(--accent);
            text-align: center;
            margin-bottom: 3rem;
            letter-spacing: 1px;
            user-select: none;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
        }

        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }

        .sidebar-menu a {
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 1rem;
            font-weight: 400;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: var(--glass-hover);
            color: var(--accent);
        }

        .sidebar-menu a i {
            margin-right: 1rem;
            color: var(--accent);
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        @media screen and (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .page-container {
                grid-template-columns: 1fr;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <aside class="sidebar">
        <div class="sidebar-logo">Admin Panel</div>
        <ul class="sidebar-menu">
            <li>
                <a href="../admin/admin_dashboard.php"
                    class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="../admin/manage_users.php"
                    class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Users
                </a>
            </li>
            <li>
                <a href="../admin/manage_music.php"
                    class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_music.php' ? 'active' : ''; ?>">
                    <i class="fas fa-music"></i> Content
                </a>
            </li>
            <li>
                <a href="../admin/manage_composers.php"
                    class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_composers.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i> Composers
                </a>
            </li>
            <li>
                <a href="../auth/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </aside>

</body>

</html>